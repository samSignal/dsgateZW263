import React, { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { toastSuccess, toastError } from '../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Modal, FormGroup, Input, Select, Grid, StatCard, Badge } from '../components/UI';

const IMPORT_COLUMNS = [
  'first_name', 'last_name', 'gender', 'date_of_birth', 'national_id', 'email',
  'form', 'class', 'category', 'blood_type', 'allergies', 'medical_conditions',
  'term', 'previous_balance_owed', 'amount_paid_now', 'student_number',
  'guardian_first_name', 'guardian_last_name', 'guardian_phone', 'guardian_email',
  'guardian_national_id', 'guardian_relationship', 'guardian_address',
];

function csvCell(v: string) {
  return /[",\n]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v;
}

const PAGE_SIZES = [20, 50, 100];

function downloadImportTemplate() {
  const rows = [
    ['Grace', 'Moyo', 'female', '2011-03-14', '63-1112223A33', '', 'Form 3', 'B', '', '', '', '', '', '', '', '', 'Farai', 'Moyo', '0771234567', 'farai@example.com', '', 'Father', '12 Baker Street, Harare'],
    ['Peter', 'Ncube', 'male', '2010-07-01', '', '', 'Form 5', '', 'Sciences', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
    ['Tendai', 'Chikafu', 'female', '2009-11-02', '', '', 'Form 2', 'A', '', '', '', '', 'Term 2', '150', '100', '', '', '', '', '', '', '', ''],
  ];
  const csv = [IMPORT_COLUMNS, ...rows].map(r => r.map(csvCell).join(',')).join('\n') + '\n';
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'student_import_template.csv';
  a.click();
  URL.revokeObjectURL(url);
}

export default function Students() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [importOpen, setImportOpen] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [importResults, setImportResults] = useState<any | null>(null);
  const [msg, setMsg] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(PAGE_SIZES[0]);
  const [form, setForm] = useState({ first_name:'', last_name:'', email:'', date_of_birth:'', gender:'', form_id:'', stream_id:'', admission_date: new Date().toISOString().split('T')[0], national_id:'', blood_type:'', allergies:'', medical_conditions:'' });
  const [verifyStudent, setVerifyStudent] = useState<any | null>(null);
  const [checklist, setChecklist] = useState<any[]>([]);
  const [checklistLoading, setChecklistLoading] = useState(false);

  // A new search always starts back on page 1 rather than stranding the viewer on an
  // out-of-range page from the previous, larger result set.
  useEffect(() => { setPage(1); }, [search, perPage]);

  const { data, isLoading } = useQuery({
    queryKey: ['students', search, page, perPage],
    queryFn: () => api.get('/students', { params: { search, page, per_page: perPage } }).then(r => r.data),
    placeholderData: keepPreviousData, // keeps the current page's rows on screen while the next page loads, instead of a full-page spinner flash on every click
  });
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
  const total = data?.total ?? 0;
  const lastPage = data?.last_page ?? 1;
  const pendingVerificationCount = data?.pending_verification_count ?? 0;

  const create = useMutation({
    mutationFn: (d: any) => api.post('/students', d),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['students'] });
      setOpen(false);
      const sn = res.data?.student_number;
      toastSuccess(`Student enrolled! Student Number: ${sn ?? 'generated'}`);
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to enroll student.'),
  });

  const bulkImport = useMutation({
    mutationFn: (file: File) => {
      const fd = new FormData();
      fd.append('csv', file);
      // Instance default forces Content-Type: application/json — clear it so the
      // browser can set the correct multipart boundary itself for this one request.
      return api.post('/students/bulk-import', fd, { headers: { 'Content-Type': undefined } });
    },
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['students'] });
      setImportResults(res.data);
      toastSuccess(res.data.message);
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Import failed.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Students" subtitle="All enrolled students"
        action={
          <div style={{ display: 'flex', gap: 8 }}>
            <Btn variant="outline" onClick={() => { setImportResults(null); setImportFile(null); setImportOpen(true); }}>⬆ Import Existing Students</Btn>
            <Btn onClick={() => setOpen(true)}>+ Enroll Student</Btn>
          </div>
        } />
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
        <Table headers={['#', 'Student #', 'Name', 'Class', 'Category', 'Gender', 'Status', 'Actions']}>
          {students.length === 0 && (
            <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af' }}>No students match this search.</Td></tr>
          )}
          {students.map((s: any, i: number) => (
            <tr key={s.id}>
              <Td style={{ color: '#9ca3af' }}>{(page - 1) * perPage + i + 1}</Td>
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
                  <Link to={`/app/students/${s.id}`} style={{ fontSize: 12, color: '#1a6b3c', textDecoration: 'none', fontWeight: 500 }}>View →</Link>
                  {!s.document_verified_at && (
                    <Btn size="sm" variant="outline" onClick={() => openVerify(s)}>Verify Docs</Btn>
                  )}
                </div>
              </Td>
            </tr>
          ))}
        </Table>

        {total > 0 && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid #f1f5f9' }}>
            <div style={{ fontSize: 12, color: '#6b7280' }}>
              Showing {(page - 1) * perPage + 1}–{Math.min(page * perPage, total)} of {total}
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <Select value={String(perPage)} onChange={e => setPerPage(Number(e.target.value))} style={{ width: 100 }}>
                {PAGE_SIZES.map(n => <option key={n} value={n}>{n} / page</option>)}
              </Select>
              <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))}>← Prev</Btn>
              <span style={{ fontSize: 12, color: '#374151', minWidth: 70, textAlign: 'center' }}>Page {page} of {lastPage}</span>
              <Btn size="sm" variant="outline" disabled={page >= lastPage} onClick={() => setPage(p => Math.min(lastPage, p + 1))}>Next →</Btn>
            </div>
          </div>
        )}
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
          <p style={{ fontSize: 11, color: '#059669', marginTop: 4, background: '#f0faf4', padding: '6px 10px', borderRadius: 6 }}>
            🔐 Student will login using their <strong>Student Number</strong> as username and <strong>National ID as default password</strong>. Student number is auto-generated on save.
          </p>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Enroll Student</Btn>
        </div>
      </Modal>

      <Modal open={importOpen} onClose={() => setImportOpen(false)} title="Import Existing Students" maxWidth={720}>
        <p style={{ fontSize: 13, color: '#475569', marginTop: 0, marginBottom: 16, lineHeight: 1.5 }}>
          For students who were already attending the school before it started using this system. Each row creates a student and a login (username = student number, password = National ID). Fees are <strong>not</strong> auto-billed for imported students — generate their bills yourself from the Finance → Generate Bills screen once you're ready, so you don't risk double-charging a family for a term the school already collected outside this system.
        </p>

        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16, padding: '12px 14px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8 }}>
          <div style={{ flex: 1, fontSize: 12, color: '#475569', lineHeight: 1.6 }}>
            Columns: <code>{IMPORT_COLUMNS.join(', ')}</code>.<br />
            Only <strong>first_name</strong>, <strong>last_name</strong> and <strong>form</strong> (e.g. "Form 1" — determines the student number) are required. Everything else can be left blank.
            <strong> category</strong> is a subject combination like "Sciences", independent of class — useful when a form (like a new Form 5/6) doesn't have classes set up yet.
            <strong> previous_balance_owed</strong>/<strong>amount_paid_now</strong> record a carried-over debt and any payment already collected before this system — both need <strong>term</strong> filled in if either is used.
            <strong> guardian_*</strong> columns add one primary parent/guardian with portal login — <strong>guardian_phone</strong> is required if you give a guardian name (it's their username); leave all guardian columns blank if there's no parent info yet.
            Leave <strong>student_number</strong> blank to auto-generate one, or fill it in to keep an existing code.
          </div>
          <Btn size="sm" variant="outline" onClick={downloadImportTemplate}>Download Template</Btn>
        </div>

        <FormGroup label="CSV File">
          <input
            type="file"
            accept=".csv,text/csv"
            onChange={e => setImportFile(e.target.files?.[0] ?? null)}
            style={{ width: '100%', padding: '9px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}
          />
        </FormGroup>

        {importResults && (
          <div style={{ marginTop: 8, marginBottom: 16 }}>
            <div style={{ display: 'flex', gap: 8, marginBottom: 10 }}>
              <Badge variant="green">{importResults.created} created</Badge>
              {importResults.failed > 0 && <Badge variant="red">{importResults.failed} failed</Badge>}
            </div>
            <Table headers={['Row', 'Name', 'Status', 'Student #', 'Note']}>
              {importResults.results.map((r: any) => (
                <tr key={r.row}>
                  <Td>{r.row}</Td>
                  <Td>{r.name}</Td>
                  <Td><Badge variant={r.status === 'created' ? 'green' : 'red'}>{r.status === 'created' ? 'Created' : 'Failed'}</Badge></Td>
                  <Td>{r.student_number ? <code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11 }}>{r.student_number}</code> : '—'}</Td>
                  <Td style={{ fontSize: 12, color: r.status === 'error' ? '#dc2626' : '#92400e' }}>{r.message ?? '—'}</Td>
                </tr>
              ))}
            </Table>
          </div>
        )}

        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setImportOpen(false)}>Close</Btn>
          <Btn loading={bulkImport.isPending} disabled={!importFile} onClick={() => importFile && bulkImport.mutate(importFile)}>Upload &amp; Import</Btn>
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
