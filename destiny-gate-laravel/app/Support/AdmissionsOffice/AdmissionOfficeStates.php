<?php

namespace App\Support\AdmissionsOffice;

class AdmissionOfficeStates
{
    public const SUBMITTED = 'SUBMITTED';
    public const UNDER_REVIEW = 'UNDER_REVIEW';
    public const DOCUMENTS_REQUIRED = 'DOCUMENTS_REQUIRED';
    public const READY_FOR_DECISION = 'READY_FOR_DECISION';
    public const ACCEPTED = 'ACCEPTED';
    public const REJECTED = 'REJECTED';
    public const WAITLISTED = 'WAITLISTED';
    public const DUPLICATE_FLAGGED = 'DUPLICATE_FLAGGED';
    public const DUPLICATE_INVALID = 'DUPLICATE_INVALID';
    public const ARCHIVED = 'ARCHIVED';

    public static function all(): array
    {
        return [
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::DOCUMENTS_REQUIRED,
            self::READY_FOR_DECISION,
            self::ACCEPTED,
            self::REJECTED,
            self::WAITLISTED,
            self::DUPLICATE_FLAGGED,
            self::DUPLICATE_INVALID,
            self::ARCHIVED,
        ];
    }

    public static function isValid(string $state): bool
    {
        return in_array(strtoupper(trim($state)), self::all(), true);
    }

    public static function normalize(?string $state, ?string $status): ?string
    {
        $s = strtoupper(trim((string) $state));
        if ($s !== '') return $s;

        $st = strtolower(trim((string) $status));
        if ($st === '') return null;

        return match ($st) {
            'submitted' => self::SUBMITTED,
            'under_review' => self::UNDER_REVIEW,
            'documents_required' => self::DOCUMENTS_REQUIRED,
            'accepted' => self::ACCEPTED,
            'rejected' => self::REJECTED,
            'waitlisted' => self::WAITLISTED,
            default => null,
        };
    }

    public static function mapToStatus(?string $lifecycleState, ?string $currentStatus): ?string
    {
        $s = strtoupper(trim((string) $lifecycleState));
        if ($s === '') return $currentStatus;

        return match ($s) {
            self::SUBMITTED => 'submitted',
            self::UNDER_REVIEW => 'under_review',
            self::DOCUMENTS_REQUIRED => 'documents_required',
            self::ACCEPTED => 'accepted',
            self::REJECTED => 'rejected',
            self::WAITLISTED => 'waitlisted',
            default => $currentStatus,
        };
    }
}
