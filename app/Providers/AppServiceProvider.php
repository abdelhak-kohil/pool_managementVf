<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Safely auto-register all permissions from DB
        try {
            // Check if the table exists first
            if (Schema::hasTable('pool_schema.permissions')) {
                // Load all permissions from PostgreSQL
                $permissions = Permission::all();

                foreach ($permissions as $permission) {
                    Gate::define($permission->permission_name, function ($user) use ($permission) {
                        return $user->hasPermission($permission->permission_name);
                    });
                }
            }
        } catch (\Throwable $e) {
            // Avoid breaking artisan commands if DB isn't ready
            // You could log it if needed:
            // \Log::info('Skipping Gate load: ' . $e->getMessage());
        }
    }
}
