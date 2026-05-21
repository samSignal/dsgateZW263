<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',   // accepts email, username, or phone
            'password' => 'required|string',
        ]);

        $login = trim($request->login);

        // Find user by email OR username (student_number / phone stored here)
        $user = DB::table('users')
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => ['No account found with these credentials.'],
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account has been deactivated. Please contact the school office.'],
            ]);
        }

        // Update last signed in
        DB::table('users')->where('id', $user->id)->update(['last_signed_in' => now()]);

        // Create Sanctum token via User model (needed for createToken)
        $userModel = \App\Models\User::find($user->id);
        $token = $userModel->createToken('spa-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'username'            => $user->username,
                'role'                => $user->role,
                'must_change_password'=> (bool) $user->must_change_password,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'username'            => $user->username,
            'role'                => $user->role,
            'phone'               => $user->phone,
            'must_change_password'=> (bool) $user->must_change_password,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password'             => Hash::make($request->new_password),
            'must_change_password' => false,
            'updated_at'           => now(),
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user) {
            // Check if this is a guardian with no email
            $guardian = DB::table('guardians')->where('email', $request->email)->first();
            if (!$guardian) {
                return response()->json([
                    'message' => 'No account found with this email address. Please contact the school office.',
                ], 404);
            }
        }

        // In a real system, send reset email here
        // For now, return success message
        return response()->json([
            'message' => 'If an account exists with this email, a password reset link has been sent.',
        ]);
    }

    public function guardianForgotPassword(Request $request)
    {
        $request->validate(['username' => 'required|string']);

        // Find guardian by phone (username)
        $user = DB::table('users')->where('username', $request->username)->where('role', 'parent')->first();

        if (!$user) {
            return response()->json(['message' => 'No parent account found with this phone number.'], 404);
        }

        if (!$user->email) {
            return response()->json([
                'message' => 'No email address is registered for this account. Please contact the school office.',
                'has_email' => false,
            ], 422);
        }

        return response()->json([
            'message'   => 'A password reset link has been sent to ' . substr($user->email, 0, 3) . '***',
            'has_email' => true,
        ]);
    }
}
