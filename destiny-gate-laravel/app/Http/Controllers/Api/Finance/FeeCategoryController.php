<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeCategoryController extends Controller
{
    public function index()
    {
        $cats = DB::table('fee_categories')
            ->select('fee_categories.*',
                DB::raw('(SELECT COUNT(*) FROM finance_fee_structures WHERE finance_fee_structures.fee_category_id = fee_categories.id) as structures_count'),
                DB::raw('(SELECT COUNT(*) FROM student_bills WHERE student_bills.fee_category_id = fee_categories.id) as bills_count')
            )
            ->orderBy('name')
            ->get();
        return response()->json($cats);
    }

    private const FREQUENCIES = ['per_term', 'once_off', 'as_applicable', 'per_event', 'per_project'];

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'nullable|string|max:20|unique:fee_categories,code',
            'name'        => 'required|string|max:100|unique:fee_categories,name',
            'description' => 'nullable|string|max:500',
            'frequency'   => 'nullable|in:' . implode(',', self::FREQUENCIES),
        ]);
        $id = DB::table('fee_categories')->insertGetId([
            'code'        => $data['code'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'frequency'   => $data['frequency'] ?? null,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        return response()->json(DB::table('fee_categories')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $cat = DB::table('fee_categories')->find($id);
        abort_if(!$cat, 404, 'Category not found.');
        $data = $request->validate([
            'code'        => 'nullable|string|max:20|unique:fee_categories,code,' . $id,
            'name'        => 'required|string|max:100|unique:fee_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'frequency'   => 'nullable|in:' . implode(',', self::FREQUENCIES),
        ]);
        DB::table('fee_categories')->where('id', $id)->update([
            'code'        => $data['code'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'frequency'   => $data['frequency'] ?? null,
            'updated_at'  => now(),
        ]);
        return response()->json(DB::table('fee_categories')->find($id));
    }

    public function activate(int $id)
    {
        DB::table('fee_categories')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Category activated.']);
    }

    public function deactivate(int $id)
    {
        DB::table('fee_categories')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Category deactivated.']);
    }

    public function destroy(int $id)
    {
        $used = DB::table('finance_fee_structures')->where('fee_category_id', $id)->exists()
             || DB::table('student_bills')->where('fee_category_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete: category is in use.'], 422);
        }
        DB::table('fee_categories')->where('id', $id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
