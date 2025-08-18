<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Activated</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .success-badge { background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 6px; text-align: center; margin: 20px 0; border: 1px solid #c3e6cb; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #28a745; margin-top: 0; }
        .plan-details { background: #f8f9ff; padding: 20px; border-radius: 8px; border: 1px solid #e7ecff; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Subscription Activated!</h1>
            <p>Welcome to {{ $planName }}</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            
            <div class="success-badge">
                ✅ Your subscription has been successfully activated
            </div>
            
            <p>Thank you for subscribing to Lawexa! Your {{ $planName }} subscription is now active and you have full access to all features.</p>
            
            <div class="plan-details">
                <h3 style="margin-top: 0; color: #333;">Subscription Details</h3>
                
                <div class="detail-row">
                    <span><strong>Plan:</strong></span>
                    <span>{{ $planName }}</span>
                </div>
                
                <div class="detail-row">
                    <span><strong>Amount:</strong></span>
                    <span class="amount">₦{{ number_format($amount / 100, 2) }}</span>
                </div>
                
                <div class="detail-row">
                    <span><strong>Next Payment:</strong></span>
                    <span>{{ \Carbon\Carbon::parse($nextPaymentDate)->format('F j, Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span><strong>Subscription ID:</strong></span>
                    <span style="font-family: monospace; background: #f1f1f1; padding: 2px 6px; border-radius: 3px;">{{ $subscriptionCode }}</span>
                </div>
            </div>
            
            <p><strong>What's next?</strong></p>
            <ul>
                <li>📱 Access your premium features immediately</li>
                <li>🔄 Your subscription will auto-renew on {{ \Carbon\Carbon::parse($nextPaymentDate)->format('F j, Y') }}</li>
                <li>⚙️ Manage your subscription anytime in your account settings</li>
                <li>📧 You'll receive email notifications before each billing cycle</li>
            </ul>
            
            <p>Questions about your subscription? Need help getting started? Our support team is always ready to assist you.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p><strong>Subscription ID:</strong> {{ $subscriptionCode }}</p>
        </div>
    </div>
</body>
</html>