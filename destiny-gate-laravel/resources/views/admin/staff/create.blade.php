@extends('layouts.app')
@section('title', 'Add Staff Member')
@section('content')
<div class="page-header">
    <h1>Add Staff Member</h1>
    <p>Create a new staff account</p>
</div>

<div style="max-width:600px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
    <form method="POST" action="{{ route('admin.staff.store') }}">
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
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}">
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" value="{{ old('department') }}">
            </div>
            <div class="form-group">
                <label>Position</label>
                <input type="text" name="position" value="{{ old('position') }}">
            </div>
        </div>
        <div class="form-group">
            <label>Role *</label>
            <select name="role" required>
                <option value="">Select role...</option>
                <option value="teacher">Teacher</option>
                <option value="bursar">Bursar</option>
                <option value="headmaster">Headmaster</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>Qualifications</label>
            <textarea name="qualifications" rows="3">{{ old('qualifications') }}</textarea>
        </div>
        <div class="form-group">
            <label>Employment Date</label>
            <input type="date" name="employment_date" value="{{ old('employment_date') }}">
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-primary">Create Staff Member</button>
            <a href="{{ route('admin.staff') }}" style="padding:8px 18px; color:#6b7280; text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
