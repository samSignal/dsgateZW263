<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BehaviourCategoryController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('behaviour_categories')
            ->select('behaviour_categories.*', DB::raw('(SELECT COUNT(*) FROM behaviour_incidents WHERE behaviour_incidents.behaviour_category_id = behaviour_categories.id) as incidents_count'))
            ->orderBy('type')
            ->orderBy('name')
            ->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:behaviour_categories,name', 'type' => 'required|in:positive,negative', 'description' => 'nullable|string']);
        $id = DB::table('behaviour_categories')->insertGetId([...$data, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(DB::table('behaviour_categories')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('behaviour_categories')->where('id', $id)->exists(), 404, 'Category not found.');
        $data = $request->validate(['name' => 'required|string|max:100|unique:behaviour_categories,name,' . $id, 'type' => 'required|in:positive,negative', 'description' => 'nullable|string']);
        DB::table('behaviour_categories')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        return response()->json(DB::table('behaviour_categories')->where('id', $id)->first());
    }

    public function activate(int $id) { DB::table('behaviour_categories')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]); return response()->json(['message' => 'Category activated.']); }
    public function deactivate(int $id) { DB::table('behaviour_categories')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]); return response()->json(['message' => 'Category deactivated.']); }

    public function destroy(int $id)
    {
        if (DB::table('behaviour_incidents')->where('behaviour_category_id', $id)->exists()) return response()->json(['message' => 'Cannot delete category with linked incidents.'], 422);
        DB::table('behaviour_categories')->where('id', $id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
