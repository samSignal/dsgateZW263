<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DestinyGate Institute</title>
    <meta name="description" content="DestinyGate Institute — Raising a Godly, Skilled and Confident Generation. Comprehensive school management system for Masvingo, Zimbabwe.">
    <link rel="icon" type="image/png" href="/logo-mark.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/tsx/main.tsx'])
</head>
<body style="margin:0;padding:0;font-family:'Inter',-apple-system,sans-serif;">
    <div id="app"></div>
</body>
</html>
