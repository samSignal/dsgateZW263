@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>System overview and management</p>
</div>

<div class="grid-4">
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Total Users</div>
        <div class="value">{{ $stats['total_users'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Active Students</div>
        <div class="value">{{ $stats['total_students'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Active Staff</div>
        <div class="value">{{ $stats['total_staff'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Pending Applications</div>
        <div class="value" style="color:#d97706;">{{ $stats['pending_apps'] }}</div>
    </div>
</div>

<div class="grid-2">
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">Recent Users</div>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
            <tbody>
                @foreach($stats['recent_users'] as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge-role badge-{{ $user->role }}">{{ $user->role }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="stat-card">
        <h3 style="margin-bottom:16px; color:#374151;">Quick Actions</h3>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="{{ route('admin.staff.create') }}" class="btn-primary">➕ Add Staff Member</a>
            <a href="{{ route('admin.classes.create') }}" class="btn-primary">➕ Create Class</a>
            <a href="{{ route('students.create') }}" class="btn-primary">➕ Enroll Student</a>
            <a href="{{ route('admin.applications') }}" class="btn-gold">📋 Review Applications</a>
            <a href="{{ route('admin.users') }}" class="btn-primary">👥 Manage Users</a>
        </div>
    </div>
</div>
@endsection
