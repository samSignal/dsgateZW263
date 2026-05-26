<?php

namespace App\Http\Controllers\Api\AdmissionsOffice\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionResponder;
use App\Support\AdmissionsOffice\AdmissionOfficeAccess;
use App\Support\AdmissionsOffice\AdmissionOfficeDocumentService;
use App\Support\AdmissionsOffice\AdmissionOfficeNotesService;
use App\Support\AdmissionsOffice\AdmissionOfficeStates;
use App\Support\AdmissionsOffice\AdmissionOfficeStateMachine;
use Illuminate\Http\Request;

class DocumentsController extends Controller
{
    public function verify(Request $request, int $documentId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.docs_review')) return $deny;
        $res = AdmissionOfficeDocumentService::reviewAction($documentId, 'verify', (int) $request->user()->id, $request, $request->all());
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409);
        return AdmissionResponder::ok($res);
    }

    public function reject(Request $request, int $documentId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.docs_review')) return $deny;
        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);
        $res = AdmissionOfficeDocumentService::reviewAction($documentId, 'reject', (int) $request->user()->id, $request, $data);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409);
        return AdmissionResponder::ok($res);
    }

    public function requestReupload(Request $request, int $documentId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.docs_review')) return $deny;
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.transition')) return $deny;

        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'notify_applicant' => 'nullable|boolean',
        ]);

        $res = AdmissionOfficeDocumentService::reviewAction($documentId, 'request_reupload', (int) $request->user()->id, $request, $data);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409);

        $transition = AdmissionOfficeStateMachine::transition((int) $res['application_id'], AdmissionOfficeStates::DOCUMENTS_REQUIRED, (string) $data['reason'], $request, AdmissionOfficeAccess::actorMeta($request));

        if (!empty($data['notify_applicant'])) {
            AdmissionOfficeNotesService::sendApplicantMessage(
                (int) $res['application_id'],
                (int) $request->user()->id,
                'Documents required',
                'Additional documents or updates are required for your admissions application: ' . $data['reason'],
                'documents_required',
                $request
            );
        }

        return AdmissionResponder::ok([
            'document' => $res,
            'transition' => $transition,
        ]);
    }
}

