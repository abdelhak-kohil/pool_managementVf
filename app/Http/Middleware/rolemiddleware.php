<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 🔹 1. User not logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        // 🔹 2. Get user role
        $user = Auth::user();
        $userRole = strtolower($user->role->role_name ?? '');

        if (!$userRole) {
            return redirect()->route('admin.dashboard')
                ->with('error', "Votre compte n'a aucun rôle assigné.");
        }

        // 🔹 3. Normalize role list
        $roles = array_map('strtolower', $roles);

        // 🔹 4. Permission check
        if (!in_array($userRole, $roles)) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Accès refusé : vous n’avez pas les permissions nécessaires.');
        }

        return $next($request);
    }
}
