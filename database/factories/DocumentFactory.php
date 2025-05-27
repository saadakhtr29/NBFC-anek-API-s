<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $file = $this->faker->file(public_path('storage/documents'));
        $mimeType = mime_content_type($file);
        $fileSize = filesize($file);

        return [
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']),
            'description' => $this->faker->paragraph,
            'file_path' => $file,
            'file_name' => basename($file),
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'tags' => $this->faker->words(3),
            'organization_id' => Organization::factory(),
            'uploaded_by' => User::factory()
        ];
    }
} 