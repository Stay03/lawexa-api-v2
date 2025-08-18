<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Cancelled</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .cancellation-notice { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; text-align: center; margin: 20px 0; border: 1px solid #f5c6cb; }
        .access-info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bee5eb; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #dc3545; margin-top: 0; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button:hover { background: #5a6fd8; }
        .button-secondary { background: #6c757d; }
        .button-secondary:hover { background: #545b62; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subscription Cancelled</h1>
            <p>{{ $planName }}</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }},</h2>
            
            <div class="cancellation-notice">
                ❌ Your subscription has been cancelled as requested
            </div>
            
            <p>We've successfully cancelled your {{ $planName }} subscription. We're sorry to see you go!</p>
            
            <div class="access-info">
                <strong>ℹ️ Important:</strong> You still have access to your premium features until 
                <strong>{{ \Carbon\Carbon::parse($accessUntil)->format('F j, Y \a\t g:i A') }}</strong>
            </div>
            
            <p><strong>What happens now?</strong></p>
            <ul>
                <li>✅ No further charges will be made to your payment method</li>
                <li>📅 Continue using premium features until {{ \Carbon\Carbon::parse($accessUntil)->format('F j, Y') }}</li>
                <li>🔄 After that, your account will revert to free tier limitations</li>
                <li>💾 All your saved data, notes, and research history will be preserved</li>
            </ul>
            
            <p><strong>Changed your mind?</strong></p>
            <p>You can reactivate your subscription anytime before {{ \Carbon\Carbon::parse($accessUntil)->format('F j, Y') }} without losing any benefits:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/subscriptions" class="button">Reactivate Subscription</a>
                <a href="{{ config('app.url') }}/dashboard" class="button button-secondary">Go to Dashboard</a>
            </div>
            
            <p>We'd love to hear why you cancelled and how we can improve. Your feedback helps us serve our legal community better.</p>
            
            <p>Thank you for being part of the Lawexa community. If you need any assistance or have questions, please don't hesitate to reach out.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p>Cancelled on {{ \Carbon\Carbon::parse($cancelledAt)->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>