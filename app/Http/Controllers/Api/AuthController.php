<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'confirm_password' => 'required|same:password',
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
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'role' => $request->has('role') && $request->role === 'admin' ? 'admin' : 'user',
                'referral_code' => Str::random(12),
                'last_login' => now(),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => "Account register successfully.",
                'user_id' => $user->id,
                'token' => $user->createToken('tokens')->plainTextToken,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'result' => false,
                'message' => "Fail to register account. Please try again.",
            ], 400);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users',
                'password' => 'required',
            ],
            $messages = [
                'email.exists' => 'Email not associated with any account.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->all(),
            ], 400);
        }

        try {
            $user = User::where('email', $request->email)->firstOrFail();

            if (Hash::check($request->password, $user->password)) {
                $user->last_login = now();
                $user->save();

                return response()->json([
                    'result' => true,
                    'message' => 'Login successful.',
                    'user_id' => $user->id,
                    'token' => $user->createToken('tokens')->plainTextToken,
                ], 201);
            } else {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid email/password.',
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid email/password.',
            ], 400);
        }
    }

    public function logout(Request $request)
    {
        $user = User::find(Auth::id());

        if (!empty($user)) {
            $user->tokens()->delete();
            return response()->json([
                "message" => 'Logout successful.',
            ]);
        }

        return response()->json([
            "message" => 'User not found',
        ], 404);
    }

    public function profile()
    {
        $user = auth()->user();

        return response()->json([
            'result' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $user,
            ]
        ], 201);
    }
}
