<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Document",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="file_name", type="string"),
 *     @OA\Property(property="file_size", type="integer"),
 *     @OA\Property(property="mime_type", type="string"),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="organization_id", type="integer"),
 *     @OA\Property(property="uploaded_by", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="organization",
 *         ref="#/components/schemas/OrganizationResource"
 *     ),
 *     @OA\Property(
 *         property="uploadedBy",
 *         ref="#/components/schemas/UserResource"
 *     )
 * )
 */
class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'tags' => $this->tags,
            'organization_id' => $this->organization_id,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'uploadedBy' => new UserResource($this->whenLoaded('uploadedBy')),
            'download_url' => route('documents.download', $this->id),
            'file_size_formatted' => $this->formatFileSize($this->file_size)
        ];
    }

    /**
     * Format file size in human-readable format
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 