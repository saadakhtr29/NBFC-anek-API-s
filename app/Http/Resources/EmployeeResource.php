<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Employee",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="organization_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer", nullable=true),
 *     @OA\Property(property="employee_id", type="string"),
 *     @OA\Property(property="first_name", type="string"),
 *     @OA\Property(property="last_name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="city", type="string", nullable=true),
 *     @OA\Property(property="state", type="string", nullable=true),
 *     @OA\Property(property="country", type="string", nullable=true),
 *     @OA\Property(property="postal_code", type="string", nullable=true),
 *     @OA\Property(property="date_of_birth", type="string", format="date", nullable=true),
 *     @OA\Property(property="date_of_joining", type="string", format="date"),
 *     @OA\Property(property="designation", type="string"),
 *     @OA\Property(property="department", type="string"),
 *     @OA\Property(property="salary", type="number", format="float"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "on_leave", "terminated"}),
 *     @OA\Property(property="employment_type", type="string", enum={"full_time", "part_time", "contract", "intern"}),
 *     @OA\Property(property="bank_name", type="string", nullable=true),
 *     @OA\Property(property="bank_account_number", type="string", nullable=true),
 *     @OA\Property(property="bank_ifsc_code", type="string", nullable=true),
 *     @OA\Property(property="emergency_contact_name", type="string", nullable=true),
 *     @OA\Property(property="emergency_contact_phone", type="string", nullable=true),
 *     @OA\Property(property="emergency_contact_relationship", type="string", nullable=true),
 *     @OA\Property(property="documents", type="array", @OA\Items(type="string"), nullable=true),
 *     @OA\Property(property="remarks", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="organization",
 *         ref="#/components/schemas/OrganizationResource"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/UserResource"
 *     ),
 *     @OA\Property(
 *         property="loans",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/LoanResource")
 *     )
 * )
 */
class EmployeeResource extends JsonResource
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
            'user_id' => $this->user_id,
            'employee_id' => $this->employee_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'date_of_birth' => $this->date_of_birth,
            'date_of_joining' => $this->date_of_joining,
            'designation' => $this->designation,
            'department' => $this->department,
            'salary' => $this->salary,
            'status' => $this->status,
            'employment_type' => $this->employment_type,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc_code' => $this->bank_ifsc_code,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'documents' => $this->documents,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'user' => new UserResource($this->whenLoaded('user')),
            'loans' => LoanResource::collection($this->whenLoaded('loans')),
            'full_name' => $this->full_name,
            'active_loans' => LoanResource::collection($this->whenLoaded('loans', function () {
                return $this->active_loans;
            })),
            'total_loan_amount' => $this->whenLoaded('loans', function () {
                return $this->total_loan_amount;
            }),
            'total_repaid_amount' => $this->whenLoaded('repayments', function () {
                return $this->total_repaid_amount;
            }),
            'remaining_loan_amount' => $this->whenLoaded(['loans', 'repayments'], function () {
                return $this->remaining_loan_amount;
            })
        ];
    }
} 