<?php

namespace App\Modules\Licensing\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Licensing\Facades\License;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        if (!License::isValid()) {
            return response()->json(['message' => 'License invalid or expired.'], 403);
            // In a real app, redirect to license activation page if not API
        }

        return $next($request);
    }
}
