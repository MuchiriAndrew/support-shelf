<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\Chat\SupportAssistantResponseService;
use App\Services\Retrieval\SupportRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use Tests\TestCase;

class SupportAssistantResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_streams_a_grounded_reply_into_the_assistant_message(): void
    {
        config()->set('broadcasting.default', 'null');
        config()->set('openai.api_key', 'test-key');
        config()->set('support-assistant.models.responses', 'gpt-5.4-mini');

        $this->mock(SupportRetrievalService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('search')->once()->andReturn(collect([
                [
                    'chunk_id' => 42,
                    'distance' => 0.12,
                    'content' => 'To reset your AirPods, put them in the case and hold the setup button.',
                    'document' => [
                        'title' => 'How to reset your AirPods and AirPods Pro',
                        'source' => 'Apple AirPods Support',
                        'document_type' => 'support_page',
                        'canonical_url' => 'https://support.apple.com/en-us/118531',
                    ],
                ],
            ]));
        });

        OpenAI::fake([
            CreateStreamedResponse::fake($this->streamFixture()),
        ]);

        $conversation = SupportConversation::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'session_token' => 'test-session',
            'title' => 'AirPods reset',
            'status' => 'queued',
            'model' => 'gpt-5.4-mini',
            'last_message_at' => now(),
        ]);

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'How do I reset my AirPods?',
            'status' => 'completed',
            'token_estimate' => 8,
        ]);

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => '',
            'status' => 'queued',
        ]);

        app(SupportAssistantResponseService::class)->streamReply($conversation->fresh('messages'), $userMessage->fresh(), $assistantMessage->fresh());

        $assistantMessage = $assistantMessage->fresh();
        $conversation = $conversation->fresh();

        $this->assertSame('completed', $assistantMessage->status);
        $this->assertStringContainsString('Hi there! How can I assist you today?', $assistantMessage->content);
        $this->assertNotEmpty($assistantMessage->citations);
        $this->assertSame('idle', $conversation->status);
        $this->assertNotNull($assistantMessage->response_id);
    }

    /**
     * @return resource
     */
    protected function streamFixture()
    {
        $resource = fopen('php://temp', 'r+');

        fwrite($resource, <<<STREAM
data: {"type":"response.created","response":{"id":"resp_test_123","object":"response","created_at":1741290958,"status":"in_progress","error":null,"incomplete_details":null,"instructions":"You are a helpful assistant.","max_output_tokens":null,"model":"gpt-5.4-mini","output":[],"parallel_tool_calls":true,"previous_response_id":null,"reasoning":{"effort":null,"summary":null},"store":false,"temperature":1.0,"text":{"format":{"type":"text"}},"tool_choice":"auto","tools":[],"top_p":1.0,"truncation":"disabled","usage":null,"user":null,"metadata":{}},"sequence_number":1}
data: {"type":"response.output_text.delta","item_id":"msg_test_123","output_index":0,"content_index":0,"delta":"Hi","sequence_number":2}
data: {"type":"response.completed","response":{"id":"resp_test_123","object":"response","created_at":1741290958,"status":"completed","error":null,"incomplete_details":null,"instructions":"You are a helpful assistant.","max_output_tokens":null,"model":"gpt-5.4-mini","output":[{"id":"msg_test_123","type":"message","status":"completed","role":"assistant","content":[{"type":"output_text","text":"Hi there! How can I assist you today?","annotations":[]}]}],"parallel_tool_calls":true,"previous_response_id":null,"reasoning":{"effort":null,"summary":null},"store":false,"temperature":1.0,"text":{"format":{"type":"text"}},"tool_choice":"auto","tools":[],"top_p":1.0,"truncation":"disabled","usage":{"input_tokens":37,"input_tokens_details":{"cached_tokens":0},"output_tokens":11,"output_tokens_details":{"reasoning_tokens":0},"total_tokens":48},"user":null,"metadata":{},"output_text":"Hi there! How can I assist you today?"},"sequence_number":3}
STREAM);

        rewind($resource);

        return $resource;
    }
}
