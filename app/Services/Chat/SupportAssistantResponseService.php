<?php

namespace App\Services\Chat;

use App\Events\SupportChatMessageUpdated;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\Documents\TokenEstimator;
use App\Services\Retrieval\SupportRetrievalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use RuntimeException;
use Throwable;

class SupportAssistantResponseService
{
    public function __construct(
        protected SupportRetrievalService $retrievalService,
        protected SupportPromptBuilder $promptBuilder,
        protected SupportChatService $chatService,
        protected TokenEstimator $tokenEstimator,
    ) {
    }

    public function streamReply(SupportConversation $conversation, SupportMessage $userMessage, SupportMessage $assistantMessage): void
    {
        $conversation->loadMissing('messages');

        if (! filled(config('openai.api_key')) || ! filled($this->model())) {
            throw new RuntimeException('OpenAI Responses API is not configured. Set OPENAI_API_KEY and OPENAI_RESPONSES_MODEL.');
        }

        $matches = $this->retrievalService->isConfigured()
            ? $this->retrievalService->search($userMessage->content)
            : collect();

        $citations = $this->formatCitations($matches);
        $assistantMessage->forceFill([
            'status' => 'streaming',
            'citations' => $citations,
            'metadata' => array_replace_recursive(is_array($assistantMessage->metadata) ? $assistantMessage->metadata : [], [
                'retrieval' => [
                    'matches' => count($citations),
                    'top_k' => (int) config('support-assistant.retrieval.top_k', 8),
                    'retrieved_at' => now()->toIso8601String(),
                ],
            ]),
        ])->save();

        $conversation->forceFill([
            'status' => 'streaming',
            'last_message_at' => now(),
        ])->save();

        $this->broadcast($conversation, [
            'type' => 'started',
            'message' => $this->chatService->serializeMessage($assistantMessage),
        ]);

        $buffer = '';
        $lastPersistedLength = 0;

        try {
            $stream = OpenAI::responses()->createStreamed([
                'model' => $this->model(),
                'instructions' => $this->promptBuilder->instructions(),
                'input' => [
                    [
                        'role' => 'developer',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $this->promptBuilder->developerMessage($matches, $this->conversationTranscript($conversation, $userMessage)),
                            ],
                        ],
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $userMessage->content,
                            ],
                        ],
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'text',
                    ],
                ],
                'store' => false,
            ]);

            foreach ($stream as $event) {
                /** @var CreateStreamedResponse $event */
                if ($event->event === 'response.output_text.delta') {
                    $delta = $event->response->delta;
                    $buffer .= $delta;

                    $this->broadcast($conversation, [
                        'type' => 'delta',
                        'message_id' => $assistantMessage->id,
                        'delta' => $delta,
                    ]);

                    if (mb_strlen($buffer) - $lastPersistedLength >= 160) {
                        $assistantMessage->forceFill([
                            'content' => $buffer,
                        ])->save();

                        $lastPersistedLength = mb_strlen($buffer);
                    }

                    continue;
                }

                if ($event->event === 'response.completed') {
                    $response = $event->response->response;
                    $finalText = $response->outputText ?? $buffer;

                    $assistantMessage->forceFill([
                        'content' => $finalText,
                        'status' => 'completed',
                        'response_id' => $response->id,
                        'token_estimate' => $this->tokenEstimator->estimate($finalText),
                        'metadata' => array_replace_recursive(is_array($assistantMessage->metadata) ? $assistantMessage->metadata : [], [
                            'response' => [
                                'id' => $response->id,
                                'model' => $response->model,
                                'status' => $response->status,
                                'usage' => $response->usage?->toArray(),
                                'completed_at' => now()->toIso8601String(),
                            ],
                        ]),
                    ])->save();

                    $conversation->forceFill([
                        'status' => 'idle',
                        'model' => $response->model,
                        'last_response_id' => $response->id,
                        'last_message_at' => now(),
                    ])->save();

                    $this->broadcast($conversation, [
                        'type' => 'completed',
                        'message' => $this->chatService->serializeMessage($assistantMessage->fresh()),
                        'conversation' => $this->chatService->serializeConversation($conversation->fresh('messages'), includeMessages: false),
                    ]);

                    return;
                }
            }

            throw new RuntimeException('The Responses API stream ended before a completed event was received.');
        } catch (Throwable $exception) {
            $assistantMessage->forceFill([
                'content' => $buffer,
                'status' => 'failed',
                'metadata' => array_replace_recursive(is_array($assistantMessage->metadata) ? $assistantMessage->metadata : [], [
                    'error' => [
                        'message' => $exception->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ],
                ]),
            ])->save();

            $conversation->forceFill([
                'status' => 'failed',
                'last_message_at' => now(),
            ])->save();

            $this->broadcast($conversation, [
                'type' => 'failed',
                'message' => $this->chatService->serializeMessage($assistantMessage->fresh()),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function model(): ?string
    {
        $model = config('support-assistant.models.responses');

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $matches
     * @return array<int, array<string, mixed>>
     */
    protected function formatCitations(Collection $matches): array
    {
        return $matches
            ->take(5)
            ->values()
            ->map(function (array $match): array {
                return [
                    'chunk_id' => $match['chunk_id'],
                    'title' => data_get($match, 'document.title', 'Untitled source'),
                    'source' => data_get($match, 'document.source'),
                    'document_type' => data_get($match, 'document.document_type'),
                    'canonical_url' => data_get($match, 'document.canonical_url'),
                    'excerpt' => Str::limit(trim((string) ($match['content'] ?? '')), 260, '...'),
                    'distance' => $match['distance'] ?? null,
                ];
            })
            ->all();
    }

    protected function conversationTranscript(SupportConversation $conversation, SupportMessage $userMessage): string
    {
        return $conversation->messages
            ->where('id', '<', $userMessage->id)
            ->whereIn('role', ['user', 'assistant'])
            ->sortBy('id')
            ->take(-8)
            ->map(function (SupportMessage $message): string {
                $label = $message->role === 'assistant' ? 'Assistant' : 'User';

                return "{$label}: ".Str::squish($message->content);
            })
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function broadcast(SupportConversation $conversation, array $payload): void
    {
        broadcast(new SupportChatMessageUpdated($conversation->uuid, $payload));
    }
}
