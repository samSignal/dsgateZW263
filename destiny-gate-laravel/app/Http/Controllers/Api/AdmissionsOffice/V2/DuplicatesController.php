<?php

namespace App\Http\Controllers\Api\AdmissionsOffice\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionResponder;
use App\Support\AdmissionsOffice\AdmissionOfficeAccess;
use App\Support\AdmissionsOffice\AdmissionOfficeDuplicateService;
use Illuminate\Http\Request;

class DuplicatesController extends Controller
{
    public function candidates(Request $request, int $applicationId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.duplicates')) return $deny;
        return AdmissionResponder::ok([
            'items' => AdmissionOfficeDuplicateService::candidates($applicationId),
        ]);
    }

    public function compare(Request $request, int $applicationId, int $candidateId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.duplicates')) return $deny;
        $res = AdmissionOfficeDuplicateService::compare($applicationId, $candidateId);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 404);
        return AdmissionResponder::ok($res);
    }

    public function link(Request $request)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.duplicates')) return $deny;
        $data = $request->validate([
            'canonical_application_id' => 'required|integer|exists:admission_applications,id',
            'related_application_id' => 'required|integer|exists:admission_applications,id',
            'status' => 'required|string|in:flagged,merged,invalid',
            'reason' => 'required|string|max:1000',
        ]);

        $res = AdmissionOfficeDuplicateService::link(
            (int) $data['canonical_application_id'],
            (int) $data['related_application_id'],
            (string) $data['status'],
            (string) $data['reason'],
            (int) $request->user()->id,
            $request
        );
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409);
        return AdmissionResponder::ok($res);
    }

    public function reopen(Request $request)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.override')) return $deny;
        $data = $request->validate([
            'canonical_application_id' => 'required|integer|exists:admission_applications,id',
            'related_application_id' => 'required|integer|exists:admission_applications,id',
            'reason' => 'required|string|max:1000',
        ]);

        $res = AdmissionOfficeDuplicateService::reopen(
            (int) $data['canonical_application_id'],
            (int) $data['related_application_id'],
            (string) $data['reason'],
            (int) $request->user()->id,
            $request
        );
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409);
        return AdmissionResponder::ok($res);
    }
}
