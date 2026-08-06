@extends('layouts.app')
@section('title', 'Headmaster Dashboard')
@section('content')
<div class="page-header">
    <h1>Headmaster Dashboard</h1>
    <p>School overview and management</p>
</div>

<div class="grid-4">
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Total Students</div>
        <div class="value">{{ $stats['total_students'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Total Staff</div>
        <div class="value">{{ $stats['total_staff'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Fees Collected ({{ date('Y') }})</div>
        <div class="value" style="font-size:1.4rem;">${{ number_format($stats['fees_collected'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Behaviour Cases</div>
        <div class="value" style="color:#dc2626;">{{ $stats['behaviour_cases'] }}</div>
    </div>
</div>

<div class="grid-2">
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; display:flex; justify-content:space-between;">
            <span>📋 Recent Applications</span>
            <a href="{{ route('admin.applications') }}" style="font-size:0.8rem; color:#1a6b3c;">View all</a>
        </div>
        <table>
            <thead><tr><th>Applicant</th><th>Class</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($recentApplications as $app)
                <tr>
                    <td>{{ $app->full_name }}</td>
                    <td>{{ $app->intended_class }}</td>
                    <td>{{ ucfirst($app->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; display:flex; justify-content:space-between;">
            <span>⚠️ Recent Behaviour Cases</span>
            <a href="{{ route('headmaster.behaviour') }}" style="font-size:0.8rem; color:#1a6b3c;">View all</a>
        </div>
        <table>
            <thead><tr><th>Student</th><th>Issue</th><th>Severity</th></tr></thead>
            <tbody>
                @foreach($recentBehaviour as $case)
                <tr>
                    <td>{{ $case->student->full_name }}</td>
                    <td>{{ $case->issue_type }}</td>
                    <td>{{ ucfirst($case->severity) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Announcements -->
<div class="table-card">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; display:flex; justify-content:space-between;">
        <span>📢 Announcements</span>
        <a href="{{ route('headmaster.announcements') }}" style="font-size:0.8rem; color:#1a6b3c;">Manage</a>
    </div>
    <div style="padding:16px;">
        <form method="POST" action="{{ route('headmaster.announcements.store') }}" style="display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end; margin-bottom:16px;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Announcement title">
            </div>
            <div class="form-group" style="margin:0;">
                <label>Audience</label>
                <select name="audience">
                    <option value="all">All</option>
                    <option value="parents">Parents</option>
                    <option value="students">Students</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Post</button>
            <div class="form-group" style="grid-column:span 3; margin:0;">
                <label>Content</label>
                <textarea name="content" rows="2" required placeholder="Announcement content..."></textarea>
            </div>
        </form>

        @foreach($announcements as $ann)
        <div style="padding:10px; background:#f9fafb; border-radius:8px; margin-bottom:8px;">
            <strong>{{ $ann->title }}</strong>
            <span style="font-size:0.75rem; color:#6b7280; margin-left:8px;">{{ ucfirst($ann->audience) }} · {{ $ann->created_at->diffForHumans() }}</span>
            <p style="margin:4px 0 0; font-size:0.85rem; color:#374151;">{{ $ann->content }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection
