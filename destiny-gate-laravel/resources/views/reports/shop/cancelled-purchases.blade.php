@php $reportTitle = 'Shop Cancelled Purchases'; $reportMeta = count($rows).' purchase(s)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Date</th><th>Purchase #</th><th>Student</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @php $n = 0; @endphp
    @foreach($rows as $r)
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->purchase_date }}</td>
            <td>{{ $r->purchase_number }}</td>
            <td>{{ $r->student_name }}</td>
            <td class="text-right">${{ number_format($r->total_amount, 2) }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="5" style="text-align:center; color:#9ca3af;">No cancelled purchases.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Storekeeper)', 'approvedLabel' => 'Verified By (Bursar)'])
