@extends('layouts.app', ['pageTitle' => 'Chat', 'pageKind' => 'chat', 'fullWidth' => true, 'hideFooter' => true])

@section('content')
    <div x-data="chatShell(@js($chatState))" x-init="init()" class="chat-page-layout">
        <aside class="chat-sidebar hidden lg:block">
            <div class="flex h-full flex-col px-4 py-4 sm:px-5">
                <button
                    type="button"
                    class="chat-sidebar-action"
                    @click="startFreshConversation"
                >
                    <span class="text-lg leading-none">+</span>
                    <span>New chat</span>
                </button>

                <div class="mt-6 min-h-0 flex-1">
                    <p class="chat-sidebar-heading px-2">Recent chats</p>

                    <div class="chat-sidebar-scroll mt-3">
                        <template x-for="conversation in conversations" :key="conversation.uuid">
                            <button
                                type="button"
                                class="chat-thread-link"
                                :class="conversation.uuid === activeConversationUuid ? 'chat-thread-link-active' : ''"
                                @click="selectConversation(conversation.uuid)"
                            >
                                <p class="truncate text-sm font-medium text-[var(--text-primary)]" x-text="conversation.title"></p>
                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--text-secondary)]" x-text="conversation.preview || 'New conversation'"></p>
                                <p class="mt-3 text-xs text-[var(--text-muted)]" x-text="formatRelative(conversation.last_message_at || conversation.updated_at)"></p>
                            </button>
                        </template>

                        <div
                            x-show="conversations.length === 0"
                            x-cloak
                            class="rounded-2xl border border-dashed border-[color:var(--border-soft)] px-4 py-4 text-sm leading-6 text-[var(--text-secondary)]"
                        >
                            No chats yet. Start with a product or policy question.
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <div
            x-cloak
            x-show="$store.chrome.chatDrawerOpen"
            x-transition.opacity
            class="shell-offcanvas-overlay lg:hidden"
            @click="$store.chrome.closeMobileMenu()"
        ></div>

        <aside
            x-cloak
            x-show="$store.chrome.chatDrawerOpen"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="chat-mobile-drawer lg:hidden"
        >
            <div class="chat-mobile-drawer-panel">
                <div class="shell-offcanvas-header">
                    <div>
                        <p class="chat-sidebar-heading">Navigate</p>
                        <p class="mt-2 text-base font-semibold text-[var(--text-primary)]">{{ config('support-assistant.brand.name') }}</p>
                    </div>

                    <button type="button" class="shell-close-button" @click="$store.chrome.closeMobileMenu()" aria-label="Close menu">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                            <path d="M6 6L18 18"></path>
                            <path d="M18 6L6 18"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-8 space-y-3">
                    @foreach ($chatState['navigation'] as $item)
                        <a href="{{ route($item['route']) }}" class="shell-offcanvas-link" @click="$store.chrome.closeMobileMenu()">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    <button
                        type="button"
                        class="chat-sidebar-action w-full justify-center"
                        @click="startFreshConversation"
                    >
                        <span class="text-lg leading-none">+</span>
                        <span>New chat</span>
                    </button>
                </div>

                <div class="mt-8 min-h-0 flex-1">
                    <p class="chat-sidebar-heading">Recent chats</p>

                    <div class="chat-sidebar-scroll mt-3">
                        <template x-for="conversation in conversations" :key="`mobile-${conversation.uuid}`">
                            <button
                                type="button"
                                class="chat-thread-link"
                                :class="conversation.uuid === activeConversationUuid ? 'chat-thread-link-active' : ''"
                                @click="selectConversation(conversation.uuid)"
                            >
                                <p class="truncate text-sm font-medium text-[var(--text-primary)]" x-text="conversation.title"></p>
                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--text-secondary)]" x-text="conversation.preview || 'New conversation'"></p>
                                <p class="mt-3 text-xs text-[var(--text-muted)]" x-text="formatRelative(conversation.last_message_at || conversation.updated_at)"></p>
                            </button>
                        </template>

                        <div
                            x-show="conversations.length === 0"
                            x-cloak
                            class="rounded-2xl border border-dashed border-[color:var(--border-soft)] px-4 py-4 text-sm leading-6 text-[var(--text-secondary)]"
                        >
                            No chats yet. Start with a product or policy question.
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="chat-main">
            <div x-ref="messageViewport" class="chat-message-viewport">
                <div class="chat-message-inner mx-auto flex min-h-full w-full max-w-4xl flex-col px-4 pt-8 sm:px-6">
                    <div
                        x-show="showPromptScreen"
                        x-cloak
                        class="chat-empty-state"
                    >
                        <div class="mx-auto max-w-3xl text-center">
                            <h1 class="chat-empty-title">Where should we begin?</h1>
                            <p class="chat-empty-copy">
                                Ask about setup, compatibility, returns, warranty coverage, or anything stored in your support library.
                            </p>
                        </div>

                        <div class="mt-10 grid w-full max-w-3xl gap-3 sm:grid-cols-2">
                            <template x-for="prompt in prompts" :key="prompt">
                                <button
                                    type="button"
                                    class="chat-prompt-chip"
                                    @click="setPrompt(prompt)"
                                    x-text="prompt"
                                ></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="!showPromptScreen" x-cloak class="mx-auto flex w-full max-w-3xl flex-col gap-8 pb-10 pt-2">
                        <template x-for="message in messages" :key="message.id">
                            <article class="chat-turn" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                                <div
                                    class="chat-turn-card"
                                    :class="message.role === 'user' ? 'chat-turn-card-user' : 'chat-turn-card-assistant'"
                                >
                                    <template x-if="isPendingAssistantMessage(message)">
                                        <div class="typing-dots" aria-hidden="true">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </template>

                                    <div
                                        x-show="!isPendingAssistantMessage(message)"
                                        class="prose-chat"
                                        :class="message.role === 'user' ? 'prose-chat-user' : 'prose-chat-assistant'"
                                        x-html="formatMessage(message)"
                                    ></div>
                                </div>

                                <p
                                    x-show="messageMetaLabel(message)"
                                    x-cloak
                                    class="mt-2 px-1 text-[11px] font-medium text-[var(--text-muted)]"
                                    x-text="messageMetaLabel(message)"
                                ></p>
                            </article>
                        </template>
                    </div>
                </div>
            </div>

            <footer x-ref="composerFooter" class="chat-fixed-composer">
                <div class="chat-fixed-composer-inner">
                    <div class="mx-auto w-full max-w-3xl">
                        <p
                            x-show="error"
                            x-cloak
                            class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200"
                            x-text="error"
                        ></p>

                        <div class="chat-composer-shell">
                            <label class="sr-only" for="support-composer">Message</label>
                            <textarea
                                id="support-composer"
                                x-ref="composer"
                                x-model="draft"
                                rows="1"
                                class="chat-composer-input"
                                placeholder="Ask anything"
                                @input="resizeComposer"
                                @focus="handleComposerFocus"
                                @keydown="handleComposerKeydown"
                            ></textarea>

                            <button
                                type="button"
                                class="chat-send-button"
                                :disabled="isSending || ! canSend"
                                :class="isSending || ! canSend ? 'cursor-not-allowed opacity-50' : ''"
                                @click="sendMessage"
                                aria-label="Send message"
                            >
                                <span x-text="isSending ? '...' : '↑'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </footer>
        </section>
    </div>
@endsection
