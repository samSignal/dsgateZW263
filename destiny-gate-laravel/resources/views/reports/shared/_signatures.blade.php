@php
    $preparedLabel = $preparedLabel ?? 'Prepared By (Bursar / Cashier)';
    $approvedLabel = $approvedLabel ?? 'Approved By (Headmaster / Admin)';
@endphp
<div class="sign-block">
    <div class="sign-cell">
        <div class="sign-line">{{ $preparedLabel }} — Signature &amp; Date</div>
    </div>
    <div class="sign-cell">
        <div class="sign-line">{{ $approvedLabel }} — Signature &amp; Date</div>
    </div>
</div>
