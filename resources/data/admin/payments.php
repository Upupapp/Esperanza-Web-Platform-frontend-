<?php

/**
 * Fixture data for resources/views/admin/payments.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/payments.php');
 */

return [
    'paymentsClassification' => [
        'Cedula (Community Tax Certificate)' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Business Permit (New)' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Barangay Clearance' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Certificate of Residency' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Business Permit Renewal' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Real Property Tax Clearance' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Social Pension — Documentary Fee' => ['category' => 'Social Protection', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'Barangay Certification (General Purpose)' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Certificate of Live Birth (Certified Copy)' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Application for Marriage License' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Certified Copy of Marriage Certificate' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Birth' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Death' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Barangay Certification for Late Registration of Birth' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Certification for Registration of Death' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Pet Registration' => ['category' => 'Other', 'office' => 'Municipal Agriculture Office', 'level' => 'Municipal'],
        'Locational Clearance' => ['category' => 'Business & Permits', 'office' => 'Municipal Planning & Development Office (MPDO)', 'level' => 'Municipal'],
    ],

    'adminFiles' => [
        ['name' => 'GCash Receipt Scan.jpg', 'category' => 'IMG'],
        ['name' => 'PayMaya Confirmation.png', 'category' => 'IMG'],
        ['name' => 'Official Receipt (OR) Scan.pdf', 'category' => 'PDF'],
    ],

    'tabs' => ['all' => 'All', 'unpaid' => 'Awaiting Payment', 'review' => 'Pending Review', 'paid' => 'Paid'],
];
