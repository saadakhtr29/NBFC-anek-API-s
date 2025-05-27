<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanDocumentResource;
use App\Models\Loan;
use App\Models\LoanDocument;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Loan Documents",
 *     description="API Endpoints for loan document management"
 * )
 */
class LoanDocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/loan-documents",
     *     summary="Get all loan documents with optional filters",
     *     tags={"Loan Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="query",
     *         description="Filter by loan ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="document_type",
     *         in="query",
     *         description="Filter by document type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "verified", "rejected"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan documents",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LoanDocumentResource"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = LoanDocument::with(['loan', 'verifiedBy']);

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->paginate(10);
        return LoanDocumentResource::collection($documents);
    }

    /**
     * @OA\Post(
     *     path="/api/loan-documents",
     *     summary="Upload a new loan document",
     *     tags={"Loan Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"loan_id", "document_type", "file"},
     *                 @OA\Property(property="loan_id", type="integer"),
     *                 @OA\Property(property="document_type", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDocumentResource")
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
            'loan_id' => 'required|exists:loans,id',
            'document_type' => 'required|string|max:50',
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($request->loan_id);
            $file = $request->file('file');
            $path = $file->store('loan-documents/' . $loan->id);

            $document = LoanDocument::create([
                'loan_id' => $request->loan_id,
                'document_type' => $request->document_type,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
                'status' => 'pending'
            ]);

            TransactionLog::create([
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'document_uploaded',
                'description' => 'New document uploaded: ' . $request->document_type
            ]);

            DB::commit();
            return new LoanDocumentResource($document);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error uploading document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loan-documents/{id}",
     *     summary="Get document details",
     *     tags={"Loan Documents"},
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
     *         @OA\JsonContent(ref="#/components/schemas/LoanDocumentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found"
     *     )
     * )
     */
    public function show($id)
    {
        $document = LoanDocument::with(['loan', 'verifiedBy'])->findOrFail($id);
        return new LoanDocumentResource($document);
    }

    /**
     * @OA\Put(
     *     path="/api/loan-documents/{id}",
     *     summary="Update document details",
     *     tags={"Loan Documents"},
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
     *             @OA\Property(property="document_type", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "verified", "rejected"}),
     *             @OA\Property(property="verification_notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDocumentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $document = LoanDocument::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'document_type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,verified,rejected',
            'verification_notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $document->status;
            $document->update($request->all());

            if ($request->has('status') && $oldStatus !== $request->status) {
                $document->verified_by = auth()->id();
                $document->verified_at = now();
                $document->save();

                TransactionLog::create([
                    'employee_id' => $document->loan->employee_id,
                    'transaction_type' => 'document_status_changed',
                    'description' => "Document status changed from {$oldStatus} to {$request->status}"
                ]);
            }

            DB::commit();
            return new LoanDocumentResource($document);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/loan-documents/{id}",
     *     summary="Delete a document",
     *     tags={"Loan Documents"},
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $document = LoanDocument::findOrFail($id);

        DB::beginTransaction();
        try {
            Storage::delete($document->file_path);

            TransactionLog::create([
                'employee_id' => $document->loan->employee_id,
                'transaction_type' => 'document_deleted',
                'description' => 'Document deleted: ' . $document->document_type
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
     *     path="/api/loans/{loan}/documents",
     *     summary="Get all documents for a loan",
     *     tags={"Loan Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan documents",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LoanDocumentResource"))
     *         )
     *     )
     * )
     */
    public function loanDocuments($loanId)
    {
        $documents = LoanDocument::with(['loan', 'verifiedBy'])
            ->where('loan_id', $loanId)
            ->paginate(10);

        return LoanDocumentResource::collection($documents);
    }

    /**
     * @OA\Post(
     *     path="/api/loan-documents/verify/{document}",
     *     summary="Verify a document",
     *     tags={"Loan Documents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"verified", "rejected"}),
     *             @OA\Property(property="verification_notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document verified successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDocumentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function verify(Request $request, $id)
    {
        $document = LoanDocument::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'verification_notes' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $document->status;
            $document->update([
                'status' => $request->status,
                'verification_notes' => $request->verification_notes,
                'verified_by' => auth()->id(),
                'verified_at' => now()
            ]);

            TransactionLog::create([
                'employee_id' => $document->loan->employee_id,
                'transaction_type' => 'document_verified',
                'description' => "Document {$request->status}: " . $request->verification_notes
            ]);

            DB::commit();
            return new LoanDocumentResource($document);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error verifying document: ' . $e->getMessage()], 500);
        }
    }
}
