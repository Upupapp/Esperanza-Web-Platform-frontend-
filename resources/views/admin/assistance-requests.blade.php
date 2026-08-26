@php
    $barangays = config('esperanza.barangays');
    // Citizen name -> avatar + biographical-fact maps, built here (not in a
    // config file — see config/esperanza_citizen_avatars.php's own header
    // for why: a config file can't safely call config() on another config
    // file from its own top level, since Laravel loads them in filename
    // order during bootstrap) so a resident's real picture and real profile
    // facts (dob/sex/civil status/occupation) are consistent wherever their
    // name appears — not just their own Constituents profile, and not
    // re-randomized per document (that was the "Cristy is 25 on her
    // profile but 53 on her scholarship form" bug).
    $citizenAvatarData = config('esperanza_citizen_avatars');
    $generatedAvatarFor = function ($acctId) use ($citizenAvatarData) {
        $bg = $citizenAvatarData['backgrounds'][crc32($acctId) % count($citizenAvatarData['backgrounds'])];
        return 'https://api.dicebear.com/9.x/personas/svg?seed=' . urlencode($acctId) . '&backgroundColor=' . $bg;
    };
    $citizenAvatars = [];
    $citizenBio = [];
    foreach (config('esperanza_constituents')['individuals'] as $ind) {
        if (empty($ind['citizen_account_id'])) {
            continue;
        }
        $acctId = $ind['citizen_account_id'];
        $citizenAvatars[$ind['name']] = $citizenAvatarData['photoByAccountId'][$acctId] ?? $generatedAvatarFor($acctId);
        $citizenBio[$ind['name']] = [
            'dob' => $ind['dob'] ?? null,
            'sex' => $ind['sex'] ?? null,
            'civil_status' => $ind['civil_status'] ?? null,
            'occupation' => $ind['occupation'] ?? null,
            // See the identical field's own comment in
            // admin/document-requests.blade.php — only populated where the
            // Constituents profile actually has this on file.
            'emergency_name' => $ind['emergency']['name'] ?? null,
            'emergency_contact' => $ind['emergency']['contact'] ?? null,
            // See the identical fields' own comment in admin/document-requests.blade.php.
            'father_name' => $ind['father_name'] ?? null,
            'mother_name' => $ind['mother_name'] ?? null,
            'mother_maiden_name' => $ind['mother_maiden_name'] ?? null,
            'education' => $ind['education'] ?? null,
            'address' => $ind['address'] ?? null,
        ];
    }
    $requests = [
        ['ref' => 'AR-2026-0151', 'citizen' => 'Elmer Bantillo', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Santiago', 'submitted' => 'Jul 9, 2026', 'status' => 'Pending Review'],
        ['ref' => 'AR-2026-0195', 'citizen' => 'Angelica Fajardo', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Rizal', 'submitted' => 'Jul 6, 2026', 'status' => 'Under Verification'],
        ['ref' => 'AR-2026-0142', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'citizen' => 'Rodrigo Palma', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Domorog', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'citizen' => 'Teresita Salazar', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Agoho', 'submitted' => 'Apr 2, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0044', 'citizen' => 'Michael Bacus', 'type' => 'Food / Relief Assistance', 'office' => 'MSWDO', 'barangay' => 'Iligan', 'submitted' => 'Mar 18, 2026', 'status' => 'Under Verification'],
        ['ref' => 'AR-2026-0037', 'citizen' => 'Josefina Marbella', 'type' => 'Social Pension (Indigent Senior)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Feb 10, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0022', 'citizen' => 'Rosemarie Tan', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Jan 28, 2026', 'status' => 'Under Review', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'Rosemarie-SoloParentID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_psa_birth', 'name' => "PSA Birth Certificate(s) of children", 'file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.', 'versions' => [
                ['file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'date' => 'Jan 28, 2026', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.'],
            ]],
            ['key' => 'req_brgy_cert', 'name' => 'Barangay Certification', 'file' => 'Brgy-Cert-Baras.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
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
        ['ref' => 'AR-2026-0117', 'citizen' => 'Marites Gonzales Ferrer', 'type' => 'Seed & Fertilizer Assistance', 'office' => 'Department of Agriculture', 'barangay' => 'Agoho', 'submitted' => 'Mar 3, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0163', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'May 18, 2026', 'status' => 'Released'],
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
            ['key' => 'req_valid_id', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_medical_need', 'name' => 'Medical Need (Abstract / Prescription)', 'file' => 'Cristy-medical-need-abstract-prescription.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_financial_obligation', 'name' => 'Financial Obligation (Hospital Bill / SOA)', 'file' => 'Cristy-financial-obligation-hospital-bill-soa.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (Barangay Certificate)', 'file' => 'Cristy-indigency-barangay-certificate.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0231', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Burial assistance for a deceased grandparent', 'requirements' => [
            ['key' => 'req_death_cert', 'name' => 'Death Certificate', 'file' => 'Cristy-death-certificate.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_claimant_id', 'name' => 'Claimant ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_relationship', 'name' => 'Relationship to Deceased', 'file' => 'Cristy-relationship-to-deceased.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (Barangay Certificate)', 'file' => 'Cristy-indigency-barangay-certificate.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0232', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Food / Relief Assistance', 'office' => 'MDRRMO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Relief assistance following recent heavy rains affecting the household', 'requirements' => [
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_affected_status', 'name' => 'Affected / Eligible Status', 'file' => 'Cristy-affected-eligible-status.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0233', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Social pension benefit application', 'requirements' => [
            ['key' => 'req_senior_status', 'name' => 'Senior Citizen Status', 'file' => 'Cristy-senior-citizen-status.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_declaration', 'name' => 'No Pension / Income / Support Declaration', 'file' => 'Cristy-no-pension-income-support-declaration.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0234', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Solo parent cash assistance application', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'Cristy-solo-parent-id.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_children_records', 'name' => "Children's Civil Records", 'file' => 'Cristy-children-s-civil-records.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0235', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'PWD Registration (PRPWD)', 'office' => 'PDAO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'PWD ID / PRPWD registration application', 'requirements' => [
            ['key' => 'req_disability_evidence', 'name' => 'Disability Evidence (Medical Certificate)', 'file' => 'Cristy-disability-evidence-medical-certificate.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_photo', 'name' => '1x1 Photo', 'file' => 'Cristy-1x1-photo.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0236', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'TUPAD Emergency Employment', 'office' => 'MPESO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Application for short-term emergency employment during school break', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_residency', 'name' => 'Barangay Certification of Residency', 'file' => 'Cristy-barangay-certification-of-residency.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_indigency', 'name' => 'Indigency (if applicable)', 'file' => 'Cristy-indigency-if-applicable.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0237', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'TESDA Skills Training Registration', 'office' => 'MPESO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Registration for Bookkeeping NC III skills training program', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Identity / Birth Record', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_photo', 'name' => '2x2 Photo', 'file' => 'Cristy-2x2-photo.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_training_profile', 'name' => 'Training Profile', 'file' => 'Cristy-training-profile.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0238', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'ERPAT Program Registration', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'ERPAT program registration', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Valid Government ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_program_profile', 'name' => 'Program Profile', 'file' => 'Cristy-program-profile.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0159', 'citizen' => 'Ferdinand Ramos Hilario', 'type' => 'Livelihood Assistance (Tools & Equipment)', 'office' => 'MSWDO', 'barangay' => 'Libertad', 'submitted' => 'May 28, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0148', 'citizen' => 'Analiza Bautista Ibarra', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'barangay' => 'Magsaysay', 'submitted' => 'Jun 2, 2026', 'status' => 'Under Verification'],
        ['ref' => 'AR-2026-0182', 'citizen' => 'Danilo Aquino Lazaro', 'type' => 'Agricultural Damage Assistance', 'office' => 'Department of Agriculture', 'barangay' => 'Potingbato', 'submitted' => 'Jun 25, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0171', 'citizen' => 'Precious Domingo Manalo', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Rizal', 'submitted' => 'Jul 2, 2026', 'status' => 'Under Verification'],
        ['ref' => 'AR-2026-0210', 'citizen' => 'Liezel Navarro Quimson', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Tawad', 'submitted' => 'Jul 5, 2026', 'status' => 'Under Review'],
        ['ref' => 'AR-2026-0203', 'citizen' => 'Cherry Ann Villaflor Sarmiento', 'type' => 'Educational Assistance (Continuing Studies)', 'office' => "Mayor's Office", 'barangay' => 'Villa', 'submitted' => 'Jun 12, 2026', 'status' => 'Pending Review'],
    ];

    // Manual Document Validation & Application Decision Guide — maps each
    // case's 'type' string to its checklist key in
    // config/esperanza_validation_guide.php. A few mock types have no
    // service defined in the LGU-supplied guide yet (e.g. Agricultural
    // Damage Assistance); those fall back to the general Tulong checklist
    // in the validation-guide panel rather than inventing criteria the
    // guide never specified.
    $validationGuide = config('esperanza_validation_guide');
    $typeToServiceGuideKey = [
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
    ];

    // Classification lookup for the mock request rows above, per the Dokyu &
    // Tulong routing plan -- centralized here rather than hand-edited per row.
    $tulongClassification = [
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
    ];
    $assignedByRef = [
        'AR-2026-0195' => 'Corazon Villareal', 'AR-2026-0142' => 'Corazon Villareal',
    ];
    // Same reasoning as Dokyu — every case needs to open as a complete,
    // filled-in record, not just a status row. Rows without hand-authored
    // purpose/requirements get realistic ones synthesized from their
    // category below.
    $purposeByCategory = [
        'Medical & Health' => 'Financial assistance for medical treatment / hospitalization expenses',
        'Burial & Bereavement' => 'Financial assistance for burial and funeral expenses of a deceased family member',
        'Education' => 'Educational assistance to support continuing studies',
        'Financial Assistance' => 'Financial assistance for basic family needs',
        'Food & Relief' => 'Relief assistance following hardship or calamity',
        'Social Protection' => 'Social protection assistance benefit application',
    ];
    $defaultRequirementsByCategory = [
        'Medical & Health' => ['Medical Certificate / Hospital Bill', 'Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Burial & Bereavement' => ['Death Certificate', 'Valid ID of Claimant', 'Proof of Relationship to the Deceased'],
        'Education' => ['Certificate of Enrollment', 'Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Financial Assistance' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Food & Relief' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Social Protection' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
    ];
    // Requirement names that are literally "the citizen's ID" rather than a
    // different supporting document — see the identical list's own comment
    // in admin/document-requests.blade.php.
    $idRequirementNames = ['Valid Government-Issued ID', 'Valid Government ID', 'Valid ID', 'Requester ID', 'Claimant ID', 'Owner ID', 'Identity / Birth Record', 'Age / Date of Birth Proof', 'Identity'];
    $synthesizeRequirements = function (array $names, string $status, ?string $remarks, string $citizenFirst, ?string $idPreviewAsset = null) use ($idRequirementNames) {
        $total = count($names);
        return collect($names)->map(function ($name, $i) use ($status, $remarks, $citizenFirst, $total, $idPreviewAsset, $idRequirementNames) {
            $slug = str($name)->slug('-');
            // Every requirement the citizen was asked for gets a real
            // attached file — submitting the request on mobile means they
            // actually uploaded something for each one, whether or not an
            // admin has reviewed it yet. Only the review $reqStatus (and,
            // for the one item that actually failed, its reject reason)
            // tracks what's been checked.
            $file = "{$citizenFirst}-{$slug}.jpg";
            $reqStatus = 'Approved';
            $rejectReason = null; $reqRemarks = null;
            if ($status === 'Rejected' && $i === 0) {
                $reqStatus = 'Rejected'; $rejectReason = 'Missing information'; $reqRemarks = $remarks;
            } elseif (in_array($status, ['Submitted', 'Pending Review'], true)) {
                $reqStatus = 'Pending Review';
            } elseif ($status === 'Under Review' && $i === $total - 1) {
                $reqStatus = 'Pending Review';
            }
            $previewAsset = null;
            if ($idPreviewAsset && in_array($name, $idRequirementNames, true)) {
                $file = "{$citizenFirst}-Valid-ID.jpg";
                $previewAsset = $idPreviewAsset;
            }
            return [
                'key' => 'req_' . $slug, 'name' => $name, 'file' => $file, 'status' => $reqStatus,
                'reject_reason' => $rejectReason, 'remarks' => $reqRemarks, 'versions' => [], 'preview_asset' => $previewAsset,
            ];
        })->all();
    };
    // Full mobile-form preview — see the identical block's own comment in
    // admin/document-requests.blade.php and
    // config/esperanza_service_form_specs.php's header for the full
    // rationale; duplicated here rather than shared since this project has
    // no shared PHP helper layer, only per-file @php blocks and config.
    $formSpecs = config('esperanza_service_form_specs');
    // See the identical helper's own comment in admin/document-requests.blade.php.
    $resolveEducationOption = function (string $raw, array $options) {
        $r = strtolower($raw);
        $buckets = [
            'post grad' => 'Post Graduate',
            'vocational' => 'Vocational',
            'college' => 'College',
            'senior high' => 'High School',
            'junior high' => 'High School',
            'high school' => 'High School',
            'elementary' => 'Elementary',
            'no grade' => 'None',
        ];
        foreach ($buckets as $needle => $bucket) {
            if (str_contains($r, $needle) && in_array($bucket, $options, true)) {
                return $bucket;
            }
        }
        return null;
    };
    $synthesizeFormAnswer = function (array $field, string $ref, string $citizenFirst, string $barangay, ?array $bio = null) use ($resolveEducationOption) {
        $seed = crc32($ref . '|' . $field['key']);
        // Fields that describe the citizen themselves must agree with their
        // real Constituents profile wherever we actually know it — an
        // applicant can't be one age on their profile and a different age
        // on their own scholarship application.
        if ($bio) {
            if ($field['key'] === 'dateOfBirth' && $field['type'] === 'date' && !empty($bio['dob'])) {
                return $bio['dob'];
            }
            if ($field['key'] === 'sex' && $field['type'] === 'select' && !empty($bio['sex']) && in_array($bio['sex'], $field['options'] ?? [], true)) {
                return $bio['sex'];
            }
            if ($field['key'] === 'civilStatus' && $field['type'] === 'select' && !empty($bio['civil_status']) && in_array($bio['civil_status'], $field['options'] ?? [], true)) {
                return $bio['civil_status'];
            }
            if (in_array($field['key'], ['occupation', 'presentOccupation'], true) && $field['type'] === 'text' && !empty($bio['occupation'])) {
                return $bio['occupation'];
            }
            if ($field['key'] === 'emergencyContactName' && $field['type'] === 'text' && !empty($bio['emergency_name'])) {
                return $bio['emergency_name'];
            }
            if ($field['key'] === 'emergencyContactNumber' && $field['type'] === 'text' && !empty($bio['emergency_contact'])) {
                return $bio['emergency_contact'];
            }
            if (in_array($field['key'], ['fatherName', 'fatherFullName'], true) && $field['type'] === 'text' && !empty($bio['father_name'])) {
                return $bio['father_name'];
            }
            if ($field['key'] === 'motherName' && $field['type'] === 'text' && !empty($bio['mother_name'])) {
                return $bio['mother_name'];
            }
            if ($field['key'] === 'motherMaidenName' && $field['type'] === 'text' && !empty($bio['mother_maiden_name'])) {
                return $bio['mother_maiden_name'];
            }
            if (in_array($field['key'], ['educationalAttainment', 'highestEducationalAttainment', 'educationalAttainmentBeforeTraining'], true) && $field['type'] === 'select' && !empty($bio['education'])) {
                $resolved = $resolveEducationOption($bio['education'], $field['options'] ?? []);
                if ($resolved) return $resolved;
            }
        }
        $textPool = [
            'businessName' => ["{$citizenFirst}'s Sari-Sari Store", "{$citizenFirst}'s Trading", "{$citizenFirst}'s Enterprises"],
            'businessNature' => ['Retail / Sari-Sari Store', 'Food Stall / Carinderia', 'Buy-and-Sell Trading', 'Home-Based Tailoring'],
            'placeOfBirth' => ["Brgy. {$barangay}, Esperanza, Masbate", 'Masbate City, Masbate', 'Milagros, Masbate'],
            'schoolName' => ['Esperanza National High School', 'Masbate National Comprehensive High School', 'Naval State University'],
            'yearOrGradeLevel' => ['Grade 11', 'Grade 12', '1st Year College', '2nd Year College', '3rd Year College', '4th Year College'],
            'degreeProgramOrCourse' => ['BS Education', 'BS Business Administration', 'BS Criminology', 'BS Information Technology'],
            'lastSchoolAverageGrade' => ['88', '90', '92', '85'],
            'fatherName' => ['Ernesto ' . $citizenFirst, 'Ramon ' . $citizenFirst, 'Domingo ' . $citizenFirst],
            'fatherOccupation' => ['Farmer', 'Fisherman', 'Tricycle Driver', 'Construction Worker'],
            'motherName' => ['Corazon ' . $citizenFirst, 'Remedios ' . $citizenFirst, 'Leticia ' . $citizenFirst],
            'motherOccupation' => ['Housewife', 'Sari-Sari Store Vendor', 'Laundrywoman'],
            'communityInvolvement' => ['Active member of the school\'s Supreme Student Council.', 'Volunteers at barangay youth council activities.', 'Member of the church youth ministry.'],
            'postGraduationPlans' => ['Plans to work locally and support the family.', 'Plans to pursue a teaching career in Esperanza.', 'Plans to apply for government service after graduation.'],
            'purpose' => ["For proof of residency and identification purposes in Brgy. {$barangay}.", 'For submission as a TESDA/scholarship requirement.', 'For local employment application requirement.'],
            'familyComposition' => ["Juan {$citizenFirst} Jr., 8, Son; Maria {$citizenFirst}, 5, Daughter", "Pedro {$citizenFirst}, 12, Son"],
            'needsOrProblems' => ['Needs assistance with school supplies and daily food expenses for the children.', 'Needs support with monthly rent and utility bills.'],
            'emergencyContactName' => ['Rosario ' . $citizenFirst, 'Teresita ' . $citizenFirst, 'Bienvenido ' . $citizenFirst],
            'emergencyContactNumber' => ['0917 4' . str_pad((string) ($seed % 100000), 6, '0', STR_PAD_LEFT), '0918 3' . str_pad((string) ($seed % 100000), 6, '0', STR_PAD_LEFT)],
            'occupation' => ['Market Vendor', 'Farmer', 'Fisherman', 'Sari-Sari Store Owner', 'Tricycle Driver'],
            'presentOccupation' => ['Retired', 'Sari-Sari Store Owner', 'Farmer (Retired)', 'Housekeeper'],
            'personFullName' => [$citizenFirst . ' (as printed on record)'],
            'deceasedFullName' => [$citizenFirst . ' (as printed on record)'],
            'motherMaidenName' => ['Reyes', 'Santos', 'Villanueva', 'Bautista'],
            'citizenship' => ['Filipino'],
            'spouseName' => ['Not applicable', $citizenFirst . "'s spouse"],
            'placeOfDeath' => ["Brgy. {$barangay}, Esperanza, Masbate", 'Masbate Provincial Hospital, Masbate City'],
            'religion' => ['Roman Catholic', 'Iglesia ni Cristo', 'Born Again Christian'],
            'philsysIdNumber' => ['1234-5678-9012-' . str_pad((string) ($seed % 10000), 4, '0', STR_PAD_LEFT)],
            'lastGovtOffice' => ['Barangay Hall (Brgy. ' . $barangay . ')', 'Municipal Treasurer\'s Office', 'Not applicable'],
            'lastGovtPosition' => ['Barangay Tanod', 'Barangay Health Worker', 'Not applicable'],
            'lastGovtYear' => ['2015', '2018', '2020', 'Not applicable'],
        ];
        switch ($field['type']) {
            case 'date':
                $age = 20 + ($seed % 35);
                $year = 2026 - $age;
                $month = 1 + ($seed % 12);
                $day = 1 + ($seed % 28);
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            case 'derivedAge':
                return null;
            case 'select':
                $opts = $field['options'] ?? [];
                return $opts ? $opts[$seed % count($opts)] : null;
            case 'multiselect':
                $opts = $field['options'] ?? [];
                if (!$opts) return [];
                $total = count($opts);
                $count = 1 + ($seed % min(3, $total));
                $picked = [];
                for ($i = 0; $i < $count; $i++) {
                    $picked[] = $opts[($seed + $i * 7) % $total];
                }
                return array_values(array_unique($picked));
            case 'checkbox':
                return $seed % 2 === 0;
            case 'number':
                if (str_contains(strtolower($field['label']), 'income') || str_contains(strtolower($field['label']), 'capital')) {
                    return 3000 + ($seed % 12000);
                }
                return 1 + ($seed % 15);
            case 'textarea':
            case 'text':
            default:
                // No curated pool for this field key — an honest "Not
                // provided" placeholder (see request-form-review.blade.php)
                // beats fabricating a specific-sounding answer for a field
                // this mock dataset has no real basis for.
                $pool = $textPool[$field['key']] ?? null;
                return $pool ? $pool[$seed % count($pool)] : null;
        }
    };
    $resolveFormSpec = function (?string $type) use ($formSpecs) {
        if (!$type || !isset($formSpecs[$type])) return null;
        $spec = $formSpecs[$type];
        return $spec['alias'] ?? null ? ($formSpecs[$spec['alias']] ?? null) : $spec;
    };
    // See the identical map's own comment in admin/document-requests.blade.php.
    $idPreviewAssetByCitizen = [
        'Cristy Pareja Bonghanoy' => asset('images/esperanza/residents/cristy-id.png'),
        'Ronaldo Panganiban Bautista' => asset('images/esperanza/residents/ronaldo-id.png'),
    ];
    // See the identical map's own comment in admin/document-requests.blade.php.
    $referencePdfByType = collect(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => !empty($d['reference_pdf']))
        ->pluck('reference_pdf', 'name');
    foreach ($requests as &$r) {
        $r = array_merge($r, $tulongClassification[$r['type']] ?? ['category' => 'Social Protection', 'office' => $r['office'], 'level' => 'Municipal']);
        $r['reference_pdf'] = $referencePdfByType[$r['type']] ?? null;
        $r['assigned'] = $assignedByRef[$r['ref']] ?? null;
        $r['service_guide_key'] = $typeToServiceGuideKey[$r['type']] ?? null;
        $r['purpose'] = $r['purpose'] ?? ($purposeByCategory[$r['category']] ?? 'Social protection assistance benefit application');
        if (!isset($r['requirements'])) {
            $names = $defaultRequirementsByCategory[$r['category']] ?? ['Valid Government-Issued ID'];
            $r['requirements'] = $synthesizeRequirements($names, $r['status'], $r['remarks'] ?? null, explode(' ', $r['citizen'])[0], $idPreviewAssetByCitizen[$r['citizen']] ?? null);
        }
        $spec = $resolveFormSpec($r['type']);
        if ($spec) {
            $citizenFirst = explode(' ', $r['citizen'])[0];
            $bio = $citizenBio[$r['citizen']] ?? null;
            $r['form_source'] = $spec['source'];
            $r['form_steps'] = collect($spec['steps'])->map(function ($step) use ($synthesizeFormAnswer, $r, $citizenFirst, $bio) {
                return [
                    'label' => $step['label'],
                    'description' => $step['description'] ?? null,
                    'fields' => collect($step['fields'])->map(function ($field) use ($synthesizeFormAnswer, $r, $citizenFirst, $bio) {
                        return $field + ['answer' => $synthesizeFormAnswer($field, $r['ref'], $citizenFirst, $r['barangay'], $bio)];
                    })->all(),
                ];
            })->all();
        }
    }
    unset($r);

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Mark to Release', 'done' => 'Released'];
    $actionStatuses = ['Pending Review', 'Under Verification', 'Under Review', 'Resubmitted'];
@endphp

<x-layouts.admin title="Tulong" subtitle="Review and manage citizen assistance requests." active="assistance-requests">
    <div
        x-data="{
            tab: 'all',
            search: '',
            categoryFilter: '',
            officeFilter: '',
            levelFilter: '',
            assignedFilter: '',
            barangayFilter: '',
            showAdvancedFilters: false,
            open: false,
            selected: null,
            processing: false,
            rejectRemarks: '',
            previewDoc: null,
            requests: @js($requests),
            citizenAvatars: @js($citizenAvatars),
            actionStatuses: @js($actionStatuses),
            // Manual Document Validation & Application Decision Guide data —
            // see config/esperanza_validation_guide.php for the source.
            serviceGuides: @js($validationGuide['services']),
            standardReasons: @js($validationGuide['standard_reasons']),
            decisionOutcomes: @js($validationGuide['decision_outcomes']),
            get selectedServiceGuide() {
                return this.selected?.service_guide_key ? this.serviceGuides[this.selected.service_guide_key] : null;
            },
            // Evidence Strength Hierarchy (guide section 1.2) — a light
            // keyword heuristic since this is mock/reference data, not a
            // real document classifier.
            evidenceTier(name) {
                const n = (name || '').toLowerCase();
                if (n.includes('interview') || n.includes('case assessment') || n.includes('registry') || n.includes('source verification')) return 4;
                if (n.includes('government') || n.includes('psa') || n.includes('valid') && n.includes('id') || n.includes('medical certificate') || n.includes('hospital') || n.includes('tax declaration') || n.includes('death certificate') || n.includes('birth certificate') || n.includes('receipt')) return 3;
                if (n.includes('barangay') || n.includes('school') || n.includes('employer') || n.includes('lease') || n.includes('utility') || n.includes('clearance')) return 2;
                return 1;
            },
            insertStandardReason(label) {
                const r = this.standardReasons.find(x => x.label === label);
                if (r) this.rejectRemarks = r.message;
            },
            get flaggedRequirements() {
                return (this.selected?.requirements || []).filter(r => r.reject_reason || r.remarks);
            },
            useRequirementReasons() {
                if (!this.flaggedRequirements.length) return;
                this.rejectRemarks = this.flaggedRequirements
                    .map(r => r.name + ': ' + (r.remarks || r.reject_reason))
                    .join('\n\n');
            },
            // Full mobile form preview helpers — see
            // components/admin/request-form-review.blade.php.
            formatFieldDate(iso) {
                if (!iso) return '—';
                const [y, m, d] = iso.split('-').map(Number);
                return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            },
            derivedAgeDisplay(step, field) {
                const source = step.fields.find(f => f.key === field.derivedFromKey);
                if (!source?.answer) return '—';
                const [y] = source.answer.split('-').map(Number);
                return (2026 - y) + ' years old';
            },
            fieldIcon(field) {
                const byKey = {
                    dateOfBirth: 'calendar-days', dateOfDeath: 'calendar-days',
                    placeOfBirth: 'map-pin', placeOfDeath: 'map-pin',
                    sex: 'venus-and-mars', civilStatus: 'heart-handshake',
                    occupation: 'briefcase', presentOccupation: 'briefcase',
                    fatherOccupation: 'briefcase', motherOccupation: 'briefcase',
                    businessName: 'store', businessNature: 'tag', yearsOperating: 'history',
                    capitalAmount: 'wallet', monthlyIncome: 'wallet', annualIncome: 'wallet', parentsMonthlyIncome: 'wallet',
                    schoolName: 'school', yearOrGradeLevel: 'graduation-cap', degreeProgramOrCourse: 'book-open',
                    lastSchoolAverageGrade: 'percent', educationalAttainment: 'graduation-cap',
                    fatherName: 'user', motherName: 'user', spouseName: 'heart', personFullName: 'user',
                    deceasedFullName: 'user', motherMaidenName: 'user', emergencyContactName: 'user',
                    communityInvolvement: 'heart-handshake', postGraduationPlans: 'flag',
                    familyComposition: 'users', needsOrProblems: 'hand-heart',
                    emergencyContactNumber: 'phone', residencyType: 'home', citizenship: 'flag',
                    religion: 'landmark', philsysIdNumber: 'id-card', employmentStatus: 'briefcase',
                    lastGovtOffice: 'landmark', lastGovtPosition: 'briefcase', lastGovtYear: 'calendar',
                    isPantawidBeneficiary: 'check-square', isIndigenousPerson: 'check-square',
                    receivingPension: 'check-square', confirmFirstTime: 'check-square',
                    soloParentClassification: 'list-checks', purpose: 'clipboard-list',
                };
                if (byKey[field.key]) return byKey[field.key];
                const byType = { date: 'calendar-days', derivedAge: 'cake', select: 'list-checks', multiselect: 'list-checks', checkbox: 'check-square', number: 'hash', textarea: 'align-left', text: 'pencil-line' };
                return byType[field.type] || 'file-text';
            },
            returnToProfile: null,
            // Arrived from a resident's profile via Open in Tulong — pre-filter to
            // their name and surface a way back to exactly where they came from.
            init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                const citizen = params.get('citizen');
                if (ref || citizen) {
                    this.search = ref || citizen;
                    this.tab = 'all';
                }
                const stored = sessionStorage.getItem('returnToProfile');
                if (stored) {
                    try { this.returnToProfile = JSON.parse(stored); } catch (e) { this.returnToProfile = null; }
                    sessionStorage.removeItem('returnToProfile');
                }
                this.restoreProgress();
            },
            // Resumable verification flow — see the identical pair of
            // methods' own comment in admin/document-requests.blade.php.
            restoreProgress() {
                let saved = {};
                try { saved = JSON.parse(localStorage.getItem('esperanza_tulong_progress') || '{}'); } catch (e) { saved = {}; }
                this.requests.forEach((r) => {
                    if (saved[r.ref]) Object.assign(r, saved[r.ref]);
                });
            },
            persistProgress() {
                const data = {};
                this.requests.forEach((r) => {
                    data[r.ref] = {
                        status: r.status, remarks: r.remarks ?? null, assigned: r.assigned ?? null,
                        release_evidence: r.release_evidence ?? null, release_evidence_at: r.release_evidence_at ?? null,
                        requirements: r.requirements ?? undefined,
                    };
                });
                try { localStorage.setItem('esperanza_tulong_progress', JSON.stringify(data)); } catch (e) {}
            },
            backToProfile() {
                if (!this.returnToProfile) return;
                sessionStorage.setItem('reopenResident', JSON.stringify(this.returnToProfile));
                window.location.href = '/admin/constituents';
            },
            get categories() { return [...new Set(this.requests.map(r => r.category))].sort(); },
            get offices() { return [...new Set(this.requests.map(r => r.office))].sort(); },
            get isMunicipalScope() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal';
            },
            barangays: @js($barangays),
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Pending Review': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Mark to Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Released': ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
            },
            matchesTab(r) {
                if (this.tab === 'all') return true;
                if (this.tab === 'action') return this.actionStatuses.includes(r.status);
                if (this.tab === 'release') return r.status === 'Mark to Release';
                if (this.tab === 'done') return r.status === 'Released' || r.status === 'Completed';
                return true;
            },
            get filteredRequests() {
                const q = this.search.trim().toLowerCase();
                return this.requests.filter(r =>
                    this.$store.session.inScope(r.barangay) &&
                    this.$store.session.inDeptScope(r.office) &&
                    this.matchesTab(r) &&
                    (this.categoryFilter === '' || r.category === this.categoryFilter) &&
                    (this.officeFilter === '' || r.office === this.officeFilter) &&
                    (this.levelFilter === '' || r.level === this.levelFilter) &&
                    (this.assignedFilter === '' || (this.assignedFilter === 'unassigned' ? !r.assigned : r.assigned === this.assignedFilter)) &&
                    (this.barangayFilter === '' || r.barangay === this.barangayFilter) &&
                    (q === '' || r.ref.toLowerCase().includes(q) || r.citizen.toLowerCase().includes(q))
                );
            },
            clearAdvancedFilters() { this.categoryFilter = ''; this.officeFilter = ''; this.levelFilter = ''; this.assignedFilter = ''; this.barangayFilter = ''; },
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Pending Review': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Completed': 4, 'Rejected': 0 };
                return map[status] ?? 0;
            },
            // Opening a freshly-submitted case IS starting its review — no
            // separate 'Begin Verification' click first. A brand-new
            // Submitted/Pending Review case auto-advances to Under
            // Verification the moment an admin actually opens it; anything
            // already past that stays exactly where it is.
            openRequest(r) {
                if (['Submitted', 'Pending Review'].includes(r.status)) {
                    r.status = this.statusOrder[this.stepIndex(r.status) + 1];
                    this.persistProgress();
                }
                this.selected = r;
                this.rejectRemarks = '';
                this.previewDoc = null;
                this.open = true;
                this.$nextTick(() => window.renderIcons?.());
            },
            // Pushes a status-change notification into the citizen's
            // Notifications list (see $store.citizenNotifications in
            // resources/js/app.js) — every status transition below calls
            // this so the citizen sees each processing step, not just the
            // final outcome.
            notifyStatus(title, body, icon = 'bell', color = 'brand') {
                this.$store.citizenNotifications.push({
                    icon, color, portal: 'tulong', ref: this.selected.ref, title, body,
                });
            },
            reject() {
                if (this.processing || !this.rejectRemarks.trim()) return;
                this.processing = true;
                setTimeout(() => {
                    this.selected.status = 'Rejected';
                    this.selected.remarks = this.rejectRemarks.trim();
                    this.rejectRemarks = '';
                    this.processing = false;
                    this.persistProgress();
                    this.$dispatch('toast', { message: 'Case ' + this.selected.ref + ' rejected.', variant: 'error' });
                    this.notifyStatus('Request Rejected', 'Your ' + this.selected.type + ' request (' + this.selected.ref + ') was rejected: ' + this.selected.remarks, 'circle-x', 'rose');
                }, 700);
            },
            rejectReasons: ['Invalid document', 'Incorrect document', 'Unreadable', 'Expired', 'Wrong document', 'Missing information', 'Does not meet requirement', 'Other'],
            verifyRequirement(req) {
                req.status = 'Approved';
                req.reject_reason = null;
                req.remarks = null;
                this.persistProgress();
                this.$dispatch('toast', { message: req.name + ' verified.', variant: 'success' });
            },
            // Requirement-level decision flow covering the guide's other
            // three outcomes — Needs Additional Documents, Needs Manual
            // Verification, and Reject — each requiring a reason before it
            // can be confirmed, same as the existing whole-case reject.
            startRequirementDecision(req, outcome) {
                req._decidingOutcome = outcome;
                req._reason = '';
                req._remarks = '';
            },
            confirmRequirementDecision(req) {
                if (!req._reason) return;
                const outcome = req._decidingOutcome;
                req.versions = req.versions || [];
                req.versions.push({ file: req.file, status: outcome, date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }), reject_reason: req._reason, remarks: req._remarks || '' });
                req.status = outcome;
                req.reject_reason = req._reason;
                req.remarks = req._remarks || '';
                req._decidingOutcome = null;
                req._reason = '';
                req._remarks = '';
                // A single requirement needing more work never silently
                // rejects the whole case — it rolls the case up into
                // whichever review state that requirement now needs.
                if (this.selected.status !== 'Rejected') {
                    this.selected.status = (outcome === 'Rejected' || outcome === 'Flagged for Replacement') ? 'Under Review' : outcome;
                }
                const variant = outcome === 'Rejected' ? 'error' : outcome === 'Flagged for Replacement' ? 'warning' : 'info';
                this.persistProgress();
                this.$dispatch('toast', { message: req.name + ' marked ' + outcome + '.', variant });
            },
            // The citizen has no live re-upload flow in this simulation, so
            // this stands in for the citizen submitting a corrected file —
            // the flagged version stays visible in req.versions (pushed by
            // confirmRequirementDecision above) so the admin can still see
            // what was wrong before, per the requirement that a correction
            // never erases what was flagged.
            markRequirementResubmitted(req) {
                const ext = (req.file || '').toLowerCase().endsWith('.pdf') ? '.pdf' : '.jpg';
                req.file = req.name.replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '') + '_revised' + ext;
                req.status = 'Resubmitted';
                req.reject_reason = null;
                req.remarks = null;
                this.persistProgress();
                this.$dispatch('toast', { message: req.name + ' marked resubmitted.', variant: 'info' });
            },
            // Plain-language read on what to do next, shown in the System
            // Advice panel beside the stepper — mirrors the Esperanza Mobile
            // app's own VerificationStatusPanel pattern (icon + title +
            // explanation) so the admin isn't left to infer it from the raw
            // requirement statuses alone. Distinct advice per actual stage —
            // see the identical getter in admin/document-requests.blade.php,
            // which this now matches exactly (Tulong shares Dokyu's process-
            // tab lifecycle).
            get processAdvice() {
                if (!this.selected) return { icon: 'info', tone: 'brand', title: 'Reviewing', message: '' };
                if (this.selected.status === 'Rejected') {
                    return { icon: 'circle-x', tone: 'rose', title: 'Rejected', message: this.selected.remarks || 'This case was rejected.' };
                }
                if (this.terminalStatuses.includes(this.selected.status)) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Finalized', message: 'This case is complete — no further action is needed.' };
                }
                if (this.selected.status === 'Approved') {
                    return { icon: 'package-check', tone: 'indigo', title: 'Prepare for Release', message: 'Verification is complete. Prepare the benefit/service, then mark it Mark to Release.' };
                }
                if (this.selected.status === 'Mark to Release') {
                    return this.selected.release_evidence
                        ? { icon: 'circle-check', tone: 'emerald', title: 'Ready to Release', message: 'Proof of Release is on file. You can now release this benefit to the citizen.' }
                        : { icon: 'camera', tone: 'amber', title: 'Awaiting Proof of Release', message: 'Capture or upload Proof of Release (see the panel below) before this can be released to the citizen.' };
                }
                if (this.selected.status === 'Under Review') {
                    const flagged = (this.selected.requirements || []).filter(r => r.status === 'Flagged for Replacement').length;
                    const resubmitted = (this.selected.requirements || []).filter(r => r.status === 'Resubmitted').length;
                    if (flagged === 0 && resubmitted > 0) {
                        return { icon: 'circle-check', tone: 'sky', title: 'Ready to Approve', message: 'The citizen resubmitted corrected documents and everything now checks out. You can approve this case directly.' };
                    }
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Citizen', message: (flagged ? flagged + ' document(s) flagged for replacement — ' : '') + 'this case is waiting for the citizen to resubmit corrected documents.' };
                }
                // Under Verification — the actual document-review phase,
                // where the advice reflects whatever is concretely blocking
                // approval right now.
                const reqs = this.selected.requirements || [];
                const rejected = reqs.filter(r => r.status === 'Rejected').length;
                const pending = reqs.filter(r => r.status === 'Pending Review' || r.status === 'Under Review').length;
                const underVerif = reqs.filter(r => r.status === 'Under Verification').length;
                if (rejected > 0) {
                    return { icon: 'file-x', tone: 'rose', title: 'Action Needed', message: rejected + ' of ' + reqs.length + ' document(s) were rejected — the citizen must resubmit before this can proceed.' };
                }
                if (pending > 0) {
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Review', message: pending + ' of ' + reqs.length + ' document(s) still need review before this can be approved.' };
                }
                if (underVerif > 0) {
                    return { icon: 'search-check', tone: 'indigo', title: 'Manual Verification', message: underVerif + ' document(s) are flagged for manual verification with the issuing office or supervisor.' };
                }
                if (reqs.length && reqs.every(r => r.status === 'Approved')) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Ready to Approve', message: 'All submitted documents are verified and complete. You can proceed to the next stage.' };
                }
                return { icon: 'info', tone: 'brand', title: 'In Progress', message: 'Review the submitted application and documents below, then choose a decision.' };
            },
            // Reject is available on the first pass (Under Verification) and
            // also while a case is sitting Under Review — a citizen who
            // never corrects, or whose original submission turns out to be
            // unsalvageable, shouldn't be stuck waiting forever for a
            // resubmission that may never come.
            get canReject() {
                return this.selected && ['Under Verification', 'Under Review'].includes(this.selected.status);
            },
            // Tulong now shares Dokyu's exact process-tab lifecycle: Submitted
            // -> Under Verification -> Approved -> Mark to Release -> Released,
            // with a correction branch off Under Verification (Under Review ->
            // Resubmitted, then back into this same sequence at Approved).
            // This replaces the previous Tulong-specific Assigned/Processing/
            // Completed stages — see CLAUDE.md's Universal status system and
            // the admin Process tab workflow spec this was unified against.
            statusOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            terminalStatuses: ['Rejected', 'Released', 'Completed', 'Archived', 'Cancelled'],
            // What clicking the advance button actually does at each stage.
            // From Under Review, this button approves the case directly —
            // once every flagged requirement has been resubmitted and
            // approved at the per-requirement level, there is no further
            // whole-case hold state to pass through.
            stageActionLabels: {
                'Submitted': 'Begin Verification',
                'Pending Review': 'Begin Verification',
                'Under Verification': 'Approve Request',
                'Under Review': 'Approve Request',
                'Approved': 'Mark to Release',
                'Mark to Release': 'Release to Citizen',
            },
            get nextStageLabel() {
                if (!this.selected) return '';
                return this.stageActionLabels[this.selected.status] ?? 'Approve & Continue';
            },
            // FRONTEND SIMULATION — see the identical method's own comment in
            // admin/document-requests.blade.php for the full rationale.
            captureReleaseEvidence(source) {
                this.selected.release_evidence = this.citizenAvatars[this.selected.citizen] || `https://api.dicebear.com/9.x/personas/svg?seed=${encodeURIComponent(this.selected.citizen)}`;
                this.selected.release_evidence_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                this.persistProgress();
                this.$dispatch('toast', { message: (source === 'camera' ? 'Photo captured' : 'Photo uploaded') + ' — frontend preview only, no data is saved.', variant: 'info' });
            },
            clearReleaseEvidence() {
                this.selected.release_evidence = null;
                this.selected.release_evidence_at = null;
                this.persistProgress();
            },
            // Generic advance-to-next-stage action. From Under Review this
            // approves the case directly — see stageActionLabels above.
            approve() {
                if (this.processing) return;
                this.processing = true;
                setTimeout(() => {
                    const next = Math.min(this.stepIndex(this.selected.status) + 1, this.statusOrder.length - 1);
                    this.selected.status = this.statusOrder[next];
                    this.processing = false;
                    this.persistProgress();
                    this.$dispatch('toast', { message: this.selected.ref + ' moved to next stage: ' + this.selected.status + '.', variant: 'success' });
                    const stageNotice = {
                        'Under Verification': ['Request Under Verification', 'is now being verified.', 'search-check', 'indigo'],
                        'Approved': ['Request Approved', 'has been approved and is being prepared for release.', 'package-check', 'emerald'],
                        'Mark to Release': ['Ready for Release', 'is ready for release! Visit the office to claim it.', 'circle-check', 'teal'],
                        'Released': ['Request Released', 'has been released! Thank you!', 'check-check', 'cyan'],
                    }[this.selected.status];
                    if (stageNotice) {
                        this.notifyStatus(stageNotice[0], 'Your ' + this.selected.type + ' request (' + this.selected.ref + ') ' + stageNotice[1], stageNotice[2], stageNotice[3]);
                    }
                }, 700);
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('tulong')" x-cloak>
            <x-admin.access-restricted module="Tulong (Citizen Assistance)" icon="hand-heart" />
        </div>

        <div x-show="$store.session.can('tulong')" x-cloak>

        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mb-4 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing Tulong requests for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Profile
            </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Active Cases" value="417" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="34" icon="inbox" color="orange" sublabel="Review or assignment" :delay="40" />
            <x-ui.stat-card @click="tab = 'release'" class="cursor-pointer" label="Mark to Release" value="112" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card :href="route('admin.analytics')" label="Budget Utilized (Q3)" value="₱1.8M" icon="wallet" color="brand" sublabel="of ₱2.5M allocated" :delay="120" />
            <x-ui.stat-card :href="route('admin.reports')" label="Ayuda Received" value="₱1.8M" icon="hand-coins" color="gold" sublabel="Disbursed to citizens" :delay="160" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" x-model="search" placeholder="Search reference or citizen..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <select x-model="categoryFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0">
                    <option value="">All Categories</option>
                    <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <button
                    type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border transition-colors shrink-0"
                    :class="showAdvancedFilters || officeFilter || levelFilter || assignedFilter || barangayFilter ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
                >
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    @foreach($tabs as $key => $label)
                        <button
                            type="button" @click="tab = '{{ $key }}'"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' cases'"></p>
            </div>

            <div
                x-show="showAdvancedFilters" x-cloak
                x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100"
            >
                <select x-model="officeFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Offices</option>
                    <template x-for="o in offices" :key="o"><option :value="o" x-text="o"></option></template>
                </select>
                <select x-model="levelFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Levels</option>
                    <option value="Municipal">Municipal</option>
                    <option value="Multi-Office">Multi-Office</option>
                </select>
                <select x-show="isMunicipalScope" x-model="barangayFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <span x-show="!isMunicipalScope" x-cloak class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                    <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                </span>
                <select x-model="assignedFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">Anyone / Unassigned</option>
                    <option value="unassigned">Unassigned only</option>
                </select>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors" x-show="officeFilter || levelFilter || assignedFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Assigned</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="r in filteredRequests" :key="r.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors group cursor-pointer" @click="openRequest(r)">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500 hidden xl:table-cell" x-text="r.ref" @click.stop="openRequest(r)"></td>
                        <td class="px-4 py-3" @click.stop="openRequest(r)">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img x-show="citizenAvatars[r.citizen]" :src="citizenAvatars[r.citizen]" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" :alt="r.citizen">
                                <div x-show="!citizenAvatars[r.citizen]" class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(r.citizen || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="r.citizen"></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700" x-text="r.type"></p>
                            <p class="text-[11px] text-slate-400 mt-0.5" x-text="r.category"></p>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell">
                            <span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap" x-text="r.office"></span>
                            <span
                                class="inline-block ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap"
                                :class="{ 'bg-brand-50 text-brand-700': r.level === 'Municipal', 'bg-purple-50 text-purple-700': r.level === 'Multi-Office' }"
                                x-text="r.level"
                            ></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell"><span x-text="'Brgy. ' + r.barangay"></span></td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell">
                            <span x-show="r.assigned" x-text="r.assigned"></span>
                            <span x-show="!r.assigned" class="text-slate-300 italic">Unassigned</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden xl:table-cell" x-text="r.submitted"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[r.status] || statusStyles['Draft'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[r.status] || statusStyles['Draft'])[1]"></span>
                                <span x-text="r.status"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop>
                            <button @click="openRequest(r)" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Review</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No cases match these filters.</p>
        </div>

        <x-ui.modal title="Review Tulong Case" maxWidth="4xl">
            <template x-if="selected">
                {{-- Three isolated regions so a long case never buries the
                     stepper or the advice off-screen: the stepper (left) and
                     System Advice (right) stay pinned in place while only the
                     middle column — fields, the submitted form, and the
                     documents list — scrolls. --}}
                <div class="flex flex-col sm:flex-row gap-5 sm:h-[66vh]">

                    {{-- LEFT — Stepper, isolated (never scrolls). --}}
                    <div class="sm:w-40 shrink-0">
                        <p class="text-xs text-slate-400 mb-3 font-mono" x-text="selected.ref"></p>
                        <div class="flex flex-col">
                            @foreach(['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'] as $i => $step)
                                <div class="flex items-start gap-3">
                                    <div class="flex flex-col items-center shrink-0">
                                        <span
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold transition-all duration-[600ms] ease-standard shrink-0"
                                            :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'bg-purple-600 text-white shadow-card ring-4 ring-purple-100' : 'bg-slate-100 text-slate-400')"
                                        >
                                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="stepIndex(selected.status) > {{ $i }}" x-cloak></i>
                                            <span x-show="stepIndex(selected.status) <= {{ $i }}">{{ $i + 1 }}</span>
                                        </span>
                                        @if(!$loop->last)
                                            <div class="w-0.5 flex-1 my-1 rounded-full transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-100'" style="min-height: 1.25rem"></div>
                                        @endif
                                    </div>
                                    <span class="text-[11px] leading-tight pt-1.5 pb-2 transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) === {{ $i }} ? 'text-navy-900 font-semibold' : 'text-slate-400'">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- MIDDLE — everything about this specific case; the
                         only column that scrolls. --}}
                    <div class="flex-1 min-w-0 sm:overflow-y-auto sm:min-h-0 scrollbar-thin sm:pr-1">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="hand-heart" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assistance Type</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Handling Office</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assigned Personnel</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Submitted</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.submitted"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-2 mt-3"
                        x-show="!selected.assigned && (!$store.session.account || ['department_head', 'municipal_admin', 'super_admin'].includes($store.session.account?.roleKey))"
                    >
                        <p class="text-xs text-slate-400">This case hasn't been routed to a processor yet.</p>
                        <x-ui.button
                            variant="secondary" size="sm" icon="user-plus"
                            @click="selected.assigned = $store.session.account?.name || 'You'; $dispatch('toast', { message: 'Assigned to yourself.', variant: 'success' })"
                        >Assign to me</x-ui.button>
                    </div>

                    <x-admin.request-form-review />

                    <x-admin.validation-guide-panel
                        group="Tulong"
                        :universalChecklist="$validationGuide['universal_checklist']"
                        :evidenceHierarchy="$validationGuide['evidence_hierarchy']"
                        :mismatchRule="$validationGuide['mismatch_rule']"
                        :decisionTree="$validationGuide['decision_tree']"
                        :generalChecklist="$validationGuide['tulong_general_checklist']"
                        :adminReviewChecklist="$validationGuide['admin_review_checklist']"
                        :knownGaps="$validationGuide['known_gaps']"
                    />

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Submitted Documents</p>
                            <p class="text-[11px] text-slate-400" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="req in (selected.requirements || [])" :key="req.key">
                                <div class="rounded-xl border p-3" :class="{ 'border-emerald-200 bg-emerald-50/30': req.status === 'Approved', 'border-rose-200 bg-rose-50/30': req.status === 'Rejected', 'border-amber-200 bg-amber-50/30': req.status === 'Flagged for Replacement', 'border-sky-200 bg-sky-50/30': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Pending Review' }">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                :class="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500') : 'bg-slate-100 text-slate-300'"
                                            >
                                                <i :data-lucide="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image') : 'file-x'" class="w-4 h-4"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <span
                                                        class="text-[9px] font-bold px-1 py-0.5 rounded shrink-0"
                                                        :class="{ 1: 'bg-slate-100 text-slate-500', 2: 'bg-blue-50 text-blue-600', 3: 'bg-emerald-50 text-emerald-600', 4: 'bg-purple-50 text-purple-600' }[evidenceTier(req.name)]"
                                                        :title="'Evidence Strength: Level ' + evidenceTier(req.name)"
                                                    >L<span x-text="evidenceTier(req.name)"></span></span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="req.file || 'Not yet submitted'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            {{-- The whole point of a review queue is looking at what was
                                                 actually submitted before deciding — these buttons used to
                                                 go straight from filename text to Approve/Reject with no way
                                                 to actually open the file in between. --}}
                                            <button
                                                type="button"
                                                x-show="req.file"
                                                @click="previewDoc = req; $nextTick(() => window.renderIcons?.())"
                                                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-purple-600 hover:border-purple-300 flex items-center justify-center transition-colors"
                                                title="View submitted document"
                                            ><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Pending Review' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : req.status"></span></span>
                                        </div>
                                    </div>

                                    <div x-show="['Rejected', 'Flagged for Replacement'].includes(req.status) || (req.status === 'Resubmitted' && (req.versions || []).length)" class="mt-2 pt-2 border-t text-[11px]" :class="req.status === 'Rejected' ? 'border-rose-100/70' : req.status === 'Flagged for Replacement' ? 'border-amber-100/70' : 'border-sky-100/70'">
                                        <p class="font-semibold" x-show="req.reject_reason" :class="req.status === 'Rejected' ? 'text-rose-700' : 'text-amber-700'" x-text="req.reject_reason"></p>
                                        <p class="mt-0.5" :class="req.status === 'Rejected' ? 'text-rose-600' : 'text-amber-600'" x-show="req.remarks" x-text="req.remarks"></p>
                                        <p class="font-medium text-sky-700 flex items-center gap-1" x-show="req.status === 'Resubmitted'"><i data-lucide="refresh-ccw" class="w-3 h-3"></i> Citizen replaced this file — compare with the flagged version below.</p>
                                        <button type="button" x-show="(req.versions || []).length" @click="req._showVersions = !req._showVersions" class="mt-1.5 text-slate-400 hover:text-slate-600 transition-colors">
                                            <span x-text="(req._showVersions ? 'Hide' : 'Show') + ' version history (' + req.versions.length + ')'"></span>
                                        </button>
                                        <div x-show="req._showVersions" x-cloak class="mt-1.5 space-y-1 pl-2 border-l-2 border-slate-100">
                                            <template x-for="(v, vi) in (req.versions || [])" :key="vi">
                                                <p class="text-slate-400">Version <span x-text="vi + 1"></span> — <span x-text="v.status"></span> · <span x-text="v.date"></span><span x-show="v.reject_reason"> · <span x-text="v.reject_reason"></span></span></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Three decision outcomes at the requirement level: Approve /
                                         Flag for Replacement / Reject. No separate "needs verification"
                                         action — reviewing a requirement here already IS the
                                         verification step. --}}
                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="(req.status === 'Pending Review' || req.status === 'Resubmitted') && !req._decidingOutcome">
                                        <button type="button" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Approve</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Flagged for Replacement')" class="text-[11px] font-medium text-amber-600 hover:underline">Flag for Replacement</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Rejected')" class="text-[11px] font-medium text-rose-600 hover:underline">Reject</button>
                                    </div>

                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="req.status === 'Flagged for Replacement' && !req._decidingOutcome">
                                        <button type="button" @click="markRequirementResubmitted(req)" class="text-[11px] font-medium text-sky-600 hover:underline">Mark as Resubmitted</button>
                                    </div>

                                    <div x-show="req._decidingOutcome" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-2">
                                        <p class="text-[11px] font-semibold text-slate-500" x-text="(decisionOutcomes[req._decidingOutcome]?.label || req._decidingOutcome) + ' — ' + req.name"></p>
                                        <select x-model="req._reason" class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">Select a reason...</option>
                                            <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                                            <template x-for="reason in rejectReasons" :key="reason"><option :value="reason" x-text="reason"></option></template>
                                        </select>
                                        <textarea x-model="req._remarks" rows="2" placeholder="Remarks for the citizen (optional)..." class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button" x-bind:disabled="!req._reason" @click="confirmRequirementDecision(req)"
                                                class="text-[11px] font-medium text-white disabled:opacity-40 disabled:pointer-events-none rounded-lg px-2.5 py-1.5 transition-colors"
                                                :class="req._decidingOutcome === 'Rejected' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-500 hover:bg-amber-600'"
                                            >Confirm</button>
                                            <button type="button" @click="req._decidingOutcome = null" class="text-[11px] font-medium text-slate-400 hover:text-slate-600">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border px-3.5 py-3 mt-3 flex items-start gap-2.5"
                        :class="selected.status === 'Rejected' ? 'bg-rose-50 border-rose-100' : selected.status === 'Under Verification' ? 'bg-indigo-50 border-indigo-100' : 'bg-amber-50 border-amber-100'"
                        x-show="['Rejected', 'Under Review', 'Under Verification'].includes(selected.status) && selected.remarks"
                    >
                        <i :data-lucide="selected.status === 'Rejected' ? 'circle-x' : selected.status === 'Under Verification' ? 'search-check' : 'file-plus-2'" class="w-4 h-4 shrink-0 mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-500' : selected.status === 'Under Verification' ? 'text-indigo-500' : 'text-amber-500'"></i>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold" :class="selected.status === 'Rejected' ? 'text-rose-700' : selected.status === 'Under Verification' ? 'text-indigo-700' : 'text-amber-700'" x-text="decisionOutcomes[selected.status]?.label || selected.status"></p>
                            <p class="text-xs mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-600' : selected.status === 'Under Verification' ? 'text-indigo-600' : 'text-amber-600'" x-text="selected.remarks"></p>
                        </div>
                    </div>

                    <div class="mt-3" x-show="canReject">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Decision Remarks <span class="text-slate-400 font-normal">(required for Reject — this is what the citizen will see)</span></label>
                        <p class="text-[11px] text-slate-400 mb-1.5">This is the final message the citizen will see. If you've already flagged specific requirements below, pull those reasons in instead of retyping them.</p>
                        <div class="flex items-center gap-2 mb-1.5">
                            <select
                                @change="insertStandardReason($event.target.value); $event.target.value = ''"
                                class="flex-1 min-w-0 text-xs rounded-xl border border-slate-200 bg-white py-2 px-3 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                            >
                                <option value="">Insert a standard reason…</option>
                                <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                            </select>
                            <button
                                type="button" x-show="flaggedRequirements.length" x-cloak
                                @click="useRequirementReasons()"
                                class="shrink-0 text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 hover:border-brand-300 rounded-xl px-3 py-2 transition-colors whitespace-nowrap"
                            >Use requirement reasons</button>
                        </div>
                        <textarea
                            x-model="rejectRemarks" rows="2" placeholder="e.g. Missing Medical Abstract. Please resubmit with this requirement attached."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-3.5 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                        ></textarea>
                    </div>
                    </div>

                    {{-- RIGHT — System Advice, isolated (never scrolls). Mirrors
                         the Esperanza Mobile app's own VerificationStatusPanel
                         pattern: icon + title + plain-language explanation,
                         so the admin always has a read on what to do next
                         regardless of how far they've scrolled the middle
                         column. --}}
                    <div class="sm:w-72 shrink-0">
                        <div
                            class="rounded-2xl border p-4"
                            :class="{
                                'bg-emerald-50/60 border-emerald-200': processAdvice.tone === 'emerald',
                                'bg-rose-50/60 border-rose-200': processAdvice.tone === 'rose',
                                'bg-amber-50/60 border-amber-200': processAdvice.tone === 'amber',
                                'bg-indigo-50/60 border-indigo-200': processAdvice.tone === 'indigo',
                                'bg-purple-50/60 border-purple-200': processAdvice.tone === 'brand',
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': processAdvice.tone === 'emerald',
                                        'bg-rose-100 text-rose-700': processAdvice.tone === 'rose',
                                        'bg-amber-100 text-amber-700': processAdvice.tone === 'amber',
                                        'bg-indigo-100 text-indigo-700': processAdvice.tone === 'indigo',
                                        'bg-purple-100 text-purple-700': processAdvice.tone === 'brand',
                                    }"
                                ><i :data-lucide="processAdvice.icon" class="w-4.5 h-4.5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide"
                                        :class="{
                                            'text-emerald-700': processAdvice.tone === 'emerald',
                                            'text-rose-700': processAdvice.tone === 'rose',
                                            'text-amber-700': processAdvice.tone === 'amber',
                                            'text-indigo-700': processAdvice.tone === 'indigo',
                                            'text-purple-700': processAdvice.tone === 'brand',
                                        }"
                                    >System Advice — <span x-text="processAdvice.title"></span></p>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed" x-text="processAdvice.message"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Proof of Release — same feature as Dokyu's own
                             process modal (see admin/document-requests.blade.php's
                             identical block for the full rationale). Now
                             gated on Mark to Release, same as Dokyu, since
                             Tulong shares that same stage. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i> Proof of Release
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.release_evidence">Photo evidence that the citizen actually received this benefit/document — required before releasing.</p>

                            <template x-if="selected.release_evidence">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 aspect-[4/3]">
                                        <img :src="selected.release_evidence" class="w-full h-full object-cover" alt="Proof of release">
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[11px] text-emerald-600 font-medium inline-flex items-center gap-1"><i data-lucide="circle-check" class="w-3 h-3"></i> Captured <span x-text="selected.release_evidence_at"></span></span>
                                        <button type="button" @click="clearReleaseEvidence()" class="text-[11px] text-slate-400 hover:text-rose-500" x-show="selected.status === 'Mark to Release'">Retake</button>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-2 mt-2.5" x-show="!selected.release_evidence">
                                <button type="button" @click="captureReleaseEvidence('camera')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-purple-400 hover:bg-purple-50/40 py-3 text-slate-500 hover:text-purple-600 transition-colors">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Take Photo</span>
                                </button>
                                <button type="button" @click="captureReleaseEvidence('upload')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-purple-400 hover:bg-purple-50/40 py-3 text-slate-500 hover:text-purple-600 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Upload Photo</span>
                                </button>
                            </div>
                        </div>

                        {{-- Reference Form — same feature as Dokyu's own
                             process modal. Gated on Mark to Release, same as
                             Proof of Release above. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Reference Form
                            </p>
                            <template x-if="selected.reference_pdf">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50" style="height: 220px;">
                                        <iframe :src="selected.reference_pdf" class="w-full h-full" title="Reference form preview"></iframe>
                                    </div>
                                    <a :href="selected.reference_pdf" target="_blank" rel="noopener" class="text-[11px] text-purple-600 hover:text-purple-700 font-medium inline-flex items-center gap-1 mt-1.5">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> Open in new tab
                                    </a>
                                </div>
                            </template>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.reference_pdf">No reference form on file for this service.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button on any
                 submitted requirement above. Fixed to the viewport (not nested
                 inside the scrollable middle column) so it sits above the
                 whole Review modal regardless of scroll position. --}}
            <div
                x-show="previewDoc"
                x-cloak
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-[150ms] ease-exit"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="position: fixed; inset: 0; z-index: 95;"
                class="bg-navy-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="previewDoc = null"
                @keydown.escape.window="previewDoc = null"
            >
                <div
                    class="w-full max-w-sm bg-white rounded-2xl shadow-float overflow-hidden"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-slate-100">
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                            :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500'"
                        ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-3.5 h-3.5"></i></span>
                        <h3 class="text-sm font-semibold text-navy-900 truncate flex-1" x-text="previewDoc?.name"></h3>
                        <button type="button" @click="previewDoc = null" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors shrink-0" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        {{-- Real placeholder scan (only wired up for the handful of
                             residents with an actual supplied ID image on file, e.g.
                             Cristy — see 'preview_asset' on her requirement rows)
                             renders as an actual image; every other mock requirement
                             still falls back to the plain icon + filename, since no
                             real file exists behind those. --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/2] overflow-hidden mb-3" x-show="previewDoc?.preview_asset">
                            <img :src="previewDoc?.preview_asset" :alt="previewDoc?.name" class="w-full h-full object-cover">
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/4] flex flex-col items-center justify-center gap-2.5 mb-3" x-show="!previewDoc?.preview_asset">
                            <span
                                class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-100 text-rose-500' : 'bg-blue-100 text-blue-500'"
                            ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-7 h-7"></i></span>
                            <p class="text-xs font-medium text-slate-500 px-4 text-center truncate max-w-full" x-text="previewDoc?.file"></p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-400 shrink-0">Requirement</dt>
                                <dd class="font-medium text-navy-900 text-right truncate" x-text="previewDoc?.name"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-400 shrink-0">Status</dt>
                                <dd class="font-medium text-navy-900" x-text="previewDoc?.status === 'Approved' ? 'Verified' : previewDoc?.status"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-3" x-show="previewDoc?.remarks">
                                <dt class="text-slate-400 shrink-0">Remarks</dt>
                                <dd class="font-medium text-right text-slate-600" x-text="previewDoc?.remarks"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <p class="text-xs text-slate-400 flex-1 flex items-center gap-1.5" x-show="selected && terminalStatuses.includes(selected.status)" x-cloak>
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>This case is finalized — no further status changes.
                </p>
                <template x-if="selected && !terminalStatuses.includes(selected.status)">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <template x-if="canReject">
                            <x-ui.button variant="danger" size="sm" x-bind:disabled="processing || !rejectRemarks.trim()" @click="reject()">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                    <span x-text="processing ? 'Rejecting…' : 'Reject'"></span>
                                </span>
                            </x-ui.button>
                        </template>
                        <x-ui.button size="sm" x-bind:disabled="processing || (selected.status === 'Mark to Release' && !selected.release_evidence)" @click="approve()" x-bind:title="selected.status === 'Mark to Release' && !selected.release_evidence ? 'Capture or upload Proof of Release first' : null">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="!processing"></i>
                                <span x-text="processing ? 'Saving…' : nextStageLabel"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </template>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
