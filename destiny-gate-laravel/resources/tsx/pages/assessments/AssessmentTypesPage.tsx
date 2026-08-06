import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Textarea, Alert, Grid } from '../../components/UI';

interface AssessmentType { id: number; name: string; weight_percentage: number; description: string | null; is_active: boolean; assessments_count: number }
const empty = { name: '', weight_percentage: '0', description: '' };

export default function AssessmentTypesPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<AssessmentType | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<AssessmentType[]>({ queryKey: ['assessment-types'], queryFn: () => api.get('/assessment-types').then(r => r.data) });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (t: AssessmentType) => { setEditing(t); setForm({ name: t.name, weight_percentage: String(t.weight_percentage), description: t.description ?? '' }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/assessment-types/${editing.id}`, d) : api.post('/assessment-types', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessment-types'] }); setOpen(false); toastSuccess(editing ? 'Type updated.' : 'Assessment type created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const toggle = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) => api.post(`/assessment-types/${id}/${active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessment-types'] }); toastSuccess('Status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Assessment Types" subtitle="Manage assessment types and their weights" action={<Btn onClick={openAdd}>+ Add Type</Btn>} />
      <Card>
        <Table headers={['Name', 'Weight %', 'Description', 'Assessments', 'Status', 'Actions']}>
          {data.map(t => (
            <tr key={t.id}>
              <Td><strong>{t.name}</strong></Td>
              <Td>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <div style={{ width: 60, height: 6, background: '#f3f4f6', borderRadius: 3, overflow: 'hidden' }}>
                    <div style={{ height: '100%', width: `${t.weight_percentage}%`, background: '#1a6b3c', borderRadius: 3 }} />
                  </div>
                  <span style={{ fontSize: 12, fontWeight: 600 }}>{t.weight_percentage}%</span>
                </div>
              </Td>
              <Td style={{ color: '#6b7280' }}>{t.description ?? '—'}</Td>
              <Td>{t.assessments_count}</Td>
              <Td><Badge variant={t.is_active ? 'green' : 'gray'}>{t.is_active ? 'Active' : 'Inactive'}</Badge></Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" onClick={() => openEdit(t)}>Edit</Btn>
                  <Btn size="sm" variant="ghost" onClick={() => toggle.mutate({ id: t.id, active: t.is_active })}>{t.is_active ? 'Deactivate' : 'Activate'}</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Assessment Type' : 'Add Assessment Type'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Type Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Weekly Test" /></FormGroup>
        <FormGroup label="Weight Percentage (0-100)"><Input type="number" min="0" max="100" step="0.01" value={form.weight_percentage} onChange={e => setForm(f => ({ ...f, weight_percentage: e.target.value }))} /></FormGroup>
        <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" /></FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
