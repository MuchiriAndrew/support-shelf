<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAssistantReplyJob;
use App\Services\Chat\AssistantChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function storeNew(
        Request $request,
        AssistantChatService $chatService,
    ): JsonResponse {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $turn = $chatService->startConversationTurn(
            $request->user(),
            $validated['content'],
        );

        GenerateAssistantReplyJob::dispatch(
            $turn['conversation']->id,
            $turn['userMessage']->id,
            $turn['assistantMessage']->id,
        );

        return response()->json([
            'conversation' => $chatService->serializeConversation($turn['conversation'], includeMessages: false),
            'user_message' => $chatService->serializeMessage($turn['userMessage']),
            'assistant_message' => $chatService->serializeMessage($turn['assistantMessage']),
        ], 202);
    }

    public function store(
        Request $request,
        string $conversation,
        AssistantChatService $chatService,
    ): JsonResponse {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $record = $chatService->findConversation(
            $request->user(),
            $conversation,
        );

        $turn = $chatService->queueConversationTurn($record, $validated['content']);

        GenerateAssistantReplyJob::dispatch(
            $turn['conversation']->id,
            $turn['userMessage']->id,
            $turn['assistantMessage']->id,
        );

        return response()->json([
            'conversation' => $chatService->serializeConversation($turn['conversation'], includeMessages: false),
            'user_message' => $chatService->serializeMessage($turn['userMessage']),
            'assistant_message' => $chatService->serializeMessage($turn['assistantMessage']),
        ], 202);
    }
}
