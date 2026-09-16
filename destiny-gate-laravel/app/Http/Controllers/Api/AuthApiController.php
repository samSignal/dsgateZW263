<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
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

        if (!$user || !Hash::check($request->password, $user->password)) {
            // No user_id yet — record the attempted login string itself so a string of
            // failed attempts against one account (or one being guessed) is visible.
            AuditLogger::log(null, $login, null, 'login_failed', "Failed login attempt for \"{$login}\"", $request, 422);
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            AuditLogger::log($user->id, $user->name, $user->role, 'login_failed', 'Login blocked — account deactivated', $request, 422);
            throw ValidationException::withMessages([
                'login' => ['Your account has been deactivated. Please contact the school office.'],
            ]);
        }

        // Update last signed in
        DB::table('users')->where('id', $user->id)->update(['last_signed_in' => now()]);

        // Create Sanctum token via User model (needed for createToken)
        $userModel = \App\Models\User::find($user->id);
        $token = $userModel->createToken('spa-token')->plainTextToken;

        AuditLogger::log($user->id, $user->name, $user->role, 'login', 'Logged in', $request, 200);

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
        $user = $request->user();
        AuditLogger::log($user->id, $user->name, $user->role, 'logout', 'Logged out', $request, 200);
        $user->currentAccessToken()->delete();
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

        // In a real system, send reset email here if an account exists.
        // Always return the same generic response so this endpoint can't be used
        // to enumerate which email addresses have accounts.
        return response()->json([
            'message' => 'If an account exists with this email, a password reset link has been sent.',
        ]);
    }

    public function guardianForgotPassword(Request $request)
    {
        $request->validate(['username' => 'required|string']);

        // Always return the same generic response so this endpoint can't be used
        // to enumerate registered phone numbers or which accounts have an email on file.
        return response()->json([
            'message' => 'If an account exists with this phone number and has an email on file, a password reset link has been sent.',
        ]);
    }
}
