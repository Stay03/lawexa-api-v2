<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityLoggerService
{
    private const CHANNEL = 'security';

    public function logAuthenticationAttempt(string $email, bool $successful, ?string $reason = null, ?Request $request = null): void
    {
        $context = [
            'email' => $email,
            'successful' => $successful,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if (!$successful && $reason) {
            $context['failure_reason'] = $reason;
        }

        if ($successful) {
            Log::channel(self::CHANNEL)->info('Authentication attempt successful', $context);
        } else {
            Log::channel(self::CHANNEL)->warning('Authentication attempt failed', $context);
        }
    }

    public function logGuestSessionCreated(int $guestId, string $token, ?Request $request = null): void
    {
        Log::channel(self::CHANNEL)->info('Guest session created', [
            'guest_id' => $guestId,
            'token_prefix' => substr($token, 0, 8) . '...',
            'expires_at' => now()->addDays(30)->toISOString(),
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logGuestSessionDeleted(int $guestId, string $reason, ?string $createdAt = null): void
    {
        Log::channel(self::CHANNEL)->info('Guest session deleted', [
            'guest_id' => $guestId,
            'deletion_reason' => $reason,
            'created_at' => $createdAt,
            'deleted_at' => now()->toISOString(),
        ]);
    }

    public function logUserLogout(int $userId, ?Request $request = null): void
    {
        Log::channel(self::CHANNEL)->info('User logout', [
            'user_id' => $userId,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logPasswordReset(string $email, bool $successful, ?Request $request = null): void
    {
        Log::channel(self::CHANNEL)->info('Password reset attempt', [
            'email' => $email,
            'successful' => $successful,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logEmailVerification(int $userId, bool $successful, ?string $reason = null, ?Request $request = null): void
    {
        $context = [
            'user_id' => $userId,
            'successful' => $successful,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if (!$successful && $reason) {
            $context['failure_reason'] = $reason;
        }

        Log::channel(self::CHANNEL)->info('Email verification attempt', $context);
    }

    public function logProfileUpdate(int $userId, array $changedFields, ?Request $request = null): void
    {
        Log::channel(self::CHANNEL)->info('Profile updated', [
            'user_id' => $userId,
            'changed_fields' => $changedFields,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logSuspiciousActivity(string $activity, array $context = [], ?Request $request = null): void
    {
        $logContext = array_merge($context, [
            'activity_type' => $activity,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        Log::channel(self::CHANNEL)->warning('Suspicious activity detected', $logContext);
    }

    public function logRoleChange(int $userId, string $oldRole, string $newRole, int $changedByUserId, ?Request $request = null): void
    {
        Log::channel(self::CHANNEL)->warning('User role changed', [
            'user_id' => $userId,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'changed_by_user_id' => $changedByUserId,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logAdminAction(int $adminUserId, string $action, array $context = [], ?Request $request = null): void
    {
        $logContext = array_merge($context, [
            'admin_user_id' => $adminUserId,
            'action' => $action,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        Log::channel(self::CHANNEL)->info('Admin action performed', $logContext);
    }
}