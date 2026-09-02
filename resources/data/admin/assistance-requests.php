<?php

/**
 * Fixture data for resources/views/admin/assistance-requests.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/assistance-requests.php');
 */

return [
    // Tulong case data. Statuses are Tulong's own vocabulary
    // (PROCESS_TAB_FLOW.txt PART 2), not Dokyu's -- ported from the Tulong
    // redesign in 16fc534.
    'requests' => [
        ['ref' => 'AR-2026-0151', 'citizen' => 'Elmer Bantillo', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Santiago', 'submitted' => 'Jul 9, 2026', 'status' => 'Submitted'],
        ['ref' => 'AR-2026-0195', 'citizen' => 'Angelica Fajardo', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Rizal', 'submitted' => 'Jul 6, 2026', 'status' => 'Initial Screening'],
        ['ref' => 'AR-2026-0142', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jun 28, 2026', 'status' => 'Assistance Processing'],
        ['ref' => 'AR-2026-0098', 'citizen' => 'Rodrigo Palma', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Domorog', 'submitted' => 'May 20, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0061', 'citizen' => 'Teresita Salazar', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Agoho', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0044', 'citizen' => 'Michael Bacus', 'type' => 'Food / Relief Assistance', 'office' => 'MSWDO', 'barangay' => 'Iligan', 'submitted' => 'Mar 18, 2026', 'status' => 'Initial Screening'],
        ['ref' => 'AR-2026-0037', 'citizen' => 'Josefina Marbella', 'type' => 'Social Pension (Indigent Senior)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Feb 10, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0022', 'citizen' => 'Rosemarie Tan', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Jan 28, 2026', 'status' => 'Needs Requirements', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'Rosemarie-SoloParentID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_psa_birth', 'name' => 'PSA Birth Certificate(s) of children', 'file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.', 'versions' => [
                ['file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'date' => 'Jan 28, 2026', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.'],
            ]],
            ['key' => 'req_brgy_cert', 'name' => 'Barangay Certification', 'file' => 'Brgy-Cert-Baras.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0009', 'citizen' => 'Ferdinand Cortez', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jan 8, 2026', 'status' => 'Rejected', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.', 'requirements' => [
            ['key' => 'req_death_cert', 'name' => 'Death Certificate', 'file' => null, 'status' => 'Rejected', 'reject_reason' => 'Missing information', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.', 'versions' => [
                ['file' => null, 'status' => 'Rejected', 'date' => 'Jan 8, 2026', 'reject_reason' => 'Missing information', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.'],
            ]],
            ['key' => 'req_claimant_id', 'name' => 'Valid ID of claimant', 'file' => 'Ferdinand-Valid-ID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_relationship', 'name' => 'Proof of relationship to the deceased', 'file' => 'Proof-of-Relationship.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_funeral_contract', 'name' => 'Funeral contract or bill', 'file' => 'Funeral-Contract.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_indigency', 'name' => 'Barangay Certificate of Indigency', 'file' => 'Brgy-Indigency-Cert.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        // Reconciled 1:1 with the same residents' Constituent Profile records
        // (same ref numbers) so "Open in Tulong" from a profile always lands on
        // real, matching data instead of an empty search result.
        ['ref' => 'AR-2026-0117', 'citizen' => 'Marites Gonzales Ferrer', 'type' => 'Seed & Fertilizer Assistance', 'office' => 'Department of Agriculture', 'barangay' => 'Agoho', 'submitted' => 'Mar 3, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0163', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'May 18, 2026', 'status' => 'Completed'],
        // Cristy is now demo'd as an 18-year-old Scholarship applicant, not
        // a Solo Parent — see her matching reset in
        // config/esperanza_constituents.php.
        ['ref' => 'AR-2026-0224', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Educational Assistance', 'office' => "MSWDO / Mayor's Office", 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted'],
        // Full-catalog demo set — one submission per remaining Tulong
        // program (see config/esperanza_validation_guide.php) on Cristy's
        // own account, with real per-type eligibility feedback rather than
        // a generic "not eligible" — freshly Submitted where her actual
        // profile plausibly qualifies, and Rejected with the specific
        // disqualifying reason where it clearly doesn't (age for Social
        // Pension, no dependent children for Solo Parent, no documented
        // disability for PWD, a fatherhood-only program for ERPAT).
        ['ref' => 'AR-2026-0230', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Financial assistance for dental treatment expenses', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_medical_need', 'name' => 'Medical Need (Abstract / Prescription)', 'file' => 'Cristy-medical-need-abstract-prescription.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_financial_obligation', 'name' => 'Financial Obligation (Hospital Bill / SOA)', 'file' => 'Cristy-financial-obligation-hospital-bill-soa.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (Barangay Certificate)', 'file' => 'Cristy-indigency-barangay-certificate.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0231', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Burial assistance for a deceased grandparent', 'requirements' => [
            ['key' => 'req_death_cert', 'name' => 'Death Certificate', 'file' => 'Cristy-death-certificate.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_claimant_id', 'name' => 'Claimant ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_relationship', 'name' => 'Relationship to Deceased', 'file' => 'Cristy-relationship-to-deceased.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (Barangay Certificate)', 'file' => 'Cristy-indigency-barangay-certificate.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0232', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Food / Relief Assistance', 'office' => 'MDRRMO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Relief assistance following recent heavy rains affecting the household', 'requirements' => [
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_affected_status', 'name' => 'Affected / Eligible Status', 'file' => 'Cristy-affected-eligible-status.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0233', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Social pension benefit application', 'requirements' => [
            ['key' => 'req_senior_status', 'name' => 'Senior Citizen Status', 'file' => 'Cristy-senior-citizen-status.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_declaration', 'name' => 'No Pension / Income / Support Declaration', 'file' => 'Cristy-no-pension-income-support-declaration.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0234', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Solo parent cash assistance application', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'Cristy-solo-parent-id.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_children_records', 'name' => "Children's Civil Records", 'file' => 'Cristy-children-s-civil-records.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0235', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'PWD Registration (PRPWD)', 'office' => 'PDAO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'PWD ID / PRPWD registration application', 'requirements' => [
            ['key' => 'req_disability_evidence', 'name' => 'Disability Evidence (Medical Certificate)', 'file' => 'Cristy-disability-evidence-medical-certificate.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_photo', 'name' => '1x1 Photo', 'file' => 'Cristy-1x1-photo.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0236', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'TUPAD Emergency Employment', 'office' => 'MPESO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Application for short-term emergency employment during school break', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_residency', 'name' => 'Barangay Certification of Residency', 'file' => 'Cristy-barangay-certification-of-residency.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (if applicable)', 'file' => 'Cristy-indigency-if-applicable.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0237', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'TESDA Skills Training Registration', 'office' => 'MPESO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Registration for Bookkeeping NC III skills training program', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Identity / Birth Record', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_photo', 'name' => '2x2 Photo', 'file' => 'Cristy-2x2-photo.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_training_profile', 'name' => 'Training Profile', 'file' => 'Cristy-training-profile.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0238', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'ERPAT Program Registration', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'ERPAT program registration', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_program_profile', 'name' => 'Program Profile', 'file' => 'Cristy-program-profile.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0159', 'citizen' => 'Ferdinand Ramos Hilario', 'type' => 'Livelihood Assistance (Tools & Equipment)', 'office' => 'MSWDO', 'barangay' => 'Libertad', 'submitted' => 'May 28, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0148', 'citizen' => 'Analiza Bautista Ibarra', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'barangay' => 'Magsaysay', 'submitted' => 'Jun 2, 2026', 'status' => 'Initial Screening'],
        ['ref' => 'AR-2026-0182', 'citizen' => 'Danilo Aquino Lazaro', 'type' => 'Agricultural Damage Assistance', 'office' => 'Department of Agriculture', 'barangay' => 'Potingbato', 'submitted' => 'Jun 25, 2026', 'status' => 'Assistance Processing'],
        ['ref' => 'AR-2026-0171', 'citizen' => 'Precious Domingo Manalo', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Rizal', 'submitted' => 'Jul 2, 2026', 'status' => 'Initial Screening'],
        ['ref' => 'AR-2026-0210', 'citizen' => 'Liezel Navarro Quimson', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Tawad', 'submitted' => 'Jul 5, 2026', 'status' => 'Needs Requirements'],
        ['ref' => 'AR-2026-0203', 'citizen' => 'Cherry Ann Villaflor Sarmiento', 'type' => 'Educational Assistance (Continuing Studies)', 'office' => "Mayor's Office", 'barangay' => 'Villa', 'submitted' => 'Jun 12, 2026', 'status' => 'Submitted'],
    ],

    'typeToServiceGuideKey' => [
        'Medical Assistance (AICS)' => 'Medical Assistance (AICS)',
        'Burial Assistance (AICS)' => 'Burial Assistance (AICS)',
        'Educational Assistance' => 'Educational Assistance',
        'Educational Assistance (Continuing Studies)' => 'Educational Assistance',
        'Financial Assistance (AICS)' => 'Financial Assistance (AICS)',
        'Food / Relief Assistance' => 'Food / Relief Assistance',
        'Social Pension (Indigent Senior)' => 'Social Pension (Indigent Senior Citizen)',
        'Social Pension (Indigent Senior Citizen)' => 'Social Pension (Indigent Senior Citizen)',
        'Solo Parent Cash Assistance' => 'Solo Parent Cash Assistance',
        // Guide-only services with no prior app usage — added so Cristy
        // Bonghanoy's full-catalog demo submissions (every Tulong type,
        // with real per-type eligibility feedback) each resolve to their
        // own specific checklist rather than falling back to the general
        // one. Senior Citizen ID Application (OSCA Membership) is
        // deliberately not duplicated here — it's already modeled as a
        // Dokyu service in document-requests.blade.php.
        'PWD Registration (PRPWD)' => 'PWD Registration (PRPWD)',
        'TUPAD Emergency Employment' => 'TUPAD Emergency Employment',
        'TESDA Skills Training Registration' => 'TESDA Skills Training Registration',
        'ERPAT Program Registration' => 'ERPAT Program Registration',
    ],

    'tulongClassification' => [
        'Medical Assistance (AICS)' => ['category' => 'Medical & Health', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Burial Assistance (AICS)' => ['category' => 'Burial & Bereavement', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Educational Assistance' => ['category' => 'Education', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'Financial Assistance (AICS)' => ['category' => 'Financial Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Food / Relief Assistance' => ['category' => 'Food & Relief', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'Social Pension (Indigent Senior)' => ['category' => 'Social Protection', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'Solo Parent Cash Assistance' => ['category' => 'Social Protection', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'PWD Registration (PRPWD)' => ['category' => 'Social Protection', 'office' => 'Persons with Disability Affairs Office', 'level' => 'Municipal'],
        'TUPAD Emergency Employment' => ['category' => 'Livelihood & Employment', 'office' => 'Municipal Public Employment Service Office', 'level' => 'Municipal'],
        'TESDA Skills Training Registration' => ['category' => 'Livelihood & Employment', 'office' => 'Municipal Public Employment Service Office', 'level' => 'Municipal'],
        'ERPAT Program Registration' => ['category' => 'Social Protection', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
    ],

    'assignedByRef' => [
        'AR-2026-0195' => 'Corazon Villareal', 'AR-2026-0142' => 'Corazon Villareal',
    ],

    'purposeByCategory' => [
        'Medical & Health' => 'Financial assistance for medical treatment / hospitalization expenses',
        'Burial & Bereavement' => 'Financial assistance for burial and funeral expenses of a deceased family member',
        'Education' => 'Educational assistance to support continuing studies',
        'Financial Assistance' => 'Financial assistance for basic family needs',
        'Food & Relief' => 'Relief assistance following hardship or calamity',
        'Social Protection' => 'Social protection assistance benefit application',
    ],

    'defaultRequirementsByCategory' => [
        'Medical & Health' => ['Medical Certificate / Hospital Bill', 'Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Burial & Bereavement' => ['Death Certificate', 'Valid ID of Claimant', 'Proof of Relationship to the Deceased'],
        'Education' => ['Certificate of Enrollment', 'Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Financial Assistance' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Food & Relief' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Social Protection' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
    ],

    'idRequirementNames' => ['Valid Government-Issued ID', 'Valid Government ID', 'Valid ID', 'Requester ID', 'Claimant ID', 'Owner ID', 'Identity / Birth Record', 'Age / Date of Birth Proof', 'Identity'],

    'idPreviewAssetByCitizen' => [
        'Cristy Pareja Bonghanoy' => asset('images/esperanza/residents/cristy-id.png'),
        'Ronaldo Panganiban Bautista' => asset('images/esperanza/residents/ronaldo-id.png'),
    ],

    'tabs' => ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Ready for Release', 'done' => 'Completed'],

    'actionStatuses' => ['Submitted', 'Initial Screening', 'Assessment', 'For Approval', 'Needs Requirements', 'Returned for Reassessment'],
];
