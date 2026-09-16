@php $reportTitle = 'Shop Sales Register'; $reportMeta = "{$from} to {$to} · ".count($purchases).' purchase(s)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Date</th><th>Purchase #</th><th>Student</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @php $n = 0; @endphp
    @foreach($purchases as $p)
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $p->purchase_date }}</td>
            <td>{{ $p->purchase_number }}</td>
            <td>{{ $p->student_name }}</td>
            <td class="text-right">${{ number_format($p->total_amount, 2) }}</td>
        </tr>
    @endforeach
    @if(count($purchases) === 0)
        <tr><td colspan="5" style="text-align:center; color:#9ca3af;">No purchases found.</td></tr>
    @endif
    <tr class="total-row"><td colspan="4">Total Sales</td><td class="text-right">${{ number_format($total, 2) }}</td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Storekeeper)', 'approvedLabel' => 'Verified By (Bursar)'])
