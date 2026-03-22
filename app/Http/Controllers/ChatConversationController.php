<?php

namespace App\Http\Controllers;

use App\Services\Chat\SupportChatService;
use App\Services\Chat\SupportChatSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function show(
        Request $request,
        string $conversation,
        SupportChatSessionService $sessionService,
        SupportChatService $chatService,
    ): JsonResponse {
        $record = $chatService->findConversation(
            $sessionService->token($request->session()),
            $conversation,
        );

        return response()->json([
            'conversation' => $chatService->serializeConversation($record),
        ]);
    }
}
