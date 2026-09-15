import React, { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toastSuccess, toastError } from '../lib/toast';
import api from '../lib/api';
import { Card, CardHeader, CardBody, Table, Td, Spinner, Alert, Btn, statusBadge, Badge, Grid, FormGroup, Input, Select, Modal } from '../components/UI';

export default function StudentDetail() {
  const { id } = useParams();
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [guardianOpen, setGuardianOpen] = useState(false);
  const [gForm, setGForm] = useState({ first_name:'', last_name:'', phone:'', email:'', relationship:'', is_primary_contact: false });
  const [classAssignOpen, setClassAssignOpen] = useState(false);
  const [classForm, setClassForm] = useState({ form_id: '', stream_id: '' });

  const { data: student, isLoading } = useQuery({ queryKey: ['student', id], queryFn: () => api.get(`/students/${id}`).then(r => r.data) });
  const { data: forms } = useQuery({ queryKey: ['forms-list'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams } = useQuery({ queryKey: ['streams-list'], queryFn: () => api.get('/streams').then(r => r.data) });
  const classOptions = (streams ?? []).filter((s: any) => !classForm.form_id || String(s.form_id) === String(classForm.form_id));

  const openClassAssign = () => {
    setClassForm({
      form_id: student.resolved_form_id != null ? String(student.resolved_form_id) : '',
      stream_id: student.resolved_stream_id != null ? String(student.resolved_stream_id) : '',
    });
    setClassAssignOpen(true);
  };

  const assignClass = useMutation({
    mutationFn: (streamId: string) => api.patch(`/students/${id}`, {
      first_name: student.first_name,
      last_name: student.last_name,
      email: student.email,
      date_of_birth: student.date_of_birth,
      gender: student.gender,
      status: student.status,
      blood_type: student.blood_type,
      allergies: student.allergies,
      medical_conditions: student.medical_conditions,
      stream_id: streamId || null,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['student', id] });
      setClassAssignOpen(false);
      toastSuccess('Class updated.');
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to update class.'),
  });

  // Full assessment history (all terms/years/subjects) — richer than the legacy
  // academic_progress table below. Some roles (e.g. bursar) can't see this; fail quietly.
  const { data: allResults } = useQuery({
    queryKey: ['student-results', id],
    queryFn: () => api.get(`/assessments/student/${id}/marks`).then(r => r.data),
    retry: false,
  });

  const { data: enrollmentHistory } = useQuery({
    queryKey: ['student-enrollment-history', id],
    queryFn: () => api.get(`/students/${id}/enrollment-history`).then(r => r.data),
    retry: false,
  });
  const roadmap = enrollmentHistory?.roadmap;

  // Finance module is admin/headmaster only — bursar/teacher will 403 here; fail quietly.
  const { data: balanceData } = useQuery({
    queryKey: ['student-balance', id],
    queryFn: () => api.get(`/finance/student/${id}/balance`).then(r => r.data),
    retry: false,
  });
  const { data: statement } = useQuery({
    queryKey: ['student-statement', id],
    queryFn: () => api.get(`/finance/reports/student/${id}/statement`).then(r => r.data),
    retry: false,
  });

  const money = (n: number) => `$${Math.abs(n).toFixed(2)}`;
  const signedMoney = (n: number) => `${n < 0 ? '-' : ''}$${Math.abs(n).toFixed(2)}`;

  const docUrl = (path?: string | null) => {
    if (!path) return null;
    const p = String(path).replace(/^\/+/, '');
    return `/documents/${p.startsWith('storage/') ? p.slice('storage/'.length) : p}`;
  };
  const openDoc = async (path: string) => {
    try {
      const res = await api.get(path, { responseType: 'blob' });
      const blobUrl = URL.createObjectURL(res.data);
      window.open(blobUrl, '_blank', 'noopener');
      setTimeout(() => URL.revokeObjectURL(blobUrl), 60000);
    } catch {
      toastError('Could not open document.');
    }
  };
  const docLabel = (k: string) => ({
    doc_student_id_path: 'Student ID / Birth Certificate',
    doc_results_path: 'Results',
    doc_parent_id_path: 'Parent ID / Birth',
    doc_transfer_letter_path: 'Transfer Letter',
  } as Record<string, string>)[k] ?? k;

  const addGuardian = useMutation({
    mutationFn: (d: any) => api.post(`/students/${id}/guardians`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['student', id] }); setGuardianOpen(false); setMsg('Guardian added.'); },
  });

  if (isLoading) return <Spinner />;
  if (!student) return <div>Student not found.</div>;

  const initials = `${student.first_name?.[0] ?? ''}${student.last_name?.[0] ?? ''}`.toUpperCase();
  const balance = balanceData?.balance ?? 0;
  const balanceBadge = balance > 0
    ? <Badge variant="red">{money(balance)} owed</Badge>
    : balanceData?.is_billed
      ? <Badge variant="green">Fully Paid</Badge>
      : balanceData
        ? <Badge variant="gray">Not Yet Billed</Badge>
        : null;

  return (
    <div>
      <Link to="/app/students" style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 12, color: '#64748b', textDecoration: 'none', marginBottom: 12, fontWeight: 600 }}>
        ← All Students
      </Link>

      {/* Profile hero — everything a staff member checks first, in one glance */}
      <div style={{
        display: 'flex', alignItems: 'center', gap: 18, flexWrap: 'wrap',
        background: '#fff', border: '1px solid #e2e8f0', borderRadius: 12,
        boxShadow: '0 1px 3px rgba(0,0,0,.05)', padding: '20px 24px', marginBottom: 20,
      }}>
        <div style={{
          width: 56, height: 56, borderRadius: '50%', flexShrink: 0,
          background: '#f0faf4', color: '#1a6b3c',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          fontSize: 20, fontWeight: 800, letterSpacing: '-.5px',
        }}>
          {initials || '?'}
        </div>
        <div style={{ flex: 1, minWidth: 200 }}>
          <div style={{ fontSize: 20, fontWeight: 800, color: '#0f172a', lineHeight: 1.2 }}>{student.first_name} {student.last_name}</div>
          <div style={{ fontSize: 13, color: '#64748b', marginTop: 4 }}>
            <code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11, fontWeight: 700 }}>{student.student_number ?? student.admission_number}</code>
            {' · '}
            {student.resolved_form_name ? `${student.resolved_form_name}${student.resolved_stream_name ? ` ${student.resolved_stream_name}` : ''}` : 'Unassigned'}
            {student.resolved_category_name ? ` · ${student.resolved_category_name}` : ''}
          </div>
        </div>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
          {statusBadge(student.status)}
          {roadmap && <Badge variant="blue">{roadmap.track_label}</Badge>}
          {balanceBadge}
        </div>
      </div>

      {msg && <Alert type="success" message={msg} />}

      <Grid cols={2}>
        {/* Info */}
        <Card>
          <CardHeader title="Personal Information" />
          <CardBody>
            <Grid cols={2} style={{ marginBottom: 0, rowGap: 14 }}>
              {[
                ['Email', student.email ?? '—'],
                ['National ID', student.national_id ?? '—'],
                ['Date of Birth', student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString() : '—'],
                ['Gender', student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : '—'],
                ['Admission Date', new Date(student.admission_date).toLocaleDateString()],
                ['Blood Type', student.blood_type ?? '—'],
                ['Allergies', student.allergies ?? '—'],
                ['Medical Conditions', student.medical_conditions ?? '—'],
              ].map(([k, v]) => (
                <div key={k}>
                  <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.3px' }}>{k}</div>
                  <div style={{ fontSize: 14, fontWeight: 600, color: '#111827', marginTop: 3 }}>{v}</div>
                </div>
              ))}
            </Grid>
          </CardBody>
        </Card>

        {/* Guardians */}
        <Card>
          <CardHeader title="Guardians" action={<Btn size="sm" onClick={() => setGuardianOpen(true)}>+ Add</Btn>} />
          <CardBody>
            {student.guardians?.length === 0 && <p style={{ color: '#9ca3af', fontSize: 13 }}>No guardians added yet.</p>}
            {student.guardians?.map((g: any) => (
              <div key={g.id} style={{ padding: '10px 0', borderBottom: '1px solid #f3f4f6' }}>
                <div style={{ fontWeight: 700, fontSize: 13, color: '#0f172a' }}>{g.first_name} {g.last_name}
                  {g.is_primary_contact && <Badge variant="green" style={{ marginLeft: 8 }}>Primary</Badge>}
                </div>
                <div style={{ fontSize: 12, color: '#6b7280', marginTop: 3 }}>{g.relationship} · {g.phone}{g.email ? ` · ${g.email}` : ''}</div>
              </div>
            ))}
          </CardBody>
        </Card>
      </Grid>

      {/* Class — Form + Class (Category) assignment */}
      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Class" action={<Btn size="sm" variant="outline" onClick={openClassAssign}>{student.stream_is_mapped ? 'Change' : 'Assign'}</Btn>} />
        <CardBody>
          {student.stream_is_mapped ? (
            <Grid cols={3} style={{ marginBottom: 0 }}>
              <div>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Form</div>
                <div style={{ fontSize: 14, fontWeight: 700, color: '#111827', marginTop: 4 }}>{student.resolved_form_name ?? '—'}</div>
              </div>
              <div>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Class</div>
                <div style={{ fontSize: 14, fontWeight: 700, color: '#111827', marginTop: 4 }}>{student.resolved_stream_name ?? '—'}</div>
              </div>
              <div>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Category</div>
                <div style={{ fontSize: 14, fontWeight: 700, color: '#111827', marginTop: 4 }}>{student.resolved_category_name ?? '—'}</div>
              </div>
            </Grid>
          ) : (
            <p style={{ color: '#9ca3af', fontSize: 13 }}>Unassigned — this student isn't placed in a class yet.</p>
          )}
        </CardBody>
      </Card>

      {/* Enrollment History — expected O-Level/A-Level progression to graduation, plus any
          recorded per-term placement history */}
      {roadmap && (
        <Card style={{ marginBottom: 20 }}>
          <CardHeader
            title="Enrollment History"
            action={<Badge variant="blue">{roadmap.track_label} track — stage {(roadmap.current_index ?? 0) + 1} of {roadmap.total_stages}</Badge>}
          />
          <CardBody style={{ padding: 0 }}>
            <div style={{ padding: '14px 20px', borderBottom: '1px solid #f1f5f9' }}>
              <div style={{ fontSize: 13, color: '#374151', marginBottom: 10 }}>
                Currently <strong>{roadmap.current_form_name}, {roadmap.current_term_name}</strong>.{' '}
                {roadmap.is_at_graduation
                  ? <span style={{ color: '#166534', fontWeight: 700 }}>🎓 At graduation stage — {roadmap.graduation_label}.</span>
                  : <>Graduates at <strong>{roadmap.graduation_label}</strong>.</>}
              </div>

              {/* Progress bar — one segment per stage, so the graduation milestone at the
                  far end stays visible regardless of how many stages there are */}
              <div style={{ display: 'flex', gap: 3 }}>
                {roadmap.stages.map((s: any) => (
                  <div key={s.index} title={`${s.form_name}, Term ${s.term_number} (${s.expected_year})`} style={{
                    flex: 1, height: 8, borderRadius: 4,
                    background: s.status === 'upcoming' ? '#e2e8f0' : s.status === 'current' ? '#1a6b3c' : '#86d4a4',
                    outline: s.status === 'current' ? '2px solid #0f3d21' : 'none',
                    outlineOffset: 1,
                  }} />
                ))}
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: '#9ca3af', marginTop: 4 }}>
                <span>{roadmap.stages[0].form_name} · {roadmap.stages[0].expected_year}</span>
                <span>{roadmap.graduation_label.split(',')[0]} · {roadmap.stages[roadmap.stages.length - 1].expected_year} 🎓</span>
              </div>
            </div>

            <Table headers={['Stage', 'Form', 'Term', 'Year', 'Status', 'Milestone']}>
              {roadmap.stages.map((s: any) => (
                <tr key={s.index} style={s.status === 'current' ? { background: '#f0faf4' } : undefined}>
                  <Td style={{ color: '#9ca3af' }}>{s.index + 1}</Td>
                  <Td style={{ fontWeight: 600 }}>{s.form_name}</Td>
                  <Td>Term {s.term_number}</Td>
                  <Td>{s.expected_year}</Td>
                  <Td>
                    {s.status === 'completed' && <Badge variant="green">Completed</Badge>}
                    {s.status === 'current' && <Badge variant="blue">Current</Badge>}
                    {s.status === 'upcoming' && <Badge variant="gray">Upcoming</Badge>}
                  </Td>
                  <Td>{s.is_graduation ? <strong style={{ color: '#166534' }}>🎓 Graduation</strong> : '—'}</Td>
                </tr>
              ))}
            </Table>

            {enrollmentHistory?.history?.length > 0 && (
              <div style={{ padding: '18px 20px' }}>
                <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', marginBottom: 8 }}>Recorded Placement History</div>
                <Table headers={['Academic Year', 'Term', 'Form', 'Class', 'Status']}>
                  {enrollmentHistory.history.map((h: any) => (
                    <tr key={h.id}>
                      <Td>{h.academic_year_name}</Td>
                      <Td>{h.term_name}</Td>
                      <Td>{h.form_name}</Td>
                      <Td>{h.stream_name}</Td>
                      <Td>{statusBadge(h.enrollment_status)}</Td>
                    </tr>
                  ))}
                </Table>
              </div>
            )}
          </CardBody>
        </Card>
      )}

      {/* Financial Summary — balance owed + full transaction ledger */}
      {(balanceData || statement) && (
        <Card style={{ marginBottom: 20 }}>
          <CardHeader
            title="Financials"
            action={
              <div style={{ textAlign: 'right' }}>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.5px' }}>Balance</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: (balanceData?.balance ?? 0) > 0 ? '#dc2626' : balanceData?.is_billed ? '#166534' : '#6b7280' }}>
                  {(balanceData?.balance ?? 0) > 0 && `${money(balanceData.balance)} owed`}
                  {(balanceData?.balance ?? 0) < 0 && `${signedMoney(balanceData.balance)} credit`}
                  {(balanceData?.balance ?? 0) === 0 && (balanceData?.is_billed ? 'Fully Paid' : 'Not Yet Billed')}
                </div>
              </div>
            }
          />
          <CardBody>
            <Grid cols={3} style={{ marginBottom: 16 }}>
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, padding: '10px 14px' }}>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Total Billed</div>
                <div style={{ fontSize: 16, fontWeight: 700, color: '#111827', marginTop: 4 }}>{money(statement?.summary?.total_billed ?? 0)}</div>
              </div>
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, padding: '10px 14px' }}>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Total Paid</div>
                <div style={{ fontSize: 16, fontWeight: 700, color: '#166534', marginTop: 4 }}>{money(statement?.summary?.total_paid ?? 0)}</div>
              </div>
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, padding: '10px 14px' }}>
                <div style={{ fontSize: 11, color: '#6b7280', fontWeight: 600, textTransform: 'uppercase' }}>Outstanding</div>
                <div style={{ fontSize: 16, fontWeight: 700, color: (statement?.summary?.total_balance ?? 0) > 0 ? '#dc2626' : '#166534', marginTop: 4 }}>
                  {money(statement?.summary?.total_balance ?? 0)}
                </div>
              </div>
            </Grid>

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', marginBottom: 8 }}>Transaction History</div>
            <Table headers={['Date', 'Description', 'Type', 'Amount', 'Balance After']}>
              {(!statement?.transactions || statement.transactions.length === 0) && (
                <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af' }}>No transactions recorded yet</Td></tr>
              )}
              {statement?.transactions?.map((t: any) => {
                // Ledger convention: a bill (debit) increases what's owed — shown positive.
                // A payment (credit) reduces what's owed — shown negative.
                const signedAmount = Number(t.debit ?? 0) - Number(t.credit ?? 0);
                return (
                  <tr key={t.id}>
                    <Td>{new Date(t.created_at).toLocaleDateString()}</Td>
                    <Td>{t.description}</Td>
                    <Td style={{ textTransform: 'capitalize' }}>{t.transaction_type}</Td>
                    <Td style={{ color: signedAmount > 0 ? '#dc2626' : signedAmount < 0 ? '#166534' : '#111827', fontWeight: 700 }}>
                      {signedAmount > 0 ? '+' : ''}{signedMoney(signedAmount)}
                    </Td>
                    <Td>{signedMoney(Number(t.balance_after))}</Td>
                  </tr>
                );
              })}
            </Table>
          </CardBody>
        </Card>
      )}

      {/* Application Documents — uploaded when this student applied, before enrollment */}
      {student.application_documents && (
        <Card style={{ marginBottom: 20 }}>
          <CardHeader title="Documents Uploaded During Application" />
          <CardBody>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              {['doc_student_id_path', 'doc_results_path', 'doc_parent_id_path', 'doc_transfer_letter_path'].map((key) => {
                const path = student.application_documents[key];
                const url = docUrl(path);
                return (
                  <div key={key} style={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10,
                    padding: '10px 12px', border: '1px solid #eef2f7', borderRadius: 8,
                  }}>
                    <span style={{ fontSize: 13, color: '#111827' }}>{docLabel(key)}</span>
                    {url ? (
                      <button type="button" onClick={() => openDoc(url)} style={{ fontSize: 12, color: '#1a6b3c', fontWeight: 700, background: 'none', border: 'none', cursor: 'pointer', textDecoration: 'none' }}>
                        Open →
                      </button>
                    ) : (
                      <span style={{ fontSize: 11, color: '#9ca3af' }}>Not uploaded</span>
                    )}
                  </div>
                );
              })}
            </Grid>
          </CardBody>
        </Card>
      )}

      {/* All Results — every assessment across every term and academic year */}
      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="All Results" />
        <Table headers={['Subject', 'Academic Year', 'Term', 'Assessment', 'Type', 'Marks', '%', 'Grade']}>
          {(!allResults || allResults.length === 0) && (
            <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af' }}>No assessment results recorded yet</Td></tr>
          )}
          {allResults?.map((r: any) => (
            <tr key={r.id}>
              <Td>{r.subject_name}</Td>
              <Td>{r.academic_year_name}</Td>
              <Td>{r.term_name}</Td>
              <Td>{r.title}{r.assessment_number ? ` #${r.assessment_number}` : ''}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{r.type_name}</Td>
              <Td>{r.mark_obtained}/{r.total_marks}</Td>
              <Td>{r.percentage != null ? `${r.percentage}%` : '—'}</Td>
              <Td><strong>{r.grade ?? '—'}</strong></Td>
            </tr>
          ))}
        </Table>
      </Card>

      {/* Academic Progress */}
      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Academic Progress" />
        <Table headers={['Subject', 'Term', 'Type', 'Marks', '%', 'Grade']}>
          {student.academic_progress?.length === 0 && (
            <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af' }}>No marks recorded yet</Td></tr>
          )}
          {student.academic_progress?.map((p: any) => (
            <tr key={p.id}>
              <Td>{p.subject?.subject_name}</Td>
              <Td>{p.term?.toUpperCase()}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{p.assessment_type?.replace(/_/g, ' ')}</Td>
              <Td>{p.marks}/{p.total_marks}</Td>
              <Td>{p.percentage}%</Td>
              <Td><strong>{p.grade}</strong></Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Grid cols={2}>
        {/* Attendance */}
        <Card>
          <CardHeader title="Recent Attendance" />
          <Table headers={['Date', 'Status']}>
            {student.attendance?.slice(0, 10).map((a: any) => (
              <tr key={a.id}>
                <Td>{new Date(a.date).toLocaleDateString()}</Td>
                <Td>{statusBadge(a.status)}</Td>
              </tr>
            ))}
          </Table>
        </Card>

        {/* Behaviour */}
        <Card>
          <CardHeader title="Behaviour Records" />
          <Table headers={['Date', 'Issue', 'Severity']}>
            {student.behaviour_records?.map((b: any) => (
              <tr key={b.id}>
                <Td>{new Date(b.issue_date).toLocaleDateString()}</Td>
                <Td>{b.issue_type}</Td>
                <Td>{statusBadge(b.severity)}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </Grid>

      {/* Add Guardian Modal */}
      <Modal open={guardianOpen} onClose={() => setGuardianOpen(false)} title="Add Guardian">
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={gForm.first_name} onChange={e => setGForm(f => ({...f, first_name: e.target.value}))} required /></FormGroup>
          <FormGroup label="Last Name"><Input value={gForm.last_name} onChange={e => setGForm(f => ({...f, last_name: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Phone"><Input value={gForm.phone} onChange={e => setGForm(f => ({...f, phone: e.target.value}))} required /></FormGroup>
          <FormGroup label="Relationship"><Input value={gForm.relationship} onChange={e => setGForm(f => ({...f, relationship: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="National ID">
            <Input value={(gForm as any).national_id ?? ''} onChange={e => setGForm(f => ({...f, national_id: e.target.value} as any))} placeholder="e.g. 63-123456A78" />
          </FormGroup>
          <FormGroup label="Email (optional)">
            <Input type="email" value={gForm.email} onChange={e => setGForm(f => ({...f, email: e.target.value}))} placeholder="For password reset" />
          </FormGroup>
        </Grid>
        <div style={{ background: '#f0faf4', border: '1px solid #d1fae5', borderRadius: 8, padding: '8px 12px', marginBottom: 12, fontSize: 12, color: '#065f46' }}>
          🔐 Parent will login using <strong>phone number</strong> as username and <strong>National ID as default password</strong>. Email is optional but required for password reset.
        </div>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setGuardianOpen(false)}>Cancel</Btn>
          <Btn loading={addGuardian.isPending} onClick={() => addGuardian.mutate(gForm)}>Add Guardian</Btn>
        </div>
      </Modal>

      {/* Assign / Change Class */}
      <Modal open={classAssignOpen} onClose={() => setClassAssignOpen(false)} title="Assign Class">
        <FormGroup label="Form">
          <Select value={classForm.form_id} onChange={e => setClassForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}>
            <option value="">Select form…</option>
            {forms?.map((f: any) => <option key={f.id} value={f.id}>{f.name}</option>)}
          </Select>
        </FormGroup>
        <FormGroup label="Class">
          <Select value={classForm.stream_id} onChange={e => setClassForm(f => ({ ...f, stream_id: e.target.value }))}>
            <option value="">Select class…</option>
            {classOptions.map((s: any) => <option key={s.id} value={s.id}>{s.name}{s.category_name ? ` — ${s.category_name}` : ''}</option>)}
          </Select>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setClassAssignOpen(false)}>Cancel</Btn>
          <Btn loading={assignClass.isPending} onClick={() => assignClass.mutate(classForm.stream_id)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
