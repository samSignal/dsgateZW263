@extends('layouts.app')
@section('title', 'My Classes')
@section('content')
<div class="page-header">
    <h1>My Classes</h1>
    <p>{{ $staff->full_name }} — Subject assignments</p>
</div>

@foreach($assignments as $assignment)
<div class="table-card" style="margin-bottom:16px;">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <strong>{{ $assignment->schoolClass->display_name }}</strong>
            <span style="color:#6b7280; margin-left:8px;">{{ $assignment->subject->subject_name }}</span>
        </div>
        <a href="{{ route('teacher.class.students', $assignment->schoolClass) }}" class="btn-primary" style="padding:6px 14px; font-size:0.85rem;">View Students</a>
    </div>
    <div style="padding:12px 20px;">
        <span style="font-size:0.85rem; color:#6b7280;">Academic Year: {{ $assignment->academic_year }}</span>
        &nbsp;|&nbsp;
        <span style="font-size:0.85rem; color:#6b7280;">Students: {{ $assignment->schoolClass->students->count() }}</span>
    </div>
</div>
@endforeach
@endsection
