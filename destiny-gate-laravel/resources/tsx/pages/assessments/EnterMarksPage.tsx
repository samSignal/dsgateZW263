import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Spinner, PageHeader, Btn, Alert, Badge } from '../../components/UI';

const gradeColors: Record<string, string> = { A: '#0f3d22', B: '#2563eb', C: '#7c3aed', D: '#d97706', E: '#f59e0b', U: '#dc2626' };

export default function EnterMarksPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [marks, setMarks] = useState<Record<number, { mark_obtained: string; status: string; teacher_comment: string }>>({});
  const [err, setErr] = useState('');
  const [saved, setSaved] = useState(false);

  const { data: assessment, isLoading } = useQuery({
    queryKey: ['assessment', id],
    queryFn: () => api.get(`/assessments/${id}`).then(r => r.data),
  });

  // Initialize marks state from loaded data
  useEffect(() => {
    if (assessment?.marks) {
      const init: typeof marks = {};
      assessment.marks.forEach((m: any) => {
        init[m.student_id] = {
          mark_obtained:   m.mark_obtained !== null ? String(m.mark_obtained) : '',
          status:          m.status,
          teacher_comment: m.teacher_comment ?? '',
        };
      });
      setMarks(init);
    }
  }, [assessment]);

  const saveDraft = useMutation({
    mutationFn: () => {
      const payload = Object.entries(marks).map(([studentId, m]) => ({
        student_id:      parseInt(studentId),
        status:          m.status === 'pending' && m.mark_obtained ? 'entered' : m.status,
        mark_obtained:   m.mark_obtained ? parseFloat(m.mark_obtained) : null,
        teacher_comment: m.teacher_comment || null,
      }));
      return api.post(`/assessments/${id}/marks`, { marks: payload });
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessment', id] }); toastSuccess('Marks saved as draft.'); setSaved(true); },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Failed to save marks.'),
  });

  const submit = useMutation({
    mutationFn: () => api.post(`/assessments/${id}/marks/submit`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessment', id] }); toastSuccess('Marks submitted for approval.'); navigate('/app/assessments'); },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Failed to submit.'),
  });

  const handleSubmit = async () => {
    const ok = await confirmAction('Submit Marks?', 'Once submitted, marks cannot be edited without headmaster approval.', 'Submit');
    if (ok) {
      await saveDraft.mutateAsync();
      submit.mutate();
    }
  };

  const updateMark = (studentId: number, field: string, value: string) => {
    setMarks(m => ({ ...m, [studentId]: { ...m[studentId], [field]: value } }));
    setSaved(false);
  };

  if (isLoading) return <Spinner />;
  if (!assessment) return <div>Assessment not found.</div>;

  const canEdit = ['open', 'draft'].includes(assessment.status);
  const totalMarks = parseFloat(assessment.total_marks);

  return (
    <div>
      <PageHeader
        title={`Enter Marks: ${assessment.title}`}
        subtitle={`${assessment.subject_name} · ${assessment.form_name} ${assessment.stream_name} · ${assessment.term_name} · Total: ${assessment.total_marks}`}
        action={
          <div style={{ display: 'flex', gap: 8 }}>
            <Badge variant={assessment.status === 'approved' ? 'green' : assessment.status === 'submitted' ? 'amber' : 'blue'} style={{ textTransform: 'capitalize' }}>{assessment.status}</Badge>
            {canEdit && (
              <>
                <Btn variant="outline" loading={saveDraft.isPending} onClick={() => saveDraft.mutate()}>💾 Save Draft</Btn>
                <Btn loading={submit.isPending} onClick={handleSubmit}>✅ Submit Marks</Btn>
              </>
            )}
          </div>
        }
      />

      {err && <Alert type="error" message={err} />}
      {saved && <Alert type="success" message="Draft saved successfully." />}

      {!canEdit && (
        <div style={{ background: '#fffbeb', border: '1px solid #fde68a', borderRadius: 8, padding: '10px 16px', marginBottom: 16, fontSize: 13, color: '#92400e' }}>
          ⚠️ This assessment is <strong>{assessment.status}</strong>. Marks cannot be edited.
        </div>
      )}

      <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', overflow: 'hidden' }}>
        {/* Header */}
        <div style={{ display: 'grid', gridTemplateColumns: '40px 1fr 120px 100px 80px 1fr', gap: 0, background: '#fafafa', borderBottom: '1px solid #e8eaed', padding: '10px 16px', fontSize: 11, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '.5px' }}>
          <div>#</div>
          <div>Student</div>
          <div>Mark /{totalMarks}</div>
          <div>Status</div>
          <div>Grade</div>
          <div>Teacher Comment</div>
        </div>

        {/* Rows */}
        {assessment.marks?.map((m: any, idx: number) => {
          const row = marks[m.student_id] ?? { mark_obtained: '', status: 'pending', teacher_comment: '' };
          const markVal = parseFloat(row.mark_obtained);
          const pct = !isNaN(markVal) && markVal >= 0 ? Math.round((markVal / totalMarks) * 100) : null;

          // Determine grade from percentage
          let grade = '—';
          if (pct !== null) {
            if (pct >= 80) grade = 'A';
            else if (pct >= 70) grade = 'B';
            else if (pct >= 60) grade = 'C';
            else if (pct >= 50) grade = 'D';
            else if (pct >= 40) grade = 'E';
            else grade = 'U';
          }

          const isAbsent  = row.status === 'absent';
          const isExcused = row.status === 'excused';

          return (
            <div key={m.student_id} style={{
              display: 'grid', gridTemplateColumns: '40px 1fr 120px 100px 80px 1fr',
              gap: 0, padding: '8px 16px', borderBottom: '1px solid #f3f4f6',
              background: isAbsent ? '#fef2f2' : isExcused ? '#fffbeb' : 'transparent',
              alignItems: 'center',
            }}>
              <div style={{ fontSize: 12, color: '#9ca3af' }}>{idx + 1}</div>
              <div>
                <div style={{ fontSize: 13, fontWeight: 600 }}>{m.student_name}</div>
                <div style={{ fontSize: 11, color: '#6b7280' }}>{m.student_number}</div>
              </div>
              <div>
                {!isAbsent && !isExcused ? (
                  <input
                    type="number" min="0" max={totalMarks} step="0.5"
                    value={row.mark_obtained}
                    onChange={e => updateMark(m.student_id, 'mark_obtained', e.target.value)}
                    disabled={!canEdit}
                    style={{
                      width: 90, padding: '5px 8px', border: '1.5px solid #e2e8f0',
                      borderRadius: 6, fontSize: 13, textAlign: 'center',
                      background: canEdit ? '#fff' : '#f9fafb',
                      borderColor: row.mark_obtained && parseFloat(row.mark_obtained) > totalMarks ? '#dc2626' : '#e2e8f0',
                    }}
                  />
                ) : (
                  <span style={{ fontSize: 12, color: '#9ca3af' }}>—</span>
                )}
                {pct !== null && <div style={{ fontSize: 10, color: '#6b7280', marginTop: 2 }}>{pct}%</div>}
              </div>
              <div>
                <select
                  value={row.status}
                  onChange={e => updateMark(m.student_id, 'status', e.target.value)}
                  disabled={!canEdit}
                  style={{ padding: '5px 8px', border: '1.5px solid #e2e8f0', borderRadius: 6, fontSize: 12, cursor: canEdit ? 'pointer' : 'default', background: canEdit ? '#fff' : '#f9fafb' }}
                >
                  <option value="pending">Pending</option>
                  <option value="entered">Entered</option>
                  <option value="absent">Absent</option>
                  <option value="excused">Excused</option>
                </select>
              </div>
              <div>
                {grade !== '—' && (
                  <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: '50%', background: gradeColors[grade] ?? '#6b7280', color: '#fff', fontSize: 12, fontWeight: 700 }}>
                    {grade}
                  </span>
                )}
              </div>
              <div>
                <input
                  type="text"
                  value={row.teacher_comment}
                  onChange={e => updateMark(m.student_id, 'teacher_comment', e.target.value)}
                  disabled={!canEdit}
                  placeholder="Optional comment…"
                  style={{ width: '100%', padding: '5px 8px', border: '1.5px solid #e2e8f0', borderRadius: 6, fontSize: 12, background: canEdit ? '#fff' : '#f9fafb' }}
                />
              </div>
            </div>
          );
        })}
      </div>

      {canEdit && (
        <div style={{ display: 'flex', gap: 10, marginTop: 16, justifyContent: 'flex-end' }}>
          <Btn variant="outline" loading={saveDraft.isPending} onClick={() => saveDraft.mutate()}>💾 Save Draft</Btn>
          <Btn loading={submit.isPending} onClick={handleSubmit}>✅ Submit for Approval</Btn>
        </div>
      )}
    </div>
  );
}
