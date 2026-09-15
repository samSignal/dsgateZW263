<?php

/**
 * Institution contact details shown on the letterhead of printed/PDF documents
 * (fee statements, arrears reports, receipts, etc). Edit the values below — or set
 * the matching SCHOOL_* environment variables — with the school's real, current
 * details before these go out to parents or auditors.
 */
return [
    'name'    => env('SCHOOL_NAME', 'DestinyGate Institute'),
    'address' => env('SCHOOL_ADDRESS', '15412 Samson Kanyemba Road, Runyararo West, Masvingo'),
    'phone'   => env('SCHOOL_PHONE', '+263 779 672 246 / +263 710 415 364'),
    'email'   => env('SCHOOL_EMAIL', 'finance@destinygate.edu'),
];
