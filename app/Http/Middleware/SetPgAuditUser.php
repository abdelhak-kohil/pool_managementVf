<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetPgAuditUser
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Auth::check()) {
                $staffId = Auth::user()->staff_id ?? null;

                if ($staffId) {
                    DB::statement("SET session app.current_staff_id = '" . intval($staffId) . "'");
                } else {
                    DB::statement("SET session app.current_staff_id = NULL");
                }
            } else {
                DB::statement("SET session app.current_staff_id = NULL");
            }
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la définition du current_staff_id dans PostgreSQL : ' . $e->getMessage());
        }

        return $next($request);
    }

    public function terminate($request, $response)
{
    try {
        DB::statement("RESET app.current_staff_id");
    } catch (\Throwable $e) {
        Log::warning('Impossible de réinitialiser app.current_staff_id : ' . $e->getMessage());
    }
}

}
