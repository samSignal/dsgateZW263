import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import {
  ComposedChart, BarChart, Bar, LineChart, Line, AreaChart, Area,
  XAxis, YAxis, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell,
} from 'recharts';
import api from '../../lib/api';
import { Spinner } from '../../components/UI';

/* ── static demo data ─────────────────────────────────────────────────────── */
const feesData = [
  { month:'Apr', collected:22000, target:30000 },
  { month:'May', collected:38000, target:45000 },
  { month:'Jun', collected:52000, target:55000 },
  { month:'Jul', collected:68000, target:70000 },
];
const perfData = [
  { month:'Apr', cls:62, inst:58 },
  { month:'May', cls:68, inst:60 },
  { month:'Jun', cls:72, inst:63 },
  { month:'Jul', cls:75, inst:65 },
];
const attendData = [
  { name:'Present', value:92.6, color:'#1a6b3c' },
  { name:'Late',    value:4.3,  color:'#f59e0b' },
  { name:'Absent',  value:3.1,  color:'#ef4444' },
];
const sparkGreen  = [3,5,4,7,6,8,7,9,8,10].map((v,i)=>({i,v}));
const sparkAmber  = [8,7,9,6,8,7,10,8,9,11].map((v,i)=>({i,v}));
const sparkRed    = [10,9,8,10,7,9,8,7,6,5].map((v,i)=>({i,v}));
const sparkBlue   = [5,6,5,7,6,8,7,9,8,10].map((v,i)=>({i,v}));
const sparkPurple = [6,7,8,7,9,8,10,9,11,10].map((v,i)=>({i,v}));

const topClasses = [
  { name:'Form 4A', pct:89.6, color:'#1a6b3c' },
  { name:'Form 3B', pct:76.4, color:'#1a6b3c' },
  { name:'Form 2A', pct:72.1, color:'#f59e0b' },
  { name:'Form 1A', pct:68.9, color:'#f59e0b' },
  { name:'Form 1B', pct:65.3, color:'#ef4444' },
];

const activities = [
  { icon:'👤', bg:'#f0faf4', text:<>New student registration: <strong>Tinashe Chikomba</strong> (Form 1A)</>, time:'2 mins ago' },
  { icon:'💰', bg:'#fffbeb', text:<>Payment received from <strong>Rudo Mayo</strong> (Form 3B)</>,           time:'15 mins ago' },
  { icon:'📝', bg:'#eff6ff', text:<>John Matanda uploaded marks for Mathematics (Form 2A)</>,                time:'1 hour ago' },
  { icon:'📅', bg:'#f5f3ff', text:<>Attendance marked for Form 4A</>,                                        time:'2 hours ago' },
  { icon:'⚠️', bg:'#fef2f2', text:<><span style={{color:'#ef4444',fontWeight:600}}>Warning issued to Tapiwa K.</span> (Form 2B)</>, time:'3 hours ago' },
];

const reminders = [
  { icon:'⚠️', text:'56 students have outstanding fees',    action:'View Details', actionColor:'#ef4444', date:null },
  { icon:'📅', text:'Form 4 Mid-term exams start on 12 May', date:'May 12, 2025' },
  { icon:'👥', text:'Staff meeting',                          date:'May 10, 2025' },
  { icon:'📅', text:'Term 2 ends on 18 July',                 date:'Jul 18, 2025' },
];

const quickActions = [
  { label:'Add Student',     icon:'👤', color:'#1a6b3c', bg:'#f0faf4', to:'/app/students' },
  { label:'Record Payment',  icon:'💰', color:'#f59e0b', bg:'#fffbeb', to:'/app/bursar/fees' },
  { label:'Upload Results',  icon:'📊', color:'#1a6b3c', bg:'#f0faf4', to:'/app/students' },
  { label:'Mark Attendance', icon:'📅', color:'#f59e0b', bg:'#fffbeb', to:'/app/students' },
  { label:'Add Staff',       icon:'🧑‍💼', color:'#1a6b3c', bg:'#f0faf4', to:'/app/admin/staff' },
  { label:'Create Invoice',  icon:'🧾', color:'#f59e0b', bg:'#fffbeb', to:'/app/bursar/fees' },
];

const notifications = [
  { dot:'#1a6b3c', text:'New fee structure for Term 3',  time:'2 hours ago' },
  { dot:'#f59e0b', text:'System maintenance on Sunday',  time:'5 hours ago' },
  { dot:'#2563eb', text:'Parents meeting on 20 May',     time:'1 day ago' },
];

