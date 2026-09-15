import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete, confirmAction } from '../../lib/toast';
import { StatCard, Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface FeeStructure { id: number; name: string; amount: number; academic_year_name: string; term_name: string; form_name: string | null; stream_name: string | null; category_name: string; is_active: boolean; is_required: boolean; due_date: string | null }

const empty = { academic_year_id: '', term_id: '', form_id: '', stream_id: '', fee_category_id: '', name: '', amount: '', due_date: '', is_required: true };

export default function FeeStructuresPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<FeeStructure | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', form_id: '', fee_category_id: '' });

  const { data: years = [] }   = useQuery({ queryKey: ['academic-years'],  queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] }   = useQuery({ queryKey: ['terms'],            queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: forms = [] }   = useQuery({ queryKey: ['forms'],            queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'],          queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: cats = [] }    = useQuery({ queryKey: ['fee-categories'],   queryFn: () => api.get('/finance/categories').then(r => r.data) });

  const { data = [], isLoading } = useQuery<FeeStructure[]>({
    queryKey: ['fee-structures', filters],
    queryFn: () => api.get('/finance/structures', { params: filters }).then(r => r.data),
  });

  const filteredTerms = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);
  const filteredStreams = (streams as any[]).filter(s => !form.form_id || String(s.form_id) === form.form_id);

  const activeStructures = data.filter(s => s.is_active);
  const formsCovered = new Set(activeStructures.map(s => s.form_name ?? 'All Forms')).size;
  const totalValue = activeStructures.reduce((sum, s) => sum + Number(s.amount), 0);

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/finance/structures/${editing.id}`, d) : api.post('/finance/structures', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-structures'] }); setOpen(false); toastSuccess(editing ? 'Structure updated.' : 'Fee structure created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const deactivate = useMutation({
    mutationFn: (id: number) => api.post(`/finance/structures/${id}/deactivate`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-structures'] }); toastSuccess('Fee structure deactivated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/finance/structures/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-structures'] }); toastSuccess('Deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Fee Structures" subtitle="Define fees per form, term, and academic year" action={<Btn onClick={() => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); }}>+ Add Structure</Btn>} />

      <div className="grid grid-cols-1 gap-3 md:grid-cols-3 mb-6">
        <StatCard label="Active Structures" value={activeStructures.length} icon="📄" color="blue" />
        <StatCard label="Forms Covered"     value={formsCovered}            icon="🏫" color="purple" />
        <StatCard label="Total Value"       value={`$${totalValue.toLocaleString()}`} icon="💰" color="green" trend="sum of active structures" />
      </div>

      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        {[
          { label: 'Year', key: 'academic_year_id', opts: years, nameKey: 'name' },
          { label: 'Term', key: 'term_id', opts: terms, nameKey: 'name' },
          { label: 'Form', key: 'form_id', opts: forms, nameKey: 'name' },
          { label: 'Category', key: 'fee_category_id', opts: cats, nameKey: 'name' },
        ].map(f => (
          <Select key={f.key} value={(filters as any)[f.key]} onChange={e => setFilters(p => ({ ...p, [f.key]: e.target.value }))} style={{ width: 160 }}>
            <option value="">All {f.label}s</option>
            {(f.opts as any[]).map(o => <option key={o.id} value={o.id}>{o[f.nameKey]}</option>)}
          </Select>
        ))}
      </div>

      <Card>
        <Table headers={['Name', 'Category', 'Form', 'Class', 'Term', 'Year', 'Amount', 'Due Date', 'Status', 'Actions']}>
          {data.map(s => (
            <tr key={s.id}>
              <Td><strong>{s.name}</strong></Td>
              <Td>{s.category_name}</Td>
              <Td>{s.form_name ?? 'All'}</Td>
              <Td>{s.stream_name ?? '—'}</Td>
              <Td>{s.term_name}</Td>
              <Td>{s.academic_year_name}</Td>
              <Td><strong style={{ color: '#1a6b3c' }}>${Number(s.amount).toLocaleString()}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{s.due_date ?? '—'}</Td>
              <Td><Badge variant={s.is_active ? 'green' : 'gray'}>{s.is_active ? 'Active' : 'Inactive'}</Badge></Td>
              <Td>
                <div style={{ display: 'flex', gap: 5 }}>
                  <Btn size="sm" variant="outline" onClick={() => { setEditing(s); setForm({ academic_year_id: '', term_id: '', form_id: '', stream_id: '', fee_category_id: '', name: s.name, amount: String(s.amount), due_date: s.due_date ?? '', is_required: s.is_required }); setFormErr(''); setOpen(true); }}>Edit</Btn>
                  {s.is_active && <Btn size="sm" variant="ghost" onClick={async () => { if (await confirmAction('Deactivate?', `Deactivate "${s.name}"?`, 'Deactivate')) deactivate.mutate(s.id); }}>Deactivate</Btn>}
                  <Btn size="sm" variant="danger" onClick={async () => { if (await confirmDelete(`"${s.name}"`)) del.mutate(s.id); }}>Delete</Btn>
                </div>
              </Td>
            </tr>
          ))}
          {data.length === 0 && <tr><Td colSpan={10} style={{ textAlign: 'center', color: '#9ca3af', padding: 32 }}>No fee structures found.</Td></tr>}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Fee Structure' : 'Add Fee Structure'}>
        {formErr && <Alert type="error" message={formErr} />}
        {!editing && (
          <>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Academic Year">
                <Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}>
                  <option value="">Select year…</option>
                  {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
                </Select>
              </FormGroup>
              <FormGroup label="Term">
                <Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}>
                  <option value="">Select term…</option>
                  {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </Select>
              </FormGroup>
            </Grid>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Form (leave blank for all)">
                <Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}>
                  <option value="">All Forms</option>
                  {(forms as any[]).map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
                </Select>
              </FormGroup>
              <FormGroup label="Class (optional)">
                <Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}>
                  <option value="">All Classes</option>
                  {filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Select>
              </FormGroup>
            </Grid>
            <FormGroup label="Fee Category">
              <Select value={form.fee_category_id} onChange={e => setForm(f => ({ ...f, fee_category_id: e.target.value }))}>
                <option value="">Select category…</option>
                {(cats as any[]).map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
            </FormGroup>
          </>
        )}
        <FormGroup label="Structure Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Form 1 Term 1 Tuition" /></FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Amount ($)"><Input type="number" step="0.01" value={form.amount} onChange={e => setForm(f => ({ ...f, amount: e.target.value }))} /></FormGroup>
          <FormGroup label="Due Date"><Input type="date" value={form.due_date} onChange={e => setForm(f => ({ ...f, due_date: e.target.value }))} /></FormGroup>
        </Grid>
        <FormGroup label="Required">
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13 }}>
            <input type="checkbox" checked={form.is_required} onChange={e => setForm(f => ({ ...f, is_required: e.target.checked }))} />
            Mark as required fee
          </label>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
