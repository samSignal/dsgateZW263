<?php

namespace App\Support\AdmissionsOffice;

use App\Mail\AdmissionsExpiryWarningMail;
use App\Support\Admissions\AdmissionAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdmissionOfficeNotesService
{
    private static function templateLibrary(): array
    {
        $lib = config('admissions.office.message_templates', []);
        return is_array($lib) ? $lib : [];
    }

    private static function renderTemplate(?string $templateKey, object $app, string $title, string $message): array
    {
        $key = $templateKey ? trim((string) $templateKey) : '';
        $templates = self::templateLibrary();
        $tpl = ($key !== '' && isset($templates[$key]) && is_array($templates[$key])) ? $templates[$key] : null;

        $resolvedTitle = $title;
        $resolvedMessage = $message;
        $resolvedKey = $key !== '' ? $key : null;

        if ($tpl) {
            if (trim($resolvedTitle) === '' && !empty($tpl['title'])) $resolvedTitle = (string) $tpl['title'];
            if (trim($resolvedMessage) === '' && !empty($tpl['message'])) $resolvedMessage = (string) $tpl['message'];
        }

        $student = trim((string) (($app->student_first_name ?? '') . ' ' . ($app->student_last_name ?? '')));
        $replacements = [
            '{application_number}' => (string) ($app->application_number ?? ''),
            '{student_name}' => $student !== '' ? $student : 'the learner',
            '{guardian_name}' => (string) ($app->guardian_name ?? ''),
        ];

        $resolvedTitle = strtr($resolvedTitle, $replacements);
        $resolvedMessage = strtr($resolvedMessage, $replacements);

        return [$resolvedTitle, $resolvedMessage, $resolvedKey];
    }

    public static function addInternalNote(int $applicationId, int $actorUserId, string $message, Request $request): int
    {
        $now = now();
        $id = DB::table('admission_office_notes')->insertGetId([
            'application_id' => $applicationId,
            'visibility' => 'internal',
            'template_key' => null,
            'message' => $message,
            'created_by' => $actorUserId,
            'created_at' => $now,
        ]);

        AdmissionAudit::log($applicationId, 'admissions', $actorUserId, 'admissions.office.note_internal_added', [
            'note_id' => $id,
        ], 'info', $request);

        return (int) $id;
    }

    public static function sendApplicantMessage(int $applicationId, int $actorUserId, string $title, string $message, ?string $templateKey, Request $request): int
    {
        $now = now();
        $app = DB::table('admission_applications')->where('id', $applicationId)->first();
        if (!$app) return 0;

        [$title, $message, $templateKey] = self::renderTemplate($templateKey, $app, $title, $message);

        $id = DB::table('admission_office_notes')->insertGetId([
            'application_id' => $applicationId,
            'visibility' => 'applicant',
            'template_key' => $templateKey,
            'message' => $message,
            'created_by' => $actorUserId,
            'created_at' => $now,
        ]);

        DB::table('application_notifications')->insert([
            'application_id' => $applicationId,
            'title' => $title,
            'message' => $message,
            'notification_channel' => 'email',
            'notification_type' => 'info',
            'sent_by' => $actorUserId,
            'sent_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $email = (string) ($app->guardian_email ?? '');
        if ($email !== '') {
            try {
                Mail::to($email)->queue(new AdmissionsExpiryWarningMail($title, $message));
            } catch (\Throwable $e) {
            }
        }

        AdmissionAudit::log($applicationId, 'admissions', $actorUserId, 'admissions.office.applicant_message_sent', [
            'note_id' => $id,
            'template_key' => $templateKey,
            'title' => $title,
        ], 'info', $request);

        return (int) $id;
    }
}
