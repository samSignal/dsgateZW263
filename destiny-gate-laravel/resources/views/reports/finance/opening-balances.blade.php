@php $reportTitle = 'Fee Migration Verification Statement'; @endphp
@include('reports.shared._header')
<style>
    /* This table has 12 columns (adds a Computed Balance cross-check column to the usual
       set) — too wide for the shared portrait layout, so it overrides to landscape here. */
    @page { margin: 20px 20px 40px; size: A4 landscape; }
</style>

<p style="font-size: 9.5px; color: #6b7280; margin: -8px 0 4px;">
    Every student currently on record, grouped by form/stream, with the balance carried forward from before this
    system, this term's fees billed, amounts already paid, and the resulting balance. Please cross-check each row
    against your own class list / paper records and note any discrepancy below the relevant row.
</p>
<p style="font-size: 9.5px; color: #0f3d22; font-weight: bold; margin: 0 0 12px;">
    Computed Balance = Opening Balance + Term Fees Billed - Paid — shown alongside the system's recorded Balance
    as a cross-check; a row shaded red means the two don't match and needs to be looked at.
</p>

<table class="report-table">
    <thead>
        <tr>
            <th>#</th><th>Student</th><th>Student #</th><th>Form / Stream</th><th>Guardian</th><th>Phone</th>
            <th class="text-right">Opening Balance</th><th class="text-right">Term Fees Billed</th>
            <th class="text-right">Paid</th><th>Calculation</th>
            <th class="text-right">Computed Balance</th><th class="text-right">Balance</th>
        </tr>
    </thead>
    <tbody>
    @php $n = 0; $totOpen = 0; $totBilled = 0; $totPaid = 0; $totComputed = 0; $totBalance = 0; $lastGroup = null; $mismatchCount = 0; @endphp
    @foreach($rows as $r)
        @php $group = trim(($r->form_name ?? '-') . ' ' . ($r->stream_name ?? '')); @endphp
        @if($group !== $lastGroup)
            <tr><td colspan="12" style="background:#f9fafb; font-weight:bold; color:#0f3d22; padding-top:10px;">{{ $group }}</td></tr>
            @php $lastGroup = $group; @endphp
        @endif
        @php
            $n++; $totOpen += $r->opening_balance; $totBilled += $r->term_billed; $totPaid += $r->paid;
            $totComputed += $r->computed_balance; $totBalance += $r->balance;
            if ($r->balance_mismatch) $mismatchCount++;
        @endphp
        <tr @if($r->balance_mismatch) style="background:#fef2f2;" @endif>
            <td>{{ $n }}</td>
            <td>{{ $r->student_name }}</td>
            <td>{{ $r->student_number }}</td>
            <td>{{ $group }}</td>
            <td>{{ $r->guardian_name ?? '-' }}</td>
            <td>{{ $r->guardian_phone ?? '-' }}</td>
            <td class="text-right">${{ number_format($r->opening_balance, 2) }}</td>
            <td class="text-right">${{ number_format($r->term_billed, 2) }}</td>
            <td class="text-right">${{ number_format($r->paid, 2) }}</td>
            <td style="font-size:9px; color:#6b7280; white-space:nowrap;">
                {{ number_format($r->opening_balance, 2) }} + {{ number_format($r->term_billed, 2) }} - {{ number_format($r->paid, 2) }}
            </td>
            <td class="text-right" style="{{ $r->balance_mismatch ? 'color:#dc2626; font-weight:bold;' : '' }}">${{ number_format($r->computed_balance, 2) }}</td>
            <td class="text-right" style="{{ $r->balance_mismatch ? 'color:#dc2626; font-weight:bold;' : '' }}">${{ number_format($r->balance, 2) }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="12" style="text-align:center; color:#9ca3af;">No students found.</td></tr>
    @endif
    <tr class="total-row">
        <td colspan="6">Total{{ $mismatchCount > 0 ? " — {$mismatchCount} row(s) with a mismatch, shaded above" : '' }}</td>
        <td class="text-right">${{ number_format($totOpen, 2) }}</td>
        <td class="text-right">${{ number_format($totBilled, 2) }}</td>
        <td class="text-right">${{ number_format($totPaid, 2) }}</td>
        <td style="font-size:9px; color:#6b7280; white-space:nowrap;">
            {{ number_format($totOpen, 2) }} + {{ number_format($totBilled, 2) }} - {{ number_format($totPaid, 2) }}
        </td>
        <td class="text-right">${{ number_format($totComputed, 2) }}</td>
        <td class="text-right">${{ number_format($totBalance, 2) }}</td>
    </tr>
    </tbody>
</table>

@include('reports.shared._stamp')

<div class="sign-block">
    <div class="sign-cell" style="width:33%;"><div class="sign-line">Verified By (Clerk)</div></div>
    <div class="sign-cell" style="width:33%;"><div class="sign-line">Prepared By (Bursar)</div></div>
    <div class="sign-cell" style="width:33%;"><div class="sign-line">Approved By (Headmaster)</div></div>
</div>
