<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Transfers;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\LogTransactionStatus;

class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $retryAfter = 60;
    public $user_id;
    public $transfer_id;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $transfer_id)
    {
        $this->user_id = $user_id;
        $this->transfer_id = $transfer_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();
        $transfer = Transfers::where('transfers_id', $this->transfer_id)->first();
        if (!$transfer) {
            // throw new Exception('Transfer id not found. '. $this->transfer_id);
            event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'failed', 'Transfer id not found'));
            return;
        }

        try {
            $sender = User::find($transfer->sender_id);
            $receiver = User::find($transfer->receiver_id);

            if (!$sender || !$receiver) {
                // throw new Exception('Sender or Receiver not found for Transfer ID: ' . $this->transfer_id);
                event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'failed', 'Sender or Receiver not found'));
                return;
            }

            if ($sender->balance < $transfer->transfer_amount) {
                // throw new Exception('Insufficient balance for Transfer ID: ' . $this->transfer_id);
                event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'failed', 'Insufficient balance'));
                return;
            }

            // process debit & fund transfer
            $sender->balance -= $transfer->transfer_amount;
            $receiver->balance += $transfer->transfer_amount;
            $sender->save();
            $receiver->save();

            // Update transaction status
            $transfer->transfer_status = 'completed';
            $transfer->save();

            // store in transaction table
            DB::table('transactions')->insert([
                [
                    'transaction_id' => $transfer->transfers_id, 
                    'user_id' => $transfer->receiver_id,
                    'transaction_amount' => $transfer->transfer_amount,
                    'transaction_status' => 'completed',
                    'transaction_type' => '3',
                    'action_reason' => 'add balance',
                    'transaction_timestamp' => now(),
                    'created_at' => now(), 
                    'updated_at' => now()
                ],
                [
                    'transaction_id' => $transfer->transfers_id, 
                    'user_id' => $transfer->sender_id,
                    'transaction_amount' => '-'.$transfer->transfer_amount,
                    'transaction_status' => 'completed',
                    'transaction_type' => '3',
                    'action_reason' => 'deduct balance',
                    'transaction_timestamp' => now(),
                    'created_at' => now(), 
                    'updated_at' => now()
                ],
            ]);

            event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'completed', 'Transaction completed successfully'));

            DB::commit();
        } catch (Exception $e) {
            // Log::error("Error processing transfer: " . $e->getMessage() . ". Transfer ID: " . $this->transfer_id);
            event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'failed', 'Error processing transfer'));
            DB::rollBack();

            if (isset($transfer)) {
                $transfer->transfer_status = 'failed';
                $transfer->save();
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        // Log::error('Job failed with message: ' . $exception->getMessage() . ". Transfer ID: " . $this->transfer_id);
        event(new LogTransactionStatus($this->user_id, $this->transfer_id, 'failed', $exception->getMessage()));
        $transfer = Transfers::where('transfers_id', $this->transfer_id)->first();

        if ($transfer) {
            $transfer->transfer_status = 'failed';
            $transfer->save();
        }
    }
}
