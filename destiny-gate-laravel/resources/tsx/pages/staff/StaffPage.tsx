import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Grid, Alert, statusBadge } from '../../components/UI';

interface Department { id: number; name: string }
interface StaffMember {
  id: number; staff_number: string; first_name: string; last_name: string;
  gender: string; phone: string; email: string; job_title: string;
  employment_type: string; employment_date: string; status: string;
  department_id: number; department_name: string;
  user_id: number | null; user_role: string | null;
  teacher_code: string | null; profile_photo: string | null;
}

const emptyForm = {
  first_name: '', last_name: '', gender: 'male', date_of_birth: '',
  phone: '', email: '', address: '', national_id: '',
  department_id: '', job_title: '', employment_type: 'full_time',
  employment_date: '', create_account: false, role: 'teacher',
};

const statusColor: Record<string, 'green' | 'amber' | 'red' | 'gray'> = {
  active: 'green', on_leave: 'amber', suspended: 'red', resigned: 'gray',
};

export default function StaffPage() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<StaffMember | null>(null);
  const [form, setForm]       = useState(emptyForm);
  const [formErr, setFormErr] = useState('');
  const [search, setSearch]   = useState('');
  const [filterDept, setFilterDept]     = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [page, setPage] = useState(1);

  const { data: depts = [] } = useQuery<Department[]>({ queryKey: ['departments'], queryFn: () => api.get('/departments').then(r => r.data) });

  const { data, isLoading } = useQuery({
    queryKey: ['staff-members', search, filterDept, filterStatus, page],
    queryFn: () => api.get('/staff-members', { params: { search: search || undefined, department_id: filterDept || undefined, status: filterStatus || undefined, page } }).then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(emptyForm); setFormErr(''); setOpen(true); };
  const openEdit = (s: StaffMember) => {
    setEditing(s);
    setForm({ first_name: s.first_name, last_name: s.last_name, gender: s.gender, date_of_birth: '', phone: s.phone, email: s.email, address: '', national_id: '', department_id: String(s.department_id), job_title: s.job_title, employment_type: s.employment_type, employment_date: s.employment_date, create_account: false, role: s.user_role ?? 'teacher' });
    setFormErr(''); setOpen(true);
  };

  const save = useMutation({
    mutationFn: (d: typeof emptyForm) => editing ? api.put(`/staff-members/${editing.id}`, d) : api.post('/staff-members', d),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['staff-members'] });
      setOpen(false);
      const pwd = res.data?.default_password;
      toastSuccess(editing ? 'Staff member updated.' : `Staff created. ${pwd ? `Default password: ${pwd}` : ''}`);
    },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const statusAction = useMutation({
    mutationFn: ({ id, action }: { id: number; action: string }) => api.post(`/staff-members/${id}/${action}`),
    onSuccess: (_, vars) => { qc.invalidateQueries({ queryKey: ['staff-members'] }); toastSuccess(`Status updated.`); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/staff-members/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff-members'] }); toastSuccess('Staff member deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete.'),
  });

  const handleStatusChange = async (s: StaffMember, action: string, label: string) => {
    const ok = await confirmAction(`${label}?`, `This will change ${s.first_name} ${s.last_name}'s status.`, `Yes, ${label}`);
    if (ok) statusAction.mutate({ id: s.id, action });
  };

  const handleDelete = async (s: StaffMember) => {
    const ok = await confirmDelete(`${s.first_name} ${s.last_name}`);
    if (ok) del.mutate(s.id);
  };

  const staff: StaffMember[] = data?.data ?? [];

  return (
    <div>
      <PageHeader title="Staff Management" subtitle="All school employees" action={<Btn onClick={openAdd}>+ Add Staff Member</Btn>} />

      {/* Filters */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} placeholder="Search name, number, email…"
          style={{ padding: '8px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13, width: 260, outline: 'none' }} />
        <Select value={filterDept} onChange={e => { setFilterDept(e.target.value); setPage(1); }} style={{ width: 180 }}>
          <option value="">All Departments</option>
          {depts.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
        </Select>
        <Select value={filterStatus} onChange={e => { setFilterStatus(e.target.value); setPage(1); }} style={{ width: 160 }}>
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="on_leave">On Leave</option>
          <option value="suspended">Suspended</option>
          <option value="resigned">Resigned</option>
        </Select>
      </div>

      <Card>
        {isLoading ? <Spinner /> : (
          <>
            <Table headers={['Staff #', 'Name', 'Department', 'Job Title', 'Type', 'Role', 'Status', 'Actions']}>
              {staff.map(s => (
                <tr key={s.id}>
                  <Td><code style={{ background: '#f1f5f9', padding: '2px 7px', borderRadius: 5, fontSize: 11 }}>{s.staff_number}</code></Td>
                  <Td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                      <div style={{ width: 32, height: 32, borderRadius: '50%', background: 'linear-gradient(135deg,#b6924c,#8a6b34)', color: '#0f1a2e', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 700, flexShrink: 0 }}>
                        {s.first_name[0]}{s.last_name[0]}
                      </div>
                      <div>
                        <div style={{ fontWeight: 600, fontSize: 13 }}>{s.first_name} {s.last_name}</div>
                        <div style={{ fontSize: 11, color: '#6b7280' }}>{s.email}</div>
                      </div>
                    </div>
                  </Td>
                  <Td>{s.department_name}</Td>
                  <Td>{s.job_title}</Td>
                  <Td style={{ textTransform: 'capitalize' }}>{s.employment_type.replace('_', ' ')}</Td>
                  <Td>
                    {s.user_role
                      ? <Badge variant={s.user_role === 'teacher' ? 'blue' : s.user_role === 'admin' ? 'red' : 'purple'} style={{ textTransform: 'capitalize' }}>{s.user_role}</Badge>
                      : <Badge variant="gray">No account</Badge>
                    }
                    {s.teacher_code && <Badge variant="green" style={{ marginLeft: 4 }}>Teacher</Badge>}
                  </Td>
                  <Td><Badge variant={statusColor[s.status] ?? 'gray'} style={{ textTransform: 'capitalize' }}>{s.status.replace('_', ' ')}</Badge></Td>
                  <Td>
                    <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap' }}>
                      <Link to={`/app/staff/${s.id}`}><Btn size="sm" variant="outline">View</Btn></Link>
                      <Btn size="sm" variant="outline" onClick={() => openEdit(s)}>Edit</Btn>
                      {s.status !== 'active'    && <Btn size="sm" variant="outline" onClick={() => handleStatusChange(s, 'activate', 'Activate')}>Activate</Btn>}
                      {s.status === 'active'    && <Btn size="sm" variant="ghost"   onClick={() => handleStatusChange(s, 'on-leave', 'Set On Leave')}>Leave</Btn>}
                      {s.status !== 'suspended' && s.status !== 'resigned' && <Btn size="sm" variant="danger" onClick={() => handleStatusChange(s, 'suspend', 'Suspend')}>Suspend</Btn>}
                      <Btn size="sm" variant="danger" onClick={() => handleDelete(s)}>Delete</Btn>
                    </div>
                  </Td>
                </tr>
              ))}
              {staff.length === 0 && (
                <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', padding: 40 }}>No staff members found.</Td></tr>
              )}
            </Table>

            {/* Pagination */}
            {data && data.last_page > 1 && (
              <div style={{ padding: '14px 20px', borderTop: '1px solid #f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span style={{ fontSize: 12, color: '#6b7280' }}>Showing {staff.length} of {data.total} staff</span>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>← Prev</Btn>
                  <span style={{ padding: '5px 12px', fontSize: 12, color: '#374151' }}>Page {page} of {data.last_page}</span>
                  <Btn size="sm" variant="outline" disabled={page >= data.last_page} onClick={() => setPage(p => p + 1)}>Next →</Btn>
                </div>
              </div>
            )}
          </>
        )}
      </Card>

      {/* Add/Edit Modal */}
      <Modal open={open} onClose={() => setOpen(false)} title={editing ? `Edit: ${editing.first_name} ${editing.last_name}` : 'Add Staff Member'}>
        {formErr && <Alert type="error" message={formErr} />}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={form.first_name} onChange={e => setForm(f => ({ ...f, first_name: e.target.value }))} required /></FormGroup>
          <FormGroup label="Last Name"> <Input value={form.last_name}  onChange={e => setForm(f => ({ ...f, last_name:  e.target.value }))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Gender">
            <Select value={form.gender} onChange={e => setForm(f => ({ ...f, gender: e.target.value }))}>
              <option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
            </Select>
          </FormGroup>
          <FormGroup label="Date of Birth"><Input type="date" value={form.date_of_birth} onChange={e => setForm(f => ({ ...f, date_of_birth: e.target.value }))} /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Phone"><Input value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} placeholder="07XX XXX XXX" /></FormGroup>
          <FormGroup label="Email"><Input type="email" value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Department">
            <Select value={form.department_id} onChange={e => setForm(f => ({ ...f, department_id: e.target.value }))}>
              <option value="">Select department…</option>
              {depts.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
            </Select>
          </FormGroup>
          <FormGroup label="Job Title"><Input value={form.job_title} onChange={e => setForm(f => ({ ...f, job_title: e.target.value }))} placeholder="e.g. Mathematics Teacher" /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Employment Type">
            <Select value={form.employment_type} onChange={e => setForm(f => ({ ...f, employment_type: e.target.value }))}>
              <option value="full_time">Full Time</option><option value="part_time">Part Time</option><option value="contract">Contract</option>
            </Select>
          </FormGroup>
          <FormGroup label="Employment Date"><Input type="date" value={form.employment_date} onChange={e => setForm(f => ({ ...f, employment_date: e.target.value }))} /></FormGroup>
        </Grid>
        <FormGroup label="National ID"><Input value={form.national_id} onChange={e => setForm(f => ({ ...f, national_id: e.target.value }))} placeholder="Optional" /></FormGroup>

        {!editing && (
          <div style={{ background: '#f9fafb', borderRadius: 8, padding: '14px 16px', marginBottom: 16, border: '1px solid #e8eaed' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13, fontWeight: 600, marginBottom: 8 }}>
              <input type="checkbox" checked={form.create_account} onChange={e => setForm(f => ({ ...f, create_account: e.target.checked }))} />
              Create login account for this staff member
            </label>
            {form.create_account && (
              <FormGroup label="System Role">
                <Select value={form.role} onChange={e => setForm(f => ({ ...f, role: e.target.value }))}>
                  <option value="teacher">Teacher</option>
                  <option value="headmaster">Headmaster</option>
                  <option value="bursar">Bursar</option>
                  <option value="admin">Admin</option>
                  <option value="user">User (no special access)</option>
                </Select>
              </FormGroup>
            )}
          </div>
        )}

        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>{editing ? 'Save Changes' : 'Create Staff Member'}</Btn>
        </div>
      </Modal>
    </div>
  );
}
