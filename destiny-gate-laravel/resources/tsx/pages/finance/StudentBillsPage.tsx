import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Select, statusBadge } from '../../components/UI';

export default function StudentBillsPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', status: '' });
  const [page, setPage] = useState(1);

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });

  const { data, isLoading } = useQuery({
    queryKey: ['student-bills', search, filters, page],
    queryFn: () => api.get('/finance/bills', { params: { search: search || undefined, ...filters, page } }).then(r => r.data),
  });

  const cancel = useMutation({
    mutationFn: (id: number) => api.post(`/finance/bills/${id}/cancel`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['student-bills'] }); toastSuccess('Bill cancelled.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot cancel.'),
  });

  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);
  const bills = data?.data ?? [];

  return (
    <div>
      <PageHeader title="Student Bills" subtitle="All student fee bills and invoices" />

      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} placeholder="Search student or bill number…"
          style={{ padding: '8px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13, width: 260, outline: 'none' }} />
        <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 150 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 140 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filters.status} onChange={e => setFilters(f => ({ ...f, status: e.target.value }))} style={{ width: 140 }}>
          <option value="">All Statuses</option>
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partial</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
        </Select>
      </div>

      <Card>
        {isLoading ? <Spinner /> : (
          <>
            <Table headers={['Bill #', 'Student', 'Description', 'Amount', 'Paid', 'Balance', 'Status', 'Due Date', 'Actions']}>
              {bills.map((b: any) => (
                <tr key={b.id}>
                  <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{b.bill_number}</code></Td>
                  <Td>
                    <div style={{ fontWeight: 600, fontSize: 13 }}>{b.student_name}</div>
                    <div style={{ fontSize: 11, color: '#6b7280' }}>{b.student_number}</div>
                  </Td>
                  <Td>{b.description}</Td>
                  <Td>${Number(b.amount).toLocaleString()}</Td>
                  <Td style={{ color: '#8a6b34' }}>${Number(b.amount_paid).toLocaleString()}</Td>
                  <Td style={{ color: Number(b.balance) > 0 ? '#dc2626' : '#8a6b34', fontWeight: 600 }}>${Number(b.balance).toLocaleString()}</Td>
                  <Td>{statusBadge(b.status)}</Td>
                  <Td style={{ color: '#6b7280' }}>{b.due_date ?? '—'}</Td>
                  <Td>
                    {b.status !== 'cancelled' && b.status !== 'paid' && (
                      <Btn size="sm" variant="danger" onClick={async () => {
                        if (await confirmAction('Cancel Bill?', `Cancel bill ${b.bill_number}?`, 'Cancel Bill'))
                          cancel.mutate(b.id);
                      }}>Cancel</Btn>
                    )}
                  </Td>
                </tr>
              ))}
              {bills.length === 0 && <tr><Td colSpan={9} style={{ textAlign: 'center', color: '#9ca3af', padding: 32 }}>No bills found.</Td></tr>}
            </Table>
            {data && data.last_page > 1 && (
              <div style={{ padding: '14px 20px', borderTop: '1px solid #f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span style={{ fontSize: 12, color: '#6b7280' }}>Showing {bills.length} of {data.total}</span>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>← Prev</Btn>
                  <span style={{ padding: '5px 12px', fontSize: 12 }}>Page {page} of {data.last_page}</span>
                  <Btn size="sm" variant="outline" disabled={page >= data.last_page} onClick={() => setPage(p => p + 1)}>Next →</Btn>
                </div>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
}
