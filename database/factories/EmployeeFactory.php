<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        $employmentTypes = ['full_time', 'part_time', 'contract', 'intern'];
        $statuses = ['active', 'inactive', 'on_leave', 'terminated'];
        $departments = ['IT', 'HR', 'Finance', 'Operations', 'Sales', 'Marketing'];
        $designations = ['Manager', 'Senior Executive', 'Executive', 'Associate', 'Trainee'];

        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'employee_id' => 'EMP' . $this->faker->unique()->numberBetween(1000, 9999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-20 years'),
            'date_of_joining' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'designation' => $this->faker->randomElement($designations),
            'department' => $this->faker->randomElement($departments),
            'salary' => $this->faker->numberBetween(30000, 150000),
            'status' => $this->faker->randomElement($statuses),
            'employment_type' => $this->faker->randomElement($employmentTypes),
            'bank_name' => $this->faker->company(),
            'bank_account_number' => $this->faker->bankAccountNumber(),
            'bank_ifsc_code' => $this->faker->regexify('[A-Z]{4}0[A-Z0-9]{6}'),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
            'documents' => [
                'id_proof' => $this->faker->imageUrl(),
                'address_proof' => $this->faker->imageUrl(),
                'education_certificates' => [$this->faker->imageUrl()],
            ],
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }

    public function onLeave()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'on_leave',
            ];
        });
    }

    public function terminated()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'terminated',
            ];
        });
    }

    public function fullTime()
    {
        return $this->state(function (array $attributes) {
            return [
                'employment_type' => 'full_time',
            ];
        });
    }

    public function partTime()
    {
        return $this->state(function (array $attributes) {
            return [
                'employment_type' => 'part_time',
            ];
        });
    }

    public function contract()
    {
        return $this->state(function (array $attributes) {
            return [
                'employment_type' => 'contract',
            ];
        });
    }

    public function intern()
    {
        return $this->state(function (array $attributes) {
            return [
                'employment_type' => 'intern',
            ];
        });
    }
} 