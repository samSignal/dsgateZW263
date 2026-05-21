@extends('layouts.app')
@section('title', 'Student Fees')
@section('content')
<div class="page-header"><h1>Student Fees</h1></div>
<div class="table-card">
    <table>
        <thead>
            <tr><th>Student</th><th>Class</th><th>Year</th><th>Term</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($fees as $fee)
            <tr>
                <td>{{ $fee->student->full_name }}</td>
                <td>{{ $fee->student->schoolClass?->display_name ?? '—' }}</td>
                <td>{{ $fee->academic_year }}</td>
                <td>{{ strtoupper($fee->term) }}</td>
                <td>${{ number_format($fee->amount, 2) }}</td>
                <td>${{ number_format($fee->amount_paid, 2) }}</td>
                <td style="color: {{ $fee->balance > 0 ? '#dc2626' : '#059669' }};">${{ number_format($fee->balance, 2) }}</td>
                <td>
                    <span style="padding:3px 8px; border-radius:20px; font-size:0.75rem; font-weight:600;
                        background: {{ $fee->status === 'paid' ? '#d1fae5' : ($fee->status === 'overdue' ? '#fee2e2' : '#fef3c7') }};
                        color: {{ $fee->status === 'paid' ? '#065f46' : ($fee->status === 'overdue' ? '#991b1b' : '#d97706') }};">
                        {{ ucfirst($fee->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $fees->links() }}</div>
</div>
@endsection
