@php $reportTitle = 'Outstanding Balance by Class'; $reportMeta = count($rows).' class(es)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead>
        <tr>
            <th>#</th><th>Form</th><th>Class</th><th class="text-right">Students</th>
            <th class="text-right">Billed</th><th class="text-right">Paid</th><th class="text-right">Balance</th>
        </tr>
    </thead>
    <tbody>
    @php $n = 0; $totStudents = 0; $totBilled = 0; $totPaid = 0; $totBalance = 0; @endphp
    @foreach($rows as $r)
        @php $n++; $totStudents += $r->student_count; $totBilled += $r->total_billed; $totPaid += $r->total_paid; $totBalance += $r->total_balance; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->form_name ?? 'Unassigned' }}</td>
            <td>{{ $r->stream_name ?? '-' }}</td>
            <td class="text-right">{{ $r->student_count }}</td>
            <td class="text-right">${{ number_format($r->total_billed, 2) }}</td>
            <td class="text-right">${{ number_format($r->total_paid, 2) }}</td>
            <td class="text-right">${{ number_format($r->total_balance, 2) }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No billing data found.</td></tr>
    @endif
    <tr class="total-row">
        <td colspan="3">Total</td>
        <td class="text-right">{{ $totStudents }}</td>
        <td class="text-right">${{ number_format($totBilled, 2) }}</td>
        <td class="text-right">${{ number_format($totPaid, 2) }}</td>
        <td class="text-right">${{ number_format($totBalance, 2) }}</td>
    </tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures')
