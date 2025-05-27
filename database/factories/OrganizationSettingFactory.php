<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationSetting>
 */
class OrganizationSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'key' => $this->faker->unique()->word,
            'value' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'is_public' => $this->faker->boolean,
            'updated_by' => User::factory(),
        ];
    }
} 