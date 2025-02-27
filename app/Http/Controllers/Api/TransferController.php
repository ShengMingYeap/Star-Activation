<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transfers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Jobs\ProcessTransfer;
use App\Events\LogTransactionStatus;

class TransferController extends Controller
{
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'transfers_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->all(),
            ], 400);
        }

        $user = auth()->user();
        
        // check user balance
        if ($user->balance < $request->amount) {
            event(new LogTransactionStatus($user->id, null, 'failed', 'Insufficient balance'));

            return response()->json([
                "result" => false,
                "message" => 'Insufficient balance.',
            ], 400);
        }

        // receiver id cannot same user
        if ($request->receiver_id == Auth::id()) {
            event(new LogTransactionStatus($user->id, null, 'failed', 'Cannot transfer amount for yourself'));

            return response()->json([
                "result" => false,
                "message" => 'Cannot transfer amount for yourself.',
            ], 400);
        }

        if ($request->has('transfers_id') && !empty($request->transfers_id)) {
            $transfer = Transfers::where('sender_id', $user->id)->where('transfers_id', $request->transfers_id)->first();
            if ($transfer) {
                return response()->json([
                    "result" => false,
                    "message" => 'Already submission. Your transfer status is '.$transfer->transfer_status.'.',
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            $transfer_id = rand(10000000, 99999999);

            $transfer = Transfers::create([
                'transfers_id' => 'T'.$transfer_id,
                'sender_id' => Auth::id(),
                'receiver_id' => $request->receiver_id,
                'transfer_amount' => $request->amount,
                'transfer_status' => 'pending',
                'transfer_timestamp' => now(),
            ]);

            DB::commit();

            ProcessTransfer::dispatch($user->id, $transfer->transfers_id);

            return response()->json([
                'result' => true,
                'message' => "Transaction is being processed.",
            ], 201);  
        } catch (Exception $e) {
            DB::rollBack();
            event(new LogTransactionStatus($user->id, null, 'failed', 'Error processing transfer'));

            return response()->json([
                'result' => false,
                'message' => "Something went wrong.",
            ], 400);
        }
    }
}
