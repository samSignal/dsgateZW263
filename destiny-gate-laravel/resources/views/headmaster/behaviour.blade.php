@extends('layouts.app')
@section('title', 'Behaviour Cases')
@section('content')
<div class="page-header">
    <h1>Behaviour & Discipline Cases</h1>
</div>
<div class="table-card">
    <table>
        <thead>
            <tr><th>Student</th><th>Class</th><th>Issue</th><th>Severity</th><th>Date</th><th>Action</th><th>Review</th></tr>
        </thead>
        <tbody>
            @foreach($cases as $case)
            <tr>
                <td>{{ $case->student->full_name }}</td>
                <td>{{ $case->schoolClass->display_name }}</td>
                <td>{{ $case->issue_type }}</td>
                <td>
                    <span style="padding:3px 8px; border-radius:20px; font-size:0.75rem; font-weight:600;
                        background: {{ $case->severity === 'severe' ? '#fee2e2' : ($case->severity === 'moderate' ? '#fef3c7' : '#d1fae5') }};
                        color: {{ $case->severity === 'severe' ? '#991b1b' : ($case->severity === 'moderate' ? '#d97706' : '#065f46') }};">
                        {{ ucfirst($case->severity) }}
                    </span>
                </td>
                <td>{{ $case->issue_date->format('d M Y') }}</td>
                <td>{{ $case->action ?? '—' }}</td>
                <td>
                    @if(!$case->reviewed_at)
                    <form method="POST" action="{{ route('headmaster.behaviour.review', $case) }}" style="display:flex; gap:6px;">
                        @csrf
                        <input type="text" name="headmaster_review" placeholder="Review notes..." style="padding:4px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:0.8rem; width:150px;">
                        <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:0.8rem;">Save</button>
                    </form>
                    @else
                    <span style="font-size:0.8rem; color:#059669;">✅ Reviewed</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $cases->links() }}</div>
</div>
@endsection
