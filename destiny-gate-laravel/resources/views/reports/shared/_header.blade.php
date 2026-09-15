<style>
    * { box-sizing: border-box; }
    @page { margin: 24px 24px 46px; }
    body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #111827; margin: 0; padding: 0; }
    .report-header { display: table; width: 100%; margin-bottom: 18px; border-bottom: 2px solid #1a6b3c; padding-bottom: 12px; }
    .report-header .logo-cell { display: table-cell; width: 60px; vertical-align: middle; }
    .report-header .logo-cell img { width: 48px; height: 48px; }
    .report-header .name-cell { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .report-header .name-cell .name { font-size: 16px; font-weight: bold; color: #0f3d22; }
    .report-header .name-cell .motto { font-size: 9px; color: #6b7280; font-style: italic; }
    .report-header .name-cell .contact { font-size: 8.5px; color: #6b7280; margin-top: 2px; }
    .report-title-row { display: table; width: 100%; margin: 14px 0 4px; }
    .report-title { display: table-cell; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
    .report-confidential { display: table-cell; text-align: right; vertical-align: bottom; font-size: 8px; color: #b45309; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
    .report-meta { font-size: 10px; color: #6b7280; margin-bottom: 14px; }
    table.report-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.report-table th { background: #f0faf4; color: #0f3d22; text-align: left; padding: 6px 8px; font-size: 9.5px; text-transform: uppercase; border-bottom: 1px solid #c6f0d8; }
    table.report-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; }
    table.report-table thead { display: table-header-group; } /* repeats the header row on every page */
    .text-right { text-align: right; }
    .total-row td { font-weight: bold; border-top: 2px solid #1a6b3c; }

    /* Slim running footer — repeats on every page via dompdf's fixed-position support,
       with a native CSS page counter (safe: no <script type="text/php"> eval, which
       dompdf disables by default for good reason). */
    .pdf-footer { position: fixed; bottom: -34px; left: 0; right: 0; height: 30px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
    .pdf-footer .f-left { display: inline-block; width: 60%; }
    .pdf-footer .pagenum { display: inline-block; width: 38%; text-align: right; }
    /* counter(pages) — the total page count — is unreliable in dompdf (resolves to 0
       here regardless of actual page count), so this shows the running page number
       only rather than a broken "Page X of 0". */
    .pagenum:before { content: "Page " counter(page); }

    .sign-block { display: table; width: 100%; margin-top: 46px; }
    .sign-cell { display: table-cell; width: 50%; padding-right: 20px; }
    .sign-line { border-top: 1px solid #111827; margin-top: 34px; padding-top: 4px; font-size: 9px; color: #6b7280; }
</style>
<div class="report-header">
    <div class="logo-cell"><img src="{{ public_path('logo-mark.png') }}"></div>
    <div class="name-cell">
        <div class="name">{{ strtoupper(config('school.name')) }}</div>
        <div class="motto">Raising a Godly, Skilled and Confident Generation</div>
        <div class="contact">{{ config('school.address') }} · {{ config('school.phone') }} · {{ config('school.email') }}</div>
    </div>
</div>
<div class="report-title-row">
    <div class="report-title">{{ $reportTitle }}</div>
    <div class="report-confidential">Confidential — {{ $confidentialLabel ?? 'Internal Financial Record' }}</div>
</div>
<div class="report-meta">{{ $reportMeta ?? '' }} · Generated {{ $generated_at }}</div>

<div class="pdf-footer">
    <span class="f-left">{{ config('school.name') }} · {{ $reportTitle }}</span>
    <span class="pagenum"></span>
</div>
