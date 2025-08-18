<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Lawexa</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #666; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .button:hover { background: #5a6fd8; }
        h1 { margin: 0; font-size: 28px; font-weight: 300; }
        h2 { color: #667eea; margin-top: 0; }
        .highlight { background: #f8f9ff; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Lawexa!</h1>
            <p>Your Legal Research Platform</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            
            <p>Welcome to Lawexa, Nigeria's comprehensive legal research platform. We're excited to have you join our community of legal professionals.</p>
            
            <div class="highlight">
                <strong>Your account is now active!</strong><br>
                Email: {{ $userEmail }}
            </div>
            
            <p>With Lawexa, you can:</p>
            <ul>
                <li>🔍 Search through thousands of court cases and legal precedents</li>
                <li>📚 Access comprehensive statutory provisions and laws</li>
                <li>📝 Save and organize your research with personal notes</li>
                <li>🎯 Get AI-powered legal insights and analysis</li>
                <li>📊 Track your research history and favorites</li>
            </ul>
            
            <p>Ready to start exploring? Click the button below to access your dashboard:</p>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/dashboard" class="button">Access Your Dashboard</a>
            </div>
            
            <p>If you have any questions or need assistance, our support team is here to help. Simply reply to this email or contact us through the platform.</p>
            
            <p>Happy researching!</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
            <p>This email was sent to {{ $userEmail }}. If you didn't sign up for Lawexa, please ignore this email.</p>
        </div>
    </div>
</body>
</html>