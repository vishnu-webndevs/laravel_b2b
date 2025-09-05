<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin role
        $role = Role::create(['name' => 'super_admin']);
        
        // Assign all permissions to super admin role
        $role->givePermissionTo(Permission::all());

        // Create super admin user
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@b2b.com',
            'password' => bcrypt('password123'),
        ]);

        // Assign super admin role to user
        $user->assignRole('super_admin');
    }
}