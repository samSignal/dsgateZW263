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
            <th>Form</th>
            <th>Class</th>
            <th>Guardian</th>
            <th>Guardian Phone</th>
        </tr>
    </thead>
    <tbody>
    @foreach($students as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $s->student_number ?? $s->admission_number }}</td>
            <td>{{ trim($s->first_name.' '.$s->last_name) }}</td>
            <td style="text-transform:capitalize">{{ $s->gender ?? '-' }}</td>
            <td>{{ $s->resolved_form_name ?? '-' }}</td>
            <td>{{ $s->resolved_stream_name ?? '-' }}</td>
            <td>{{ $s->guardian_name ?? '-' }}</td>
            <td>{{ $s->guardian_phone ?? '-' }}</td>
        </tr>
    @endforeach
    @if(count($students) === 0)
        <tr><td colspan="8" style="text-align:center; color:#9ca3af;">No students match this filter.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Class Teacher / Admin)', 'approvedLabel' => 'Verified By (Headmaster)'])