/* ── Sparkline helper ─────────────────────────────────────────────────────── */
function Spark({ data, color }: { data:{i:number;v:number}[]; color:string }) {
  return (
    <ResponsiveContainer width="100%" height={40}>
      <AreaChart data={data} margin={{top:2,right:0,left:0,bottom:0}}>
        <defs>
          <linearGradient id={`sg${color.replace('#','')}`} x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%"  stopColor={color} stopOpacity={0.15}/>
            <stop offset="95%" stopColor={color} stopOpacity={0}/>
          </linearGradient>
        </defs>
        <Area type="monotone" dataKey="v" stroke={color} strokeWidth={1.8}
          fill={`url(#sg${color.replace('#','')})`} dot={false} />
      </AreaChart>
    </ResponsiveContainer>
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

function TermSelect() {
  return (
    <select style={{fontSize:11,border:'1px solid #e8eaed',borderRadius:6,padding:'3px 8px',color:'#374151',cursor:'pointer',background:'#fff'}}>
      <option>This Term</option>
    </select>
  );
}

/* ── Main component ───────────────────────────────────────────────────────── */
export default function AdminDashboard() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: () => api.get('/admin/dashboard').then(r => r.data),
  });

  if (isLoading) return <Spinner />;

  const kpis = [
    { label:'Total Students',  value: data?.total_students ?? 1248, trend:'+5.3% from last term',  up:true,  color:'#1a6b3c', spark:sparkGreen,  icon:'👥' },
    { label:'Fees Collected',  value:`$${Number(142560).toLocaleString()}.00`, trend:'+12.5% from last term', up:true,  color:'#f59e0b', spark:sparkAmber,  icon:'💰' },
    { label:'Owing Students',  value: data?.owing_students ?? 256,  trend:'-8.2% from last term',  up:false, color:'#ef4444', spark:sparkRed,    icon:'👤' },
    { label:'Staff Members',   value: data?.total_staff ?? 87,      trend:'+2.1% from last term',  up:true,  color:'#2563eb', spark:sparkBlue,   icon:'👥' },
    { label:'Attendance Rate', value:'92.6%',                        trend:'+3.7% from last week',  up:true,  color:'#7c3aed', spark:sparkPurple, icon:'📅' },
  ];

  return (
    <div style={{animation:'fadeUp .2s ease'}}>

      {/* Page header */}
      <div className="page-header-row" style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',marginBottom:20}}>
        <div>
          <h1 style={{fontSize:22,fontWeight:800,color:'#111827',letterSpacing:'-.4px',margin:0,lineHeight:1.2}}>Dashboard</h1>
          <p style={{fontSize:13,color:'#6b7280',marginTop:3}}>Welcome back, {data?.admin_name ?? 'Dr. Emmanuel Chikwanda'} 👋</p>
        </div>
        <button style={{display:'flex',alignItems:'center',gap:7,padding:'9px 18px',background:'#1a6b3c',color:'#fff',border:'none',borderRadius:8,fontSize:13,fontWeight:600,cursor:'pointer',boxShadow:'0 2px 8px rgba(26,107,60,.25)'}}>
          <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download Report
        </button>
      </div>

      {/* ── KPI row ── */}
      <div className="grid-5" style={{marginBottom:18}}>
        {kpis.map(k => (
          <Panel key={k.label} style={{padding:'16px 16px 0'}}>
            <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',marginBottom:8}}>
              <div>
                <div style={{fontSize:10,fontWeight:700,color:'#6b7280',letterSpacing:'.5px',textTransform:'uppercase',marginBottom:6}}>{k.label}</div>
                <div style={{fontSize:22,fontWeight:800,color:'#111827',letterSpacing:'-.5px',lineHeight:1}}>{k.value}</div>
                <div style={{fontSize:11,marginTop:5,color:k.up?'#059669':'#ef4444',display:'flex',alignItems:'center',gap:2}}>
                  <span>{k.up?'↑':'↓'}</span><span style={{color:'#6b7280'}}>{k.trend}</span>
                </div>
              </div>
              <div style={{fontSize:10,color:'#9ca3af',cursor:'pointer'}}>···</div>
            </div>
            <Spark data={k.spark} color={k.color} />
          </Panel>
        ))}
      </div>

      {/* ── Charts row ── */}
      <div className="grid-4" style={{marginBottom:14}}>

        {/* Fees Collection */}
        <Panel>
          <PanelHead title="Fees Collection Overview" action={<TermSelect/>}/>
          <div style={{padding:'0 18px 4px',display:'flex',gap:14,marginBottom:4}}>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2.5,background:'#1a6b3c',display:'inline-block',borderRadius:2}}/> Collected</span>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2,background:'#f59e0b',display:'inline-block',borderRadius:2,borderTop:'2px dashed #f59e0b'}}/> Target</span>
          </div>
          <div style={{padding:'0 8px 14px'}}>
            <ResponsiveContainer width="100%" height={155}>
              <ComposedChart data={feesData} barSize={20}>
                <XAxis dataKey="month" tick={{fontSize:11,fill:'#9ca3af'}} axisLine={false} tickLine={false}/>
                <YAxis tick={{fontSize:10,fill:'#9ca3af'}} axisLine={false} tickLine={false} tickFormatter={v=>`$${v/1000}k`}/>
                <Tooltip formatter={(v:number)=>`$${v.toLocaleString()}`} contentStyle={{fontSize:12,borderRadius:8,border:'1px solid #e8eaed',boxShadow:'0 4px 12px rgba(0,0,0,.08)'}}/>
                <Bar dataKey="collected" fill="#1a6b3c" radius={[4,4,0,0]}/>
                <Line type="monotone" dataKey="target" stroke="#f59e0b" strokeWidth={2} strokeDasharray="4 3" dot={false}/>
              </ComposedChart>
            </ResponsiveContainer>
          </div>
        </Panel>

        {/* Class Performance */}
        <Panel>
          <PanelHead title="Class Performance Trend" action={<TermSelect/>}/>
          <div style={{padding:'0 18px 4px',display:'flex',gap:14,marginBottom:4}}>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2,background:'#1a6b3c',display:'inline-block'}}/> Class Average</span>
            <span style={{fontSize:11,color:'#374151',display:'flex',alignItems:'center',gap:5}}><span style={{width:14,height:2,background:'#f59e0b',display:'inline-block'}}/> Institute Average</span>
          </div>
          <div style={{padding:'0 8px 14px'}}>
            <ResponsiveContainer width="100%" height={155}>
              <LineChart data={perfData}>
                <XAxis dataKey="month" tick={{fontSize:11,fill:'#9ca3af'}} axisLine={false} tickLine={false}/>
                <YAxis tick={{fontSize:10,fill:'#9ca3af'}} axisLine={false} tickLine={false} domain={[0,100]} tickFormatter={v=>`${v}%`}/>
                <Tooltip formatter={(v:number)=>`${v}%`} contentStyle={{fontSize:12,borderRadius:8,border:'1px solid #e8eaed',boxShadow:'0 4px 12px rgba(0,0,0,.08)'}}/>
                <Line type="monotone" dataKey="cls"  stroke="#1a6b3c" strokeWidth={2.5} dot={{r:3,fill:'#1a6b3c'}}/>
                <Line type="monotone" dataKey="inst" stroke="#f59e0b" strokeWidth={2} strokeDasharray="4 3" dot={{r:3,fill:'#f59e0b'}}/>
              </LineChart>
            </ResponsiveContainer>
          </div>
        </Panel>

        {/* Attendance Donut */}
        <Panel>
          <PanelHead title="Attendance Overview" action={<select style={{fontSize:11,border:'1px solid #e8eaed',borderRadius:6,padding:'3px 8px',color:'#374151',cursor:'pointer',background:'#fff'}}><option>This Week</option></select>}/>
          <div style={{padding:'0 14px 14px'}}>
            <div style={{position:'relative',display:'flex',alignItems:'center',justifyContent:'center',height:130}}>
              <ResponsiveContainer width="100%" height={130}>
                <PieChart>
                  <Pie data={attendData} cx="50%" cy="50%" innerRadius={42} outerRadius={58} dataKey="value" startAngle={90} endAngle={-270}>
                    {attendData.map((e,i)=><Cell key={i} fill={e.color}/>)}
                  </Pie>
                </PieChart>
              </ResponsiveContainer>
              <div style={{position:'absolute',textAlign:'center',pointerEvents:'none'}}>
                <div style={{fontSize:18,fontWeight:800,color:'#111827'}}>92.6%</div>
                <div style={{fontSize:10,color:'#6b7280'}}>Present</div>
              </div>
            </div>
            <div style={{marginTop:6}}>
              {attendData.map(d=>(
                <div key={d.name} style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:4}}>
                  <span style={{display:'flex',alignItems:'center',gap:6,fontSize:11,color:'#374151'}}>
                    <span style={{width:7,height:7,borderRadius:'50%',background:d.color,display:'inline-block'}}/>
                    {d.name}
                  </span>
                  <span style={{fontSize:11,fontWeight:600,color:'#374151'}}>{d.value}%</span>
                </div>
              ))}
            </div>
          </div>
        </Panel>

        {/* Top Performing Classes */}
        <Panel>
          <PanelHead title="Top Performing Classes" action={<select style={{fontSize:11,border:'1px solid #e8eaed',borderRadius:6,padding:'3px 8px',color:'#374151',cursor:'pointer',background:'#fff'}}><option>This Term</option></select>}/>
          <div style={{padding:'0 16px 14px'}}>
            {topClasses.map((c,i)=>(
              <div key={c.name} style={{marginBottom:10}}>
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:4}}>
                  <span style={{fontSize:12,color:'#374151',display:'flex',alignItems:'center',gap:7}}>
                    <span style={{fontSize:11,fontWeight:700,color:'#9ca3af',width:14,textAlign:'right'}}>{i+1}</span>
                    {c.name}
                  </span>
                  <span style={{fontSize:12,fontWeight:700,color:'#111827'}}>{c.pct}%</span>
                </div>
                <div style={{height:5,background:'#f3f4f6',borderRadius:3,overflow:'hidden'}}>
                  <div style={{height:'100%',width:`${c.pct}%`,background:c.color,borderRadius:3,transition:'width .4s ease'}}/>
                </div>
              </div>
            ))}
            <Link to="/app/students" style={{fontSize:12,color:'#1a6b3c',fontWeight:600,display:'flex',alignItems:'center',gap:4,marginTop:8}}>
              View Full Report →
            </Link>
          </div>
        </Panel>
      </div>

      {/* ── Bottom row ── */}
      <div className="grid-4">

        {/* Recent Activities */}
        <Panel>
          <PanelHead title="Recent Activities" action={<Link to="/app/students" style={{fontSize:12,color:'#1a6b3c',fontWeight:600}}>View All</Link>}/>
          <div style={{padding:'0 18px 16px'}}>
            {activities.map((a,i)=>(
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
          <PanelHead title="Important Reminders" action={<Link to="/app/students" style={{fontSize:12,color:'#1a6b3c',fontWeight:600}}>View All</Link>}/>
          <div style={{padding:'0 18px 16px'}}>
            {reminders.map((r,i)=>(
              <div key={i} style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:10,paddingBottom:12,marginBottom:12,borderBottom:i<reminders.length-1?'1px solid #f3f4f6':'none'}}>
                <div style={{display:'flex',gap:9,flex:1}}>
                  <span style={{fontSize:15,flexShrink:0,marginTop:1}}>{r.icon}</span>
                  <span style={{fontSize:12,color:'#374151',lineHeight:1.5}}>{r.text}</span>
                </div>
                {r.action
                  ? <span style={{fontSize:11,color:r.actionColor,fontWeight:600,whiteSpace:'nowrap',cursor:'pointer'}}>{r.action}</span>
                  : <span style={{fontSize:11,color:'#6b7280',whiteSpace:'nowrap'}}>{r.date}</span>
                }
              </div>
            ))}
          </div>
        </Panel>

        {/* Quick Actions */}
        <Panel>
          <PanelHead title="Quick Actions"/>
          <div style={{padding:'0 14px 16px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:8}}>
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

        {/* Notifications + Promo */}
        <div style={{display:'flex',flexDirection:'column',gap:12}}>
          <Panel>
            <PanelHead title="Notifications" action={<Link to="/app/admin/users" style={{fontSize:12,color:'#1a6b3c',fontWeight:600}}>View All</Link>}/>
            <div style={{padding:'0 16px 14px'}}>
              {notifications.map((n,i)=>(
                <div key={i} style={{display:'flex',alignItems:'flex-start',gap:9,paddingBottom:10,marginBottom:10,borderBottom:i<notifications.length-1?'1px solid #f3f4f6':'none'}}>
                  <span style={{width:7,height:7,borderRadius:'50%',background:n.dot,display:'inline-block',marginTop:5,flexShrink:0}}/>
                  <div style={{flex:1}}>
                    <p style={{fontSize:12,color:'#374151',margin:0,lineHeight:1.4}}>{n.text}</p>
                    <span style={{fontSize:11,color:'#9ca3af'}}>{n.time}</span>
                  </div>
                </div>
              ))}
            </div>
          </Panel>

          {/* Promo card */}
          <div style={{
            background:'linear-gradient(135deg,#0f3d22 0%,#1a6b3c 100%)',
            borderRadius:12,padding:'18px 16px',
            display:'flex',alignItems:'flex-start',gap:12,
            position:'relative',overflow:'hidden',
          }}>
            <div style={{flex:1}}>
              <div style={{fontSize:13,fontWeight:700,color:'#fff',marginBottom:5,lineHeight:1.3}}>Keep DestinyGate Moving Forward 🚀</div>
              <p style={{fontSize:11,color:'rgba(255,255,255,.7)',margin:0,lineHeight:1.5}}>Your dashboard is updated in real-time.</p>
            </div>
            <div style={{width:52,height:52,flexShrink:0,background:'rgba(255,255,255,.15)',borderRadius:10,display:'flex',alignItems:'center',justifyContent:'center',padding:4}}>
              <img src="/logo.svg" alt="Logo" style={{width:'100%',height:'100%',objectFit:'contain',opacity:.9}}/>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}


