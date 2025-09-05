<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    public function getCountries(): JsonResponse
    {
        $countries = University::select('country_code')
            ->distinct()
            ->orderBy('country_code')
            ->get()
            ->pluck('country_code')
            ->toArray();

        return ApiResponse::success([
            'countries' => $countries
        ], 'Countries retrieved successfully');
    }

    public function getUniversities(Request $request): JsonResponse
    {
        $query = University::query();

        // Filter by country if provided
        if ($request->has('country')) {
            $query->byCountry($request->country);
        }

        // Search by name if provided
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $universities = $query->select('id', 'name', 'country_code', 'website')
            ->orderBy('name')
            ->get();

        return ApiResponse::success([
            'universities' => $universities
        ], 'Universities retrieved successfully');
    }

    public function getAcademicLevels(): JsonResponse
    {
        $levels = [
            // Nigerian System
            ['value' => '100L', 'label' => '100 Level (1st Year)'],
            ['value' => '200L', 'label' => '200 Level (2nd Year)'],
            ['value' => '300L', 'label' => '300 Level (3rd Year)'],
            ['value' => '400L', 'label' => '400 Level (4th Year)'],
            ['value' => '500L', 'label' => '500 Level (5th Year)'],
            ['value' => '600L', 'label' => '600 Level (6th Year)'],
            
            // US System
            ['value' => 'Freshman', 'label' => 'Freshman (1st Year)'],
            ['value' => 'Sophomore', 'label' => 'Sophomore (2nd Year)'],
            ['value' => 'Junior', 'label' => 'Junior (3rd Year)'],
            ['value' => 'Senior', 'label' => 'Senior (4th Year)'],
            
            // UK/General System
            ['value' => 'First Year', 'label' => 'First Year'],
            ['value' => 'Second Year', 'label' => 'Second Year'],
            ['value' => 'Third Year', 'label' => 'Third Year'],
            ['value' => 'Final Year', 'label' => 'Final Year'],
            
            // General/Numeric
            ['value' => 'Year 1', 'label' => 'Year 1'],
            ['value' => 'Year 2', 'label' => 'Year 2'],
            ['value' => 'Year 3', 'label' => 'Year 3'],
            ['value' => 'Year 4', 'label' => 'Year 4'],
            ['value' => 'Year 5', 'label' => 'Year 5'],
            
            // Graduate Levels
            ['value' => 'Masters Year 1', 'label' => 'Masters - Year 1'],
            ['value' => 'Masters Year 2', 'label' => 'Masters - Year 2'],
            ['value' => 'PhD Year 1', 'label' => 'PhD - Year 1'],
            ['value' => 'PhD Year 2', 'label' => 'PhD - Year 2'],
            ['value' => 'PhD Year 3', 'label' => 'PhD - Year 3'],
            ['value' => 'PhD Year 4+', 'label' => 'PhD - Year 4+'],
            
            // General Categories
            ['value' => 'Undergraduate', 'label' => 'Undergraduate (General)'],
            ['value' => 'Graduate', 'label' => 'Graduate (General)'],
            ['value' => 'Postgraduate', 'label' => 'Postgraduate'],
            ['value' => 'Diploma', 'label' => 'Diploma Program'],
            ['value' => 'Certificate', 'label' => 'Certificate Program'],
        ];

        return ApiResponse::success([
            'levels' => $levels
        ], 'Academic levels retrieved successfully');
    }

    public function getLegalAreas(): JsonResponse
    {
        $legalAreas = [
            'Criminal Law',
            'Civil Law',
            'Corporate Law',
            'Constitutional Law',
            'Family Law',
            'Employment Law',
            'Immigration Law',
            'Intellectual Property Law',
            'Environmental Law',
            'Tax Law',
            'Real Estate Law',
            'Healthcare Law',
            'International Law',
            'Human Rights Law',
            'Commercial Law',
            'Contract Law',
            'Tort Law',
            'Administrative Law',
            'Banking Law',
            'Insurance Law',
        ];

        sort($legalAreas);

        return ApiResponse::success([
            'legal_areas' => $legalAreas
        ], 'Legal areas retrieved successfully');
    }

    public function getCommonProfessions(): JsonResponse
    {
        $professions = [
            'lawyer',
            'student',
            'doctor',
            'engineer',
            'teacher',
            'nurse',
            'accountant',
            'consultant',
            'manager',
            'analyst',
            'developer',
            'designer',
            'researcher',
            'journalist',
            'entrepreneur',
            'pharmacist',
            'architect',
            'psychologist',
            'social worker',
            'marketing specialist',
            'sales representative',
            'project manager',
            'administrator',
            'technician',
            'other',
        ];

        sort($professions);

        return ApiResponse::success([
            'professions' => $professions
        ], 'Common professions retrieved successfully');
    }
}
