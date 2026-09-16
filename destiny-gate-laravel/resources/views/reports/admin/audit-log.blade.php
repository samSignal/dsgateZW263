@php $reportTitle = 'System Audit Log'; $reportMeta = count($rows).' entr'.(count($rows) === 1 ? 'y' : 'ies'); $confidentialLabel = 'Restricted — Admin Only'; @endphp
@include('reports.shared._header')
<style>@page { margin: 20px 20px 40px; size: A4 landscape; }</style>

<table class="report-table">
    <thead>
        <tr>
            <th>#</th><th>Date/Time</th><th>User</th><th>Role</th><th>Action</th>
            <th>Description</th><th>Method</th><th>Path</th><th>Status</th><th>IP Address</th>
        </tr>
    </thead>
    <tbody>
    @php $n = 0; @endphp
    @foreach($rows as $r)
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td style="white-space:nowrap;">{{ $r->created_at }}</td>
            <td>{{ $r->user_name ?? '-' }}</td>
            <td style="text-transform:capitalize">{{ $r->user_role ?? '-' }}</td>
            <td>{{ $r->action }}</td>
            <td>{{ $r->description }}</td>
            <td>{{ $r->method ?? '-' }}</td>
            <td style="font-size:9px;">{{ $r->path ?? '-' }}</td>
            <td>{{ $r->status_code ?? '-' }}</td>
            <td>{{ $r->ip_address ?? '-' }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="10" style="text-align:center; color:#9ca3af;">No matching audit entries.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Exported By (Admin)', 'approvedLabel' => 'Reviewed By'])
