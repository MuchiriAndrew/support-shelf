<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $conversationUuid,
        public array $payload,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel("assistant-chat.{$this->conversationUuid}");
    }

    public function broadcastAs(): string
    {
        return 'assistant.chat.message.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge([
            'conversation_uuid' => $this->conversationUuid,
        ], $this->payload);
    }
}
