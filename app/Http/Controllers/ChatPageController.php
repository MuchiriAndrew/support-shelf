<?php

namespace App\Http\Controllers;

use App\Services\Chat\SupportChatService;
use App\Services\Chat\SupportChatSessionService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\View;

class ChatPageController extends Controller
{
    public function __invoke(Session $session, SupportChatSessionService $sessionService, SupportChatService $chatService): View
    {
        $sessionToken = $sessionService->token($session);
        $conversations = $chatService->listConversations($sessionToken);
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
                    'sendMessage' => route('chat.messages.store', ['conversation' => '__CONVERSATION__']),
                ],
                'navigation' => [
                    ['label' => 'Chat', 'route' => 'chat'],
                    ['label' => 'Overview', 'route' => 'home'],
                    ['label' => 'Ingestion', 'route' => 'filament.admin.pages.knowledge-ingestion'],
                ],
                'brand' => [
                    'name' => config('support-assistant.brand.name'),
                ],
            ],
        ]);
    }
}
