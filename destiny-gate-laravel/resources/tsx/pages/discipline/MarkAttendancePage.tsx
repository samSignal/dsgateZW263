import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { confirmAction, toastError, toastSuccess } from '../../lib/toast';
import { Btn, Card, CardHeader, Input, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

const statuses = ['present', 'absent', 'late', 'excused', 'sick', 'early_departure'];

export default function MarkAttendancePage() {
  const { id } = useParams();
  const qc = useQueryClient();
  const { data: session, isLoading } = useQuery({ queryKey: ['attendance-session', id], queryFn: () => api.get(`/discipline/attendance/sessions/${id}`).then(r => r.data), enabled: !!id });
  const [rows, setRows] = useState<any[] | null>(null);
  const currentRows = rows ?? session?.records ?? [];
  const save = useMutation({
    mutationFn: () => api.put(`/discipline/attendance/sessions/${id}/records`, { records: currentRows.map((r: any) => ({ id: r.id, status: r.status, arrival_time: r.arrival_time, reason: r.reason, remarks: r.remarks })) }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['attendance-session', id] }); setRows(null); toastSuccess('Attendance saved.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not save attendance.'),
  });
  const submit = useMutation({
    mutationFn: () => api.post(`/discipline/attendance/sessions/${id}/submit`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['attendance-session', id] }); toastSuccess('Register submitted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not submit register.'),
  });
  const updateRow = (rowId: number, patch: any) => setRows(currentRows.map((r: any) => r.id === rowId ? { ...r, ...patch } : r));
  const bulk = (status: string) => setRows(currentRows.map((r: any) => ({ ...r, status, arrival_time: status === 'late' ? r.arrival_time : null })));
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title={`${session.form_name} ${session.stream_name} Register`} subtitle={`${session.attendance_date} - ${session.session_type} - ${session.status}`} action={<div className="flex gap-2"><Btn variant="outline" onClick={() => bulk('present')}>Bulk Present</Btn><Btn variant="outline" onClick={() => bulk('absent')}>Bulk Absent</Btn><Btn loading={save.isPending} onClick={() => save.mutate()}>Save Draft</Btn><Btn variant="outline" loading={submit.isPending} onClick={async () => { if (await confirmAction('Submit register?', 'Teachers cannot edit after submission.', 'Submit')) submit.mutate(); }}>Submit</Btn></div>} />
      <Card>
        <CardHeader title="Student Register" />
        <Table headers={['Student', 'Status', 'Arrival', 'Reason', 'Remarks']}>
          {currentRows.map((r: any) => <tr key={r.id}><Td><strong>{r.student_name}</strong><br /><span className="text-xs text-slate-500">{r.student_number ?? r.admission_number}</span></Td><Td><Select value={r.status} onChange={e => updateRow(r.id, { status: e.target.value })}>{statuses.map(s => <option key={s} value={s}>{s.replace('_', ' ')}</option>)}</Select></Td><Td><Input type="time" value={r.arrival_time ?? ''} onChange={e => updateRow(r.id, { arrival_time: e.target.value })} disabled={r.status !== 'late'} /></Td><Td><Input value={r.reason ?? ''} onChange={e => updateRow(r.id, { reason: e.target.value })} /></Td><Td><Input value={r.remarks ?? ''} onChange={e => updateRow(r.id, { remarks: e.target.value })} /></Td></tr>)}
        </Table>
      </Card>
    </div>
  );
}
