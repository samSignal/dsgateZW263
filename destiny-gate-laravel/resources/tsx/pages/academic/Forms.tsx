import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Modal, FormGroup, Input, Alert } from '../../components/UI';

interface SchoolForm { id: number; name: string; level: number; description: string | null }

export default function Forms() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<SchoolForm | null>(null);
  const [form, setForm]       = useState({ name: '', description: '' });
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<SchoolForm[]>({
    queryKey: ['forms'],
    queryFn: () => api.get('/forms').then(r => r.data),
  });

  const openEdit = (f: SchoolForm) => {
    setEditing(f);
    setForm({ name: f.name, description: f.description ?? '' });
    setFormErr('');
    setOpen(true);
  };

  const save = useMutation({
    mutationFn: (d: typeof form) => api.put(`/forms/${editing!.id}`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['forms'] }); setOpen(false); toastSuccess('Form updated successfully.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Forms" subtitle="Form 1 to Form 6 — high school levels (seeded by default)" />

      <Card>
        <Table headers={['Level', 'Name', 'Description', 'Actions']}>
          {data.map(f => (
            <tr key={f.id}>
              <Td>
                <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: '50%', background: '#f0faf4', color: '#1a6b3c', fontWeight: 700, fontSize: 12 }}>
                  {f.level}
                </span>
              </Td>
              <Td><strong>{f.name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{f.description ?? '—'}</Td>
              <Td><Btn size="sm" variant="outline" onClick={() => openEdit(f)}>Edit</Btn></Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={`Edit ${editing?.name}`}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Name">
          <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
        </FormGroup>
        <FormGroup label="Description">
          <Input value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
