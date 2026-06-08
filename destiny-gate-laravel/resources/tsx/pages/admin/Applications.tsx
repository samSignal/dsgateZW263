import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Modal, FormGroup, Textarea } from '../../components/UI';

export default function Applications() {
  const qc = useQueryClient();
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [viewApp, setViewApp] = useState<any | null>(null);

  const { data, isLoading } = useQuery({ queryKey: ['applications'], queryFn: () => api.get('/admin/applications').then(r => r.data) });

  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/applications/${id}/approve`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); toastSuccess('Application approved.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });
  const reject = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => api.post(`/admin/applications/${id}/reject`, { rejection_reason: reason }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); setRejectId(null); setReason(''); toastSuccess('Application rejected.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  if (isLoading) return <Spinner />;

  const docUrl = (path?: string | null) => {
    if (!path) return null;
    const p = String(path).replace(/^\/+/, '');
    return `/uploads/${p.startsWith('storage/') ? p.slice('storage/'.length) : p}`;
  };

  return (
    <div>
      <PageHeader title="Admission Applications" subtitle="Review and process student applications" />
      <Card>
        <Table headers={['App #', 'Applicant', 'Class', 'Year', 'Status', 'Submitted', 'Actions']}>
          {data?.data?.map((a: any) => (
            <tr key={a.id}>
              <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{a.application_number}</code></Td>
              <Td><strong>{a.first_name} {a.last_name}</strong></Td>
              <Td>{a.intended_class}</Td>
              <Td>{a.academic_year}</Td>
              <Td>{statusBadge(a.status)}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(a.created_at).toLocaleDateString()}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                  <Btn size="sm" variant="outline" onClick={() => setViewApp(a)}>View</Btn>
                  {a.status === 'pending' && (
                    <>
                      <Btn size="sm" loading={approve.isPending} onClick={() => approve.mutate(a.id)}>Approve</Btn>
                      <Btn size="sm" variant="danger" onClick={() => setRejectId(a.id)}>Reject</Btn>
                    </>
                  )}
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={!!viewApp} onClose={() => setViewApp(null)} title="Application Details" maxWidth={820}>
        {viewApp && (
          <div>
            <div style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: 12,
              marginBottom: 14,
            }}>
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 10, padding: 12 }}>
                <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.5px', textTransform: 'uppercase', marginBottom: 6 }}>Application</div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10 }}>
                  <div style={{ fontSize: 14, fontWeight: 800, color: '#0f172a' }}>{viewApp.application_number}</div>
                  <div>{statusBadge(viewApp.status)}</div>
                </div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 6 }}>
                  Submitted: {viewApp.created_at ? new Date(viewApp.created_at).toLocaleString() : '—'}
                </div>
              </div>
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 10, padding: 12 }}>
                <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.5px', textTransform: 'uppercase', marginBottom: 6 }}>Intake</div>
                <div style={{ fontSize: 13, color: '#0f172a', fontWeight: 700 }}>{viewApp.intended_class || '—'}</div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>{viewApp.academic_year || '—'}</div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 6 }}>
                  IDs: Year {viewApp.academic_year_id ?? '—'} · Term {viewApp.term_id ?? '—'} · Form {viewApp.form_id ?? '—'} · Category {viewApp.category_id ?? '—'}
                </div>
              </div>
            </div>

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', margin: '10px 0 8px' }}>Student</div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 12 }}>
              <div style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12 }}>
                <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.4px', textTransform: 'uppercase' }}>Name</div>
                <div style={{ marginTop: 6, fontSize: 13, fontWeight: 700, color: '#0f172a' }}>
                  {[viewApp.first_name, viewApp.middle_name, viewApp.last_name].filter(Boolean).join(' ') || '—'}
                </div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 6 }}>
                  DOB: {viewApp.date_of_birth || '—'} · ID: {viewApp.id_number || '—'}
                </div>
              </div>
              <div style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12 }}>
                <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.4px', textTransform: 'uppercase' }}>Contacts</div>
                <div style={{ fontSize: 12, color: '#0f172a', marginTop: 6 }}>
                  Email: <span style={{ fontWeight: 700 }}>{viewApp.email || '—'}</span>
                </div>
                <div style={{ fontSize: 12, color: '#0f172a', marginTop: 6 }}>
                  Phone: <span style={{ fontWeight: 700 }}>{viewApp.phone || '—'}</span>
                </div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 6 }}>
                  Address: {viewApp.student_address || '—'}
                </div>
              </div>
            </div>

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', margin: '10px 0 8px' }}>Guardians</div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 12 }}>
              {[
                { n: viewApp.guardian_name, e: viewApp.guardian_email, p: viewApp.guardian_phone, title: 'Guardian 1' },
                { n: viewApp.guardian2_name, e: viewApp.guardian2_email, p: viewApp.guardian2_phone, title: 'Guardian 2' },
                { n: viewApp.guardian3_name, e: viewApp.guardian3_email, p: viewApp.guardian3_phone, title: 'Guardian 3' },
              ].filter(g => g.n || g.e || g.p).map(g => (
                <div key={g.title} style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12 }}>
                  <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.4px', textTransform: 'uppercase' }}>{g.title}</div>
                  <div style={{ marginTop: 6, fontSize: 13, fontWeight: 700, color: '#0f172a' }}>{g.n || '—'}</div>
                  <div style={{ fontSize: 12, color: '#64748b', marginTop: 6 }}>
                    {g.e || '—'} · {g.p || '—'}
                  </div>
                </div>
              ))}
            </div>

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', margin: '10px 0 8px' }}>Academic History</div>
            <div style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12, marginBottom: 12 }}>
              <div style={{ fontSize: 12, color: '#0f172a' }}>
                Previous School: <span style={{ fontWeight: 700 }}>{viewApp.previous_school || '—'}</span>
              </div>
              <div style={{ fontSize: 12, color: '#0f172a', marginTop: 6 }}>
                Former Grade/Form: <span style={{ fontWeight: 700 }}>{viewApp.former_grade || '—'}</span>
              </div>
              <div style={{ fontSize: 12, color: '#64748b', marginTop: 8, whiteSpace: 'pre-wrap' }}>
                {viewApp.reason_for_joining || '—'}
              </div>
            </div>

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', margin: '10px 0 8px' }}>Documents</div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 12 }}>
              {[
                { label: 'Student ID / Birth Certificate', path: viewApp.doc_student_id_path },
                { label: 'Results', path: viewApp.doc_results_path },
                { label: 'Parent ID / Birth', path: viewApp.doc_parent_id_path },
                { label: 'Transfer Letter', path: viewApp.doc_transfer_letter_path },
              ].map(d => {
                const url = docUrl(d.path);
                return (
                  <div key={d.label} style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12 }}>
                    <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.4px', textTransform: 'uppercase' }}>{d.label}</div>
                    <div style={{ marginTop: 8 }}>
                      {url ? (
                        <a href={url} target="_blank" rel="noreferrer" style={{ fontSize: 12, color: '#1a6b3c', fontWeight: 700, textDecoration: 'none' }}>
                          Open document
                        </a>
                      ) : (
                        <span style={{ fontSize: 12, color: '#94a3b8' }}>Not uploaded</span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10 }}>
              <Btn variant="outline" onClick={() => setViewApp(null)}>Close</Btn>
            </div>
          </div>
        )}
      </Modal>

      <Modal open={!!rejectId} onClose={() => setRejectId(null)} title="Reject Application">
        <FormGroup label="Rejection Reason">
          <Textarea value={reason} onChange={e => setReason(e.target.value)} placeholder="Provide a reason for rejection..." />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setRejectId(null)}>Cancel</Btn>
          <Btn variant="danger" loading={reject.isPending} onClick={() => rejectId && reject.mutate({ id: rejectId, reason })}>Reject</Btn>
        </div>
      </Modal>
    </div>
  );
}
