<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

class Authenticate extends Middleware
{
    public function handle($request, \Closure $next, ...$guards)
    {
        return parent::handle($request, function ($request) use ($next) {
            if ($request->user()->disabled_at !== null) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Akun tidak aktif.', [auth()->getDefaultDriver()], route('login'));
            }

            return app(AuthenticateSession::class)->handle($request, $next);
        }, ...$guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
