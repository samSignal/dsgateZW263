<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Submitted — DestinyGate Institute</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; padding: 48px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        h1 { color: #1a6b3c; margin-bottom: 8px; }
        p { color: #6b7280; }
        .app-number { background: #d1fae5; color: #065f46; padding: 12px 24px; border-radius: 10px; font-size: 1.2rem; font-weight: 700; margin: 20px 0; display: inline-block; }
        .btn { background: #1a6b3c; color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-size: 1rem; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 16px; }
    </style>
</head>
<body>
<div class="card">
    <div class="app-number">{{ $application->application_number }}</div>
    <div class="icon">✅</div>
    <h1>Application Submitted!</h1>
    <p>Thank you, <strong>{{ $application->full_name }}</strong>. Your application has been received.</p>
    <p>Please keep this application number for reference. We will contact you at <strong>{{ $application->email }}</strong> with updates.</p>
    <a href="/" class="btn">Back to Portal</a>
</div>
</body>
</html>
