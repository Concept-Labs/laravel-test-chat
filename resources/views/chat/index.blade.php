@extends('test-chat::layouts.app')

@section('content')
<main
    id="tc-root"
    class="tc-app"
    data-current-user-id="{{ $currentUserId }}"
    data-active-user-id="{{ $selectedUserId }}"
    data-conversation-url-template="{{ route('test-chat.chat.conversation', ['user' => '__USER__']) }}"
    data-send-url-template="{{ route('test-chat.chat.send', ['user' => '__USER__']) }}"
    data-users-url="{{ route('test-chat.chat.users') }}"
>
    <aside class="tc-sidebar">
        <div class="tc-sidebar-head">
            <h2>{{ auth()->user()->name }}</h2>
            @if (\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tc-btn tc-btn-ghost">Logout</button>
                </form>
            @endif
        </div>

        <div id="tc-users" class="tc-users">
            @forelse($users as $user)
                @php
                    $userId = (int) $user->id;
                    $isActive = $userId === (int) $selectedUserId;
                    $unread = (int) ($unreadBySender[$userId] ?? 0);
                @endphp
                <button
                    type="button"
                    class="tc-user-item {{ $isActive ? 'is-active' : '' }}"
                    data-user-id="{{ $userId }}"
                >
                    <span class="tc-user-meta">
                        <span class="tc-user-name">{{ $user->name }}</span>
                        <span class="tc-user-email">{{ $user->email }}</span>
                    </span>
                    <span class="tc-user-unread {{ $unread > 0 ? '' : 'is-hidden' }}" data-unread-for="{{ $userId }}">{{ $unread }}</span>
                </button>
            @empty
                <p class="tc-empty">No users found.</p>
            @endforelse
        </div>
    </aside>

    <button
        id="tc-sidebar-backdrop"
        class="tc-sidebar-backdrop"
        type="button"
        aria-label="Close users list"
        aria-hidden="true"
        tabindex="-1"
    ></button>

    <section class="tc-chat">
        <header class="tc-chat-head">
            <button
                id="tc-users-toggle"
                class="tc-users-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="tc-users"
            >
                <span class="tc-users-toggle-bars" aria-hidden="true"></span>
                <span class="tc-users-toggle-label">Users</span>
            </button>
            <h1 id="tc-chat-title">{{ $selectedUser?->name ?? 'Select user' }}</h1>
        </header>

        <section id="tc-messages" class="tc-messages">
            @forelse($messages as $message)
                <article class="tc-message {{ $message['is_self'] ? 'is-self' : '' }}">
                    <p class="tc-message-body">{{ $message['body'] }}</p>
                    <time class="tc-message-time">{{ $message['created_at'] }}</time>
                </article>
            @empty
                <p class="tc-empty">No messages yet.</p>
            @endforelse
        </section>

        <form id="tc-send-form" class="tc-send-form">
            @csrf
            <input id="tc-message-input" type="text" name="body" placeholder="Type your message..." required>
            <button type="submit" class="tc-btn tc-btn-primary">Send</button>
        </form>
    </section>
</main>
@endsection