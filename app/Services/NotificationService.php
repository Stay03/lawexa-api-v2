<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Mail\SubscriptionCreatedEmail;
use App\Mail\SubscriptionCancelledEmail;
use App\Mail\IssueCreatedEmail;
use App\Mail\IssueUpdatedEmail;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Issue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::to($user->email)->queue(new WelcomeEmail($user));
            
            Log::info('Welcome email queued successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendSubscriptionCreatedEmail(User $user, Subscription $subscription): void
    {
        try {
            $subscription->load('plan');
            Mail::to($user->email)->queue(new SubscriptionCreatedEmail($user, $subscription));
            
            Log::info('Subscription created email queued successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $subscription->plan->name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue subscription created email', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendSubscriptionCancelledEmail(User $user, Subscription $subscription): void
    {
        try {
            $subscription->load('plan');
            Mail::to($user->email)->queue(new SubscriptionCancelledEmail($user, $subscription));
            
            Log::info('Subscription cancelled email queued successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $subscription->plan->name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue subscription cancelled email', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendIssueCreatedEmail(User $user, Issue $issue): void
    {
        try {
            // Send confirmation email to user
            Mail::to($user->email)->queue(new IssueCreatedEmail($user, $issue, false));
            
            // Send notification to all admin emails
            $adminEmails = $this->getAdminEmails();
            foreach ($adminEmails as $adminEmail) {
                Mail::to($adminEmail)->queue(new IssueCreatedEmail($user, $issue, true));
            }
            
            Log::info('Issue created emails queued successfully', [
                'user_id' => $user->id,
                'issue_id' => $issue->id,
                'issue_severity' => $issue->severity,
                'admin_emails_count' => count($adminEmails),
                'admin_emails' => $adminEmails
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue issue created emails', [
                'user_id' => $user->id,
                'issue_id' => $issue->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendIssueUpdatedEmail(User $user, Issue $issue, array $changes = []): void
    {
        try {
            Mail::to($user->email)->queue(new IssueUpdatedEmail($user, $issue, $changes));
            
            Log::info('Issue updated email queued successfully', [
                'user_id' => $user->id,
                'issue_id' => $issue->id,
                'status' => $issue->status,
                'changes_count' => count($changes)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue issue updated email', [
                'user_id' => $user->id,
                'issue_id' => $issue->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAdminEmails(): array
    {
        try {
            $adminEmails = [];

            // Get admin emails from environment variable (comma-separated)
            $configEmails = env('ADMIN_EMAILS', env('ADMIN_EMAIL', ''));
            if ($configEmails) {
                $emailList = array_map('trim', explode(',', $configEmails));
                $adminEmails = array_merge($adminEmails, $emailList);
            }

            // Also get admin users from database
            $adminUsers = User::whereIn('role', ['admin', 'superadmin'])->pluck('email')->toArray();
            $adminEmails = array_merge($adminEmails, $adminUsers);

            // Remove duplicates and empty values
            $adminEmails = array_unique(array_filter($adminEmails));

            Log::debug('Admin emails retrieved', ['count' => count($adminEmails), 'emails' => $adminEmails]);

            return $adminEmails;
        } catch (\Exception $e) {
            Log::error('Failed to get admin emails', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function sendBulkEmails(array $emails, $mailable): void
    {
        try {
            foreach (array_chunk($emails, 50) as $emailChunk) {
                foreach ($emailChunk as $email) {
                    Mail::to($email)->queue($mailable);
                }
            }
            
            Log::info('Bulk emails queued successfully', [
                'email_count' => count($emails),
                'mailable_class' => get_class($mailable)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue bulk emails', [
                'email_count' => count($emails),
                'error' => $e->getMessage()
            ]);
        }
    }
}