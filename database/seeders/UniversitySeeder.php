<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // You can place your CSV file in storage/app/universities.csv
        // Or use the data directly here for now
        
        $this->command->info('Seeding universities...');

        // Clear existing data
        University::truncate();

        // Sample data based on your CSV format: country_code,name,website
        $universities = [
            ['AD', 'University of Andorra', 'http://www.uda.ad/'],
            ['AE', 'Abu Dhabi University', 'http://www.adu.ac.ae/'],
            ['AE', 'Ajman University of Science & Technology', 'http://www.ajman.ac.ae/'],
            // Add more universities here or load from CSV file
        ];

        // If you have a CSV file, uncomment and use this instead:
        // $csvPath = storage_path('app/universities.csv');
        // if (file_exists($csvPath)) {
        //     $handle = fopen($csvPath, 'r');
        //     $header = fgetcsv($handle); // Skip header if exists
        //     
        //     while (($data = fgetcsv($handle)) !== FALSE) {
        //         University::create([
        //             'country_code' => $data[0],
        //             'name' => $data[1],
        //             'website' => $data[2] ?? null,
        //         ]);
        //     }
        //     fclose($handle);
        // } else {
            // Use the sample data
            foreach ($universities as $university) {
                University::create([
                    'country_code' => $university[0],
                    'name' => $university[1],
                    'website' => $university[2] ?? null,
                ]);
            }
        // }

        $this->command->info('Universities seeded successfully!');
    }
}
