<?php

return [
    'inactivity_expiry_days' => (int) env('ADMISSIONS_INACTIVITY_EXPIRY_DAYS', 90),
    'warning_days' => [
        'first' => (int) env('ADMISSIONS_WARNING_FIRST_DAYS', 14),
        'final' => (int) env('ADMISSIONS_WARNING_FINAL_DAYS', 3),
    ],

    'magic_link_expiry_minutes' => (int) env('ADMISSIONS_MAGIC_LINK_EXPIRY_MINUTES', 20),
    'session_idle_minutes' => (int) env('ADMISSIONS_SESSION_IDLE_MINUTES', 30),
    'session_ttl_minutes' => (int) env('ADMISSIONS_SESSION_TTL_MINUTES', 120),
    'session_bind_ip' => (bool) env('ADMISSIONS_SESSION_BIND_IP', false),
    'stepup_ttl_minutes' => (int) env('ADMISSIONS_STEPUP_TTL_MINUTES', 10),
    'stepup' => [
        'allowed_actions' => [
            'sensitive_action',
            'change_identity',
            'change_guardian_contact',
            'replace_documents',
            'recover_archived',
            'suspicious_activity',
        ],
        'action_scopes' => [
            'sensitive_action' => ['*'],
            'change_identity' => ['identity'],
            'change_guardian_contact' => ['guardian_contact'],
            'replace_documents' => ['documents'],
            'recover_archived' => ['recovery'],
            'suspicious_activity' => ['suspicious'],
        ],
    ],

    'suspicious' => [
        'token_verify_fail_threshold' => (int) env('ADMISSIONS_VERIFY_FAIL_THRESHOLD', 5),
        'recovery_request_threshold' => (int) env('ADMISSIONS_RECOVERY_THRESHOLD', 5),
        'verification_consume_fail_threshold' => (int) env('ADMISSIONS_VERIFICATION_CONSUME_FAIL_THRESHOLD', 10),
        'stepup_request_threshold' => (int) env('ADMISSIONS_STEPUP_REQUEST_THRESHOLD', 10),
        'doc_replace_attempt_threshold' => (int) env('ADMISSIONS_DOC_REPLACE_ATTEMPT_THRESHOLD', 10),
        'lockout_minutes' => (int) env('ADMISSIONS_LOCKOUT_MINUTES', 15),
    ],

    'intake' => [
        'require_open_intake_for_drafts' => (bool) env('ADMISSIONS_REQUIRE_OPEN_INTAKE_FOR_DRAFTS', true),
        'archive_drafts_on_close' => (bool) env('ADMISSIONS_ARCHIVE_DRAFTS_ON_CLOSE', true),
    ],

    'public_urls' => [
        'recovery' => env('ADMISSIONS_PUBLIC_RECOVERY_URL', env('APP_URL', 'http://localhost') . '/admissions/recover'),
        'verify' => env('ADMISSIONS_PUBLIC_VERIFY_URL', env('APP_URL', 'http://localhost') . '/admissions/verify'),
    ],

    'office' => [
        'stale_days' => (int) env('ADMISSIONS_OFFICE_STALE_DAYS', 7),
        'message_templates' => [
            'documents_required' => [
                'title' => 'Documents required',
                'message' => "We need additional documents or updates for application {application_number} ({student_name}). Please review your checklist and upload the requested updates.",
            ],
            'accepted' => [
                'title' => 'Application accepted',
                'message' => "Your application {application_number} ({student_name}) has been accepted. This is a pre-enrollment decision; further instructions will follow.",
            ],
            'rejected' => [
                'title' => 'Application outcome',
                'message' => "Your application {application_number} ({student_name}) was not successful at this time.",
            ],
            'waitlisted' => [
                'title' => 'Waitlist update',
                'message' => "Your application {application_number} ({student_name}) has been placed on the waitlist. We will contact you if a place becomes available.",
            ],
        ],
    ],
];
