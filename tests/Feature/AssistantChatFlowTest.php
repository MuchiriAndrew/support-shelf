<?php

namespace Tests\Feature;

use App\Jobs\GenerateAssistantReplyJob;
use App\Models\AssistantConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssistantChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chat_page_renders_with_the_live_shell(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('chat'))
            ->assertOk()
            ->assertSee('Recent chats')
            ->assertSee('Where should we begin?');
    }

    public function test_it_can_create_a_conversation_and_queue_a_message_turn(): void
    {
        Bus::fake();
        $user = User::factory()->create();

        $this->assertDatabaseCount('support_conversations', 0);

        $messageResponse = $this->actingAs($user)->postJson(route('chat.messages.start'), [
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
        $this->assertDatabaseHas('support_conversations', [
            'user_id' => $user->id,
        ]);

        Bus::assertDispatched(GenerateAssistantReplyJob::class);
    }

    public function test_a_user_cannot_load_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = AssistantConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'session_token' => "user-{$owner->id}",
            'title' => 'Private workspace',
            'status' => 'idle',
            'model' => 'gpt-5.4-mini',
            'last_message_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->getJson(route('chat.conversations.show', $conversation))
            ->assertNotFound();
    }

    public function test_a_user_can_delete_their_own_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = AssistantConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'session_token' => "user-{$user->id}",
            'title' => 'Disposable conversation',
            'status' => 'idle',
            'model' => 'gpt-5.4-mini',
            'last_message_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('chat.conversations.destroy', $conversation))
            ->assertOk()
            ->assertJson([
                'deleted' => true,
                'conversation_uuid' => $conversation->uuid,
            ]);

        $this->assertDatabaseMissing('support_conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_a_user_cannot_delete_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = AssistantConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'session_token' => "user-{$owner->id}",
            'title' => 'Private workspace',
            'status' => 'idle',
            'model' => 'gpt-5.4-mini',
            'last_message_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->deleteJson(route('chat.conversations.destroy', $conversation))
            ->assertNotFound();

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
        ]);
    }
}
