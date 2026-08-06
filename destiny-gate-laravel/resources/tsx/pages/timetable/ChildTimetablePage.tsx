import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Spinner, PageHeader, Btn, Select } from '../../components/UI';

const DAYS = ['monday','tuesday','wednesday','thursday','friday'];
const SUBJECT_COLORS = ['#0f1a2e','#2563eb','#7c3aed','#d97706','#dc2626','#0891b2','#059669','#9333ea'];

export default function ChildTimetablePage() {
  const [selectedChild, setSelectedChild] = useState<any>(null);
  const [yearId, setYearId] = useState('');
  const [termId, setTermId] = useState('');

  const { data: years = [] }    = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: allTerms = [] } = useQuery({ queryKey: ['terms'],          queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: periods = [] }  = useQuery({ queryKey: ['tt-periods'],     queryFn: () => api.get('/timetable/periods').then(r => r.data) });

  // Get parent's children
  const { data: childrenData } = useQuery({ queryKey: ['parent-children'], queryFn: () => api.get('/parent/finance/children').then(r => r.data) });
  const children = childrenData?.children ?? [];

  const filteredTerms = (allTerms as any[]).filter(t => !yearId || String(t.academic_year_id) === yearId);

  const { data, isLoading } = useQuery({
    queryKey: ['child-timetable', selectedChild?.id, yearId, termId],
    queryFn: () => api.get(`/parent/timetable/child/${selectedChild.id}`, { params: { academic_year_id: yearId || undefined, term_id: termId || undefined } }).then(r => r.data),
    enabled: !!selectedChild,
  });

  const entries: any[] = data?.entries ?? [];
  const activePeriods: any[] = (periods as any[]).filter((p: any) => p.is_active);

  const grid: Record<string, Record<string, any>> = {};
  activePeriods.forEach((p: any) => { grid[p.id] = {}; DAYS.forEach(d => { grid[p.id][d] = null; }); });
  entries.forEach((e: any) => { if (grid[e.period_id]) grid[e.period_id][e.day_of_week] = e; });

  const colorMap: Record<string, string> = {};
  let ci = 0;
  entries.forEach((e: any) => { if (!colorMap[e.subject_name]) colorMap[e.subject_name] = SUBJECT_COLORS[ci++ % SUBJECT_COLORS.length]; });

  return (
    <div>
      <PageHeader title="Child's Timetable" subtitle="View your child's weekly class schedule"
        action={data && <Btn variant="outline" onClick={() => window.print()}>🖨️ Print</Btn>} />

      {/* Child selector */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
        {children.map((c: any) => (
          <div key={c.id} onClick={() => setSelectedChild(c)} style={{
            padding: '12px 18px', borderRadius: 10, cursor: 'pointer',
            border: `2px solid ${selectedChild?.id === c.id ? '#b6924c' : '#e8eaed'}`,
            background: selectedChild?.id === c.id ? '#eef1f8' : '#fff',
          }}>
            <div style={{ fontWeight: 700, fontSize: 13 }}>{c.name}</div>
            <div style={{ fontSize: 11, color: '#6b7280' }}>{c.student_number} · {c.class_name ?? '—'}</div>
          </div>
        ))}
        {children.length === 0 && <p style={{ color: '#9ca3af', fontSize: 13 }}>No children linked to your account.</p>}
      </div>

      {selectedChild && (
        <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
          <Select value={yearId} onChange={e => { setYearId(e.target.value); setTermId(''); }} style={{ width: 160 }}>
            <option value="">All Years</option>
            {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
          </Select>
          <Select value={termId} onChange={e => setTermId(e.target.value)} style={{ width: 150 }}>
            <option value="">All Terms</option>
            {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </Select>
        </div>
      )}

      {selectedChild && (isLoading ? <Spinner /> : (
        <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', overflow: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 700 }}>
            <thead>
              <tr>
                <th style={{ background: '#0f1a2e', color: '#fff', padding: '10px 14px', textAlign: 'left', fontSize: 11, fontWeight: 700, width: 120 }}>PERIOD</th>
                {DAYS.map(d => (
                  <th key={d} style={{ background: '#0f1a2e', color: '#fff', padding: '10px 14px', textAlign: 'center', fontSize: 12, fontWeight: 700, borderLeft: '1px solid rgba(255,255,255,.1)' }}>
                    {d.charAt(0).toUpperCase() + d.slice(1)}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {activePeriods.map((p: any, idx: number) => (
                <tr key={p.id} style={{ background: p.is_break ? '#fffbeb' : idx % 2 === 0 ? '#fff' : '#fafafa' }}>
                  <td style={{ padding: '8px 14px', borderBottom: '1px solid #f3f4f6' }}>
                    <div style={{ fontSize: 12, fontWeight: 700, color: p.is_break ? '#d97706' : '#374151' }}>{p.name}</div>
                    <div style={{ fontSize: 10, color: '#9ca3af' }}>{p.start_time.slice(0,5)} – {p.end_time.slice(0,5)}</div>
                  </td>
                  {DAYS.map(d => {
                    const entry = grid[p.id]?.[d];
                    const color = entry ? (colorMap[entry.subject_name] ?? '#0f1a2e') : null;
                    return (
                      <td key={d} style={{ padding: 6, borderBottom: '1px solid #f3f4f6', borderLeft: '1px solid #f3f4f6', minWidth: 130 }}>
                        {p.is_break ? (
                          <div style={{ textAlign: 'center', padding: '10px 4px', fontSize: 11, color: '#d97706', fontWeight: 600 }}>— Break —</div>
                        ) : entry ? (
                          <div style={{ background: color + '18', border: `1.5px solid ${color}35`, borderRadius: 8, padding: '8px 10px' }}>
                            <div style={{ fontSize: 12, fontWeight: 700, color }}>{entry.subject_name}</div>
                            <div style={{ fontSize: 10, color: '#6b7280', marginTop: 2 }}>{entry.teacher_name}</div>
                            {entry.room_name && <div style={{ fontSize: 10, color: '#9ca3af' }}>📍 {entry.room_name}</div>}
                          </div>
                        ) : (
                          <div style={{ minHeight: 52, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#e5e7eb', fontSize: 11 }}>—</div>
                        )}
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
          {entries.length === 0 && (
            <div style={{ textAlign: 'center', padding: '40px 20px', color: '#9ca3af' }}>
              <div style={{ fontSize: 28, marginBottom: 8, opacity: .4 }}>📅</div>
              <p style={{ fontSize: 13 }}>No timetable found for {selectedChild.name}.</p>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
