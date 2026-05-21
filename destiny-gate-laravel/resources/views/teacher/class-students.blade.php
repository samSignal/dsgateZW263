@extends('layouts.app')
@section('title', $class->display_name . ' Students')
@section('content')
<div class="page-header">
    <h1>{{ $class->display_name }} — Students</h1>
    <p>Record marks, attendance, and comments</p>
</div>

<div class="table-card" style="margin-bottom:16px;">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">🎓 Students ({{ $students->count() }})</div>
    <table>
        <thead><tr><th>Admission #</th><th>Name</th><th>Gender</th><th>Actions</th></tr></thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td><code>{{ $student->admission_number }}</code></td>
                <td>{{ $student->full_name }}</td>
                <td>{{ ucfirst($student->gender ?? '—') }}</td>
                <td>
                    <a href="{{ route('students.show', $student) }}" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">View Profile</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Record Marks Form -->
<div class="grid-2">
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom:16px; color:#374151;">📝 Record Marks</h3>
        <form method="POST" action="{{ route('teacher.marks.store') }}">
            @csrf
            <div class="form-group">
                <label>Student</label>
                <select name="student_id" required>
                    <option value="">Select student...</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <div class="form-group">
                <label>Subject</label>
                <select name="subject_id" required>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="{{ date('Y') . '/' . (date('Y')+1) }}" required>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select name="term" required>
                        <option value="term1">Term 1</option>
                        <option value="term2">Term 2</option>
                        <option value="term3">Term 3</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Assessment Type</label>
                <select name="assessment_type" required>
                    <option value="weekly_test">Weekly Test</option>
                    <option value="monthly_test">Monthly Test</option>
                    <option value="assignment">Assignment</option>
                    <option value="exam">Exam</option>
                    <option value="project">Project</option>
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Marks Obtained</label>
                    <input type="number" name="marks" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Total Marks</label>
                    <input type="number" name="total_marks" step="0.01" min="1" value="100" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">Record Marks</button>
        </form>
    </div>

    <!-- Mark Attendance -->
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom:16px; color:#374151;">📅 Mark Attendance</h3>
        <form method="POST" action="{{ route('teacher.attendance.store') }}">
            @csrf
            <div class="form-group">
                <label>Student</label>
                <select name="student_id" required>
                    <option value="">Select student...</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="excused">Excused</option>
                    <option value="sick">Sick</option>
                    <option value="early_departure">Early Departure</option>
                </select>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="remarks">
            </div>
            <button type="submit" class="btn-primary">Mark Attendance</button>
        </form>
    </div>
</div>
@endsection
