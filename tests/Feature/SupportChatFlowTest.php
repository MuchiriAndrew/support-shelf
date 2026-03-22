<?php

namespace Tests\Feature;

use App\Jobs\GenerateSupportReplyJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SupportChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chat_page_renders_with_the_live_shell(): void
    {
        $this->get(route('chat'))
            ->assertOk()
            ->assertSee('Recent chats')
            ->assertSee('Where should we begin?');
    }

    public function test_it_can_create_a_conversation_and_queue_a_message_turn(): void
    {
        Bus::fake();

        $this->assertDatabaseCount('support_conversations', 0);

        $messageResponse = $this->postJson(route('chat.messages.start'), [
            'content' => 'How do I reset my AirPods?',
        ]);

        $messageResponse
            ->assertStatus(202)
            ->assertJsonPath('conversation.status', 'queued')
            ->assertJsonPath('user_message.role', 'user')
            ->assertJsonPath('assistant_message.role', 'assistant')
            ->assertJsonPath('assistant_message.status', 'queued');

        $this->assertDatabaseCount('support_conversations', 1);
        $this->assertDatabaseCount('support_messages', 2);

        Bus::assertDispatched(GenerateSupportReplyJob::class);
    }
}
