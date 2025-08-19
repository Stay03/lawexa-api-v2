<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentCreatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public User $recipient,
        public string $notificationType = 'issue_owner'
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->notificationType) {
            'confirmation' => 'Comment Posted Successfully: ' . $this->comment->commentable->title,
            'reply' => 'New Reply to Your Comment: ' . $this->comment->commentable->title,
            default => 'New Comment on Your Issue: ' . $this->comment->commentable->title,
        };

        $fromAddress = 'notifications@lawexa.com';
        $fromName = 'Lawexa Notifications';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            subject: $subject
        );
    }

    public function content(): Content
    {
        $view = match ($this->notificationType) {
            'confirmation' => 'emails.comment-created-confirmation',
            'reply' => 'emails.comment-reply',
            default => 'emails.comment-created',
        };

        return new Content(
            view: $view,
            with: [
                'recipientName' => $this->recipient->name,
                'commenterName' => $this->comment->user->name,
                'commenterEmail' => $this->comment->user->email,
                'commentContent' => $this->comment->content,
                'commentId' => $this->comment->id,
                'issueTitle' => $this->comment->commentable->title,
                'issueId' => $this->comment->commentable->id,
                'createdAt' => $this->comment->created_at,
                'isReply' => $this->comment->isReply(),
                'parentComment' => $this->comment->parent,
                'notificationType' => $this->notificationType,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}