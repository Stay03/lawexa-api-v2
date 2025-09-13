<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profession' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'area_of_expertise' => 'required|array|min:1|max:5',
            'area_of_expertise.*' => 'required|string|max:150',
            'university' => 'nullable|required_if:profession,student|string|max:200',
            'level' => 'nullable|required_if:profession,student|string|max:50',
            'work_experience' => 'nullable|integer|min:0|max:50',
        ], [
            'area_of_expertise.required' => 'At least one area of expertise is required',
            'area_of_expertise.array' => 'Area of expertise must be an array',
            'area_of_expertise.min' => 'At least one area of expertise is required',
            'area_of_expertise.max' => 'You can select up to 5 areas of expertise',
            'area_of_expertise.*.required' => 'Each area of expertise is required',
            'area_of_expertise.*.string' => 'Each area of expertise must be a valid text',
            'area_of_expertise.*.max' => 'Each area of expertise cannot exceed 150 characters',
            'university.required_if' => 'University is required for students',
            'level.required_if' => 'Academic level is required for students',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $user = $request->user();
        
        // Update profile fields
        $user->update([
            'profession' => $request->profession,
            'country' => $request->country,
            'area_of_expertise' => $request->area_of_expertise,
            'university' => $request->university,
            'level' => $request->level,
            'work_experience' => $request->work_experience,
        ]);

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()->load(['activeSubscription', 'subscriptions']))
        ], 'Profile updated successfully');
    }

    public function getProfile(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => new UserResource($request->user()->load(['activeSubscription', 'subscriptions']))
        ], 'Profile retrieved successfully');
    }
}
