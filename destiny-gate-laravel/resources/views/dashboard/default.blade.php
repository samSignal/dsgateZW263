@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-header">
    <h1>Welcome, {{ $user->name }}</h1>
    <p>Your role is <strong>{{ $user->role }}</strong>. Please contact an administrator to get the correct access.</p>
</div>
@endsection
