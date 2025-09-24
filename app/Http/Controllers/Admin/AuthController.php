<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        $title = 'Login';
        return view('admin.auth.login', compact('title'));
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        // Validate the input (email & password)
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        // Attempt to log the user in
        if (Auth::attempt($credentials, $remember)) {
            // Regenerate the session to prevent session fixation attacks:contentReference[oaicite:3]{index=3}.
            $request->session()->regenerate();

            // Redirect to the intended URL or dashboard
            return redirect()->intended(route('admin.dashboard'));
        }

        // Authentication failed — return back with error
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate and regenerate the session token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}