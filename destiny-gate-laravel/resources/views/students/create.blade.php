@extends('layouts.app')
@section('title', 'Enroll Student')
@section('content')
<div class="page-header">
    <h1>Enroll New Student</h1>
</div>
<div style="max-width:700px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
    <form method="POST" action="{{ route('students.store') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required>
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id">
                    <option value="">Select class...</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->display_name }} ({{ $class->academic_year }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Admission Date *</label>
            <input type="date" name="admission_date" value="{{ old('admission_date', date('Y-m-d')) }}" required>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label>Blood Type</label>
                <input type="text" name="blood_type" value="{{ old('blood_type') }}" placeholder="e.g. O+">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Allergies</label>
                <input type="text" name="allergies" value="{{ old('allergies') }}">
            </div>
        </div>
        <div class="form-group">
            <label>Medical Conditions</label>
            <textarea name="medical_conditions" rows="2">{{ old('medical_conditions') }}</textarea>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-primary">Enroll Student</button>
            <a href="{{ route('students.index') }}" style="padding:8px 18px; color:#6b7280; text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
