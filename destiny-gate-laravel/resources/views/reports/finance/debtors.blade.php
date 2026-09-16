@php $reportTitle = 'Fees Arrears Report'; $reportMeta = count($rows).' debtor(s)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Student</th><th>Student #</th><th>Form</th><th class="text-right">Billed</th><th class="text-right">Paid</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
    @php $n = 0; $total = 0; @endphp
    @foreach($rows as $r)
        @php $n++; $total += $r->total_balance; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->student_name }}</td>
            <td>{{ $r->student_number ?: $r->admission_number }}</td>
            <td>{{ $r->form_name ?? '-' }}</td>
            <td class="text-right">${{ number_format($r->total_billed, 2) }}</td>
            <td class="text-right">${{ number_format($r->total_paid, 2) }}</td>
            <td class="text-right">${{ number_format($r->total_balance, 2) }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No debtors found.</td></tr>
    @endif
    <tr class="total-row"><td colspan="6">Total Outstanding</td><td class="text-right">${{ number_format($total, 2) }}</td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures')
