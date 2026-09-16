@php $reportTitle = 'Shop Low Stock Items'; $reportMeta = count($rows).' item(s)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Item</th><th>Code</th><th>Category</th><th class="text-right">In Stock</th><th class="text-right">Reorder Level</th></tr></thead>
    <tbody>
    @php $n = 0; @endphp
    @foreach($rows as $r)
        @php $n++; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->item_name }}</td>
            <td>{{ $r->item_code }}</td>
            <td>{{ $r->category_name ?? '-' }}</td>
            <td class="text-right">{{ $r->quantity_in_stock }}</td>
            <td class="text-right">{{ $r->reorder_level }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="6" style="text-align:center; color:#9ca3af;">Stock levels look healthy.</td></tr>
    @endif
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Storekeeper)', 'approvedLabel' => 'Verified By (Bursar)'])
