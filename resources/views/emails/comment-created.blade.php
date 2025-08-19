<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Comment on Your Issue</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .notification-badge { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; border: 1px solid #bee5eb; }
        .comment-box { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; position: relative; }
        .comment-box::before { content: '💬'; position: absolute; top: -10px; left: 20px; background: white; padding: 0 10px; font-size: 20px; }
        .issue-info { background: #fff3cd; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba; margin: 15px 0; }
        .commenter-info { color: #17a2b8; font-weight: bold; margin-bottom: 10px; }
        .comment-content { background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd; margin: 10px 0; white-space: pre-wrap; }
        .action-button { display: inline-block; background: #17a2b8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #28a745; margin-top: 0; }
        .timestamp { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💬 New Comment</h1>
            <p>Someone commented on your issue</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $recipientName }}!</h2>
            
            <div class="notification-badge">
                📢 {{ $commenterName }} posted a new comment on your issue
            </div>
            
            <div class="issue-info">
                <strong>Issue:</strong> {{ $issueTitle }}
                <br><strong>Issue ID:</strong> #{{ $issueId }}
            </div>
            
            <div class="comment-box">
                <div class="commenter-info">
                    👤 {{ $commenterName }}
                    <span class="timestamp">• {{ \Carbon\Carbon::parse($createdAt)->format('M j, Y \a\t g:i A') }}</span>
                </div>
                
                <div class="comment-content">{{ $commentContent }}</div>
            </div>
            
            @if($isReply && $parentComment)
            <div style="margin: 15px 0; padding: 10px; background: #f1f3f4; border-radius: 6px; border-left: 4px solid #17a2b8;">
                <strong>💭 In reply to:</strong><br>
                <em>{{ Str::limit($parentComment->content, 150) }}</em>
            </div>
            @endif
            
            <p>You can view all comments and reply to this issue by logging into your account.</p>
            
            <div style="text-align: center; margin: 25px 0;">
                <a href="#" class="action-button">View Issue & Reply</a>
            </div>
            
            <p><strong>Don't want these notifications?</strong> You can manage your notification preferences in your account settings.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Issue ID:</strong> #{{ $issueId }} | <strong>Comment ID:</strong> #{{ $commentId }}</p>
        </div>
    </div>
</body>
</html>