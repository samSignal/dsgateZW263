<?php

namespace App\Console\Commands;

use App\Mail\AdmissionsExpiryWarningMail;
use App\Support\Admissions\AdmissionAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdmissionsSendExpiryWarnings extends Command
{
    protected $signature = 'admissions:send-expiry-warnings {--dry-run : Report without sending}';
    protected $description = 'Send inactivity expiry warning emails for draft applications.';

    public function handle(): int
    {
        $days14 = (int) config('admissions.warning_days.first', 14);
        $days3 = (int) config('admissions.warning_days.final', 3);

        $now = now();
        $firstCutoff = $now->copy()->addDays($days14);
        $finalCutoff = $now->copy()->addDays($days3);

        $base = DB::table('admission_applications')
            ->whereNull('archived_at')
            ->whereNull('locked_at')
            ->where('status', 'draft')
            ->whereNotNull('guardian_email')
            ->whereNotNull('expires_at');

        $first = (clone $base)
            ->whereNull('expiry_warning_14_sent_at')
            ->where('expires_at', '<=', $firstCutoff)
            ->where('expires_at', '>', $finalCutoff)
            ->select('id', 'guardian_email', 'expires_at', 'academic_year_id')
            ->get();

        $final = (clone $base)
            ->whereNull('expiry_warning_3_sent_at')
            ->where('expires_at', '<=', $finalCutoff)
            ->select('id', 'guardian_email', 'expires_at', 'academic_year_id')
            ->get();

        $this->line('First warnings to send: ' . count($first));
        $this->line('Final warnings to send: ' . count($final));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $yearIds = array_values(array_unique(array_merge(
            $first->pluck('academic_year_id')->filter()->map(fn ($v) => (int) $v)->all(),
            $final->pluck('academic_year_id')->filter()->map(fn ($v) => (int) $v)->all(),
        )));

        $intakeCloseByYear = [];
        if (!empty($yearIds)) {
            $intakeCloseByYear = DB::table('admission_intakes')
                ->whereIn('academic_year_id', $yearIds)
                ->where('is_active', true)
                ->whereNotNull('closes_at')
                ->pluck('closes_at', 'academic_year_id')
                ->all();
        }

        foreach ($first as $row) {
            $email = (string) $row->guardian_email;
            $body = 'Your saved admissions draft will expire soon due to inactivity. Please return to the admissions portal and continue your application.';
            $reason = 'inactivity';
            $closeAt = $intakeCloseByYear[(string) $row->academic_year_id] ?? null;
            if ($closeAt && $row->expires_at && \Illuminate\Support\Carbon::parse($closeAt)->diffInMinutes(\Illuminate\Support\Carbon::parse($row->expires_at)) <= 10) {
                $reason = 'intake_close';
                $body = 'The admissions intake is scheduled to close soon. Please return to the admissions portal and submit your application before the intake closes. After closure, drafts become read-only and may require recovery.';
            }
            try {
                Mail::to($email)->send(new AdmissionsExpiryWarningMail('Admissions draft expiry warning', $body));
            } catch (\Throwable $e) {
            }
            DB::table('admission_applications')->where('id', $row->id)->update(['expiry_warning_14_sent_at' => $now, 'updated_at' => $now]);
            AdmissionAudit::log((int) $row->id, 'system', null, 'admissions.lifecycle.expiry_warning_first_sent', ['expires_at' => (string) $row->expires_at, 'reason' => $reason], 'info');
        }

        foreach ($final as $row) {
            $email = (string) $row->guardian_email;
            $body = 'Your saved admissions draft is close to expiring due to inactivity. Please return to the admissions portal as soon as possible to complete your application.';
            $reason = 'inactivity';
            $closeAt = $intakeCloseByYear[(string) $row->academic_year_id] ?? null;
            if ($closeAt && $row->expires_at && \Illuminate\Support\Carbon::parse($closeAt)->diffInMinutes(\Illuminate\Support\Carbon::parse($row->expires_at)) <= 10) {
                $reason = 'intake_close';
                $body = 'The admissions intake is scheduled to close very soon. Please return to the admissions portal and submit your application before the intake closes. After closure, drafts become read-only and may require recovery.';
            }
            try {
                Mail::to($email)->send(new AdmissionsExpiryWarningMail('Admissions draft expiring soon', $body));
            } catch (\Throwable $e) {
            }
            DB::table('admission_applications')->where('id', $row->id)->update(['expiry_warning_3_sent_at' => $now, 'updated_at' => $now]);
            AdmissionAudit::log((int) $row->id, 'system', null, 'admissions.lifecycle.expiry_warning_final_sent', ['expires_at' => (string) $row->expires_at, 'reason' => $reason], 'info');
        }

        return self::SUCCESS;
    }
}
