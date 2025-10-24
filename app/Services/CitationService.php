<?php

namespace App\Services;

use App\Models\CourtCase;
use App\Models\Country;
use App\Models\Court;
use Carbon\Carbon;

class CitationService
{
    /**
     * Generate citation for a case following the pattern:
     * (YYYY) LAWEXA ELR [ID] [COUNTRY_ABBR] [COURT_ABBR]
     *
     * Returns null if any required field is missing or not found.
     *
     * @param CourtCase $case
     * @return string|null
     */
    public function generateCitation(CourtCase $case): ?string
    {
        // Check if all required fields are present
        if (!$case->date || !$case->country || !$case->court || !$case->id) {
            return null;
        }

        // Extract year from date
        $year = Carbon::parse($case->date)->format('Y');

        // Lookup country by name to get abbreviation
        $country = Country::where('name', $case->country)->first();
        if (!$country || !$country->abbreviation) {
            return null;
        }

        // Lookup court by name to get abbreviation
        $court = Court::where('name', $case->court)->first();
        if (!$court || !$court->abbreviation) {
            return null;
        }

        // Generate citation
        return sprintf(
            '(%s) LAWEXA ELR %d %s %s',
            $year,
            $case->id,
            strtoupper($country->abbreviation),
            strtoupper($court->abbreviation)
        );
    }

    /**
     * Append citation to title, removing any existing citation first.
     *
     * @param string $title
     * @param string $citation
     * @return string
     */
    public function appendCitationToTitle(string $title, string $citation): string
    {
        // Remove any existing citation from title
        $cleanTitle = $this->removeCitationFromTitle($title);

        // Append new citation
        return trim($cleanTitle) . ' ' . $citation;
    }

    /**
     * Remove LAWEXA ELR citation pattern from title.
     *
     * @param string $title
     * @return string
     */
    public function removeCitationFromTitle(string $title): string
    {
        // Pattern: (YYYY) LAWEXA ELR [digits] [UPPERCASE] [UPPERCASE]
        $pattern = '/\s*\(\d{4}\)\s+LAWEXA\s+ELR\s+\d+\s+[A-Z]+\s+[A-Z]+\s*$/';

        return trim(preg_replace($pattern, '', $title));
    }
}
