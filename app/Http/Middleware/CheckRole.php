<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
     
    // 1. Add $role to the function here
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = auth()->user();

        if ($user && $user->role === 'pending_teacher') {
            return redirect('/')->with('status', 'Your teacher account is pending administrator approval.');
        }

        if ($user && $user->role !== $role) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}