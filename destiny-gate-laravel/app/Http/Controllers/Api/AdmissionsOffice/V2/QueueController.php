<?php

namespace App\Http\Controllers\Api\AdmissionsOffice\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionResponder;
use App\Support\AdmissionsOffice\AdmissionOfficeAccess;
use App\Support\AdmissionsOffice\AdmissionOfficeQueueService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function list(Request $request, string $queue)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.queue_view')) return $deny;

        $data = AdmissionOfficeQueueService::list($queue, $request->all());
        return AdmissionResponder::ok($data);
    }

    public function metrics(Request $request)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.queue_view')) return $deny;

        $data = AdmissionOfficeQueueService::metrics($request->all());
        return AdmissionResponder::ok($data);
    }
}

