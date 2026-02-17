<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoogleAuthController extends Controller
{
    // 1. Send user to Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // 2. Handle return from Google
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // New User: Create with 'pending' role
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, 
                    'role' => 'pending', 
                ]);
            } else {
                // Existing User: Link Google ID if missing
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            Auth::login($user);

            // If setup is incomplete (no school_id or role is pending), go to Onboarding
            if ($user->role === 'pending' || empty($user->school_id)) {
                return redirect()->route('register.onboarding');
            }

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google Login Failed');
        }
    }

    // 3. Show the Multi-Step Form
    public function onboarding()
    {
        return Inertia::render('Auth/Onboarding', [
            'user' => Auth::user()
        ]);
    }

    // 4. Save Final Details
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'role' => 'required|in:student,teacher',
            'school_id' => 'required|string|max:50',
            'contact_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ]);

        $user = User::find(Auth::id());
        
        // Set to pending_teacher for approval logic
        $finalRole = $request->role === 'teacher' ? 'pending_teacher' : 'student';

        $user->update([
            'role' => $finalRole,
            'school_id' => $request->school_id,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('dashboard');
    }
}