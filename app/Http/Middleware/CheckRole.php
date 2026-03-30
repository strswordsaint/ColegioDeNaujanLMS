<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // 1. Redirect pending teachers
        if ($user->role === 'pending_teacher') {
            return redirect('/')->with('status', 'Your teacher account is pending administrator approval.');
        }

        // 2. MASTER OVERRIDE (GOD MODE): Allow Admins to access Teacher routes
        if ($user->role === 'admin' && $role === 'teacher') {
            return $next($request);
        }

        // 3. Standard strict role check
        if ($user->role !== $role) {
            abort(403, 'Unauthorized Access.');
        }

        return $next($request);
    }
}