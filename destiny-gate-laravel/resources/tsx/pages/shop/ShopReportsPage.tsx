import React, { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { Card, CardHeader, Input, PageHeader, Spinner, Table, Td, Btn } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;
const chartTooltipStyle = { fontSize: 12, borderRadius: 8, border: '1px solid #e8eaed', boxShadow: '0 4px 12px rgba(0,0,0,.08)' };

function DownloadButtons({ endpoint, params, filename }: { endpoint: string; params: Record<string, any>; filename: string }) {
  const [busy, setBusy] = useState<'csv' | 'pdf' | null>(null);
  const go = async (format: 'csv' | 'pdf') => {
    setBusy(format);
    try { await downloadReport(endpoint, { ...params, format }, `${filename}.${format}`); } finally { setBusy(null); }
  };
  return (
    <div style={{ display: 'flex', gap: 8 }}>
      <Btn size="sm" variant="outline" loading={busy === 'pdf'} onClick={() => go('pdf')}>⬇ PDF</Btn>
      <Btn size="sm" variant="outline" loading={busy === 'csv'} onClick={() => go('csv')}>⬇ CSV</Btn>
    </div>
  );
}

export default function ShopReportsPage() {
  const [from, setFrom] = useState(new Date().toISOString().slice(0, 10));
  const [to, setTo] = useState(new Date().toISOString().slice(0, 10));
  const [categoryId, setCategoryId] = useState<string | null>(null);
  const rangeParams = { date_from: from, date_to: to };
  const itemParams = { ...rangeParams, category_id: categoryId ?? undefined };

  const { data: range, isLoading } = useQuery({ queryKey: ['shop-range', from, to], queryFn: () => api.get('/shop/reports/date-range', { params: rangeParams }).then(r => r.data) });
  const { data: byItem = [] } = useQuery({ queryKey: ['shop-by-item', from, to, categoryId], queryFn: () => api.get('/shop/reports/by-item', { params: itemParams }).then(r => r.data) });
  const { data: byCategory = [] } = useQuery({ queryKey: ['shop-by-category', from, to], queryFn: () => api.get('/shop/reports/by-category', { params: rangeParams }).then(r => r.data) });
  const { data: cancelled = [] } = useQuery({ queryKey: ['shop-cancelled'], queryFn: () => api.get('/shop/reports/cancelled').then(r => r.data) });

  const categoryChart = useMemo(() => (byCategory as any[]).map(c => ({
    name: c.name.length > 16 ? c.name.slice(0, 15) + '…' : c.name,
    fullName: c.name,
    categoryId: c.id,
    sales: Number(c.total_sales),
  })), [byCategory]);

  const activeCategoryName = categoryId ? (byCategory as any[]).find(c => String(c.id) === categoryId)?.name : null;

  const onCategoryBarClick = (data: any) => {
    if (data?.categoryId) setCategoryId(String(data.categoryId));
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Shop Reports" subtitle="Sales, unpaid balances, low stock, and cancelled purchase views" />
      <div className="mb-5 grid grid-cols-1 gap-3 md:grid-cols-3">
        <Input type="date" value={from} onChange={e => setFrom(e.target.value)} />
        <Input type="date" value={to} onChange={e => setTo(e.target.value)} />
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm"><span className="text-slate-600">Range total</span><br /><strong className="text-lg text-emerald-800">{money(range?.total)}</strong></div>
      </div>

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Sales by Category" action={<DownloadButtons endpoint="/shop/reports/by-category" params={rangeParams} filename="shop-sales-by-category" />} />
        <div style={{ padding: '4px 8px 14px' }}>
          {categoryChart.length === 0 ? (
            <div style={{ padding: 24, textAlign: 'center', color: '#9ca3af', fontSize: 13 }}>No sales in this range.</div>
          ) : (
            <>
              <div style={{ fontSize: 11, color: '#9ca3af', padding: '0 8px 6px' }}>Click a bar to filter "Sales by Item" below to that category.</div>
              <ResponsiveContainer width="100%" height={200}>
                <BarChart data={categoryChart} barSize={28}>
                  <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#9ca3af' }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} tickFormatter={v => `$${v}`} />
                  <Tooltip formatter={(v: number) => `$${Number(v).toLocaleString()}`} labelFormatter={(_, p) => (p?.[0]?.payload as any)?.fullName ?? ''} contentStyle={chartTooltipStyle} cursor={{ fill: '#f9fafb' }} />
                  <Bar dataKey="sales" fill="#1a6b3c" radius={[4, 4, 0, 0]} cursor="pointer" onClick={onCategoryBarClick}>
                    {categoryChart.map((c, i) => <Cell key={i} fill={String(c.categoryId) === categoryId ? '#0f3d22' : '#1a6b3c'} />)}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </>
          )}
        </div>
      </Card>

      <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader
            title={activeCategoryName ? `Sales by Item — ${activeCategoryName}` : 'Sales by Item'}
            action={
              <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                {categoryId && <Btn size="sm" variant="outline" onClick={() => setCategoryId(null)}>Clear filter</Btn>}
                <DownloadButtons endpoint="/shop/reports/by-item" params={itemParams} filename="shop-sales-by-item" />
              </div>
            }
          />
          <Table headers={['#', 'Item', 'Code', 'Qty', 'Sales']}>
            {(byItem as any[]).map((i, idx) => (
              <tr key={i.id}>
                <Td style={{ color: '#9ca3af' }}>{idx + 1}</Td>
                <Td>{i.item_name}</Td>
                <Td>{i.item_code}</Td>
                <Td>{i.quantity_sold}</Td>
                <Td><strong>{money(i.total_sales)}</strong></Td>
              </tr>
            ))}
            {(byItem as any[]).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af' }}>No sales found.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Sales by Category" action={<DownloadButtons endpoint="/shop/reports/by-category" params={rangeParams} filename="shop-sales-by-category" />} />
          <Table headers={['#', 'Category', 'Qty', 'Sales']}>
            {(byCategory as any[]).map((c, idx) => (
              <tr key={c.id}>
                <Td style={{ color: '#9ca3af' }}>{idx + 1}</Td>
                <Td>{c.name}</Td>
                <Td>{c.quantity_sold}</Td>
                <Td><strong>{money(c.total_sales)}</strong></Td>
              </tr>
            ))}
            {(byCategory as any[]).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#9ca3af' }}>No sales found.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Date Range Purchases" action={<DownloadButtons endpoint="/shop/reports/date-range" params={rangeParams} filename="shop-sales-register" />} />
          <Table headers={['#', 'Date', 'Purchase', 'Student', 'Total']}>
            {(range?.purchases ?? []).map((p: any, idx: number) => (
              <tr key={p.id}>
                <Td style={{ color: '#9ca3af' }}>{idx + 1}</Td>
                <Td>{p.purchase_date}</Td>
                <Td>{p.purchase_number}</Td>
                <Td>{p.student_name}</Td>
                <Td><strong>{money(p.total_amount)}</strong></Td>
              </tr>
            ))}
            {(range?.purchases ?? []).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af' }}>No purchases in this range.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Cancelled Purchases" action={<DownloadButtons endpoint="/shop/reports/cancelled" params={{}} filename="shop-cancelled-purchases" />} />
          <Table headers={['#', 'Date', 'Purchase', 'Student', 'Total']}>
            {(cancelled as any[]).map((p, idx) => (
              <tr key={p.id}>
                <Td style={{ color: '#9ca3af' }}>{idx + 1}</Td>
                <Td>{p.purchase_date}</Td>
                <Td>{p.purchase_number}</Td>
                <Td>{p.student_name}</Td>
                <Td>{money(p.total_amount)}</Td>
              </tr>
            ))}
            {(cancelled as any[]).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af' }}>No cancelled purchases.</Td></tr>}
          </Table>
        </Card>
      </div>
    </div>
  );
}
