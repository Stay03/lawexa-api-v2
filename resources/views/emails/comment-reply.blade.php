<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Reply to Your Comment</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .notification-badge { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; border: 1px solid #ffeeba; }
        .reply-box { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; position: relative; }
        .reply-box::before { content: '↩️'; position: absolute; top: -10px; left: 20px; background: white; padding: 0 10px; font-size: 20px; }
        .original-comment { background: #f1f3f4; padding: 15px; border-radius: 6px; border-left: 4px solid #fd7e14; margin: 15px 0; }
        .issue-info { background: #d1ecf1; padding: 15px; border-radius: 6px; border: 1px solid #bee5eb; margin: 15px 0; }
        .commenter-info { color: #fd7e14; font-weight: bold; margin-bottom: 10px; }
        .comment-content { background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd; margin: 10px 0; white-space: pre-wrap; }
        .action-button { display: inline-block; background: #fd7e14; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #fd7e14; margin-top: 0; }
        .timestamp { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>↩️ New Reply</h1>
            <p>Someone replied to your comment</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $recipientName }}!</h2>
            
            <div class="notification-badge">
                🔔 {{ $commenterName }} replied to your comment
            </div>
            
            <div class="issue-info">
                <strong>Issue:</strong> {{ $issueTitle }}
                <br><strong>Issue ID:</strong> #{{ $issueId }}
            </div>
            
            @if($parentComment)
            <div class="original-comment">
                <strong>💭 Your original comment:</strong><br>
                <em>{{ Str::limit($parentComment->content, 200) }}</em>
            </div>
            @endif
            
            <div class="reply-box">
                <div class="commenter-info">
                    👤 {{ $commenterName }} replied
                    <span class="timestamp">• {{ \Carbon\Carbon::parse($createdAt)->format('M j, Y \a\t g:i A') }}</span>
                </div>
                
                <div class="comment-content">{{ $commentContent }}</div>
            </div>
            
            <p>Someone found your comment interesting enough to reply! Check out what they had to say and continue the conversation.</p>
            
            <div style="text-align: center; margin: 25px 0;">
                <a href="#" class="action-button">View Reply & Respond</a>
            </div>
            
            <p><strong>Join the discussion:</strong></p>
            <ul>
                <li>💬 Reply back to continue the conversation</li>
                <li>👀 See what others are saying about this issue</li>
                <li>🔔 Get notified about future replies to your comments</li>
            </ul>
            
            <p><strong>Don't want reply notifications?</strong> You can manage your notification preferences in your account settings.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Issue ID:</strong> #{{ $issueId }} | <strong>Reply ID:</strong> #{{ $commentId }}</p>
        </div>
    </div>
</body>
</html>