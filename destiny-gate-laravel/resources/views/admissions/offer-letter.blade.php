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
    </style>
</head>
<body>
@if(!empty($watermark))
    <div class="watermark">{{ $watermark }}</div>
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

</body>
</html>

