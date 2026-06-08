<!DOCTYPE html>
<html lang="{{ str_replace(chr(95), chr(45), app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config(chr(97).chr(112).chr(112).chr(46).chr(110).chr(97).chr(109).chr(101), 'DestinyGate') }} &mdash; @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
        --sidebar-w:256px;
        --green:#1a6b3c;
        --green-dark:#0f3d22;
        --green-light:#e8f5ee;
        --text:#111827;
        --text-muted:#6b7280;
        --border:#e5e7eb;
        --bg:#f5f6fa;
        --white:#ffffff;
        --radius:10px;
        --shadow:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.04);
        --shadow-md:0 4px 12px rgba(0,0,0,.08);
    }
    html,body{height:100%;font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5}

    /* ── LAYOUT ── */
    .layout{display:flex;min-height:100vh}
    .main-wrap{flex:1;display:flex;flex-direction:column;min-width:0;margin-left:var(--sidebar-w)}

    /* ── SIDEBAR ── */
    .sidebar{
        position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);
        background:var(--green-dark);
        display:flex;flex-direction:column;
        z-index:100;overflow-y:auto;
    }
    .sidebar-brand{
        display:flex;align-items:center;gap:12px;
        padding:20px 20px 16px;
        border-bottom:1px solid rgba(255,255,255,.08);
    }
    .sidebar-brand img{width:40px;height:40px;object-fit:contain;flex-shrink:0}
    .sidebar-brand-text{line-height:1.2}
    .sidebar-brand-name{color:#fff;font-size:13px;font-weight:700;letter-spacing:.3px}
    .sidebar-brand-sub{color:rgba(255,255,255,.45);font-size:10px;font-weight:400;letter-spacing:.5px;text-transform:uppercase}

    .sidebar-section{padding:20px 12px 4px;color:rgba(255,255,255,.3);font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase}
    .sidebar nav{padding:8px 0 20px}
    .sidebar a,.sidebar button.nav-btn{
        display:flex;align-items:center;gap:10px;
        padding:9px 16px;margin:1px 8px;
        border-radius:8px;
        color:rgba(255,255,255,.65);
        font-size:13px;font-weight:500;
        text-decoration:none;
        transition:background .15s,color .15s;
        border:none;background:none;cursor:pointer;width:calc(100% - 16px);text-align:left;
    }
    .sidebar a:hover,.sidebar button.nav-btn:hover{background:rgba(255,255,255,.08);color:#fff}
    .sidebar a.active{background:rgba(255,255,255,.12);color:#fff}
    .nav-icon{width:16px;text-align:center;opacity:.8;flex-shrink:0}
    .sidebar-footer{margin-top:auto;padding:12px;border-top:1px solid rgba(255,255,255,.08)}

    /* ── TOPBAR ── */
    .topbar{
        background:var(--white);border-bottom:1px solid var(--border);
        padding:0 28px;height:60px;
        display:flex;align-items:center;justify-content:space-between;
        position:sticky;top:0;z-index:50;
    }
    .topbar-title{font-size:15px;font-weight:600;color:var(--text)}
    .topbar-right{display:flex;align-items:center;gap:12px}
    .topbar-avatar{
        width:34px;height:34px;border-radius:50%;
        background:var(--green);color:#fff;
        display:flex;align-items:center;justify-content:center;
        font-size:13px;font-weight:600;
    }
    .topbar-name{font-size:13px;color:var(--text-muted);font-weight:500}

    /* ── CONTENT ── */
    .content{padding:28px;flex:1}

    /* ── ALERTS ── */
    .alert{padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:13px;display:flex;align-items:flex-start;gap:10px}
    .alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .alert ul{margin:0;padding-left:16px}

    /* ── PAGE HEADER ── */
    .page-header{margin-bottom:24px}
    .page-header h1{font-size:20px;font-weight:700;color:var(--text);letter-spacing:-.3px}
    .page-header p{color:var(--text-muted);font-size:13px;margin-top:3px}
    .page-header-row{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px}
    .page-header-row .page-header{margin-bottom:0}

    /* ── STAT CARDS ── */
    .stats-grid{display:grid;gap:16px;margin-bottom:24px}
    .stats-grid.cols-4{grid-template-columns:repeat(4,1fr)}
    .stats-grid.cols-3{grid-template-columns:repeat(3,1fr)}
    .stats-grid.cols-2{grid-template-columns:repeat(2,1fr)}
    .stat-card{
        background:var(--white);border-radius:var(--radius);
        padding:20px 22px;box-shadow:var(--shadow);
        border:1px solid var(--border);
    }
    .stat-label{font-size:12px;font-weight:500;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
    .stat-value{font-size:26px;font-weight:700;color:var(--text);letter-spacing:-.5px;line-height:1}
    .stat-value.green{color:var(--green)}
    .stat-value.red{color:#dc2626}
    .stat-value.amber{color:#d97706}
    .stat-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:12px}
    .stat-icon.green{background:var(--green-light)}
    .stat-icon.red{background:#fef2f2}
    .stat-icon.amber{background:#fffbeb}
    .stat-icon.blue{background:#eff6ff}

    /* ── CARDS ── */
    .card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:20px}
    .card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .card-title{font-size:14px;font-weight:600;color:var(--text)}
    .card-body{padding:20px}
    .card-link{font-size:12px;color:var(--green);text-decoration:none;font-weight:500}
    .card-link:hover{text-decoration:underline}

    /* ── GRID ── */
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
    .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px}

    /* ── TABLE ── */
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    thead th{
        background:#fafafa;padding:10px 16px;
        text-align:left;font-size:11px;font-weight:600;
        color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px;
        border-bottom:1px solid var(--border);white-space:nowrap;
    }
    tbody td{padding:12px 16px;border-bottom:1px solid #f3f4f6;font-size:13px;color:var(--text);vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover td{background:#fafafa}
    .table-pagination{padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end}

    /* ── BADGES ── */
    .badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.3px;white-space:nowrap}
    .badge-green{background:#ecfdf5;color:#065f46}
    .badge-red{background:#fef2f2;color:#991b1b}
    .badge-amber{background:#fffbeb;color:#92400e}
    .badge-blue{background:#eff6ff;color:#1e40af}
    .badge-purple{background:#f5f3ff;color:#5b21b6}
    .badge-gray{background:#f3f4f6;color:#374151}

    /* role badges */
    .role-admin{background:#fef2f2;color:#991b1b}
    .role-headmaster{background:#f5f3ff;color:#5b21b6}
    .role-teacher{background:#eff6ff;color:#1e40af}
    .role-bursar{background:#fffbeb;color:#92400e}
    .role-parent{background:#ecfdf5;color:#065f46}
    .role-student{background:#e0f2fe;color:#0369a1}
    .role-user{background:#f3f4f6;color:#374151}

    /* ── BUTTONS ── */
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;border:none;text-decoration:none;transition:all .15s;white-space:nowrap}
    .btn-sm{padding:5px 12px;font-size:12px;border-radius:6px}
    .btn-primary{background:var(--green);color:#fff}
    .btn-primary:hover{background:var(--green-dark);color:#fff}
    .btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
    .btn-outline:hover{background:#f9fafb;color:var(--text)}
    .btn-danger{background:#dc2626;color:#fff}
    .btn-danger:hover{background:#b91c1c;color:#fff}
    .btn-ghost{background:transparent;color:var(--text-muted);border:none}
    .btn-ghost:hover{color:var(--text);background:#f3f4f6}

    /* ── FORMS ── */
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:12px;font-weight:600;color:var(--text);margin-bottom:5px;letter-spacing:.2px}
    .form-control{
        width:100%;padding:9px 12px;
        border:1px solid var(--border);border-radius:8px;
        font-size:13px;color:var(--text);background:var(--white);
        transition:border-color .15s,box-shadow .15s;
        font-family:inherit;
    }
    .form-control:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(26,107,60,.1)}
    .form-control::placeholder{color:#9ca3af}
    textarea.form-control{resize:vertical;min-height:80px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-row.cols-3{grid-template-columns:1fr 1fr 1fr}

    /* ── CODE ── */
    code{background:#f3f4f6;padding:2px 7px;border-radius:5px;font-size:12px;color:#374151;font-family:monospace}

    /* ── EMPTY STATE ── */
    .empty-state{text-align:center;padding:48px 20px;color:var(--text-muted)}
    .empty-state-icon{font-size:36px;margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:13px}

    /* ── DETAILS/SUMMARY ── */
    details summary{cursor:pointer;color:var(--green);font-size:13px;font-weight:500;list-style:none;padding:8px 0}
    details summary::-webkit-details-marker{display:none}
    details[open] summary{margin-bottom:12px}

    /* ── DIVIDER ── */
    .divider{border:none;border-top:1px solid var(--border);margin:16px 0}

    /* ── RESPONSIVE ── */
    @media(max-width:900px){
        .sidebar{transform:translateX(-100%)}
        .main-wrap{margin-left:0}
        .stats-grid.cols-4,.stats-grid.cols-3{grid-template-columns:1fr 1fr}
        .grid-2,.grid-3,.form-row{grid-template-columns:1fr}
    }
    @media(max-width:600px){
        .stats-grid.cols-4,.stats-grid.cols-2{grid-template-columns:1fr}
        .content{padding:16px}
    }
    </style>
</head>
<body>
<div class="layout">

    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="/logo.svg" alt="DestinyGate Logo">
            <div class="sidebar-brand-text">
                <div class="sidebar-brand-name">DestinyGate</div>
                <div class="sidebar-brand-sub">Institute</div>
            </div>
        </div>

        <nav>
            @auth
            @php $role = auth()->user()->role; @endphp

            @if($role === 'admin')
                <div class="sidebar-section">Main</div>
                <a href="{{ route('admin.dashboard') }}"><span class="nav-icon">&#9632;</span> Dashboard</a>
                <div class="sidebar-section">Management</div>
                <a href="{{ route('admin.users') }}"><span class="nav-icon">&#9632;</span> Users</a>
                <a href="{{ route('admin.staff') }}"><span class="nav-icon">&#9632;</span> Staff</a>
                <a href="{{ route('admin.classes') }}"><span class="nav-icon">&#9632;</span> Classes</a>
                <a href="{{ route('students.index') }}"><span class="nav-icon">&#9632;</span> Students</a>
                <a href="{{ route('admin.applications') }}"><span class="nav-icon">&#9632;</span> Admissions</a>
                <a href="{{ route('admin.applications') }}"><span class="nav-icon">&#9632;</span> Applications</a>

            @elseif($role === 'headmaster')
                <div class="sidebar-section">Main</div>
                <a href="{{ route('headmaster.dashboard') }}"><span class="nav-icon">&#9632;</span> Dashboard</a>
                <div class="sidebar-section">School</div>
                <a href="{{ route('students.index') }}"><span class="nav-icon">&#9632;</span> Students</a>
                <a href="{{ route('admin.applications') }}"><span class="nav-icon">&#9632;</span> Admissions</a>
                <a href="{{ route('headmaster.announcements') }}"><span class="nav-icon">&#9632;</span> Announcements</a>
                <a href="{{ route('headmaster.behaviour') }}"><span class="nav-icon">&#9632;</span> Behaviour</a>
                <a href="{{ route('headmaster.reports') }}"><span class="nav-icon">&#9632;</span> Reports</a>

            @elseif($role === 'teacher')
                <div class="sidebar-section">Main</div>
                <a href="{{ route('teacher.dashboard') }}"><span class="nav-icon">&#9632;</span> Dashboard</a>
                <a href="{{ route('teacher.classes') }}"><span class="nav-icon">&#9632;</span> My Classes</a>

            @elseif($role === 'bursar')
                <div class="sidebar-section">Main</div>
                <a href="{{ route('bursar.dashboard') }}"><span class="nav-icon">&#9632;</span> Dashboard</a>
                <div class="sidebar-section">Finance</div>
                <a href="{{ route('bursar.fees') }}"><span class="nav-icon">&#9632;</span> Student Fees</a>
                <a href="{{ route('bursar.payments') }}"><span class="nav-icon">&#9632;</span> Payments</a>
                <a href="{{ route('bursar.fee-structures') }}"><span class="nav-icon">&#9632;</span> Fee Structures</a>
                <div class="sidebar-section">Reports</div>
                <a href="{{ route('bursar.reports.debtors') }}"><span class="nav-icon">&#9632;</span> Debtors List</a>
                <a href="{{ route('bursar.reports.paid') }}"><span class="nav-icon">&#9632;</span> Paid Students</a>

            @elseif($role === 'parent')
                <div class="sidebar-section">Portal</div>
                <a href="{{ route('parent.portal') }}"><span class="nav-icon">&#9632;</span> My Dashboard</a>

            @elseif($role === 'student')
                <div class="sidebar-section">Portal</div>
                <a href="{{ route('student.portal') }}"><span class="nav-icon">&#9632;</span> My Dashboard</a>
            @endif

            <div class="sidebar-footer">
                <a href="{{ route('profile.edit') }}"><span class="nav-icon">&#9632;</span> Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-btn"><span class="nav-icon">&#9632;</span> Sign Out</button>
                </form>
            </div>
            @endauth
        </nav>
    </aside>

    {{-- MAIN --}}
    <div class="main-wrap">
        <header class="topbar">
            <span class="topbar-title">@yield('title', 'Dashboard')</span>
            @auth
            <div class="topbar-right">
                <span class="badge badge-gray role-{{ auth()->user()->role }}">{{ ucfirst(auth()->user()->role) }}</span>
                <span class="topbar-name">{{ auth()->user()->name }}</span>
                <div class="topbar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            </div>
            @endauth
        </header>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <span>&#10003;</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">
                    <span>&#33;</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <span>&#33;</span>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
