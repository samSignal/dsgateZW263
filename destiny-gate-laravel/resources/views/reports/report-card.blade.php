<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#0f172a; font-size:11px; }
        .page { position:relative; }
        .watermark { position:fixed; top:38%; left:12%; right:12%; text-align:center; font-size:46px; color:#1a6b3c; opacity:.055; font-weight:800; transform:rotate(-18deg); }
        .header { border-bottom:3px solid #1a6b3c; padding-bottom:12px; margin-bottom:14px; display:table; width:100%; }
        .brand, .photo { display:table-cell; vertical-align:top; }
        .brand h1 { margin:0; color:#0f3d22; font-size:24px; letter-spacing:.3px; }
        .brand .motto { color:#b88a11; font-weight:700; margin-top:3px; }
        .brand .address { color:#64748b; font-size:9.5px; margin-top:4px; }
        .photo { text-align:right; width:90px; }
        .photo-box { width:72px; height:84px; border:1px solid #cbd5e1; display:inline-block; text-align:center; line-height:84px; color:#94a3b8; }
        .meta { display:table; width:100%; margin:12px 0; }
        .meta .col { display:table-cell; width:50%; vertical-align:top; }
        .label { color:#64748b; font-size:9px; text-transform:uppercase; letter-spacing:.4px; }
        .value { font-weight:700; margin-bottom:6px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th { background:#f8fafc; color:#334155; font-size:9px; text-transform:uppercase; letter-spacing:.35px; text-align:left; padding:7px; border:1px solid #dbe3ec; }
        td { padding:7px; border:1px solid #e2e8f0; vertical-align:top; }
        .section-title { background:#1a6b3c; color:#fff; padding:6px 9px; font-weight:700; margin-top:13px; border-radius:3px; }
        .cards { display:table; width:100%; border-spacing:8px 0; margin-top:8px; }
        .card { display:table-cell; border:1px solid #e2e8f0; padding:9px; border-radius:4px; }
        .big { font-size:19px; font-weight:800; color:#0f3d22; }
        .badge { display:inline-block; padding:3px 7px; border-radius:10px; background:#ecfdf5; color:#065f46; font-weight:700; }
        .comments { display:table; width:100%; border-spacing:8px 0; margin-top:8px; }
        .comment { display:table-cell; width:50%; border:1px solid #e2e8f0; min-height:56px; padding:9px; }
        .signatures { display:table; width:100%; border-spacing:16px 0; margin-top:24px; }
        .sig { display:table-cell; text-align:center; border-top:1px solid #334155; padding-top:5px; color:#475569; }
        .footer { margin-top:18px; padding-top:8px; border-top:1px solid #e2e8f0; color:#64748b; font-size:9px; display:table; width:100%; }
        .footer div { display:table-cell; }
        .right { text-align:right; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="page">
    <div class="watermark">DESTINYGATE INSTITUTE</div>
    @if(($report['financial_clearance_status'] ?? 'cleared') !== 'cleared')
        <div style="background:#991b1b;color:#fff;text-align:center;font-weight:800;padding:9px;margin-bottom:10px;letter-spacing:.5px;">
            RESULT WITHHELD - FEES NOT CLEARED
        </div>
    @endif
    <div class="header">
        <div class="brand">
            <h1>DestinyGate Institute</h1>
            <div class="motto">Academic Excellence, Character, and Purpose</div>
            <div class="address">15412 Samson Kanyemba Street, Runyararo West, Masvingo</div>
            <div style="margin-top:8px;font-weight:700;">Academic Report Card</div>
        </div>
        <div class="photo"><div class="photo-box">PHOTO</div></div>
    </div>

    <div class="meta">
        <div class="col">
            <div class="label">Student</div><div class="value">{{ $report['student_name'] }}</div>
            <div class="label">Student Number</div><div class="value">{{ $report['student_number'] ?? $report['admission_number'] }}</div>
            <div class="label">Class</div><div class="value">{{ $report['form_name'] }} {{ $report['stream_name'] }}</div>
        </div>
        <div class="col">
            <div class="label">Report Number</div><div class="value">{{ $report['report_number'] }}</div>
            <div class="label">Term / Year</div><div class="value">{{ $report['term_name'] }} - {{ $report['academic_year_name'] }}</div>
            <div class="label">Generated</div><div class="value">{{ $report['generated_at'] }}</div>
        </div>
    </div>

    <div class="section-title">Subject Performance</div>
    <table>
        <thead><tr><th>Subject</th><th>Final %</th><th>Grade</th><th>Class Avg</th><th>Position</th><th>Teacher Comment</th></tr></thead>
        <tbody>
        @foreach($report['subjects'] as $subject)
            <tr>
                <td>{{ $subject->subject_name }}<br><span class="label">{{ $subject->subject_code }}</span></td>
                <td>{{ $subject->subject_average !== null ? number_format($subject->subject_average, 2) . '%' : '-' }}</td>
                <td><span class="badge">{{ $subject->subject_grade ?? '-' }}</span></td>
                <td>{{ $subject->class_average !== null ? number_format($subject->class_average, 2) . '%' : '-' }}</td>
                <td>{{ $subject->subject_position ?? '-' }}</td>
                <td>{{ $subject->teacher_comment ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">Overall Performance</div>
    <div class="cards">
        <div class="card"><div class="label">Overall Average</div><div class="big">{{ $report['overall_average'] !== null ? number_format($report['overall_average'], 2) . '%' : '-' }}</div></div>
        <div class="card"><div class="label">Overall Grade</div><div class="big">{{ $report['overall_grade'] ?? '-' }}</div></div>
        <div class="card"><div class="label">Stream Position</div><div class="big">{{ $report['class_position'] ?? '-' }}/{{ $report['stream_total_students'] ?? '-' }}</div></div>
        <div class="card"><div class="label">Trend</div><div class="big">{{ ucfirst($report['performance_trend']) }}</div></div>
    </div>

    <div class="section-title">Attendance, Conduct & Fees</div>
    <div class="cards">
        <div class="card"><div class="label">Attendance</div><div class="big">{{ $report['attendance_percentage'] !== null ? number_format($report['attendance_percentage'], 2) . '%' : '-' }}</div></div>
        <div class="card"><div class="label">Absent / Late</div><div class="big">{{ $report['attendance_summary']['absent_count'] }} / {{ $report['attendance_summary']['late_count'] }}</div></div>
        <div class="card"><div class="label">Conduct</div><div class="big">{{ $report['conduct_grade'] ?? '-' }}</div></div>
        <div class="card"><div class="label">Fees Balance</div><div class="big">${{ number_format($report['fees_balance'], 2) }}</div></div>
    </div>

    <div class="section-title">Skills & Competencies</div>
    <table>
        <tbody>
        @foreach($report['skills'] as $skill)
            <tr><td>{{ $skill->skill_name }}</td><td>{{ $skill->rating }}</td><td>{{ $skill->remarks ?? '' }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">Recommendations & Comments</div>
    <div class="comments">
        <div class="comment"><div class="label">Class Teacher</div>{{ $report['teacher_comment'] ?? '-' }}</div>
        <div class="comment"><div class="label">Headmaster</div>{{ $report['headmaster_comment'] ?? '-' }}</div>
    </div>
    <div class="comment" style="width:auto;margin-top:8px;"><div class="label">Recommendation</div>{{ $report['recommendation'] ?? '-' }}</div>

    <div class="signatures">
        <div class="sig">Class Teacher Signature</div>
        <div class="sig">Headmaster Signature</div>
        <div class="sig">Parent Signature</div>
    </div>

    <div class="footer">
        <div>DestinyGate Institute - {{ $report['report_number'] }}</div>
        <div class="right">QR Verification: {{ $report['report_number'] }}</div>
    </div>
</div>
</body>
</html>
