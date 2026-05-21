<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentTypeController extends Controller
{
    public function index()
    {
        return response()->json(
            DB::table('assessment_types')
                ->select('assessment_types.*',
                    DB::raw('(SELECT COUNT(*) FROM assessments WHERE assessments.assessment_type_id = assessment_types.id) as assessments_count')
                )
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100|unique:assessment_types,name',
            'weight_percentage'  => 'nullable|numeric|min:0|max:100',
            'description'        => 'nullable|string|max:500',
        ]);

        $id = DB::table('assessment_types')->insertGetId([
            'name'              => $data['name'],
            'weight_percentage' => $data['weight_percentage'] ?? 0,
            'description'       => $data['description'] ?? null,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json(DB::table('assessment_types')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $type = DB::table('assessment_types')->find($id);
        abort_if(!$type, 404, 'Assessment type not found.');

        $data = $request->validate([
            'name'              => 'required|string|max:100|unique:assessment_types,name,' . $id,
            'weight_percentage' => 'nullable|numeric|min:0|max:100',
            'description'       => 'nullable|string|max:500',
        ]);

        DB::table('assessment_types')->where('id', $id)->update([
            'name'              => $data['name'],
            'weight_percentage' => $data['weight_percentage'] ?? $type->weight_percentage,
            'description'       => $data['description'] ?? null,
            'updated_at'        => now(),
        ]);

        return response()->json(DB::table('assessment_types')->find($id));
    }

    public function deactivate(int $id)
    {
        DB::table('assessment_types')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment type deactivated.']);
    }

    public function activate(int $id)
    {
        DB::table('assessment_types')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment type activated.']);
    }

    public function gradingScales()
    {
        return response()->json(DB::table('grading_scales')->where('is_active', true)->orderByDesc('min_percentage')->get());
    }
}
