<?php

namespace Mtr\TestChat\Events;

use Mtr\TestChat\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param ChatMessage $message
     */
    public function __construct(public ChatMessage $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(config('test-chat.channel_prefix', 'test-chat.user').'.'.$this->message->receiver_id),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastAs(): string
    {
        return config('test-chat.event_name', 'test-chat.message.sent');
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'body' => $this->message->body,
                'created_at' => $this->message->created_at?->toIso8601String(),
                'is_read' => (bool) $this->message->is_read,
            ],
        ];
    }
}