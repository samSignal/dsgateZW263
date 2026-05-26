import React, { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Btn, Card, CardBody, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

async function download(id: number, number: string) {
  const res = await api.get(`/parent/reports/${id}/download`, { responseType: 'blob' });
  const href = URL.createObjectURL(new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' }));
  const a = document.createElement('a'); a.href = href; a.download = `${number}.pdf`; a.click(); URL.revokeObjectURL(href);
}

export default function ParentReportsPage() {
  const { data: legacyReports = [], isLoading: isLoadingLegacy } = useQuery({ queryKey: ['parent-reports-legacy'], queryFn: () => api.get('/parent/reports').then(r => r.data) });
  const { data: childrenRes, isLoading: isLoadingChildren } = useQuery({ queryKey: ['parent-stream-native-children'], queryFn: () => api.get('/parent/stream-native/children').then(r => r.data) });
  const children = (childrenRes?.children ?? []) as any[];
  const [selectedChildId, setSelectedChildId] = useState<number | null>(children[0]?.id ?? null);

  useEffect(() => {
    if (selectedChildId === null && children.length > 0) setSelectedChildId(children[0].id);
  }, [children, selectedChildId]);

  const { data: yearRes, isLoading: isLoadingYear } = useQuery({
    queryKey: ['parent-stream-native-child-year', selectedChildId],
    queryFn: () => api.get(`/parent/stream-native/child/${selectedChildId}/year`).then(r => r.data),
    enabled: !!selectedChildId,
  });

  if (isLoadingLegacy || isLoadingChildren || isLoadingYear) return <Spinner />;
  if (children.length === 0) return <div><PageHeader title="Child Report Cards" subtitle="Published academic reports for your linked children" /><Card><CardBody>No linked students.</CardBody></Card></div>;

  const child = children.find((c) => c.id === selectedChildId) ?? children[0];
  const terms = (yearRes?.terms ?? []) as any[];
  const academicYearId = Number(yearRes?.year?.academic_year_id ?? 0);

  return (
    <div>
      <PageHeader title="Child Report Cards" subtitle="Stream-native term summaries with optional PDF downloads (when available)" />
      <Card style={{ marginBottom: 16 }}>
        <CardBody>
          <div className="text-xs uppercase text-slate-500" style={{ marginBottom: 6 }}>Child</div>
          <Select value={String(selectedChildId ?? '')} onChange={(e) => setSelectedChildId(e.target.value ? Number(e.target.value) : null)} style={{ width: 420 }}>
            {children.map((c) => (
              <option key={c.id} value={c.id}>{c.name} · {c.form_name ?? c.resolved_form_name} {c.stream_name ?? c.resolved_stream_name}</option>
            ))}
          </Select>
        </CardBody>
      </Card>

      <Card>
        <Table headers={['Term', 'Status', 'Average', 'GPA', 'Grade', 'Position', 'Download']}>
          {terms.map((t: any) => {
            const isLocked = Boolean(t.is_withheld);
            const match = (legacyReports as any[]).find((r) => Number(r.student_id) === Number(child.id) && (academicYearId ? Number(r.academic_year_id) === academicYearId : true) && Number(r.term_id) === Number(t.term_id));
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
        </Table>
      </Card>
    </div>
  );
}
