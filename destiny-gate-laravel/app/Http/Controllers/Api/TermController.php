<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TermController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('terms')
            ->join('academic_years', 'terms.academic_year_id', '=', 'academic_years.id')
            ->select('terms.*', 'academic_years.name as academic_year_name')
            ->orderBy('terms.start_date');

        if ($request->academic_year_id) {
            $query->where('terms.academic_year_id', $request->academic_year_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:50',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
        ]);

        // Unique name within academic year
        $exists = DB::table('terms')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Term name already exists in this academic year.'], 422);
        }

        $id = DB::table('terms')->insertGetId([
            'academic_year_id' => $data['academic_year_id'],
            'name'             => $data['name'],
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
            'is_current'       => false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(DB::table('terms')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $term = DB::table('terms')->find($id);
        abort_if(!$term, 404, 'Term not found.');

        $data = $request->validate([
            'name'       => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        // Unique name within academic year (excluding self)
        $exists = DB::table('terms')
            ->where('academic_year_id', $term->academic_year_id)
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Term name already exists in this academic year.'], 422);
        }

        DB::table('terms')->where('id', $id)->update([
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('terms')->find($id));
    }

    public function destroy(int $id)
    {
        $term = DB::table('terms')->find($id);
        abort_if(!$term, 404, 'Term not found.');

        $used = DB::table('teacher_allocations')->where('term_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete: term has teacher allocations.'], 422);
        }

        DB::table('terms')->where('id', $id)->delete();
        return response()->json(['message' => 'Term deleted.']);
    }

    public function setCurrent(int $id)
    {
        $term = DB::table('terms')->find($id);
        abort_if(!$term, 404, 'Term not found.');

        DB::transaction(function () use ($term, $id) {
            // Unset all terms in same academic year
            DB::table('terms')
                ->where('academic_year_id', $term->academic_year_id)
                ->update(['is_current' => false, 'updated_at' => now()]);
            // Set this one
            DB::table('terms')->where('id', $id)->update(['is_current' => true, 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Term set as current.', 'term' => DB::table('terms')->find($id)]);
    }
}
