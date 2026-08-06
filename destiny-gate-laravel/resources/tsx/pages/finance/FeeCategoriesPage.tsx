import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Textarea, Alert } from '../../components/UI';

interface FeeCategory { id: number; name: string; description: string | null; is_active: boolean; structures_count: number; bills_count: number }
const empty = { name: '', description: '' };

export default function FeeCategoriesPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<FeeCategory | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<FeeCategory[]>({ queryKey: ['fee-categories'], queryFn: () => api.get('/finance/categories').then(r => r.data) });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (c: FeeCategory) => { setEditing(c); setForm({ name: c.name, description: c.description ?? '' }); setFormErr(''); setOpen(true); };

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
            <div style={{ fontSize: 14, fontWeight: 700, color: '#111827', marginBottom: 4 }}>{c.name}</div>
            <div style={{ fontSize: 12, color: '#6b7280', marginBottom: 12, minHeight: 28 }}>{c.description ?? 'No description'}</div>
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
        <FormGroup label="Category Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Tuition" /></FormGroup>
        <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" /></FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
