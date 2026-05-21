<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableRoomController extends Controller
{
    public function index()
    {
        return response()->json(
            DB::table('timetable_rooms')
                ->select('timetable_rooms.*',
                    DB::raw('(SELECT COUNT(*) FROM school_timetables WHERE school_timetables.room_id = timetable_rooms.id) as usage_count')
                )
                ->orderBy('room_name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:100',
            'room_code' => 'nullable|string|max:30|unique:timetable_rooms,room_code',
            'room_type' => 'required|in:classroom,laboratory,computer_lab,hall,sports',
            'capacity'  => 'nullable|integer|min:1',
        ]);
        $id = DB::table('timetable_rooms')->insertGetId([
            'room_name'  => $data['room_name'],
            'room_code'  => $data['room_code'] ?? null,
            'room_type'  => $data['room_type'],
            'capacity'   => $data['capacity'] ?? null,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(DB::table('timetable_rooms')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $room = DB::table('timetable_rooms')->find($id);
        abort_if(!$room, 404);
        $data = $request->validate([
            'room_name' => 'required|string|max:100',
            'room_code' => 'nullable|string|max:30|unique:timetable_rooms,room_code,' . $id,
            'room_type' => 'required|in:classroom,laboratory,computer_lab,hall,sports',
            'capacity'  => 'nullable|integer|min:1',
        ]);
        DB::table('timetable_rooms')->where('id', $id)->update([
            'room_name'  => $data['room_name'],
            'room_code'  => $data['room_code'] ?? null,
            'room_type'  => $data['room_type'],
            'capacity'   => $data['capacity'] ?? null,
            'updated_at' => now(),
        ]);
        return response()->json(DB::table('timetable_rooms')->find($id));
    }

    public function deactivate(int $id)
    {
        DB::table('timetable_rooms')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Room deactivated.']);
    }

    public function activate(int $id)
    {
        DB::table('timetable_rooms')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Room activated.']);
    }
}
