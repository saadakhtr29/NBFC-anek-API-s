<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationSettingResource;
use App\Models\OrganizationSetting;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Organization Settings",
 *     description="API Endpoints for managing organization settings"
 * )
 */
class OrganizationSettingController extends Controller
{
    /**
     * Display a listing of the organization settings.
     *
     * @OA\Get(
     *     path="/api/organization-settings",
     *     tags={"Organization Settings"},
     *     summary="Get all organization settings",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="key",
     *         in="query",
     *         description="Filter by setting key",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of organization settings",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/OrganizationSettingResource"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = OrganizationSetting::query()
            ->with(['organization', 'updatedBy']);

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('key')) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        $settings = $query->paginate(10);

        return OrganizationSettingResource::collection($settings);
    }

    /**
     * Store a newly created organization setting.
     *
     * @OA\Post(
     *     path="/api/organization-settings",
     *     tags={"Organization Settings"},
     *     summary="Create a new organization setting",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"organization_id", "key", "value"},
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="key", type="string"),
     *             @OA\Property(property="value", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_public", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Organization setting created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OrganizationSettingResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'key' => 'required|string|max:255',
            'value' => 'required',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate setting
        $exists = OrganizationSetting::where('organization_id', $request->organization_id)
            ->where('key', $request->key)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A setting with this key already exists for this organization'
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = auth()->id();

        $setting = DB::transaction(function () use ($data) {
            $setting = OrganizationSetting::create($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => OrganizationSetting::class,
                'model_id' => $setting->id,
                'description' => 'Created organization setting',
                'old_values' => null,
                'new_values' => $setting->toArray(),
            ]);

            return $setting;
        });

        return new OrganizationSettingResource($setting);
    }

    /**
     * Display the specified organization setting.
     *
     * @OA\Get(
     *     path="/api/organization-settings/{setting}",
     *     tags={"Organization Settings"},
     *     summary="Get organization setting details",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization setting details",
     *         @OA\JsonContent(ref="#/components/schemas/OrganizationSettingResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization setting not found"
     *     )
     * )
     */
    public function show(OrganizationSetting $setting)
    {
        $setting->load(['organization', 'updatedBy']);
        return new OrganizationSettingResource($setting);
    }

    /**
     * Update the specified organization setting.
     *
     * @OA\Put(
     *     path="/api/organization-settings/{setting}",
     *     tags={"Organization Settings"},
     *     summary="Update organization setting details",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_public", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization setting updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OrganizationSettingResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization setting not found"
     *     )
     * )
     */
    public function update(Request $request, OrganizationSetting $setting)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $setting->toArray();
        $data = $validator->validated();
        $data['updated_by'] = auth()->id();

        $setting = DB::transaction(function () use ($setting, $data, $oldValues) {
            $setting->update($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => OrganizationSetting::class,
                'model_id' => $setting->id,
                'description' => 'Updated organization setting',
                'old_values' => $oldValues,
                'new_values' => $setting->toArray(),
            ]);

            return $setting;
        });

        return new OrganizationSettingResource($setting);
    }

    /**
     * Remove the specified organization setting.
     *
     * @OA\Delete(
     *     path="/api/organization-settings/{setting}",
     *     tags={"Organization Settings"},
     *     summary="Delete an organization setting",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization setting deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization setting not found"
     *     )
     * )
     */
    public function destroy(OrganizationSetting $setting)
    {
        DB::transaction(function () use ($setting) {
            $oldValues = $setting->toArray();
            $setting->delete();

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => OrganizationSetting::class,
                'model_id' => $setting->id,
                'description' => 'Deleted organization setting',
                'old_values' => $oldValues,
                'new_values' => null,
            ]);
        });

        return response()->noContent(204);
    }

    /**
     * Get organization setting by key.
     *
     * @OA\Get(
     *     path="/api/organization-settings/key/{key}",
     *     tags={"Organization Settings"},
     *     summary="Get organization setting by key",
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization setting details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="key", type="string"),
     *                 @OA\Property(property="value", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Organization setting not found"
     *     )
     * )
     */
    public function getByKey($key, Request $request)
    {
        $query = OrganizationSetting::where('key', $key);

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $setting = $query->firstOrFail();

        return response()->json([
            'data' => [
                'key' => $setting->key,
                'value' => $setting->value
            ]
        ]);
    }
} 