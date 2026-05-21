<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('finance_fee_structures as fs')
            ->join('academic_years', 'fs.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'fs.term_id', '=', 'terms.id')
            ->join('fee_categories', 'fs.fee_category_id', '=', 'fee_categories.id')
            ->leftJoin('forms', 'fs.form_id', '=', 'forms.id')
            ->leftJoin('streams', 'fs.stream_id', '=', 'streams.id')
            ->select(
                'fs.*',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'fee_categories.name as category_name',
                'forms.name as form_name',
                'streams.name as stream_name'
            )
            ->orderBy('academic_years.name', 'desc')
            ->orderBy('terms.name')
            ->orderBy('forms.level');

        if ($request->academic_year_id) $q->where('fs.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('fs.term_id', $request->term_id);
        if ($request->form_id)          $q->where('fs.form_id', $request->form_id);
        if ($request->fee_category_id)  $q->where('fs.fee_category_id', $request->fee_category_id);
        if ($request->is_active !== null) $q->where('fs.is_active', (bool)$request->is_active);

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'form_id'          => 'nullable|exists:forms,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'fee_category_id'  => 'required|exists:fee_categories,id',
            'name'             => 'required|string|max:150',
            'amount'           => 'required|numeric|min:0.01',
            'due_date'         => 'nullable|date',
            'is_required'      => 'boolean',
        ]);

        // Prevent duplicate active structure
        $exists = DB::table('finance_fee_structures')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id',          $data['term_id'])
            ->where('form_id',          $data['form_id'] ?? null)
            ->where('stream_id',        $data['stream_id'] ?? null)
            ->where('fee_category_id',  $data['fee_category_id'])
            ->where('is_active',        true)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'An active fee structure already exists for this combination.'], 422);
        }

        $id = DB::table('finance_fee_structures')->insertGetId([
            'academic_year_id' => $data['academic_year_id'],
            'term_id'          => $data['term_id'],
            'form_id'          => $data['form_id'] ?? null,
            'stream_id'        => $data['stream_id'] ?? null,
            'fee_category_id'  => $data['fee_category_id'],
            'name'             => $data['name'],
            'amount'           => $data['amount'],
            'due_date'         => $data['due_date'] ?? null,
            'is_required'      => $data['is_required'] ?? true,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(DB::table('finance_fee_structures')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $fs = DB::table('finance_fee_structures')->find($id);
        abort_if(!$fs, 404, 'Fee structure not found.');

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'amount'      => 'required|numeric|min:0.01',
            'due_date'    => 'nullable|date',
            'is_required' => 'boolean',
        ]);

        DB::table('finance_fee_structures')->where('id', $id)->update([
            'name'        => $data['name'],
            'amount'      => $data['amount'],
            'due_date'    => $data['due_date'] ?? null,
            'is_required' => $data['is_required'] ?? $fs->is_required,
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('finance_fee_structures')->find($id));
    }

    public function deactivate(int $id)
    {
        DB::table('finance_fee_structures')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Fee structure deactivated.']);
    }

    public function destroy(int $id)
    {
        $used = DB::table('student_bills')->where('fee_structure_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete: bills have been generated from this structure.'], 422);
        }
        DB::table('finance_fee_structures')->where('id', $id)->delete();
        return response()->json(['message' => 'Fee structure deleted.']);
    }
}
