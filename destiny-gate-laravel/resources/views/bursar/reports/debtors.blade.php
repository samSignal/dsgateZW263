@extends('layouts.app')
@section('title', 'Debtors List')
@section('content')
<div class="page-header"><h1>Debtors List</h1><p>Students with outstanding fee balances</p></div>
<div class="table-card">
    <table>
        <thead><tr><th>Student</th><th>Class</th><th>Term</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($debtors as $studentId => $fees)
            @php $student = $fees->first()->student; @endphp
            @foreach($fees as $fee)
            <tr>
                @if($loop->first)
                <td rowspan="{{ $fees->count() }}">{{ $student->full_name }}</td>
                <td rowspan="{{ $fees->count() }}">{{ $student->schoolClass?->display_name ?? '—' }}</td>
                @endif
                <td>{{ strtoupper($fee->term) }}</td>
                <td>${{ number_format($fee->amount, 2) }}</td>
                <td>${{ number_format($fee->amount_paid, 2) }}</td>
                <td style="color:#dc2626; font-weight:600;">${{ number_format($fee->balance, 2) }}</td>
                <td>{{ ucfirst($fee->status) }}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
</div>
@endsection
