<?php

namespace App\Operational;

use App\Contracts\Operational\AttendanceGateway;
use App\Contracts\Operational\ExaminationsGateway;
use App\Contracts\Operational\HostelGateway;
use App\Contracts\Operational\LibraryGateway;
use App\Contracts\Operational\LmsProvisioningGateway;
use App\Contracts\Operational\TimetableGateway;
use App\Contracts\Operational\TransportGateway;

class AcademicServiceOrchestrator
{
    public static function buildActivationPlan(array $requestedServices = []): array
    {
        $plan = OperationalDependencyGraphService::buildPlan($requestedServices);
        if (empty($plan['ok'])) return $plan;

        $steps = [];
        foreach ((array) ($plan['steps'] ?? []) as $s) {
            if (!is_array($s)) continue;
            $steps[] = [
                'order' => (int) ($s['order'] ?? 0),
                'service_code' => (string) ($s['service'] ?? ''),
                'depends_on' => (array) ($s['depends_on'] ?? []),
            ];
        }

        return [
            'ok' => true,
            'code' => 'OK',
            'steps' => $steps,
            'diagnostics' => $plan['diagnostics'] ?? null,
        ];
    }

    public static function execute(string $serviceCode, array $ctx, bool $dryRun): array
    {
        $serviceCode = trim($serviceCode);
        return match ($serviceCode) {
            'identity' => IdentityActivationService::activate($ctx, $dryRun),
            'timetable' => app(TimetableGateway::class)->activate($ctx, $dryRun),
            'attendance' => app(AttendanceGateway::class)->activate($ctx, $dryRun),
            'lms' => app(LmsProvisioningGateway::class)->activate($ctx, $dryRun),
            'exams' => app(ExaminationsGateway::class)->activate($ctx, $dryRun),
            'library' => app(LibraryGateway::class)->activate($ctx, $dryRun),
            'transport' => app(TransportGateway::class)->activate($ctx, $dryRun),
            'hostel' => app(HostelGateway::class)->activate($ctx, $dryRun),
            default => ['ok' => false, 'code' => 'INVALID', 'message' => 'Unknown service.'],
        };
    }

    public static function rollback(string $serviceCode, array $ctx, array $rollbackMeta): array
    {
        $serviceCode = trim($serviceCode);
        return match ($serviceCode) {
            'identity' => IdentityActivationService::rollback($ctx, $rollbackMeta),
            'timetable' => app(TimetableGateway::class)->rollback($ctx, $rollbackMeta),
            'attendance' => app(AttendanceGateway::class)->rollback($ctx, $rollbackMeta),
            'lms' => app(LmsProvisioningGateway::class)->rollback($ctx, $rollbackMeta),
            'exams' => app(ExaminationsGateway::class)->rollback($ctx, $rollbackMeta),
            'library' => app(LibraryGateway::class)->rollback($ctx, $rollbackMeta),
            'transport' => app(TransportGateway::class)->rollback($ctx, $rollbackMeta),
            'hostel' => app(HostelGateway::class)->rollback($ctx, $rollbackMeta),
            default => ['ok' => false, 'code' => 'INVALID', 'message' => 'Unknown service.'],
        };
    }

    public static function reconcile(string $serviceCode, array $ctx): array
    {
        $serviceCode = trim($serviceCode);
        return match ($serviceCode) {
            'identity' => IdentityActivationService::reconcile($ctx),
            'timetable' => app(TimetableGateway::class)->reconcile($ctx),
            'attendance' => app(AttendanceGateway::class)->reconcile($ctx),
            'lms' => app(LmsProvisioningGateway::class)->reconcile($ctx),
            'exams' => app(ExaminationsGateway::class)->reconcile($ctx),
            'library' => app(LibraryGateway::class)->reconcile($ctx),
            'transport' => app(TransportGateway::class)->reconcile($ctx),
            'hostel' => app(HostelGateway::class)->reconcile($ctx),
            default => ['ok' => false, 'drift' => true, 'issues' => ['unknown_service']],
        };
    }

    public static function health(): array
    {
        return [
            'identity' => ['ok' => true, 'adapter' => 'db', 'service' => 'identity'],
            'timetable' => app(TimetableGateway::class)->health(),
            'attendance' => app(AttendanceGateway::class)->health(),
            'lms' => app(LmsProvisioningGateway::class)->health(),
            'exams' => app(ExaminationsGateway::class)->health(),
            'library' => app(LibraryGateway::class)->health(),
            'transport' => app(TransportGateway::class)->health(),
            'hostel' => app(HostelGateway::class)->health(),
        ];
    }

    public static function incidentTypeForService(string $serviceCode): string
    {
        return match (trim($serviceCode)) {
            'timetable' => 'timetable_activation_failed',
            'attendance' => 'attendance_activation_failed',
            'lms' => 'lms_provision_failed',
            'exams' => 'exam_eligibility_conflict',
            default => 'operational_dependency_failure',
        };
    }
}

