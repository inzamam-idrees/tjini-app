<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role' => ['required', 'string', 'in:parent,dispatcher,viewer'],
                'device_token' => ['nullable', 'string'],
            ]);

            // Create the user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'device_token' => $request->device_token,
            ]);

            // Assign the role
            $user->assignRole($request->role);

            // Create a token for the user
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Registration successful.',
            ], Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'device_token' => ['nullable', 'string'],
            ]);

            // Find the user by email
            $user = User::with('school')->where('email', $request->email)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Check if user has one of the allowed roles (parent, dispatcher, viewer)
            if (!$user->hasAnyRole(['parent', 'dispatcher', 'viewer'])) {
                return response()->json([
                    'message' => 'Access denied. Only parents and school staff can use the mobile app.'
                ], Response::HTTP_FORBIDDEN);
            }

            // Update the device token if provided
            if ($request->device_token) {
                $user->device_token = $request->device_token;
                $user->save();
            }

            // Create a token for the user
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Login successful.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * API for mobile app: forgot password (no old password required).
     * User enters email, if verified, can reset password.
     * POST: email, password, password_confirmation
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user has one of the allowed roles (parent, dispatcher, viewer)
        if (!$user->hasAnyRole(['parent', 'dispatcher', 'viewer'])) {
            return response()->json([
                'message' => 'Access denied. Only parents and school staff can update their password.'
            ], Response::HTTP_FORBIDDEN);
        }

        // If you use email verification, check it here
        // if (isset($user->email_verified_at) && !$user->email_verified_at) {
        //     return response()->json([
        //         'message' => 'Email not verified.'
        //     ], Response::HTTP_FORBIDDEN);
        // }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.'
        ], Response::HTTP_OK);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Clear device token so push notifications won't be sent to this device
            $user->device_token = null;
            $user->save();

            // Delete current access token if present
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        return response()->json([
            'message' => 'Logout successful.'
        ]);
    }
}