<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                'verify@lawexa.com',
                'Lawexa Team'
            ),
            subject: 'Verify Your Email Address - Lawexa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'verificationUrl' => $this->verificationUrl(),
                'expires' => Config::get('auth.verification.expire', 60),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(): string
    {
        try {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $this->user->getKey(),
                    'hash' => sha1($this->user->getEmailForVerification()),
                ]
            );
        } catch (\Exception $e) {
            // Fallback URL construction if route is not found
            $baseUrl = rtrim(config('app.url'), '/');
            $expiry = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60))->timestamp;
            $signature = hash_hmac('sha256', "verification.verify{$this->user->getKey()}" . sha1($this->user->getEmailForVerification()) . $expiry, config('app.key'));
            
            return "{$baseUrl}/api/auth/email/verify/{$this->user->getKey()}/" . sha1($this->user->getEmailForVerification()) . 
                   "?expires={$expiry}&signature={$signature}";
        }
    }
}