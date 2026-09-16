@php
    $reportTitle = 'Class List';
    $reportMeta = $scopeLabel . ' · ' . ucfirst($statusLabel) . ' · ' . count($students) . ' student(s)';
    $confidentialLabel = 'Internal Academic Record';
@endphp
@include('reports.shared._header')

<table class="report-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Student #</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Form / Class</th>
            <th>Guardian</th>
            <th>Guardian Phone</th>
        </tr>
    </thead>
    <tbody>
    @php $n = 0; $lastGroup = null; @endphp
    @foreach($students as $s)
        @php $group = trim(($s->resolved_form_name ?? 'Unassigned') . ' ' . ($s->resolved_stream_name ?? '')); @endphp
        @if($group !== $lastGroup)
            <tr><td colspan="7" style="background:#f9fafb; font-weight:bold; color:#0f3d22; padding-top:10px;">{{ $group }}</td></tr>
            @php $lastGroup = $group; @endphp
        @endif
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $s->student_number ?? $s->admission_number }}</td>
            <td>{{ trim($s->first_name.' '.$s->last_name) }}</td>
            <td style="text-transform:capitalize">{{ $s->gender ?? '-' }}</td>
            <td>{{ $group }}</td>
            <td>{{ $s->guardian_name ?? '-' }}</td>
            <td>{{ $s->guardian_phone ?? '-' }}</td>
        </tr>
    @endforeach
    @if(count($students) === 0)
        <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No students match this filter.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Class Teacher / Admin)', 'approvedLabel' => 'Verified By (Headmaster)'])
