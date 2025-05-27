<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="LoanDocumentResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="loan_id", type="integer"),
 *     @OA\Property(property="document_type", type="string"),
 *     @OA\Property(property="file_name", type="string"),
 *     @OA\Property(property="file_size", type="integer"),
 *     @OA\Property(property="mime_type", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "verified", "rejected"}),
 *     @OA\Property(property="verified_at", type="string", format="date-time"),
 *     @OA\Property(property="verification_notes", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="loan",
 *         ref="#/components/schemas/LoanResource"
 *     ),
 *     @OA\Property(
 *         property="verified_by",
 *         ref="#/components/schemas/UserResource"
 *     )
 * )
 */
class LoanDocumentResource extends JsonResource
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
            'loan_id' => $this->loan_id,
            'document_type' => $this->document_type,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'description' => $this->description,
            'status' => $this->status,
            'verified_at' => $this->verified_at,
            'verification_notes' => $this->verification_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'loan' => new LoanResource($this->whenLoaded('loan')),
            'verified_by' => new UserResource($this->whenLoaded('verifiedBy')),
        ];
    }
}
