@php $reportTitle = 'Shop Unpaid Purchases'; $reportMeta = count($rows).' purchase(s)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Student</th><th>Student #</th><th>Purchase #</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
    <tbody>
    @php $n = 0; $totBalance = 0; @endphp
    @foreach($rows as $r)
        @php $n++; $totBalance += $r->balance; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->student_name }}</td>
            <td>{{ $r->student_number ?: $r->admission_number }}</td>
            <td>{{ $r->purchase_number }}</td>
            <td class="text-right">${{ number_format($r->total_amount, 2) }}</td>
            <td class="text-right">${{ number_format($r->amount_paid, 2) }}</td>
            <td class="text-right">${{ number_format($r->balance, 2) }}</td>
            <td style="text-transform:capitalize">{{ $r->status }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="8" style="text-align:center; color:#9ca3af;">No unpaid purchases.</td></tr>
    @endif
    <tr class="total-row"><td colspan="6">Total Outstanding</td><td class="text-right">${{ number_format($totBalance, 2) }}</td><td></td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Storekeeper)', 'approvedLabel' => 'Verified By (Bursar)'])
