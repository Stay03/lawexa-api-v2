<?php

namespace App\Console\Commands;

use App\Models\University;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Locale;

class ImportUniversitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:universities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import universities from CSV files in the data folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dataPath = base_path('data');
        $csvFiles = ['world-universities-1.csv', 'world-universities-2.csv'];
        
        $totalProcessed = 0;
        $totalImported = 0;
        $unresolvedCountryCodes = [];
        $skippedDuplicates = 0;
        
        $this->info('Starting university import from CSV files...');
        
        foreach ($csvFiles as $fileName) {
            $filePath = $dataPath . '/' . $fileName;
            
            if (!File::exists($filePath)) {
                $this->warn("File not found: {$fileName}");
                continue;
            }
            
            $this->info("Processing: {$fileName}");
            
            $file = fopen($filePath, 'r');
            $lineNumber = 0;
            
            while (($row = fgetcsv($file)) !== false) {
                $lineNumber++;
                $totalProcessed++;
                
                if (count($row) < 3) {
                    $this->warn("Skipping malformed row {$lineNumber} in {$fileName}");
                    continue;
                }
                
                $countryCode = trim($row[0]);
                $universityName = trim($row[1]);
                $website = trim($row[2]);
                
                if (empty($countryCode) || empty($universityName)) {
                    $this->warn("Skipping row {$lineNumber} - missing required data");
                    continue;
                }
                
                $existingUniversity = University::where('country_code', $countryCode)
                    ->where('name', $universityName)
                    ->first();
                
                if ($existingUniversity) {
                    $skippedDuplicates++;
                    continue;
                }
                
                $countryName = $this->getCountryName($countryCode);
                
                if ($countryName === $countryCode) {
                    if (!in_array($countryCode, $unresolvedCountryCodes)) {
                        $unresolvedCountryCodes[] = $countryCode;
                    }
                }
                
                University::create([
                    'country_code' => $countryCode,
                    'country' => $countryName ?: $countryCode,
                    'name' => $universityName,
                    'website' => $website ?: null,
                ]);
                
                $totalImported++;
                
                if ($totalImported % 100 === 0) {
                    $this->info("Imported {$totalImported} universities...");
                }
            }
            
            fclose($file);
            $this->info("Finished processing: {$fileName}");
        }
        
        $this->info("\n=== Import Summary ===");
        $this->info("Total rows processed: {$totalProcessed}");
        $this->info("Successfully imported: {$totalImported}");
        $this->info("Skipped duplicates: {$skippedDuplicates}");
        
        if (!empty($unresolvedCountryCodes)) {
            $this->warn("\nCountry codes that couldn't be resolved:");
            foreach ($unresolvedCountryCodes as $code) {
                $this->warn("- {$code}");
            }
            $this->warn("These country codes were stored as-is in the country field.");
        }
        
        $this->info("\nImport completed successfully!");
        
        return Command::SUCCESS;
    }
    
    /**
     * Convert country code to full country name using Laravel's Locale
     */
    private function getCountryName(string $countryCode): string
    {
        try {
            $countryName = Locale::getDisplayRegion('-' . $countryCode, 'en');
            
            if ($countryName && $countryName !== $countryCode) {
                return $countryName;
            }
        } catch (\Exception $e) {
            // Locale failed, fallback to country code
        }
        
        return $countryCode;
    }
}
