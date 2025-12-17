<?php

namespace App\Modules\Licensing\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Licensing\Facades\License;

class VerifyModuleAccess
{

    public function handle(Request $request, Closure $next, string $module)
    {
        if (!License::isValid()) {
             return response()->json(['message' => 'System License invalid.'], 403);
        }

        if (!License::hasModule($module)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => "Module '{$module}' is not enabled in your license."], 403);
            }
            abort(403, "Access to '{$module}' module is denied by your license.");
        }

        return $next($request);
    }
}
