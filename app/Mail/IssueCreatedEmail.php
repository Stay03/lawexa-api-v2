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

class IssueCreatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Issue $issue,
        public bool $isAdminNotification = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isAdminNotification 
            ? 'New Issue Submitted: ' . $this->issue->title
            : 'Issue Submitted Successfully: ' . $this->issue->title;

        $fromAddress = $this->isAdminNotification 
            ? 'alerts@lawexa.com'
            : 'support@lawexa.com';
            
        $fromName = $this->isAdminNotification 
            ? 'Lawexa Alerts'
            : 'Lawexa Support';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            subject: $subject
        );
    }

    public function content(): Content
    {
        $view = $this->isAdminNotification 
            ? 'emails.issue-created-admin' 
            : 'emails.issue-created';

        return new Content(
            view: $view,
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'issueTitle' => $this->issue->title,
                'issueDescription' => $this->issue->description,
                'issueType' => $this->issue->type,
                'issueSeverity' => $this->issue->severity,
                'issueArea' => $this->issue->area,
                'issueId' => $this->issue->id,
                'createdAt' => $this->issue->created_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}