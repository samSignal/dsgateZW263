import React, { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, Badge, FormGroup, Select, Input, Grid } from '../../components/UI';

const PAGE_SIZES = [25, 50, 100];

function timeAgo(iso: string | null) {
  if (!iso) return 'never';
  // Server timestamps are naive strings in the app's timezone (Africa/Harare, fixed
  // UTC+2, no DST) — not UTC — so they need that offset attached before parsing here.
  const secs = Math.floor((Date.now() - new Date(iso.replace(' ', 'T') + '+02:00').getTime()) / 1000);
  if (secs < 5) return 'just now';
  if (secs < 60) return `${secs}s ago`;
  if (secs < 3600) return `${Math.floor(secs / 60)}m ago`;
  if (secs < 86400) return `${Math.floor(secs / 3600)}h ago`;
  return `${Math.floor(secs / 86400)}d ago`;
}

export default function SystemAudit() {
  const [userId, setUserId] = useState('');
  const [role, setRole] = useState('');
  const [action, setAction] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(PAGE_SIZES[0]);
  const [downloading, setDownloading] = useState<'csv' | 'pdf' | null>(null);

  useEffect(() => { setPage(1); }, [userId, role, action, dateFrom, dateTo, search, pageSize]);

  // Refetches every 20s so "who's online" stays reasonably live without a websocket.
  const { data: sessions, isLoading: sessionsLoading } = useQuery({
    queryKey: ['audit-sessions'],
    queryFn: () => api.get('/admin/audit/sessions').then(r => r.data),
    refetchInterval: 20000,
  });

  const { data: meta } = useQuery({ queryKey: ['audit-meta'], queryFn: () => api.get('/admin/audit/meta').then(r => r.data) });

  const params = {
    user_id: userId || undefined, role: role || undefined, action: action || undefined,
    date_from: dateFrom || undefined, date_to: dateTo || undefined, search: search || undefined,
    page, per_page: pageSize,
  };

  const { data: logData, isLoading: logsLoading } = useQuery({
    queryKey: ['audit-logs', userId, role, action, dateFrom, dateTo, search, page, pageSize],
    queryFn: () => api.get('/admin/audit/logs', { params }).then(r => r.data),
  });

  const download = async (format: 'csv' | 'pdf') => {
    setDownloading(format);
    try {
      await downloadReport('/admin/audit/logs', { ...params, page: undefined, per_page: undefined, format }, `audit-log.${format}`);
    } finally {
      setDownloading(null);
    }
  };

  const logs = logData?.data ?? [];
  const total = logData?.total ?? 0;
  const lastPage = logData?.last_page ?? 1;

  return (
    <div>
      <PageHeader title="System Audit" subtitle="Who is logged in right now, and a trail of every action taken across the system" />

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Currently Logged In" action={
          sessions && <Badge variant="green">{sessions.online_count} active now</Badge>
        } />
        {sessionsLoading ? <div style={{ padding: 24 }}><Spinner /></div> : (
          <Table headers={['Status', 'User', 'Role', 'Last Active', 'Session Started']}>
            {(sessions?.sessions ?? []).map((s: any, i: number) => (
              <tr key={i}>
                <Td>
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                    <span style={{ width: 8, height: 8, borderRadius: '50%', background: s.is_online ? '#22c55e' : '#d1d5db', display: 'inline-block' }} />
                    {s.is_online ? 'Online' : 'Idle'}
                  </span>
                </Td>
                <Td><strong>{s.name}</strong><br /><span style={{ fontSize: 11, color: '#6b7280' }}>{s.email}</span></Td>
                <Td style={{ textTransform: 'capitalize' }}>{s.role}</Td>
                <Td>{timeAgo(s.last_used_at)}</Td>
                <Td>{timeAgo(s.session_started)}</Td>
              </tr>
            ))}
            {(sessions?.sessions ?? []).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af' }}>No active sessions.</Td></tr>}
          </Table>
        )}
        <div style={{ fontSize: 11, color: '#9ca3af', padding: '8px 20px 14px' }}>
          "Online" means used the system within the last 5 minutes. A session that's just idle hasn't logged out — they may still be signed in but away from the keyboard.
        </div>
      </Card>

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Filters" />
        <CardBody>
          <Grid cols={6} style={{ marginBottom: 0, alignItems: 'end' }}>
            <FormGroup label="User">
              <Select value={userId} onChange={e => setUserId(e.target.value)}>
                <option value="">All Users</option>
                {(meta?.users ?? []).map((u: any) => <option key={u.user_id} value={u.user_id}>{u.user_name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Role">
              <Select value={role} onChange={e => setRole(e.target.value)}>
                <option value="">All Roles</option>
                {(meta?.roles ?? []).map((r: string) => <option key={r} value={r}>{r}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Action">
              <Select value={action} onChange={e => setAction(e.target.value)}>
                <option value="">All Actions</option>
                {(meta?.actions ?? []).map((a: string) => <option key={a} value={a}>{a}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="From"><Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} /></FormGroup>
            <FormGroup label="To"><Input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} /></FormGroup>
            <FormGroup label="Search">
              <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Description, path, IP…" />
            </FormGroup>
          </Grid>
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Audit Trail" action={
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <Badge variant="gray">{logsLoading ? '…' : total} entries</Badge>
            <Btn size="sm" variant="outline" loading={downloading === 'pdf'} onClick={() => download('pdf')}>⬇ PDF</Btn>
            <Btn size="sm" variant="outline" loading={downloading === 'csv'} onClick={() => download('csv')}>⬇ CSV</Btn>
          </div>
        } />
        {logsLoading ? <div style={{ padding: 24 }}><Spinner /></div> : (
          <>
            <Table headers={['#', 'Date/Time', 'User', 'Role', 'Action', 'Description', 'Status', 'IP']}>
              {logs.map((l: any, i: number) => (
                <tr key={l.id}>
                  <Td style={{ color: '#9ca3af' }}>{(page - 1) * pageSize + i + 1}</Td>
                  <Td style={{ whiteSpace: 'nowrap' }}>{l.created_at}</Td>
                  <Td>{l.user_name ?? '—'}</Td>
                  <Td style={{ textTransform: 'capitalize' }}>{l.user_role ?? '—'}</Td>
                  <Td>
                    <Badge variant={l.action === 'login_failed' ? 'red' : l.action === 'login' ? 'green' : l.action === 'logout' ? 'gray' : 'blue'}>
                      {l.action.replace('_', ' ')}
                    </Badge>
                  </Td>
                  <Td>{l.description}</Td>
                  <Td style={{ color: l.status_code && l.status_code >= 400 ? '#dc2626' : '#6b7280' }}>{l.status_code ?? '—'}</Td>
                  <Td style={{ fontSize: 11, color: '#6b7280' }}>{l.ip_address ?? '—'}</Td>
                </tr>
              ))}
              {logs.length === 0 && <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af' }}>No matching audit entries.</Td></tr>}
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
