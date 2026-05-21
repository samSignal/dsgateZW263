@extends('layouts.app')
@section('title', 'Paid Students')
@section('content')
<div class="page-header"><h1>Paid Students</h1></div>
<div class="table-card">
    <table>
        <thead><tr><th>Student</th><th>Class</th><th>Year</th><th>Term</th><th>Amount</th></tr></thead>
        <tbody>
            @foreach($paid as $fee)
            <tr>
                <td>{{ $fee->student->full_name }}</td>
                <td>{{ $fee->student->schoolClass?->display_name ?? '—' }}</td>
                <td>{{ $fee->academic_year }}</td>
                <td>{{ strtoupper($fee->term) }}</td>
                <td style="color:#059669; font-weight:600;">${{ number_format($fee->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $paid->links() }}</div>
</div>
@endsection
