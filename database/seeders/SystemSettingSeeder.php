<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        $settings = [
            [
                'key' => 'system_name',
                'value' => 'NBFC Management System',
                'description' => 'The name of the system',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'system_version',
                'value' => '1.0.0',
                'description' => 'The current version of the system',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'default_currency',
                'value' => 'INR',
                'description' => 'The default currency for the system',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'default_timezone',
                'value' => 'Asia/Kolkata',
                'description' => 'The default timezone for the system',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'max_loan_amount',
                'value' => 1000000,
                'description' => 'Maximum loan amount allowed',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'min_loan_amount',
                'value' => 1000,
                'description' => 'Minimum loan amount allowed',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'max_loan_term',
                'value' => 60,
                'description' => 'Maximum loan term in months',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'min_loan_term',
                'value' => 3,
                'description' => 'Minimum loan term in months',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'default_interest_rate',
                'value' => 12.5,
                'description' => 'Default interest rate for loans',
                'type' => 'float',
                'is_public' => false,
            ],
            [
                'key' => 'working_hours',
                'value' => [
                    'start' => '09:00:00',
                    'end' => '18:00:00',
                    'lunch_start' => '13:00:00',
                    'lunch_end' => '14:00:00'
                ],
                'description' => 'Default working hours',
                'type' => 'object',
                'is_public' => true,
            ],
            [
                'key' => 'allowed_file_types',
                'value' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                'description' => 'Allowed file types for document uploads',
                'type' => 'array',
                'is_public' => false,
            ],
            [
                'key' => 'max_file_size',
                'value' => 5,
                'description' => 'Maximum file size in MB',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'enable_email_notifications',
                'value' => true,
                'description' => 'Enable email notifications',
                'type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'enable_sms_notifications',
                'value' => false,
                'description' => 'Enable SMS notifications',
                'type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'description' => 'System maintenance mode',
                'type' => 'boolean',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting + ['updated_by' => $admin->id]
            );
        }
    }
} 