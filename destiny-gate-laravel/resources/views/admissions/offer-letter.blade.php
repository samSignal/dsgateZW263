<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .meta { margin-top: 6px; font-size: 11px; color: #444; }
        .section { margin-top: 14px; }
        .label { font-weight: bold; }
        .watermark {
            position: fixed;
            top: 38%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 64px;
            color: rgba(200, 200, 200, 0.35);
            transform: rotate(-20deg);
            z-index: -1;
        }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; }
        .alert { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; font-size: 12px; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .actions { margin-top: 18px; padding-top: 14px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 16px; font-size: 12px; font-weight: bold; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #b6924c; color: #fff; }
        .accepted { color: #166534; font-weight: bold; }
    </style>
</head>
<body>
@if(!empty($watermark))
    <div class="watermark">{{ $watermark }}</div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="header">
    <p class="title">Provisional Offer Letter</p>
    <div class="meta">
        Offer ID: {{ $offer['id'] }} &nbsp;|&nbsp;
        Version: {{ $offer['version'] }} &nbsp;|&nbsp;
        Expires: {{ $offer['expires_at'] }}
    </div>
</div>

<div class="section">
    <p class="label">Applicant</p>
    <table>
        <tr><td class="label" style="width: 160px;">Application Number</td><td>{{ $application['application_number'] }}</td></tr>
        <tr><td class="label">Learner</td><td>{{ $application['student_name'] }}</td></tr>
        <tr><td class="label">Date of Birth</td><td>{{ $application['date_of_birth'] }}</td></tr>
        <tr><td class="label">Guardian Email</td><td>{{ $application['guardian_email'] }}</td></tr>
    </table>
</div>

<div class="section">
    <p class="label">Intake</p>
    <table>
        <tr><td class="label" style="width: 160px;">Academic Year</td><td>{{ $intake['academic_year'] }}</td></tr>
        <tr><td class="label">Applying Form</td><td>{{ $intake['form'] }}</td></tr>
        <tr><td class="label">Category</td><td>{{ $intake['category'] }}</td></tr>
    </table>
</div>

<div class="section">
    <p class="label">Important Notes</p>
    <p>This offer is a pre-activation enrollment-preparation step. Accepting this offer does not create an active learner, does not activate accounts, and does not trigger billing, timetables, results, or LMS access.</p>
    <p>Additional onboarding requirements may apply. Please follow the enrollment preparation instructions provided by the admissions office.</p>
</div>

<div class="actions">
    @if(!empty($offer['accepted_at']))
        <div class="accepted">Offer accepted on {{ $offer['accepted_at'] }}</div>
    @else
        <form method="POST" action="{{ route('applications.offer-letter.accept', $token) }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-primary">Accept Offer Letter</button>
        </form>
    @endif
</div>

</body>
</html>
