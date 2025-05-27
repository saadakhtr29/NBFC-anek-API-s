<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Organization",
 *     title="Organization",
 *     description="Organization model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Acme Corporation"),
 *     @OA\Property(property="code", type="string", example="ACME001"),
 *     @OA\Property(property="type", type="string", example="Private Limited"),
 *     @OA\Property(property="registration_number", type="string", example="REG123456"),
 *     @OA\Property(property="tax_number", type="string", example="TAX789012"),
 *     @OA\Property(property="address", type="string", example="123 Business Street"),
 *     @OA\Property(property="city", type="string", example="New York"),
 *     @OA\Property(property="state", type="string", example="NY"),
 *     @OA\Property(property="country", type="string", example="USA"),
 *     @OA\Property(property="postal_code", type="string", example="10001"),
 *     @OA\Property(property="phone", type="string", example="+1-555-0123"),
 *     @OA\Property(property="email", type="string", example="contact@acme.com"),
 *     @OA\Property(property="website", type="string", example="https://acme.com"),
 *     @OA\Property(property="logo", type="string", example="https://acme.com/logo.png"),
 *     @OA\Property(property="description", type="string", example="Leading provider of innovative solutions"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
 *     @OA\Property(property="founding_date", type="string", format="date", example="2020-01-01"),
 *     @OA\Property(property="industry", type="string", example="Technology"),
 *     @OA\Property(property="size", type="string", enum={"small", "medium", "large", "enterprise"}, example="medium"),
 *     @OA\Property(property="annual_revenue", type="number", format="float", example=1000000.00),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="timezone", type="string", example="America/New_York"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="remarks", type="string", example="Additional notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="statistics",
 *         type="object",
 *         @OA\Property(property="active_employees", type="integer", example=50),
 *         @OA\Property(property="active_loans", type="integer", example=10),
 *         @OA\Property(property="total_loan_amount", type="number", format="float", example=500000.00),
 *         @OA\Property(property="remaining_loan_amount", type="number", format="float", example=250000.00),
 *         @OA\Property(
 *             property="documents",
 *             type="object",
 *             @OA\Property(property="total", type="integer", example=100),
 *             @OA\Property(property="total_size", type="integer", example=10485760)
 *         )
 *     )
 * )
 */
class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'registration_number' => $this->registration_number,
            'tax_number' => $this->tax_number,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo' => $this->logo,
            'description' => $this->description,
            'status' => $this->status,
            'founding_date' => $this->founding_date,
            'industry' => $this->industry,
            'size' => $this->size,
            'annual_revenue' => $this->annual_revenue,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'settings' => $this->settings,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'statistics' => [
                'active_employees' => $this->active_employees_count,
                'active_loans' => $this->active_loans_count,
                'total_loan_amount' => $this->total_loan_amount,
                'remaining_loan_amount' => $this->remaining_loan_amount,
                'documents' => $this->document_statistics
            ]
        ];
    }
} 