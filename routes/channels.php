<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    config('test-chat.channel_prefix', 'test-chat.user').'.{userId}',
    fn ($user, int $userId): bool => (int) $user->getAuthIdentifier() === (int) $userId,
    ['guards' => ['web']]
);