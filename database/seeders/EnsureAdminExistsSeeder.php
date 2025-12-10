<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EnsureAdminExistsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Force ID 1 if possible, or update existing
        $user = User::find(1);
        
        if (!$user) {
            // Need to insert with ID 1 specifically to fix the FK issue
            // Eloquent create() might auto-increment differently depending on driver, 
            // but usually setting 'id' works if fillable or forced.
            
            DB::table('users')->insert([
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@pool.com',
                'password' => Hash::make('password'), // or bcrypt
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('User ID 1 created successfully.');
        } else {
            $this->command->info('User ID 1 already exists.');
        }
    }
}
