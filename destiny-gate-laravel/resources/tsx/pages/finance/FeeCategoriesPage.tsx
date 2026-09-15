import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Textarea, Alert } from '../../components/UI';

interface FeeCategory { id: number; code: string | null; name: string; description: string | null; frequency: string | null; is_active: boolean; structures_count: number; bills_count: number }
const empty = { code: '', name: '', description: '', frequency: '' };

const FREQUENCY_LABELS: Record<string, string> = {
  per_term: 'Per Term', once_off: 'Once-off', as_applicable: 'As Applicable', per_event: 'Per Event', per_project: 'Per Project',
};

export default function FeeCategoriesPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<FeeCategory | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<FeeCategory[]>({ queryKey: ['fee-categories'], queryFn: () => api.get('/finance/categories').then(r => r.data) });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (c: FeeCategory) => { setEditing(c); setForm({ code: c.code ?? '', name: c.name, description: c.description ?? '', frequency: c.frequency ?? '' }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/finance/categories/${editing.id}`, d) : api.post('/finance/categories', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-categories'] }); setOpen(false); toastSuccess(editing ? 'Category updated.' : 'Category created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const toggle = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) => api.post(`/finance/categories/${id}/${active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-categories'] }); toastSuccess('Status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/finance/categories/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-categories'] }); toastSuccess('Category deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete — category is in use.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Fee Categories" subtitle="Manage fee types (Tuition, Levy, Registration…)" action={<Btn onClick={openAdd}>+ Add Category</Btn>} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 14, marginBottom: 24 }}>
        {data.map(c => (
          <div key={c.id} style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', padding: '18px 20px', boxShadow: '0 1px 3px rgba(0,0,0,.04)' }}>
            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 10 }}>
              <div style={{ width: 36, height: 36, borderRadius: 9, background: c.is_active ? '#f0faf4' : '#f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18 }}>💰</div>
              <Badge variant={c.is_active ? 'green' : 'gray'}>{c.is_active ? 'Active' : 'Inactive'}</Badge>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 4 }}>
              {c.code && <code style={{ fontSize: 10, background: '#f1f5f9', color: '#475569', padding: '2px 6px', borderRadius: 4 }}>{c.code}</code>}
              <span style={{ fontSize: 14, fontWeight: 700, color: '#111827' }}>{c.name}</span>
            </div>
            <div style={{ fontSize: 12, color: '#6b7280', marginBottom: 8, minHeight: 16 }}>{c.description ?? 'No description'}</div>
            {c.frequency && <Badge variant="blue" style={{ marginBottom: 8 }}>{FREQUENCY_LABELS[c.frequency] ?? c.frequency}</Badge>}
            <div style={{ fontSize: 11, color: '#9ca3af', marginBottom: 12 }}>{c.structures_count} structures · {c.bills_count} bills</div>
            <div style={{ display: 'flex', gap: 6 }}>
              <Btn size="sm" variant="outline" onClick={() => openEdit(c)}>Edit</Btn>
              <Btn size="sm" variant="outline" onClick={() => toggle.mutate({ id: c.id, active: c.is_active })}>{c.is_active ? 'Deactivate' : 'Activate'}</Btn>
              {c.bills_count === 0 && c.structures_count === 0 && (
                <Btn size="sm" variant="danger" onClick={async () => { if (await confirmDelete(`"${c.name}"`)) del.mutate(c.id); }}>Delete</Btn>
              )}
            </div>
          </div>
        ))}
      </div>
      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Category' : 'Add Fee Category'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Code (optional)"><Input value={form.code} onChange={e => setForm(f => ({ ...f, code: e.target.value.toUpperCase() }))} placeholder="e.g. FEE-001" /></FormGroup>
        <FormGroup label="Category Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Tuition" /></FormGroup>
        <FormGroup label="Frequency (optional)">
          <Select value={form.frequency} onChange={e => setForm(f => ({ ...f, frequency: e.target.value }))}>
            <option value="">Not set</option>
            {Object.entries(FREQUENCY_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </Select>
        </FormGroup>
        <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" /></FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
