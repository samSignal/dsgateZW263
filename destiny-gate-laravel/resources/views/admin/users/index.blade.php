@extends('layouts.app')
@section('title', 'User Management')
@section('content')
<div class="page-header">
    <h1>User Management</h1>
    <p>Manage user roles and access</p>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td><span class="badge-role badge-{{ $user->role }}">{{ $user->role }}</span></td>
                <td>{{ $user->is_active ? '✅ Active' : '❌ Inactive' }}</td>
                <td>{{ $user->created_at->format('d M Y') }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.users.role', $user) }}" style="display:inline-flex; gap:6px;">
                        @csrf @method('PATCH')
                        <select name="role" style="padding:4px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:0.8rem;">
                            @foreach(['admin','headmaster','teacher','bursar','parent','student','user'] as $role)
                                <option value="{{ $role }}" {{ $user->role === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">Update</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $users->links() }}</div>
</div>
@endsection
