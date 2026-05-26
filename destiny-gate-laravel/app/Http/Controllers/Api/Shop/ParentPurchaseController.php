<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParentPurchaseController extends Controller
{
    private function linkedStudentIds()
    {
        return DB::table('guardians')->where('user_id', Auth::id())->pluck('student_id')->toArray();
    }

    private function canViewChild(int $studentId): bool
    {
        return DB::table('guardians')->where('user_id', Auth::id())->where('student_id', $studentId)->exists();
    }

    public function myChildrenPurchases()
    {
        $studentIds = $this->linkedStudentIds();

        $children = DB::table('students as s')
            ->select('s.id', 's.first_name', 's.last_name', 's.student_number', 's.admission_number')
            ->whereIn('s.id', $studentIds)
            ->get();
        foreach ($children as $child) {
            StudentStreamResolver::attachResolvedFields($child);
        }

        $purchases = DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', 's.first_name', 's.last_name', 's.student_number')
            ->whereIn('sp.student_id', $studentIds)
            ->orderByDesc('sp.purchase_date')
            ->get();
        foreach ($purchases as $p) {
            $p->student_name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($p);
        }

        return response()->json(['children' => $children, 'purchases' => $purchases]);
    }

    public function childPurchases(int $id)
    {
        abort_if(!$this->canViewChild($id), 403, 'You cannot view this child.');

        return response()->json(DB::table('student_purchases')
            ->where('student_id', $id)
            ->orderByDesc('purchase_date')
            ->get());
    }

    public function childPurchaseDetails(int $id, int $purchaseId)
    {
        abort_if(!$this->canViewChild($id), 403, 'You cannot view this child.');

        $purchase = DB::table('student_purchases')->where('id', $purchaseId)->where('student_id', $id)->first();
        abort_if(!$purchase, 404, 'Purchase not found.');

        $items = DB::table('student_purchase_items as spi')
            ->join('shop_items as si', 'spi.shop_item_id', '=', 'si.id')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('spi.*', 'si.item_name', 'si.item_code', 'sc.name as category_name')
            ->where('spi.student_purchase_id', $purchaseId)
            ->get();

        return response()->json([...(array) $purchase, 'items' => $items]);
    }
}
