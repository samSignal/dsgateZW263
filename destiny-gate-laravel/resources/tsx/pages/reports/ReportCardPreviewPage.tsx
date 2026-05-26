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
  const rawId = String(id ?? '');
  const sn = rawId.match(/^sn-(\d+)-(\d+)-(\d+)$/);
  const numericReportId = /^\d+$/.test(rawId) ? rawId : null;

  const streamNativeFromRoute = sn
    ? { studentId: Number(sn[1]), academicYearId: Number(sn[2]), termId: Number(sn[3]) }
    : null;

  const { data: report, isLoading: isLoadingLegacy } = useQuery({
    queryKey: ['report', numericReportId],
    queryFn: () => api.get(`/reports/${numericReportId}`).then(r => r.data),
    enabled: !!numericReportId,
  });

  const studentId = streamNativeFromRoute?.studentId ?? report?.student_id ?? null;
  const academicYearId = streamNativeFromRoute?.academicYearId ?? report?.academic_year_id ?? null;
  const termId = streamNativeFromRoute?.termId ?? report?.term_id ?? null;

  const { data: preview, isLoading: isLoadingPreview } = useQuery({
    queryKey: ['stream-native-report-preview', studentId, academicYearId, termId],
    queryFn: () =>
      api
        .get(`/stream-native/report-card/student/${studentId}/term`, { params: { academic_year_id: academicYearId, term_id: termId } })
        .then(r => r.data),
    enabled: !!studentId && !!academicYearId && !!termId,
  });

  const approve = useMutation({
    mutationFn: () => api.post(`/reports/${numericReportId}/approve`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['report', numericReportId] });
      toastSuccess('Report approved.');
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not approve.'),
  });
  const publish = useMutation({
    mutationFn: () => api.post(`/reports/${numericReportId}/publish`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['report', numericReportId] });
      toastSuccess('Report published.');
    },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not publish.'),
  });

  const isLoading = isLoadingLegacy || isLoadingPreview;
  if (isLoading) return <Spinner />;
  if (!preview && !report) return <div>Report not found.</div>;

  const isStreamNativeOnly = !!streamNativeFromRoute && !numericReportId;
  const withheld = Boolean(preview?.is_withheld);
  const uncleared = !isStreamNativeOnly && report?.financial_clearance_status !== 'cleared';
  const headerStudentName = report?.student_name ?? `${preview?.student?.first_name ?? ''} ${preview?.student?.last_name ?? ''}`.trim();
  const headerTerm = report?.term_name ?? preview?.aggregate?.term_name ?? '';
  const headerYear = report?.academic_year_name ?? preview?.aggregate?.academic_year_name ?? '';

  return <div>
    <PageHeader
      title={`Report Card: ${headerStudentName || 'Student'}`}
      subtitle={
        isStreamNativeOnly
          ? `Stream-native preview - ${headerTerm} ${headerYear}`.trim()
          : `${report?.report_number ?? ''} - ${headerTerm} ${headerYear}`.trim()
      }
      action={
        isStreamNativeOnly ? null : (
          <div className="flex gap-2">
            <Btn variant="outline" onClick={() => download(`/reports/${numericReportId}/download`, `${report?.report_number ?? 'report'}.pdf`)}>Download PDF</Btn>
            <Btn loading={approve.isPending} onClick={() => approve.mutate()}>Approve</Btn>
            <Btn loading={publish.isPending} onClick={() => publish.mutate()}>Publish</Btn>
          </div>
        )
      }
    />
    {(uncleared || withheld) && (
      <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
        RESULT WITHHELD - FEES NOT CLEARED.
        {!isStreamNativeOnly && (
          <> Outstanding balance: ${Number(report?.financial_clearance?.outstanding_balance ?? report?.fees_balance ?? 0).toFixed(2)}.</>
        )}
      </div>
    )}
    <Card>
      <CardBody>
        <div className="border-b-4 border-emerald-800 pb-4">
          <div className="text-2xl font-bold text-emerald-800">DestinyGate Institute</div>
          <div className="font-semibold text-amber-700">Academic Excellence, Character, and Purpose</div>
        </div>
        <Grid cols={4} style={{ marginTop: 16 }}>
          <div><div className="text-xs uppercase text-slate-500">Student</div><div className="font-bold">{headerStudentName}</div></div>
          <div><div className="text-xs uppercase text-slate-500">Class</div><div className="font-bold">{preview?.student?.form_name ?? report?.form_name} {preview?.student?.stream_name ?? report?.stream_name}</div></div>
          <div><div className="text-xs uppercase text-slate-500">Attendance</div><div className="font-bold">{report?.attendance_percentage ?? '-' }%</div></div>
          <div><div className="text-xs uppercase text-slate-500">Status</div><Badge variant={(report?.status ?? '') === 'published' ? 'green' : (report?.status ?? '') === 'approved' ? 'blue' : 'amber'}>{isStreamNativeOnly ? 'preview' : (report?.status ?? 'draft')}</Badge></div>
        </Grid>
      </CardBody>
    </Card>
    <Grid cols={4} style={{ marginTop: 16 }}>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Average</div><div className="text-3xl font-bold text-emerald-800">{withheld ? '-' : (preview?.aggregate?.term_average ?? '-')}%</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Grade</div><div className="text-3xl font-bold text-amber-700">{withheld ? '-' : (preview?.aggregate?.overall_grade ?? '-')}</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">Position</div><div className="text-3xl font-bold">{withheld ? '-' : `#${preview?.ranking?.stream_rank ?? '-'}`}</div></CardBody></Card>
      <Card><CardBody><div className="text-xs uppercase text-slate-500">GPA</div><div className="text-3xl font-bold">{withheld ? '-' : (preview?.aggregate?.gpa ?? '-')}</div></CardBody></Card>
    </Grid>
    <Card style={{ marginTop: 16 }}>
      <CardHeader title="Subject Performance" />
      <Table headers={['Subject', 'Average', 'Grade', 'GPA Points']}>
        {(preview?.subjects ?? []).map((s: any) => (
          <tr key={s.subject_id}>
            <Td>{s.subject_name}<div className="text-xs text-slate-500">{s.subject_code}</div></Td>
            <Td>{s.subject_average ?? '-'}%</Td>
            <Td>{s.grade ?? '-'}</Td>
            <Td>{s.gpa_points ?? '-'}</Td>
          </tr>
        ))}
        {!withheld && (preview?.subjects ?? []).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#64748b' }}>No subject records for this term.</Td></tr>}
        {withheld && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#b91c1c' }}>Results withheld.</Td></tr>}
      </Table>
    </Card>
    {!isStreamNativeOnly && report && (
      <Grid cols={2} style={{ marginTop: 16 }}>
        <Card>
          <CardHeader title="Skills & Competencies" />
          <Table headers={['Skill', 'Rating']}>
            {(report.skills ?? []).map((s: any) => <tr key={s.id}><Td>{s.skill_name}</Td><Td>{s.rating}</Td></tr>)}
            {(report.skills ?? []).length === 0 && <tr><Td colSpan={2} style={{ textAlign: 'center', color: '#64748b' }}>No skills recorded.</Td></tr>}
          </Table>
        </Card>
        <Card>
          <CardHeader title="Comments" />
          <CardBody>
            <p><strong>Teacher:</strong> {report.teacher_comment ?? '-'}</p>
            <p><strong>Headmaster:</strong> {report.headmaster_comment ?? '-'}</p>
            <p><strong>Recommendation:</strong> {report.recommendation ?? '-'}</p>
          </CardBody>
        </Card>
      </Grid>
    )}
  </div>;
}
