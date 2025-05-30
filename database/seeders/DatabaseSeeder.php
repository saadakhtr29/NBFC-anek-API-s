<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
            ]
        );

        $this->call([
            RolesAndPermissionsSeeder::class,
            // UserSeeder::class,
            // OrganizationSeeder::class,
            // EmployeeSeeder::class,
            OrganizationSettingSeeder::class,
            SystemSettingSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
