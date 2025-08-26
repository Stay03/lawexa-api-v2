<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupExpiredGuestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired guest accounts and their associated data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting guest cleanup process...');
        
        \App\Jobs\CleanupExpiredGuests::dispatch();
        
        $this->info('Guest cleanup job dispatched successfully.');
        
        return Command::SUCCESS;
    }
}
