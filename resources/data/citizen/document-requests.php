<?php

/**
 * Fixture data for resources/views/citizen/document-requests.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/document-requests.php');
 */

return [
    'requests' => [
        ['ref' => 'DR-2026-2098', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall', 'purpose' => 'Proof of Residency, Local Employment', 'submitted' => 'Aug 20, 2026', 'status' => 'Mark to Release',
            'previewType' => 'barangay', 'docKey' => 'brgy_clearance', 'certTitle' => 'BARANGAY CLEARANCE', 'certSubtitle' => null, 'barangay' => 'Baras',
            'formSnapshot' => [
                'requester_name' => 'Cristy Pareja Bonghanoy',
                'age' => '34',
                'residency_type' => 'Permanent',
                'purok' => '3',
                'purpose' => ['Proof of Residency', 'Local Employment'],
            ],
        ],
        ['ref' => 'DR-2026-2210', 'type' => 'Cedula (Community Tax Certificate)', 'office' => "Treasurer's Office", 'purpose' => 'Employment requirement', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2187', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall', 'purpose' => 'Proof of Residency', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-1998', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'purpose' => 'School enrollment', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-1872', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'purpose' => 'Medical assistance application', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-1655', 'type' => 'Business Permit (New)', 'office' => 'BPLO', 'purpose' => 'Sari-sari store registration', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'requirements' => [
            ['key' => 'req_dti', 'name' => 'DTI or SEC Registration', 'file' => 'DTI-Registration.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_business', 'name' => 'Barangay Business Clearance', 'file' => 'Brgy-Business-Clearance.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_zoning', 'name' => 'Locational / Zoning Clearance', 'file' => 'Zoning-Clearance.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_fsic', 'name' => 'Fire Safety Inspection Certificate (FSIC)', 'file' => 'Fire-Safety-Inspection-Certificate.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_sanitary', 'name' => 'Sanitary Permit', 'file' => 'Sanitary-Permit.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_cedula', 'name' => 'Cedula (Community Tax Certificate)', 'file' => 'Cedula-2026.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_lease', 'name' => 'Lease contract or land title', 'file' => 'Lease-Contract.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-1490', 'type' => 'Real Property Tax Clearance', 'office' => "Treasurer's Office", 'purpose' => 'Lot sale requirement', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2201', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'purpose' => 'Medical assistance application', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'One (1) valid government-issued ID', 'file' => 'My-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_indigency', 'name' => 'Barangay Certification of Indigency', 'file' => 'Brgy-Indigency-Cert.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_interview', 'name' => 'Brief interview / case assessment with MSWDO', 'file' => 'Interview-Notes.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
    ],

    'documentTypes' => [],

    'barangayCatalog' => [],

    'barangayNames' => [],

    'pickupDirectory' => [
        "Treasurer's Office" => ['contact' => '(056) 333-1032', 'address' => 'Municipal Hall, Poblacion, Esperanza, Masbate'],
        'Civil Registrar' => ['contact' => '(056) 333-1067', 'address' => 'Municipal Hall, Poblacion, Esperanza, Masbate'],
        'MSWDO' => ['contact' => '(056) 333-1044', 'address' => 'Municipal Hall, Poblacion, Esperanza, Masbate'],
        'BPLO' => ['contact' => '(056) 333-1050', 'address' => 'Municipal Hall, Poblacion, Esperanza, Masbate'],
        'OSCA' => ['contact' => '(056) 333-1088', 'address' => 'Municipal Hall, Poblacion, Esperanza, Masbate'],
    ],

    'submittedDocs' => [
        ['name' => 'PhilSys National ID.jpg', 'category' => 'IMG', 'type' => 'Valid ID', 'size' => '1.2 MB', 'uploaded' => 'Jul 8, 2026', 'status' => 'Approved'],
        ['name' => 'Barangay Certificate.pdf', 'category' => 'PDF', 'type' => 'Proof of Residency', 'size' => '480 KB', 'uploaded' => 'Jul 3, 2026', 'status' => 'Approved'],
        ['name' => '2x2 ID Photo.jpg', 'category' => 'IMG', 'type' => 'Photo', 'size' => '220 KB', 'uploaded' => 'Jun 14, 2026', 'status' => 'Approved'],
        ['name' => 'DTI Registration.pdf', 'category' => 'PDF', 'type' => 'Business Document', 'size' => '640 KB', 'uploaded' => 'May 12, 2026', 'status' => 'Submitted'],
        ['name' => 'Affidavit of Loss.docx', 'category' => 'DOCX', 'type' => 'Supporting Document', 'size' => '96 KB', 'uploaded' => 'Apr 20, 2026', 'status' => 'Completed'],
    ],

    'availableFiles' => [
        ['name' => 'TIN ID.jpg', 'category' => 'IMG'],
        ['name' => 'Marriage Certificate.pdf', 'category' => 'PDF'],
        ['name' => 'Certificate of Employment.docx', 'category' => 'DOCX'],
    ],

    'fileCardStyle' => [
        'IMG' => ['ring' => 'from-brand-200 to-brand-50', 'icon' => 'image', 'iconColor' => 'text-brand-400', 'badge' => 'bg-brand-600 text-white'],
        'PDF' => ['ring' => 'from-rose-200 to-rose-50', 'icon' => 'file-text', 'iconColor' => 'text-rose-400', 'badge' => 'bg-rose-600 text-white'],
        'DOCX' => ['ring' => 'from-blue-200 to-blue-50', 'icon' => 'file-type', 'iconColor' => 'text-blue-400', 'badge' => 'bg-blue-600 text-white'],
    ],
];
