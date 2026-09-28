<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached permissions and roles.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.change-status',
            'users.assign-roles',

            // Roles and permissions
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign-permissions',
            'roles.manage-permissions',
            'permissions.view',

            // Categories
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // SubCategories
            'subCategories.view',
            'subCategories.create',
            'subCategories.update',
            'subCategories.delete',

            // Drugs
            'drugs.view',
            'drugs.create',
            'drugs.update',
            'drugs.delete',

            // Drug batches
            'drug-batches.view',
            'drug-batches.create',
            'drug-batches.update',
            'drug-batches.delete',

            // Inventory
            'inventory.view',
            'inventory.receive-stock',
            'inventory.adjust-stock',
            'inventory.approve-adjustment',
            'inventory.view-movements',
            'inventory.view-expiry',

            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            // Purchases
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.approve',
            'purchases.cancel',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            // Sales
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.cancel',
            'sales.approve-cancellation',
            'sales.refund',
            'sales.print-receipt',

            // Reports
            'reports.view',
            'reports.view-sales',
            'reports.view-inventory',
            'reports.export',

            // Audit logs
            'audit-logs.view',

            // System settings
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $pharmacyManager = Role::firstOrCreate([
            'name' => 'pharmacy-manager',
            'guard_name' => 'web',
        ]);

        $pharmacist = Role::firstOrCreate([
            'name' => 'pharmacist',
            'guard_name' => 'web',
        ]);

        $cashier = Role::firstOrCreate([
            'name' => 'cashier',
            'guard_name' => 'web',
        ]);

        $inventoryOfficer = Role::firstOrCreate([
            'name' => 'inventory-officer',
            'guard_name' => 'web',
        ]);

        $auditor = Role::firstOrCreate([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Super Admin Permissions
        |--------------------------------------------------------------------------
        */

        $superAdmin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Manager Permissions
        |--------------------------------------------------------------------------
        */

        $pharmacyManager->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'users.change-status',
            'users.assign-roles',

            'roles.view',
            'permissions.view',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            'subCategories.view',
            'subCategories.create',
            'subCategories.update',
            'subCategories.delete',

            'drugs.view',
            'drugs.create',
            'drugs.update',

            'drug-batches.view',
            'drug-batches.create',
            'drug-batches.update',

            'inventory.view',
            'inventory.receive-stock',
            'inventory.adjust-stock',
            'inventory.view-movements',
            'inventory.view-expiry',
            'inventory.approve-adjustment',

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',

            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.approve',

            'customers.view',
            'customers.create',
            'customers.update',

            'sales.view',
            'sales.create',
            'sales.cancel',
            'sales.print-receipt',

            'reports.view',
            'reports.view-sales',
            'reports.view-inventory',
            'reports.export',

            'audit-logs.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pharmacist Permissions
        |--------------------------------------------------------------------------
        */

        $pharmacist->syncPermissions([
            'categories.view',
            'categories.create',
            'categories.update',
            'subCategories.view',
            'subCategories.create',
            'subCategories.update',

            'drugs.view',
            'drugs.create',
            'drugs.update',

            'drug-batches.view',
            'drug-batches.create',
            'drug-batches.update',

            'inventory.view',
            'inventory.receive-stock',
            'inventory.view-movements',
            'inventory.view-expiry',

            'suppliers.view',

            'customers.view',
            'customers.create',
            'customers.update',

            'sales.view',
            'sales.create',
            'sales.print-receipt',

            'reports.view',
            'reports.view-inventory',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cashier Permissions
        |--------------------------------------------------------------------------
        */

        $cashier->syncPermissions([
            'categories.view',
            'subCategories.view',
            'drugs.view',
            'drug-batches.view',

            'inventory.view',

            'customers.view',
            'customers.create',
            'customers.update',

            'sales.view',
            'sales.create',
            'sales.print-receipt',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Inventory Officer Permissions
        |--------------------------------------------------------------------------
        */

        $inventoryOfficer->syncPermissions([
            'categories.view',
            'categories.create',
            'categories.update',
            'subCategories.view',
            'subCategories.create',
            'subCategories.update',
            'drugs.view',
            'drugs.create',
            'drugs.update',

            'drug-batches.view',
            'drug-batches.create',
            'drug-batches.update',

            'inventory.view',
            'inventory.receive-stock',
            'inventory.adjust-stock',
            'inventory.approve-adjustment',
            'inventory.view-movements',
            'inventory.view-expiry',

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',

            'purchases.view',
            'purchases.create',
            'purchases.update',

            'reports.view',
            'reports.view-inventory',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Auditor Permissions
        |--------------------------------------------------------------------------
        */

        $auditor->syncPermissions([
            'users.view',
            'roles.view',
            'permissions.view',
            'categories.view',
            'subCategories.view',
            'drugs.view',
            'drug-batches.view',

            'inventory.view',
            'inventory.view-movements',
            'inventory.view-expiry',

            'suppliers.view',
            'purchases.view',

            'customers.view',

            'sales.view',

            'reports.view',
            'reports.view-sales',
            'reports.view-inventory',
            'reports.export',

            'audit-logs.view',
        ]);

        // Clear cache after seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            'Roles and permissions seeded successfully.'
        );
    }
}
