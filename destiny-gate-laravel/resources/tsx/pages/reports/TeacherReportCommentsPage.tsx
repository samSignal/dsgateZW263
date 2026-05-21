import React, { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Btn, Card, CardBody, FormGroup, Input, PageHeader, Textarea } from '../../components/UI';

export default function TeacherReportCommentsPage() {
  const [form, setForm] = useState({ report_id: '', subject_id: '', teacher_comment: '' });
  const save = useMutation({
    mutationFn: () => api.post(`/reports/${form.report_id}/teacher-comment`, { subject_id: form.subject_id || undefined, teacher_comment: form.teacher_comment }),
    onSuccess: () => toastSuccess('Comment saved.'),
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not save comment.'),
  });
  return <div>
    <PageHeader title="Teacher Report Comments" subtitle="Add class or subject comments to generated report cards" />
    <Card style={{ maxWidth: 720 }}><CardBody>
      <FormGroup label="Report ID"><Input value={form.report_id} onChange={e => setForm(f => ({ ...f, report_id: e.target.value }))} placeholder="Report card ID" /></FormGroup>
      <FormGroup label="Subject ID (optional)"><Input value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))} placeholder="Leave blank for overall teacher comment" /></FormGroup>
      <FormGroup label="Comment"><Textarea value={form.teacher_comment} onChange={e => setForm(f => ({ ...f, teacher_comment: e.target.value }))} /></FormGroup>
      <Btn loading={save.isPending} onClick={() => save.mutate()}>Save Comment</Btn>
    </CardBody></Card>
  </div>;
}
