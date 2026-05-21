import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, CardBody, CardHeader, Grid, PageHeader, Spinner, Table, Td } from '../../components/UI';

async function download(url: string, filename: string) {
  const res = await api.get(url, { responseType: 'blob' });
  const blob = new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' });
  const href = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = href;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(href);
}

export default function ReportCardPreviewPage() {
  const { id } = useParams<{ id: string }>();
  const qc = useQueryClient();
  const { data: report, isLoading } = useQuery({ queryKey: ['report', id], queryFn: () => api.get(`/reports/${id}`).then(r => r.data) });
  const approve = useMutation({ mutationFn: () => api.post(`/reports/${id}/approve`), onSuccess: () => { qc.invalidateQueries({ queryKey: ['report', id] }); toastSuccess('Report approved.'); }, onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not approve.') });
  const publish = useMutation({ mutationFn: () => api.post(`/reports/${id}/publish`), onSuccess: () => { qc.invalidateQueries({ queryKey: ['report', id] }); toastSuccess('Report published.'); }, onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not publish.') });

  if (isLoading) return <Spinner />;
  if (!report) return <div>Report not found.</div>;
  const uncleared = report.financial_clearance_status !== 'cleared';

  return <div>
    <PageHeader title={`Report Card: ${report.student_name}`} subtitle={`${report.report_number} - ${report.term_name} ${report.academic_year_name}`} action={<div className="flex gap-2"><Btn variant="outline" onClick={() => download(`/reports/${id}/download`, `${report.report_number}.pdf`)}>Download PDF</Btn><Btn loading={approve.isPending} onClick={() => approve.mutate()}>Approve</Btn><Btn loading={publish.isPending} onClick={() => publish.mutate()}>Publish</Btn></div>} />
    {uncleared && <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">RESULT WITHHELD - FEES NOT CLEARED. Outstanding balance: ${Number(report.financial_clearance?.outstanding_balance ?? report.fees_balance ?? 0).toFixed(2)}.</div>}
    <Card>
      <CardBody>
        <div className="border-b-4 border-emerald-800 pb-4">
          <div className="text-2xl font-bold text-emerald-800">DestinyGate Institute</div>
          <div className="font-semibold text-amber-700">Academic Excellence, Character, and Purpose</div>
        </div>
        <Grid cols={4} style={{ marginTop: 16 }}>
          <div><div className="text-xs uppercase text-slate-500">Student</div><div className="font-bold">{report.student_name}</div></div>
          <div><div className="text-xs uppercase text-slate-500">Class</div><div className="font-bold">{report.form_name} {report.stream_name}</div></div>
          <div><div className="text-xs uppercase text-slate-500">Attendance</div><div className="font-bold">{report.attendance_percentage ?? '-'}%</div></div>
          <div><div className="text-xs uppercase text-slate-500">Status</div><Badge variant={report.status === 'published' ? 'green' : report.status === 'approved' ? 'blue' : 'amber'}>{report.status}</Badge></div>
        </Grid>
      </CardBody>
    </Card>
    <Grid cols={4} style={{ marginTop: 16 }}>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Average</div><div className="text-3xl font-bold text-emerald-800">{report.overall_average ?? '-'}%</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Grade</div><div className="text-3xl font-bold text-amber-700">{report.overall_grade ?? '-'}</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Position</div><div className="text-3xl font-bold">#{report.class_position ?? '-'}</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Fees Balance</div><div className="text-3xl font-bold">${Number(report.fees_balance ?? 0).toFixed(2)}</div></CardBody></Card>
    </Grid>
    <Card style={{ marginTop: 16 }}><CardHeader title="Subject Performance" /><Table headers={['Subject', 'Average', 'Grade', 'Class Avg', 'Position', 'Comment']}>{report.subjects.map((s: any) => <tr key={s.id}><Td>{s.subject_name}</Td><Td>{s.subject_average ?? '-'}%</Td><Td>{s.subject_grade ?? '-'}</Td><Td>{s.class_average ?? '-'}%</Td><Td>{s.subject_position ?? '-'}</Td><Td>{s.teacher_comment ?? '-'}</Td></tr>)}</Table></Card>
    <Grid cols={2} style={{ marginTop: 16 }}>
      <Card><CardHeader title="Skills & Competencies" /><Table headers={['Skill', 'Rating']}>{report.skills.map((s: any) => <tr key={s.id}><Td>{s.skill_name}</Td><Td>{s.rating}</Td></tr>)}</Table></Card>
      <Card><CardHeader title="Comments" /><CardBody><p><strong>Teacher:</strong> {report.teacher_comment ?? '-'}</p><p><strong>Headmaster:</strong> {report.headmaster_comment ?? '-'}</p><p><strong>Recommendation:</strong> {report.recommendation ?? '-'}</p></CardBody></Card>
    </Grid>
  </div>;
}
