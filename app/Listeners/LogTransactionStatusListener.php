<?php

namespace App\Listeners;

use App\Events\LogTransactionStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogTransactionStatusListener
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
    public function handle(LogTransactionStatus $event): void
    {
        Log::info('User Id: ' . $event->userId . ' - Transaction ID: ' . $event->transactionId . ' - Status: ' . $event->status . ' - Message: ' . $event->message);

        DB::table('log_transaction_status')->insert([
            'user_id' => $event->userId,
            'transfers_id' => $event->transactionId,
            'status' => $event->status,
            'message' => $event->message,
            'transfer_timestamp' => now(),
        ]);
    }
}
