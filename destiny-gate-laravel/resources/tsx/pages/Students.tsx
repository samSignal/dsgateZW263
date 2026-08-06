import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { toastSuccess, toastError } from '../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Modal, FormGroup, Input, Select, Grid, StatCard } from '../components/UI';

export default function Students() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [msg, setMsg] = useState('');
  const [search, setSearch] = useState('');
  const [form, setForm] = useState({ first_name:'', last_name:'', email:'', date_of_birth:'', gender:'', form_id:'', stream_id:'', admission_date: new Date().toISOString().split('T')[0], national_id:'', blood_type:'', allergies:'', medical_conditions:'' });
  const [verifyStudent, setVerifyStudent] = useState<any | null>(null);
  const [checklist, setChecklist] = useState<any[]>([]);
  const [checklistLoading, setChecklistLoading] = useState(false);

  const { data, isLoading } = useQuery({ queryKey: ['students', search], queryFn: () => api.get('/students', { params: { search } }).then(r => r.data) });
  const { data: forms } = useQuery({ queryKey: ['forms-list'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams } = useQuery({ queryKey: ['streams-list'], queryFn: () => api.get('/streams').then(r => r.data) });
  const classOptions = (streams ?? []).filter((s: any) => !form.form_id || String(s.form_id) === String(form.form_id));

  const openVerify = async (s: any) => {
    setVerifyStudent(s);
    setChecklistLoading(true);
    try {
      const r = await api.get(`/admin/students/${s.id}/document-checklist`);
      setChecklist(r.data?.checklist ?? []);
    } catch {
      setChecklist([]);
    } finally {
      setChecklistLoading(false);
    }
  };

  const verify = useMutation({
    mutationFn: (id: number) => api.post(`/admin/students/${id}/verify-documents`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['students'] });
      setVerifyStudent(null);
      toastSuccess('Documents marked as verified.');
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to verify documents.'),
  });

  const students = data?.data ?? [];
  const pendingVerificationCount = students.filter((s: any) => !s.document_verified_at).length;

  const create = useMutation({
    mutationFn: (d: any) => api.post('/students', d),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['students'] });
      setOpen(false);
      const sn = res.data?.student_number;
      toastSuccess(`Student enrolled! Student Number: ${sn ?? 'generated'}`);
    },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Students" subtitle="All enrolled students"
        action={<Btn onClick={() => setOpen(true)}>+ Enroll Student</Btn>} />
      {msg && <Alert type="success" message={msg} />}

      {pendingVerificationCount > 0 && (
        <div style={{ marginBottom: 16, maxWidth: 280 }}>
          <StatCard label="Pending Verification" value={pendingVerificationCount} color="amber"
            trend="Students awaiting document verification" />
        </div>
      )}

      <div style={{ marginBottom: 16 }}>
        <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search by name or admission number…"
          style={{ padding: '9px 14px', border: '1px solid #d1d5db', borderRadius: 8, fontSize: 13, width: 300, outline: 'none' }} />
      </div>

      <Card>
        <Table headers={['Student #', 'Name', 'Class', 'Category', 'Gender', 'Status', 'Actions']}>
          {students.map((s: any) => (
            <tr key={s.id}>
              <Td>
                <div>
                  <code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11, display: 'block', marginBottom: 2 }}>{s.student_number ?? '—'}</code>
                  <code style={{ background: '#f1f5f9', color: '#475569', padding: '2px 7px', borderRadius: 5, fontSize: 10 }}>{s.admission_number}</code>
                </div>
              </Td>
              <Td><strong>{s.first_name} {s.last_name}</strong></Td>
              <Td>{s.class_name ?? '—'}</Td>
              <Td>{s.resolved_category_name ?? '—'}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{s.gender ?? '—'}</Td>
              <Td>
                <div style={{ display: 'grid', gap: 4 }}>
                  <div>{statusBadge(s.status)}</div>
                  {!s.document_verified_at && (
                    <span style={{
                      display: 'inline-flex', alignItems: 'center', padding: '2px 8px', borderRadius: 999,
                      fontSize: 10, fontWeight: 800, color: '#92400e', background: '#fffbeb', border: '1px solid #fde68a', width: 'fit-content',
                    }}>Unverified</span>
                  )}
                </div>
              </Td>
              <Td>
                <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                  <Link to={`/app/students/${s.id}`} style={{ fontSize: 12, color: '#8a6b34', textDecoration: 'none', fontWeight: 500 }}>View →</Link>
                  {!s.document_verified_at && (
                    <Btn size="sm" variant="outline" onClick={() => openVerify(s)}>Verify Docs</Btn>
                  )}
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="Enroll New Student">
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={form.first_name} onChange={e => setForm(f => ({...f, first_name: e.target.value}))} required /></FormGroup>
          <FormGroup label="Last Name"><Input value={form.last_name} onChange={e => setForm(f => ({...f, last_name: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Email"><Input type="email" value={form.email} onChange={e => setForm(f => ({...f, email: e.target.value}))} /></FormGroup>
          <FormGroup label="Date of Birth"><Input type="date" value={form.date_of_birth} onChange={e => setForm(f => ({...f, date_of_birth: e.target.value}))} /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Gender">
            <Select value={form.gender} onChange={e => setForm(f => ({...f, gender: e.target.value}))}>
              <option value="">Select…</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </Select>
          </FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Form">
            <Select value={form.form_id} onChange={e => setForm(f => ({...f, form_id: e.target.value, stream_id: ''}))}>
              <option value="">Select form…</option>
              {forms?.map((f: any) => <option key={f.id} value={f.id}>{f.name}</option>)}
            </Select>
          </FormGroup>
          <FormGroup label="Class">
            <Select value={form.stream_id} onChange={e => setForm(f => ({...f, stream_id: e.target.value}))}>
              <option value="">Select class…</option>
              {classOptions.map((s: any) => <option key={s.id} value={s.id}>{s.name}{s.category_name ? ` — ${s.category_name}` : ''}</option>)}
            </Select>
          </FormGroup>
        </Grid>
        <FormGroup label="Admission Date"><Input type="date" value={form.admission_date} onChange={e => setForm(f => ({...f, admission_date: e.target.value}))} required /></FormGroup>
        <FormGroup label="National ID">
          <Input value={form.national_id} onChange={e => setForm(f => ({...f, national_id: e.target.value}))} placeholder="e.g. 63-123456A78" />
          <p style={{ fontSize: 11, color: '#059669', marginTop: 4, background: '#eef1f8', padding: '6px 10px', borderRadius: 6 }}>
            🔐 Student will login using their <strong>Student Number</strong> as username and <strong>National ID as default password</strong>. Student number is auto-generated on save.
          </p>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Enroll Student</Btn>
        </div>
      </Modal>

      <Modal open={!!verifyStudent} onClose={() => setVerifyStudent(null)} title="Verify Documents" maxWidth={520}>
        {verifyStudent && (
          <div>
            <div style={{ fontSize: 13, fontWeight: 700, marginBottom: 4 }}>{verifyStudent.first_name} {verifyStudent.last_name}</div>
            <div style={{ fontSize: 12, color: '#64748b', marginBottom: 14 }}>
              Compare each physical document against what was uploaded during application before confirming.
            </div>
            {checklistLoading ? (
              <Spinner />
            ) : (
              <div style={{ display: 'grid', gap: 8, marginBottom: 16 }}>
                {checklist.map((c: any) => (
                  <div key={c.key} style={{
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                    padding: '10px 12px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#f8fafc',
                  }}>
                    <span style={{ fontSize: 13, color: '#0f172a' }}>{c.label}</span>
                    <span style={{
                      fontSize: 11, fontWeight: 800, padding: '2px 8px', borderRadius: 999,
                      color: c.status === 'fulfilled' || c.present ? '#166534' : '#9a3412',
                      background: c.status === 'fulfilled' || c.present ? '#dcfce7' : '#ffedd5',
                    }}>
                      {c.status === 'fulfilled' ? 'Resubmitted' : c.present ? 'Submitted' : 'Missing'}
                    </span>
                  </div>
                ))}
              </div>
            )}
            <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
              <Btn variant="outline" onClick={() => setVerifyStudent(null)}>Cancel</Btn>
              <Btn loading={verify.isPending} onClick={() => verify.mutate(verifyStudent.id)}>Confirm Verified</Btn>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
