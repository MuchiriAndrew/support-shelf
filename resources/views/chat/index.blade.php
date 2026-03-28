@extends('layouts.app', ['pageTitle' => 'Chat', 'pageKind' => 'chat', 'fullWidth' => true, 'hideFooter' => true])

@section('content')
    @php
        $sidebarActionClass = 'inline-flex items-center gap-3 rounded-2xl border border-[color:var(--button-secondary-border)] bg-[var(--button-secondary-bg)] px-4 py-3 text-[0.95rem] font-medium text-[var(--text-primary)] transition hover:border-[color:var(--button-secondary-border)] hover:bg-[var(--page-bg-strong)]';
        $sidebarHeadingClass = 'text-[0.82rem] font-semibold uppercase tracking-[0.16em] text-[var(--text-muted)]';
        $threadLinkBase = 'block w-full rounded-2xl border border-transparent px-[0.95rem] py-[0.9rem] text-left transition hover:border-[color:var(--border-soft)] hover:bg-[var(--button-secondary-bg)]';
        $threadLinkActive = 'border-[color:var(--border-soft)] bg-[var(--button-secondary-bg)]';
        $threadDeleteClass = 'absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent text-[var(--text-muted)] transition hover:bg-[var(--page-bg-strong)] hover:text-[var(--text-primary)] disabled:cursor-not-allowed disabled:opacity-45';
        $drawerOverlayClass = 'fixed inset-0 z-40 bg-black/45 backdrop-blur-sm';
        $drawerClass = 'fixed inset-y-0 right-0 z-50 w-[min(100%,21rem)] border-l border-[color:var(--border-soft)] bg-[var(--drawer-bg)] shadow-[var(--shadow-soft)]';
        $drawerPanelClass = 'flex h-full min-h-0 flex-col overflow-y-auto px-4 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-[calc(1rem+env(safe-area-inset-top))]';
        $drawerLinkClass = 'block rounded-2xl border border-transparent px-4 py-3 text-sm font-medium text-[var(--text-primary)] transition hover:border-[color:var(--button-secondary-border)] hover:bg-[var(--button-secondary-bg)]';
        $messageContentClass = 'text-[0.97rem] leading-7 text-[var(--text-primary)] [&>*+*]:mt-4 [&_p]:m-0 [&_strong]:font-semibold [&_em]:italic [&_code]:rounded-md [&_code]:bg-[var(--code-inline-bg)] [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[0.85em] [&_pre]:overflow-x-auto [&_pre]:rounded-2xl [&_pre]:bg-[var(--code-block-bg)] [&_pre]:px-4 [&_pre]:py-3.5 [&_pre]:text-[0.9rem] [&_pre]:leading-6 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_blockquote]:border-l-2 [&_blockquote]:border-[color:var(--border-soft)] [&_blockquote]:pl-4 [&_blockquote]:italic';
    @endphp

    <div x-data="chatShell(@js($chatState))" x-init="init()" class="grid h-full min-h-0 lg:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="hidden h-full min-h-0 border-r border-[color:var(--border-soft)] bg-[var(--sidebar-bg)] lg:block">
            <div class="flex h-full min-h-0 flex-col px-4 py-4 sm:px-5">
                <button type="button" class="{{ $sidebarActionClass }}" @click="startFreshConversation">
                    <span class="text-lg leading-none">+</span>
                    <span>New chat</span>
                </button>

                <div class="mt-6 min-h-0 flex-1">
                    <p class="{{ $sidebarHeadingClass }} px-2">Recent chats</p>

                    <div class="mt-3 h-full min-h-0 overflow-y-auto pr-[0.3rem]">
                        <template x-for="conversation in conversations" :key="conversation.uuid">
                            <div class="relative">
                                <button
                                    type="button"
                                    class="{{ $threadLinkBase }}"
                                    :class="conversation.uuid === activeConversationUuid ? @js($threadLinkActive) : ''"
                                    @click="selectConversation(conversation.uuid)"
                                >
                                    <p class="truncate pr-9 text-sm font-medium text-[var(--text-primary)]" x-text="conversation.title"></p>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--text-secondary)]" x-text="conversation.preview || 'New conversation'"></p>
                                    <p class="mt-3 text-xs text-[var(--text-muted)]" x-text="formatRelative(conversation.last_message_at || conversation.updated_at)"></p>
                                </button>

                                <button
                                    type="button"
                                    class="{{ $threadDeleteClass }}"
                                    :disabled="isDeletingConversation(conversation.uuid)"
                                    @click.stop="deleteConversation(conversation.uuid)"
                                    :aria-label="`Delete ${conversation.title}`"
                                    title="Delete conversation"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 7H20"></path>
                                        <path d="M9 7V5.75C9 5.33579 9.33579 5 9.75 5H14.25C14.6642 5 15 5.33579 15 5.75V7"></path>
                                        <path d="M7.5 7L8.1 18.1C8.13663 18.7778 8.69747 19.3077 9.37625 19.3077H14.6237C15.3025 19.3077 15.8634 18.7778 15.9 18.1L16.5 7"></path>
                                        <path d="M10 10.25V16"></path>
                                        <path d="M14 10.25V16"></path>
                                    </svg>
                                </button>
                            </div>
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
            class="{{ $drawerOverlayClass }} lg:hidden"
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
            class="{{ $drawerClass }} lg:hidden"
        >
            <div class="{{ $drawerPanelClass }}">
                <div class="sticky top-0 flex items-center justify-between gap-4 border-b border-[color:var(--border-soft)] bg-[var(--drawer-bg)] pb-4">
                    <div>
                        <p class="{{ $sidebarHeadingClass }}">Navigate</p>
                        <p class="mt-2 text-base font-semibold text-[var(--text-primary)]">{{ config('assistant.brand.name') }}</p>
                    </div>

                    <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[color:var(--button-secondary-border)] bg-[var(--button-secondary-bg)] text-[var(--text-primary)] transition hover:bg-[var(--page-bg-strong)]" @click="$store.chrome.closeMobileMenu()" aria-label="Close menu">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                            <path d="M6 6L18 18"></path>
                            <path d="M18 6L6 18"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-8 space-y-3">
                    @foreach ($chatState['navigation'] as $item)
                        <a href="{{ route($item['route']) }}" class="{{ $drawerLinkClass }}" @click="$store.chrome.closeMobileMenu()">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    <button type="button" class="{{ $sidebarActionClass }} w-full justify-center" @click="startFreshConversation">
                        <span class="text-lg leading-none">+</span>
                        <span>New chat</span>
                    </button>
                </div>

                <div class="mt-8 min-h-0 flex-1">
                    <p class="{{ $sidebarHeadingClass }}">Recent chats</p>

                    <div class="mt-3 h-full min-h-0 overflow-y-auto pr-[0.3rem]">
                        <template x-for="conversation in conversations" :key="`mobile-${conversation.uuid}`">
                            <div class="relative">
                                <button
                                    type="button"
                                    class="{{ $threadLinkBase }}"
                                    :class="conversation.uuid === activeConversationUuid ? @js($threadLinkActive) : ''"
                                    @click="selectConversation(conversation.uuid)"
                                >
                                    <p class="truncate pr-9 text-sm font-medium text-[var(--text-primary)]" x-text="conversation.title"></p>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--text-secondary)]" x-text="conversation.preview || 'New conversation'"></p>
                                    <p class="mt-3 text-xs text-[var(--text-muted)]" x-text="formatRelative(conversation.last_message_at || conversation.updated_at)"></p>
                                </button>

                                <button
                                    type="button"
                                    class="{{ $threadDeleteClass }}"
                                    :disabled="isDeletingConversation(conversation.uuid)"
                                    @click.stop="deleteConversation(conversation.uuid)"
                                    :aria-label="`Delete ${conversation.title}`"
                                    title="Delete conversation"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 7H20"></path>
                                        <path d="M9 7V5.75C9 5.33579 9.33579 5 9.75 5H14.25C14.6642 5 15 5.33579 15 5.75V7"></path>
                                        <path d="M7.5 7L8.1 18.1C8.13663 18.7778 8.69747 19.3077 9.37625 19.3077H14.6237C15.3025 19.3077 15.8634 18.7778 15.9 18.1L16.5 7"></path>
                                        <path d="M10 10.25V16"></path>
                                        <path d="M14 10.25V16"></path>
                                    </svg>
                                </button>
                            </div>
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

        <section class="relative flex min-h-0 flex-1 flex-col overflow-hidden">
            <div x-ref="messageViewport" class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <div class="mx-auto flex min-h-full w-full max-w-4xl flex-col px-4 pb-[calc(var(--chat-composer-height)+env(safe-area-inset-bottom)+0.75rem)] pt-8 sm:px-6">
                    <div
                        x-show="showPromptScreen"
                        x-cloak
                        class="my-auto flex min-h-[calc(100%-1rem)] flex-col items-center justify-center py-8 text-center max-sm:min-h-full max-sm:justify-center max-sm:py-5"
                    >
                        <div class="mx-auto max-w-3xl">
                            <h1 class="text-[clamp(2.35rem,5vw,3.45rem)] font-semibold tracking-[-0.04em] text-[var(--text-primary)] max-sm:text-[clamp(2rem,10vw,2.5rem)] max-sm:leading-[1.02] max-sm:tracking-[-0.05em]">Where should we begin?</h1>
                            <p class="mx-auto mt-4 max-w-[38rem] text-base leading-[1.85] text-[var(--text-secondary)] max-sm:mt-3 max-sm:max-w-[19rem] max-sm:text-[0.95rem] max-sm:leading-[1.65]">
                                Ask anything grounded in your own uploaded documents and crawled website content.
                            </p>
                        </div>

                        <div class="mt-10 grid w-full max-w-3xl gap-3 sm:grid-cols-2">
                            <template x-for="prompt in prompts" :key="prompt">
                                <button
                                    type="button"
                                    class="rounded-[1.4rem] border border-[color:var(--button-secondary-border)] bg-[var(--button-secondary-bg)] px-[1.15rem] py-[0.95rem] text-left text-[0.95rem] leading-[1.5] text-[var(--text-primary)] transition hover:-translate-y-px hover:border-[color:var(--button-secondary-border)] hover:bg-[var(--page-bg-strong)] max-sm:rounded-[1.2rem] max-sm:px-4 max-sm:text-[0.92rem]"
                                    @click="setPrompt(prompt)"
                                    x-text="prompt"
                                ></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="!showPromptScreen" x-cloak class="mx-auto flex w-full max-w-3xl flex-col gap-8 pb-10 pt-2">
                        <template x-for="message in messages" :key="message.id">
                            <article class="flex flex-col" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                                <div
                                    class="rounded-[1.5rem] px-[1.15rem] py-[0.95rem] shadow-none max-sm:rounded-[1.35rem] max-sm:px-[0.95rem] max-sm:py-[0.9rem]"
                                    :class="message.role === 'user'
                                        ? 'max-w-[min(88%,32rem)] bg-[var(--user-bubble-bg)] text-[var(--text-primary)] shadow-[var(--shadow-card)]'
                                        : 'max-w-full bg-transparent text-[var(--text-primary)]'"
                                >
                                    <template x-if="isPendingAssistantMessage(message)">
                                        <div class="flex items-center gap-2 py-2" aria-hidden="true">
                                            <span class="h-2 w-2 rounded-full bg-[var(--text-primary)] opacity-55 animate-[typing-bounce_1.2s_infinite_ease-in-out]"></span>
                                            <span class="h-2 w-2 rounded-full bg-[var(--text-primary)] opacity-55 animate-[typing-bounce_1.2s_infinite_ease-in-out] [animation-delay:150ms]"></span>
                                            <span class="h-2 w-2 rounded-full bg-[var(--text-primary)] opacity-55 animate-[typing-bounce_1.2s_infinite_ease-in-out] [animation-delay:300ms]"></span>
                                        </div>
                                    </template>

                                    <div
                                        x-show="!isPendingAssistantMessage(message)"
                                        class="{{ $messageContentClass }}"
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

            <footer class="sticky bottom-0 z-20 flex-none border-t border-[color:var(--border-soft)] bg-[var(--page-bg)]">
                <div class="px-4 py-3 pb-[calc(max(0.95rem,env(safe-area-inset-bottom)))] sm:px-6">
                    <div class="mx-auto w-full max-w-3xl">
                        <p
                            x-show="error"
                            x-cloak
                            class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200"
                            x-text="error"
                        ></p>

                        <div class="flex min-h-[3.8rem] items-end gap-[0.7rem] rounded-[1.55rem] border border-[color:var(--input-border)] bg-[var(--composer-bg)] px-[0.9rem] py-[0.5rem] shadow-[0_18px_40px_-28px_rgba(0,0,0,0.4)]">
                            <label class="sr-only" for="support-composer">Message</label>
                            <textarea
                                id="support-composer"
                                x-ref="composer"
                                x-model="draft"
                                rows="1"
                                class="max-h-[9.5rem] min-h-[2.85rem] flex-1 resize-none border-0 bg-transparent px-0 py-[0.5rem] text-[16px] leading-[1.55] text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]"
                                placeholder="Ask anything"
                                @input="resizeComposer"
                                @keydown="handleComposerKeydown"
                            ></textarea>

                            <button
                                type="button"
                                class="inline-flex h-[2.65rem] w-[2.65rem] items-center justify-center rounded-full bg-[var(--button-primary-bg)] text-base font-semibold text-[var(--button-primary-text)] transition hover:opacity-95"
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
