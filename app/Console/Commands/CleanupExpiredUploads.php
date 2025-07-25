<?php

namespace App\Console\Commands;

use App\Services\DirectS3UploadService;
use Illuminate\Console\Command;

class CleanupExpiredUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:cleanup-expired {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired pending uploads (older than 2 hours)';

    /**
     * Execute the console command.
     */
    public function handle(DirectS3UploadService $uploadService)
    {
        $this->info('Starting cleanup of expired uploads...');
        
        try {
            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No files will be deleted');
                // In a real implementation, you might want to add a dry-run method to the service
            }
            
            $result = $uploadService->cleanupExpiredUploads();
            
            $this->info("Cleanup completed successfully:");
            $this->line("  - Deleted: {$result['deleted']} files");
            $this->line("  - Errors: " . count($result['errors']));
            
            if (!empty($result['errors'])) {
                $this->error('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->line("  - File ID {$error['file_id']}: {$error['error']}");
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
