@extends('layouts.app')
@section('title', 'Bursar Dashboard')
@section('content')
<div class="page-header">
    <h1>Bursar Dashboard</h1>
    <p>Finance and fee management</p>
</div>

<div class="grid-4">
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Total Collected ({{ date('Y') }})</div>
        <div class="value" style="font-size:1.4rem;">${{ number_format($stats['total_collected'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Outstanding Balance</div>
        <div class="value" style="font-size:1.4rem; color:#dc2626;">${{ number_format($stats['total_outstanding'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Paid in Full</div>
        <div class="value" style="color:#059669;">{{ $stats['paid_in_full'] }}</div>
    </div>
    <div class="stat-card">
        <div style="color:#6b7280; font-size:0.85rem;">Debtors</div>
        <div class="value" style="color:#d97706;">{{ $stats['debtors_count'] }}</div>
    </div>
</div>

<div class="grid-2">
    <!-- Record Payment -->
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom:16px; color:#374151;">💳 Record Payment</h3>
        <form method="POST" action="{{ route('bursar.payments.store') }}">
            @csrf
            <div class="form-group">
                <label>Student ID</label>
                <input type="number" name="student_id" required placeholder="Student ID">
            </div>
            <div class="form-group">
                <label>Student Fee ID</label>
                <input type="number" name="student_fee_id" required placeholder="Fee record ID">
            </div>
            <div class="form-group">
                <label>Amount ($)</label>
                <input type="number" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="check">Check</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-primary">Record Payment</button>
        </form>
    </div>

    <!-- Recent Payments -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; display:flex; justify-content:space-between;">
            <span>🧾 Recent Payments</span>
            <a href="{{ route('bursar.payments') }}" style="font-size:0.8rem; color:#8a6b34;">View all</a>
        </div>
        <table>
            <thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($recentPayments as $payment)
                <tr>
                    <td>{{ $payment->student->full_name }}</td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}</td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
