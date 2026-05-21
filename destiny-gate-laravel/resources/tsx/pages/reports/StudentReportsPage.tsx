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
  const { data = [], isLoading } = useQuery({ queryKey: ['student-reports'], queryFn: () => api.get('/student/reports').then(r => r.data) });
  const locked = (data as any[]).find(r => r.financial_clearance_status !== 'cleared');

  return <div>
    <PageHeader title="My Report Cards" subtitle="Your published academic reports and downloadable PDFs" />
    {locked && <Card style={{ marginBottom: 16, borderColor: '#fecaca', background: '#fff7f7' }}><CardBody><div className="font-semibold text-red-800">Your results are currently unavailable because your school fees balance for this term has not been fully cleared. Please contact the bursar's office.</div><div className="mt-2 text-sm text-red-700">Outstanding Balance: ${Number(locked.financial_clearance?.outstanding_balance ?? 0).toFixed(2)} | Amount Paid: ${Number(locked.financial_clearance?.amount_paid ?? 0).toFixed(2)} | Required Balance: ${Number(locked.financial_clearance?.required_balance ?? 0).toFixed(2)}</div><button className="mt-3 rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700" type="button">Contact Bursar</button></CardBody></Card>}
    <Card>{isLoading ? <Spinner /> : <Table headers={['Report', 'Term', 'Financial Status', 'Average', 'Grade', 'Position', 'Download']}>
      {(data as any[]).map(r => {
        const isLocked = r.financial_clearance_status !== 'cleared';
        return <tr key={r.id}>
          <Td>{r.report_number}</Td>
          <Td>{r.term_name} {r.academic_year_name}</Td>
          <Td>{isLocked ? <Badge variant="red">withheld</Badge> : <Badge variant="green">cleared</Badge>}</Td>
          <Td>{isLocked ? 'Locked' : `${r.overall_average ?? '-'}%`}</Td>
          <Td>{isLocked ? '-' : (r.overall_grade ?? '-')}</Td>
          <Td>{isLocked ? '-' : `${r.class_position ?? '-'}/${r.stream_total_students ?? '-'}`}</Td>
          <Td><Btn size="sm" variant="outline" disabled={isLocked} onClick={() => download(r.id, r.report_number)}>{isLocked ? 'Locked' : 'PDF'}</Btn></Td>
        </tr>;
      })}
      {(data as any[]).length === 0 && <tr><Td colSpan={7} style={{ textAlign: 'center', color: '#64748b' }}>No published reports yet.</Td></tr>}
    </Table>}</Card>
  </div>;
}
