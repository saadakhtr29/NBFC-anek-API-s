<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="System Settings",
 *     description="API Endpoints for managing global system settings"
 * )
 */
class SystemSettingController extends Controller
{
    /**
     * List all system settings.
     *
     * @OA\Get(
     *     path="/api/system-settings",
     *     tags={"System Settings"},
     *     summary="List all system settings",
     *     @OA\Response(
     *         response=200,
     *         description="List of system settings",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="key", type="string"),
     *                 @OA\Property(property="value", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="is_public", type="boolean"),
     *                 @OA\Property(property="updated_by", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $settings = SystemSetting::all();
        return response()->json(['data' => $settings]);
    }

    /**
     * Create a new system setting.
     *
     * @OA\Post(
     *     path="/api/system-settings",
     *     tags={"System Settings"},
     *     summary="Create a new system setting",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "value"},
     *             @OA\Property(property="key", type="string"),
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_public", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="System setting created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:system_settings,key',
            'value' => 'required',
            'description' => 'nullable|string',
            'type' => 'required|in:string,integer,float,boolean,array,object',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['updated_by'] = auth()->id();

        $setting = SystemSetting::create($data);

        TransactionLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'model_type' => SystemSetting::class,
            'model_id' => $setting->id,
            'description' => 'Created system setting',
            'old_values' => null,
            'new_values' => $setting->toArray(),
        ]);

        return response()->json(['data' => $setting], 201);
    }

    /**
     * Get a specific system setting.
     *
     * @OA\Get(
     *     path="/api/system-settings/{setting}",
     *     tags={"System Settings"},
     *     summary="Get a specific system setting",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="System setting details"
     *     )
     * )
     */
    public function show(SystemSetting $setting)
    {
        return response()->json(['data' => $setting]);
    }

    /**
     * Update a system setting.
     *
     * @OA\Put(
     *     path="/api/system-settings/{setting}",
     *     tags={"System Settings"},
     *     summary="Update a system setting",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_public", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="System setting updated successfully"
     *     )
     * )
     */
    public function update(Request $request, SystemSetting $setting)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'description' => 'nullable|string',
            'type' => 'in:string,integer,float,boolean,array,object',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $setting->toArray();
        $setting->update($request->all() + ['updated_by' => auth()->id()]);

        TransactionLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'model_type' => SystemSetting::class,
            'model_id' => $setting->id,
            'description' => 'Updated system setting',
            'old_values' => $oldValues,
            'new_values' => $setting->toArray(),
        ]);

        return response()->json(['data' => $setting]);
    }

    /**
     * Delete a system setting.
     *
     * @OA\Delete(
     *     path="/api/system-settings/{setting}",
     *     tags={"System Settings"},
     *     summary="Delete a system setting",
     *     @OA\Parameter(
     *         name="setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="System setting deleted successfully"
     *     )
     * )
     */
    public function destroy(SystemSetting $setting)
    {
        $oldValues = $setting->toArray();
        $setting->delete();

        TransactionLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'model_type' => SystemSetting::class,
            'model_id' => $setting->id,
            'description' => 'Deleted system setting',
            'old_values' => $oldValues,
            'new_values' => null,
        ]);

        return response()->json(null, 204);
    }

    /**
     * Get a system setting by key.
     *
     * @OA\Get(
     *     path="/api/system-settings/key/{key}",
     *     tags={"System Settings"},
     *     summary="Get a system setting by key",
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="System setting details"
     *     )
     * )
     */
    public function getByKey($key)
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();
        return response()->json(['data' => $setting]);
    }
} 