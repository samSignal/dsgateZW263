@extends('layouts.app')
@section('title', 'Student Portal')
@section('content')
<div class="page-header">
    <h1>Student Portal</h1>
    <p>{{ $student->full_name }} — <code>{{ $student->admission_number }}</code> — {{ $student->schoolClass?->display_name ?? 'Unassigned' }}</p>
</div>

<div class="grid-2">
    <!-- Results -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📚 My Results</div>
        <table>
            <thead><tr><th>Subject</th><th>Term</th><th>Type</th><th>%</th><th>Grade</th></tr></thead>
            <tbody>
                @forelse($student->academicProgress as $progress)
                <tr>
                    <td>{{ $progress->subject->subject_name }}</td>
                    <td>{{ strtoupper($progress->term) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($progress->assessment_type)) }}</td>
                    <td>{{ $progress->percentage }}%</td>
                    <td><strong>{{ $progress->grade }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; color:#9ca3af; padding:20px;">No results yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Timetable -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📅 My Timetable</div>
        <table>
            <thead><tr><th>Day</th><th>Period</th><th>Subject</th><th>Teacher</th><th>Time</th></tr></thead>
            <tbody>
                @forelse($student->schoolClass?->timetables ?? [] as $slot)
                <tr>
                    <td>{{ ucfirst($slot->day_of_week) }}</td>
                    <td>{{ $slot->period_number }}</td>
                    <td>{{ $slot->subject->subject_name }}</td>
                    <td>{{ $slot->teacher->full_name }}</td>
                    <td>{{ $slot->start_time }} - {{ $slot->end_time }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; color:#9ca3af; padding:20px;">No timetable set</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Attendance -->
<div class="table-card" style="margin-bottom:16px;">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📋 My Attendance</div>
    <table>
        <thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
            @forelse($student->attendance->take(20) as $att)
            <tr>
                <td>{{ $att->date->format('d M Y') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $att->status)) }}</td>
                <td>{{ $att->remarks ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center; color:#9ca3af; padding:20px;">No attendance records</td></tr>
            @endforelse
        </tbody>
    </table>
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
