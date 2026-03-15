<?php

namespace Mtr\TestChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'test_chat_messages';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the sender of the message.
     *
     * @return BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     *
     * @return BelongsTo
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /*
     * Scope a query to only include messages between two users.
     *
     * @param Builder $query
     * @param int $firstUserId
     * @param int $secondUserId
     * @return Builder
     */
    public function scopeBetweenUsers(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $inner) use ($firstUserId, $secondUserId): void {
            $inner->where('sender_id', $firstUserId)->where('receiver_id', $secondUserId);
        })->orWhere(function (Builder $inner) use ($firstUserId, $secondUserId): void {
            $inner->where('sender_id', $secondUserId)->where('receiver_id', $firstUserId);
        });
    }

    /*
     * Scope a query to only include unread messages for a specific receiver.
     *
     * @param Builder $query
     * @param int $receiverId
     * @return Builder
     */
    public function scopeUnreadFor(Builder $query, int $receiverId): Builder
    {
        return $query->where('receiver_id', $receiverId)->where('is_read', false);
    }
}