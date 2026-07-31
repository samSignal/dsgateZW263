@extends('layouts.app')
@section('title', 'Applications')
@section('content')
<div class="page-header">
    <h1>Admission Applications</h1>
    <p>Review and process student applications</p>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr><th>App #</th><th>Applicant</th><th>Class</th><th>Year</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($applications as $app)
            <tr>
                <td><code>{{ $app->application_number }}</code></td>
                <td>{{ $app->full_name }}</td>
                <td>{{ $app->intended_class }}</td>
                <td>{{ $app->academic_year }}</td>
                <td>
                    <span style="padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600;
                        background: {{ $app->status === 'pending' || $app->status === 'waiting_list' ? '#fef3c7' : ($app->status === 'approved' || $app->status === 'offered' || $app->status === 'enrolled' ? '#d1fae5' : '#fee2e2') }};
                        color: {{ $app->status === 'pending' || $app->status === 'waiting_list' ? '#d97706' : ($app->status === 'approved' || $app->status === 'offered' || $app->status === 'enrolled' ? '#065f46' : '#991b1b') }};">
                        {{ ucfirst($app->status) }}
                    </span>
                </td>
                <td>{{ $app->created_at->format('d M Y') }}</td>
                <td>
                    @if($app->status === 'pending')
                    <form method="POST" action="{{ route('admin.applications.approve', $app) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">✅ Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.applications.reject', $app) }}" style="display:inline;" onsubmit="return confirm('Enter rejection reason:')">
                        @csrf
                        <input type="hidden" name="rejection_reason" value="Does not meet requirements">
                        <button type="submit" class="btn-danger" style="padding:4px 10px; font-size:0.8rem;">❌ Reject</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $applications->links() }}</div>
</div>
@endsection
