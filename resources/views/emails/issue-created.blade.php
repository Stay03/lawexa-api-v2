<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Issue Submitted Successfully</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .success-badge { background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 6px; text-align: center; margin: 20px 0; border: 1px solid #c3e6cb; }
        .issue-details { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; }
        .detail-row { margin: 10px 0; }
        .severity-high { color: #dc3545; font-weight: bold; }
        .severity-medium { color: #ffc107; font-weight: bold; }
        .severity-low { color: #28a745; font-weight: bold; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #17a2b8; margin-top: 0; }
        .ticket-id { background: #e3f2fd; padding: 10px; border-radius: 6px; text-align: center; font-family: monospace; font-size: 18px; font-weight: bold; color: #1976d2; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Issue Submitted!</h1>
            <p>We've received your report</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            
            <div class="success-badge">
                ✅ Your issue has been successfully submitted and assigned a tracking number
            </div>
            
            <div class="ticket-id">
                Issue ID: #{{ $issueId }}
            </div>
            
            <p>Thank you for reporting this issue. Our team has been notified and will investigate promptly.</p>
            
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
                
                <div class="detail-row">
                    <strong>Submitted:</strong> {{ \Carbon\Carbon::parse($createdAt)->format('F j, Y \a\t g:i A') }}
                </div>
                
                <div class="detail-row" style="margin-top: 15px;">
                    <strong>Description:</strong><br>
                    <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-top: 8px;">
                        {!! nl2br(e($issueDescription)) !!}
                    </div>
                </div>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>🔍 Our technical team will review your issue within 24 hours</li>
                <li>📧 You'll receive email updates on the progress</li>
                <li>⚡ High severity issues are prioritized for faster resolution</li>
                <li>💬 We may reach out if we need additional information</li>
            </ul>
            
            <p><strong>Need to add more details or files?</strong> You can update your issue anytime by replying to this email with your Issue ID (#{{ $issueId }}).</p>
            
            <p>We appreciate your patience and will resolve this as quickly as possible.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Issue ID:</strong> #{{ $issueId }} | <strong>Reference:</strong> Keep this email for your records</p>
        </div>
    </div>
</body>
</html>