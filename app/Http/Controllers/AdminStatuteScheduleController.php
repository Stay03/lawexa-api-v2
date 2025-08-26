<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteScheduleController extends Controller
{
    public function index(Request $request, Statute $statute): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        
        $query = $statute->schedules();
        
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('schedule_type')) {
            $query->byScheduleType($request->schedule_type);
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
    
    public function store(Request $request, Statute $statute): JsonResponse
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
            // Map schedule fields to division fields
            $divisionData = [
                'division_type' => 'schedule',
                'division_number' => $validated['schedule_number'],
                'division_title' => $validated['schedule_title'],
                'division_subtitle' => $validated['schedule_type'] ?? null,
                'content' => $validated['content'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'status' => $validated['status'] ?? 'active',
                'effective_date' => $validated['effective_date'] ?? null,
                'level' => 1, // Schedules are typically top-level
            ];
            
            $schedule = $statute->divisions()->create($divisionData);
            
            return ApiResponse::success([
                'schedule' => $schedule
            ], 'Schedule created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create schedule: ' . $e->getMessage(), 500);
        }
    }
    
    public function show(Statute $statute, StatuteSchedule $schedule): JsonResponse
    {
        return ApiResponse::success([
            'schedule' => $schedule
        ], 'Schedule retrieved successfully');
    }
    
    public function update(Request $request, Statute $statute, StatuteSchedule $schedule): JsonResponse
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
            // Map schedule fields to division fields
            $divisionData = [];
            if (isset($validated['schedule_number'])) {
                $divisionData['division_number'] = $validated['schedule_number'];
            }
            if (isset($validated['schedule_title'])) {
                $divisionData['division_title'] = $validated['schedule_title'];
            }
            if (isset($validated['schedule_type'])) {
                $divisionData['division_subtitle'] = $validated['schedule_type'];
            }
            if (isset($validated['content'])) {
                $divisionData['content'] = $validated['content'];
            }
            if (isset($validated['sort_order'])) {
                $divisionData['sort_order'] = $validated['sort_order'];
            }
            if (isset($validated['status'])) {
                $divisionData['status'] = $validated['status'];
            }
            if (isset($validated['effective_date'])) {
                $divisionData['effective_date'] = $validated['effective_date'];
            }
            
            $schedule->update($divisionData);
            
            return ApiResponse::success([
                'schedule' => $schedule
            ], 'Schedule updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update schedule: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy(Statute $statute, StatuteSchedule $schedule): JsonResponse
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