<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'User does not have the right roles.');
        }

        // Support multiple roles separated by pipe (|)
        $allowedRoles = explode('|', $role);
        
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'User does not have the right roles.');
        }

        return $next($request);
    }
}
