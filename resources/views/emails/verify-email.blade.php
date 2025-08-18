<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email - Lawexa</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .verify-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 600; font-size: 16px; }
        .verify-button:hover { background: #5a6fd8; color: white; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #667eea; margin-top: 0; }
        .highlight { background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 20px 0; color: #856404; }
        .security-note { background: #f8f9ff; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea; margin: 20px 0; font-size: 14px; }
        .link-text { word-break: break-all; color: #6c757d; font-size: 12px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Verify Your Email</h1>
            <p>Complete your Lawexa registration</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            
            <p>Thank you for registering with Lawexa! To complete your account setup and start accessing our comprehensive legal research platform, please verify your email address.</p>
            
            <div class="highlight">
                <strong>⚠️ Account Verification Required</strong><br>
                Your account is currently unverified. Please click the button below to activate your account.
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-button">Verify Email Address</a>
            </div>
            
            <div class="security-note">
                <strong>🛡️ Security Information:</strong><br>
                • This verification link expires in {{ $expires }} minutes for your security<br>
                • If you didn't create an account, please ignore this email<br>
                • Never share this verification link with others
            </div>
            
            <p><strong>What happens after verification?</strong></p>
            <ul>
                <li>✅ Full access to Lawexa's legal research database</li>
                <li>📚 Browse thousands of court cases and statutory provisions</li>
                <li>📝 Create and save personal research notes</li>
                <li>🎯 Get AI-powered legal insights</li>
                <li>📊 Track your research history</li>
            </ul>
            
            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
            <div class="link-text">{{ $verificationUrl }}</div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            
            <p><strong>Need help?</strong> Contact our support team - we're here to assist you!</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p>This verification email was sent to {{ $userEmail }}.</p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                If you're having trouble clicking the verification button, copy and paste the URL above into your web browser.
            </p>
        </div>
    </div>
</body>
</html>