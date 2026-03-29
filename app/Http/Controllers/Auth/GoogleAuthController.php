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
    // Redirect to Google
    public function redirect()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->scopes([
                'openid', 'profile', 'email'
            ])
            ->redirect();
    }

    // Handle return from Google
    public function callback()
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, 
                    'role' => 'pending', 
                    'email_verified_at' => now(), 
                ]);
            } else {
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            Auth::login($user);

            if ($user->role === 'pending' || empty($user->school_id)) {
                return redirect()->route('register.onboarding');
            }

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google Login Failed');
        }
    }
    
    // Show the Multi-Step Form
    public function onboarding()
    {
        return Inertia::render('Auth/Onboarding', [
            'user' => Auth::user()
        ]);
    }

    // Save Final Details
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'school_id' => 'required|string|max:50',
            'program' => 'required|string|max:100', // Validate the new program field
            'contact_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ]);

        $user = User::find(Auth::id());
        
        $user->update([
            'role' => 'student', 
            'school_id' => $request->school_id,
            'program' => $request->program, // Save the new program field
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('dashboard');
    }
}