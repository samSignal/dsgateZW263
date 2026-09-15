import React, { useEffect, useMemo, useState } from 'react';
import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { downloadReport } from '../lib/download';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, FormGroup, Select, Grid, Badge, statusBadge } from '../components/UI';

const PAGE_SIZES = [20, 50, 100];

export default function ClassListPage() {
  const [formId, setFormId] = useState('');
  const [streamId, setStreamId] = useState('');
  const [status, setStatus] = useState('active');
  const [gender, setGender] = useState('');
  const [search, setSearch] = useState('');
  const [downloading, setDownloading] = useState<'csv' | 'pdf' | null>(null);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(PAGE_SIZES[0]);

  const { data: forms = [] } = useQuery({ queryKey: ['forms-list'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams-list'], queryFn: () => api.get('/streams').then(r => r.data) });
  const filteredStreams = (streams as any[]).filter(s => !formId || String(s.form_id) === formId);

  const params = {
    form_id: formId || undefined,
    stream_id: streamId || undefined,
    status: status || undefined,
    gender: gender || undefined,
    search: search || undefined,
  };

  const { data: students = [], isLoading } = useQuery({
    queryKey: ['class-list', formId, streamId, status, gender, search],
    queryFn: () => api.get('/students/class-list', { params }).then(r => r.data),
    placeholderData: keepPreviousData, // keeps the table showing while a filter/search change refetches, instead of flashing to a spinner on every keystroke
  });

  // Filters change the underlying result set, so always land back on page 1 rather than
  // stranding the viewer on a now out-of-range page.
  useEffect(() => { setPage(1); }, [formId, streamId, status, gender, search, pageSize]);

  const total = (students as any[]).length;
  const lastPage = Math.max(1, Math.ceil(total / pageSize));
  const pageStudents = useMemo(
    () => (students as any[]).slice((page - 1) * pageSize, page * pageSize),
    [students, page, pageSize]
  );

  const download = async (format: 'csv' | 'pdf') => {
    setDownloading(format);
    try {
      await downloadReport('/students/class-list', { ...params, format }, `class-list.${format}`);
    } finally {
      setDownloading(null);
    }
  };

  return (
    <div>
      <PageHeader title="Class List" subtitle="Filterable student roster, downloadable as CSV or PDF" />

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Filters" />
        <CardBody>
          <Grid cols={5} style={{ marginBottom: 0, alignItems: 'end' }}>
            <FormGroup label="Form">
              <Select value={formId} onChange={e => { setFormId(e.target.value); setStreamId(''); }}>
                <option value="">All Forms</option>
                {(forms as any[]).map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Class">
              <Select value={streamId} onChange={e => setStreamId(e.target.value)}>
                <option value="">All Classes</option>
                {filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Status">
              <Select value={status} onChange={e => setStatus(e.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="transferred">Transferred</option>
                <option value="graduated">Graduated</option>
                <option value="suspended">Suspended</option>
                <option value="">All Statuses</option>
              </Select>
            </FormGroup>
            <FormGroup label="Gender">
              <Select value={gender} onChange={e => setGender(e.target.value)}>
                <option value="">All</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </Select>
            </FormGroup>
            <FormGroup label="Search">
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Name or student number…"
                style={{ width: '100%', padding: '9px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }} />
            </FormGroup>
          </Grid>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Students"
          action={
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <Badge variant="green">{isLoading ? '…' : (students as any[]).length} student(s)</Badge>
              <Btn size="sm" variant="outline" loading={downloading === 'csv'} onClick={() => download('csv')}>⬇ CSV</Btn>
              <Btn size="sm" variant="outline" loading={downloading === 'pdf'} onClick={() => download('pdf')}>⬇ PDF</Btn>
            </div>
          }
        />
        {isLoading ? <div style={{ padding: 24 }}><Spinner /></div> : (
          <>
            <Table headers={['#', 'Student #', 'Name', 'Gender', 'Form', 'Class', 'Guardian', 'Guardian Phone', 'Status']}>
              {pageStudents.length === 0 && (
                <tr><Td colSpan={9} style={{ textAlign: 'center', color: '#9ca3af' }}>No students match this filter.</Td></tr>
              )}
              {pageStudents.map((s, i) => (
                <tr key={s.id}>
                  <Td style={{ color: '#9ca3af' }}>{(page - 1) * pageSize + i + 1}</Td>
                  <Td><code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11 }}>{s.student_number ?? s.admission_number}</code></Td>
                  <Td><Link to={`/app/students/${s.id}`} style={{ color: '#1a6b3c', fontWeight: 600, textDecoration: 'none' }}>{s.first_name} {s.last_name}</Link></Td>
                  <Td style={{ textTransform: 'capitalize' }}>{s.gender ?? '—'}</Td>
                  <Td>{s.resolved_form_name ?? '—'}</Td>
                  <Td>{s.resolved_stream_name ?? '—'}</Td>
                  <Td>{s.guardian_name ?? '—'}</Td>
                  <Td>{s.guardian_phone ?? '—'}</Td>
                  <Td>{statusBadge(s.status)}</Td>
                </tr>
              ))}
            </Table>

            {total > 0 && (
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid #f1f5f9' }}>
                <div style={{ fontSize: 12, color: '#6b7280' }}>
                  Showing {(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <Select value={String(pageSize)} onChange={e => setPageSize(Number(e.target.value))} style={{ width: 100 }}>
                    {PAGE_SIZES.map(n => <option key={n} value={n}>{n} / page</option>)}
                  </Select>
                  <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))}>← Prev</Btn>
                  <span style={{ fontSize: 12, color: '#374151', minWidth: 70, textAlign: 'center' }}>Page {page} of {lastPage}</span>
                  <Btn size="sm" variant="outline" disabled={page >= lastPage} onClick={() => setPage(p => Math.min(lastPage, p + 1))}>Next →</Btn>
                </div>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
}
