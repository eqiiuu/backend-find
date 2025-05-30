<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>
        <p>You are receiving this email because we received a password reset request for your account.</p>
        
        <a href="{{ $resetLink }}" class="button">Reset Password</a>
        
        <p>If you did not request a password reset, no further action is required.</p>
        
        <p>This password reset link will expire in 60 minutes.</p>
        
        <p>Thanks,<br>
        {{ config('app.name') }}</p>
    </div>
</body>
</html> 