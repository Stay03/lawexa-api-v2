<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public string $resetUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                'noreply@lawexa.com',
                'Lawexa Team'
            ),
            subject: 'Reset Your Password - Lawexa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'resetUrl' => $this->resetUrl,
                'expires' => config('auth.passwords.users.expire', 60),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}