<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="OrganizationSettingResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="organization_id", type="integer"),
 *     @OA\Property(property="key", type="string"),
 *     @OA\Property(property="value", type="object"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="is_public", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="organization",
 *         ref="#/components/schemas/OrganizationResource"
 *     ),
 *     @OA\Property(
 *         property="updated_by",
 *         ref="#/components/schemas/UserResource"
 *     )
 * )
 */
class OrganizationSettingResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'key' => $this->key,
            'value' => $this->value,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'updated_by' => new UserResource($this->whenLoaded('updatedBy')),
        ];
    }
} 