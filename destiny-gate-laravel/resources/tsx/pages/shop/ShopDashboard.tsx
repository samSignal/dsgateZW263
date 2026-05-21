import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { PageHeader, StatCard, Card, CardHeader, Table, Td, Spinner, Badge, Btn } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function ShopDashboard() {
  const { data: summary, isLoading } = useQuery({ queryKey: ['shop-summary'], queryFn: () => api.get('/shop/reports/summary').then(r => r.data) });
  const { data: unpaid = [] } = useQuery({ queryKey: ['shop-unpaid'], queryFn: () => api.get('/shop/reports/unpaid').then(r => r.data) });
  const { data: lowStock = [] } = useQuery({ queryKey: ['shop-low-stock'], queryFn: () => api.get('/shop/items/low-stock').then(r => r.data) });

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

      <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader title="Unpaid Purchases" action={<Link className="text-sm font-semibold text-emerald-700" to="/app/shop/reports">View reports</Link>} />
          <Table headers={['Student', 'Purchase', 'Balance', 'Status']}>
            {(unpaid as any[]).slice(0, 8).map(p => (
              <tr key={p.id}>
                <Td><strong>{p.student_name}</strong><br /><span className="text-xs text-slate-500">{p.student_number ?? p.admission_number}</span></Td>
                <Td><Link className="font-semibold text-emerald-700" to={`/app/shop/purchases/${p.id}`}>{p.purchase_number}</Link></Td>
                <Td style={{ color: '#dc2626', fontWeight: 700 }}>{money(p.balance)}</Td>
                <Td><Badge variant={p.status === 'partial' ? 'amber' : 'red'}>{p.status}</Badge></Td>
              </tr>
            ))}
            {(unpaid as any[]).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#94a3b8' }}>No unpaid shop purchases.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Low Stock" action={<Link className="text-sm font-semibold text-emerald-700" to="/app/shop/items">Manage stock</Link>} />
          <Table headers={['Item', 'Category', 'Stock', 'Reorder']}>
            {(lowStock as any[]).slice(0, 8).map(i => (
              <tr key={i.id}>
                <Td><strong>{i.item_name}</strong><br /><span className="text-xs text-slate-500">{i.item_code}</span></Td>
                <Td>{i.category_name}</Td>
                <Td><Badge variant="red">{i.quantity_in_stock}</Badge></Td>
                <Td>{i.reorder_level}</Td>
              </tr>
            ))}
            {(lowStock as any[]).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#94a3b8' }}>Stock levels look healthy.</Td></tr>}
          </Table>
        </Card>
      </div>
    </div>
  );
}
