import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, Input, PageHeader, Spinner, Table, Td } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function ShopReportsPage() {
  const [from, setFrom] = useState(new Date().toISOString().slice(0, 10));
  const [to, setTo] = useState(new Date().toISOString().slice(0, 10));
  const params = { date_from: from, date_to: to };
  const { data: range, isLoading } = useQuery({ queryKey: ['shop-range', from, to], queryFn: () => api.get('/shop/reports/date-range', { params }).then(r => r.data) });
  const { data: byItem = [] } = useQuery({ queryKey: ['shop-by-item', from, to], queryFn: () => api.get('/shop/reports/by-item', { params }).then(r => r.data) });
  const { data: byCategory = [] } = useQuery({ queryKey: ['shop-by-category', from, to], queryFn: () => api.get('/shop/reports/by-category', { params }).then(r => r.data) });
  const { data: cancelled = [] } = useQuery({ queryKey: ['shop-cancelled'], queryFn: () => api.get('/shop/reports/cancelled').then(r => r.data) });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Shop Reports" subtitle="Sales, unpaid balances, low stock, and cancelled purchase views" />
      <div className="mb-5 grid grid-cols-1 gap-3 md:grid-cols-3">
        <Input type="date" value={from} onChange={e => setFrom(e.target.value)} />
        <Input type="date" value={to} onChange={e => setTo(e.target.value)} />
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm"><span className="text-slate-600">Range total</span><br /><strong className="text-lg text-emerald-800">{money(range?.total)}</strong></div>
      </div>
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <Card><CardHeader title="Sales by Item" /><Table headers={['Item', 'Code', 'Qty', 'Sales']}>{(byItem as any[]).map(i => <tr key={i.id}><Td>{i.item_name}</Td><Td>{i.item_code}</Td><Td>{i.quantity_sold}</Td><Td><strong>{money(i.total_sales)}</strong></Td></tr>)}</Table></Card>
        <Card><CardHeader title="Sales by Category" /><Table headers={['Category', 'Qty', 'Sales']}>{(byCategory as any[]).map(c => <tr key={c.id}><Td>{c.name}</Td><Td>{c.quantity_sold}</Td><Td><strong>{money(c.total_sales)}</strong></Td></tr>)}</Table></Card>
        <Card><CardHeader title="Date Range Purchases" /><Table headers={['Date', 'Purchase', 'Student', 'Total']}>{(range?.purchases ?? []).map((p: any) => <tr key={p.id}><Td>{p.purchase_date}</Td><Td>{p.purchase_number}</Td><Td>{p.student_name}</Td><Td><strong>{money(p.total_amount)}</strong></Td></tr>)}</Table></Card>
        <Card><CardHeader title="Cancelled Purchases" /><Table headers={['Date', 'Purchase', 'Student', 'Total']}>{(cancelled as any[]).map(p => <tr key={p.id}><Td>{p.purchase_date}</Td><Td>{p.purchase_number}</Td><Td>{p.student_name}</Td><Td>{money(p.total_amount)}</Td></tr>)}</Table></Card>
      </div>
    </div>
  );
}
