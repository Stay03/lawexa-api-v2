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
            
            // Send notification to admin email
            $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL'));
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new IssueCreatedEmail($user, $issue, true));
            }
            
            Log::info('Issue created emails queued successfully', [
                'user_id' => $user->id,
                'issue_id' => $issue->id,
                'issue_severity' => $issue->severity,
                'admin_notified' => !empty($adminEmail)
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
            // Get admin emails from config or database
            $configEmail = config('mail.admin_email', env('ADMIN_EMAIL'));
            $adminEmails = [];

            if ($configEmail) {
                $adminEmails[] = $configEmail;
            }

            // Also get admin users from database
            $adminUsers = User::whereIn('role', ['admin', 'superadmin'])->pluck('email')->toArray();
            $adminEmails = array_merge($adminEmails, $adminUsers);

            return array_unique(array_filter($adminEmails));
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