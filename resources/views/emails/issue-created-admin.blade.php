<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Issue Submitted</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #fd7e14 0%, #e55100 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .alert-badge { background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; text-align: center; margin: 20px 0; border: 1px solid #ffeaa7; }
        .user-info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #b8daff; }
        .issue-details { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; }
        .detail-row { margin: 10px 0; }
        .severity-high { color: #dc3545; font-weight: bold; background: #f8d7da; padding: 4px 8px; border-radius: 4px; }
        .severity-medium { color: #856404; font-weight: bold; background: #fff3cd; padding: 4px 8px; border-radius: 4px; }
        .severity-low { color: #155724; font-weight: bold; background: #d4edda; padding: 4px 8px; border-radius: 4px; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #fd7e14; margin-top: 0; }
        .ticket-id { background: #fff3cd; padding: 10px; border-radius: 6px; text-align: center; font-family: monospace; font-size: 18px; font-weight: bold; color: #856404; margin: 20px 0; }
        .button { display: inline-block; background: #fd7e14; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button:hover { background: #e55100; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 New Issue Alert</h1>
            <p>User Issue Requires Attention</p>
        </div>
        
        <div class="content">
            <h2>New Issue Submitted</h2>
            
            <div class="alert-badge">
                ⚠️ A new issue has been submitted and requires admin attention
            </div>
            
            <div class="ticket-id">
                Issue ID: #{{ $issueId }}
            </div>
            
            <div class="user-info">
                <h3 style="margin-top: 0; color: #333;">Submitted By</h3>
                <div class="detail-row">
                    <strong>Name:</strong> {{ $userName }}
                </div>
                <div class="detail-row">
                    <strong>Email:</strong> <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a>
                </div>
                <div class="detail-row">
                    <strong>Submitted:</strong> {{ \Carbon\Carbon::parse($createdAt)->format('F j, Y \a\t g:i A') }}
                </div>
            </div>
            
            <div class="issue-details">
                <h3 style="margin-top: 0; color: #333;">Issue Details</h3>
                
                <div class="detail-row">
                    <strong>Title:</strong> {{ $issueTitle }}
                </div>
                
                <div class="detail-row">
                    <strong>Type:</strong> {{ ucfirst($issueType) }}
                </div>
                
                <div class="detail-row">
                    <strong>Severity:</strong> 
                    <span class="severity-{{ strtolower($issueSeverity) }}">{{ ucfirst($issueSeverity) }}</span>
                </div>
                
                @if($issueArea)
                <div class="detail-row">
                    <strong>Area:</strong> {{ ucfirst($issueArea) }}
                </div>
                @endif
                
                <div class="detail-row" style="margin-top: 15px;">
                    <strong>Description:</strong><br>
                    <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-top: 8px;">
                        {!! nl2br(e($issueDescription)) !!}
                    </div>
                </div>
            </div>
            
            @if($issueSeverity === 'high')
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #f5c6cb; text-align: center;">
                <strong>🔥 HIGH PRIORITY:</strong> This issue requires immediate attention!
            </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/admin/issues/{{ $issueId }}" class="button">View in Admin Panel</a>
            </div>
            
            <p><strong>Response Time Guidelines:</strong></p>
            <ul>
                <li><strong>High:</strong> Respond within 2 hours</li>
                <li><strong>Medium:</strong> Respond within 24 hours</li>
                <li><strong>Low:</strong> Respond within 48 hours</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa Admin Panel</p>
            <p><strong>Issue ID:</strong> #{{ $issueId }} | This is an automated admin notification</p>
        </div>
    </div>
</body>
</html>