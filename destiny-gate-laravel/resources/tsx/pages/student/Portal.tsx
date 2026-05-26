import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardBody, CardHeader, Table, Td, Spinner, PageHeader, statusBadge, Empty, Grid } from '../../components/UI';

export default function StudentPortal() {
  const { data, isLoading } = useQuery({ queryKey: ['student-portal'], queryFn: () => api.get('/student/portal').then(r => r.data) });
  const { data: termData, isLoading: isLoadingTerm } = useQuery({ queryKey: ['stream-native-my-term'], queryFn: () => api.get('/stream-native/me/results/term').then(r => r.data) });
  const { data: progression, isLoading: isLoadingProg } = useQuery({ queryKey: ['stream-native-my-progression'], queryFn: () => api.get('/stream-native/me/progression/status').then(r => r.data) });
  const { data: transcript, isLoading: isLoadingTranscript } = useQuery({ queryKey: ['stream-native-my-transcript'], queryFn: () => api.get('/stream-native/me/transcript').then(r => r.data) });
  const { data: timetable, isLoading: isLoadingTt } = useQuery({ queryKey: ['my-timetable'], queryFn: () => api.get('/student/timetable').then(r => r.data) });

  if (isLoading || isLoadingTerm || isLoadingProg || isLoadingTranscript || isLoadingTt) return <Spinner />;
  if (!data?.student) return <Empty message="No student profile found. Contact the school administration." />;

  const { student, announcements } = data;
  const withheld = Boolean(termData?.is_withheld);
  const streamRank = termData?.rankings?.stream?.rank ?? null;
  const streamScore = termData?.rankings?.stream?.score ?? null;

  return (
    <div>
      <PageHeader title="Student Portal" subtitle={`${student.first_name} ${student.last_name} · ${student.admission_number} · ${student.school_class?.class_name ?? 'Unassigned'}`} />

      <Grid cols={4} style={{ marginBottom: 20 }}>
        <Card><CardBody><div className="text-xs uppercase text-slate-500">Term Average</div><div className="text-3xl font-bold text-emerald-800">{withheld ? '-' : `${termData?.aggregate?.term_average ?? '-' }%`}</div></CardBody></Card>
        <Card><CardBody><div className="text-xs uppercase text-slate-500">GPA</div><div className="text-3xl font-bold">{withheld ? '-' : (termData?.aggregate?.gpa ?? '-')}</div><div className="text-xs text-slate-500">{transcript?.cumulative_gpa !== null && transcript?.cumulative_gpa !== undefined ? `Cumulative ${transcript.cumulative_gpa}` : ''}</div></CardBody></Card>
        <Card><CardBody><div className="text-xs uppercase text-slate-500">Stream Rank</div><div className="text-3xl font-bold">{withheld ? '-' : (streamRank ?? '-')}</div><div className="text-xs text-slate-500">{withheld ? '' : (streamScore !== null ? `Score ${streamScore}%` : '')}</div></CardBody></Card>
        <Card><CardBody><div className="text-xs uppercase text-slate-500">Progression</div><div className="text-xl font-bold">{progression?.decision ?? '-'}</div><div className="text-xs text-slate-500">{progression?.reason ?? ''}</div></CardBody></Card>
      </Grid>

      {withheld && (
        <Card style={{ marginBottom: 20, borderColor: '#fecaca', background: '#fff7f7' }}>
          <CardBody>
            <div className="font-semibold text-red-800">Your results are currently withheld because your fees are not cleared for this term.</div>
          </CardBody>
        </Card>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        <Card>
          <CardHeader title="My Results" />
          <Table headers={['Subject', 'Average', 'Grade', 'GPA Points']}>
            {(termData?.subjects ?? []).map((s: any) => (
              <tr key={s.subject_id}>
                <Td>{s.subject_name}<div className="text-xs text-slate-500">{s.subject_code}</div></Td>
                <Td>{s.subject_average ?? '-'}%</Td>
                <Td><strong>{s.grade ?? '-'}</strong></Td>
                <Td>{s.gpa_points ?? '-'}</Td>
              </tr>
            ))}
            {!withheld && (termData?.subjects ?? []).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#64748b' }}>No results yet for this term.</Td></tr>}
          </Table>
        </Card>

        <Card>
          <CardHeader title="My Timetable" />
          <Table headers={['Day', 'Period', 'Subject', 'Teacher', 'Time']}>
            {(timetable?.entries ?? []).map((t: any, idx: number) => (
              <tr key={`${t.day_of_week}-${t.period_number}-${idx}`}>
                <Td style={{ textTransform: 'capitalize' }}>{t.day_of_week}</Td>
                <Td>{t.period_number}</Td>
                <Td>{t.subject_name}</Td>
                <Td>{t.teacher_name}</Td>
                <Td style={{ color: '#6b7280' }}>{String(t.start_time).slice(0, 5)} – {String(t.end_time).slice(0, 5)}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </div>

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="My Attendance" />
        <Table headers={['Date', 'Status', 'Remarks']}>
          {student.attendance?.slice(0, 20).map((a: any) => (
            <tr key={a.id}>
              <Td>{new Date(a.date).toLocaleDateString()}</Td>
              <Td>{statusBadge(a.status)}</Td>
              <Td style={{ color: '#6b7280' }}>{a.remarks ?? '—'}</Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Card>
        <CardHeader title="School Notices" />
        <div style={{ padding: 20 }}>
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
