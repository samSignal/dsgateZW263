@extends('layouts.app')
@section('title', 'Create Class')
@section('content')
<div class="page-header">
    <h1>Create Class</h1>
</div>
<div style="max-width:500px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
    <form method="POST" action="{{ route('admin.classes.store') }}">
        @csrf
        <div class="form-group">
            <label>Class Name *</label>
            <input type="text" name="class_name" value="{{ old('class_name') }}" placeholder="e.g. Form 1" required>
        </div>
        <div class="form-group">
            <label>Stream</label>
            <input type="text" name="stream" value="{{ old('stream') }}" placeholder="e.g. A, B, Science">
        </div>
        <div class="form-group">
            <label>Academic Year *</label>
            <input type="text" name="academic_year" value="{{ old('academic_year', date('Y') . '/' . (date('Y')+1)) }}" required>
        </div>
        <div class="form-group">
            <label>Class Teacher</label>
            <select name="class_teacher_id">
                <option value="">None</option>
                @foreach($staff as $member)
                    <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Capacity</label>
            <input type="number" name="capacity" value="{{ old('capacity') }}" min="1">
        </div>
        <button type="submit" class="btn-primary">Create Class</button>
    </form>
</div>
@endsection
