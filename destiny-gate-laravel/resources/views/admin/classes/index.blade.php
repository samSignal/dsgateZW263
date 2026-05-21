@extends('layouts.app')
@section('title', 'Classes')
@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
    <div><h1>Classes</h1><p>Manage school classes and streams</p></div>
    <a href="{{ route('admin.classes.create') }}" class="btn-primary">➕ Create Class</a>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr><th>Class Name</th><th>Stream</th><th>Academic Year</th><th>Class Teacher</th><th>Capacity</th><th>Students</th></tr>
        </thead>
        <tbody>
            @foreach($classes as $class)
            <tr>
                <td><strong>{{ $class->class_name }}</strong></td>
                <td>{{ $class->stream ?? '—' }}</td>
                <td>{{ $class->academic_year }}</td>
                <td>{{ $class->classTeacher?->full_name ?? '—' }}</td>
                <td>{{ $class->capacity ?? '—' }}</td>
                <td>{{ $class->students_count ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $classes->links() }}</div>
</div>
@endsection
