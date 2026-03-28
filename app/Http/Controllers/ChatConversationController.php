<?php

namespace App\Http\Controllers;

use App\Services\Chat\AssistantChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function show(
        Request $request,
        string $conversation,
        AssistantChatService $chatService,
    ): JsonResponse {
        $record = $chatService->findConversation(
            $request->user(),
            $conversation,
        );

        return response()->json([
            'conversation' => $chatService->serializeConversation($record),
        ]);
    }

    public function destroy(
        Request $request,
        string $conversation,
        AssistantChatService $chatService,
    ): JsonResponse {
        $chatService->deleteConversation(
            $request->user(),
            $conversation,
        );

        return response()->json([
            'deleted' => true,
            'conversation_uuid' => $conversation,
        ]);
    }
}
