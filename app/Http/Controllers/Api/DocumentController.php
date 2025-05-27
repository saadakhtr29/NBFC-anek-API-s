<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Organization;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\DocumentRequest;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Documents",
 *     description="API Endpoints for document management"
 * )
 */
class DocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/documents",
     *     summary="Get all documents",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by document type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of documents",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Document")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->with(['organization', 'uploadedBy'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return DocumentResource::collection($documents);
    }

    /**
     * @OA\Post(
     *     path="/api/documents",
     *     summary="Upload a new document",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "title", "type", "organization_id"},
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="organization_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     )
     * )
     */
    public function store(DocumentRequest $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $path = $file->store('documents/' . $request->organization_id);

            $data = $request->validated();
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();
            $data['uploaded_by'] = auth()->id();

            $document = Document::create($data);

            // Log the document upload
            TransactionLog::create([
                'employee_id' => auth()->id(),
                'transaction_type' => 'document_uploaded',
                'status' => 'completed',
                'remarks' => "Document {$document->title} uploaded"
            ]);

            DB::commit();

            return new DocumentResource($document->load(['organization', 'uploadedBy']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error uploading document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/documents/{id}",
     *     summary="Get document details",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document details",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     )
     * )
     */
    public function show(Document $document)
    {
        return new DocumentResource($document->load(['organization', 'uploadedBy']));
    }

    /**
     * @OA\Put(
     *     path="/api/documents/{id}",
     *     summary="Update document details",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     )
     * )
     */
    public function update(DocumentRequest $request, Document $document)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $document->update($data);

            // Log the update
            TransactionLog::create([
                'employee_id' => auth()->id(),
                'transaction_type' => 'document_updated',
                'status' => 'completed',
                'remarks' => "Document {$document->title} updated"
            ]);

            DB::commit();

            return new DocumentResource($document->load(['organization', 'uploadedBy']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/documents/{id}",
     *     summary="Delete a document",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document deleted successfully"
     *     )
     * )
     */
    public function destroy(Document $document)
    {
        try {
            DB::beginTransaction();

            // Delete the file from storage
            Storage::delete($document->file_path);

            // Log the deletion
            TransactionLog::create([
                'employee_id' => auth()->id(),
                'transaction_type' => 'document_deleted',
                'status' => 'completed',
                'remarks' => "Document {$document->title} deleted"
            ]);

            $document->delete();

            DB::commit();

            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/documents/{id}/download",
     *     summary="Download a document",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document file download"
     *     )
     * )
     */
    public function download(Document $document)
    {
        if (!Storage::exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Log the download
        TransactionLog::create([
            'employee_id' => auth()->id(),
            'transaction_type' => 'document_downloaded',
            'status' => 'completed',
            'remarks' => "Document {$document->title} downloaded"
        ]);

        return Storage::download(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * @OA\Get(
     *     path="/api/documents/statistics",
     *     summary="Get document statistics",
     *     tags={"Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Document statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_documents", type="integer"),
     *             @OA\Property(property="total_size", type="number"),
     *             @OA\Property(property="type_distribution", type="object"),
     *             @OA\Property(property="monthly_trend", type="array")
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $query = Document::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $stats = [
            'total_documents' => $query->count(),
            'total_size' => $query->sum('file_size'),
            'type_distribution' => $query->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'monthly_trend' => $query->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as count'),
                DB::raw('sum(file_size) as total_size')
            )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
        ];

        return response()->json($stats);
    }
} 