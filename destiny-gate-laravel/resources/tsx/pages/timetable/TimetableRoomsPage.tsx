import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface Room { id: number; room_name: string; room_code: string | null; room_type: string; capacity: number | null; is_active: boolean; usage_count: number }
const empty = { room_name: '', room_code: '', room_type: 'classroom', capacity: '' };

const typeColors: Record<string, 'blue' | 'purple' | 'green' | 'amber' | 'gray'> = {
  classroom: 'blue', laboratory: 'purple', computer_lab: 'green', hall: 'amber', sports: 'gray',
};

export default function TimetableRoomsPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Room | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<Room[]>({ queryKey: ['tt-rooms'], queryFn: () => api.get('/timetable/rooms').then(r => r.data) });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (r: Room) => { setEditing(r); setForm({ room_name: r.room_name, room_code: r.room_code ?? '', room_type: r.room_type, capacity: r.capacity ? String(r.capacity) : '' }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/timetable/rooms/${editing.id}`, d) : api.post('/timetable/rooms', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['tt-rooms'] }); setOpen(false); toastSuccess(editing ? 'Room updated.' : 'Room created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const toggle = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) => api.post(`/timetable/rooms/${id}/${active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['tt-rooms'] }); toastSuccess('Status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Rooms & Labs" subtitle="Manage classrooms, laboratories, and halls" action={<Btn onClick={openAdd}>+ Add Room</Btn>} />
      <Card>
        <Table headers={['Room Name', 'Code', 'Type', 'Capacity', 'Usage', 'Status', 'Actions']}>
          {data.map(r => (
            <tr key={r.id}>
              <Td><strong>{r.room_name}</strong></Td>
              <Td>{r.room_code ? <code style={{ background: '#f1f5f9', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{r.room_code}</code> : '—'}</Td>
              <Td><Badge variant={typeColors[r.room_type] ?? 'gray'} style={{ textTransform: 'capitalize' }}>{r.room_type.replace('_', ' ')}</Badge></Td>
              <Td>{r.capacity ?? '—'}</Td>
              <Td style={{ color: '#6b7280' }}>{r.usage_count} lessons</Td>
              <Td><Badge variant={r.is_active ? 'green' : 'gray'}>{r.is_active ? 'Active' : 'Inactive'}</Badge></Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Btn>
                  <Btn size="sm" variant="ghost" onClick={() => toggle.mutate({ id: r.id, active: r.is_active })}>{r.is_active ? 'Deactivate' : 'Activate'}</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Room' : 'Add Room'}>
        {formErr && <Alert type="error" message={formErr} />}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Room Name"><Input value={form.room_name} onChange={e => setForm(f => ({ ...f, room_name: e.target.value }))} placeholder="e.g. Physics Lab" /></FormGroup>
          <FormGroup label="Room Code"><Input value={form.room_code} onChange={e => setForm(f => ({ ...f, room_code: e.target.value.toUpperCase() }))} placeholder="e.g. PLAB" /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Room Type">
            <Select value={form.room_type} onChange={e => setForm(f => ({ ...f, room_type: e.target.value }))}>
              <option value="classroom">Classroom</option>
              <option value="laboratory">Laboratory</option>
              <option value="computer_lab">Computer Lab</option>
              <option value="hall">Hall</option>
              <option value="sports">Sports</option>
            </Select>
          </FormGroup>
          <FormGroup label="Capacity"><Input type="number" value={form.capacity} onChange={e => setForm(f => ({ ...f, capacity: e.target.value }))} placeholder="e.g. 35" /></FormGroup>
        </Grid>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
