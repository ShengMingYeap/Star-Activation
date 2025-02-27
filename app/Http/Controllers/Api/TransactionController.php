<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transactions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function getTransaction(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->all(),
            ], 400);
        }

        $userRole = Auth::user()->role;
        $userId = Auth::id();

        $transaction = Transactions::where('transaction_id', $id)
                        ->when($request->has('status'), function ($query) use ($request) {
                            return $query->where('transaction_status', $request->input('status'));
                        })->get();

        if (!$transaction) {
            return response()->json([
                'result' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        // Regular users can only query their own transactions.
        if ($userRole == 'user') {
            $isAuthorized = $transaction->contains(function ($record) {
                return $record->user_id === Auth::id();
            });
        
            if (!$isAuthorized) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 400);
            }
        }

        return response()->json([
            'result' => true,
            'message' => 'Transaction retrieved successfully.',
            'data' => $transaction
        ], 201);
    }
}
