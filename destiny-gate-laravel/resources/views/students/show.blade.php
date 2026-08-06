@extends('layouts.app')
@section('title', $student->full_name)
@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
    <div>
        <h1>{{ $student->full_name }}</h1>
        <p>Admission: <code>{{ $student->admission_number }}</code> &nbsp;|&nbsp; Class: {{ $student->schoolClass?->display_name ?? 'Unassigned' }}</p>
    </div>
    <a href="{{ route('students.edit', $student) }}" class="btn-primary">✏️ Edit</a>
</div>

<div class="grid-2">
    <!-- Student Info -->
    <div class="stat-card">
        <h3 style="margin-bottom:12px; color:#374151;">Personal Information</h3>
        <table style="width:100%;">
            <tr><td style="color:#6b7280; padding:4px 0;">Email</td><td>{{ $student->email ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280; padding:4px 0;">Date of Birth</td><td>{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280; padding:4px 0;">Gender</td><td>{{ ucfirst($student->gender ?? '—') }}</td></tr>
            <tr><td style="color:#6b7280; padding:4px 0;">Status</td><td>{{ ucfirst($student->status) }}</td></tr>
            <tr><td style="color:#6b7280; padding:4px 0;">Blood Type</td><td>{{ $student->blood_type ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280; padding:4px 0;">Admission Date</td><td>{{ $student->admission_date->format('d M Y') }}</td></tr>
        </table>
    </div>

    <!-- Guardians -->
    <div class="stat-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="color:#374151;">Guardians</h3>
        </div>
        @forelse($student->guardians as $guardian)
        <div style="padding:8px 0; border-bottom:1px solid #f3f4f6;">
            <strong>{{ $guardian->full_name }}</strong> ({{ $guardian->relationship }})
            @if($guardian->is_primary_contact) <span style="color:#059669; font-size:0.75rem;">★ Primary</span> @endif
            <br><small style="color:#6b7280;">📞 {{ $guardian->phone }}</small>
        </div>
        @empty
        <p style="color:#9ca3af;">No guardians added yet.</p>
        @endforelse

        <details style="margin-top:12px;">
            <summary style="cursor:pointer; color:#8a6b34; font-size:0.9rem;">➕ Add Guardian</summary>
            <form method="POST" action="{{ route('students.guardians.store', $student) }}" style="margin-top:12px;">
                @csrf
                <div class="grid-2">
                    <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
                    <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
                </div>
                <div class="grid-2">
                    <div class="form-group"><label>Phone *</label><input type="text" name="phone" required></div>
                    <div class="form-group"><label>Relationship *</label><input type="text" name="relationship" required></div>
                </div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <button type="submit" class="btn-primary">Add Guardian</button>
            </form>
        </details>
    </div>
</div>

<!-- Academic Progress -->
<div class="table-card" style="margin-bottom:16px;">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📚 Academic Progress</div>
    <table>
        <thead><tr><th>Subject</th><th>Term</th><th>Type</th><th>Marks</th><th>%</th><th>Grade</th></tr></thead>
        <tbody>
            @forelse($student->academicProgress as $progress)
            <tr>
                <td>{{ $progress->subject->subject_name }}</td>
                <td>{{ strtoupper($progress->term) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($progress->assessment_type)) }}</td>
                <td>{{ $progress->marks }}/{{ $progress->total_marks }}</td>
                <td>{{ $progress->percentage }}%</td>
                <td><strong>{{ $progress->grade }}</strong></td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:20px;">No marks recorded yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Attendance Summary -->
<div class="grid-2">
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">📅 Recent Attendance</div>
        <table>
            <thead><tr><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($student->attendance->take(10) as $att)
                <tr>
                    <td>{{ $att->date->format('d M Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $att->status)) }}</td>
                </tr>
                @empty
                <tr><td colspan="2" style="text-align:center; color:#9ca3af; padding:20px;">No attendance records</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">⚠️ Behaviour Records</div>
        <table>
            <thead><tr><th>Date</th><th>Issue</th><th>Severity</th></tr></thead>
            <tbody>
                @forelse($student->behaviourRecords as $record)
                <tr>
                    <td>{{ $record->issue_date->format('d M Y') }}</td>
                    <td>{{ $record->issue_type }}</td>
                    <td>{{ ucfirst($record->severity) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center; color:#9ca3af; padding:20px;">No behaviour records</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
