<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Finance\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    use ExportsReports;

    /**
     * "Who is currently logged in" — derived from Sanctum's personal_access_tokens
     * rather than a separate presence table. last_used_at updates on every authenticated
     * request, so a token used within the last few minutes means that person is actively
     * using the system right now; older ones are just a session sitting open unused.
     */
    public function activeSessions()
    {
        $onlineThreshold = now()->subMinutes(5);

        $rows = DB::table('personal_access_tokens as t')
            ->join('users as u', function ($j) {
                $j->on('t.tokenable_id', '=', 'u.id')->where('t.tokenable_type', '=', \App\Models\User::class);
            })
            ->select('u.id as user_id', 'u.name', 'u.email', 'u.role', 't.last_used_at', 't.created_at as session_started')
            ->orderByDesc('t.last_used_at')
            ->get();

        $rows->each(fn ($r) => $r->is_online = $r->last_used_at && $r->last_used_at >= $onlineThreshold);

        return response()->json([
            'online_count' => $rows->where('is_online', true)->count(),
            'sessions'     => $rows,
        ]);
    }

    public function index(Request $request)
    {
        $q = DB::table('audit_logs')->orderByDesc('created_at');

        if ($request->user_id)   $q->where('user_id', $request->user_id);
        if ($request->role)      $q->where('user_role', $request->role);
        if ($request->action)    $q->where('action', $request->action);
        if ($request->date_from) $q->where('created_at', '>=', $request->date_from . ' 00:00:00');
        if ($request->date_to)   $q->where('created_at', '<=', $request->date_to . ' 23:59:59');
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) {
                $x->where('description', 'like', $s)
                  ->orWhere('user_name', 'like', $s)
                  ->orWhere('path', 'like', $s)
                  ->orWhere('ip_address', 'like', $s);
            });
        }

        if ($request->format === 'pdf' || $request->format === 'csv') {
            $rows = $q->get();
            if ($request->format === 'pdf') {
                return $this->exportPdf('reports.admin.audit-log', compact('rows'), 'audit-log.pdf');
            }
            return $this->exportCsv('audit-log.csv',
                ['#', 'Date/Time', 'User', 'Role', 'Action', 'Description', 'Method', 'Path', 'Status', 'IP Address'],
                $rows->values()->map(fn ($r, $i) => [
                    $i + 1, $r->created_at, $r->user_name, $r->user_role, $r->action,
                    $r->description, $r->method, $r->path, $r->status_code, $r->ip_address,
                ])
            );
        }

        $perPage = (int) ($request->per_page ?? 50);
        $page    = (int) ($request->page ?? 1);
        $total   = (clone $q)->count();
        $items   = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / max($perPage, 1)),
        ]);
    }

    /** Distinct action types + users, for the filter dropdowns. */
    public function meta()
    {
        return response()->json([
            'actions' => DB::table('audit_logs')->distinct()->orderBy('action')->pluck('action'),
            'roles'   => DB::table('audit_logs')->whereNotNull('user_role')->distinct()->orderBy('user_role')->pluck('user_role'),
            'users'   => DB::table('audit_logs')->whereNotNull('user_id')
                ->select('user_id', 'user_name')->distinct()->orderBy('user_name')->get(),
        ]);
    }
}
