<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Exception;

class UserAccountController extends Controller
{
    public function tradingAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->all(),
            ], 400);
        }

        $user = User::with(['account' => function ($query) use ($request) {
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        }])->find(auth()->user()->id);

        if (!$user) {
            return response()->json([
                'result' => false,
                'message' => 'User not found.',
            ], 400);
        }

        return response()->json([
            'result' => true,
            'message' => 'User account retrieved successfully.',
            'data' => $user
        ], 201);
    }

    public function createAccount(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'server' => 'required|string',
                'leverage' => 'required',
                'type' => 'required|in:live,demo',
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->all(),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $userAccount = UserAccount::create([
                'receiver_id' => Auth::id(),
                'login' => $this->generateUniqueLogin(),
                'password' => Str::random(12),
                'balance' => 0,
                'server' => $request->input('server'),
                'leverage' => $request->input('leverage'),
                'type' => $request->input('type'),
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => "Trading account create successfully.",
                'data' => $userAccount,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'result' => false,
                'message' => "Fail to create trading account.",
            ], 400);
        }
    }

    public function generateUniqueLogin()
    {
        do {
            $login = rand(10000000, 99999999);

            $exists = DB::table('user_accounts')->where('login', $login)->exists();
        } while ($exists);

        return $login;
    }
}
