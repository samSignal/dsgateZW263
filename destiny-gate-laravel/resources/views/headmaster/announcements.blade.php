@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
<div class="page-header"><h1>Announcements</h1></div>
<div class="table-card">
    <table>
        <thead><tr><th>Title</th><th>Audience</th><th>Created By</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($announcements as $ann)
            <tr>
                <td><strong>{{ $ann->title }}</strong><br><small style="color:#6b7280;">{{ Str::limit($ann->content, 80) }}</small></td>
                <td>{{ ucfirst($ann->audience) }}</td>
                <td>{{ $ann->createdBy->name }}</td>
                <td>{{ $ann->created_at->format('d M Y') }}</td>
                <td>{{ $ann->is_active ? '✅ Active' : '❌ Inactive' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $announcements->links() }}</div>
</div>
@endsection
