import React from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Spinner, PageHeader, Btn } from '../../components/UI';

const DAYS = ['monday','tuesday','wednesday','thursday','friday'];
const SUBJECT_COLORS = ['#0f3d22','#2563eb','#7c3aed','#d97706','#dc2626','#0891b2','#059669','#9333ea','#ea580c','#0284c7'];

export default function StreamTimetablePage() {
  const { streamId } = useParams<{ streamId: string }>();
  const [params] = useSearchParams();
  const yearId = params.get('year') ?? '';
  const termId = params.get('term') ?? '';

  const { data, isLoading } = useQuery({
    queryKey: ['stream-tt', streamId, yearId, termId],
    queryFn: () => api.get(`/timetable/stream/${streamId}`, { params: { academic_year_id: yearId, term_id: termId } }).then(r => r.data),
    enabled: !!streamId,
  });

  if (isLoading) return <Spinner />;
  if (!data) return <div style={{ padding: 40, textAlign: 'center', color: '#9ca3af' }}>No timetable data.</div>;

  const { stream, periods, entries } = data;
  const activePeriods = (periods as any[]).filter((p: any) => p.is_active);

  // Build grid
  const grid: Record<string, Record<string, any>> = {};
  activePeriods.forEach((p: any) => { grid[p.id] = {}; DAYS.forEach(d => { grid[p.id][d] = null; }); });
  (entries as any[]).forEach((e: any) => { if (grid[e.period_id]) grid[e.period_id][e.day_of_week] = e; });

  // Subject colours
  const colorMap: Record<number, string> = {};
  let ci = 0;
  (entries as any[]).forEach((e: any) => { if (!colorMap[e.subject_id]) { colorMap[e.subject_id] = SUBJECT_COLORS[ci++ % SUBJECT_COLORS.length]; } });

  return (
    <div>
      <PageHeader
        title={`${stream?.form_name} — ${stream?.name} Timetable`}
        subtitle={`${data.entries?.length ?? 0} lessons scheduled`}
        action={<Btn variant="outline" onClick={() => window.print()}>🖨️ Print</Btn>}
      />

      <div id="printable" style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', overflow: 'auto' }}>
        {/* School header for print */}
        <div className="print-only" style={{ display: 'none', textAlign: 'center', padding: '16px 20px', borderBottom: '2px solid #1a6b3c' }}>
          <div style={{ fontSize: 18, fontWeight: 800, color: '#0f3d22' }}>DestinyGate Institute</div>
          <div style={{ fontSize: 13, color: '#374151' }}>{stream?.form_name} — {stream?.name} · Class Timetable</div>
        </div>

        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 700 }}>
          <thead>
            <tr>
              <th style={{ background: '#0f3d22', color: '#fff', padding: '10px 14px', textAlign: 'left', fontSize: 11, fontWeight: 700, letterSpacing: '.5px', width: 120 }}>PERIOD</th>
              {DAYS.map(d => (
                <th key={d} style={{ background: '#0f3d22', color: '#fff', padding: '10px 14px', textAlign: 'center', fontSize: 12, fontWeight: 700, borderLeft: '1px solid rgba(255,255,255,.1)' }}>
                  {d.charAt(0).toUpperCase() + d.slice(1)}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {activePeriods.map((p: any, idx: number) => (
              <tr key={p.id} style={{ background: p.is_break ? '#fffbeb' : idx % 2 === 0 ? '#fff' : '#fafafa' }}>
                <td style={{ padding: '8px 14px', borderBottom: '1px solid #f3f4f6', verticalAlign: 'middle' }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: p.is_break ? '#d97706' : '#374151' }}>{p.name}</div>
                  <div style={{ fontSize: 10, color: '#9ca3af' }}>{p.start_time.slice(0,5)} – {p.end_time.slice(0,5)}</div>
                </td>
                {DAYS.map(d => {
                  const entry = grid[p.id]?.[d];
                  const color = entry ? (colorMap[entry.subject_id] ?? '#0f3d22') : null;
                  return (
                    <td key={d} style={{ padding: 6, borderBottom: '1px solid #f3f4f6', borderLeft: '1px solid #f3f4f6', verticalAlign: 'top', minWidth: 130 }}>
                      {p.is_break ? (
                        <div style={{ textAlign: 'center', padding: '10px 4px', fontSize: 11, color: '#d97706', fontWeight: 600 }}>— Break —</div>
                      ) : entry ? (
                        <div style={{ background: color + '18', border: `1.5px solid ${color}35`, borderRadius: 8, padding: '8px 10px' }}>
                          <div style={{ fontSize: 12, fontWeight: 700, color }}>{entry.subject_name}</div>
                          <div style={{ fontSize: 10, color: '#6b7280', marginTop: 2 }}>{entry.teacher_name}</div>
                          {entry.room_name && <div style={{ fontSize: 10, color: '#9ca3af', marginTop: 1 }}>📍 {entry.room_name}</div>}
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
      </div>

      <style>{`
        @media print {
          .print-only { display: block !important; }
          body > * { display: none; }
          #printable { display: block !important; border: none !important; }
        }
      `}</style>
    </div>
  );
}
