<?php

namespace App\Services\Chat;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use App\Services\Documents\TokenEstimator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AssistantChatService
{
    public function __construct(
        protected TokenEstimator $tokenEstimator,
    ) {
    }

    public function createConversation(User $user, ?string $title = null): AssistantConversation
    {
        return AssistantConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'session_token' => "user-{$user->id}",
            'title' => $title,
            'status' => 'idle',
            'model' => config('assistant.models.responses'),
            'last_message_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, AssistantConversation>
     */
    public function listConversations(User $user): Collection
    {
        return AssistantConversation::query()
            ->ownedBy($user)
            ->with(['messages' => fn ($query) => $query->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findConversation(User $user, string $conversationUuid): AssistantConversation
    {
        return AssistantConversation::query()
            ->ownedBy($user)
            ->where('uuid', $conversationUuid)
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->firstOrFail();
    }

    public function deleteConversation(User $user, string $conversationUuid): void
    {
        AssistantConversation::query()
            ->ownedBy($user)
            ->where('uuid', $conversationUuid)
            ->firstOrFail()
            ->delete();
    }

    /**
     * @return array{conversation: AssistantConversation, userMessage: AssistantMessage, assistantMessage: AssistantMessage}
     */
    public function startConversationTurn(User $user, string $content, ?string $title = null): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('A message is required before starting a conversation.');
        }

        return DB::transaction(function () use ($user, $content, $title): array {
            $conversation = $this->createConversation($user, $title);

            return $this->persistConversationTurn($conversation, $content);
        });
    }

    /**
     * @return array{conversation: AssistantConversation, userMessage: AssistantMessage, assistantMessage: AssistantMessage}
     */
    public function queueConversationTurn(AssistantConversation $conversation, string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('A message is required before continuing the conversation.');
        }

        return DB::transaction(fn (): array => $this->persistConversationTurn($conversation, $content));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeConversation(AssistantConversation $conversation, bool $includeMessages = true): array
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
            'preview' => $latestMessage ? Str::limit(Str::squish($latestMessage->content), 96, '...') : 'Ask a question to start building from your private knowledge base.',
            'messages' => $includeMessages
                ? $conversation->messages
                    ->sortBy('id')
                    ->values()
                    ->map(fn (AssistantMessage $message): array => $this->serializeMessage($message))
                    ->all()
                : [],
        ];
    }

    /**
     * @param  Collection<int, AssistantConversation>  $conversations
     * @return array<int, array<string, mixed>>
     */
    public function serializeConversationList(Collection $conversations): array
    {
        return $conversations
            ->map(fn (AssistantConversation $conversation): array => $this->serializeConversation($conversation, includeMessages: false))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(AssistantMessage $message): array
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
     * @return array{conversation: AssistantConversation, userMessage: AssistantMessage, assistantMessage: AssistantMessage}
     */
    protected function persistConversationTurn(AssistantConversation $conversation, string $content): array
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
            'model' => config('assistant.models.responses'),
            'last_message_at' => now(),
        ])->save();

        return [
            'conversation' => $conversation->fresh('messages'),
            'userMessage' => $userMessage->fresh(),
            'assistantMessage' => $assistantMessage->fresh(),
        ];
    }
}
