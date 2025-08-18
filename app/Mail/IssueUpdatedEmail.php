<?php

namespace App\Mail;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueUpdatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Issue $issue,
        public array $changes = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                'support@lawexa.com',
                'Lawexa Support'
            ),
            subject: 'Issue Updated: ' . $this->issue->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.issue-updated',
            with: [
                'userName' => $this->user->name,
                'issueTitle' => $this->issue->title,
                'issueId' => $this->issue->id,
                'status' => $this->issue->status,
                'changes' => $this->changes,
                'updatedAt' => $this->issue->updated_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}