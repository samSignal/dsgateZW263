import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Btn, Card, CardBody, PageHeader, Spinner, Table, Td } from '../../components/UI';

async function download(id: number, number: string) {
  const res = await api.get(`/student/reports/${id}/download`, { responseType: 'blob' });
  const href = URL.createObjectURL(new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' }));
  const a = document.createElement('a');
  a.href = href;
  a.download = `${number}.pdf`;
  a.click();
  URL.revokeObjectURL(href);
}

export default function StudentReportsPage() {
  const { data: legacyReports = [], isLoading: isLoadingLegacy } = useQuery({ queryKey: ['student-reports-legacy'], queryFn: () => api.get('/student/reports').then(r => r.data) });
  const { data: yearRes, isLoading: isLoadingYear } = useQuery({ queryKey: ['stream-native-my-year'], queryFn: () => api.get('/stream-native/me/results/year').then(r => r.data) });
  const terms = (yearRes?.terms ?? []) as any[];
  const academicYearId = Number(yearRes?.year?.academic_year_id ?? 0);
  const locked = terms.find((t) => t.is_withheld);

  return <div>
    <PageHeader title="My Report Cards" subtitle="Your published academic reports and downloadable PDFs" />
    {locked && <Card style={{ marginBottom: 16, borderColor: '#fecaca', background: '#fff7f7' }}><CardBody><div className="font-semibold text-red-800">Some term results are currently withheld because fees are not cleared for those terms. Please contact the bursar's office.</div></CardBody></Card>}
    <Card>{(isLoadingLegacy || isLoadingYear) ? <Spinner /> : <Table headers={['Term', 'Status', 'Average', 'GPA', 'Grade', 'Position', 'Download']}>
      {terms.map((t: any) => {
        const isLocked = Boolean(t.is_withheld);
        const match = (legacyReports as any[]).find((r) => (academicYearId ? Number(r.academic_year_id) === academicYearId : true) && Number(r.term_id) === Number(t.term_id));
        const canDownload = !!match && match.financial_clearance_status === 'cleared';
        return (
          <tr key={t.term_id}>
            <Td>{t.term_name} {yearRes?.year?.academic_year_name ?? ''}</Td>
            <Td>{isLocked ? <Badge variant="red">withheld</Badge> : <Badge variant="green">ok</Badge>}</Td>
            <Td>{isLocked ? 'Locked' : `${t.term_average ?? '-'}%`}</Td>
            <Td>{isLocked ? '-' : (t.gpa ?? '-')}</Td>
            <Td>{isLocked ? '-' : (t.overall_grade ?? '-')}</Td>
            <Td>{isLocked ? '-' : `${t.stream_rank ?? '-'}/${t.stream_total ?? '-'}`}</Td>
            <Td>
              <Btn size="sm" variant="outline" disabled={!canDownload} onClick={() => canDownload && download(match.id, match.report_number)}>
                {canDownload ? 'PDF' : (match ? 'Locked' : '—')}
              </Btn>
            </Td>
          </tr>
        );
      })}
      {terms.length === 0 && <tr><Td colSpan={7} style={{ textAlign: 'center', color: '#64748b' }}>No computed results yet.</Td></tr>}
    </Table>}</Card>
  </div>;
}
