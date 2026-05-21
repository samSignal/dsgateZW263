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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:fee_categories,name',
            'description' => 'nullable|string|max:500',
        ]);
        $id = DB::table('fee_categories')->insertGetId([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
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
            'name'        => 'required|string|max:100|unique:fee_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);
        DB::table('fee_categories')->where('id', $id)->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
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
