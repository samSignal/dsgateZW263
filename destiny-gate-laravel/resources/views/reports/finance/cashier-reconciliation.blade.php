@php $reportTitle = 'Cashier Reconciliation'; $reportMeta = $dateFrom === $dateTo ? "Date: {$dateFrom}" : "{$dateFrom} to {$dateTo}"; @endphp
@include('reports.shared._header')

<p style="font-size:9.5px; color:#6b7280; margin: 0 0 14px;">
    "System Recorded" is the total this software has on file per payment method. Each cashier counts their actual
    float/till and writes the counted amount and any variance by hand below before signing.
</p>

@foreach($cashiers as $c)
<table class="report-table" style="margin-bottom: 8px;">
    <thead>
        <tr><th colspan="4">{{ $c->cashier_name }} · {{ $c->payment_count }} payment(s)</th></tr>
        <tr><th>Method</th><th class="text-right">System Recorded</th><th class="text-right">Counted (Actual)</th><th class="text-right">Variance</th></tr>
    </thead>
    <tbody>
    @foreach($c->by_method as $method => $amount)
        <tr>
            <td style="width:34%">{{ ucfirst(str_replace('_', ' ', $method)) }}</td>
            <td class="text-right">${{ number_format($amount, 2) }}</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
        </tr>
    @endforeach
    <tr class="total-row">
        <td>Subtotal</td>
        <td class="text-right">${{ number_format($c->total_amount, 2) }}</td>
        <td class="text-right">&nbsp;</td>
        <td class="text-right">&nbsp;</td>
    </tr>
    </tbody>
</table>
<div class="sign-block" style="margin-top: 8px; margin-bottom: 22px;">
    <div class="sign-cell"><div class="sign-line">{{ $c->cashier_name }} — Signature &amp; Date</div></div>
    <div class="sign-cell"><div class="sign-line">Witnessed By (Bursar) — Signature &amp; Date</div></div>
</div>
@endforeach

@if(count($cashiers) === 0)
    <p style="color:#9ca3af;">No payments recorded in this date range.</p>
@endif

<table class="report-table">
    <thead><tr><th>Grand Total (All Cashiers)</th><th class="text-right">System Recorded</th><th class="text-right">Counted (Actual)</th><th class="text-right">Variance</th></tr></thead>
    <tbody>
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">${{ number_format($grandTotal, 2) }}</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
        </tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Reconciled By (Bursar)', 'approvedLabel' => 'Approved By (Headmaster / Admin)'])
