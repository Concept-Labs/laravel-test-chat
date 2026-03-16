(function () {
    const root = document.getElementById('tc-root');
    if (!root) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const channelPrefix = document.querySelector('meta[name="test-chat-channel-prefix"]')?.getAttribute('content') || 'test-chat.user';
    const eventName = document.querySelector('meta[name="test-chat-event-name"]')?.getAttribute('content') || 'test-chat.message.sent';
    const reverbKey = document.querySelector('meta[name="test-chat-reverb-key"]')?.getAttribute('content') || '';
    const reverbHost = document.querySelector('meta[name="test-chat-reverb-host"]')?.getAttribute('content') || window.location.hostname;
    const reverbPort = Number(document.querySelector('meta[name="test-chat-reverb-port"]')?.getAttribute('content') || 8080);
    const reverbScheme = document.querySelector('meta[name="test-chat-reverb-scheme"]')?.getAttribute('content') || 'http';

    const currentUserId = Number(root.dataset.currentUserId || 0);
    let activeUserId = Number(root.dataset.activeUserId || 0);

    const conversationUrlTemplate = root.dataset.conversationUrlTemplate || '';
    const sendUrlTemplate = root.dataset.sendUrlTemplate || '';

    const usersContainer = document.getElementById('tc-users');
    const usersToggle = document.getElementById('tc-users-toggle');
    const sidebarBackdrop = document.getElementById('tc-sidebar-backdrop');
    const chatTitle = document.getElementById('tc-chat-title');
    const messagesContainer = document.getElementById('tc-messages');
    const sendForm = document.getElementById('tc-send-form');
    const messageInput = document.getElementById('tc-message-input');

    const mobileMediaQuery = window.matchMedia('(max-width: 960px)');

    function isMobileView() {
        return mobileMediaQuery.matches;
    }

    function setUsersMenuOpen(isOpen) {
        root.classList.toggle('is-users-open', isOpen);
        usersToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('tc-lock-scroll', isOpen && isMobileView());
    }

    function closeUsersMenu() {
        setUsersMenuOpen(false);
    }

    function urlFor(template, userId) {
        return template.replace('__USER__', String(userId));
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderMessage(message) {
        const isSelf = Number(message.sender_id) === currentUserId;
        const wrapper = document.createElement('article');
        wrapper.className = `tc-message${isSelf ? ' is-self' : ''}`;
        wrapper.dataset.messageId = String(message.id || '');
        wrapper.innerHTML = `
            <p class="tc-message-body">${escapeHtml(message.body || '')}</p>
            <time class="tc-message-time">${escapeHtml(message.created_at || '')}</time>
        `;
        messagesContainer.appendChild(wrapper);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function renderMessages(messages) {
        messagesContainer.innerHTML = '';

        if (!Array.isArray(messages) || messages.length === 0) {
            messagesContainer.innerHTML = '<p class="tc-empty">No messages yet.</p>';
            return;
        }

        messages.forEach(renderMessage);
    }

    function updateUnreadBadge(userId, count) {
        const badge = usersContainer?.querySelector(`[data-unread-for="${userId}"]`);
        if (!badge) {
            return;
        }

        const normalized = Math.max(0, Number(count) || 0);
        badge.textContent = String(normalized);
        badge.classList.toggle('is-hidden', normalized === 0);
    }

    function incrementUnread(userId) {
        const badge = usersContainer?.querySelector(`[data-unread-for="${userId}"]`);
        const current = Number(badge?.textContent || 0);
        updateUnreadBadge(userId, current + 1);
    }

    function setActiveUser(userId) {
        activeUserId = Number(userId || 0);

        usersContainer?.querySelectorAll('.tc-user-item').forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.userId) === activeUserId);
        });
    }

    async function loadConversation(userId) {
        if (!userId) {
            return;
        }

        const response = await fetch(urlFor(conversationUrlTemplate, userId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load conversation');
        }

        const payload = await response.json();
        renderMessages(payload.messages || []);
        chatTitle.textContent = payload.user?.name || 'Conversation';
        setActiveUser(userId);
        updateUnreadBadge(userId, 0);
    }

    usersContainer?.addEventListener('click', async (event) => {
        const button = event.target.closest('.tc-user-item');
        if (!button) {
            return;
        }

        const userId = Number(button.dataset.userId || 0);
        if (!userId) {
            return;
        }

        try {
            await loadConversation(userId);
            if (isMobileView()) {
                closeUsersMenu();
            }
        } catch (error) {
            console.error(error);
        }
    });

    usersToggle?.addEventListener('click', () => {
        const nextIsOpen = !root.classList.contains('is-users-open');
        setUsersMenuOpen(nextIsOpen);
    });

    sidebarBackdrop?.addEventListener('click', closeUsersMenu);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeUsersMenu();
        }
    });

    mobileMediaQuery.addEventListener('change', (event) => {
        if (!event.matches) {
            closeUsersMenu();
        }
    });

    sendForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!activeUserId) {
            return;
        }

        const body = (messageInput?.value || '').trim();
        if (!body) {
            return;
        }

        messageInput.disabled = true;

        try {
            const response = await fetch(urlFor(sendUrlTemplate, activeUserId), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ body }),
            });

            if (!response.ok) {
                throw new Error('Send failed');
            }

            const payload = await response.json();
            if (payload.message) {
                renderMessage(payload.message);
                messageInput.value = '';
            }
        } catch (error) {
            console.error(error);
        } finally {
            messageInput.disabled = false;
            messageInput.focus();
        }
    });

    function ensureEcho() {
        if (window.Echo && typeof window.Echo.private === 'function') {
            return window.Echo;
        }

        const EchoCtor = window.Echo?.default || window.Echo;
        if (typeof EchoCtor !== 'function' || !window.Pusher || !reverbKey) {
            return null;
        }

        const echo = new EchoCtor({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        window.Echo = echo;

        return echo;
    }

    function subscribeRealtime() {
        const echo = ensureEcho();
        if (!echo || !currentUserId) {
            return;
        }

        echo.private(`${channelPrefix}.${currentUserId}`).listen(`.${eventName}`, (event) => {
            const message = event?.message;
            if (!message) {
                return;
            }

            if (Number(message.sender_id) === activeUserId) {
                renderMessage(message);
                updateUnreadBadge(message.sender_id, 0);
                return;
            }

            incrementUnread(message.sender_id);
        });
    }

    subscribeRealtime();
})();