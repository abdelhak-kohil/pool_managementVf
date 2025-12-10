<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = Staff::where('username', strtolower($request->username))->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return back()->withErrors(['login' => 'Nom d\'utilisateur ou mot de passe incorrect.']);
        }

        if (!$user->is_active) {
            return back()->withErrors(['login' => 'Le compte est inactif.']);
        }

        Auth::login($user);
        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
