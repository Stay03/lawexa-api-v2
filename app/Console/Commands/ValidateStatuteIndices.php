<?php

namespace App\Console\Commands;

use App\Models\Statute;
use App\Services\OrderIndexManagerService;
use Illuminate\Console\Command;

class ValidateStatuteIndices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statutes:validate-indices {statute_id? : The ID of a specific statute to validate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate order_index values for statute content';

    /**
     * Execute the console command.
     */
    public function handle(OrderIndexManagerService $orderIndexManager)
    {
        $statuteId = $this->argument('statute_id');

        if ($statuteId) {
            // Validate specific statute
            $statute = Statute::find($statuteId);
            if (!$statute) {
                $this->error("Statute with ID {$statuteId} not found");
                return Command::FAILURE;
            }

            $this->info("Validating statute: {$statute->slug}");
            $report = $orderIndexManager->validateIndices($statute);
            $this->displayReport($report);

            return $report['valid'] ? Command::SUCCESS : Command::FAILURE;
        } else {
            // Validate all statutes
            $statutes = Statute::all();
            $this->info("Validating all statutes (" . $statutes->count() . " total)\n");

            $totalValid = 0;
            $totalInvalid = 0;
            $invalidStatutes = [];

            $progressBar = $this->output->createProgressBar($statutes->count());
            $progressBar->start();

            foreach ($statutes as $statute) {
                $report = $orderIndexManager->validateIndices($statute);

                if ($report['valid']) {
                    $totalValid++;
                } else {
                    $totalInvalid++;
                    $invalidStatutes[] = [
                        'statute' => $statute->slug,
                        'issues' => $report['issues']
                    ];
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Summary
            $this->info('========================================');
            $this->info('VALIDATION SUMMARY');
            $this->info('========================================');
            $this->info("Total statutes: " . $statutes->count());
            $this->info("Valid: {$totalValid}");
            $this->info("Invalid: {$totalInvalid}");
            $this->newLine();

            if ($totalInvalid > 0) {
                $this->warn("STATUTES WITH ISSUES:");
                foreach ($invalidStatutes as $invalid) {
                    $this->error("\n" . $invalid['statute'] . ":");
                    foreach ($invalid['issues'] as $issue) {
                        $this->line("  - {$issue}");
                    }
                }
                $this->newLine();
                $this->warn("Run 'php artisan statutes:populate-order-index' to fix issues");
            } else {
                $this->info("All statutes have valid order indices!");
            }

            return $totalInvalid > 0 ? Command::FAILURE : Command::SUCCESS;
        }
    }

    /**
     * Display validation report for a single statute
     */
    private function displayReport(array $report): void
    {
        $this->newLine();
        $this->info('========================================');
        $this->info('VALIDATION REPORT');
        $this->info('========================================');
        $this->line("Statute: {$report['statute_slug']}");
        $this->line("Status: " . ($report['valid'] ? '<info>VALID</info>' : '<error>INVALID</error>'));
        $this->newLine();

        if (isset($report['statistics'])) {
            $this->info('STATISTICS:');
            $this->line("  Total items: {$report['statistics']['total_items']}");
            $this->line("  Items with index: {$report['statistics']['items_with_index']}");
            $this->line("  Items without index: {$report['statistics']['items_without_index']}");

            if (isset($report['statistics']['average_gap'])) {
                $this->line("  Average gap: {$report['statistics']['average_gap']}");
                $this->line("  Minimum gap: {$report['statistics']['minimum_gap']}");
            }
        }

        if (!empty($report['issues'])) {
            $this->newLine();
            $this->error('ISSUES FOUND:');
            foreach ($report['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        if (isset($report['duplicates']) && !empty($report['duplicates'])) {
            $this->newLine();
            $this->error('DUPLICATE INDICES:');
            foreach ($report['duplicates'] as $index => $count) {
                $this->line("  - order_index {$index}: {$count} occurrences");
            }
        }

        $this->newLine();
        $this->info("RECOMMENDATION: {$report['recommendation']}");
    }
}
