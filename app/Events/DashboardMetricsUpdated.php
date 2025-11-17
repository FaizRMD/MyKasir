<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Jika tidak perlu data tambahan, biarkan kosong

    /** Channel broadcast (ubah sesuai kebutuhanmu) */
    public function broadcastOn(): Channel
    {
        // Channel publik bernama "dashboard"
        return new Channel('dashboard');
    }

    /** Nama event saat dibroadcast (opsional) */
    public function broadcastAs(): string
    {
        return 'metrics.updated';
    }

    /** Payload ke frontend (opsional) */
    public function broadcastWith(): array
    {
        return [];
    }
}
