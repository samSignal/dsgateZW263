<?php

namespace App\Support\Admissions;

class AdmissionRequirements
{
    public static function requiredDocuments(string $applicationType): array
    {
        if ($applicationType === 'transfer') {
            return ['birth_certificate', 'passport_photo', 'latest_report', 'transfer_letter', 'discipline_record'];
        }
        return ['birth_certificate', 'passport_photo', 'grade7_report'];
    }
}

