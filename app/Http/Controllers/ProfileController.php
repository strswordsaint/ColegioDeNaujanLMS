<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Department;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('department:id,name');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'programs' => Department::orderBy('name')->get(['id', 'name']),
            'departmentName' => $user->department?->name,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $isImpersonating = $request->session()->has('impersonate_admin_id');

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'school_id' => $validated['school_id'] ?? $user->school_id,
            'contact_number' => $validated['contact_number'] ?? $user->contact_number,
            'program' => $user->role === 'student'
                ? ($validated['program'] ?? $user->program)
                : $user->program,
        ]);

        // Never wipe email verification when an Admin is impersonating a user
        if ($user->isDirty('email') && ! $isImpersonating) {
            $user->email_verified_at = null;
        }

        // Helper to delete previously uploaded custom avatar file if present
        $deleteOldUploadedAvatar = function ($avatarUrl) {
            if ($avatarUrl && !str_contains($avatarUrl, 'googleusercontent.com')) {
                $path = parse_url($avatarUrl, PHP_URL_PATH);
                $cleanPath = ltrim(str_replace('/storage/', '', $path ?? ''), '/');
                if ($cleanPath) {
                    Storage::disk('s3')->delete($cleanPath);
                }
            }
        };

        // 1. Handle uploading a new custom profile picture
        if ($request->hasFile('avatar')) {
            $deleteOldUploadedAvatar($user->avatar);
            $path = $request->file('avatar')->store('avatars', 's3');
            $user->avatar = Storage::disk('s3')->url($path);
        }
        // 2. Handle resetting back to Google Sync / Default
        elseif ($request->boolean('reset_avatar')) {
            $deleteOldUploadedAvatar($user->avatar);
            $user->avatar = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}