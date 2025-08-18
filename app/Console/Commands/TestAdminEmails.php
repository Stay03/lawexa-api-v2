<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestAdminEmails extends Command
{
    protected $signature = 'test:admin-emails';
    protected $description = 'Test and display admin email configuration';

    public function handle(NotificationService $notificationService)
    {
        $this->info('🔍 Testing Admin Email Configuration...');
        $this->newLine();

        // Get admin emails
        $adminEmails = $notificationService->getAdminEmails();
        
        if (empty($adminEmails)) {
            $this->error('❌ No admin emails configured!');
            $this->newLine();
            $this->info('Configure admin emails by:');
            $this->info('1. Setting ADMIN_EMAILS="email1@domain.com,email2@domain.com" in .env');
            $this->info('2. Creating users with role="superadmin" in database');
            return;
        }

        $this->info("✅ Found {count($adminEmails)} admin email(s):");
        foreach ($adminEmails as $email) {
            $this->info("  📧 {$email}");
        }
        
        $this->newLine();
        $this->info('📊 Admin Email Sources:');
        
        // Check config emails
        $configEmails = env('ADMIN_EMAILS', env('ADMIN_EMAIL', ''));
        if ($configEmails) {
            $this->info("  🔧 Config: {$configEmails}");
        } else {
            $this->warn("  ⚠️  No ADMIN_EMAILS or ADMIN_EMAIL in .env");
        }
        
        // Check database superadmins
        $superAdmins = \App\Models\User::where('role', 'superadmin')->get(['email', 'name']);
        if ($superAdmins->count() > 0) {
            $this->info("  🎯 Superadmin Users ({$superAdmins->count()}):");
            foreach ($superAdmins as $admin) {
                $this->info("     - {$admin->email} ({$admin->name})");
            }
        } else {
            $this->warn("  ⚠️  No superadmin users found in database");
        }
        
        $this->newLine();
        $this->info('🎉 Test complete!');
    }
}