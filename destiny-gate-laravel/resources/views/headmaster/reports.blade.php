@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-header"><h1>Reports</h1><p>Generate and view school reports</p></div>
<div class="grid-3">
    <div class="stat-card" style="text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:10px;">💰</div>
        <h3>Financial Report</h3>
        <p style="color:#6b7280; font-size:0.85rem; margin:8px 0;">Fee collections, outstanding balances, debtors</p>
        <a href="{{ route('bursar.reports.debtors') }}" class="btn-primary" style="margin-top:10px;">View Debtors</a>
    </div>
    <div class="stat-card" style="text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:10px;">🎓</div>
        <h3>Student Records</h3>
        <p style="color:#6b7280; font-size:0.85rem; margin:8px 0;">All enrolled students and their details</p>
        <a href="{{ route('students.index') }}" class="btn-primary" style="margin-top:10px;">View Students</a>
    </div>
    <div class="stat-card" style="text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:10px;">⚠️</div>
        <h3>Behaviour Report</h3>
        <p style="color:#6b7280; font-size:0.85rem; margin:8px 0;">Discipline cases and actions taken</p>
        <a href="{{ route('headmaster.behaviour') }}" class="btn-primary" style="margin-top:10px;">View Cases</a>
    </div>
</div>
@endsection
