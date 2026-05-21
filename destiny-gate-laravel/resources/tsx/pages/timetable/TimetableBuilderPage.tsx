import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Select, Grid, Alert } from '../../components/UI';

const DAYS = ['monday','tuesday','wednesday','thursday','friday'];
const DAY_LABELS: Record<string, string> = { monday:'Mon', tuesday:'Tue', wednesday:'Wed', thursday:'Thu', friday:'Fri' };

// Subject colour palette
const SUBJECT_COLORS = ['#1a6b3c','#2563eb','#7c3aed','#d97706','#dc2626','#0891b2','#059669','#9333ea','#ea580c','#0284c7'];

export default function TimetableBuilderPage() {
  const qc = useQueryClient();
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', stream_id: '' });
  const [open, setOpen] = useState(false);
  const [prefill, setPrefill] = useState<{ day: string; period_id: number } | null>(null);
  const [form, setForm] = useState({ subject_id: '', teacher_id: '', room_id: '', period_id: '', day_of_week: '', timetable_type: 'class', remarks: '' });
  const [formErr, setFormErr] = useState('');

  const { data: years = [] }   = useQuery({ queryKey: ['academic-years'],  queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: allTerms = [] }= useQuery({ queryKey: ['terms'],            queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'],          queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: subjects = [] }= useQuery({ queryKey: ['subjects'],         queryFn: () => api.get('/subjects').then(r => r.data) });
  const { data: periods = [] } = useQuery({ queryKey: ['tt-periods'],       queryFn: () => api.get('/timetable/periods').then(r => r.data) });
  const { data: rooms = [] }   = useQuery({ queryKey: ['tt-rooms'],         queryFn: () => api.get('/timetable/rooms').then(r => r.data) });
  const { data: teachers = [] }= useQuery({ queryKey: ['staff-dropdown'],   queryFn: () => api.get('/staff-members/dropdown').then(r => r.data) });

  const filteredTerms = (allTerms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);

  const { data: ttData, isLoading } = useQuery({
    queryKey: ['timetable-stream', filters],
    queryFn: () => filters.stream_id && filters.academic_year_id && filters.term_id
      ? api.get(`/timetable/stream/${filters.stream_id}`, { params: { academic_year_id: filters.academic_year_id, term_id: filters.term_id } }).then(r => r.data)
      : Promise.resolve(null),
    enabled: !!(filters.stream_id && filters.academic_year_id && filters.term_id),
  });

  const entries: any[] = ttData?.entries ?? [];
  const activePeriods: any[] = (periods as any[]).filter((p: any) => p.is_active);

  // Build grid: period × day → entry
  const grid: Record<string, Record<string, any>> = {};
  activePeriods.forEach((p: any) => {
    grid[p.id] = {};
    DAYS.forEach(d => { grid[p.id][d] = null; });
  });
  entries.forEach((e: any) => {
    if (grid[e.period_id]) grid[e.period_id][e.day_of_week] = e;
  });

  // Assign colours to subjects
  const subjectColorMap: Record<number, string> = {};
  let colorIdx = 0;
  entries.forEach((e: any) => {
    if (!subjectColorMap[e.subject_id]) {
      subjectColorMap[e.subject_id] = SUBJECT_COLORS[colorIdx % SUBJECT_COLORS.length];
      colorIdx++;
    }
  });

  const openAdd = (day: string, period_id: number) => {
    setPrefill({ day, period_id });
    setForm({ subject_id: '', teacher_id: '', room_id: '', period_id: String(period_id), day_of_week: day, timetable_type: 'class', remarks: '' });
    setFormErr('');
    setOpen(true);
  };

  const save = useMutation({
    mutationFn: (d: typeof form) => api.post('/timetable', {
      ...d,
      academic_year_id: filters.academic_year_id,
      term_id:          filters.term_id,
      form_id:          (streams as any[]).find(s => String(s.id) === filters.stream_id)?.form_id,
      stream_id:        filters.stream_id,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['timetable-stream', filters] });
      setOpen(false);
      toastSuccess('Timetable entry added.');
    },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'Failed.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/timetable/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['timetable-stream', filters] }); toastSuccess('Entry removed.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const handleDelete = async (e: any) => {
    const ok = await confirmAction('Remove Entry?', `Remove ${e.subject_name} on ${e.day_of_week}?`, 'Remove');
    if (ok) del.mutate(e.id);
  };

  const selectedStream = (streams as any[]).find(s => String(s.id) === filters.stream_id);

  return (
    <div>
      <PageHeader title="Timetable Builder" subtitle="Build and manage class timetables" />

      {/* Filters */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 20, flexWrap: 'wrap' }}>
        <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 160 }}>
          <option value="">Select Year…</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 150 }}>
          <option value="">Select Term…</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))} style={{ width: 200 }}>
          <option value="">Select Stream…</option>
          {(streams as any[]).map(s => <option key={s.id} value={s.id}>{s.form_name} — {s.name}</option>)}
        </Select>
        {filters.stream_id && filters.term_id && (
          <Link to={`/app/timetable/stream/${filters.stream_id}?year=${filters.academic_year_id}&term=${filters.term_id}`}>
            <Btn variant="outline">🖨️ View Full Grid</Btn>
          </Link>
        )}
      </div>

      {!filters.stream_id || !filters.academic_year_id || !filters.term_id ? (
        <div style={{ textAlign: 'center', padding: '60px 20px', color: '#9ca3af', background: '#fff', borderRadius: 12, border: '1px solid #e8eaed' }}>
          <div style={{ fontSize: 36, marginBottom: 12, opacity: .4 }}>📅</div>
          <p style={{ fontSize: 14 }}>Select an academic year, term, and stream to view or build the timetable.</p>
        </div>
      ) : isLoading ? <Spinner /> : (
        <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', overflow: 'auto' }}>
          <div style={{ padding: '14px 20px', borderBottom: '1px solid #f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: 14, fontWeight: 700 }}>{selectedStream?.form_name} — {selectedStream?.name} Timetable</span>
            <span style={{ fontSize: 12, color: '#6b7280' }}>{entries.length} entries</span>
          </div>

          {/* Grid */}
          <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 700 }}>
            <thead>
              <tr>
                <th style={{ background: '#f9fafb', padding: '10px 14px', textAlign: 'left', fontSize: 11, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '.5px', borderBottom: '1px solid #e8eaed', width: 120 }}>Period</th>
                {DAYS.map(d => (
                  <th key={d} style={{ background: '#f9fafb', padding: '10px 14px', textAlign: 'center', fontSize: 12, fontWeight: 700, color: '#374151', borderBottom: '1px solid #e8eaed', borderLeft: '1px solid #f3f4f6' }}>
                    {d.charAt(0).toUpperCase() + d.slice(1)}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {activePeriods.map((p: any) => (
                <tr key={p.id} style={{ background: p.is_break ? '#fffbeb' : '#fff' }}>
                  <td style={{ padding: '8px 14px', borderBottom: '1px solid #f3f4f6', verticalAlign: 'middle' }}>
                    <div style={{ fontSize: 12, fontWeight: 600, color: p.is_break ? '#d97706' : '#374151' }}>{p.name}</div>
                    <div style={{ fontSize: 10, color: '#9ca3af' }}>{p.start_time.slice(0,5)} – {p.end_time.slice(0,5)}</div>
                  </td>
                  {DAYS.map(d => {
                    const entry = grid[p.id]?.[d];
                    const color = entry ? (subjectColorMap[entry.subject_id] ?? '#1a6b3c') : null;
                    return (
                      <td key={d} style={{ padding: 4, borderBottom: '1px solid #f3f4f6', borderLeft: '1px solid #f3f4f6', verticalAlign: 'top', minWidth: 130 }}>
                        {p.is_break ? (
                          <div style={{ textAlign: 'center', padding: '8px 4px', fontSize: 11, color: '#d97706', fontWeight: 600 }}>— Break —</div>
                        ) : entry ? (
                          <div style={{ background: color + '15', border: `1px solid ${color}30`, borderRadius: 7, padding: '7px 9px', position: 'relative', cursor: 'pointer' }}
                            onClick={() => handleDelete(entry)}>
                            <div style={{ fontSize: 12, fontWeight: 700, color }}>{entry.subject_name}</div>
                            <div style={{ fontSize: 10, color: '#6b7280', marginTop: 2 }}>{entry.teacher_name}</div>
                            {entry.room_name && <div style={{ fontSize: 10, color: '#9ca3af' }}>📍 {entry.room_name}</div>}
                            <div style={{ position: 'absolute', top: 4, right: 6, fontSize: 10, color: '#dc2626', opacity: .6 }}>✕</div>
                          </div>
                        ) : (
                          <button onClick={() => openAdd(d, p.id)} style={{
                            width: '100%', minHeight: 52, background: 'transparent', border: '1.5px dashed #e2e8f0',
                            borderRadius: 7, cursor: 'pointer', color: '#9ca3af', fontSize: 18,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            transition: 'all .12s',
                          }}>+</button>
                        )}
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Add Entry Modal */}
      <Modal open={open} onClose={() => setOpen(false)} title={`Add Lesson — ${prefill?.day?.charAt(0).toUpperCase()}${prefill?.day?.slice(1)} · ${activePeriods.find(p => p.id === prefill?.period_id)?.name}`}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Subject">
          <Select value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))}>
            <option value="">Select subject…</option>
            {(subjects as any[]).map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
          </Select>
        </FormGroup>
        <FormGroup label="Teacher">
          <Select value={form.teacher_id} onChange={e => setForm(f => ({ ...f, teacher_id: e.target.value }))}>
            <option value="">Select teacher…</option>
            {(teachers as any[]).map(t => <option key={t.id} value={t.id}>{t.name} ({t.staff_number})</option>)}
          </Select>
        </FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Room (optional)">
            <Select value={form.room_id} onChange={e => setForm(f => ({ ...f, room_id: e.target.value }))}>
              <option value="">No room</option>
              {(rooms as any[]).filter((r: any) => r.is_active).map(r => <option key={r.id} value={r.id}>{r.room_name}</option>)}
            </Select>
          </FormGroup>
          <FormGroup label="Type">
            <Select value={form.timetable_type} onChange={e => setForm(f => ({ ...f, timetable_type: e.target.value }))}>
              <option value="class">Class</option>
              <option value="exam">Exam</option>
              <option value="remedial">Remedial</option>
            </Select>
          </FormGroup>
        </Grid>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Add to Timetable</Btn>
        </div>
      </Modal>
    </div>
  );
}
