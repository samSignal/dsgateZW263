<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#0f172a; font-size:10px; }
        h1 { margin:0; color:#1a6b3c; font-size:22px; }
        .motto { color:#b88a11; font-weight:700; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th { background:#1a6b3c; color:#fff; padding:7px; border:1px solid #dbe3ec; }
        td { height:58px; border:1px solid #dbe3ec; padding:6px; vertical-align:top; }
        .break { background:#fffbeb; color:#92400e; font-weight:700; text-align:center; }
        .lesson { background:#f8fafc; border-left:4px solid #1a6b3c; padding:5px; border-radius:3px; }
        .subject { font-weight:800; color:#1a6b3c; }
        .meta { color:#475569; font-size:9px; margin-top:2px; }
        .footer { margin-top:12px; border-top:1px solid #e2e8f0; padding-top:6px; color:#64748b; }
    </style>
</head>
<body>
<h1>DestinyGate Institute</h1>
<div class="motto">Weekly Timetable - {{ ucfirst($scope['type']) }}</div>
@php $days = ['monday','tuesday','wednesday','thursday','friday','saturday']; @endphp
<table>
    <thead>
    <tr>
        <th style="width:80px;">Day</th>
        @foreach($periods as $period)
            <th>{{ $period->name }}<br>{{ substr($period->start_time,0,5) }}-{{ substr($period->end_time,0,5) }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($days as $day)
        <tr>
            <th>{{ ucfirst($day) }}</th>
            @foreach($periods as $period)
                @php $entry = collect($entries)->first(fn($e) => $e->day_of_week === $day && (int)$e->period_id === (int)$period->id); @endphp
                <td class="{{ $period->is_break ? 'break' : '' }}">
                    @if($period->is_break)
                        Break
                    @elseif($entry)
                        <div class="lesson">
                            <div class="subject">{{ $entry->subject_name }}</div>
                            <div class="meta">{{ $entry->form_name }} {{ $entry->stream_name }}</div>
                            <div class="meta">{{ $entry->teacher_name }}</div>
                            <div class="meta">{{ $entry->room_name ?? $entry->room ?? 'No room' }}</div>
                        </div>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
<div class="footer">Generated {{ now() }} | DestinyGate Institute</div>
</body>
</html>
