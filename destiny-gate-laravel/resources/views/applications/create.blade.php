<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for Admission — DestinyGate Institute</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .container { max-width: 700px; margin: 40px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { color: #1a6b3c; font-size: 1.8rem; }
        .header p { color: #6b7280; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1a6b3c; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .section-title { font-size: 1rem; font-weight: 700; color: #1a6b3c; margin: 24px 0 12px; border-bottom: 2px solid #1a6b3c; padding-bottom: 6px; }
        .btn { background: #1a6b3c; color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; }
        .btn:hover { background: #0f3d22; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div style="font-size:3rem;">🛡️</div>
            <h1>DestinyGate Institute</h1>
            <p>Admission Application Form</p>
        </div>

        @if($errors->any())
        <div class="alert-error">
            <ul style="margin:0; padding-left:16px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('applications.store') }}">
            @csrf

            <div class="section-title">👤 Applicant Information</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Date of Birth *</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
            </div>

            <div class="section-title">👨‍👩‍👧 Guardian Information</div>
            <div class="form-group">
                <label>Guardian Full Name *</label>
                <input type="text" name="guardian_name" value="{{ old('guardian_name') }}" required>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Guardian Email *</label>
                    <input type="email" name="guardian_email" value="{{ old('guardian_email') }}" required>
                </div>
                <div class="form-group">
                    <label>Guardian Phone *</label>
                    <input type="text" name="guardian_phone" value="{{ old('guardian_phone') }}" required>
                </div>
            </div>

            <div class="section-title">🏫 Academic Information</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Intended Class *</label>
                    <input type="text" name="intended_class" value="{{ old('intended_class') }}" placeholder="e.g. Form 1" required>
                </div>
                <div class="form-group">
                    <label>Academic Year *</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year', date('Y') . '/' . (date('Y')+1)) }}" required>
                </div>
            </div>

            <button type="submit" class="btn">Submit Application</button>
        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="{{ route('home') }}" style="color:#6b7280; font-size:0.85rem;">← Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
