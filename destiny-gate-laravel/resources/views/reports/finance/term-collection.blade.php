@php $reportTitle = 'Fee Collection by Term / Academic Year'; $reportMeta = ($academicYearName ?? '').' · '.($termName ?? ''); @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Receipt #</th><th>Student</th><th>Method</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
    @php $n = 0; @endphp
    @foreach($payments as $p)
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $p->receipt_number }}</td>
            <td>{{ $p->student_name }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
            <td>{{ $p->payment_date }}</td>
            <td class="text-right">${{ number_format($p->amount, 2) }}</td>
        </tr>
    @endforeach
    @if(count($payments) === 0)
        <tr><td colspan="6" style="text-align:center; color:#9ca3af;">No payments found.</td></tr>
    @endif
    <tr class="total-row"><td colspan="5">Total Collected</td><td class="text-right">${{ number_format($total, 2) }}</td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures')
