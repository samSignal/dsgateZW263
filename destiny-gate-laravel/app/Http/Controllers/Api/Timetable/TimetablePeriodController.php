<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetablePeriodController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('timetable_periods')->orderBy('period_number')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:80',
            'period_number' => 'required|integer|min:1|unique:timetable_periods,period_number',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'is_break'      => 'boolean',
        ]);
        $id = DB::table('timetable_periods')->insertGetId([
            'name'          => $data['name'],
            'period_number' => $data['period_number'],
            'start_time'    => $data['start_time'] . ':00',
            'end_time'      => $data['end_time'] . ':00',
            'is_break'      => $data['is_break'] ?? false,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        return response()->json(DB::table('timetable_periods')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $p = DB::table('timetable_periods')->find($id);
        abort_if(!$p, 404);
        $data = $request->validate([
            'name'       => 'required|string|max:80',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'is_break'   => 'boolean',
        ]);
        DB::table('timetable_periods')->where('id', $id)->update([
            'name'       => $data['name'],
            'start_time' => $data['start_time'] . ':00',
            'end_time'   => $data['end_time'] . ':00',
            'is_break'   => $data['is_break'] ?? $p->is_break,
            'updated_at' => now(),
        ]);
        return response()->json(DB::table('timetable_periods')->find($id));
    }

    public function deactivate(int $id)
    {
        DB::table('timetable_periods')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Period deactivated.']);
    }

    public function activate(int $id)
    {
        DB::table('timetable_periods')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Period activated.']);
    }
}
