<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = DB::table('academic_years')->orderByDesc('start_date')->get();
        return response()->json($years);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $id = DB::table('academic_years')->insertGetId([
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'is_active'  => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('academic_years')->find($id), 201);
    }

    public function show(int $id)
    {
        $year = DB::table('academic_years')->find($id);
        abort_if(!$year, 404, 'Academic year not found.');
        return response()->json($year);
    }

    public function update(Request $request, int $id)
    {
        $year = DB::table('academic_years')->find($id);
        abort_if(!$year, 404, 'Academic year not found.');

        $data = $request->validate([
            'name'       => 'required|string|max:20|unique:academic_years,name,' . $id,
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        DB::table('academic_years')->where('id', $id)->update([
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('academic_years')->find($id));
    }

    public function destroy(int $id)
    {
        $year = DB::table('academic_years')->find($id);
        abort_if(!$year, 404, 'Academic year not found.');

        // Dependency check
        $termCount = DB::table('terms')->where('academic_year_id', $id)->count();
        if ($termCount > 0) {
            return response()->json(['message' => 'Cannot delete: academic year has terms linked to it.'], 422);
        }

        DB::table('academic_years')->where('id', $id)->delete();
        return response()->json(['message' => 'Academic year deleted.']);
    }

    public function activate(int $id)
    {
        $year = DB::table('academic_years')->find($id);
        abort_if(!$year, 404, 'Academic year not found.');

        DB::transaction(function () use ($id) {
            DB::table('academic_years')->update(['is_active' => false, 'updated_at' => now()]);
            DB::table('academic_years')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Academic year activated.', 'year' => DB::table('academic_years')->find($id)]);
    }
}
