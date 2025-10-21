<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseCollection;
use App\Http\Resources\CourseResource;
use App\Http\Responses\ApiResponse;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['creator:id,name']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $courses = $query->latest()
                        ->paginate($request->get('per_page', 15));

        $courseCollection = new CourseCollection($courses);

        return ApiResponse::success(
            $courseCollection->toArray($request),
            'Courses retrieved successfully'
        );
    }

    /**
     * Store a newly created course.
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        try {
            $course = Course::create($validated);
            $course->load(['creator:id,name']);

            return ApiResponse::success([
                'course' => new CourseResource($course)
            ], 'Course created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create course: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified course.
     */
    public function show(int $id): JsonResponse
    {
        $course = Course::with(['creator:id,name'])->findOrFail($id);

        return ApiResponse::success([
            'course' => new CourseResource($course)
        ], 'Course retrieved successfully');
    }

    /**
     * Update the specified course.
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $validated = $request->validated();

        try {
            $course->update($validated);
            $course->load(['creator:id,name']);

            return ApiResponse::success([
                'course' => new CourseResource($course)
            ], 'Course updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update course: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified course.
     */
    public function destroy(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        try {
            $course->delete();

            return ApiResponse::success([], 'Course deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete course: ' . $e->getMessage(), 500);
        }
    }
}
