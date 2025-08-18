<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelledEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Subscription $subscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                'billing@lawexa.com',
                'Lawexa Billing'
            ),
            subject: 'Subscription Cancelled - ' . $this->subscription->plan->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-cancelled',
            with: [
                'userName' => $this->user->name,
                'planName' => $this->subscription->plan->name,
                'cancelledAt' => $this->subscription->updated_at,
                'accessUntil' => $this->subscription->next_payment_date ?? $this->subscription->updated_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}