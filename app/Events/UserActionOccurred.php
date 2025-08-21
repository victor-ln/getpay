<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActionOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?User $user;
    public string $action;
    public array $context;
    public string $level;
    public string $message;

    public function __construct(?User $user, string $action, array $context = [], string $message = '', string $level = 'info')
    {
        $this->user = $user;
        $this->action = $action;
        $this->context = $context;
        $this->message = $message;
        $this->level = $level;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
