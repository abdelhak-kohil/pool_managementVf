<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('pool_schema.roles')->pluck('role_id', 'role_name');

        $staffMembers = [
            ['first_name' => 'Ali', 'last_name' => 'Ben', 'username' => 'directeur', 'role' => 'directeur'],
            ['first_name' => 'Sara', 'last_name' => 'Fin', 'username' => 'financer', 'role' => 'financer'],
            ['first_name' => 'Mounir', 'last_name' => 'Fix', 'username' => 'maintenance', 'role' => 'maintenance_manager'],
            ['first_name' => 'Lina', 'last_name' => 'Rec', 'username' => 'reception', 'role' => 'réceptionniste'],
            ['first_name' => 'Admin', 'last_name' => 'User', 'username' => 'admin', 'role' => 'admin'],
        ];

        foreach ($staffMembers as $s) {
            DB::table('pool_schema.staff')->updateOrInsert(
                ['username' => $s['username']],
                [
                    'first_name'    => $s['first_name'],
                    'last_name'     => $s['last_name'],
                    'password_hash' => Hash::make('123'),
                    'role_id'       => $roles[$s['role']],
                    'is_active'     => true,
                    'created_at'    => now(),
                ]
            );
        }
    }
}
