import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Btn, Card, PageHeader, Spinner, Table, Td } from '../../components/UI';

async function download(id: number, number: string) {
  const res = await api.get(`/parent/reports/${id}/download`, { responseType: 'blob' });
  const href = URL.createObjectURL(new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' }));
  const a = document.createElement('a'); a.href = href; a.download = `${number}.pdf`; a.click(); URL.revokeObjectURL(href);
}

export default function ParentReportsPage() {
  const { data = [], isLoading } = useQuery({ queryKey: ['parent-reports'], queryFn: () => api.get('/parent/reports').then(r => r.data) });
  return <div><PageHeader title="Child Report Cards" subtitle="Published academic reports for your linked children" /><Card>{isLoading ? <Spinner /> : <Table headers={['Child', 'Term', 'Financial Status', 'Average', 'Grade', 'Position', 'Download']}>{(data as any[]).map(r => {
    const locked = r.financial_clearance_status !== 'cleared';
    return <tr key={r.id}>
      <Td>{r.student_name}<div className="text-xs text-slate-500">{r.student_number}</div></Td>
      <Td>{r.term_name} {r.academic_year_name}</Td>
      <Td>{locked ? <div><Badge variant="red">withheld</Badge><div className="mt-1 text-xs text-red-700">Balance ${Number(r.financial_clearance?.outstanding_balance ?? 0).toFixed(2)}</div><button className="mt-2 rounded border px-2 py-1 text-xs text-slate-600" type="button">Contact Bursar</button></div> : <Badge variant="green">cleared</Badge>}</Td>
      <Td>{locked ? 'Locked' : `${r.overall_average ?? '-'}%`}</Td>
      <Td>{locked ? '-' : (r.overall_grade ?? '-')}</Td>
      <Td>{locked ? '-' : `${r.class_position ?? '-'}/${r.stream_total_students ?? '-'}`}</Td>
      <Td><Btn size="sm" variant="outline" disabled={locked} onClick={() => download(r.id, r.report_number)}>{locked ? 'Locked' : 'PDF'}</Btn></Td>
    </tr>;
  })}</Table>}</Card></div>;
}
