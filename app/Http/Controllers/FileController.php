<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Http\Resources\FileCollection;
use App\Http\Resources\FileResource;
use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\FileUploadService;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class FileController extends Controller
{
    use HandlesFileUploads;

    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of files.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = File::with('uploadedBy');

            // Filter by category
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            // Filter by type (image or document)
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Filter by parent model
            if ($request->has('fileable_type') && $request->has('fileable_id')) {
                $query->where('fileable_type', $request->fileable_type)
                      ->where('fileable_id', $request->fileable_id);
            }

            // Search by filename or original name
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('original_name', 'like', '%' . $request->search . '%')
                      ->orWhere('filename', 'like', '%' . $request->search . '%');
                });
            }

            // Order by creation date (newest first)
            $query->orderBy('created_at', 'desc');

            $perPage = min($request->get('per_page', 15), 100);
            $files = $query->paginate($perPage);

            $fileCollection = new FileCollection($files);
            
            return ApiResponse::success(
                $fileCollection->toArray($request),
                'Files retrieved successfully'
            );

        } catch (Exception $e) {
            return ApiResponse::error('Failed to retrieve files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(FileUploadRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedDataWithDefaults();
            $uploadedFiles = [];
            $errors = [];

            if ($request->isSingleFileUpload()) {
                $file = $this->fileUploadService->uploadFile(
                    $request->file('file'),
                    $validatedData['category'],
                    $validatedData['disk'],
                    [],
                    $request->user()->id
                );
                $uploadedFiles[] = $file;
            } else {
                $result = $this->fileUploadService->uploadFiles(
                    $request->file('files'),
                    $validatedData['category'],
                    $validatedData['disk'],
                    [],
                    $request->user()->id
                );
                $uploadedFiles = $result['uploaded'];
                $errors = $result['errors'];
            }

            $response = [
                'files' => FileResource::collection($uploadedFiles),
                'uploaded_count' => count($uploadedFiles),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                $response['error_count'] = count($errors);
            }

            return ApiResponse::success($response, 'Files uploaded successfully');

        } catch (Exception $e) {
            return ApiResponse::error('File upload failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Display the specified file.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $file = File::with('uploadedBy')->findOrFail($id);
            return ApiResponse::success([
                'file' => new FileResource($file)
            ], 'File retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error('File not found', 404);
        }
    }

    /**
     * Download the specified file.
     */
    public function download(int $id): JsonResponse
    {
        return $this->downloadFile($id);
    }

    /**
     * Remove the specified file from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $file = File::findOrFail($id);
            
            // Check if user has permission to delete this file
            if (!auth()->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to delete this file', 403);
            }

            $deleted = $this->fileUploadService->deleteFile($file);

            if ($deleted) {
                return ApiResponse::success(null, 'File deleted successfully');
            } else {
                return ApiResponse::error('Failed to delete file', 500);
            }

        } catch (Exception $e) {
            return ApiResponse::error('File not found', 404);
        }
    }

    /**
     * Delete multiple files.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'integer|exists:files,id'
        ]);

        try {
            // Check if user has permission to delete files
            if (!auth()->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to delete files', 403);
            }

            $result = $this->fileUploadService->deleteFiles($request->file_ids);

            return ApiResponse::success([
                'deleted' => $result['deleted'],
                'errors' => $result['errors'],
                'deleted_count' => count($result['deleted']),
                'error_count' => count($result['errors'])
            ], 'Files deletion completed');

        } catch (Exception $e) {
            return ApiResponse::error('Failed to delete files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_files' => File::count(),
                'total_size' => File::sum('size'),
                'by_category' => File::selectRaw('category, COUNT(*) as count, SUM(size) as total_size')
                    ->groupBy('category')
                    ->get()
                    ->keyBy('category'),
                'by_type' => [
                    'images' => File::where('mime_type', 'like', 'image/%')->count(),
                    'documents' => File::whereIn('mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain',
                        'application/rtf',
                        'text/rtf'
                    ])->count(),
                ],
                'by_disk' => File::selectRaw('disk, COUNT(*) as count, SUM(size) as total_size')
                    ->groupBy('disk')
                    ->get()
                    ->keyBy('disk'),
            ];

            return ApiResponse::success($stats);

        } catch (Exception $e) {
            return ApiResponse::error('Failed to retrieve file statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cleanup orphaned files (files not attached to any model).
     */
    public function cleanup(): JsonResponse
    {
        try {
            // Check if user has admin access
            if (!auth()->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to perform cleanup', 403);
            }

            $orphanedFiles = File::whereNull('fileable_id')->orWhereNull('fileable_type')->get();
            $deletedCount = 0;
            $errors = [];

            foreach ($orphanedFiles as $file) {
                if ($this->fileUploadService->deleteFile($file)) {
                    $deletedCount++;
                } else {
                    $errors[] = $file->id;
                }
            }

            return ApiResponse::success([
                'deleted_count' => $deletedCount,
                'error_count' => count($errors),
                'errors' => $errors
            ], 'File cleanup completed');

        } catch (Exception $e) {
            return ApiResponse::error('Cleanup failed: ' . $e->getMessage(), 500);
        }
    }
}