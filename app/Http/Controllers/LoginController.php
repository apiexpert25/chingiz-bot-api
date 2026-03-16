<?php

namespace App\Http\Controllers;

use App\Entities\UserEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userEntity = UserEntity::findByEmail($credentials['email']);

        if ($userEntity && Hash::check($credentials['password'], $userEntity->getPassword())) {
            Auth::login($userEntity->getUser());
            $request->session()->regenerate();
            return redirect()->intended(route('prompts.edit'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
