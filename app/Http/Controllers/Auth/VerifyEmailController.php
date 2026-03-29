<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified from a public link.
     */
    public function __invoke(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Security check: ensure the hashed link matches the user's email
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid or expired verification link.');
        }

        // If not verified yet, verify them now
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Return a simple success page instead of logging them in
        return Inertia::render('Auth/VerifiedSuccess');
    }
}