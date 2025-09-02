<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password - Lawexa</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .reset-button { display: inline-block; background: #e74c3c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 600; font-size: 16px; }
        .reset-button:hover { background: #c0392b; color: white; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #e74c3c; margin-top: 0; }
        .highlight { background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 20px 0; color: #856404; }
        .security-note { background: #ffeaea; padding: 15px; border-radius: 6px; border-left: 4px solid #e74c3c; margin: 20px 0; font-size: 14px; color: #721c24; }
        .link-text { word-break: break-all; color: #6c757d; font-size: 12px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Reset Your Password</h1>
            <p>Secure password reset for your Lawexa account</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            
            <p>We received a request to reset the password for your Lawexa account ({{ $userEmail }}). If you made this request, click the button below to reset your password.</p>
            
            <div class="highlight">
                <strong>⚠️ Password Reset Request</strong><br>
                If you didn't request this password reset, please ignore this email and your password will remain unchanged.
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>
            </div>
            
            <div class="security-note">
                <strong>🛡️ Security Information:</strong><br>
                • This reset link expires in {{ $expires }} minutes for your security<br>
                • Only use this link if you requested a password reset<br>
                • Never share this reset link with others<br>
                • After resetting, please use a strong, unique password
            </div>
            
            <p><strong>Password Security Tips:</strong></p>
            <ul>
                <li>🔒 Use at least 8 characters with a mix of letters, numbers, and symbols</li>
                <li>🚫 Don't reuse passwords from other accounts</li>
                <li>📱 Consider using a password manager</li>
                <li>🔄 Update your password regularly</li>
            </ul>
            
            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
            <div class="link-text">{{ $resetUrl }}</div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            
            <p><strong>Didn't request this?</strong> If you didn't ask to reset your password, please contact our support team immediately.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p>This password reset email was sent to {{ $userEmail }}.</p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                If you're having trouble clicking the reset button, copy and paste the URL above into your web browser.
            </p>
        </div>
    </div>
</body>
</html>