import React, { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { PageHeader, StatCard, Card, CardHeader, Table, Td, Spinner, Badge, Btn } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const chartTooltipStyle = { fontSize: 12, borderRadius: 8, border: '1px solid #e8eaed', boxShadow: '0 4px 12px rgba(0,0,0,.08)' };

function DownloadButtons({ endpoint, filename }: { endpoint: string; filename: string }) {
  const [busy, setBusy] = useState<'csv' | 'pdf' | null>(null);
  const go = async (format: 'csv' | 'pdf') => {
    setBusy(format);
    try { await downloadReport(endpoint, { format }, `${filename}.${format}`); } finally { setBusy(null); }
  };
  return (
    <div style={{ display: 'flex', gap: 8 }}>
      <Btn size="sm" variant="outline" loading={busy === 'pdf'} onClick={() => go('pdf')}>⬇ PDF</Btn>
      <Btn size="sm" variant="outline" loading={busy === 'csv'} onClick={() => go('csv')}>⬇ CSV</Btn>
    </div>
  );
}

export default function ShopDashboard() {
  const navigate = useNavigate();
  const { data: summary, isLoading } = useQuery({ queryKey: ['shop-summary'], queryFn: () => api.get('/shop/reports/summary').then(r => r.data) });
  const { data: unpaid = [] } = useQuery({ queryKey: ['shop-unpaid'], queryFn: () => api.get('/shop/reports/unpaid').then(r => r.data) });
  const { data: lowStock = [] } = useQuery({ queryKey: ['shop-low-stock'], queryFn: () => api.get('/shop/items/low-stock').then(r => r.data) });

  const topUnpaid = useMemo(() => (unpaid as any[]).slice(0, 10).map(p => ({
    name: p.student_name.length > 18 ? p.student_name.slice(0, 17) + '…' : p.student_name,
    fullName: p.student_name,
    purchaseId: p.id,
    balance: Number(p.balance),
  })), [unpaid]);

  const onPurchaseBarClick = (data: any) => {
    if (data?.purchaseId) navigate(`/app/shop/purchases/${data.purchaseId}`);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader
        title="School Shop"
        subtitle="Student purchases, stock levels, payments, and parent purchase visibility"
        action={<Link to="/app/shop/record"><Btn>Record Purchase</Btn></Link>}
      />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5 mb-6">
        <StatCard label="Today Sales" value={money(summary?.today_sales)} color="green" />
        <StatCard label="Total Purchases" value={summary?.total_purchases ?? 0} color="blue" />
        <StatCard label="Unpaid Purchases" value={summary?.unpaid_purchases ?? 0} color="amber" />
        <StatCard label="Low Stock Items" value={summary?.low_stock_items ?? 0} color="red" />
        <StatCard label="Cancelled" value={summary?.cancelled_purchases ?? 0} color="purple" />
      </div>

      {topUnpaid.length > 0 && (
        <Card style={{ marginBottom: 20 }}>
          <CardHeader title="Top 10 Unpaid Purchases" />
          <div style={{ padding: '4px 8px 14px' }}>
            <div style={{ fontSize: 11, color: '#9ca3af', padding: '0 8px 6px' }}>Click a bar to open that purchase.</div>
            <ResponsiveContainer width="100%" height={Math.max(180, topUnpaid.length * 28)}>
              <BarChart data={topUnpaid} layout="vertical" barSize={16} margin={{ left: 8, right: 16 }}>
                <XAxis type="number" tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} tickFormatter={v => `$${v}`} />
                <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: '#374151' }} axisLine={false} tickLine={false} width={110} />
                <Tooltip formatter={(v: number) => `$${Number(v).toLocaleString()}`} labelFormatter={(_, p) => (p?.[0]?.payload as any)?.fullName ?? ''} contentStyle={chartTooltipStyle} cursor={{ fill: '#f9fafb' }} />
                <Bar dataKey="balance" fill="#dc2626" radius={[0, 4, 4, 0]} cursor="pointer" onClick={onPurchaseBarClick}>
                  {topUnpaid.map((_: any, i: number) => <Cell key={i} />)}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      )}

      <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader title="Unpaid Purchases" action={
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <DownloadButtons endpoint="/shop/reports/unpaid" filename="shop-unpaid-purchases" />
              <Link className="text-sm font-semibold text-emerald-700" to="/app/shop/reports">View reports</Link>
            </div>
          } />
          <Table headers={['#', 'Student', 'Purchase', 'Balance', 'Status']}>
            {(unpaid as any[]).slice(0, 8).map((p, i) => (
              <tr key={p.id}>
                <Td style={{ color: '#9ca3af' }}>{i + 1}</Td>
                <Td><strong>{p.student_name}</strong><br /><span className="text-xs text-slate-500">{p.student_number ?? p.admission_number}</span></Td>
                <Td><Link className="font-semibold text-emerald-700" to={`/app/shop/purchases/${p.id}`}>{p.purchase_number}</Link></Td>
                <Td style={{ color: '#dc2626', fontWeight: 700 }}>{money(p.balance)}</Td>
                <Td><Badge variant={p.status === 'partial' ? 'amber' : 'red'}>{p.status}</Badge></Td>
              </tr>
            ))}
            {(unpaid as any[]).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#94a3b8' }}>No unpaid shop purchases.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Low Stock" action={
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <DownloadButtons endpoint="/shop/reports/low-stock" filename="shop-low-stock" />
              <Link className="text-sm font-semibold text-emerald-700" to="/app/shop/items">Manage stock</Link>
            </div>
          } />
          <Table headers={['#', 'Item', 'Category', 'Stock', 'Reorder']}>
            {(lowStock as any[]).slice(0, 8).map((i, idx) => (
              <tr key={i.id}>
                <Td style={{ color: '#9ca3af' }}>{idx + 1}</Td>
                <Td><strong>{i.item_name}</strong><br /><span className="text-xs text-slate-500">{i.item_code}</span></Td>
                <Td>{i.category_name}</Td>
                <Td><Badge variant="red">{i.quantity_in_stock}</Badge></Td>
                <Td>{i.reorder_level}</Td>
              </tr>
            ))}
            {(lowStock as any[]).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#94a3b8' }}>Stock levels look healthy.</Td></tr>}
          </Table>
        </Card>
      </div>
    </div>
  );
}
