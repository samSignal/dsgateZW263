import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Textarea, Alert } from '../../components/UI';

interface Department { id: number; name: string; description: string | null; staff_count: number }
const empty = { name: '', description: '' };

export default function Departments() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Department | null>(null);
  const [form, setForm]       = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<Department[]>({
    queryKey: ['departments'],
    queryFn: () => api.get('/departments').then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (d: Department) => { setEditing(d); setForm({ name: d.name, description: d.description ?? '' }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/departments/${editing.id}`, d) : api.post('/departments', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['departments'] }); setOpen(false); toastSuccess(editing ? 'Department updated.' : 'Department created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/departments/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['departments'] }); toastSuccess('Department deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this department.'),
  });

  const handleDelete = async (d: Department) => {
    const ok = await confirmDelete(`department "${d.name}"`);
    if (ok) del.mutate(d.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Departments" subtitle="Manage school departments" action={<Btn onClick={openAdd}>+ Add Department</Btn>} />

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: 14, marginBottom: 24 }}>
        {data.map(d => (
          <div key={d.id} style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', padding: '18px 20px', boxShadow: '0 1px 3px rgba(0,0,0,.04)' }}>
            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 8 }}>
              <div style={{ width: 38, height: 38, borderRadius: 9, background: '#eef1f8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18 }}>🏢</div>
              <Badge variant="blue">{d.staff_count} staff</Badge>
            </div>
            <div style={{ fontSize: 14, fontWeight: 700, color: '#111827', marginBottom: 4 }}>{d.name}</div>
            <div style={{ fontSize: 12, color: '#6b7280', marginBottom: 14, minHeight: 32 }}>{d.description ?? 'No description'}</div>
            <div style={{ display: 'flex', gap: 6 }}>
              <Btn size="sm" variant="outline" onClick={() => openEdit(d)}>Edit</Btn>
              <Btn size="sm" variant="danger"  onClick={() => handleDelete(d)}>Delete</Btn>
            </div>
          </div>
        ))}
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Department' : 'Add Department'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Department Name">
          <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Sciences" />
        </FormGroup>
        <FormGroup label="Description">
          <Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
