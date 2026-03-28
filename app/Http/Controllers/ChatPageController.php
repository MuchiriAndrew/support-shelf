<?php

namespace App\Http\Controllers;

use App\Services\Chat\AssistantChatService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ChatPageController extends Controller
{
    public function __invoke(Request $request, AssistantChatService $chatService): View
    {
        $user = $request->user();
        $conversations = $chatService->listConversations($user);
        $activeConversation = $conversations->first();

        return view('chat.index', [
            'realtime' => true,
            'chatState' => [
                'conversationList' => $chatService->serializeConversationList($conversations),
                'activeConversation' => $activeConversation
                    ? $chatService->serializeConversation($activeConversation->load(['messages' => fn ($query) => $query->orderBy('id')]))
                    : null,
                'prompts' => [
                    // 'How do I factory reset the AeroTune 90 earbuds?',
                    // 'Does the ViewPort 27 monitor support video over USB-C?',
                    // 'What is the return window for opened accessories?',
                    // 'How can I update the firmware on the KeyLite Pro keyboard?',
                ],
                'endpoints' => [
                    'startConversation' => route('chat.messages.start'),
                    'showConversation' => route('chat.conversations.show', ['conversation' => '__CONVERSATION__']),
                    'deleteConversation' => route('chat.conversations.destroy', ['conversation' => '__CONVERSATION__']),
                    'sendMessage' => route('chat.messages.store', ['conversation' => '__CONVERSATION__']),
                ],
                'navigation' => [
                    ['label' => 'Chat', 'route' => 'chat'],
                    ['label' => 'Overview', 'route' => 'home'],
                    ['label' => 'Ingestion', 'route' => 'filament.admin.pages.knowledge-ingestion'],
                    ['label' => 'My Assistant', 'route' => 'filament.admin.pages.assistant-settings'],
                ],
                'brand' => [
                    'name' => $user->assistantDisplayName(),
                ],
            ],
        ]);
    }
}
