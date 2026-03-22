<?php

namespace App\Jobs;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\Chat\SupportAssistantResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSupportReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $conversationId,
        public int $userMessageId,
        public int $assistantMessageId,
    ) {
    }

    public function handle(SupportAssistantResponseService $responseService): void
    {
        $conversation = SupportConversation::query()
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($this->conversationId);

        $userMessage = SupportMessage::query()->findOrFail($this->userMessageId);
        $assistantMessage = SupportMessage::query()->findOrFail($this->assistantMessageId);

        $responseService->streamReply($conversation, $userMessage, $assistantMessage);
    }
}
