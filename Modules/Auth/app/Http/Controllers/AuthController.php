<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }
        return view('auth::login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(
            ['username' => $request->username, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            Auth::user()->update(['last_login' => now()]);
            return $this->redirectByRole();
        }

        return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectByRole()
    {
        return match (Auth::user()->role) {
            'admin', 'coordinator' => redirect()->route('admin.dashboard'),
            'teacher'              => redirect()->route('teacher.dashboard'),
            default                => redirect()->route('login'),
        };
    }
}
