<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and log them in (saving them in the session).
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // This saves the user in the session.
        Auth::login($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user'    => $user,
        ], 201);
    }

    /**
     * Log in an existing user, storing them in the session.
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to log the user in; if successful, Laravel stores the user in the session.
        if (Auth::attempt($credentials)) {
            // Regenerate session to prevent fixation.
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login successful.',
                'user'    => Auth::user(),
                // You might include the new token if you need to update the client:
                'csrf_token' => $request->session()->token(),
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }

    /**
     * Log out the currently authenticated user.
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate the session and regenerate the CSRF token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
