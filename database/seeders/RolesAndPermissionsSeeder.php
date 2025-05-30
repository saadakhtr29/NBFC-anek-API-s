<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Organization permissions
            ['name' => 'View Organizations', 'slug' => 'view-organizations'],
            ['name' => 'Create Organizations', 'slug' => 'create-organizations'],
            ['name' => 'Edit Organizations', 'slug' => 'edit-organizations'],
            ['name' => 'Delete Organizations', 'slug' => 'delete-organizations'],

            // Employee permissions
            ['name' => 'View Employees', 'slug' => 'view-employees'],
            ['name' => 'Create Employees', 'slug' => 'create-employees'],
            ['name' => 'Edit Employees', 'slug' => 'edit-employees'],
            ['name' => 'Delete Employees', 'slug' => 'delete-employees'],

            // Loan permissions
            ['name' => 'View Loans', 'slug' => 'view-loans'],
            ['name' => 'Create Loans', 'slug' => 'create-loans'],
            ['name' => 'Edit Loans', 'slug' => 'edit-loans'],
            ['name' => 'Delete Loans', 'slug' => 'delete-loans'],
            ['name' => 'Approve Loans', 'slug' => 'approve-loans'],
            ['name' => 'Reject Loans', 'slug' => 'reject-loans'],

            // Loan Deficit permissions
            ['name' => 'View Loan Deficits', 'slug' => 'view-loan-deficits'],
            ['name' => 'Create Loan Deficits', 'slug' => 'create-loan-deficits'],
            ['name' => 'Edit Loan Deficits', 'slug' => 'edit-loan-deficits'],
            ['name' => 'Delete Loan Deficits', 'slug' => 'delete-loan-deficits'],

            // Loan Excess permissions
            ['name' => 'View Loan Excess', 'slug' => 'view-loan-excess'],
            ['name' => 'Create Loan Excess', 'slug' => 'create-loan-excess'],
            ['name' => 'Edit Loan Excess', 'slug' => 'edit-loan-excess'],
            ['name' => 'Delete Loan Excess', 'slug' => 'delete-loan-excess'],

            // Document permissions
            ['name' => 'View Documents', 'slug' => 'view-documents'],
            ['name' => 'Upload Documents', 'slug' => 'upload-documents'],
            ['name' => 'Edit Documents', 'slug' => 'edit-documents'],
            ['name' => 'Delete Documents', 'slug' => 'delete-documents'],

            // Attendance permissions
            ['name' => 'View Attendance', 'slug' => 'view-attendance'],
            ['name' => 'Create Attendance', 'slug' => 'create-attendance'],
            ['name' => 'Edit Attendance', 'slug' => 'edit-attendance'],
            ['name' => 'Delete Attendance', 'slug' => 'delete-attendance'],

            // Dashboard permissions
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard'],
            ['name' => 'View Reports', 'slug' => 'view-reports'],

            // User management permissions
            ['name' => 'View Users', 'slug' => 'view-users'],
            ['name' => 'Create Users', 'slug' => 'create-users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles'],
            ['name' => 'Manage Permissions', 'slug' => 'manage-permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        // Create roles and assign permissions
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Super Administrator with all permissions',
                'permissions' => Permission::all()->pluck('slug')->toArray()
            ],
            [
                'name' => 'Organization Admin',
                'slug' => 'organization-admin',
                'description' => 'Organization Administrator',
                'permissions' => [
                    'view-organizations', 'edit-organizations',
                    'view-employees', 'create-employees', 'edit-employees', 'delete-employees',
                    'view-loans', 'create-loans', 'edit-loans', 'approve-loans', 'reject-loans',
                    'view-loan-deficits', 'create-loan-deficits', 'edit-loan-deficits',
                    'view-loan-excess', 'create-loan-excess', 'edit-loan-excess',
                    'view-documents', 'upload-documents', 'edit-documents', 'delete-documents',
                    'view-attendance', 'create-attendance', 'edit-attendance', 'delete-attendance',
                    'view-dashboard', 'view-reports'
                ]
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Regular Employee',
                'permissions' => [
                    'view-loans',
                    'view-documents',
                    'view-attendance'
                ]
            ]
        ];

        foreach ($roles as $role) {
            $permissions = $role['permissions'];
            unset($role['permissions']);
            
            $role = Role::firstOrCreate($role);
            $role->permissions()->syncWithoutDetaching(
                Permission::whereIn('slug', $permissions)->get()
            );
        }
    }
} 