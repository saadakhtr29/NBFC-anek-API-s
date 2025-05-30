<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $code = strtoupper(Str::slug($name)) . $this->faker->numberBetween(100, 999);
        $registrationNumber = 'REG' . $this->faker->numberBetween(100000, 999999);
        $taxNumber = 'TAX' . $this->faker->numberBetween(100000, 999999);

        $types = ['Private Limited', 'Public Limited', 'Partnership', 'Sole Proprietorship', 'LLC'];
        $industries = ['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail', 'Education', 'Real Estate'];
        $sizes = ['small', 'medium', 'large', 'enterprise'];
        $currencies = ['USD', 'EUR', 'GBP', 'INR', 'JPY', 'AUD', 'CAD'];
        $timezones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'];

        return [
            'name' => $name,
            'code' => $code,
            'type' => $this->faker->randomElement($types),
            'registration_number' => $registrationNumber,
            'tax_number' => $taxNumber,
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'password' => bcrypt('password123'),
            'website' => $this->faker->url(),
            'logo' => null,
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'founding_date' => $this->faker->dateTimeBetween('-20 years', 'now'),
            'industry' => $this->faker->randomElement($industries),
            'size' => $this->faker->randomElement($sizes),
            'annual_revenue' => $this->faker->randomFloat(2, 100000, 1000000000),
            'currency' => $this->faker->randomElement($currencies),
            'timezone' => $this->faker->randomElement($timezones),
            'settings' => [
                'theme' => $this->faker->randomElement(['light', 'dark']),
                'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true
                ]
            ],
            'remarks' => $this->faker->optional()->sentence()
        ];
    }

    /**
     * Indicate that the organization is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active'
        ]);
    }

    /**
     * Indicate that the organization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive'
        ]);
    }

    /**
     * Indicate that the organization is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended'
        ]);
    }

    /**
     * Indicate that the organization is small.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'small',
            'annual_revenue' => $this->faker->randomFloat(2, 100000, 1000000)
        ]);
    }

    /**
     * Indicate that the organization is medium.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'medium',
            'annual_revenue' => $this->faker->randomFloat(2, 1000000, 10000000)
        ]);
    }

    /**
     * Indicate that the organization is large.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'large',
            'annual_revenue' => $this->faker->randomFloat(2, 10000000, 100000000)
        ]);
    }

    /**
     * Indicate that the organization is enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'enterprise',
            'annual_revenue' => $this->faker->randomFloat(2, 100000000, 1000000000)
        ]);
    }
} 