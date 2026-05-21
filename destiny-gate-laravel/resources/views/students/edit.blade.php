@extends('layouts.app')
@section('title', 'Edit Student')
@section('content')
<div class="page-header">
    <h1>Edit Student: {{ $student->full_name }}</h1>
</div>
<div style="max-width:700px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
    <form method="POST" action="{{ route('students.update', $student) }}">
        @csrf @method('PUT')
        <div class="grid-2">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required>
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $student->email) }}">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select...</option>
                    @foreach(['male','female','other'] as $g)
                        <option value="{{ $g }}" {{ $student->gender === $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id">
                    <option value="">Unassigned</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ $student->class_id == $class->id ? 'selected' : '' }}>{{ $class->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Status *</label>
            <select name="status" required>
                @foreach(['active','inactive','transferred','graduated','suspended'] as $s)
                    <option value="{{ $s }}" {{ $student->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ route('students.show', $student) }}" style="padding:8px 18px; color:#6b7280; text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
