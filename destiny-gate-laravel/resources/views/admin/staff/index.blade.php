@extends('layouts.app')
@section('title', 'Staff Management')
@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
    <div>
        <h1>Staff Management</h1>
        <p>Manage teachers, bursars, and admin staff</p>
    </div>
    <a href="{{ route('admin.staff.create') }}" class="btn-primary">➕ Add Staff</a>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr><th>Staff ID</th><th>Name</th><th>Email</th><th>Department</th><th>Position</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($staff as $member)
            <tr>
                <td><code>{{ $member->staff_id }}</code></td>
                <td>{{ $member->full_name }}</td>
                <td>{{ $member->email }}</td>
                <td>{{ $member->department ?? '—' }}</td>
                <td>{{ $member->position ?? '—' }}</td>
                <td>{{ $member->is_active ? '✅ Active' : '❌ Inactive' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $staff->links() }}</div>
</div>
@endsection
