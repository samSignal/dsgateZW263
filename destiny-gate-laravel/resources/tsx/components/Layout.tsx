import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import type { User } from '../types';
import api from '../lib/api';

// ── Icon map ──────────────────────────────────────────────────────────────────

const Ic = {
  grid:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>,
  users:   <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>,
  user:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>,
  book:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>,
  dollar:  <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>,
  clip:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>,
  bell:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>,
  mail:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>,
  alert:   <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>,
  bar:     <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>,
  home:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>,
  logout:  <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>,
  gear:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>,
  chart:   <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>,
  attend:  <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>,
  shop:    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>,
  key:     <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>,
  chevron: <svg width="11" height="11" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>,
  search:  <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>,
  cal:     <svg width="13" height="13" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>,
  menu:    <svg width="17" height="17" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>,
  down:    <svg width="11" height="11" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>,
  notif:   <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>,
  log:     <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>,
};

interface NavItem { label: string; path: string; icon: React.ReactNode }
interface NavGroup { section: string; items: NavItem[] }

function navForRole(role: string): NavGroup[] {
  switch (role) {
    case 'admin': return [
      { section: '', items: [{ label: 'Dashboard', path: '/app/admin', icon: Ic.grid }] },
      { section: 'ACADEMICS', items: [
        { label: 'Students',          path: '/app/students',                  icon: Ic.users },
        { label: 'Assessments',       path: '/app/assessments',               icon: Ic.chart },
        { label: 'Assessment Types',  path: '/app/assessments/types',         icon: Ic.clip },
        { label: 'Assessment Reports',path: '/app/assessments/reports',       icon: Ic.bar },
        { label: 'Report Cards',      path: '/app/reports',                   icon: Ic.log },
        { label: 'Stream Rankings',   path: '/app/reports/rankings/stream',   icon: Ic.bar },
        { label: 'Attendance',        path: '/app/discipline/attendance',     icon: Ic.attend },
      ]},
      { section: 'ADMISSIONS', items: [
        { label: 'Admissions Office', path: '/app/admissions', icon: Ic.users },
      ]},
      { section: 'ACADEMIC SETUP', items: [
        { label: 'Academic Years',    path: '/app/academic/years',            icon: Ic.cal },
        { label: 'Terms',             path: '/app/academic/terms',            icon: Ic.cal },
        { label: 'Forms',             path: '/app/academic/forms',            icon: Ic.book },
        { label: 'Streams',           path: '/app/academic/streams',          icon: Ic.book },
        { label: 'Subject Groups',    path: '/app/academic/subject-groups',   icon: Ic.grid },
        { label: 'Subjects',          path: '/app/academic/subjects',         icon: Ic.book },
        { label: 'Teacher Allocation',path: '/app/academic/allocations',      icon: Ic.users },
        { label: 'Stream Subjects',    path: '/app/academic-foundation/stream-subjects', icon: Ic.book },
        { label: 'Teacher Subjects',   path: '/app/academic-foundation/teacher-allocations', icon: Ic.users },
        { label: 'Student Enrolment',  path: '/app/academic-foundation/enrolment', icon: Ic.clip },
        { label: 'Subject Reports',    path: '/app/academic-foundation/reports', icon: Ic.bar },
      ]},      { section: 'TIMETABLE', items: [
        { label: 'Timetable Builder', path: '/app/timetable/builder',  icon: Ic.attend },
        { label: 'Periods',           path: '/app/timetable/periods',  icon: Ic.cal },
        { label: 'Rooms & Labs',      path: '/app/timetable/rooms',    icon: Ic.book },
        { label: 'Teacher Schedules', path: '/app/timetable/teachers', icon: Ic.user },
      ]},      { section: 'FINANCE', items: [
        { label: 'Finance Dashboard',  path: '/app/finance',             icon: Ic.dollar },
        { label: 'Fee Categories',     path: '/app/finance/categories',  icon: Ic.clip },
        { label: 'Fee Structures',     path: '/app/finance/structures',  icon: Ic.clip },
        { label: 'Student Bills',      path: '/app/finance/bills',       icon: Ic.clip },
        { label: 'Generate Bills',     path: '/app/finance/generate',    icon: Ic.bar },
        { label: 'Record Payment',     path: '/app/finance/payments',    icon: Ic.dollar },
        { label: 'Finance Reports',    path: '/app/finance',             icon: Ic.bar },
      ]},
      { section: 'SCHOOL SHOP', items: [
        { label: 'Shop Dashboard',      path: '/app/shop',                icon: Ic.shop },
        { label: 'Categories',          path: '/app/shop/categories',     icon: Ic.grid },
        { label: 'Items & Stock',       path: '/app/shop/items',          icon: Ic.clip },
        { label: 'Record Purchase',     path: '/app/shop/record',         icon: Ic.shop },
        { label: 'Shop Reports',        path: '/app/shop/reports',        icon: Ic.bar },
      ]},
      { section: 'DISCIPLINE', items: [
        { label: 'Attendance',          path: '/app/discipline/attendance', icon: Ic.attend },
        { label: 'Attendance Reports',  path: '/app/discipline/attendance/reports', icon: Ic.bar },
        { label: 'Behaviour Incidents', path: '/app/discipline/behaviour/incidents', icon: Ic.alert },
        { label: 'Behaviour Categories',path: '/app/discipline/behaviour/categories', icon: Ic.grid },
        { label: 'Discipline Actions',  path: '/app/discipline/actions', icon: Ic.clip },
      ]},
      { section: 'PEOPLE', items: [
        { label: 'Departments',        path: '/app/staff/departments', icon: Ic.grid },
        { label: 'Staff',              path: '/app/staff',             icon: Ic.user },
        { label: 'Teachers',           path: '/app/staff/teachers',    icon: Ic.book },
        { label: 'Parents / Guardians',path: '/app/admin/users',       icon: Ic.users },
        { label: 'Users & Roles',      path: '/app/admin/users',       icon: Ic.key },
      ]},      { section: 'COMMUNICATION', items: [
        { label: 'Notifications', path: '/app/headmaster/announcements', icon: Ic.notif },
        { label: 'Announcements', path: '/app/headmaster/announcements', icon: Ic.bell },
      ]},
      { section: 'REPORTS', items: [
        { label: 'Academic Reports',   path: '/app/headmaster/reports', icon: Ic.bar },
        { label: 'Discipline',         path: '/app/headmaster/discipline', icon: Ic.alert },
        { label: 'Attendance Reports', path: '/app/discipline/attendance/reports', icon: Ic.attend },
      ]},
      { section: 'SETTINGS', items: [
        { label: 'Roles & Permissions', path: '/app/admin/roles', icon: Ic.key },
        { label: 'Settings',            path: '/app/admin/users', icon: Ic.gear },
        { label: 'System Logs',         path: '/app/admin/users', icon: Ic.log },
      ]},
    ];
    case 'headmaster': return [
      { section: '', items: [{ label: 'Dashboard', path: '/app/headmaster', icon: Ic.grid }] },
      { section: 'ACADEMICS', items: [
        { label: 'Students',   path: '/app/students',               icon: Ic.users },
        { label: 'Attendance', path: '/app/discipline/attendance/reports', icon: Ic.attend },
        { label: 'Results',    path: '/app/assessments/reports',    icon: Ic.chart },
        { label: 'Report Cards', path: '/app/reports',              icon: Ic.log },
        { label: 'Rankings',   path: '/app/reports/rankings/stream', icon: Ic.bar },
      ]},
      { section: 'SCHOOL', items: [
        { label: 'Announcements', path: '/app/headmaster/announcements', icon: Ic.bell },
        { label: 'Behaviour',     path: '/app/headmaster/behaviour',     icon: Ic.alert },
        { label: 'Reports',       path: '/app/headmaster/reports',       icon: Ic.bar },
        { label: 'Shop Reports',  path: '/app/shop/reports',             icon: Ic.shop },
        { label: 'Discipline',    path: '/app/headmaster/discipline',    icon: Ic.alert },
      ]},
    ];
    case 'teacher': return [
      { section: '', items: [{ label: 'Dashboard', path: '/app/teacher', icon: Ic.grid }] },
      { section: 'TEACHING', items: [
        { label: 'My Classes',      path: '/app/teacher/classes',              icon: Ic.book },
        { label: 'My Allocations',  path: '/app/teacher/allocations',          icon: Ic.users },
        { label: 'Assessments',     path: '/app/assessments',                  icon: Ic.chart },
        { label: 'Create Assessment',path: '/app/assessments/create',          icon: Ic.clip },
        { label: 'Report Comments', path: '/app/reports/comments',             icon: Ic.log },
        { label: 'My Timetable',    path: '/app/timetable/my-timetable',       icon: Ic.attend },
        { label: 'Attendance',      path: '/app/discipline/attendance',        icon: Ic.attend },
        { label: 'Behaviour',       path: '/app/discipline/behaviour/incidents',icon: Ic.alert },
      ]},
    ];
    case 'bursar': return [
      { section: '', items: [{ label: 'Dashboard', path: '/app/bursar', icon: Ic.grid }] },
      { section: 'TIMETABLE', items: [
        { label: 'Timetable Builder', path: '/app/timetable/builder',  icon: Ic.attend },
        { label: 'Periods',           path: '/app/timetable/periods',  icon: Ic.cal },
        { label: 'Rooms & Labs',      path: '/app/timetable/rooms',    icon: Ic.book },
        { label: 'Teacher Schedules', path: '/app/timetable/teachers', icon: Ic.user },
      ]},      { section: 'FINANCE', items: [
        { label: 'Student Fees',   path: '/app/bursar/fees',           icon: Ic.dollar },
        { label: 'Payments',       path: '/app/bursar/payments',       icon: Ic.clip },
        { label: 'Fee Structures', path: '/app/bursar/fee-structures', icon: Ic.clip },
        { label: 'School Shop',    path: '/app/shop',                  icon: Ic.shop },
        { label: 'Record Purchase',path: '/app/shop/record',           icon: Ic.shop },
      ]},
      { section: 'REPORTS', items: [
        { label: 'Debtors List',  path: '/app/bursar/debtors', icon: Ic.alert },
        { label: 'Paid Students', path: '/app/bursar/paid',    icon: Ic.users },
        { label: 'Shop Reports',  path: '/app/shop/reports',   icon: Ic.bar },
      ]},
    ];
    case 'parent':  return [{ section: '', items: [
      { label: 'My Dashboard', path: '/app/parent',         icon: Ic.home },
      { label: 'My Fees',      path: '/app/finance/parent', icon: Ic.dollar },
      { label: 'My Purchases', path: '/app/parent/shop/purchases', icon: Ic.shop },
      { label: 'Attendance & Behaviour', path: '/app/parent/discipline', icon: Ic.attend },
      { label: 'Notifications', path: '/app/parent/notifications', icon: Ic.bell },
      { label: 'Child Subjects', path: '/app/parent/subjects', icon: Ic.book },
      { label: 'Assessments', path: '/app/assessments/parent', icon: Ic.chart },
      { label: 'Report Cards', path: '/app/parent/reports', icon: Ic.log },
    ]}];
    case 'storekeeper': return [
      { section: '', items: [{ label: 'Items & Stock', path: '/app/shop/items', icon: Ic.clip }] },
      { section: 'SCHOOL SHOP', items: [
        { label: 'Record Purchase', path: '/app/shop/record', icon: Ic.shop },
      ]},
    ];
    case 'student': return [{ section: '', items: [
      { label: 'My Dashboard', path: '/app/student', icon: Ic.home },
      { label: 'Attendance & Behaviour', path: '/app/student/discipline', icon: Ic.attend },
      { label: 'Notifications', path: '/app/student/notifications', icon: Ic.bell },
      { label: 'My Subjects', path: '/app/student/subjects', icon: Ic.book },
      { label: 'My Marks', path: '/app/assessments/student', icon: Ic.chart },
      { label: 'Report Cards', path: '/app/student/reports', icon: Ic.log },
    ] }];
    default: return [];
  }
}

