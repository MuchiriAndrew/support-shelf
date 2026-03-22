import { formatMessageContent } from './composables/format-message';

const THEME_STORAGE_KEY = 'supportshelf-theme';

const resolveInitialTheme = () => {
    const savedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);

    if (savedTheme === 'light' || savedTheme === 'dark') {
        return savedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    window.localStorage.setItem(THEME_STORAGE_KEY, theme);
};

document.addEventListener('alpine:init', () => {
    window.Alpine.store('chrome', {
        pageKind: 'default',
        mobileNavOpen: false,
        chatDrawerOpen: false,
        theme: resolveInitialTheme(),
        initialized: false,

        init(pageKind = 'default') {
            this.pageKind = pageKind;

            if (this.initialized) {
                return;
            }

            this.initialized = true;
            applyTheme(this.theme);
        },

        openMobileMenu() {
            if (this.pageKind === 'chat') {
                this.chatDrawerOpen = true;
                return;
            }

            this.mobileNavOpen = true;
        },

        closeMobileMenu() {
            this.mobileNavOpen = false;
            this.chatDrawerOpen = false;
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            applyTheme(this.theme);
        },
    });

    window.Alpine.data('siteChrome', (config = {}) => ({
        init() {
            window.Alpine.store('chrome').init(config.pageKind || 'default');
        },

        get theme() {
            return window.Alpine.store('chrome').theme;
        },

        get mobileNavOpen() {
            return window.Alpine.store('chrome').mobileNavOpen;
        },

        openMobileMenu() {
            window.Alpine.store('chrome').openMobileMenu();
        },

        closeMobileNav() {
            window.Alpine.store('chrome').closeMobileMenu();
        },

        toggleTheme() {
            window.Alpine.store('chrome').toggleTheme();
        },
    }));

    window.Alpine.data('chatShell', (initialState = {}) => ({
        brand: initialState.brand || { name: 'SupportShelf AI' },
        prompts: initialState.prompts || [],
        endpoints: initialState.endpoints || {},
        navigation: initialState.navigation || [],
        conversations: initialState.conversationList || [],
        activeConversation: initialState.activeConversation,
        activeConversationUuid: initialState.activeConversation?.uuid || null,
        messages: initialState.activeConversation?.messages || [],
        draft: '',
        error: null,
        isSending: false,
        activeChannelName: null,

        init() {
            this.subscribeToActiveConversation();
            this.resizeComposer();
            this.scrollToBottom(false);
            this.focusComposer();
        },

        get canSend() {
            return this.draft.trim().length > 1;
        },

        get showPromptScreen() {
            return this.messages.length === 0;
        },

        setPrompt(prompt) {
            this.draft = prompt;
            this.resizeComposer();
            this.focusComposer();
            window.Alpine.store('chrome').closeMobileMenu();
        },

        clearDraft() {
            this.draft = '';
            this.resizeComposer();
            this.focusComposer();
        },

        startFreshConversation() {
            this.activeConversation = null;
            this.activeConversationUuid = null;
            this.messages = [];
            this.error = null;
            this.clearDraft();
            this.unsubscribeFromConversation();
            window.Alpine.store('chrome').closeMobileMenu();
            this.scrollToBottom(false);
        },

        async selectConversation(uuid) {
            if (! uuid || uuid === this.activeConversationUuid) {
                window.Alpine.store('chrome').closeMobileMenu();
                return;
            }

            this.error = null;

            try {
                const response = await window.axios.get(this.interpolateEndpoint(this.endpoints.showConversation, uuid));
                this.setActiveConversation(response.data.conversation);
                window.Alpine.store('chrome').closeMobileMenu();
            } catch (error) {
                this.error = error.response?.data?.message || 'Could not load that conversation.';
            }
        },

        async sendMessage() {
            const content = this.draft.trim();

            if (this.isSending || content.length < 2) {
                return;
            }

            this.error = null;
            this.isSending = true;

            try {
                const response = this.activeConversationUuid
                    ? await window.axios.post(
                        this.interpolateEndpoint(this.endpoints.sendMessage, this.activeConversationUuid),
                        { content },
                    )
                    : await window.axios.post(this.endpoints.startConversation, { content });

                this.draft = '';
                this.resizeComposer();
                this.upsertConversation(response.data.conversation);
                this.activeConversationUuid = response.data.conversation.uuid;
                this.activeConversation = {
                    ...(this.activeConversation || {}),
                    ...response.data.conversation,
                    messages: this.messages,
                };
                this.upsertMessage(response.data.user_message);
                this.upsertMessage(response.data.assistant_message);
                this.subscribeToActiveConversation();
                window.Alpine.store('chrome').closeMobileMenu();
                this.scrollToBottom();
            } catch (error) {
                this.error = error.response?.data?.message || 'Could not send that message.';
            } finally {
                this.isSending = false;
                this.focusComposer();
            }
        },

        setActiveConversation(conversation) {
            this.activeConversation = conversation;
            this.activeConversationUuid = conversation?.uuid || null;
            this.messages = conversation?.messages || [];
            this.error = null;
            this.subscribeToActiveConversation();
            this.scrollToBottom(false);
        },

        unsubscribeFromConversation() {
            if (this.activeChannelName && window.Echo) {
                window.Echo.leave(this.activeChannelName);
            }

            this.activeChannelName = null;
        },

        subscribeToActiveConversation() {
            this.unsubscribeFromConversation();

            if (! this.activeConversationUuid || ! window.Echo) {
                return;
            }

            const channelName = `support-chat.${this.activeConversationUuid}`;
            this.activeChannelName = channelName;

            window.Echo.channel(channelName)
                .stopListening('.support.chat.message.updated')
                .listen('.support.chat.message.updated', (event) => {
                    this.handleRealtimeEvent(event);
                });
        },

        handleRealtimeEvent(event) {
            if (event.conversation_uuid !== this.activeConversationUuid) {
                return;
            }

            if (event.type === 'delta') {
                this.appendDelta(event.message_id, event.delta || '');
                this.scrollToBottom();

                return;
            }

            if (event.message) {
                this.upsertMessage(event.message);
            }

            if (event.conversation) {
                this.upsertConversation(event.conversation);
                this.activeConversation = {
                    ...this.activeConversation,
                    ...event.conversation,
                    messages: this.messages,
                };
            }

            if (event.type === 'failed' && event.error) {
                this.error = event.error;
            }

            this.scrollToBottom();
        },

        appendDelta(messageId, delta) {
            this.messages = this.messages.map((message) => {
                if (message.id !== messageId) {
                    return message;
                }

                return {
                    ...message,
                    status: 'streaming',
                    content: `${message.content || ''}${delta}`,
                };
            });

            this.syncConversationPreview();
        },

        upsertMessage(message) {
            const existingIndex = this.messages.findIndex((candidate) => candidate.id === message.id);

            if (existingIndex === -1) {
                this.messages = [...this.messages, message];
            } else {
                this.messages.splice(existingIndex, 1, {
                    ...this.messages[existingIndex],
                    ...message,
                });
                this.messages = [...this.messages];
            }

            this.syncConversationPreview();
        },

        upsertConversation(conversation) {
            const existingIndex = this.conversations.findIndex((candidate) => candidate.uuid === conversation.uuid);

            if (existingIndex === -1) {
                this.conversations = [conversation, ...this.conversations];
            } else {
                this.conversations.splice(existingIndex, 1, {
                    ...this.conversations[existingIndex],
                    ...conversation,
                });
                this.conversations = [...this.conversations];
            }

            this.conversations = [...this.conversations].sort((left, right) => {
                return new Date(right.last_message_at || right.updated_at || 0) - new Date(left.last_message_at || left.updated_at || 0);
            });
        },

        syncConversationPreview() {
            if (! this.activeConversationUuid) {
                return;
            }

            const latestMessage = this.messages[this.messages.length - 1];

            this.conversations = this.conversations.map((conversation) => {
                if (conversation.uuid !== this.activeConversationUuid) {
                    return conversation;
                }

                return {
                    ...conversation,
                    status: this.activeConversation?.status || latestMessage?.status || conversation.status,
                    preview: latestMessage?.content?.trim()
                        ? latestMessage.content.trim().slice(0, 96)
                        : conversation.preview,
                    last_message_at: latestMessage?.updated_at || latestMessage?.created_at || conversation.last_message_at,
                };
            });
        },

        interpolateEndpoint(template, uuid) {
            return template.replace('__CONVERSATION__', uuid);
        },

        formatMessage(message) {
            return formatMessageContent(message?.content || '');
        },

        isPendingAssistantMessage(message) {
            return message.role === 'assistant'
                && ! (message.content || '').trim()
                && ['queued', 'streaming'].includes(message.status);
        },

        messageMetaLabel(message) {
            if (message.status === 'failed') {
                return 'Not delivered';
            }

            if (['queued', 'streaming'].includes(message.status) && ! (message.content || '').trim()) {
                return '';
            }

            return this.formatTime(message.updated_at || message.created_at);
        },

        focusComposer() {
            requestAnimationFrame(() => {
                this.$refs.composer?.focus();
            });
        },

        resizeComposer() {
            requestAnimationFrame(() => {
                const composer = this.$refs.composer;

                if (! composer) {
                    return;
                }

                composer.style.height = '0px';
                composer.style.height = `${Math.min(composer.scrollHeight, 180)}px`;
            });
        },

        handleComposerKeydown(event) {
            if (event.key === 'Enter' && ! event.shiftKey) {
                event.preventDefault();
                this.sendMessage();
            }
        },

        scrollToBottom(smooth = true) {
            requestAnimationFrame(() => {
                const viewport = this.$refs.messageViewport;

                if (! viewport) {
                    return;
                }

                viewport.scrollTo({
                    top: viewport.scrollHeight,
                    behavior: smooth ? 'smooth' : 'auto',
                });
            });
        },

        formatRelative(value) {
            if (! value) {
                return 'Just now';
            }

            const date = new Date(value);
            const seconds = Math.round((Date.now() - date.getTime()) / 1000);

            if (Number.isNaN(seconds) || Math.abs(seconds) < 60) {
                return 'Just now';
            }

            if (Math.abs(seconds) < 3600) {
                return `${Math.round(seconds / 60)}m ago`;
            }

            if (Math.abs(seconds) < 86400) {
                return `${Math.round(seconds / 3600)}h ago`;
            }

            return date.toLocaleDateString();
        },

        formatTime(value) {
            if (! value) {
                return '';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return date.toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit',
            });
        },
    }));
});
