@php $reportTitle = 'Daily Payment / Receipt Register'; $reportMeta = 'Date: '.$date; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>Receipt #</th><th>Student</th><th>Method</th><th>Received By</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
    @foreach($payments as $p)
        <tr>
            <td>{{ $p->receipt_number }}</td>
            <td>{{ $p->student_name }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
            <td>{{ $p->received_by_name }}</td>
            <td class="text-right">${{ number_format($p->amount, 2) }}</td>
        </tr>
    @endforeach
    @if(count($payments) === 0)
        <tr><td colspan="5" style="text-align:center; color:#9ca3af;">No payments recorded on this date.</td></tr>
    @endif
    <tr class="total-row"><td colspan="4">Total Collected</td><td class="text-right">${{ number_format($total, 2) }}</td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Cashier)', 'approvedLabel' => 'Verified By (Bursar)'])
