<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();
        $admin = User::where('email', 'admin@example.com')->first();

        foreach ($organizations as $organization) {
            // Create default settings for each organization
            $settings = [
                [
                    'key' => 'working_hours_start',
                    'value' => '09:00',
                    'description' => 'Default working hours start time',
                    'is_public' => true,
                ],
                [
                    'key' => 'working_hours_end',
                    'value' => '18:00',
                    'description' => 'Default working hours end time',
                    'is_public' => true,
                ],
                [
                    'key' => 'lunch_break_start',
                    'value' => '13:00',
                    'description' => 'Default lunch break start time',
                    'is_public' => true,
                ],
                [
                    'key' => 'lunch_break_end',
                    'value' => '14:00',
                    'description' => 'Default lunch break end time',
                    'is_public' => true,
                ],
                [
                    'key' => 'overtime_threshold',
                    'value' => '8',
                    'description' => 'Hours after which overtime is calculated',
                    'is_public' => true,
                ],
                [
                    'key' => 'leave_approval_required',
                    'value' => 'true',
                    'description' => 'Whether leave approval is required',
                    'is_public' => true,
                ],
                [
                    'key' => 'salary_day',
                    'value' => '25',
                    'description' => 'Day of the month when salary is paid',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_name',
                    'value' => $organization->name,
                    'description' => 'Organization name',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_address',
                    'value' => $organization->address,
                    'description' => 'Organization address',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_phone',
                    'value' => $organization->phone,
                    'description' => 'Organization phone number',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_email',
                    'value' => $organization->email,
                    'description' => 'Organization email',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_website',
                    'value' => $organization->website,
                    'description' => 'Organization website',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_logo',
                    'value' => $organization->logo_url,
                    'description' => 'Organization logo URL',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_description',
                    'value' => $organization->description,
                    'description' => 'Organization description',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_currency',
                    'value' => 'INR',
                    'description' => 'Organization currency',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_timezone',
                    'value' => 'Asia/Kolkata',
                    'description' => 'Organization timezone',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_language',
                    'value' => 'en',
                    'description' => 'Organization language',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_date_format',
                    'value' => 'Y-m-d',
                    'description' => 'Organization date format',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_time_format',
                    'value' => 'H:i',
                    'description' => 'Organization time format',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_number_format',
                    'value' => '#,##0.00',
                    'description' => 'Organization number format',
                    'is_public' => true,
                ],
            ];

            foreach ($settings as $setting) {
                OrganizationSetting::create([
                    'organization_id' => $organization->id,
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'is_public' => $setting['is_public'],
                    'updated_by' => $admin->id,
                ]);
            }
        }
    }
} 