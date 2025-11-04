<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\CourtCase;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    public function getCountries(): JsonResponse
    {
        $countries = University::select('country_code', 'country')
            ->distinct()
            ->orderBy('country_code')
            ->get()
            ->map(function ($university) {
                return [
                    'country_code' => $university->country_code,
                    'country' => $university->country
                ];
            })
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

        $universities = $query->select('id', 'name', 'country_code', 'country', 'website')
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
        $professions = \App\Models\User::whereNotNull('profession')
            ->where('profession', '!=', '')
            ->pluck('profession')
            ->filter()
            ->mapToGroups(function ($profession) {
                return [strtolower(trim($profession)) => trim($profession)];
            })
            ->map(function ($group) {
                // Convert to consistent title case format
                return ucwords(strtolower($group->first()));
            })
            ->sort()
            ->values()
            ->toArray();

        return ApiResponse::success([
            'professions' => $professions
        ], 'Common professions retrieved successfully');
    }

    public function getAreasOfExpertise(): JsonResponse
    {
        $users = \App\Models\User::whereNotNull('area_of_expertise')
            ->where('area_of_expertise', '!=', '[]')
            ->where('area_of_expertise', '!=', '')
            ->select('area_of_expertise')
            ->get();

        $areas = collect();

        foreach ($users as $user) {
            $userAreas = $user->area_of_expertise;
            if (is_array($userAreas) && !empty($userAreas)) {
                foreach ($userAreas as $area) {
                    if (!empty(trim($area))) {
                        $areas->push(trim($area));
                    }
                }
            }
        }

        // Apply case-insensitive consolidation
        $uniqueAreas = $areas->filter()
            ->mapToGroups(function ($area) {
                return [strtolower(trim($area)) => trim($area)];
            })
            ->map(function ($group) {
                // Convert to consistent title case format
                return ucwords(strtolower($group->first()));
            })
            ->sort()
            ->values()
            ->toArray();

        return ApiResponse::success([
            'areas' => $uniqueAreas
        ], 'Areas of expertise retrieved successfully');
    }

    /**
     * Smart split comma-separated values respecting parentheses and quotes
     *
     * @param string $value The comma-separated string to split
     * @return array Array of split values
     */
    private function smartSplitCommaSeparatedValues(string $value): array
    {
        $result = [];
        $current = '';
        $parenthesesDepth = 0;
        $inQuotes = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            // Toggle quote state (handle escaped quotes)
            if ($char === '"' && ($i === 0 || $value[$i - 1] !== '\\')) {
                $inQuotes = !$inQuotes;
                $current .= $char;
                continue;
            }

            // Track parentheses depth only when not in quotes
            if (!$inQuotes) {
                if ($char === '(') {
                    $parenthesesDepth++;
                } elseif ($char === ')') {
                    $parenthesesDepth--;
                }
            }

            // Split on comma only if we're not inside parentheses or quotes
            if ($char === ',' && $parenthesesDepth === 0 && !$inQuotes) {
                $trimmed = trim($current);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Add the last segment
        $trimmed = trim($current);
        if (!empty($trimmed)) {
            $result[] = $trimmed;
        }

        return $result;
    }

    public function getCaseTopics(Request $request): JsonResponse
    {
        // Get all non-null topics from database
        $allTopics = CourtCase::whereNotNull('topic')
            ->where('topic', '!=', '')
            ->distinct()
            ->pluck('topic');

        // Split comma-separated values using smart split (respects parentheses and quotes)
        $topics = collect();

        foreach ($allTopics as $topicString) {
            // Use smart split instead of simple explode
            $individualTopics = $this->smartSplitCommaSeparatedValues($topicString);
            foreach ($individualTopics as $topic) {
                if (!empty($topic)) {
                    $topics->push($topic);
                }
            }
        }

        // Apply search filter if provided
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $topics = $topics->filter(function ($topic) use ($search) {
                return strpos(strtolower($topic), $search) !== false;
            });
        }

        // Remove duplicates with case-insensitive consolidation
        $uniqueTopics = $topics->mapToGroups(function ($topic) {
                return [strtolower($topic) => $topic];
            })
            ->map(function ($group) {
                return $group->first();
            })
            ->sort()
            ->values();

        // Use Laravel's LengthAwarePaginator for consistency with other controllers
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $uniqueTopics->forPage($page, $perPage)->values(),
            $uniqueTopics->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return ApiResponse::success([
            'topics' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ], 'Case topics retrieved successfully');
    }

    public function getCaseTags(Request $request): JsonResponse
    {
        // Get all non-null tags from database
        $allTags = CourtCase::whereNotNull('tag')
            ->where('tag', '!=', '')
            ->distinct()
            ->pluck('tag');

        // Split comma-separated values using smart split (respects parentheses and quotes)
        $tags = collect();

        foreach ($allTags as $tagString) {
            // Use smart split instead of simple explode
            $individualTags = $this->smartSplitCommaSeparatedValues($tagString);
            foreach ($individualTags as $tag) {
                if (!empty($tag)) {
                    $tags->push($tag);
                }
            }
        }

        // Apply search filter if provided
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $tags = $tags->filter(function ($tag) use ($search) {
                return strpos(strtolower($tag), $search) !== false;
            });
        }

        // Remove duplicates with case-insensitive consolidation
        $uniqueTags = $tags->mapToGroups(function ($tag) {
                return [strtolower($tag) => $tag];
            })
            ->map(function ($group) {
                return $group->first();
            })
            ->sort()
            ->values();

        // Use Laravel's LengthAwarePaginator for consistency with other controllers
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $uniqueTags->forPage($page, $perPage)->values(),
            $uniqueTags->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return ApiResponse::success([
            'tags' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ], 'Case tags retrieved successfully');
    }
}
