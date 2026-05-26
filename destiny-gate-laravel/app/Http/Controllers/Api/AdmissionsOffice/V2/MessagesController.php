<?php

namespace App\Http\Controllers\Api\AdmissionsOffice\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionResponder;
use App\Support\AdmissionsOffice\AdmissionOfficeAccess;
use App\Support\AdmissionsOffice\AdmissionOfficeNotesService;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function templates(Request $request)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.messages')) return $deny;

        $lib = config('admissions.office.message_templates', []);
        $templates = is_array($lib) ? $lib : [];

        return AdmissionResponder::ok([
            'items' => collect($templates)->map(function ($v, $k) {
                return [
                    'key' => (string) $k,
                    'title' => (string) ($v['title'] ?? ''),
                    'message' => (string) ($v['message'] ?? ''),
                ];
            })->values()->all(),
        ]);
    }

    public function internalNote(Request $request, int $applicationId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_notes')) return $deny;
        $data = $request->validate([
            'message' => 'required|string|max:5000',
        ]);
        $id = AdmissionOfficeNotesService::addInternalNote($applicationId, (int) $request->user()->id, (string) $data['message'], $request);
        return AdmissionResponder::ok(['note_id' => $id]);
    }

    public function applicantMessage(Request $request, int $applicationId)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.messages')) return $deny;
        $data = $request->validate([
            'title' => 'nullable|string|max:140',
            'message' => 'nullable|string|max:5000',
            'template_key' => 'nullable|string|max:80',
        ]);
        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $templateKey = $data['template_key'] ? (string) $data['template_key'] : null;
        if ($message === '' && !$templateKey) {
            return AdmissionResponder::fail('VALIDATION_FAILED', 'Provide a message or a template.', 422, [
                'message' => ['Provide a message or select a template.'],
            ]);
        }
        $titleForService = $title !== '' ? $title : ($templateKey ? '' : 'Admissions update');
        $id = AdmissionOfficeNotesService::sendApplicantMessage(
            $applicationId,
            (int) $request->user()->id,
            $titleForService,
            $message,
            $templateKey,
            $request
        );
        return AdmissionResponder::ok(['note_id' => $id]);
    }
}
