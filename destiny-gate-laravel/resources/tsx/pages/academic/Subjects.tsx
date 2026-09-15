import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface SubjectGroup { id: number; name: string }
interface Subject { id: number; name: string; code: string; subject_group_id: number | null; group_name: string | null; pass_mark: number; is_compulsory: boolean }
const empty = { name: '', code: '', subject_group_id: '', pass_mark: '50', is_compulsory: false as boolean };

export default function Subjects() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Subject | null>(null);
  const [form, setForm]       = useState(empty);
  const [search, setSearch]   = useState('');
  const [filterGroup, setFilterGroup] = useState('');
  const [formErr, setFormErr] = useState('');

  const { data: groups = [] } = useQuery<SubjectGroup[]>({ queryKey: ['subject-groups'], queryFn: () => api.get('/subject-groups').then(r => r.data) });
  const { data = [], isLoading } = useQuery<Subject[]>({
    queryKey: ['subjects', search, filterGroup],
    queryFn: () => api.get('/subjects', { params: { search: search || undefined, subject_group_id: filterGroup || undefined } }).then(r => r.data),
    placeholderData: keepPreviousData, // otherwise every keystroke flashes the whole page to a spinner
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (s: Subject) => {
    setEditing(s);
    setForm({ name: s.name, code: s.code, subject_group_id: s.subject_group_id ? String(s.subject_group_id) : '', pass_mark: String(s.pass_mark), is_compulsory: s.is_compulsory });
    setFormErr(''); setOpen(true);
  };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/subjects/${editing.id}`, d) : api.post('/subjects', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['subjects'] }); setOpen(false); toastSuccess(editing ? 'Subject updated.' : 'Subject created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/subjects/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['subjects'] }); toastSuccess('Subject deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete — subject has allocations.'),
  });

  const handleDelete = async (s: Subject) => {
    const ok = await confirmDelete(`subject "${s.name}"`);
    if (ok) del.mutate(s.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Subjects" subtitle="All subjects offered at DestinyGate Institute" action={<Btn onClick={openAdd}>+ Add Subject</Btn>} />

      <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
        <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search by name or code…"
          style={{ padding: '8px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13, width: 260, outline: 'none' }} />
        <Select value={filterGroup} onChange={e => setFilterGroup(e.target.value)} style={{ width: 200 }}>
          <option value="">All Groups</option>
          {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
        </Select>
      </div>

      <Card>
        <Table headers={['Code', 'Subject Name', 'Group', 'Pass Mark', 'Type', 'Actions']}>
          {data.map(s => (
            <tr key={s.id}>
              <Td><code style={{ background: '#f1f5f9', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{s.code}</code></Td>
              <Td><strong>{s.name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{s.group_name ?? '—'}</Td>
              <Td>{s.pass_mark}%</Td>
              <Td>{s.is_compulsory ? <Badge variant="blue">Compulsory</Badge> : <Badge variant="gray">Optional</Badge>}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" onClick={() => openEdit(s)}>Edit</Btn>
                  <Btn size="sm" variant="danger"  onClick={() => handleDelete(s)}>Delete</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Subject' : 'Add Subject'}>
        {formErr && <Alert type="error" message={formErr} />}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Subject Name">
            <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="Mathematics" />
          </FormGroup>
          <FormGroup label="Subject Code">
            <Input value={form.code} onChange={e => setForm(f => ({ ...f, code: e.target.value.toUpperCase() }))} placeholder="MATH" />
          </FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Subject Group">
            <Select value={form.subject_group_id} onChange={e => setForm(f => ({ ...f, subject_group_id: e.target.value }))}>
              <option value="">None</option>
              {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
            </Select>
          </FormGroup>
          <FormGroup label="Pass Mark (%)">
            <Input type="number" value={form.pass_mark} onChange={e => setForm(f => ({ ...f, pass_mark: e.target.value }))} min="0" max="100" />
          </FormGroup>
        </Grid>
        <FormGroup label="Type">
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13 }}>
            <input type="checkbox" checked={form.is_compulsory} onChange={e => setForm(f => ({ ...f, is_compulsory: e.target.checked }))} />
            Mark as Compulsory
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
