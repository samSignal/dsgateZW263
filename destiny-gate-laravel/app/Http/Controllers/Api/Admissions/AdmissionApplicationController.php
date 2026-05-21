<?php

namespace App\Http\Controllers\Api\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdmissionApplicationController extends Controller
{
    private array $editableFields = [
        'application_type', 'academic_year_id', 'term_id', 'applying_form_id',
        'student_first_name', 'student_last_name', 'gender', 'date_of_birth',
        'birth_certificate_number', 'student_national_id', 'passport_photo',
        'guardian_name', 'guardian_national_id', 'guardian_phone', 'emergency_phone',
        'guardian_email', 'address', 'occupation', 'grade7_school', 'grade7_results',
        'previous_school_name', 'current_form', 'transfer_reason', 'last_term_average',
        'reason_for_joining', 'medical_information', 'current_step',
    ];

    public function submit(Request $request)
    {
        $data = $request->validate($this->finalRules());
        $token = $request->input('tracking_token');

        DB::beginTransaction();
        try {
            if ($token) {
                $app = $this->applicationByToken($token);
                if (!$app) {
                    DB::rollBack();
                    return response()->json(['message' => 'Invalid tracking token.'], 404);
                }
                if ($app->is_submitted) {
                    DB::rollBack();
                    return response()->json(['message' => 'Application already submitted.'], 422);
                }

                DB::table('admission_applications')->where('id', $app->id)->update(array_merge(
                    $this->payload($request),
                    [
                        'status' => 'submitted',
                        'is_submitted' => true,
                        'submitted_at' => now(),
                        'status_updated_at' => now(),
                        'progress_percentage' => 20,
                        'completion_percentage' => 100,
                        'current_step' => 6,
                        'updated_at' => now(),
                    ]
                ));
                $id = $app->id;
                $appNumber = $app->application_number;
            } else {
                $appNumber = $this->generateApplicationNumber();
                $token = $this->generateTrackingToken();
                $id = DB::table('admission_applications')->insertGetId(array_merge(
                    $this->payload($request),
                    [
                        'application_number' => $appNumber,
                        'tracking_token' => $token,
                        'status' => 'submitted',
                        'is_submitted' => true,
                        'submitted_at' => now(),
                        'status_updated_at' => now(),
                        'progress_percentage' => 20,
                        'completion_percentage' => 100,
                        'current_step' => 6,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ));
            }

            $fresh = DB::table('admission_applications')->where('id', $id)->first();
            $this->recordNotification($id, 'Application Submitted', 'Your application has been received and is now under review.', 'system', 'success');
            $this->sendTokenEmail($fresh, 'submitted');
            $this->queueWhatsAppToken($fresh);

            DB::commit();

            return response()->json([
                'message' => 'Application submitted successfully.',
                'application_number' => $appNumber,
                'tracking_token' => $token,
                'tracking_link' => url('/admissions/track'),
                'continue_link' => url('/admissions/continue/' . $token),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Admission submission failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to submit application. Please try again.'], 500);
        }
    }

    public function saveDraft(Request $request)
    {
        return $this->persistDraft($request);
    }

    public function updateDraft(Request $request)
    {
        return $this->persistDraft($request);
    }

    public function uploadDocuments(Request $request, $token)
    {
        $app = $this->applicationByToken($token);
        if (!$app) {
            return response()->json(['message' => 'Invalid token.'], 404);
        }

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp,svg|max:5120',
            'document_type' => 'required|in:birth_certificate,passport_photo,grade7_report,latest_report,transfer_letter,discipline_record,medical_record',
        ]);

        $file = $request->file('document');
        $fileName = Str::slug($request->document_type) . '-' . Str::random(12) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('admissions/documents/' . $app->application_number, $fileName, 'public');

        DB::table('application_documents')->updateOrInsert(
            ['application_id' => $app->id, 'document_type' => $request->document_type],
            [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('admission_applications')->where('id', $app->id)->update([
            'draft_last_saved_at' => $app->is_submitted ? $app->draft_last_saved_at : now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'file_path' => $filePath,
            'document_type' => $request->document_type
        ]);
    }

    private function persistDraft(Request $request)
    {
        $request->validate([
            'application_type' => 'required|in:new_intake,transfer',
            'guardian_email' => 'nullable|email|max:320',
            'current_step' => 'nullable|integer|min:1|max:6',
        ]);

        DB::beginTransaction();
        try {
            $token = $request->input('tracking_token');
            $payload = array_merge($this->payload($request), [
                'status' => 'draft',
                'is_submitted' => false,
                'completion_percentage' => $this->completionPercentage($request),
                'draft_last_saved_at' => now(),
                'updated_at' => now(),
            ]);

            if ($token) {
                $app = $this->applicationByToken($token);
                if (!$app) {
                    DB::rollBack();
                    return response()->json(['message' => 'Invalid tracking token.'], 404);
                }
                if ($app->is_submitted) {
                    DB::rollBack();
                    return response()->json(['message' => 'Submitted applications cannot be edited as drafts.'], 422);
                }
                DB::table('admission_applications')->where('id', $app->id)->update($payload);
                $id = $app->id;
                $appNumber = $app->application_number;
            } else {
                $appNumber = $this->generateApplicationNumber();
                $token = $this->generateTrackingToken();
                $id = DB::table('admission_applications')->insertGetId(array_merge([
                    'student_first_name' => $request->input('student_first_name', 'Draft'),
                    'student_last_name' => $request->input('student_last_name', 'Applicant'),
                    'birth_certificate_number' => $request->input('birth_certificate_number', 'DRAFT-' . Str::upper(Str::random(6))),
                ], $payload, [
                    'application_number' => $appNumber,
                    'tracking_token' => $token,
                    'created_at' => now(),
                ]));
            }

            $fresh = DB::table('admission_applications')->where('id', $id)->first();
            $this->recordNotification($id, 'Draft Saved', 'Your application draft was saved. Use your tracking token to continue later.', 'system', 'info');
            $this->sendTokenEmail($fresh, 'draft');
            $this->queueWhatsAppToken($fresh);

            DB::commit();

            return response()->json([
                'message' => 'Draft saved successfully.',
                'application_number' => $appNumber,
                'tracking_token' => $token,
                'current_step' => (int) $fresh->current_step,
                'completion_percentage' => (int) $fresh->completion_percentage,
                'continue_link' => url('/admissions/continue/' . $token),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Admission draft save failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to save draft. Please try again.'], 500);
        }
    }

    private function payload(Request $request): array
    {
        $payload = [];
        foreach ($this->editableFields as $field) {
            if ($request->has($field)) {
                $payload[$field] = $request->input($field);
            }
        }
        $payload['current_step'] = min(6, max(1, (int) ($payload['current_step'] ?? 1)));
        return $payload;
    }

    private function finalRules(): array
    {
        return [
            'tracking_token' => 'nullable|string|max:20',
            'application_type' => 'required|in:new_intake,transfer',
            'student_first_name' => 'required|string|max:100',
            'student_last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'birth_certificate_number' => 'required|string|max:50',
            'student_national_id' => 'nullable|string|max:50',
            'guardian_name' => 'required|string|max:100',
            'guardian_national_id' => 'required|string|max:50',
            'guardian_phone' => 'required|string|max:20',
            'emergency_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email|max:320',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'term_id' => 'nullable|integer|exists:terms,id',
            'applying_form_id' => 'required|integer|exists:forms,id',
            'grade7_school' => 'nullable|string|max:150',
            'grade7_results' => 'nullable|string|max:255',
            'previous_school_name' => 'nullable|string|max:150',
            'current_form' => 'nullable|string|max:50',
            'transfer_reason' => 'nullable|string',
            'last_term_average' => 'nullable|string|max:50',
            'reason_for_joining' => 'nullable|string',
            'medical_information' => 'nullable|string',
        ];
    }

    private function generateApplicationNumber(): string
    {
        $year = date('Y');
        $count = DB::table('admission_applications')->whereYear('created_at', $year)->lockForUpdate()->count() + 1;
        $number = 'APP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        while (DB::table('admission_applications')->where('application_number', $number)->exists()) {
            $count++;
            $number = 'APP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        }
        return $number;
    }

    private function generateTrackingToken(): string
    {
        do {
            $token = 'DGI-' . Str::upper(Str::random(8));
        } while (DB::table('admission_applications')->where('tracking_token', $token)->exists());
        return $token;
    }

    private function applicationByToken(string $token): ?object
    {
        if (!preg_match('/^DGI-[A-Z0-9]{8}$/', $token)) {
            return null;
        }
        return DB::table('admission_applications')->where('tracking_token', $token)->first();
    }

    private function completionPercentage(Request $request): int
    {
        return min(100, max(0, (int) round(((int) $request->input('current_step', 1) / 6) * 100)));
    }

    private function recordNotification(int $id, string $title, string $message, string $channel, string $type): void
    {
        DB::table('application_notifications')->insert([
            'application_id' => $id,
            'title' => $title,
            'message' => $message,
            'notification_channel' => $channel,
            'notification_type' => $type,
            'sent_at' => $channel === 'system' ? null : now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sendTokenEmail(object $app, string $context): void
    {
        if (!$app->guardian_email) {
            return;
        }

        $body = "DestinyGate Institute Admission Application\n\n"
            . "Student: {$app->student_first_name} {$app->student_last_name}\n"
            . "Application Number: {$app->application_number}\n"
            . "Tracking Token: {$app->tracking_token}\n\n"
            . "Track application: " . url('/admissions/track') . "\n"
            . "Continue application: " . url('/admissions/continue/' . $app->tracking_token) . "\n\n"
            . "Please keep this token secure. Contact admissions for support.";

        try {
            Mail::raw($body, function ($message) use ($app) {
                $message->to($app->guardian_email)
                    ->subject('DestinyGate Institute Admission Application');
            });
        } catch (\Throwable $e) {
            Log::warning('Admission token email could not be sent: ' . $e->getMessage());
        }

        $this->recordNotification($app->id, 'Admission Token Email', $body, 'email', $context === 'submitted' ? 'success' : 'info');
    }

    private function queueWhatsAppToken(object $app): void
    {
        $message = "Your DestinyGate Institute application has been received.\n\n"
            . "Application Number:\n{$app->application_number}\n\n"
            . "Tracking Token:\n{$app->tracking_token}\n\n"
            . "Track application:\n" . url('/admissions/track') . "\n\n"
            . "Continue application:\n" . url('/admissions/continue/' . $app->tracking_token);

        $this->recordNotification($app->id, 'WhatsApp Message Ready', $message, 'whatsapp', 'info');
    }
}
