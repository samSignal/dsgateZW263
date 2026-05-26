<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentPurchaseController extends Controller
{
    private function nextPurchaseNumber(): string
    {
        $year = date('Y');
        $last = DB::table('student_purchases')
            ->where('purchase_number', 'like', "PUR-{$year}-%")
            ->orderByDesc('id')
            ->value('purchase_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return "PUR-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function purchaseQuery()
    {
        return DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->leftJoin('users as u', 'sp.recorded_by', '=', 'u.id')
            ->leftJoin('academic_years as ay', 'sp.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'sp.term_id', '=', 't.id')
            ->select(
                'sp.*',
                's.first_name',
                's.last_name',
                's.student_number',
                's.admission_number',
                'u.name as recorded_by_name',
                'ay.name as academic_year_name',
                't.name as term_name'
            );
    }

    public function index(Request $request)
    {
        $q = $this->purchaseQuery()->orderByDesc('sp.purchase_date')->orderByDesc('sp.id');

        if ($request->filled('student_id')) $q->where('sp.student_id', $request->student_id);
        if ($request->filled('status')) $q->where('sp.status', $request->status);
        if ($request->filled('date_from')) $q->where('sp.purchase_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $q->where('sp.purchase_date', '<=', $request->date_to);
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) {
                $x->where('sp.purchase_number', 'like', $s)
                  ->orWhere('s.first_name', 'like', $s)
                  ->orWhere('s.last_name', 'like', $s)
                  ->orWhere('s.student_number', 'like', $s)
                  ->orWhere('s.admission_number', 'like', $s);
            });
        }

        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);
        $total = (clone $q)->count();
        $items = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();
        foreach ($items as $item) {
            $item->student_name = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($item);
        }

        return response()->json([
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'purchased_by' => 'nullable|exists:guardians,id',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.shop_item_id' => 'required|exists:shop_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,ecocash,bank_transfer,swipe,online,other',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $lines = [];
            $total = 0.0;

            foreach ($data['items'] as $line) {
                $item = DB::table('shop_items')->where('id', $line['shop_item_id'])->lockForUpdate()->first();
                if (!$item || !$item->is_active) {
                    DB::rollBack();
                    return response()->json(['message' => 'Inactive or missing items cannot be sold.'], 422);
                }
                if ((int) $line['quantity'] > (int) $item->quantity_in_stock) {
                    DB::rollBack();
                    return response()->json(['message' => "{$item->item_name} has only {$item->quantity_in_stock} in stock."], 422);
                }

                $lineTotal = (float) $item->unit_price * (int) $line['quantity'];
                $lines[] = ['item' => $item, 'quantity' => (int) $line['quantity'], 'line_total' => $lineTotal];
                $total += $lineTotal;
            }

            $paid = min((float) ($data['payment_amount'] ?? 0), $total);
            if (($data['payment_amount'] ?? 0) > $total) {
                DB::rollBack();
                return response()->json(['message' => 'Payment cannot exceed purchase total.'], 422);
            }

            $balance = max(0, $total - $paid);
            $status = $paid <= 0 ? 'unpaid' : ($balance <= 0 ? 'paid' : 'partial');
            $purchaseId = DB::table('student_purchases')->insertGetId([
                'purchase_number' => $this->nextPurchaseNumber(),
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'term_id' => $data['term_id'] ?? null,
                'purchased_by' => $data['purchased_by'] ?? null,
                'total_amount' => $total,
                'amount_paid' => $paid,
                'balance' => $balance,
                'status' => $status,
                'payment_status' => $status === 'paid' ? 'paid' : ($status === 'partial' ? 'partial' : 'unpaid'),
                'notes' => $data['notes'] ?? null,
                'recorded_by' => Auth::id(),
                'purchase_date' => $data['purchase_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('student_purchase_items')->insert([
                    'student_purchase_id' => $purchaseId,
                    'shop_item_id' => $line['item']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['item']->unit_price,
                    'line_total' => $line['line_total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('shop_items')->where('id', $line['item']->id)->update([
                    'quantity_in_stock' => (int) $line['item']->quantity_in_stock - $line['quantity'],
                    'updated_at' => now(),
                ]);
            }

            if ($paid > 0) {
                DB::table('student_purchase_payments')->insert([
                    'student_purchase_id' => $purchaseId,
                    'finance_payment_id' => null,
                    'amount' => $paid,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference_number' => $data['reference_number'] ?? null,
                    'received_by' => Auth::id(),
                    'payment_date' => $data['payment_date'] ?? $data['purchase_date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Purchase recorded.', 'purchase_id' => $purchaseId], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $purchase = $this->purchaseQuery()->where('sp.id', $id)->first();
        abort_if(!$purchase, 404, 'Purchase not found.');
        $purchase->student_name = trim(($purchase->first_name ?? '') . ' ' . ($purchase->last_name ?? ''));
        StudentStreamResolver::attachResolvedFields($purchase);

        $items = DB::table('student_purchase_items as spi')
            ->join('shop_items as si', 'spi.shop_item_id', '=', 'si.id')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('spi.*', 'si.item_name', 'si.item_code', 'sc.name as category_name')
            ->where('spi.student_purchase_id', $id)
            ->get();

        $payments = DB::table('student_purchase_payments as spp')
            ->leftJoin('users as u', 'spp.received_by', '=', 'u.id')
            ->select('spp.*', 'u.name as received_by_name')
            ->where('spp.student_purchase_id', $id)
            ->orderByDesc('spp.payment_date')
            ->get();

        return response()->json([...(array) $purchase, 'items' => $items, 'payments' => $payments]);
    }

    public function cancel(Request $request, int $id)
    {
        $data = $request->validate([
            'refund_note' => 'nullable|string|max:1000',
            'admin_confirm_refund' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $purchase = DB::table('student_purchases')->where('id', $id)->lockForUpdate()->first();
            abort_if(!$purchase, 404, 'Purchase not found.');
            if ($purchase->status === 'cancelled') {
                DB::rollBack();
                return response()->json(['message' => 'Purchase is already cancelled.'], 422);
            }
            if ($purchase->status === 'paid' && empty($data['admin_confirm_refund'])) {
                DB::rollBack();
                return response()->json(['message' => 'Fully paid purchases require admin refund confirmation.'], 422);
            }

            $items = DB::table('student_purchase_items')->where('student_purchase_id', $id)->get();
            foreach ($items as $line) {
                DB::table('shop_items')->where('id', $line->shop_item_id)->increment('quantity_in_stock', (int) $line->quantity, ['updated_at' => now()]);
            }

            $notes = trim(($purchase->notes ? $purchase->notes . "\n" : '') . 'Cancelled: ' . ($data['refund_note'] ?? 'No refund note.'));
            DB::table('student_purchases')->where('id', $id)->update([
                'status' => 'cancelled',
                'notes' => $notes,
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Purchase cancelled and stock restored.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function studentPurchases(int $studentId)
    {
        $rows = $this->purchaseQuery()->where('sp.student_id', $studentId)->orderByDesc('sp.purchase_date')->get();
        foreach ($rows as $row) {
            $row->student_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($row);
        }
        return response()->json($rows);
    }

    public function searchStudents(Request $request)
    {
        $s = '%' . $request->query('search', '') . '%';
        $students = DB::table('students as s')
            ->select('s.id', 's.first_name', 's.last_name', 's.student_number', 's.admission_number')
            ->where(function ($q) use ($s) {
                $q->where('s.first_name', 'like', $s)
                  ->orWhere('s.last_name', 'like', $s)
                  ->orWhere('s.student_number', 'like', $s)
                  ->orWhere('s.admission_number', 'like', $s);
            })
            ->orderBy('s.last_name')
            ->limit(20)
            ->get();
        foreach ($students as $student) {
            StudentStreamResolver::attachResolvedFields($student);
        }

        return response()->json($students);
    }
}
