<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Issue Update Notification - Admin</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .admin-badge { background: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 6px; text-align: center; margin: 20px 0; border: 1px solid #f5c6cb; font-weight: bold; }
        .status-resolved { background: #d4edda; color: #155724; padding: 8px 12px; border-radius: 4px; font-weight: bold; }
        .status-in-progress { background: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 4px; font-weight: bold; }
        .status-open { background: #cce7ff; color: #004085; padding: 8px 12px; border-radius: 4px; font-weight: bold; }
        .user-info { background: #e2e3f0; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6f42c1; }
        .changes-section { background: #fff8f0; padding: 20px; border-radius: 8px; border: 1px solid #ffecd1; margin: 20px 0; }
        .change-item { background: white; padding: 12px; margin: 8px 0; border-radius: 4px; border-left: 4px solid #dc3545; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #dc3545; margin-top: 0; }
        .ticket-id { background: #f8d7da; padding: 10px; border-radius: 6px; text-align: center; font-family: monospace; font-size: 18px; font-weight: bold; color: #721c24; margin: 20px 0; }
        .button { display: inline-block; background: #dc3545; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Issue Update Notification</h1>
            <p>Administrative Update Alert</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $adminName }}!</h2>
            
            <div class="admin-badge">
                🛡️ Admin Notification: An issue has been updated by administration
            </div>
            
            <div class="ticket-id">
                Issue ID: #{{ $issueId }}
            </div>
            
            <div class="user-info">
                <strong>Original Submitter:</strong> {{ $originalUserName }} ({{ $originalUserEmail }})<br>
                <strong>Issue Title:</strong> {{ $issueTitle }}
            </div>
            
            <p><strong>Current Status:</strong> 
                <span class="status-{{ str_replace('_', '-', strtolower($status)) }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>
            </p>
            
            <p><strong>Last Updated:</strong> {{ \Carbon\Carbon::parse($updatedAt)->format('F j, Y \a\t g:i A') }}</p>
            
            @if(!empty($changes))
            <div class="changes-section">
                <h3 style="margin-top: 0; color: #333;">Administrative Changes Made:</h3>
                
                @foreach($changes as $field => $change)
                <div class="change-item">
                    <strong>{{ ucwords(str_replace('_', ' ', $field)) }}:</strong><br>
                    @if(isset($change['from']) && isset($change['to']))
                        <span style="color: #dc3545; text-decoration: line-through;">{{ $change['from'] ?: 'Not set' }}</span> → 
                        <span style="color: #28a745; font-weight: bold;">{{ $change['to'] ?: 'Not set' }}</span>
                    @else
                        <span style="color: #28a745;">{{ $change }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
            
            @if($status === 'resolved')
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #c3e6cb; text-align: center;">
                <strong>✅ Issue Resolved</strong> This issue has been marked as resolved. The user has been notified.
            </div>
            @elseif($status === 'in_progress')
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #ffeaa7; text-align: center;">
                <strong>⚠️ In Progress</strong> This issue is actively being worked on.
            </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/admin/issues/{{ $issueId }}" class="button">View in Admin Panel</a>
            </div>
            
            <p><strong>Note:</strong> This is an administrative notification. The original issue submitter ({{ $originalUserName }}) has been separately notified of these changes.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Admin Notification</strong> | Issue ID: #{{ $issueId }} | Updated {{ \Carbon\Carbon::parse($updatedAt)->format('M j, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>