export function RoleBadge({ role }: { role: string }) {
  const m: Record<string, [string,string]> = {
    admin:['#fef2f2','#991b1b'], headmaster:['#f5f3ff','#5b21b6'],
    teacher:['#eff6ff','#1e40af'], bursar:['#fffbeb','#92400e'],
    parent:['#ecfdf5','#065f46'], student:['#e0f2fe','#0369a1'], user:['#f3f4f6','#374151'],
  };
  const [bg,color] = m[role]??m.user;
  return <span style={{padding:'2px 10px',borderRadius:20,fontSize:11,fontWeight:600,background:bg,color,textTransform:'capitalize',whiteSpace:'nowrap'}}>{role}</span>;
}

interface Props { user: User; onLogout: () => void; children: React.ReactNode }

export default function Layout({ user, onLogout, children }: Props) {
  const location = useLocation();
  const navigate = useNavigate();
  const [loggingOut, setLoggingOut] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(true);   // desktop: open by default
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 900);
  const nav = navForRole(user.role);
  const initials = user.name.split(' ').map(n=>n[0]).join('').slice(0,2).toUpperCase();

  // Track viewport width
  useEffect(() => {
    const handler = () => setIsMobile(window.innerWidth <= 900);
    window.addEventListener('resize', handler);
    return () => window.removeEventListener('resize', handler);
  }, []);

  // Close sidebar on route change — mobile only
  useEffect(() => {
    if (isMobile) setSidebarOpen(false);
  }, [location.pathname]);

  const isActive = (path: string) =>
    location.pathname === path || (path.length > 5 && location.pathname.startsWith(path));

  const handleLogout = async () => {
    setLoggingOut(true);
    try { await api.post('/logout'); } catch {}
    onLogout(); navigate('/app');
  };

  const pageTitle = nav.flatMap(g=>g.items).find(i=>isActive(i.path))?.label ?? 'Dashboard';

  /* ── Sidebar content (shared between desktop + mobile) ── */
  const SidebarContent = ({ onClose }: { onClose?: () => void }) => (
    <>
      {/* Brand */}
      <div style={{
        padding:'16px 14px 14px',
        borderBottom:'1px solid rgba(255,255,255,.1)',
        display:'flex', alignItems:'center', justifyContent:'space-between',
      }}>
        <div style={{display:'flex', alignItems:'center', gap:10}}>
          <div style={{
            width:42, height:42, flexShrink:0,
            background:'#c9a227',
            borderRadius:9, border:'2px solid rgba(255,255,255,.25)',
            display:'flex', alignItems:'center', justifyContent:'center', padding:4,
          }}>
            <img src="/logo.svg" alt="Logo" style={{width:'100%', height:'100%', objectFit:'contain'}}/>
          </div>
          <div>
            <div style={{fontSize:14, fontWeight:800, color:'#fff', letterSpacing:'.3px', lineHeight:1.1}}>DESTINYGATE</div>
            <div style={{fontSize:9, color:'rgba(255,255,255,.55)', fontWeight:600, letterSpacing:'1.2px', textTransform:'uppercase', marginTop:2}}>INSTITUTE</div>
          </div>
        </div>
        {onClose && (
          <button onClick={onClose} style={{background:'none', border:'none', cursor:'pointer', color:'rgba(255,255,255,.6)', fontSize:20, lineHeight:1, padding:4}}>×</button>
        )}
      </div>

      {/* Nav */}
      <nav style={{flex:1, padding:'6px 0 12px', overflowY:'auto'}}>
        {nav.map((group, gi) => (
          <div key={gi}>
            {group.section && (
              <div style={{padding:'12px 14px 4px', fontSize:9.5, fontWeight:700, color:'rgba(255,255,255,.35)', letterSpacing:'1px', textTransform:'uppercase'}}>
                {group.section}
              </div>
            )}
            {group.items.map(item => {
              const active = isActive(item.path);
              return (
                <Link key={item.label} to={item.path} style={{
                  display:'flex', alignItems:'center', gap:9,
                  padding:'8px 10px 8px 14px', margin:'1px 6px',
                  borderRadius:8,
                  color: active ? '#fff' : 'rgba(255,255,255,.65)',
                  background: active ? 'rgba(255,255,255,.15)' : 'transparent',
                  fontSize:13, fontWeight: active ? 600 : 400,
                  transition:'all .12s',
                  borderLeft: active ? '3px solid #4ade80' : '3px solid transparent',
                }}>
                  <span style={{opacity: active ? 1 : .6, flexShrink:0}}>{item.icon}</span>
                  {item.label}
                </Link>
              );
            })}
          </div>
        ))}
      </nav>

      {/* User card */}
      <div style={{padding:'10px 12px 14px', borderTop:'1px solid rgba(255,255,255,.1)'}}>
        <div style={{display:'flex', alignItems:'center', gap:9, padding:'8px 10px', borderRadius:8, background:'rgba(255,255,255,.08)', cursor:'pointer'}}>
          <div style={{width:32, height:32, borderRadius:'50%', background:'rgba(255,255,255,.2)', color:'#fff', display:'flex', alignItems:'center', justifyContent:'center', fontSize:11, fontWeight:700, flexShrink:0}}>{initials}</div>
          <div style={{flex:1, minWidth:0}}>
            <div style={{fontSize:12, fontWeight:600, color:'#fff', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{user.name}</div>
            <div style={{fontSize:10, color:'rgba(255,255,255,.5)', textTransform:'capitalize', whiteSpace:'nowrap'}}>
              {user.role === 'admin' ? 'Super Admin' : user.role}
            </div>
          </div>
          <div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:2,flexShrink:0}}>
            <button onClick={handleLogout} disabled={loggingOut} title="Sign out" style={{background:'none', border:'none', cursor:'pointer', color:'rgba(255,255,255,.4)', padding:2, display:'flex'}}>
              {Ic.logout}
            </button>
          </div>
        </div>
      </div>
    </>
  );

  return (
    <div style={{display:'flex',minHeight:'100vh',background:'#f5f6fa',fontFamily:"'Inter',-apple-system,sans-serif",fontSize:13}}>

      {/* ── DESKTOP SIDEBAR ── */}
      {!isMobile && sidebarOpen && (
        <aside className="sidebar-desktop" style={{
          width:200, background:'#0f3d22',
          display:'flex', flexDirection:'column',
          position:'fixed', top:0, left:0, bottom:0, zIndex:100, overflowY:'auto',
          transition:'transform .22s ease',
        }}>
          <SidebarContent />
        </aside>
      )}

      {/* ── MOBILE SIDEBAR OVERLAY ── */}
      {isMobile && sidebarOpen && (
        <>
          <div className="sidebar-overlay" onClick={() => setSidebarOpen(false)} />
          <aside className="sidebar-mobile" style={{display:'flex', flexDirection:'column', background:'#0f3d22'}}>
            <SidebarContent onClose={() => setSidebarOpen(false)} />
          </aside>
        </>
      )}

      {/* ── MAIN ── */}
      <div className="main-wrap" style={{
        marginLeft: isMobile ? 0 : (sidebarOpen ? 200 : 0),
        flex:1, display:'flex', flexDirection:'column', minWidth:0,
        transition:'margin-left .22s ease',
      }}>

        {/* Topbar */}
        <header style={{
          background:'#fff',borderBottom:'1px solid #e8eaed',
          padding:'0 16px',height:56,
          display:'flex',alignItems:'center',justifyContent:'space-between',
          position:'sticky',top:0,zIndex:50,gap:10,
        }}>
          {/* Left: hamburger + title */}
          <div style={{display:'flex',alignItems:'center',gap:10,flexShrink:0}}>
            <button
              onClick={() => setSidebarOpen(s => !s)}
              style={{background:'none',border:'none',cursor:'pointer',color:'#6b7280',display:'flex',padding:4,borderRadius:6,flexShrink:0}}
            >{Ic.menu}</button>
            <span style={{fontSize:15,fontWeight:700,color:'#111827',whiteSpace:'nowrap'}}>{pageTitle}</span>
          </div>

          {/* Search — hidden on small screens via CSS */}
          <div className="topbar-search" style={{flex:1,maxWidth:400,position:'relative'}}>
            <span style={{position:'absolute',left:11,top:'50%',transform:'translateY(-50%)',color:'#9ca3af',pointerEvents:'none'}}>{Ic.search}</span>
            <input
              placeholder="Search for students, classes, staff, payments…"
              style={{width:'100%',padding:'7px 44px 7px 34px',border:'1px solid #e8eaed',borderRadius:8,fontSize:12,color:'#374151',background:'#f9fafb',boxSizing:'border-box'}}
            />
            <span style={{position:'absolute',right:10,top:'50%',transform:'translateY(-50%)',fontSize:10,color:'#9ca3af',background:'#efefef',padding:'2px 6px',borderRadius:4,fontFamily:'monospace',letterSpacing:'.5px'}}>⌘K</span>
          </div>

          {/* Right controls */}
          <div style={{display:'flex',alignItems:'center',gap:6,flexShrink:0}}>
            {/* Term selector — hidden on small screens */}
            <div className="topbar-term" style={{display:'flex',alignItems:'center',gap:5,padding:'5px 10px',border:'1px solid #e8eaed',borderRadius:7,cursor:'pointer',fontSize:11.5,color:'#374151',background:'#fff',whiteSpace:'nowrap'}}>
              <span style={{color:'#9ca3af'}}>{Ic.cal}</span>
              <span style={{fontWeight:600}}>Term 2, {new Date().getFullYear()}</span>
              <span style={{color:'#9ca3af',fontSize:10}}>Apr 14 – Jul 18, {new Date().getFullYear()}</span>
              <span style={{color:'#9ca3af'}}>{Ic.down}</span>
            </div>

            {/* Bell with badge */}
            <div style={{position:'relative',cursor:'pointer'}}>
              <div style={{width:34,height:34,borderRadius:8,border:'1px solid #e8eaed',display:'flex',alignItems:'center',justifyContent:'center',color:'#6b7280',background:'#fff'}}>{Ic.bell}</div>
              <span style={{
                position:'absolute',top:-4,right:-4,
                minWidth:16,height:16,
                background:'#f59e0b',borderRadius:8,
                border:'2px solid #fff',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontSize:9,fontWeight:700,color:'#fff',lineHeight:1,
                padding:'0 3px',
              }}>3</span>
            </div>

            {/* Mail with badge */}
            <div style={{position:'relative',cursor:'pointer'}}>
              <div style={{width:34,height:34,borderRadius:8,border:'1px solid #e8eaed',display:'flex',alignItems:'center',justifyContent:'center',color:'#6b7280',background:'#fff'}}>{Ic.mail}</div>
              <span style={{
                position:'absolute',top:-4,right:-4,
                minWidth:16,height:16,
                background:'#1a6b3c',borderRadius:8,
                border:'2px solid #fff',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontSize:9,fontWeight:700,color:'#fff',lineHeight:1,
                padding:'0 3px',
              }}>2</span>
            </div>

            {/* User card */}
            <div style={{display:'flex',alignItems:'center',gap:8,padding:'4px 10px 4px 5px',border:'1px solid #e8eaed',borderRadius:8,cursor:'pointer',background:'#fff'}}>
              <div style={{width:28,height:28,borderRadius:'50%',background:'#c9a227',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',fontSize:11,fontWeight:700,flexShrink:0}}>{initials}</div>
              <div className="topbar-user-name">
                <div style={{fontSize:12,fontWeight:600,color:'#111827',lineHeight:1.2,whiteSpace:'nowrap'}}>{user.name}</div>
                <div style={{fontSize:10,color:'#9ca3af',textTransform:'capitalize',whiteSpace:'nowrap'}}>
                  {user.role === 'admin' ? 'Super Admin' : user.role}
                </div>
              </div>
              <span style={{color:'#9ca3af'}}>{Ic.down}</span>
            </div>
          </div>
        </header>

        {/* Page content */}
        <main className="content-pad" style={{padding:'22px 24px',flex:1}}>
          {children}
        </main>

        {/* Footer */}
        <footer style={{padding:'10px 24px',borderTop:'1px solid #f0f0f0',display:'flex',justifyContent:'space-between',alignItems:'center',flexWrap:'wrap',gap:6,fontSize:11,color:'#9ca3af',background:'#fff'}}>
          <div style={{display:'flex',alignItems:'center',gap:8}}>
            <div style={{
              width:26,height:26,borderRadius:6,
              background:'#1a6b3c',
              display:'flex',alignItems:'center',justifyContent:'center',padding:3,
              flexShrink:0,
            }}>
              <img src="/logo.svg" alt="Logo" style={{width:'100%',height:'100%',objectFit:'contain'}}/>
            </div>
            <span>© {new Date().getFullYear()} DestinyGate Institute. All rights reserved.</span>
          </div>
          <span>SMN Dev Consultancy</span>
        </footer>
      </div>
    </div>
  );
}


