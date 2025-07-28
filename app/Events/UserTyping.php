<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $userName;
    public $chatGroupId;
    public $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $userName, $chatGroupId, $isTyping)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->chatGroupId = $chatGroupId;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat.group.' . $this->chatGroupId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'chat_group_id' => $this->chatGroupId,
            'is_typing' => $this->isTyping
        ];
    }
} 