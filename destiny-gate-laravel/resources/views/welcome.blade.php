<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DestinyGate Institute - School Management System</title>
    <meta name="description" content="DestinyGate Institute — Raising a Godly, Skilled and Confident Generation. Comprehensive school management system for Masvingo, Zimbabwe.">
    <link rel="icon" type="image/png" href="/logo-mark.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #0f3d22 0%, #123918 100%); color: #fff; min-height: 100vh; }
        .hero { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 40px 20px; }
        .shield { width: 130px; height: auto; object-fit: contain; margin-bottom: 24px; filter: drop-shadow(0 8px 24px rgba(0,0,0,.35)); }
        h1 { font-size: 2.8rem; font-weight: 800; color: #EAC445; margin-bottom: 8px; }
        .motto { font-size: 1rem; color: #9ca3af; margin-bottom: 12px; font-style: italic; }
        .tagline { font-size: 0.9rem; color: #d1fae5; margin-bottom: 28px; }
        .address { font-size: 0.85rem; color: #6b7280; margin-bottom: 40px; }
        .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 900px; margin: 0 auto 40px; }
        .feature { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; }
        .feature .icon { font-size: 2rem; margin-bottom: 10px; }
        .feature h3 { font-size: 1rem; color: #EAC445; margin-bottom: 6px; }
        .feature p { font-size: 0.8rem; color: #9ca3af; }
        .actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 14px 32px; border-radius: 10px; font-size: 1rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
        .btn-login { background: #1a6b3c; color: #fff; }
        .btn-apply { background: transparent; color: #EAC445; border: 2px solid #c9a227; }
        .btn:hover { opacity: 0.9; }
        @media (max-width: 600px) { .features { grid-template-columns: 1fr; } h1 { font-size: 2rem; } }
    </style>
</head>
<body>
<div class="hero">
    <img src="/logo-mark.png" alt="DestinyGate Institute" class="shield">
    <h1>DestinyGate Institute</h1>
    <p class="motto">"Raising a Godly, Skilled and Confident Generation"</p>
    <p class="tagline">Comprehensive School Management System · Elegant school management for the modern institution</p>
    <p class="address">📍 15412 Samson Kanyemba Street, Runyararo West, Masvingo &nbsp;|&nbsp; 📞 0779 672 246 / 0710415364</p>

    <div class="features">
        <div class="feature">
            <div class="icon">🎓</div>
            <h3>Student Management</h3>
            <p>Complete student records, admissions, and academic tracking</p>
        </div>
        <div class="feature">
            <div class="icon">💰</div>
            <h3>Finance & Fees</h3>
            <p>Fee management, payment recording, and financial reports</p>
        </div>
        <div class="feature">
            <div class="icon">📊</div>
            <h3>Academic Progress</h3>
            <p>Marks recording, grading, and performance analytics</p>
        </div>
        <div class="feature">
            <div class="icon">📅</div>
            <h3>Attendance</h3>
            <p>Daily attendance tracking with detailed reports</p>
        </div>
        <div class="feature">
            <div class="icon">👨‍👩‍👧</div>
            <h3>Parent Portal</h3>
            <p>Parents can monitor their child's progress and fees</p>
        </div>
        <div class="feature">
            <div class="icon">📢</div>
            <h3>Announcements</h3>
            <p>School-wide communication and notice board</p>
        </div>
    </div>

    <div class="actions">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-login">Go to Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-login">Staff Login</a>
            <a href="{{ route('applications.create') }}" class="btn btn-apply">Apply for Admission</a>
        @endauth
    </div>
</div>
</body>
</html>
