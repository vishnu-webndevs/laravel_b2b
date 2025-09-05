<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superadminRole = Role::create(['name' => 'superadmin']);
        $adminRole = Role::create(['name' => 'admin']);
        $vendorRole = Role::create(['name' => 'vendor']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create permissions
        $manageUsers = Permission::create(['name' => 'manage users']);
        $manageRoles = Permission::create(['name' => 'manage roles']);
        $managePermissions = Permission::create(['name' => 'manage permissions']);
        $manageVendors = Permission::create(['name' => 'manage vendors']);
        $manageProducts = Permission::create(['name' => 'manage products']);
        $manageOrders = Permission::create(['name' => 'manage orders']);
        $viewDashboard = Permission::create(['name' => 'view dashboard']);

        // Assign permissions to roles
        $superadminRole->givePermissionTo([
            $manageUsers,
            $manageRoles,
            $managePermissions,
            $manageVendors,
            $manageProducts,
            $manageOrders,
            $viewDashboard,
        ]);

        $adminRole->givePermissionTo([
            $manageUsers,
            $manageVendors,
            $manageProducts,
            $manageOrders,
            $viewDashboard,
        ]);

        $vendorRole->givePermissionTo([
            $manageProducts,
            $manageOrders,
            $viewDashboard,
        ]);

        $customerRole->givePermissionTo([
            $manageOrders,
            $viewDashboard,
        ]);
    }
}