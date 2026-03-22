<?php

namespace App\Services\Chat;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\Documents\TokenEstimator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SupportChatService
{
    public function __construct(
        protected TokenEstimator $tokenEstimator,
    ) {
    }

    public function createConversation(string $sessionToken, ?string $title = null): SupportConversation
    {
        return SupportConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'session_token' => $sessionToken,
            'title' => $title,
            'status' => 'idle',
            'model' => config('support-assistant.models.responses'),
            'last_message_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, SupportConversation>
     */
    public function listConversations(string $sessionToken): Collection
    {
        return SupportConversation::query()
            ->where('session_token', $sessionToken)
            ->with(['messages' => fn ($query) => $query->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findConversation(string $sessionToken, string $conversationUuid): SupportConversation
    {
        return SupportConversation::query()
            ->where('session_token', $sessionToken)
            ->where('uuid', $conversationUuid)
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->firstOrFail();
    }

    /**
     * @return array{conversation: SupportConversation, userMessage: SupportMessage, assistantMessage: SupportMessage}
     */
    public function startConversationTurn(string $sessionToken, string $content, ?string $title = null): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('A message is required before starting a support turn.');
        }

        return DB::transaction(function () use ($sessionToken, $content, $title): array {
            $conversation = $this->createConversation($sessionToken, $title);

            return $this->persistConversationTurn($conversation, $content);
        });
    }

    /**
     * @return array{conversation: SupportConversation, userMessage: SupportMessage, assistantMessage: SupportMessage}
     */
    public function queueConversationTurn(SupportConversation $conversation, string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('A message is required before starting a support turn.');
        }

        return DB::transaction(fn (): array => $this->persistConversationTurn($conversation, $content));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeConversation(SupportConversation $conversation, bool $includeMessages = true): array
    {
        $conversation->loadMissing('messages');
        $latestMessage = $conversation->messages->last();

        return [
            'uuid' => $conversation->uuid,
            'title' => $conversation->title ?: 'New conversation',
            'status' => $conversation->status,
            'model' => $conversation->model,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
            'preview' => $latestMessage ? Str::limit(Str::squish($latestMessage->content), 96, '...') : 'Ask a product support question to get started.',
            'messages' => $includeMessages
                ? $conversation->messages
                    ->sortBy('id')
                    ->values()
                    ->map(fn (SupportMessage $message): array => $this->serializeMessage($message))
                    ->all()
                : [],
        ];
    }

    /**
     * @param  Collection<int, SupportConversation>  $conversations
     * @return array<int, array<string, mixed>>
     */
    public function serializeConversationList(Collection $conversations): array
    {
        return $conversations
            ->map(fn (SupportConversation $conversation): array => $this->serializeConversation($conversation, includeMessages: false))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'status' => $message->status,
            'response_id' => $message->response_id,
            'citations' => is_array($message->citations) ? $message->citations : [],
            'metadata' => is_array($message->metadata) ? $message->metadata : [],
            'created_at' => $message->created_at?->toIso8601String(),
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{conversation: SupportConversation, userMessage: SupportMessage, assistantMessage: SupportMessage}
     */
    protected function persistConversationTurn(SupportConversation $conversation, string $content): array
    {
        $conversation->refresh();

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $content,
            'status' => 'completed',
            'token_estimate' => $this->tokenEstimator->estimate($content),
        ]);

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => '',
            'status' => 'queued',
            'citations' => [],
            'metadata' => [],
        ]);

        $title = $conversation->title;

        if (! is_string($title) || trim($title) === '') {
            $title = Str::limit(Str::squish($content), 56, '...');
        }

        $conversation->forceFill([
            'title' => $title,
            'status' => 'queued',
            'model' => config('support-assistant.models.responses'),
            'last_message_at' => now(),
        ])->save();

        return [
            'conversation' => $conversation->fresh('messages'),
            'userMessage' => $userMessage->fresh(),
            'assistantMessage' => $assistantMessage->fresh(),
        ];
    }
}
