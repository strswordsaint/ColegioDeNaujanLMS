<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // === SUSPENSION CHECK ===
        if ($request->user()->status === 'suspended') {
            $reason = $request->user()->suspension_reason ?? 'Violation of school policies.';
            
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Send them back to login with the specific error message
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been suspended. Reason: ' . $reason
            ]);
        }

        $request->session()->regenerate();

        // === ROLE CHECK ===
        $role = $request->user()->role;

        if ($role === 'teacher') {
            return redirect()->intended(route('teacher.dashboard'));
        }

        if ($role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Default (Student)
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // CHANGE: Redirect to Login instead of Welcome ('/')
        return redirect()->route('login');
    }
}