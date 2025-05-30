<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        Employee::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'phone' => '1234567890',
            'address' => 'Test Address',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'postal_code' => '123456',
            'position' => 'Test Position',
            'department' => 'Test Department',
            'joining_date' => now(),
            'status' => 'active',
            'organization_id' => 1,
        ]);
    }
} 