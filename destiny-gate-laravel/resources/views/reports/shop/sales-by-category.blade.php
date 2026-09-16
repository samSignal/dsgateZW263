@php $reportTitle = 'Shop Sales by Category'; $reportMeta = count($rows).' category(ies)'; @endphp
@include('reports.shared._header')

<table class="report-table">
    <thead><tr><th>#</th><th>Category</th><th class="text-right">Qty Sold</th><th class="text-right">Total Sales</th></tr></thead>
    <tbody>
    @php $n = 0; $totQty = 0; $totSales = 0; @endphp
    @foreach($rows as $r)
        @php $n++; $totQty += $r->quantity_sold; $totSales += $r->total_sales; @endphp
        <tr>
            <td>{{ $n }}</td>
            <td>{{ $r->name }}</td>
            <td class="text-right">{{ $r->quantity_sold }}</td>
            <td class="text-right">${{ number_format($r->total_sales, 2) }}</td>
        </tr>
    @endforeach
    @if(count($rows) === 0)
        <tr><td colspan="4" style="text-align:center; color:#9ca3af;">No sales found.</td></tr>
    @endif
    <tr class="total-row"><td colspan="2">Total</td><td class="text-right">{{ $totQty }}</td><td class="text-right">${{ number_format($totSales, 2) }}</td></tr>
    </tbody>
</table>

@include('reports.shared._stamp')
@include('reports.shared._signatures', ['preparedLabel' => 'Prepared By (Storekeeper)', 'approvedLabel' => 'Verified By (Bursar)'])
