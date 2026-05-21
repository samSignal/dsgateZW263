@extends('layouts.app')
@section('title', 'Fee Structures')
@section('content')
<div class="page-header"><h1>Fee Structures</h1></div>

<div class="grid-2">
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom:16px;">➕ Add Fee Structure</h3>
        <form method="POST" action="{{ route('bursar.fee-structures.store') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="{{ date('Y') . '/' . (date('Y')+1) }}" required>
                </div>
                <div class="form-group">
                    <label>Class Name</label>
                    <input type="text" name="class_name" required placeholder="e.g. Form 1">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Term</label>
                    <select name="term" required>
                        <option value="term1">Term 1</option>
                        <option value="term2">Term 2</option>
                        <option value="term3">Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" step="0.01" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-primary">Create Fee Structure</button>
        </form>
    </div>

    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;">Existing Structures</div>
        <table>
            <thead><tr><th>Class</th><th>Term</th><th>Amount</th><th>Due Date</th></tr></thead>
            <tbody>
                @foreach($structures as $s)
                <tr>
                    <td>{{ $s->class_name }}</td>
                    <td>{{ strtoupper($s->term) }}</td>
                    <td>${{ number_format($s->amount, 2) }}</td>
                    <td>{{ $s->due_date->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:16px;">{{ $structures->links() }}</div>
    </div>
</div>
@endsection
