<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Uangku Verification Code</title>
    <!-- Load Poppins from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
            font-weight: 600;
        }
        p {
            font-size: 1rem;
            line-height: 1.6;
            font-weight: 400;
        }
        .otp-code {
            font-size: 2rem;
            font-weight: 600;
            color: #2D9CDC;
            border: 2px solid #2D9CDC;
            border-radius: 16px;
            padding: 1rem 2rem;
            display: inline-block;
            margin: 1.5rem auto;
            letter-spacing: 0.5rem;
            text-align: center;
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
    <p>Here’s your <strong>Uangku</strong> verification code. Enter this code in the app to continue. It’s valid for <strong>5 minutes</strong> only:</p>

    <div class="otp-code">{{ $otp }}</div>

    <p>If you didn’t request this, feel free to ignore this email. We’ve got your back.</p>

    <div class="footer">
        &copy; {{ now()->year }} Uangku. All rights reserved.
    </div>
</div>
</body>
</html>
