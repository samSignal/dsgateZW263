@extends('layouts.app')
@section('title', 'Teacher Dashboard')
@section('content')
<div class="page-header">
    <h1>Teacher Dashboard</h1>
    <p>Welcome, {{ $staff->full_name }}</p>
</div>

<div class="grid-4">
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">My Classes</div>
        <div class="value">{{ $stats['my_classes'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Total Students</div>
        <div class="value">{{ $stats['total_students'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Marks Recorded</div>
        <div class="value">{{ $stats['marks_recorded'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Comments Added</div>
        <div class="value">{{ $stats['comments_added'] }}</div>
    </div>
</div>

<div class="grid-2">
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">🏫 My Classes</div>
        <table>
            <thead><tr><th>Class</th><th>Subject</th><th>Year</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($myClasses as $classId => $assignments)
                @php $class = $assignments->first()->schoolClass; @endphp
                <tr>
                    <td><strong>{{ $class->display_name }}</strong></td>
                    <td>{{ $assignments->pluck('subject.subject_name')->join(', ') }}</td>
                    <td>{{ $assignments->first()->academic_year }}</td>
                    <td>
                        <a href="{{ route('teacher.class.students', $class) }}" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">View Students</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="stat-card">
        <h3 style="margin-bottom:16px; color:#374151;">Quick Actions</h3>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="{{ route('teacher.classes') }}" class="btn-primary">🏫 My Classes</a>
        </div>
    </div>
</div>
@endsection
