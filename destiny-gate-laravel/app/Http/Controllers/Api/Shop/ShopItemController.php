<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopItemController extends Controller
{
    private function baseQuery()
    {
        return DB::table('shop_items as si')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('si.*', 'sc.name as category_name', DB::raw('CASE WHEN si.quantity_in_stock <= si.reorder_level THEN 1 ELSE 0 END as is_low_stock'));
    }

    public function index(Request $request)
    {
        $q = $this->baseQuery()->orderBy('si.item_name');

        if ($request->filled('category_id')) $q->where('si.shop_category_id', $request->category_id);
        if ($request->filled('active')) $q->where('si.is_active', (bool) $request->boolean('active'));
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) {
                $x->where('si.item_name', 'like', $s)->orWhere('si.item_code', 'like', $s)->orWhere('si.size', 'like', $s)->orWhere('sc.name', 'like', $s);
            });
        }

        return response()->json($q->get());
    }

    public function show(int $id)
    {
        $item = $this->baseQuery()->where('si.id', $id)->first();
        abort_if(!$item, 404, 'Item not found.');
        return response()->json($item);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_category_id' => 'required|exists:shop_categories,id',
            'item_name' => 'required|string|max:150',
            'size' => 'nullable|string|max:50',
            'item_code' => 'required|string|max:50|unique:shop_items,item_code',
            'description' => 'nullable|string|max:1000',
            'unit_price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $id = DB::table('shop_items')->insertGetId([
            'shop_category_id' => $data['shop_category_id'],
            'item_name' => $data['item_name'],
            'size' => $data['size'] ?? null,
            'item_code' => $data['item_code'],
            'description' => $data['description'] ?? null,
            'unit_price' => $data['unit_price'],
            'quantity_in_stock' => $data['quantity_in_stock'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json($this->baseQuery()->where('si.id', $id)->first(), 201);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('shop_items')->where('id', $id)->exists(), 404, 'Item not found.');

        $data = $request->validate([
            'shop_category_id' => 'required|exists:shop_categories,id',
            'item_name' => 'required|string|max:150',
            'size' => 'nullable|string|max:50',
            'item_code' => 'required|string|max:50|unique:shop_items,item_code,' . $id,
            'description' => 'nullable|string|max:1000',
            'unit_price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        DB::table('shop_items')->where('id', $id)->update([
            'shop_category_id' => $data['shop_category_id'],
            'item_name' => $data['item_name'],
            'size' => $data['size'] ?? null,
            'item_code' => $data['item_code'],
            'description' => $data['description'] ?? null,
            'unit_price' => $data['unit_price'],
            'quantity_in_stock' => $data['quantity_in_stock'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => now(),
        ]);

        return response()->json($this->baseQuery()->where('si.id', $id)->first());
    }

    public function activate(int $id)
    {
        DB::table('shop_items')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return response()->json(['message' => 'Item activated.']);
    }

    public function deactivate(int $id)
    {
        DB::table('shop_items')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['message' => 'Item deactivated.']);
    }

    public function destroy(int $id)
    {
        if (DB::table('student_purchase_items')->where('shop_item_id', $id)->exists()) {
            return response()->json(['message' => 'Cannot delete item with purchase history. Deactivate it instead.'], 422);
        }

        DB::table('shop_items')->where('id', $id)->delete();
        return response()->json(['message' => 'Item deleted.']);
    }

    public function lowStock()
    {
        return response()->json($this->baseQuery()
            ->whereColumn('si.quantity_in_stock', '<=', 'si.reorder_level')
            ->orderBy('si.quantity_in_stock')
            ->get());
    }
}
