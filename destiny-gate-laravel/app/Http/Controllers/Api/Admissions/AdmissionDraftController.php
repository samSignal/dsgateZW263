<?php

namespace App\Http\Controllers\Api\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionDraftController extends Controller
{
    public function continueApplication()
    {
        return response()->json([
            'message' => 'Enter your DestinyGate Institute tracking token to continue your application.',
            'token_format' => 'DGI-XXXXXXXX',
            'dob_verification' => 'Optional date_of_birth may be supplied when resuming.',
        ]);
    }

    public function saveDraft(Request $request)
    {
        return app(AdmissionApplicationController::class)->saveDraft($request);
    }

    public function updateDraft(Request $request)
    {
        return app(AdmissionApplicationController::class)->updateDraft($request);
    }

    public function updateStep(Request $request, string $token)
    {
        $request->validate([
            'current_step' => 'required|integer|min:1|max:6',
            'completion_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $app = $this->findByToken($token);
        if (!$app) {
            return response()->json(['message' => 'Invalid tracking token.'], 404);
        }
        if ($app->is_submitted) {
            return response()->json(['message' => 'Submitted applications cannot be moved back into draft steps.'], 422);
        }

        DB::table('admission_applications')->where('id', $app->id)->update([
            'current_step' => $request->integer('current_step'),
            'completion_percentage' => $request->input('completion_percentage', round(($request->integer('current_step') / 6) * 100)),
            'draft_last_saved_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Application step updated.']);
    }

    public function resumeByToken(Request $request, string $token)
    {
        $app = $this->findByToken($token);
        if (!$app) {
            return response()->json(['message' => 'Invalid tracking token.'], 404);
        }

        if ($request->filled('date_of_birth') && $request->input('date_of_birth') !== $app->date_of_birth) {
            return response()->json(['message' => 'Date of birth verification failed.'], 403);
        }

        $documents = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->orderBy('document_type')
            ->get();

        return response()->json([
            'application' => $app,
            'documents' => $documents,
            'required_documents' => $this->requiredDocuments($app->application_type),
            'resume_step' => (int) $app->current_step,
        ]);
    }

    private function findByToken(string $token): ?object
    {
        if (!preg_match('/^DGI-[A-Z0-9]{8}$/', $token)) {
            return null;
        }

        return DB::table('admission_applications')->where('tracking_token', $token)->first();
    }

    private function requiredDocuments(string $type): array
    {
        return $type === 'transfer'
            ? ['birth_certificate', 'passport_photo', 'latest_report', 'transfer_letter', 'discipline_record']
            : ['birth_certificate', 'passport_photo', 'grade7_report'];
    }
}
