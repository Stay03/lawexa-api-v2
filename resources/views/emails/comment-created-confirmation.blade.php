<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comment Posted Successfully</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .success-badge { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; border: 1px solid #c3e6cb; }
        .comment-preview { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; position: relative; }
        .comment-preview::before { content: '✅'; position: absolute; top: -10px; left: 20px; background: white; padding: 0 10px; font-size: 20px; }
        .issue-info { background: #fff3cd; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba; margin: 15px 0; }
        .your-comment { background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd; margin: 10px 0; white-space: pre-wrap; }
        .action-button { display: inline-block; background: #6f42c1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #6f42c1; margin-top: 0; }
        .timestamp { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Comment Posted!</h1>
            <p>Your comment has been published</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $recipientName }}!</h2>
            
            <div class="success-badge">
                🎉 Your comment has been successfully posted and is now visible to others
            </div>
            
            <div class="issue-info">
                <strong>Issue:</strong> {{ $issueTitle }}
                <br><strong>Issue ID:</strong> #{{ $issueId }}
            </div>
            
            <div class="comment-preview">
                <div style="color: #6f42c1; font-weight: bold; margin-bottom: 10px;">
                    Your Comment
                    <span class="timestamp">• {{ \Carbon\Carbon::parse($createdAt)->format('M j, Y \a\t g:i A') }}</span>
                </div>
                
                <div class="your-comment">{{ $commentContent }}</div>
            </div>
            
            @if($isReply && $parentComment)
            <div style="margin: 15px 0; padding: 10px; background: #f1f3f4; border-radius: 6px; border-left: 4px solid #6f42c1;">
                <strong>💭 Your reply to:</strong><br>
                <em>{{ Str::limit($parentComment->content, 150) }}</em>
            </div>
            @endif
            
            <p>Your comment is now live and others can see and respond to it. You'll receive notifications when someone replies to your comment.</p>
            
            <div style="text-align: center; margin: 25px 0;">
                <a href="#" class="action-button">View Full Discussion</a>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>📧 You'll get notified if someone replies to your comment</li>
                <li>🔔 Issue updates will be sent to you as they happen</li>
                <li>💬 You can continue the conversation anytime</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Comment ID:</strong> #{{ $commentId }} | <strong>Issue ID:</strong> #{{ $issueId }}</p>
        </div>
    </div>
</body>
</html>