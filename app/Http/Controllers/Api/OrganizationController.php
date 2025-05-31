<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Organizations",
 *     description="API Endpoints for organization management"
 * )
 */
class OrganizationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/organizations",
     *     summary="Get all organizations",
     *     tags={"Organizations"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="industry",
     *         in="query",
     *         description="Filter by industry",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, code, or registration number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of organizations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Organization"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('industry')) {
            $query->where('industry', $request->industry);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        $organizations = $query->latest()->paginate();

        return OrganizationResource::collection($organizations);
    }

    /**
     * @OA\Post(
     *     path="/api/organizations",
     *     summary="Create a new organization",
     *     tags={"Organizations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Organization")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Organization created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Organization")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(OrganizationRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $path = $logo->store('organizations/logos', 'public');
                $data['logo'] = $path;
            }

            $organization = Organization::create($data);

            // Log the transaction
            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => Organization::class,
                'model_id' => $organization->id,
                'details' => 'Organization created',
                'ip_address' => request()->ip()
            ]);

            DB::commit();

            return new OrganizationResource($organization);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/organizations/{id}",
     *     summary="Get organization details",
     *     tags={"Organizations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization details",
     *         @OA\JsonContent(ref="#/components/schemas/Organization")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization not found"
     *     )
     * )
     */
    public function show(Organization $organization)
    {
        return new OrganizationResource($organization);
    }

    /**
     * @OA\Put(
     *     path="/api/organizations/{id}",
     *     summary="Update organization details",
     *     tags={"Organizations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Organization")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Organization")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(OrganizationRequest $request, Organization $organization)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($organization->logo) {
                    Storage::disk('public')->delete($organization->logo);
                }

                $logo = $request->file('logo');
                $path = $logo->store('organizations/logos', 'public');
                $data['logo'] = $path;
            }

            $organization->update($data);

            // Log the transaction
            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => Organization::class,
                'model_id' => $organization->id,
                'details' => 'Organization updated',
                'ip_address' => request()->ip()
            ]);

            DB::commit();

            return new OrganizationResource($organization);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/organizations/{id}",
     *     summary="Delete an organization",
     *     tags={"Organizations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Organization deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete organization with active employees or loans"
     *     )
     * )
     */
    public function destroy(Organization $organization)
    {
        try {
            DB::beginTransaction();

            // Check if organization has active employees
            if ($organization->employees()->where('status', 'active')->exists()) {
                return response()->json([
                    'message' => 'Cannot delete organization with active employees'
                ], 422);
            }

            // Check if organization has active loans
            if ($organization->loans()->where('status', 'active')->exists()) {
                return response()->json([
                    'message' => 'Cannot delete organization with active loans'
                ], 422);
            }

            // Delete logo if exists
            if ($organization->logo) {
                Storage::disk('public')->delete($organization->logo);
            }

            $organization->delete();

            // Log the transaction
            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => Organization::class,
                'model_id' => $organization->id,
                'details' => 'Organization deleted',
                'ip_address' => request()->ip()
            ]);

            DB::commit();

            return response()->noContent(204);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/organizations/statistics",
     *     summary="Get organization statistics",
     *     tags={"Organizations"},
     *     @OA\Response(
     *         response=200,
     *         description="Organization statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_organizations", type="integer"),
     *             @OA\Property(property="active_organizations", type="integer"),
     *             @OA\Property(property="total_employees", type="integer"),
     *             @OA\Property(property="active_employees", type="integer"),
     *             @OA\Property(property="total_loans", type="integer"),
     *             @OA\Property(property="active_loans", type="integer"),
     *             @OA\Property(property="total_loan_amount", type="number", format="float"),
     *             @OA\Property(property="remaining_loan_amount", type="number", format="float"),
     *             @OA\Property(
     *                 property="organizations_by_status",
     *                 type="object",
     *                 @OA\Property(property="active", type="integer"),
     *                 @OA\Property(property="inactive", type="integer"),
     *                 @OA\Property(property="suspended", type="integer")
     *             ),
     *             @OA\Property(
     *                 property="organizations_by_size",
     *                 type="object",
     *                 @OA\Property(property="small", type="integer"),
     *                 @OA\Property(property="medium", type="integer"),
     *                 @OA\Property(property="large", type="integer"),
     *                 @OA\Property(property="enterprise", type="integer")
     *             ),
     *             @OA\Property(
     *                 property="organizations_by_industry",
     *                 type="object",
     *                 additionalProperties=@OA\Schema(type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        $statistics = [
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('status', 'active')->count(),
            'total_employees' => DB::table('employees')->count(),
            'active_employees' => DB::table('employees')->where('status', 'active')->count(),
            'total_loans' => DB::table('loans')->count(),
            'active_loans' => DB::table('loans')->where('status', 'active')->count(),
            'total_loan_amount' => DB::table('loans')->sum('amount'),
            'remaining_loan_amount' => DB::table('loans')->where('status', 'active')->sum('remaining_amount'),
            'organizations_by_status' => [
                'active' => Organization::where('status', 'active')->count(),
                'inactive' => Organization::where('status', 'inactive')->count(),
                'suspended' => Organization::where('status', 'suspended')->count()
            ],
            'organizations_by_size' => [
                'small' => Organization::where('size', 'small')->count(),
                'medium' => Organization::where('size', 'medium')->count(),
                'large' => Organization::where('size', 'large')->count(),
                'enterprise' => Organization::where('size', 'enterprise')->count()
            ],
            'organizations_by_industry' => Organization::select('industry', DB::raw('count(*) as count'))
                ->groupBy('industry')
                ->pluck('count', 'industry')
                ->toArray()
        ];

        return response()->json($statistics);
    }
} 