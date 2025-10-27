<?php

namespace App\Console\Commands;

use App\Models\Statute;
use App\Services\OrderIndexManagerService;
use Illuminate\Console\Command;

class PopulateStatuteOrderIndices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statutes:populate-order-index
                            {statute_id? : The ID of a specific statute to reindex}
                            {--dry-run : Run without making database changes}
                            {--show-details : Show detailed output for each statute}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate order_index values for statute divisions and provisions';

    /**
     * Execute the console command.
     */
    public function handle(OrderIndexManagerService $orderIndexManager)
    {
        $statuteId = $this->argument('statute_id');
        $dryRun = $this->option('dry-run');
        $showDetails = $this->option('show-details');

        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be saved');
        }

        // Get statutes to process
        if ($statuteId) {
            $statutes = Statute::where('id', $statuteId)->get();
            if ($statutes->isEmpty()) {
                $this->error("Statute with ID {$statuteId} not found");
                return Command::FAILURE;
            }
        } else {
            $statutes = Statute::all();
            $this->info("Processing all statutes (" . $statutes->count() . " total)");
        }

        $totalStatutes = $statutes->count();
        $processedCount = 0;
        $errorCount = 0;
        $totalItems = 0;
        $totalDuration = 0;

        $progressBar = $this->output->createProgressBar($totalStatutes);
        $progressBar->start();

        foreach ($statutes as $statute) {
            $progressBar->advance();

            try {
                // Reindex the statute
                $report = $orderIndexManager->reindexStatute($statute, $dryRun);

                $processedCount++;
                $totalItems += $report['total_items'];
                $totalDuration += $report['duration_ms'];

                if (isset($report['error'])) {
                    $errorCount++;
                    if ($showDetails) {
                        $this->newLine();
                        $this->error("Error processing statute {$statute->id} ({$statute->slug}): {$report['error']}");
                    }
                } else {
                    if ($showDetails) {
                        $this->newLine();
                        $this->info("Processed: {$statute->slug}");
                        $this->line("  Total items: {$report['total_items']}");
                        $this->line("  Divisions updated: {$report['divisions_updated']}");
                        $this->line("  Provisions updated: {$report['provisions_updated']}");
                        $this->line("  Duration: {$report['duration_ms']}ms");
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                if ($showDetails) {
                    $this->newLine();
                    $this->error("Exception processing statute {$statute->id}: {$e->getMessage()}");
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('========================================');
        $this->info('SUMMARY');
        $this->info('========================================');
        $this->info("Total statutes processed: {$processedCount}");
        $this->info("Total items indexed: {$totalItems}");
        $this->info("Errors: {$errorCount}");
        $this->info("Total duration: " . round($totalDuration, 2) . "ms");
        $this->info("Average per statute: " . round($totalDuration / max($processedCount, 1), 2) . "ms");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN completed - no changes were saved');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('Order indices populated successfully!');
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
