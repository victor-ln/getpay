<?php

namespace App\Listeners;

use App\Events\UserActionOccurred;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserAction
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserActionOccurred $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user?->id,
            'action' => $event->action,
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
