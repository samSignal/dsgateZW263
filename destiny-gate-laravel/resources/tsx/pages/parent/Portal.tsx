import React, { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { StatCard, Card, CardBody, CardHeader, Table, Td, Spinner, PageHeader, statusBadge, Empty, Select, Grid, Badge } from '../../components/UI';

export default function ParentPortal() {
  const { data, isLoading } = useQuery({ queryKey: ['parent-portal'], queryFn: () => api.get('/parent/portal').then(r => r.data).catch(() => null) });
  const { data: childrenRes, isLoading: isLoadingChildren } = useQuery({ queryKey: ['parent-stream-native-children'], queryFn: () => api.get('/parent/stream-native/children').then(r => r.data) });
  const children = (childrenRes?.children ?? []) as any[];
  const [selectedChildId, setSelectedChildId] = useState<number | null>(children[0]?.id ?? null);

  useEffect(() => {
    if (selectedChildId === null && children.length > 0) setSelectedChildId(children[0].id);
  }, [children, selectedChildId]);

  const { data: termData, isLoading: isLoadingTerm } = useQuery({
    queryKey: ['parent-stream-native-child-term', selectedChildId],
    queryFn: () => api.get(`/parent/stream-native/child/${selectedChildId}/term`).then(r => r.data),
    enabled: !!selectedChildId,
  });

  if (isLoading || isLoadingChildren || isLoadingTerm) return <Spinner />;
  if (children.length === 0) return <Empty message="No student linked to your account. Contact the school administration." />;

  const stats = data?.stats ?? { outstanding_fees: 0, attendance_rate: 0, avg_grade: 'N/A', behaviour_cases: 0 };
  const announcements = data?.announcements ?? [];
  const child = children.find((c) => c.id === selectedChildId) ?? children[0];
  const withheld = Boolean(termData?.is_withheld);

  return (
    <div>
      <PageHeader
        title="Parent Portal"
        subtitle={`Monitoring: ${child?.name ?? ''} · ${child?.form_name ?? child?.resolved_form_name ?? ''} ${child?.stream_name ?? child?.resolved_stream_name ?? ''}`.trim()}
      />

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="Outstanding Fees"  value={`$${Number(stats.outstanding_fees).toLocaleString()}`} icon="💰" color="red" />
        <StatCard label="Attendance Rate"   value={`${stats.attendance_rate}%`} icon="📅" color="green" />
        <StatCard label="Average Grade"     value={stats.avg_grade} icon="📚" color="blue" />
        <StatCard label="Behaviour Cases"   value={stats.behaviour_cases} icon="⚠️" color="amber" />
      </div>

      <Card style={{ marginBottom: 20 }}>
        <CardBody>
          <div className="text-xs uppercase text-slate-500" style={{ marginBottom: 6 }}>Child</div>
          <Select value={String(selectedChildId ?? '')} onChange={(e) => setSelectedChildId(e.target.value ? Number(e.target.value) : null)} style={{ width: 360 }}>
            {children.map((c) => (
              <option key={c.id} value={c.id}>{c.name} · {c.form_name ?? c.resolved_form_name} {c.stream_name ?? c.resolved_stream_name}</option>
            ))}
          </Select>
        </CardBody>
      </Card>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        <Card>
          <CardHeader title="Fee Statement" />
          <Table headers={['Year', 'Term', 'Amount', 'Paid', 'Balance', 'Status']}>
            {(data?.student?.fees ?? []).map((f: any) => (
              <tr key={f.id}>
                <Td>{f.academic_year}</Td>
                <Td style={{ textTransform: 'uppercase' }}>{f.term}</Td>
                <Td>${Number(f.amount).toLocaleString()}</Td>
                <Td style={{ color: '#1a6b3c' }}>${Number(f.amount_paid).toLocaleString()}</Td>
                <Td style={{ color: Number(f.balance) > 0 ? '#dc2626' : '#1a6b3c', fontWeight: 600 }}>${Number(f.balance).toLocaleString()}</Td>
                <Td>{statusBadge(f.status)}</Td>
              </tr>
            ))}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Academic Results" />
          <Grid cols={3} style={{ padding: '0 14px', marginTop: 14, marginBottom: 10 }}>
            <div><div className="text-xs uppercase text-slate-500">Term Average</div><div className="text-xl font-bold">{withheld ? '-' : `${termData?.aggregate?.term_average ?? '-'}%`}</div></div>
            <div><div className="text-xs uppercase text-slate-500">GPA</div><div className="text-xl font-bold">{withheld ? '-' : (termData?.aggregate?.gpa ?? '-')}</div></div>
            <div><div className="text-xs uppercase text-slate-500">Class Rank</div><div className="text-xl font-bold">{withheld ? '-' : (termData?.rankings?.stream?.rank ?? '-')}</div></div>
          </Grid>
          {withheld && <div style={{ padding: '0 14px 10px' }}><Badge variant="red">withheld</Badge></div>}
          <Table headers={['Subject', '%', 'Grade', 'GPA Points']}>
            {(termData?.subjects ?? []).map((p: any) => (
              <tr key={p.subject_id}>
                <Td>{p.subject_name}<div className="text-xs text-slate-500">{p.subject_code}</div></Td>
                <Td>{p.subject_average ?? '-'}%</Td>
                <Td><strong>{p.grade ?? '-'}</strong></Td>
                <Td>{p.gpa_points ?? '-'}</Td>
              </tr>
            ))}
            {!withheld && (termData?.subjects ?? []).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#64748b' }}>No results yet for this term.</Td></tr>}
          </Table>
        </Card>
      </div>

      <Card>
        <CardHeader title="School Notices" />
        <div style={{ padding: 20 }}>
          {announcements?.length === 0 && <Empty message="No announcements." />}
          {announcements?.map((a: any) => (
            <div key={a.id} style={{ padding: '12px 0', borderBottom: '1px solid #f3f4f6' }}>
              <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>{a.title}</div>
              <p style={{ fontSize: 13, color: '#374151', margin: 0 }}>{a.content}</p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}
