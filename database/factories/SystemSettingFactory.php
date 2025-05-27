<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $types = ['string', 'integer', 'float', 'boolean', 'array', 'object'];
        $type = $this->faker->randomElement($types);
        $value = match($type) {
            'string' => $this->faker->sentence(),
            'integer' => $this->faker->numberBetween(1, 1000),
            'float' => $this->faker->randomFloat(2, 1, 1000),
            'boolean' => $this->faker->boolean(),
            'array' => json_encode($this->faker->words(3)),
            'object' => json_encode(['key' => $this->faker->word(), 'value' => $this->faker->word()]),
            default => $this->faker->word(),
        };

        return [
            'key' => $this->faker->unique()->word(),
            'value' => $value,
            'description' => $this->faker->sentence(),
            'type' => $type,
            'is_public' => $this->faker->boolean(),
            'updated_by' => User::factory(),
        ];
    }
} 