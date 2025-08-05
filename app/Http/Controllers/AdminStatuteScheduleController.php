<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteSchedule;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteScheduleController extends Controller
{
    public function index(Request $request, $statuteId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        
        $query = $statute->schedules();
        
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('schedule_type')) {
            $query->byType($request->schedule_type);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $schedules = $query->orderBy('sort_order')
                          ->paginate($request->get('per_page', 20));
        
        return ApiResponse::success(
            $schedules,
            'Statute schedules retrieved successfully'
        );
    }
    
    public function store(Request $request, $statuteId): JsonResponse
    {
        $validated = $request->validate([
            'schedule_number' => 'required|string|max:255',
            'schedule_title' => 'required|string|max:255',
            'content' => 'required|string',
            'schedule_type' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        
        try {
            $schedule = $statute->schedules()->create($validated);
            
            return ApiResponse::success([
                'schedule' => $schedule
            ], 'Schedule created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create schedule: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($statuteId, $scheduleId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $schedule = $statute->schedules()->findOrFail($scheduleId);
        
        return ApiResponse::success([
            'schedule' => $schedule
        ], 'Schedule retrieved successfully');
    }
    
    public function update(Request $request, $statuteId, $scheduleId): JsonResponse
    {
        $validated = $request->validate([
            'schedule_number' => 'sometimes|string|max:255',
            'schedule_title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'schedule_type' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        $schedule = $statute->schedules()->findOrFail($scheduleId);
        
        try {
            $schedule->update($validated);
            
            return ApiResponse::success([
                'schedule' => $schedule
            ], 'Schedule updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update schedule: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($statuteId, $scheduleId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $schedule = $statute->schedules()->findOrFail($scheduleId);
        
        try {
            $schedule->delete();
            
            return ApiResponse::success([], 'Schedule deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete schedule: ' . $e->getMessage(), 500);
        }
    }
}