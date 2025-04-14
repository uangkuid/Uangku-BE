<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Uangku Verification Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7fafc;
            color: #2d3748;
            padding: 2rem;
        }
        .container {
            max-width: 480px;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin: auto;
        }
        h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 1rem;
            line-height: 1.6;
        }
        .otp-code {
            font-size: 2.25rem;
            font-weight: 700;
            color: #007BFF;
            margin: 1.5rem 0;
            text-align: center;
            letter-spacing: 4px;
        }
        .footer {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #a0aec0;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Hey there 👋</h2>
    <p>Here’s your <strong>Uangku</strong> verification code. Just pop it into the app to continue:</p>

    <div class="otp-code">{{ $otp }}</div>

    <p>This code is valid for a short time only. Didn’t request it? No worries, just ignore this message.</p>

    <div class="footer">
        &copy; {{ now()->year }} Uangku. All rights reserved.
    </div>
</div>
</body>
</html>
