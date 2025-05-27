<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_documents()
    {
        Document::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'type',
                        'description',
                        'file_name',
                        'file_size',
                        'mime_type',
                        'tags',
                        'organization_id',
                        'uploaded_by',
                        'created_at',
                        'updated_at',
                        'download_url',
                        'formatted_file_size',
                        'organization',
                        'uploaded_by_user'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_upload_a_document()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/documents', [
            'title' => 'Test Document',
            'type' => 'pdf',
            'description' => 'Test Description',
            'file' => $file,
            'tags' => ['test', 'document'],
            'organization_id' => $this->organization->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'type',
                    'description',
                    'file_name',
                    'file_size',
                    'mime_type',
                    'tags',
                    'organization_id',
                    'uploaded_by',
                    'created_at',
                    'updated_at',
                    'download_url',
                    'formatted_file_size',
                    'organization',
                    'uploaded_by_user'
                ]
            ]);

        $this->assertDatabaseHas('documents', [
            'title' => 'Test Document',
            'type' => 'pdf',
            'description' => 'Test Description',
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id
        ]);

        Storage::disk('documents')->assertExists($file->hashName());
    }

    /** @test */
    public function it_can_get_document_details()
    {
        $document = Document::factory()->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->getJson("/api/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'type',
                    'description',
                    'file_name',
                    'file_size',
                    'mime_type',
                    'tags',
                    'organization_id',
                    'uploaded_by',
                    'created_at',
                    'updated_at',
                    'download_url',
                    'formatted_file_size',
                    'organization',
                    'uploaded_by_user'
                ]
            ]);
    }

    /** @test */
    public function it_can_update_document_details()
    {
        $document = Document::factory()->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->putJson("/api/documents/{$document->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'tags' => ['updated', 'document']
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated Description',
                    'tags' => ['updated', 'document']
                ]
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ]);
    }

    /** @test */
    public function it_can_delete_a_document()
    {
        $document = Document::factory()->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Document deleted successfully'
            ]);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id
        ]);

        Storage::disk('documents')->assertMissing($document->file_path);
    }

    /** @test */
    public function it_can_download_a_document()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        $document = Document::factory()->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id,
            'file_path' => $file->hashName(),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType()
        ]);

        Storage::disk('documents')->put($file->hashName(), $file->getContent());

        $response = $this->getJson("/api/documents/{$document->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', $file->getMimeType())
            ->assertHeader('Content-Disposition', 'attachment; filename="' . $file->getClientOriginalName() . '"');
    }

    /** @test */
    public function it_can_get_document_statistics()
    {
        Document::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'uploaded_by' => $this->user->id,
            'file_size' => 1000
        ]);

        $response = $this->getJson('/api/documents/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_documents',
                    'total_size',
                    'formatted_total_size',
                    'documents_by_type',
                    'documents_by_organization'
                ]
            ])
            ->assertJson([
                'data' => [
                    'total_documents' => 3,
                    'total_size' => 3000
                ]
            ]);
    }

    /** @test */
    public function it_validates_file_size_on_upload()
    {
        $file = UploadedFile::fake()->create('document.pdf', 11000); // 11MB

        $response = $this->postJson('/api/documents', [
            'title' => 'Test Document',
            'type' => 'pdf',
            'file' => $file,
            'organization_id' => $this->organization->id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_type_on_upload()
    {
        $file = UploadedFile::fake()->create('document.exe', 1000);

        $response = $this->postJson('/api/documents', [
            'title' => 'Test Document',
            'type' => 'pdf',
            'file' => $file,
            'organization_id' => $this->organization->id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_required_fields_on_upload()
    {
        $response = $this->postJson('/api/documents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type', 'file', 'organization_id']);
    }

    /** @test */
    public function it_validates_organization_exists()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/documents', [
            'title' => 'Test Document',
            'type' => 'pdf',
            'file' => $file,
            'organization_id' => 999999
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id']);
    }
} 