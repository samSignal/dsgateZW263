import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import {
  ComposedChart, Bar, Line,
  XAxis, YAxis, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell,
} from 'recharts';
import api from '../../lib/api';
import { Spinner } from '../../components/UI';

const quickActions = [
  { label:'Enroll Student',  icon:'👤', color:'#1a6b3c', bg:'#f0faf4', to:'/app/students' },
  { label:'Record Payment',  icon:'💰', color:'#f59e0b', bg:'#fffbeb', to:'/app/finance/payments' },
  { label:'Generate Bills',  icon:'🧾', color:'#f59e0b', bg:'#fffbeb', to:'/app/finance/generate' },
  { label:'Mark Attendance', icon:'📅', color:'#1a6b3c', bg:'#f0faf4', to:'/app/students' },
  { label:'Add Staff',       icon:'🧑‍💼', color:'#1a6b3c', bg:'#f0faf4', to:'/app/admin/staff' },
  { label:'Applications',    icon:'📥', color:'#f59e0b', bg:'#fffbeb', to:'/app/admin/applications' },
];

/* ── empty-state helper ───────────────────────────────────────────────────── */
function Empty({ label }: { label: string }) {
  return (
    <div style={{ height: 130, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#9ca3af', fontSize: 12, textAlign: 'center', padding: '0 16px' }}>
      {label}
    </div>
  );
}

/* ── Card wrapper ─────────────────────────────────────────────────────────── */
function Panel({ children, style }: { children: React.ReactNode; style?: React.CSSProperties }) {
  return (
    <div style={{background:'#fff',borderRadius:12,border:'1px solid #e8eaed',boxShadow:'0 1px 3px rgba(0,0,0,.04)',overflow:'hidden',...style}}>
      {children}
    </div>
  );
}

function PanelHead({ title, action }: { title:string; action?:React.ReactNode }) {
  return (
    <div style={{padding:'14px 18px 0',display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:12}}>
      <span style={{fontSize:13,fontWeight:700,color:'#111827'}}>{title}</span>
      {action}
    </div>
  );
}

/* ── Main component ───────────────────────────────────────────────────────── */
export default function AdminDashboard() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: () => api.get('/admin/dashboard').then(r => r.data),
  });

  if (isLoading) return <Spinner />;

  const attendanceRate = data?.attendance_rate; // null when no attendance has ever been recorded
  const attendanceBreakdown: any[] = data?.attendance_breakdown ?? [];
  const monthlyCollections: any[] = data?.monthly_collections ?? [];
  const topClasses: any[] = data?.top_classes ?? [];
  const activities: any[] = data?.recent_activities ?? [];
  const reminders: any[] = data?.reminders ?? [];
  const pendingActions: any[] = data?.pending_actions ?? [];
  const unbilled = data?.unbilled ?? { count: 0, students: [] };
  const currentYearId = data?.current_academic_year_id;
  const currentTermId = data?.current_term_id;

  // Click a month's bar → Payment History for that whole calendar month.
  const onMonthBarClick = (bar: any) => {
    const ym = bar?.ym;
    if (!ym) return;
    const [y, m] = ym.split('-').map(Number);
    const lastDay = new Date(y, m, 0).getDate();
    navigate(`/app/finance/history?date_from=${ym}-01&date_to=${ym}-${String(lastDay).padStart(2, '0')}`);
  };

  // Click a class row → its Stream Ranking, prefilled with the current term.
  const onClassRowClick = (c: any) => {
    if (!c.stream_id || !currentYearId || !currentTermId) return;
    navigate(`/app/reports/rankings/stream?stream_id=${c.stream_id}&academic_year_id=${currentYearId}&term_id=${currentTermId}`);
  };

  const kpis = [
    { label:'Total Students',  value: data?.total_students ?? 0, color:'#1a6b3c', icon:'👥' },
    { label:'Fees Collected (Term)', value:`$${Number(data?.fees_this_term?.collected ?? 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`, color:'#f59e0b', icon:'💰' },
    { label:'Owing Students',  value: data?.owing_students ?? 0,  color:'#ef4444', icon:'⚠️' },
    { label:'Staff Members',   value: data?.total_staff ?? 0,     color:'#2563eb', icon:'🧑‍💼' },
    { label:'Attendance Rate', value: attendanceRate != null ? `${attendanceRate}%` : '—', color:'#7c3aed', icon:'📅' },
  ];

  return (
    <div style={{animation:'fadeUp .2s ease'}}>

      {/* Page header */}
      <div className="page-header-row" style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',marginBottom:20}}>
        <div>
          <h1 style={{fontSize:22,fontWeight:800,color:'#111827',letterSpacing:'-.4px',margin:0,lineHeight:1.2}}>Dashboard</h1>
          <p style={{fontSize:13,color:'#6b7280',marginTop:3}}>Welcome back, {data?.admin_name ?? 'Administrator'} 👋</p>
        </div>
      </div>

      {/* ── KPI row ── */}
      <div className="grid-5" style={{marginBottom:18}}>
        {kpis.map(k => (
          <Panel key={k.label} style={{padding:'16px'}}>
            <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between'}}>
              <div>
                <div style={{fontSize:10,fontWeight:700,color:'#6b7280',letterSpacing:'.5px',textTransform:'uppercase',marginBottom:6}}>{k.label}</div>
                <div style={{fontSize:22,fontWeight:800,color:'#111827',letterSpacing:'-.5px',lineHeight:1}}>{k.value}</div>
              </div>
              <div style={{width:34,height:34,borderRadius:8,background:`${k.color}18`,display:'flex',alignItems:'center',justifyContent:'center',fontSize:15,flexShrink:0}}>{k.icon}</div>
            </div>
          </Panel>
        ))}
      </div>

      {/* Not Yet Billed — students with zero bills this term, distinct from "owing" (billed
          but unpaid) above. Only shows up when there's actually something to act on. */}
      {unbilled.count > 0 && (
        <Panel style={{ marginBottom: 18 }}>
          <div style={{ padding: '16px 20px', display: 'flex', alignItems: 'center', gap: 16, flexWrap: 'wrap' }}>
            <div style={{ width: 40, height: 40, borderRadius: 10, background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18, flexShrink: 0 }}>🧾</div>
            <div style={{ flex: 1, minWidth: 220 }}>
              <div style={{ fontSize: 13, fontWeight: 700, color: '#111827' }}>
                {unbilled.count} student{unbilled.count === 1 ? '' : 's'} not yet billed this term
              </div>
              <div style={{ fontSize: 12, color: '#6b7280', marginTop: 2 }}>
                {unbilled.students.map((s: any) => s.name).join(', ')}{unbilled.count > unbilled.students.length ? `, +${unbilled.count - unbilled.students.length} more` : ''}
              </div>
            </div>
            <Link to="/app/finance/generate" style={{
              fontSize: 12, fontWeight: 700, color: '#fff', background: '#1a6b3c',
              padding: '8px 16px', borderRadius: 8, textDecoration: 'none', whiteSpace: 'nowrap',
            }}>
              Generate Bills →
            </Link>
          </div>
        </Panel>
      )}

      {/* ── Charts row ── */}
      <div className="grid-4" style={{marginBottom:14}}>

        {/* Fees Collection */}
        <Panel>
          <PanelHead title="Fees Collection — Last 6 Months"/>
          <div style={{padding:'0 18px 4px',display:'flex',gap:14,marginBottom:4}}>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2.5,background:'#1a6b3c',display:'inline-block',borderRadius:2}}/> Collected</span>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2,background:'#f59e0b',display:'inline-block',borderRadius:2,borderTop:'2px dashed #f59e0b'}}/> Billed</span>
          </div>
          <div style={{padding:'0 8px 14px'}}>
            <div style={{fontSize:11,color:'#9ca3af',padding:'0 8px 6px'}}>Click a bar to see that month's payments.</div>
            <ResponsiveContainer width="100%" height={155}>
              <ComposedChart data={monthlyCollections} barSize={20}>
                <XAxis dataKey="month" tick={{fontSize:11,fill:'#9ca3af'}} axisLine={false} tickLine={false}/>
                <YAxis tick={{fontSize:10,fill:'#9ca3af'}} axisLine={false} tickLine={false} tickFormatter={v=>`$${v/1000}k`}/>
                <Tooltip formatter={(v:number)=>`$${Number(v).toLocaleString()}`} contentStyle={{fontSize:12,borderRadius:8,border:'1px solid #e8eaed',boxShadow:'0 4px 12px rgba(0,0,0,.08)'}}/>
                <Bar dataKey="collected" fill="#1a6b3c" radius={[4,4,0,0]} cursor="pointer" onClick={onMonthBarClick}/>
                <Line type="monotone" dataKey="target" stroke="#f59e0b" strokeWidth={2} strokeDasharray="4 3" dot={false}/>
              </ComposedChart>
            </ResponsiveContainer>
          </div>
        </Panel>

        {/* Top Performing Classes */}
        <Panel>
          <PanelHead title="Top Performing Classes (This Term)"/>
          <div style={{padding:'0 16px 14px'}}>
            {topClasses.length === 0 ? <Empty label="No assessment results recorded yet this term." /> : topClasses.map((c: any,i: number)=>(
              <div key={c.name} onClick={() => onClassRowClick(c)} style={{marginBottom:10,cursor:c.stream_id?'pointer':'default'}} title={c.stream_id ? "View this class's ranking" : undefined}>
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:4}}>
                  <span style={{fontSize:12,color:'#374151',display:'flex',alignItems:'center',gap:7}}>
                    <span style={{fontSize:11,fontWeight:700,color:'#9ca3af',width:14,textAlign:'right'}}>{i+1}</span>
                    {c.name}
                  </span>
                  <span style={{fontSize:12,fontWeight:700,color:'#111827'}}>{c.pct}%</span>
                </div>
                <div style={{height:5,background:'#f3f4f6',borderRadius:3,overflow:'hidden'}}>
                  <div style={{height:'100%',width:`${Math.min(100,c.pct)}%`,background:c.pct>=70?'#1a6b3c':c.pct>=50?'#f59e0b':'#ef4444',borderRadius:3,transition:'width .4s ease'}}/>
                </div>
              </div>
            ))}
          </div>
        </Panel>

        {/* Attendance Donut */}
        <Panel>
          <PanelHead title="Attendance Overview"/>
          <div style={{padding:'0 14px 14px'}}>
            {attendanceBreakdown.length === 0 ? <Empty label="No attendance has been recorded yet." /> : (
              <>
                <div style={{fontSize:11,color:'#9ca3af',padding:'0 4px 2px'}}>Click the chart for the full attendance report.</div>
                <div style={{position:'relative',display:'flex',alignItems:'center',justifyContent:'center',height:130,cursor:'pointer'}} onClick={() => navigate('/app/discipline/attendance/reports')}>
                  <ResponsiveContainer width="100%" height={130}>
                    <PieChart>
                      <Pie data={attendanceBreakdown} cx="50%" cy="50%" innerRadius={42} outerRadius={58} dataKey="value" startAngle={90} endAngle={-270}>
                        {attendanceBreakdown.map((e: any,i: number)=><Cell key={i} fill={e.color}/>)}
                      </Pie>
                    </PieChart>
                  </ResponsiveContainer>
                  <div style={{position:'absolute',textAlign:'center',pointerEvents:'none'}}>
                    <div style={{fontSize:18,fontWeight:800,color:'#111827'}}>{attendanceRate}%</div>
                    <div style={{fontSize:10,color:'#6b7280'}}>Present</div>
                  </div>
                </div>
                <div style={{marginTop:6}}>
                  {attendanceBreakdown.map((d: any)=>(
                    <div key={d.name} style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:4}}>
                      <span style={{display:'flex',alignItems:'center',gap:6,fontSize:11,color:'#374151'}}>
                        <span style={{width:7,height:7,borderRadius:'50%',background:d.color,display:'inline-block'}}/>
                        {d.name}
                      </span>
                      <span style={{fontSize:11,fontWeight:600,color:'#374151'}}>{d.value}%</span>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        </Panel>

        {/* Pending Actions (real, replaces the old fake notifications feed) */}
        <Panel>
          <PanelHead title="Needs Attention"/>
          <div style={{padding:'0 18px 14px'}}>
            {pendingActions.length === 0 ? <Empty label="Nothing needs attention right now." /> : pendingActions.map((n: any,i: number)=>(
              <div key={i} style={{display:'flex',alignItems:'flex-start',gap:9,paddingBottom:10,marginBottom:10,borderBottom:i<pendingActions.length-1?'1px solid #f3f4f6':'none'}}>
                <span style={{width:7,height:7,borderRadius:'50%',background:n.dot,display:'inline-block',marginTop:5,flexShrink:0}}/>
                <p style={{fontSize:12,color:'#374151',margin:0,lineHeight:1.4}}>{n.text}</p>
              </div>
            ))}
          </div>
        </Panel>
      </div>

      {/* ── Bottom row ── */}
      <div className="grid-3">

        {/* Recent Activities */}
        <Panel>
          <PanelHead title="Recent Activity"/>
          <div style={{padding:'0 18px 16px'}}>
            {activities.length === 0 ? <Empty label="No activity recorded yet." /> : activities.map((a: any,i: number)=>(
              <div key={i} style={{display:'flex',gap:10,paddingBottom:12,marginBottom:12,borderBottom:i<activities.length-1?'1px solid #f3f4f6':'none'}}>
                <div style={{width:30,height:30,borderRadius:8,background:a.bg,display:'flex',alignItems:'center',justifyContent:'center',fontSize:13,flexShrink:0}}>{a.icon}</div>
                <div style={{flex:1,minWidth:0}}>
                  <p style={{fontSize:12,color:'#374151',margin:0,lineHeight:1.5}}>{a.text}</p>
                  <span style={{fontSize:11,color:'#9ca3af'}}>{a.time}</span>
                </div>
              </div>
            ))}
          </div>
        </Panel>

        {/* Important Reminders */}
        <Panel>
          <PanelHead title="Important Reminders"/>
          <div style={{padding:'0 18px 16px'}}>
            {reminders.length === 0 ? <Empty label="No reminders right now." /> : reminders.map((r: any,i: number)=>(
              <div key={i} style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:10,paddingBottom:12,marginBottom:12,borderBottom:i<reminders.length-1?'1px solid #f3f4f6':'none'}}>
                <div style={{display:'flex',gap:9,flex:1}}>
                  <span style={{fontSize:15,flexShrink:0,marginTop:1}}>{r.icon}</span>
                  <span style={{fontSize:12,color:'#374151',lineHeight:1.5}}>{r.text}</span>
                </div>
                {r.date && <span style={{fontSize:11,color:'#6b7280',whiteSpace:'nowrap'}}>{r.date}</span>}
              </div>
            ))}
          </div>
        </Panel>

        {/* Quick Actions */}
        <Panel>
          <PanelHead title="Quick Actions"/>
          <div className="two-col-grid" style={{padding:'0 14px 16px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:8}}>
            {quickActions.map(a=>(
              <Link key={a.label} to={a.to} style={{
                display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',
                gap:7,padding:'12px 6px',
                background:a.bg,borderRadius:10,
                border:`1px solid ${a.color}18`,
                textDecoration:'none',transition:'all .12s',
              }}>
                <div style={{width:32,height:32,borderRadius:8,background:'#fff',display:'flex',alignItems:'center',justifyContent:'center',fontSize:16,boxShadow:'0 1px 4px rgba(0,0,0,.08)'}}>{a.icon}</div>
                <span style={{fontSize:11,fontWeight:600,color:a.color,textAlign:'center',lineHeight:1.3}}>{a.label}</span>
              </Link>
            ))}
          </div>
        </Panel>
      </div>
    </div>
  );
}
