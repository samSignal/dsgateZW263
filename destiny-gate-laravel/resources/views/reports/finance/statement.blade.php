@php
    $reportTitle = 'Individual Learner Fee Statement';
    $reportMeta = ($student->first_name ?? '').' '.($student->last_name ?? '').' · Adm# '.($student->admission_number ?? '-');
    $signedMoney = fn ($n) => ($n < 0 ? '-$' : '$') . number_format(abs($n), 2);
@endphp
@include('reports.shared._header')

@if($accountSummary)
<table class="report-table" style="margin-bottom: 16px;">
    <tr><td style="width:60%">Opening Balance (b/f)</td><td class="text-right">{{ $signedMoney($accountSummary['opening_balance']) }}</td></tr>
    <tr><td>Current Term Charges</td><td class="text-right">${{ number_format($accountSummary['current_term_charges'], 2) }}</td></tr>
    <tr><td>Payments This Term</td><td class="text-right">-${{ number_format($accountSummary['payments_this_term'], 2) }}</td></tr>
    <tr class="total-row"><td>{{ $accountSummary['outstanding_balance'] < 0 ? 'Credit Balance' : 'Outstanding Balance' }}</td><td class="text-right">{{ $signedMoney($accountSummary['outstanding_balance']) }}</td></tr>
</table>
@endif

<table class="report-table">
    <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
    @foreach($transactions as $t)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($t->created_at)->format('d M Y') }}</td>
            <td>{{ $t->description }}</td>
            <td>{{ ucfirst($t->transaction_type) }}</td>
            <td class="text-right">{{ $t->debit > 0 ? '$'.number_format($t->debit, 2) : '' }}</td>
            <td class="text-right">{{ $t->credit > 0 ? '$'.number_format($t->credit, 2) : '' }}</td>
            <td class="text-right">{{ $signedMoney($t->balance_after) }}</td>
        </tr>
    @endforeach
    @if(count($transactions) === 0)
        <tr><td colspan="6" style="text-align:center; color:#9ca3af;">No transactions found.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Finance Office)', 'approvedLabel' => 'Acknowledged By (Parent / Guardian)'])
