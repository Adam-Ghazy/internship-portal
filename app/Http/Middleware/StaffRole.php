<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        abort_if($user->disabled_at !== null, 403);
        foreach ($roles as $role) {
            if (in_array($role, ['admin', 'manager', 'sm'], true) && $user->hasRole($role)) {
                return $next($request);
            }
        }
        abort(403);
    }
}
