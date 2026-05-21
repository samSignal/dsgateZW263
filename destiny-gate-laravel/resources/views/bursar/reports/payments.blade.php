@extends('layouts.app')
@section('title', 'Payment History')
@section('content')
<div class="page-header"><h1>Payment History</h1></div>
<div class="table-card">
    <table>
        <thead><tr><th>Receipt #</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Recorded By</th></tr></thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td><code>{{ $payment->receipt_number }}</code></td>
                <td>{{ $payment->student->full_name }}</td>
                <td>${{ number_format($payment->amount, 2) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}</td>
                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                <td>{{ $payment->recordedBy->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:16px;">{{ $payments->links() }}</div>
</div>
@endsection
