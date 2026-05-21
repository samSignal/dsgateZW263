<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('shop_categories')
            ->select('shop_categories.*', DB::raw('(SELECT COUNT(*) FROM shop_items WHERE shop_items.shop_category_id = shop_categories.id) as items_count'))
            ->orderBy('name');

        if ($request->filled('active')) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:shop_categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $id = DB::table('shop_categories')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('shop_categories')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('shop_categories')->where('id', $id)->exists(), 404, 'Category not found.');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:shop_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('shop_categories')->where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('shop_categories')->find($id));
    }

    public function activate(int $id)
    {
        DB::table('shop_categories')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Category activated.']);
    }

    public function deactivate(int $id)
    {
        DB::table('shop_categories')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Category deactivated.']);
    }

    public function destroy(int $id)
    {
        if (DB::table('shop_items')->where('shop_category_id', $id)->exists()) {
            return response()->json(['message' => 'Cannot delete category while items are linked.'], 422);
        }

        DB::table('shop_categories')->where('id', $id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
