<?php

/**
 * Fixture data for resources/views/citizen/assistance-requests.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/assistance-requests.php');
 */

return [
    // Tulong case data. Statuses are Tulong's own vocabulary (PROCESS_TAB_FLOW.txt
    // PART 2), not Dokyu's -- ported from the Tulong redesign in 16fc534.
    'requests' => [
        ['ref' => 'AR-2026-0142', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'purpose' => 'Hospital bill support — Masbate Provincial Hospital', 'submitted' => 'Jun 28, 2026', 'status' => 'Assistance Processing'],
        ['ref' => 'AR-2026-0098', 'type' => 'Educational Assistance', 'office' => 'MSWDO', 'purpose' => 'Tuition support for 2 dependents, SY 2026-2027', 'submitted' => 'May 20, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0061', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'purpose' => 'Livelihood capital support', 'submitted' => 'Apr 2, 2026', 'status' => 'Initial Screening'],
        ['ref' => 'AR-2026-0037', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'purpose' => 'Quarterly social pension enrollment', 'submitted' => 'Feb 10, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0009', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'purpose' => 'Funeral expenses support', 'submitted' => 'Jan 8, 2026', 'status' => 'Rejected', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.', 'requirements' => [
            ['key' => 'req_death_cert', 'name' => 'Death Certificate', 'file' => null, 'status' => 'Rejected', 'reject_reason' => 'Missing information', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.', 'versions' => [
                ['file' => null, 'status' => 'Rejected', 'date' => 'Jan 8, 2026', 'reject_reason' => 'Missing information', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.'],
            ]],
            ['key' => 'req_claimant_id', 'name' => 'Valid ID of claimant', 'file' => 'My-Valid-ID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_relationship', 'name' => 'Proof of relationship to the deceased', 'file' => 'Proof-of-Relationship.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_funeral_contract', 'name' => 'Funeral contract or bill', 'file' => 'Funeral-Contract.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_indigency', 'name' => 'Barangay Certificate of Indigency', 'file' => 'Brgy-Indigency-Cert.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0022', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'purpose' => 'Cash grant + goods, per assessment', 'submitted' => 'Jan 28, 2026', 'status' => 'Needs Requirements', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'My-SoloParentID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_psa_birth', 'name' => 'PSA Birth Certificate(s) of children', 'file' => 'PSA-Birth-Cert.jpg', 'status' => 'Flagged for Replacement', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.', 'versions' => [
                ['file' => 'PSA-Birth-Cert.jpg', 'status' => 'Flagged for Replacement', 'date' => 'Jan 28, 2026', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.'],
            ]],
            ['key' => 'req_brgy_cert', 'name' => 'Barangay Certification', 'file' => 'Brgy-Cert.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
    ],

    'submittedDocs' => [
        ['name' => 'PhilSys National ID.jpg', 'category' => 'IMG'],
        ['name' => 'Barangay Certificate of Indigency.pdf', 'category' => 'PDF'],
        ['name' => 'Medical Abstract.pdf', 'category' => 'PDF'],
        ['name' => 'Hospital Bill.pdf', 'category' => 'PDF'],
        ['name' => 'Senior Citizen ID.jpg', 'category' => 'IMG'],
    ],
];
