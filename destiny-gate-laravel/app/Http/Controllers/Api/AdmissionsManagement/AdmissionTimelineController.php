<?php

namespace App\Http\Controllers\Api\AdmissionsManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionTimelineController extends Controller
{
    public function history($applicationId)
    {
        $logs = DB::table('application_status_logs')
                    ->leftJoin('users', 'application_status_logs.changed_by', '=', 'users.id')
                    ->select('application_status_logs.*', 'users.first_name', 'users.last_name')
                    ->where('application_id', $applicationId)
                    ->orderBy('changed_at', 'desc')
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }
}
