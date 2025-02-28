<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogTransactionStatus
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $userId;
    public $transactionId;
    public $status;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $transactionId, $status, $message)
    {
        $this->userId = $userId;
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->message = $message;
    }
}
