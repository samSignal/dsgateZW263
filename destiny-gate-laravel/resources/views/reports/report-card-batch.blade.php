@foreach($reports as $report)
    @include('reports.report-card', ['report' => $report])
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
