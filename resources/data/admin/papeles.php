<?php

/**
 * Fixture data for resources/views/admin/papeles.blade.php.
 *
 * TAB 04: the view declared these inline. Moved out so the view is presentation
 * only, and so the shapes have one home when they are swapped for API calls --
 * the field names here ARE the backend contract and must not be renamed.
 *
 * A render-time data file rather than a config file: config files are loaded in
 * filename order during bootstrap and cannot call config() on one another, and
 * several of these call config()/asset()/route(). See
 * config/esperanza_citizen_avatars.php's header for the original note.
 *
 * Required by the view as:
 *     $__fixtures = require base_path('resources/data/admin/papeles.php');
 */

return [
    'docTypePool' => [
        ['type' => 'Valid ID (PhilSys National ID)', 'category' => 'Identity / Verification', 'file_type' => 'jpg'],
        ['type' => '2x2 ID Photo', 'category' => 'Identity / Verification', 'file_type' => 'jpg'],
        ['type' => 'PSA Birth Certificate', 'category' => 'Civil Registry', 'file_type' => 'pdf'],
        ['type' => 'Marriage Certificate', 'category' => 'Civil Registry', 'file_type' => 'pdf'],
        ['type' => 'Barangay Certificate of Indigency', 'category' => 'Barangay Certificates', 'file_type' => 'pdf'],
        ['type' => 'Barangay Residency', 'category' => 'Barangay Certificates', 'file_type' => 'pdf'],
        ['type' => 'Cedula (Community Tax Certificate)', 'category' => 'Tax & Property', 'file_type' => 'jpg'],
        ['type' => 'DTI Registration', 'category' => 'Business & Permits', 'file_type' => 'pdf'],
        ['type' => 'Certificate of Employment', 'category' => 'Supporting Documents', 'file_type' => 'docx'],
        ['type' => 'Proof of Income', 'category' => 'Supporting Documents', 'file_type' => 'pdf'],
    ],

    'storageStatuses' => ['Submitted', 'Under Verification', 'Under Review', 'Approved', 'Completed', 'Rejected'],

    'storageMonths' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],

    'storageCitizenIds' => [
        'RES-2026-023', 'RES-2026-024', 'RES-2026-028', 'RES-2026-030',
        'RES-2026-101', 'RES-2026-104', 'RES-2026-106', 'RES-2026-108',
        'RES-2026-121', 'RES-2026-122', 'RES-2026-123', 'RES-2026-124', 'RES-2026-125',
        'RES-2026-126', 'RES-2026-127', 'RES-2026-128', 'RES-2026-129', 'RES-2026-130', 'RES-2026-131',
    ],

    'citizenDocuments' => [],

    'storageFinalStatuses' => ['Approved', 'Completed', 'Rejected', 'Archived'],
];
