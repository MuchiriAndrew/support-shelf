<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSupportReplyJob;
use App\Services\Chat\SupportChatService;
use App\Services\Chat\SupportChatSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function storeNew(
        Request $request,
        SupportChatSessionService $sessionService,
        SupportChatService $chatService,
    ): JsonResponse {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $turn = $chatService->startConversationTurn(
            $sessionService->token($request->session()),
            $validated['content'],
        );

        GenerateSupportReplyJob::dispatch(
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
        SupportChatSessionService $sessionService,
        SupportChatService $chatService,
    ): JsonResponse {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $record = $chatService->findConversation(
            $sessionService->token($request->session()),
            $conversation,
        );

        $turn = $chatService->queueConversationTurn($record, $validated['content']);

        GenerateSupportReplyJob::dispatch(
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
