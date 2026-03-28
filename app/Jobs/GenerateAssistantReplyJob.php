<?php

namespace App\Jobs;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\Chat\AssistantResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAssistantReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $conversationId,
        public int $userMessageId,
        public int $assistantMessageId,
    ) {
    }

    public function handle(AssistantResponseService $responseService): void
    {
        $conversation = AssistantConversation::query()
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($this->conversationId);

        $userMessage = AssistantMessage::query()->findOrFail($this->userMessageId);
        $assistantMessage = AssistantMessage::query()->findOrFail($this->assistantMessageId);

        $responseService->streamReply($conversation, $userMessage, $assistantMessage);
    }
}
