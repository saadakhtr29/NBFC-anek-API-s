<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::create([
            'name' => 'Test Organization',
            'code' => 'TEST001',
            'type' => 'Private Limited',
            'registration_number' => 'REG123456',
            'tax_number' => 'TAX789012',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'postal_code' => '12345',
            'phone' => '1234567890',
            'email' => 'org@example.com',
            'password' => Hash::make('password123'),
            'website' => 'https://test.com',
            'description' => 'Test organization for development',
            'status' => 'active',
            'founding_date' => '2020-01-01',
            'industry' => 'Technology',
            'size' => 'medium',
            'annual_revenue' => 1000000.00,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'settings' => [
                'theme' => 'light',
                'language' => 'en'
            ],
            'remarks' => 'Test organization'
        ]);
    }
} 