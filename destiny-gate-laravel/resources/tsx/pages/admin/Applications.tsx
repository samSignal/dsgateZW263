import React, { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, statusBadge, Modal, FormGroup, Textarea, StatCard } from '../../components/UI';

export default function Applications() {
  const qc = useQueryClient();
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [viewApp, setViewApp] = useState<any | null>(null);
  const [activeTab, setActiveTab] = useState<'accepted' | 'offered' | 'waitingList' | 'pending' | 'rejected' | 'other'>('accepted');
  const [requestOpen, setRequestOpen] = useState(false);
  const [requestDocKey, setRequestDocKey] = useState('doc_results_path');
  const [requestInstructions, setRequestInstructions] = useState('');
  const [docRequests, setDocRequests] = useState<any[]>([]);

  const { data, isLoading } = useQuery({ queryKey: ['applications'], queryFn: () => api.get('/admin/applications').then(r => r.data) });

  const offer = useMutation({
    mutationFn: (id: number) => api.post(`/admin/applications/${id}/offer`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); toastSuccess('Place offered.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });
  const waitlist = useMutation({
    mutationFn: (id: number) => api.post(`/admin/applications/${id}/waitlist`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); toastSuccess('Moved to waiting list.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });
  const reject = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => api.post(`/admin/applications/${id}/reject`, { rejection_reason: reason }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); setRejectId(null); setReason(''); toastSuccess('Application rejected.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const createDocRequest = useMutation({
    mutationFn: ({ id, document_key, instructions }: { id: number; document_key: string; instructions?: string }) =>
      api.post(`/admin/applications/${id}/document-requests`, { document_key, instructions }),
    onSuccess: () => {
      toastSuccess('Resubmission request sent.');
      setRequestOpen(false);
      setRequestInstructions('');
      if (viewApp?.id) {
        api.get(`/admin/applications/${viewApp.id}/document-requests`).then(r => setDocRequests(r.data)).catch(() => {});
      }
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const docUrl = (path?: string | null) => {
    if (!path) return null;
    const p = String(path).replace(/^\/+/, '');
    return `/uploads/${p.startsWith('storage/') ? p.slice('storage/'.length) : p}`;
  };

  const docLabel = (k?: string) => {
    const map: Record<string, string> = {
      doc_student_id_path: 'Student ID / Birth Certificate',
      doc_results_path: 'Results',
      doc_parent_id_path: 'Parent ID / Birth',
      doc_transfer_letter_path: 'Transfer Letter',
    };
    return map[String(k ?? '')] ?? String(k ?? 'Document');
  };

  const reqBadge = (status?: string) => {
    const s = String(status ?? '').toLowerCase();
    const base: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', padding: '2px 8px', borderRadius: 999, fontSize: 11, fontWeight: 800, border: '1px solid' };
    if (s === 'fulfilled') return <span style={{ ...base, color: '#166534', background: '#dcfce7', borderColor: '#bbf7d0' }}>Resubmitted</span>;
    return <span style={{ ...base, color: '#9a3412', background: '#ffedd5', borderColor: '#fed7aa' }}>Requested</span>;
  };

  const pendingKeys = new Set(docRequests.filter(r => String(r.status).toLowerCase() === 'pending').map(r => String(r.document_key)));
  const fulfilledKeys = new Set(docRequests.filter(r => String(r.status).toLowerCase() === 'fulfilled').map(r => String(r.document_key)));
  const applications = Array.isArray(data?.data) ? data.data : [];

  const grouped = useMemo(() => {
    const groups = {
      accepted: [] as any[],
      offered: [] as any[],
      waitingList: [] as any[],
      pending: [] as any[],
      rejected: [] as any[],
      other: [] as any[],
    };

    for (const app of applications) {
      const status = String(app?.status ?? '').toLowerCase();
      if (app?.offer_accepted_at) groups.accepted.push(app);
      else if (status === 'offered') groups.offered.push(app);
      else if (status === 'waiting_list') groups.waitingList.push(app);
      else if (status === 'pending') groups.pending.push(app);
      else if (status === 'rejected') groups.rejected.push(app);
      else groups.other.push(app);
    }

    return groups;
  }, [applications]);

  const renderRow = (a: any) => (
    <tr key={a.id}>
      <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{a.application_number}</code></Td>
      <Td>
        <div style={{ display: 'grid', gap: 3 }}>
          <strong>{a.first_name} {a.last_name}</strong>
          <span style={{ fontSize: 12, color: '#64748b' }}>{a.email || 'No email'}</span>
        </div>
      </Td>
      <Td>{a.intended_class}</Td>
      <Td>{a.academic_year}</Td>
      <Td>
        <div style={{ display: 'grid', gap: 6 }}>
          <div>{statusBadge(a.status)}</div>
          {a.offer_accepted_at && (
            <span style={{ fontSize: 11, fontWeight: 800, color: '#166534' }}>
              Accepted: {new Date(a.offer_accepted_at).toLocaleDateString()}
            </span>
          )}
        </div>
      </Td>
      <Td style={{ color: '#6b7280' }}>{new Date(a.created_at).toLocaleDateString()}</Td>
      <Td>
        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
          <Btn size="sm" variant="outline" onClick={async () => {
            setViewApp(a);
            try {
              const r = await api.get(`/admin/applications/${a.id}/document-requests`);
              setDocRequests(r.data ?? []);
            } catch {
              setDocRequests([]);
            }
          }}>View</Btn>
          {a.status !== 'rejected' && (
            <>
              {a.status !== 'offered' && (
                <Btn size="sm" loading={offer.isPending} onClick={() => offer.mutate(a.id)}>Offer</Btn>
              )}
              {a.status !== 'waiting_list' && (
                <Btn size="sm" variant="outline" loading={waitlist.isPending} onClick={() => waitlist.mutate(a.id)}>Waiting List</Btn>
              )}
              <Btn size="sm" variant="danger" onClick={() => setRejectId(a.id)}>Reject</Btn>
            </>
          )}
        </div>
      </Td>
    </tr>
  );

  const renderSection = (
    title: string,
    subtitle: string,
    items: any[],
    accent: string,
    emptyText: string,
  ) => (
    <Card style={{ marginTop: 18 }}>
      <div style={{
        padding: '18px 20px',
        borderBottom: '1px solid #e2e8f0',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: 12,
        background: `linear-gradient(135deg, ${accent}14 0%, #ffffff 68%)`,
      }}>
        <div>
          <div style={{ fontSize: 16, fontWeight: 800, color: '#0f172a' }}>{title}</div>
          <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>{subtitle}</div>
        </div>
        <div style={{
          minWidth: 44,
          height: 44,
          padding: '0 14px',
          borderRadius: 999,
          background: accent,
          color: '#fff',
          display: 'inline-flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: 14,
          fontWeight: 800,
          boxShadow: '0 8px 20px rgba(15, 23, 42, 0.08)',
        }}>
          {items.length}
        </div>
      </div>
      {items.length === 0 ? (
        <div style={{ padding: 24, fontSize: 13, color: '#94a3b8' }}>{emptyText}</div>
      ) : (
        <Table headers={['App #', 'Applicant', 'Class', 'Year', 'Status', 'Submitted', 'Actions']}>
          {items.map(renderRow)}
        </Table>
      )}
    </Card>
  );

  const tabs = [
    { key: 'accepted' as const, label: 'Accepted Offers', count: grouped.accepted.length, accent: '#15803d' },
    { key: 'offered' as const, label: 'Offered', count: grouped.offered.length, accent: '#1a6b3c' },
    { key: 'waitingList' as const, label: 'Waiting List', count: grouped.waitingList.length, accent: '#d97706' },
    { key: 'pending' as const, label: 'Pending', count: grouped.pending.length, accent: '#7c3aed' },
    { key: 'rejected' as const, label: 'Rejected', count: grouped.rejected.length, accent: '#dc2626' },
    { key: 'other' as const, label: 'Other', count: grouped.other.length, accent: '#0f172a' },
  ];

  const activeSection = (() => {
    if (activeTab === 'accepted') {
      return renderSection(
        'Accepted Offers',
        'Applicants who accepted their offer letter and are now confirmed for enrolment preparation.',
        grouped.accepted,
        '#15803d',
        'No applicants have accepted their offer letters yet.',
      );
    }

    if (activeTab === 'offered') {
      return renderSection(
        'Offered Applicants',
        'Applicants who have already received an offer of admission.',
        grouped.offered,
        '#1a6b3c',
        'No offered applicants yet.',
      );
    }

    if (activeTab === 'waitingList') {
      return renderSection(
        'Waiting List',
        'Applicants who are on standby while places remain unavailable.',
        grouped.waitingList,
        '#d97706',
        'No applicants are currently on the waiting list.',
      );
    }

    if (activeTab === 'pending') {
      return renderSection(
        'Pending Review',
        'New or undecided applications that still need admissions action.',
        grouped.pending,
        '#7c3aed',
        'No applications are pending review.',
      );
    }

    if (activeTab === 'rejected') {
      return renderSection(
        'Rejected Applications',
        'Applications that have been closed after review.',
        grouped.rejected,
        '#dc2626',
        'No rejected applications yet.',
      );
    }

    return renderSection(
      'Other Statuses',
      'Applications in any additional workflow state such as approved or enrolled.',
      grouped.other,
      '#0f172a',
      'No applications exist in other statuses.',
    );
  })();

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Admission Applications" subtitle="Review and process student applications" />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(210px, 1fr))', gap: 14 }}>
        <StatCard label="Total Applications" value={applications.length} color="blue" trend="All submitted applications" />
        <StatCard label="Accepted Offers" value={grouped.accepted.length} color="green" trend="Confirmed for enrolment preparation" />
        <StatCard label="Offered" value={grouped.offered.length} color="green" trend="Applicants offered a place" />
        <StatCard label="Waiting List" value={grouped.waitingList.length} color="amber" trend="Applicants awaiting a place" />
        <StatCard label="Pending Review" value={grouped.pending.length} color="purple" trend="Applications still under review" />
      </div>

      <div style={{
        marginTop: 18,
        padding: '16px 18px',
        borderRadius: 14,
        border: '1px solid #dbeafe',
        background: 'linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%)',
        color: '#334155',
        fontSize: 13,
      }}>
        Applications are grouped by decision status for faster review. Accepted offers are separated first so admissions can quickly identify learners who have confirmed they are coming for enrolment.
      </div>

      <Card style={{ marginTop: 18, overflow: 'visible' }}>
        <div style={{
          padding: '10px',
          display: 'flex',
          gap: 10,
          flexWrap: 'wrap',
          background: '#fff',
        }}>
          {tabs.map((tab) => {
            const active = tab.key === activeTab;
            return (
              <button
                key={tab.key}
                type="button"
                onClick={() => setActiveTab(tab.key)}
                style={{
                  border: active ? `1px solid ${tab.accent}` : '1px solid #e2e8f0',
                  background: active ? `${tab.accent}12` : '#f8fafc',
                  color: active ? tab.accent : '#475569',
                  padding: '10px 14px',
                  borderRadius: 12,
                  cursor: 'pointer',
                  fontSize: 13,
                  fontWeight: 800,
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: 10,
                  transition: 'all .15s ease',
                }}
              >
                <span>{tab.label}</span>
                <span style={{
                  minWidth: 24,
                  height: 24,
                  padding: '0 7px',
                  borderRadius: 999,
                  background: active ? tab.accent : '#e2e8f0',
                  color: active ? '#fff' : '#334155',
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: 11,
                  fontWeight: 800,
                }}>
                  {tab.count}
                </span>
              </button>
            );
          })}
        </div>
      </Card>

      {activeSection}

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
                { key: 'doc_student_id_path', label: 'Student ID / Birth Certificate', path: viewApp.doc_student_id_path },
                { key: 'doc_results_path', label: 'Results', path: viewApp.doc_results_path },
                { key: 'doc_parent_id_path', label: 'Parent ID / Birth', path: viewApp.doc_parent_id_path },
                { key: 'doc_transfer_letter_path', label: 'Transfer Letter', path: viewApp.doc_transfer_letter_path },
              ].map(d => {
                const url = docUrl(d.path);
                return (
                  <div key={d.label} style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12 }}>
                    <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 10 }}>
                      <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, letterSpacing: '.4px', textTransform: 'uppercase' }}>{d.label}</div>
                      {(pendingKeys.has(d.key) || fulfilledKeys.has(d.key)) && reqBadge(fulfilledKeys.has(d.key) ? 'fulfilled' : 'pending')}
                    </div>
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

            <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a', margin: '10px 0 8px' }}>Resubmission Requests</div>
            <div style={{ background: '#fff', border: '1px solid #eef2f7', borderRadius: 10, padding: 12, marginBottom: 12 }}>
              {docRequests.length === 0 ? (
                <div style={{ fontSize: 12, color: '#94a3b8' }}>No requests yet.</div>
              ) : (
                <div style={{ display: 'grid', gap: 8 }}>
                  {docRequests.map((r: any) => (
                    <div key={r.id} style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 10, border: '1px solid #e2e8f0', borderRadius: 10, padding: 10, background: '#f8fafc' }}>
                      <div style={{ minWidth: 0 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                          <div style={{ fontSize: 12, fontWeight: 800, color: '#0f172a' }}>{docLabel(r.document_key)}</div>
                          {reqBadge(r.status)}
                        </div>
                        <div style={{ fontSize: 12, color: '#64748b', marginTop: 6, whiteSpace: 'pre-wrap' }}>
                          {r.instructions ? r.instructions : 'No instructions.'}
                        </div>
                        <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 8 }}>
                          Requested: {r.requested_at ? new Date(r.requested_at).toLocaleString() : '—'}
                          {' · '}
                          Resubmitted: {r.fulfilled_at ? new Date(r.fulfilled_at).toLocaleString() : '—'}
                        </div>

                        {String(r.status).toLowerCase() === 'fulfilled' && r.new_path && (
                          <div style={{ marginTop: 8 }}>
                            <a
                              href={docUrl(r.new_path) ?? '#'}
                              target="_blank"
                              rel="noreferrer"
                              style={{ fontSize: 12, color: '#1a6b3c', fontWeight: 800, textDecoration: 'none' }}
                            >
                              Open resubmitted document
                            </a>
                          </div>
                        )}
                      </div>
                      <div style={{ fontSize: 11, color: '#64748b', flexShrink: 0 }}>
                        {r.status === 'pending' ? 'Pending' : 'Completed'}
                      </div>
                    </div>
                  ))}
                </div>
              )}

              <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 10 }}>
                <Btn variant="outline" onClick={() => setRequestOpen(true)}>Request Resubmission</Btn>
              </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10 }}>
              <Btn variant="outline" onClick={() => setViewApp(null)}>Close</Btn>
            </div>
          </div>
        )}
      </Modal>

      <Modal open={requestOpen} onClose={() => setRequestOpen(false)} title="Request Document Resubmission" maxWidth={560}>
        <FormGroup label="Document">
          <select
            value={requestDocKey}
            onChange={(e) => setRequestDocKey(e.target.value)}
            style={{ width: '100%', padding: '9px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}
          >
            <option value="doc_student_id_path">Student ID / Birth Certificate</option>
            <option value="doc_results_path">Results</option>
            <option value="doc_parent_id_path">Parent ID / Birth</option>
            <option value="doc_transfer_letter_path">Transfer Letter</option>
          </select>
        </FormGroup>
        <FormGroup label="Instructions (Optional)">
          <Textarea value={requestInstructions} onChange={e => setRequestInstructions(e.target.value)} placeholder="Add instructions for the applicant..." />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setRequestOpen(false)}>Cancel</Btn>
          <Btn
            loading={createDocRequest.isPending}
            onClick={() => viewApp?.id && createDocRequest.mutate({ id: viewApp.id, document_key: requestDocKey, instructions: requestInstructions || undefined })}
          >
            Send Request
          </Btn>
        </div>
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
