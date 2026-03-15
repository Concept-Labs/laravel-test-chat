<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="test-chat-reverb-key" content="{{ env('REVERB_APP_KEY', '') }}">
    <meta name="test-chat-reverb-host" content="{{ env('REVERB_HOST', request()->getHost()) }}">
    <meta name="test-chat-reverb-port" content="{{ env('REVERB_PORT', '8080') }}">
    <meta name="test-chat-reverb-scheme" content="{{ env('REVERB_SCHEME', 'http') }}">
    <meta name="test-chat-channel-prefix" content="{{ config('test-chat.channel_prefix', 'test-chat.user') }}">
    <meta name="test-chat-event-name" content="{{ config('test-chat.event_name', 'test-chat.message.sent') }}">
    <title>Test Chat</title>
    <link rel="stylesheet" href="{{ asset(config('test-chat.asset_path', 'vendor/test-chat').'/css/test-chat.css') }}">
    @stack('styles')
</head>
<body>
    @yield('content')

    <script src="https://unpkg.com/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://unpkg.com/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="{{ asset(config('test-chat.asset_path', 'vendor/test-chat').'/js/test-chat.js') }}" defer></script>
    @stack('scripts')
</body>
</html>