<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseCollection;
use App\Http\Resources\CourseResource;
use App\Http\Responses\ApiResponse;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
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
     * Display the specified course.
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        $course->load(['creator:id,name']);

        return ApiResponse::success(
            ['course' => new CourseResource($course)],
            'Course retrieved successfully'
        );
    }
}
