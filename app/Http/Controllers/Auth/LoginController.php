<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:5,1,login')->only('login');
    }

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if (is_string($request->input('login'))) {
            $login = trim($request->input('login'));
            // Email stored lowercase per check constraint; usernames keep their case.
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $login = Str::lower($login);
            }
            $request->merge(['login' => $login]);
        }

        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Username atau email wajib diisi.',
            'login.string' => 'Username atau email harus berupa teks.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.string' => 'Kata sandi harus berupa teks.',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::guard('web')->attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()
                ->withErrors(['login' => 'Username/email atau kata sandi salah.'])
                ->onlyInput('login', 'remember');
        }

        $user = Auth::guard('web')->user();
        if ($user->disabled_at !== null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['login' => 'Akun tidak aktif. Hubungi administrator.'])->onlyInput('login', 'remember');
        }

        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        return redirect()->intended($user->isStaff() ? route('staff.dashboard') : route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
