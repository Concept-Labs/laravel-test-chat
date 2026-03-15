<?php

return [
    'route_prefix' => 'tchat',
    'route_name_prefix' => 'test-chat.',
    'middleware' => ['web', 'auth'],
    'messages_table' => 'test_chat_messages',
    'channel_prefix' => 'test-chat.user',
    'event_name' => 'test-chat.message.sent',
    'asset_path' => 'vendor/test-chat',
    'messages_per_page' => 100,
];