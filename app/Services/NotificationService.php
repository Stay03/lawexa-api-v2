<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Mail\SubscriptionCreatedEmail;
use App\Mail\SubscriptionCancelledEmail;
use App\Mail\IssueCreatedEmail;
use App\Mail\IssueUpdatedEmail;
use App\Mail\CommentCreatedEmail;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Issue;
use App\Models\Comment;
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
                'user_name' => $user->name,
                'welcome_email_sent_to' => $user->email
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
                'user_email' => $user->email,
                'subscription_id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'subscription_email_sent_to' => $user->email,
                'amount' => $subscription->amount
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
                'user_email' => $user->email,
                'subscription_id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'cancellation_email_sent_to' => $user->email,
                'cancelled_at' => $subscription->updated_at
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
                'user_email' => $user->email,
                'issue_id' => $issue->id,
                'issue_title' => $issue->title,
                'issue_severity' => $issue->severity,
                'user_confirmation_sent_to' => $user->email,
                'admin_notifications_sent_to' => $adminEmails,
                'total_admin_emails' => count($adminEmails)
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
                'user_email' => $user->email,
                'issue_id' => $issue->id,
                'issue_title' => $issue->title,
                'current_status' => $issue->status,
                'changes_made' => array_keys($changes),
                'update_notification_sent_to' => $user->email,
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

            // Also get superadmin users from database
            $superAdminUsers = User::where('role', 'superadmin')->pluck('email')->toArray();
            $adminEmails = array_merge($adminEmails, $superAdminUsers);

            // Remove duplicates and empty values
            $adminEmails = array_unique(array_filter($adminEmails));

            Log::info('Admin emails discovered for notifications', [
                'total_count' => count($adminEmails),
                'config_emails' => $configEmails ? explode(',', $configEmails) : [],
                'superadmin_users' => $superAdminUsers,
                'final_email_list' => $adminEmails
            ]);

            return $adminEmails;
        } catch (\Exception $e) {
            Log::error('Failed to get admin emails', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function sendCommentCreatedEmail(Comment $comment): void
    {
        try {
            $comment->load(['user', 'commentable', 'parent.user']);
            
            // Get the issue owner (assuming commentable is an Issue)
            $issueOwner = $comment->commentable->user ?? null;
            
            // Don't send notification if commenter is the issue owner
            if ($issueOwner && $issueOwner->id !== $comment->user_id) {
                // Send notification to issue owner
                Mail::to($issueOwner->email)->queue(
                    new CommentCreatedEmail($comment, $issueOwner, 'issue_owner')
                );
            }
            
            // If this is a reply, notify the parent comment author
            if ($comment->isReply() && $comment->parent && $comment->parent->user) {
                $parentCommentAuthor = $comment->parent->user;
                
                // Don't send notification if replier is the parent comment author or issue owner
                if ($parentCommentAuthor->id !== $comment->user_id && 
                    (!$issueOwner || $parentCommentAuthor->id !== $issueOwner->id)) {
                    
                    Mail::to($parentCommentAuthor->email)->queue(
                        new CommentCreatedEmail($comment, $parentCommentAuthor, 'reply')
                    );
                }
            }
            
            // Send confirmation to the commenter
            Mail::to($comment->user->email)->queue(
                new CommentCreatedEmail($comment, $comment->user, 'confirmation')
            );
            
            Log::info('Comment notification emails queued successfully', [
                'comment_id' => $comment->id,
                'commenter_id' => $comment->user_id,
                'issue_id' => $comment->commentable_id,
                'is_reply' => $comment->isReply(),
                'issue_owner_notified' => $issueOwner && $issueOwner->id !== $comment->user_id,
                'parent_author_notified' => $comment->isReply() && 
                    $comment->parent && 
                    $comment->parent->user && 
                    $comment->parent->user->id !== $comment->user_id &&
                    (!$issueOwner || $comment->parent->user->id !== $issueOwner->id),
                'confirmation_sent' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue comment notification emails', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage()
            ]);
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