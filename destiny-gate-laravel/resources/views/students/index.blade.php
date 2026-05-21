@extends('layouts.app')
@section('title', 'Students')
@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
    <div><h1>Students</h1><p>All enrolled students</p></div>
    <a href="{{ route('students.create') }}" class="btn-primary">➕ Enroll Student</a>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr><th>Admission #</th><th>Name</th><th>Class</th><th>Gender</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td><code>{{ $student->admission_number }}</code></td>
                <td>{{ $student->full_name }}</td>
                <td>{{ $student->schoolClass?->display_name ?? '—' }}</td>
                <td>{{ ucfirst($student->gender ?? '—') }}</td>
                <td>
                    <span style="padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600;
                        background: {{ $student->status === 'active' ? '#d1fae5' : '#fee2e2' }};
                        color: {{ $student->status === 'active' ? '#065f46' : '#991b1b' }};">
                        {{ ucfirst($student->status) }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('students.show', $student) }}" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">View</a>
                    <a href="{{ route('students.edit', $student) }}" style="padding:4px 10px; font-size:0.8rem; color:#6b7280; text-decoration:none;">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $students->links() }}</div>
</div>
@endsection
