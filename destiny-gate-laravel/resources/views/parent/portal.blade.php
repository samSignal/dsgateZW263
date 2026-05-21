@extends('layouts.app')
@section('title', 'Parent Portal')
@section('content')
<div class="page-header">
    <h1>Parent Portal</h1>
    <p>Monitoring: <strong>{{ $student->full_name }}</strong> — {{ $student->schoolClass?->display_name ?? 'Unassigned' }}</p>
</div>

<div class="grid-4">
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Outstanding Fees</div>
        <div class="value" style="color:#dc2626; font-size:1.4rem;">${{ number_format($stats['outstanding_fees'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Attendance Rate</div>
        <div class="value">{{ $stats['attendance_rate'] }}%</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Average Grade</div>
        <div class="value">{{ $stats['avg_grade'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Behaviour Cases</div>
        <div class="value" style="color:#d97706;">{{ $stats['behaviour_cases'] }}</div>
    </div>
</div>

<div class="grid-2">
    <!-- Fees -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">💰 Fee Statement</div>
        <table>
            <thead><tr><th>Year</th><th>Term</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($student->fees as $fee)
                <tr>
                    <td>{{ $fee->academic_year }}</td>
                    <td>{{ strtoupper($fee->term) }}</td>
                    <td>${{ number_format($fee->amount, 2) }}</td>
                    <td>${{ number_format($fee->amount_paid, 2) }}</td>
                    <td style="color:{{ $fee->balance > 0 ? '#dc2626' : '#059669' }};">${{ number_format($fee->balance, 2) }}</td>
                    <td>{{ ucfirst($fee->status) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:20px;">No fee records</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Academic Results -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📚 Academic Results</div>
        <table>
            <thead><tr><th>Subject</th><th>Term</th><th>%</th><th>Grade</th></tr></thead>
            <tbody>
                @forelse($student->academicProgress as $progress)
                <tr>
                    <td>{{ $progress->subject->subject_name }}</td>
                    <td>{{ strtoupper($progress->term) }}</td>
                    <td>{{ $progress->percentage }}%</td>
                    <td><strong>{{ $progress->grade }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#9ca3af; padding:20px;">No results yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Announcements -->
<div class="table-card">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📢 School Notices</div>
    <div style="padding:16px;">
        @forelse($announcements as $ann)
        <div style="padding:10px; background:#f9fafb; border-radius:8px; margin-bottom:8px;">
            <strong>{{ $ann->title }}</strong>
            <span style="font-size:0.75rem; color:#6b7280; margin-left:8px;">{{ $ann->created_at->diffForHumans() }}</span>
            <p style="margin:4px 0 0; font-size:0.85rem; color:#374151;">{{ $ann->content }}</p>
        </div>
        @empty
        <p style="color:#9ca3af;">No announcements.</p>
        @endforelse
    </div>
</div>
@endsection
