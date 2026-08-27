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
        $citizenAvatars[$ind['name']] = isset($citizenAvatarData['photoByAccountId'][$acctId])
            ? asset($citizenAvatarData['photoByAccountId'][$acctId])
            : $generatedAvatarFor($acctId);
        $citizenBio[$ind['name']] = [
            'dob' => $ind['dob'] ?? null,
            'age' => $ind['age'] ?? null,
            'sex' => $ind['sex'] ?? null,
            'civil_status' => $ind['civil_status'] ?? null,
            'occupation' => $ind['occupation'] ?? null,
            // Only populated where the Constituents profile actually has an
            // emergency contact on file — most residents don't yet, so this
            // stays null for them rather than guessing, same principle as
            // dob/sex/civil_status/occupation above. Not every citizen has
            // this filled in yet (family/household data is still sparse
            // platform-wide); update here as real family info gets added.
            'emergency_name' => $ind['emergency']['name'] ?? null,
            'emergency_contact' => $ind['emergency']['contact'] ?? null,
            // Same principle, extended to family background + education +
            // address — most residents don't have these on file yet either,
            // so they stay null (and the form falls back to the random pool)
            // until real data is added per resident.
            'father_name' => $ind['father_name'] ?? null,
            'mother_name' => $ind['mother_name'] ?? null,
            'mother_maiden_name' => $ind['mother_maiden_name'] ?? null,
            'education' => $ind['education'] ?? null,
            'address' => $ind['address'] ?? null,
        ];
    }
    // Same barangayCatalog build as citizen/document-requests.blade.php,
    // used by the Document panel's fallback resolver to look up real
    // officials by barangay name for any request that never had
    // barangayOfficials hand-authored onto it directly.
    $barangayCatalog = [];
    foreach (config('esperanza_barangay_documents') as $info) {
        $barangayCatalog[$info['label']] = ['label' => $info['label'], 'seal' => $info['seal'], 'officials' => $info['officials']];
    }
    $requests = [
        ['ref' => 'DR-2026-2231', 'citizen' => 'Perla Dionisio', 'type' => 'Barangay Residency', 'office' => 'Barangay Hall (Brgy. Labangtaytay)', 'barangay' => 'Labangtaytay', 'submitted' => 'Jul 12, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2228', 'citizen' => 'Romeo Villaflor', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Labangtaytay)', 'barangay' => 'Labangtaytay', 'submitted' => 'Jul 11, 2026', 'status' => 'Mark to Release',
            // Carries the same formSnapshot/docKey shape a citizen's own
            // wizard submission captures (see citizen/document-requests.blade.php's
            // submitRequest()) so the Process tab's own Document panel below
            // has something real to render — this request was already at
            // Mark to Release with nothing backing a "prepared document" view,
            // same gap the citizen-side Preview/Download/Print work found.
            'previewType' => 'barangay', 'docKey' => 'brgy_business_clearance', 'certTitle' => 'BARANGAY BUSINESS CLEARANCE', 'certSubtitle' => null,
            'barangayOfficials' => ['punong_barangay' => 'Hon. Jorge C. Bruza', 'barangay_secretary' => 'Jenneth A. Cachila'],
            'formSnapshot' => [
                'applicant_name' => 'Romeo Villaflor',
                'age' => '41',
                'business_type' => 'Sari-sari Store',
                'years_operating' => '3',
                'purok' => '5',
                'capital_words' => 'Fifty Thousand Pesos',
                'capital_figures' => '50,000.00',
            ],
        ],
        ['ref' => 'DR-2026-2225', 'citizen' => 'Cristina Manalo', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Labangtaytay)', 'barangay' => 'Labangtaytay', 'submitted' => 'Jul 10, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2270', 'citizen' => 'Kevin Ray Dizon', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Labangtaytay)', 'barangay' => 'Labangtaytay', 'submitted' => 'Jul 9, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2217', 'citizen' => 'Bienvenido Rosales', 'type' => 'Barangay Certification (Registration of Death)', 'office' => 'Barangay Hall (Brgy. Labangtaytay)', 'barangay' => 'Labangtaytay', 'submitted' => 'Jul 9, 2026', 'status' => 'Under Review'],
        ['ref' => 'DR-2026-2214', 'citizen' => 'Rosemarie Tan', 'type' => 'Business Permit (New)', 'office' => 'BPLO', 'barangay' => 'Baras', 'submitted' => 'Jul 10, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2210', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Cedula', 'office' => "Treasurer's Office", 'barangay' => 'Poblacion', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2201', 'citizen' => 'Elmer Bantillo', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'barangay' => 'Santiago', 'submitted' => 'Jul 7, 2026', 'status' => 'Under Review', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'One (1) valid government-issued ID', 'file' => 'Elmer-Valid-ID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_indigency', 'name' => 'Barangay Certification of Indigency', 'file' => 'Brgy-Indigency-Cert.jpg', 'status' => 'Rejected', 'reject_reason' => 'Expired', 'remarks' => 'Please upload a current barangay certification (issued within the last 30 days).', 'versions' => [
                ['file' => 'Brgy-Indigency-Cert.jpg', 'status' => 'Rejected', 'date' => 'Jul 7, 2026', 'reject_reason' => 'Expired', 'remarks' => 'Please upload a current barangay certification (issued within the last 30 days).'],
            ]],
            ['key' => 'req_interview', 'name' => 'Brief interview / case assessment with MSWDO', 'file' => 'Interview-Notes.pdf', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2187', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall', 'barangay' => 'Poblacion', 'submitted' => 'Jul 3, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'DR-2026-2154', 'citizen' => 'Corazon Villareal', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'barangay' => 'Iligan', 'submitted' => 'Jun 29, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-2098', 'citizen' => 'Teresita Salazar', 'type' => 'Senior Citizen ID', 'office' => 'OSCA', 'barangay' => 'Agoho', 'submitted' => 'Jun 22, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-1998', 'citizen' => 'Josefina Marbella', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1872', 'citizen' => 'Alvin Dagohoy', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'barangay' => 'Domorog', 'submitted' => 'May 30, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1655', 'citizen' => 'Rosemarie Tan', 'type' => 'Business Permit Renewal', 'office' => 'BPLO', 'barangay' => 'Baras', 'submitted' => 'May 12, 2026', 'status' => 'Rejected', 'remarks' => 'Missing Fire Safety Inspection Certificate. Please resubmit with this requirement attached.', 'requirements' => [
            ['key' => 'req_dti', 'name' => 'DTI or SEC Registration', 'file' => 'DTI-Registration.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_business', 'name' => 'Barangay Business Clearance', 'file' => 'Brgy-Business-Clearance.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_zoning', 'name' => 'Locational / Zoning Clearance', 'file' => 'Zoning-Clearance.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_fsic', 'name' => 'Fire Safety Inspection Certificate (FSIC)', 'file' => null, 'status' => 'Rejected', 'reject_reason' => 'Missing information', 'remarks' => 'Missing Fire Safety Inspection Certificate. Please resubmit with this requirement attached.', 'versions' => [
                ['file' => null, 'status' => 'Rejected', 'date' => 'May 12, 2026', 'reject_reason' => 'Missing information', 'remarks' => 'Missing Fire Safety Inspection Certificate. Please resubmit with this requirement attached.'],
            ]],
            ['key' => 'req_sanitary', 'name' => 'Sanitary Permit', 'file' => 'Sanitary-Permit.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_cedula', 'name' => 'Cedula (Community Tax Certificate)', 'file' => 'Cedula-2026.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_lease', 'name' => 'Lease contract or land title', 'file' => 'Lease-Contract.pdf', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-1490', 'citizen' => 'Michael Bacus', 'type' => 'Real Property Tax Clearance', 'office' => "Treasurer's Office", 'barangay' => 'Iligan', 'submitted' => 'Apr 20, 2026', 'status' => 'Completed'],
        // One sample request per remaining barangay so every Barangay Secretary
        // account (BS-004 through BS-020) has something to review — otherwise
        // most of the 20 barangay-scoped roles would land on an empty table.
        ['ref' => 'DR-2026-2240', 'citizen' => 'Joel Aranas', 'type' => 'Barangay Residency', 'office' => 'Barangay Hall (Brgy. Almero)', 'barangay' => 'Almero', 'submitted' => 'Jul 6, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2271', 'citizen' => 'Lorna Dimaano', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Guadalupe)', 'barangay' => 'Guadalupe', 'submitted' => 'Jul 5, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2242', 'citizen' => 'Divina Galvez', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Labrador)', 'barangay' => 'Labrador', 'submitted' => 'Jul 4, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'DR-2026-2243', 'citizen' => 'Ferdinand Hilario', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Libertad)', 'barangay' => 'Libertad', 'submitted' => 'Jun 25, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2244', 'citizen' => 'Analiza Ibarra', 'type' => 'Barangay Certification (Registration of Death)', 'office' => 'Barangay Hall (Brgy. Magsaysay)', 'barangay' => 'Magsaysay', 'submitted' => 'Jul 2, 2026', 'status' => 'Under Review'],
        ['ref' => 'DR-2026-2245', 'citizen' => 'Romulo Jacinto', 'type' => 'Barangay Residency', 'office' => 'Barangay Hall (Brgy. Masbaranon)', 'barangay' => 'Masbaranon', 'submitted' => 'Jul 8, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2246', 'citizen' => 'Danilo Lazaro', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Potingbato)', 'barangay' => 'Potingbato', 'submitted' => 'Jun 18, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2247', 'citizen' => 'Precious Manalo', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Rizal)', 'barangay' => 'Rizal', 'submitted' => 'Jul 9, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2272', 'citizen' => 'Ernesto Nierva', 'type' => 'Barangay Certification (Late Registration)', 'office' => 'Barangay Hall (Brgy. San Roque)', 'barangay' => 'San Roque', 'submitted' => 'Jul 6, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'DR-2026-2273', 'citizen' => 'Ramil Pascua', 'type' => 'Barangay Residency', 'office' => 'Barangay Hall (Brgy. Sorosimbajan)', 'barangay' => 'Sorosimbajan', 'submitted' => 'Jul 11, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2250', 'citizen' => 'Liezel Quimson', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Tawad)', 'barangay' => 'Tawad', 'submitted' => 'Jun 30, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2251', 'citizen' => 'Dante Robles', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Tunga)', 'barangay' => 'Tunga', 'submitted' => 'Jul 7, 2026', 'status' => 'Under Review'],
        ['ref' => 'DR-2026-2252', 'citizen' => 'Cherry Ann Sarmiento', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Villa)', 'barangay' => 'Villa', 'submitted' => 'Jul 10, 2026', 'status' => 'Mark to Release'],
        // Reconciled 1:1 with the same resident's Constituent Profile record
        // (same ref number) so "Open in Dokyu" from a profile always lands on
        // real, matching data instead of an empty search result. Ronaldo
        // Panganiban Bautista's own DR-2026-2241 (Barangay Residency) was
        // deliberately removed from here: his citizen account (ESP-RES-2024-1102,
        // config/esperanza_citizens.php) is 'Pending Review', which the mobile
        // app's own access-tier rule (CitizenSessionService.accessLevel) maps
        // to Unverified — and an Unverified account cannot reach the Dokyu tab
        // to submit a request at all. The account itself, and its Constituent
        // Profile record, are kept — only the request that could never have
        // legitimately existed was removed (see esperanza_constituents.php's
        // matching RES-2026-101 record, whose document_requests is now empty).
        ['ref' => 'DR-2026-2288', 'citizen' => 'Joel Villanueva Aranas', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall (Brgy. Almero)', 'barangay' => 'Almero', 'submitted' => 'Jul 22, 2026', 'status' => 'Processing'],
        // Reset to Submitted (rather than their normal Released/Under
        // Verification/Under Review progression) so a live demo can
        // walk through "citizen submits on mobile → admin sees and processes
        // it" from a genuinely fresh state — see the same reset on Cristy's
        // Constituent Profile record below and in esperanza_constituents.php.
        ['ref' => 'DR-2026-2233', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'One (1) Valid Government-Issued ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_business_location', 'name' => 'Proof of Business Location (Lease Contract or Land Title)', 'file' => 'Cristy-proof-of-business-location.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        // Paid via GCash (see config/esperanza_payments.php) — advanced past
        ['ref' => 'DR-2026-2255', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'One (1) Valid Government-Issued ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_proof_residency', 'name' => 'Proof of Residency', 'file' => 'Cristy-proof-of-residency.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2312', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Barangay Certification (General Purpose)', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For submission as a requirement for a local government transaction'],
        // Full-catalog demo set — one submission per remaining Dokyu service
        // type (see config/esperanza_validation_guide.php) on Cristy's own
        // account, each with real per-type eligibility feedback rather than
        // a generic "not eligible" — freshly Submitted where her actual
        // profile (25 years old, Single, Student, Brgy. Baras, no property/
        // business/disability/children of her own) plausibly qualifies, and
        // Rejected with the specific disqualifying reason (age, civil
        // status, no property, etc.) where it clearly doesn't. Certificate
        // of Fetal Death is deliberately omitted — there's no factual basis
        // in her profile for that scenario and it isn't appropriate to
        // fabricate one just for catalog completeness.
        ['ref' => 'DR-2026-2350', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Cedula', 'office' => "Treasurer's Office", 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For submission as a government transaction requirement', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Valid Government-Issued ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_income_basis', 'name' => 'Proof of Income / Certificate of Employment', 'file' => 'Cristy-proof-of-income-certificate-of-employment.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_assessment', 'name' => "Treasurer's Assessment & Payment", 'file' => 'Cristy-treasurer-s-assessment-payment.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2331', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Certificate of Residency', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For submission to a requesting government agency', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'Valid Government-Issued ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_brgy_clearance', 'name' => 'Barangay Clearance', 'file' => 'Cristy-barangay-clearance.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_proof_residency', 'name' => 'Proof of Residency (Utility Bill / Lease Contract)', 'file' => 'Cristy-proof-of-residency-utility-bill-lease-contract.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2332', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For scholarship and financial assistance application requirement', 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'Valid Government-Issued ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_brgy_indigency', 'name' => 'Barangay Indigency Certification', 'file' => 'Cristy-barangay-indigency-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_interview', 'name' => 'MSWDO Interview / Case Assessment', 'file' => 'Cristy-mswdo-interview-case-assessment.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2333', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Business Permit (New)', 'office' => 'BPLO', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'New business permit application', 'requirements' => [
            ['key' => 'req_dti', 'name' => 'DTI or SEC Registration', 'file' => 'Cristy-dti-or-sec-registration.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_business', 'name' => 'Barangay Business Clearance', 'file' => 'Cristy-barangay-business-clearance.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_zoning', 'name' => 'Locational / Zoning Clearance', 'file' => 'Cristy-locational-zoning-clearance.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_sanitary', 'name' => 'Sanitary Permit', 'file' => 'Cristy-sanitary-permit.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_cedula', 'name' => 'Cedula', 'file' => 'Cristy-cedula.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2334', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Real Property Tax Clearance', 'office' => "Treasurer's Office", 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Real property tax clearance for a property transaction', 'requirements' => [
            ['key' => 'req_tax_dec', 'name' => 'Tax Declaration', 'file' => 'Cristy-tax-declaration.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_rpt_receipt', 'name' => 'Last RPT Official Receipt', 'file' => 'Cristy-last-rpt-official-receipt.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_identity_authority', 'name' => 'Identity / Authority over Property', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
        ]],
        ['ref' => 'DR-2026-2335', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Certificate of Live Birth (Certified Copy)', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For submission as a scholarship and school enrollment requirement', 'requirements' => [
            ['key' => 'req_requester_id', 'name' => 'Requester ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_record_details', 'name' => 'Record-Locating Details', 'file' => 'Cristy-record-locating-details.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2336', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For local job application requirement', 'requirements' => [
            ['key' => 'req_identity', 'name' => 'Identity / Birth Record', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_residency', 'name' => 'Barangay Residency', 'file' => 'Cristy-barangay-residency.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_first_time', 'name' => 'First-Time Status Declaration', 'file' => 'Cristy-first-time-status-declaration.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2337', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Application for Marriage License', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Application for marriage license', 'requirements' => [
            ['key' => 'req_psa_birth_both', 'name' => 'PSA Birth Certificates (Both Parties)', 'file' => 'Cristy-psa-birth-certificates-both-parties.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_cedula_both', 'name' => 'Cedula of Both Parties', 'file' => 'Cristy-cedula-of-both-parties.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_cenomar', 'name' => 'CENOMAR', 'file' => 'Cristy-cenomar.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_photos', 'name' => 'Photos / Identity Consistency', 'file' => 'Cristy-photos-identity-consistency.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2338', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Certified Copy of Marriage Certificate', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'For submission as proof of civil status', 'requirements' => [
            ['key' => 'req_requester_id', 'name' => 'Requester ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_record_details', 'name' => 'Marriage Record Details', 'file' => 'Cristy-marriage-record-details.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2339', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Delayed Registration of Birth', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Delayed registration of birth', 'requirements' => [
            ['key' => 'req_non_registration', 'name' => 'PSA Non-Registration Certification', 'file' => 'Cristy-psa-non-registration-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_late_reg', 'name' => 'Barangay Late-Registration Certification', 'file' => 'Cristy-barangay-late-registration-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_supporting_records', 'name' => 'Supporting Early-Life Records', 'file' => 'Cristy-supporting-early-life-records.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_affidavit', 'name' => 'Affidavit of Two Disinterested Persons', 'file' => 'Cristy-affidavit-of-two-disinterested-persons.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2340', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Delayed Registration of Death', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => "Delayed registration of a deceased grandparent's death for civil registry purposes", 'requirements' => [
            ['key' => 'req_non_registration', 'name' => 'PSA Non-Registration Certification', 'file' => 'Cristy-psa-non-registration-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_death_reg', 'name' => 'Barangay Death-Registration Certification', 'file' => 'Cristy-barangay-death-registration-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_medical_records', 'name' => 'Medical / Death Records', 'file' => 'Cristy-medical-death-records.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_affidavit', 'name' => 'Affidavit of Two Disinterested Persons', 'file' => 'Cristy-affidavit-of-two-disinterested-persons.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2341', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Barangay Certification (Late Registration)', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => "Supporting certification for a family member's late birth registration", 'requirements' => [
            ['key' => 'req_valid_id', 'name' => 'Valid ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_birth_corroboration', 'name' => 'Birth Corroboration (Baptismal / School Record)', 'file' => 'Cristy-birth-corroboration-baptismal-school-record.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_verification', 'name' => 'Barangay Knowledge / Verification', 'file' => 'Cristy-barangay-knowledge-verification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2342', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Barangay Certification (Registration of Death)', 'office' => 'Barangay Hall (Brgy. Baras)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => "Barangay-level supporting certification for a deceased grandparent's death registration", 'requirements' => [
            ['key' => 'req_claimant_id', 'name' => 'Claimant ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_relationship', 'name' => 'Relationship Proof', 'file' => 'Cristy-relationship-proof.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_verification', 'name' => 'Barangay Verification', 'file' => 'Cristy-barangay-verification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2343', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Pet Registration', 'office' => 'Municipal Agriculture Office', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Registration of household pet dog for the municipal pet registry', 'requirements' => [
            ['key' => 'req_owner_id', 'name' => 'Owner ID', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_rabies_vaccination', 'name' => 'Rabies Vaccination Proof', 'file' => 'Cristy-rabies-vaccination-proof.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_pet_details', 'name' => 'Pet Details', 'file' => 'Cristy-pet-details.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2344', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Locational Clearance', 'office' => 'Municipal Planning & Development Office (MPDO)', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Locational clearance for a proposed structure', 'requirements' => [
            ['key' => 'req_property_right', 'name' => 'Property Right', 'file' => 'Cristy-property-right.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_clearance', 'name' => 'Barangay Clearance', 'file' => 'Cristy-barangay-clearance.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_vicinity_map', 'name' => 'Location / Vicinity Map', 'file' => 'Cristy-location-vicinity-map.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2345', 'citizen' => 'Cristy Pareja Bonghanoy', 'type' => 'Senior Citizen ID', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Aug 24, 2026', 'status' => 'Submitted', 'purpose' => 'Senior Citizen ID / OSCA membership application', 'requirements' => [
            ['key' => 'req_age_dob', 'name' => 'Age / Date of Birth Proof', 'file' => 'Cristy-Valid-ID.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => [], 'preview_asset' => asset('images/esperanza/residents/cristy-id.png')],
            ['key' => 'req_photo', 'name' => 'Recent ID Photo', 'file' => 'Cristy-recent-id-photo.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_brgy_certification', 'name' => 'Barangay Certification', 'file' => 'Cristy-barangay-certification.jpg', 'status' => 'Submitted', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'DR-2026-2295', 'citizen' => 'Lorna Reyes Dimaano', 'type' => 'Certificate of Widowhood', 'office' => 'Civil Registrar', 'barangay' => 'Guadalupe', 'submitted' => 'Jul 24, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-2248', 'citizen' => 'Bayani Cruz Espinosa', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Iligan)', 'barangay' => 'Iligan', 'submitted' => 'Jul 9, 2026', 'status' => 'Under Review'],
        ['ref' => 'DR-2026-2301', 'citizen' => 'Divina Torres Galvez', 'type' => 'Certificate of Good Moral Character', 'office' => 'Barangay Hall (Brgy. Labrador)', 'barangay' => 'Labrador', 'submitted' => 'Jul 8, 2026', 'status' => 'Released'],
        ['ref' => 'DR-2026-2219', 'citizen' => 'Analiza Bautista Ibarra', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'barangay' => 'Magsaysay', 'submitted' => 'Jun 15, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2309', 'citizen' => 'Grace Mendoza Kalaw', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'barangay' => 'Poblacion', 'submitted' => 'Jun 30, 2026', 'status' => 'Released'],
        ['ref' => 'DR-2026-2261', 'citizen' => 'Precious Domingo Manalo', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Rizal)', 'barangay' => 'Rizal', 'submitted' => 'Jul 16, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2277', 'citizen' => 'Ernesto Salazar Nierva', 'type' => 'Certificate of Good Moral Character', 'office' => 'Barangay Hall (Brgy. San Roque)', 'barangay' => 'San Roque', 'submitted' => 'Jul 18, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2313', 'citizen' => 'Josephine Castro Olivar', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Santiago)', 'barangay' => 'Santiago', 'submitted' => 'Jul 20, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'DR-2026-2249', 'citizen' => 'Dante Marquez Robles', 'type' => 'Barangay Certification (General Purpose)', 'office' => 'Barangay Hall (Brgy. Tunga)', 'barangay' => 'Tunga', 'submitted' => 'Jul 10, 2026', 'status' => 'Mark to Release'],
    ];

    // Manual Document Validation & Application Decision Guide — maps each
    // request's 'type' string to its checklist key in
    // config/esperanza_validation_guide.php. A handful of mock types have
    // no service defined in the LGU-supplied guide yet (e.g. Certificate of
    // Good Moral Character); those fall back to the general Dokyu checklist
    // in the validation-guide panel rather than inventing criteria the guide
    // never specified.
    $validationGuide = config('esperanza_validation_guide');
    // Payment method + receipt — same source of truth as admin/payments.blade.php
    // (config/esperanza_payments.php), keyed by ref so a request's own process
    // modal can show its payment/receipt inline instead of only on the
    // separate Payments page. Absent for any ref with no payment record
    // (nothing due yet, or the service is free) — the shared receipt
    // component itself renders nothing in that case.
    $paymentByRef = collect(config('esperanza_payments'))->keyBy('ref')->all();
    $typeToServiceGuideKey = [
        'Barangay Residency' => 'Certificate of Residency',
        'Barangay Business Clearance' => 'Barangay Business Clearance',
        'Barangay Indigency' => 'Certificate of Indigency',
        'First Time Jobseeker Certificate (RA 11261)' => 'First Time Job Seeker Certificate (RA 11261)',
        'Barangay Certification (Registration of Death)' => 'Barangay Certification for Registration of Death',
        'Business Permit (New)' => 'Business Permit (New Application)',
        'Business Permit Renewal' => 'Business Permit (New Application)',
        'Cedula' => 'Cedula (Community Tax Certificate)',
        'Certificate of Indigency' => 'Certificate of Indigency',
        'Barangay Clearance' => 'Barangay Clearance',
        'Certificate of Residency' => 'Certificate of Residency',
        'Senior Citizen ID' => 'Senior Citizen ID Application (OSCA Membership)',
        'Real Property Tax Clearance' => 'Real Property Tax Clearance',
        'Barangay Certification (Late Registration)' => 'Barangay Certification for Late Registration of Birth',
        'Barangay Certification (General Purpose)' => 'Barangay Certification (General Purpose)',
        // Guide-only services with no prior app usage — added so Cristy
        // Bonghanoy's full-catalog demo submissions (every Dokyu type, with
        // real per-type eligibility feedback) each resolve to their own
        // specific checklist rather than falling back to the general one.
        'Certificate of Live Birth (Certified Copy)' => 'Certificate of Live Birth (Certified Copy)',
        'Application for Marriage License' => 'Application for Marriage License',
        'Certified Copy of Marriage Certificate' => 'Certified Copy of Marriage Certificate',
        'Delayed Registration of Birth' => 'Delayed Registration of Birth',
        'Delayed Registration of Death' => 'Delayed Registration of Death',
        'Pet Registration' => 'Pet Registration',
        'Locational Clearance' => 'Locational Clearance',
    ];

    // Classification lookup for the mock request rows above -- keyed by the
    // exact (sometimes shortened) 'type' string already used in $requests, per
    // the Dokyu & Tulong routing plan. Kept centralized here rather than
    // hand-edited per row so every row's category/office/level stays correct
    // and traceable to one source instead of 27 separate literals.
    $dokyuClassification = [
        'Barangay Residency' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Business Clearance' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Indigency' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'First Time Jobseeker Certificate (RA 11261)' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Certification (Registration of Death)' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Certification (Late Registration)' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Clearance' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Business Permit (New)' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Business Permit Renewal' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Cedula' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Real Property Tax Clearance' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Certificate of Indigency' => ['category' => 'Social Services', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Certificate of Residency' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Senior Citizen ID' => ['category' => 'Senior Citizen / PWD', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'Certificate of Live Birth (Certified Copy)' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Application for Marriage License' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Certified Copy of Marriage Certificate' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Birth' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Death' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Pet Registration' => ['category' => 'Other Municipal Services', 'office' => 'Municipal Agriculture Office', 'level' => 'Municipal'],
        'Locational Clearance' => ['category' => 'Business & Permits', 'office' => 'Municipal Planning & Development Office (MPDO)', 'level' => 'Municipal'],
    ];
    // Assigned-personnel is only populated for requests already picked up by
    // a processor (mid-pipeline statuses) -- unassigned is the realistic
    // default for a fresh queue, not every row needs one.
    $assignedByRef = [
        'DR-2026-2210' => 'Ferdinand Cortez', 'DR-2026-2154' => 'Marivic Ong', 'DR-2026-2098' => 'Teresita Fajardo',
        'DR-2026-2228' => 'Jenneth A. Cachila', 'DR-2026-2247' => 'Precious Manalo',
    ];
    // Every request needs to open as a complete, filled-in record — the
    // purpose line and requirement documents the citizen actually submitted
    // — not just a status row. Rows without hand-authored purpose/requirements
    // (most of them) get realistic ones synthesized from their category below,
    // so "Process Document Request" never opens on an empty application.
    $purposeByCategory = [
        'Barangay Certificates' => 'For local transaction and identification purposes',
        'Business & Permits' => 'For business registration/renewal requirement',
        'Tax & Property' => 'For real property tax settlement',
        'Social Services' => 'For social welfare assistance application',
        'Civil Registry' => 'For submission to a requesting government agency',
        'Senior Citizen / PWD' => 'For special privileges and benefits application',
    ];
    $defaultRequirementsByCategory = [
        'Barangay Certificates' => ['Valid Government-Issued ID', 'Barangay Residency Verification'],
        'Business & Permits' => ['DTI/SEC Registration', 'Barangay Business Clearance', 'Valid Government-Issued ID'],
        'Tax & Property' => ['Valid Government-Issued ID', 'Proof of Property / Tax Declaration'],
        'Social Services' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Civil Registry' => ['Valid Government-Issued ID', 'Proof of Residency'],
        'Senior Citizen / PWD' => ['Valid Government-Issued ID', 'Proof of Age / Birth Certificate'],
    ];
    // Requirement names that are literally "the citizen's ID" rather than a
    // different supporting document — the only ones a real placeholder scan
    // (see $idRequirementNames use below) can honestly stand in for.
    $idRequirementNames = ['Valid Government-Issued ID', 'Valid ID', 'Valid Government ID', 'Requester ID', 'Claimant ID', 'Owner ID', 'Identity / Birth Record', 'Age / Date of Birth Proof', 'Identity'];
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
            } elseif ($status === 'Under Review' && $i === $total - 1) {
                // The specific document standing in the way of approval —
                // matches the per-requirement "Flag for Replacement"
                // vocabulary, so the checklist stays consistent with the
                // rest of the request-correction flow.
                $reqStatus = 'Flagged for Replacement'; $rejectReason = 'Missing information'; $reqRemarks = $remarks;
            } elseif (in_array($status, ['Submitted', 'Under Verification'], true)) {
                // Under Verification is the whole point of the review queue
                // — nothing has been decided on any requirement yet, so
                // Approve / Flag for Replacement / Reject need to actually
                // be available here, not silently pre-approved.
                $reqStatus = 'Submitted';
            }
            // A real supplied ID scan (currently only wired up for a couple
            // of residents with an actual photo/ID pair on file, e.g.
            // Cristy) is worth actually being able to open even before it's
            // been reviewed — unlike every other mock requirement, there's
            // a genuine file behind this one.
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
    // Full mobile-form preview — ported field-for-field from the Esperanza
    // Mobile app's own ServiceFormSpec definitions (see
    // config/esperanza_service_form_specs.php's own header comment). The
    // admin doesn't persist a citizen's literal typed answers anywhere, so
    // each field's displayed value is synthesized deterministically (same
    // ref number -> same answer, every reload) rather than re-randomized —
    // a real backend would simply store what the citizen actually typed.
    $formSpecs = config('esperanza_service_form_specs');
    // Every educationalAttainment/highestEducationalAttainment/
    // educationalAttainmentBeforeTraining field in the form specs uses the
    // same short bucket list (None/Elementary/High School/Vocational/
    // College/Post Graduate) — never the Constituents profile's own more
    // specific wording ("Senior High School Graduate", "College Graduate",
    // etc.), so an exact-match guard (like the one used for sex/civil
    // status above) would never fire. This maps a real education value to
    // whichever bucket it actually belongs in.
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
                return null; // computed client-side from the dateOfBirth answer, per ServiceFieldType.derivedAge's own contract
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
                // beats fabricating a specific-sounding answer (e.g. a
                // made-up father's name, business name, address) for a
                // field this mock dataset has no real basis for.
                $pool = $textPool[$field['key']] ?? null;
                return $pool ? $pool[$seed % count($pool)] : null;
        }
    };
    $resolveFormSpec = function (?string $type) use ($formSpecs) {
        if (!$type || !isset($formSpecs[$type])) return null;
        $spec = $formSpecs[$type];
        return $spec['alias'] ?? null ? ($formSpecs[$spec['alias']] ?? null) : $spec;
    };
    // The only residents with a real supplied ID photo on file (see
    // constituents.blade.php's own $residentIdImageAsset) — everyone else's
    // "Valid ID" requirement stays the plain mock filename, since there's
    // no real scan behind it to show.
    $idPreviewAssetByCitizen = [
        'Cristy Pareja Bonghanoy' => asset('images/esperanza/residents/cristy-id.png'),
        'Ronaldo Panganiban Bautista' => asset('images/esperanza/residents/ronaldo-id.png'),
    ];
    // Official reference-form PDF per service, for the "Reference Form" panel
    // shown once a request reaches Mark to Release (see admin/document-
    // requests.blade.php's process modal). Not every service has one on
    // file — those simply resolve to null and the panel shows a fallback.
    $referencePdfByType = collect(config('esperanza_municipal_documents.documents'))
        ->concat(config('esperanza_barangay_documents.labangtaytay.documents'))
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => !empty($d['reference_pdf']))
        ->pluck('reference_pdf', 'name')
        ->map(fn ($path) => asset($path));
    foreach ($requests as &$r) {
        $r = array_merge($r, $dokyuClassification[$r['type']] ?? ['category' => 'Other Municipal Services', 'office' => $r['office'], 'level' => 'Municipal']);
        $r['reference_pdf'] = $referencePdfByType[$r['type']] ?? null;
        $r['assigned'] = $assignedByRef[$r['ref']] ?? null;
        $r['service_guide_key'] = $typeToServiceGuideKey[$r['type']] ?? null;
        $r['purpose'] = $r['purpose'] ?? ($purposeByCategory[$r['category']] ?? 'For general legal/administrative purposes');
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

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Mark to Release', 'done' => 'Completed'];
    $actionStatuses = ['Submitted', 'Under Verification', 'Under Review', 'Resubmitted'];
    $doneStatuses = ['Completed', 'Released'];
@endphp

<x-layouts.admin title="Dokyu" subtitle="Track and process citizen document requests." active="document-requests">
    <div
        x-data="{
            view: 'requests',
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
            docPreviewOpen: false,
            // Edit Document rich-text editor state for the Prepared Document
            // modal below — see resources/js/dokyu-editor.js for the shared
            // engine and document-editor-toolbar/document-editor-background-panel
            // .blade.php for the UI. The first time a request is edited, the
            // CURRENT resolved output of whichever docKey template rendered it
            // (barangay-certificate-preview.blade.php / municipal-request-preview
            // .blade.php) is captured into selected.customDocumentHtml — a
            // plain, self-contained HTML string the admin can then freely edit
            // via contenteditable, independent of that template's own Alpine
            // bindings. Once set, the Prepared Document modal renders that
            // captured/edited HTML instead of re-invoking the template
            // component, for every future view (see the previewType-branching
            // template below) — this is what lets one generic editor work
            // across every Dokyu paper's own particular layout rather than
            // needing per-docKey editing logic.
            editingDocument: false,
            documentEditBackup: null,
            backgroundEditBackup: null,
            // Which editor side panel is open (null, background, spacing, or
            // seals) — only one at a time, toggled from the toolbar.
            activePanel: null,
            // Currently selected block for the Spacing panel below — a plain
            // DOM element reference (findSelectableBlock() in dokyu-editor.js),
            // not itself reactive; sectionSpacing mirrors its current inline
            // margin-top/margin-bottom/line-height so the panel's inputs have
            // something reactive to bind to.
            selectedSectionEl: null,
            hoveredSectionEl: null,
            sectionSpacing: { before: 0, after: 0, lineHeight: 1.0, canMoveUp: false, canMoveDown: false },
            // Free vertical offset (transform:translateY) mirroring
            // selectedSectionEl's own inline transform — a different
            // mechanism from sectionSpacing above, see setVerticalOffset()
            // in dokyu-editor.js for why margin alone cannot reliably
            // reposition a block pinned by margin-top:auto.
            sectionOffsetY: 0,
            // A small, drag-independent horizontal nudge (see setHorizontalOffset()
            // in dokyu-editor.js) — dragging itself stays vertical-only, this is
            // just the Position & Spacing panel's own X +/- control.
            sectionOffsetX: 0,
            isDraggingSection: false,
            documentOverflow: false,
            // Floating Position & Spacing inspector (document-editor-spacing-panel
            // .blade.php) — a UI-window drag, unrelated to document-section
            // dragging above; position resets to this default every time
            // editing starts fresh (not persisted beyond the current session).
            spacingPanelPos: { x: 32, y: 88 },
            spacingPanelCollapsed: false,
            sealOffsets: { left: { x: 0, y: 0 }, right: { x: 0, y: 0 } },
            // Viewer zoom (document-editor-zoom-controls.blade.php) — a
            // display-only setting, reset to 100 every time View Document
            // opens (see the View Document button below); see
            // resources/js/dokyu-editor.js for why this never affects the
            // real document, print, PDF, or the overflow check.
            zoomLevel: 100,
            defaultWatermarkSrc: @js(asset('images/esperanza/esperanza-seal.png')),
            barangayCatalog: @js($barangayCatalog),
            docTypeLookup: @js(config('esperanza_document_type_lookup')),
            // Mirrors citizen/document-requests.blade.php's own resolvedDocument/
            // documentReady/printDocument/downloadDocument — same #dp-print-area /
            // dp-printing convention (shared print CSS in app.css), so staff can
            // preview, print, or download the prepared document from the Process
            // tab itself before handing it over, not just the blank Reference
            // Form. Falls back to docTypeLookup (config/esperanza_document_type_lookup.php)
            // for any request that never had a hand-authored formSnapshot — see
            // that config file's own header comment for why almost none of the
            // seeded queue does, and what the fallback synthesizes instead.
            get resolvedDocument() {
                const r = this.selected;
                if (!r) return null;
                if (r.previewType && r.formSnapshot) {
                    return { previewType: r.previewType, docKey: r.docKey, certTitle: r.certTitle, certSubtitle: r.certSubtitle, snapshot: r.formSnapshot, barangayOfficials: r.barangayOfficials || (this.barangayCatalog[r.barangay]?.officials ?? null) };
                }
                const meta = this.docTypeLookup[r.type];
                if (!meta) return null;
                // Synthesized once per request, then cached onto the request
                // itself (r.formSnapshot) so admin edits to individual fields
                // survive this getter re-running on every reactive read —
                // without this, editing a field would appear to work for one
                // tick and then silently reset, since a plain (un-memoized)
                // getter rebuilds a brand-new snapshot object from scratch on
                // every single access.
                if (!r.formSnapshot) {
                const snapshot = {};
                if (meta.nameField) snapshot[meta.nameField] = r.citizen || '';
                if (meta.purposeField) snapshot[meta.purposeField] = meta.purposeIsArray ? (r.purpose ? [r.purpose] : []) : (r.purpose || '');
                // The citizen's real Constituents profile — the overview tab —
                // already has age/dob/sex/civil status/occupation/parents/
                // address on file for anyone with a citizen account (see
                // $citizenBio in this file's own @php block) — merge
                // whichever of those a docKey's template actually uses
                // instead of leaving them blank just because this request
                // never had its own formSnapshot. Never overwrites a field
                // the name/purpose mapping above already set.
                const bio = this.citizenBio[r.citizen];
                if (bio) {
                    const purokMatch = /Purok\s+([^,]+)/i.exec(bio.address || '');
                    const bioFields = {
                        age: bio.age, date_of_birth: bio.dob, sex: bio.sex, civil_status: bio.civil_status,
                        citizenship: 'Filipino', occupation: bio.occupation, father_name: bio.father_name,
                        mother_name: bio.mother_name, mother_maiden_name: bio.mother_maiden_name,
                        address: bio.address, residence: bio.address, purok: purokMatch ? purokMatch[1].trim() : null,
                    };
                    for (const [key, value] of Object.entries(bioFields)) {
                        if (!(key in snapshot) && value) snapshot[key] = value;
                    }
                }
                // Every field the citizen supposedly answered in the mobile
                // app's own request wizard is already synthesized onto this
                // request as r.form_steps (see $synthesizeFormAnswer in this
                // file's own @php block, sourced from config/esperanza_service_form_specs.php
                // — real Mobile Main formSpecs, not invented). That system
                // uses the mobile app's own camelCase field keys
                // (businessNature, capitalAmount, residencyType...); this
                // document's own fields use snake_case (business_type,
                // capital_figures, residency_type...) — auto-converted, plus
                // a short alias list for the handful of keys/values that
                // don't line up 1:1 after conversion (a business capital
                // amount figure vs. this doc's separate figures/words split;
                // mobile's Permanent Resident vs. this doc's plain Permanent).
                // This is what actually answers the missing-capital-field
                // report — the field exists, it just wasn't being read.
                const residencyTypeMap = { 'Permanent Resident': 'Permanent', 'Temporary Resident': 'Temporary', 'Renter / Lessee': 'Renter' };
                // These docKey fields are always rendered as a joined list
                // (.join(', ') in the templates) — coerce a single-select
                // mobile answer into a one-item array so it never crashes
                // against a template expecting .join() to exist.
                const arrayFields = ['purpose', 'type_of_disability', 'cause_of_disability', 'zoning_classification'];
                (r.form_steps || []).forEach(step => (step.fields || []).forEach(f => {
                    if (f.answer === null || f.answer === undefined || f.answer === '') return;
                    const key = f.key.replace(/([A-Z])/g, '_$1').toLowerCase();
                    if (key in snapshot) return;
                    let value = key === 'residency_type' ? (residencyTypeMap[f.answer] || f.answer) : f.answer;
                    if (arrayFields.includes(key) && !Array.isArray(value)) value = [value];
                    snapshot[key] = value;
                    if (key === 'business_nature') { snapshot.business_type = snapshot.business_type || value; snapshot.line_of_business = snapshot.line_of_business || value; }
                    if (key === 'capital_amount') { snapshot.capital_figures = snapshot.capital_figures || ('₱' + value); snapshot.capital_investment = snapshot.capital_investment || ('₱' + value); }
                }));
                // 'age' is deliberately never a stored answer on the mobile
                // side (a derivedAge field computes it live from Date of
                // Birth rather than being independently entered — see
                // $synthesizeFormAnswer's own derivedAge case) — so derive
                // it here the same way, whenever a date_of_birth made it
                // into the snapshot above but age itself didn't.
                if (!snapshot.age && snapshot.date_of_birth) {
                    const dob = new Date(snapshot.date_of_birth);
                    if (!isNaN(dob)) {
                        const today = new Date();
                        let age = today.getFullYear() - dob.getFullYear();
                        if (today.getMonth() < dob.getMonth() || (today.getMonth() === dob.getMonth() && today.getDate() < dob.getDate())) age--;
                        snapshot.age = String(age);
                    }
                }
                r.formSnapshot = snapshot;
                }
                return { previewType: meta.previewType, docKey: meta.docKey, certTitle: meta.certTitle, certSubtitle: meta.certSubtitle || null, snapshot: r.formSnapshot, barangayOfficials: this.barangayCatalog[r.barangay]?.officials ?? null };
            },
            get documentReady() {
                const r = this.selected;
                return !!(r && ['Approved', 'Mark to Release', 'Released'].includes(r.status) && this.resolvedDocument);
            },
            get defaultBackground() {
                return { src: this.defaultWatermarkSrc, opacity: 5, size: 'large', position: 'center', paperColor: '#FFFFFF' };
            },
            get documentBackground() {
                return (this.selected && this.selected.background) || this.defaultBackground;
            },
            ensureBackground() {
                if (this.selected && !this.selected.background) {
                    this.selected.background = { ...this.defaultBackground };
                }
            },
            paperStyle() {
                return window.DokyuEditor.paperStyle(this.documentBackground);
            },
            watermarkStyle() {
                return window.DokyuEditor.watermarkStyle(this.documentBackground);
            },
            startEditingDocument() {
                const doc = this.resolvedDocument;
                if (!doc || !this.selected) return;
                // First time editing this request: freeze the exact output the
                // template just rendered into a plain HTML string — see this
                // file's own comment above editingDocument for why.
                if (!this.selected.customDocumentHtml) {
                    const contentEl = document.querySelector('#dp-print-area .doc-content');
                    if (contentEl) {
                        this.selected.customDocumentHtml = window.DokyuEditor.captureContent(contentEl);
                    }
                }
                this.ensureBackground();
                this.documentEditBackup = this.selected.customDocumentHtml;
                this.backgroundEditBackup = JSON.parse(JSON.stringify(this.selected.background));
                this.editingDocument = true;
                // Prefer inline style-based formatting (e.g. a span with a
                // CSS color style) over legacy presentational tags (font
                // color, b, i) for
                // everything except fontSize, which always uses the legacy
                // <font size> mechanism regardless of this setting — see
                // applyFontSize()'s own comment in dokyu-editor.js.
                document.execCommand('styleWithCSS', false, true);
                this.$nextTick(() => {
                    window.renderIcons?.();
                    this.refreshOverflowWarning();
                    this.recomputeZoomSizer();
                });
            },
            cancelDocumentEdits() {
                if (this.selected) {
                    this.selected.customDocumentHtml = this.documentEditBackup;
                    if (this.backgroundEditBackup) this.selected.background = this.backgroundEditBackup;
                }
                this.documentEditBackup = null;
                this.backgroundEditBackup = null;
                this.editingDocument = false;
                this.resetEditorUiState();
                this.$nextTick(() => this.recomputeZoomSizer());
            },
            // Shared by Cancel, Save, and modal-close below — the x-html
            // re-render those trigger replaces .doc-content's DOM entirely,
            // so any element the Spacing panel was pointing at no longer
            // exists; clearing it here avoids the panel acting on a detached
            // node afterward.
            resetEditorUiState() {
                // Detach any handle BEFORE nulling the refs below — otherwise
                // it's orphaned in the live DOM. This matters most when the
                // modal is simply closed (docPreviewOpen goes false without
                // an x-html re-render): without this, a stray handle would
                // stay visible even in plain, non-editing View Document.
                if (this.selectedSectionEl) this.detachDragHandle(this.selectedSectionEl);
                if (this.hoveredSectionEl && this.hoveredSectionEl !== this.selectedSectionEl) this.detachDragHandle(this.hoveredSectionEl);
                this.activePanel = null;
                this.selectedSectionEl = null;
                this.hoveredSectionEl = null;
                this.isDraggingSection = false;
                this.sectionSpacing = { before: 0, after: 0, lineHeight: 1.0, canMoveUp: false, canMoveDown: false };
                this.sectionOffsetY = 0;
                this.sectionOffsetX = 0;
                this.documentOverflow = false;
                this.spacingPanelPos = { x: 32, y: 88 };
                this.spacingPanelCollapsed = false;
            },
            // Toolbar controls (document-editor-toolbar.blade.php) — every
            // command acts on the live browser selection inside .doc-content;
            // see resources/js/dokyu-editor.js for why saveSelection()/
            // restoreSelection() exist (the toolbar sits outside the
            // contenteditable region, so focusing it would otherwise collapse
            // the selection before the command runs).
            saveSelection() {
                window.DokyuEditor.saveSelection(this.$refs.docEditor);
            },
            restoreSelection() {
                window.DokyuEditor.restoreSelection();
            },
            execCmd(cmd) {
                document.execCommand(cmd);
            },
            applyFontFamily(family) {
                this.restoreSelection();
                document.execCommand('fontName', false, family);
            },
            applyFontSize(px) {
                this.restoreSelection();
                window.DokyuEditor.applyFontSize(this.$refs.docEditor, px);
            },
            applyColor(hex) {
                this.restoreSelection();
                document.execCommand('foreColor', false, hex);
            },
            resetSelectionFormatting() {
                this.restoreSelection();
                document.execCommand('removeFormat');
            },
            // Paragraph indentation — Tab/Shift+Tab (handleContentKeydown()
            // below) and the toolbar's Increase/Decrease Indent buttons both
            // call these exact same two methods, rather than the toolbar
            // staying on execCommand('indent')/('outdent') while Tab does
            // something else; see the shared increaseIndent()/decreaseIndent()
            // comment in dokyu-editor.js for why margin-left was chosen over
            // that native command.
            increaseIndentAtCaret() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                const block = window.DokyuEditor.getCurrentBlock(contentEl);
                if (!block) return;
                window.DokyuEditor.increaseIndent(block);
                if (this.selectedSectionEl === block) this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            decreaseIndentAtCaret() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                const block = window.DokyuEditor.getCurrentBlock(contentEl);
                if (!block) return;
                window.DokyuEditor.decreaseIndent(block);
                if (this.selectedSectionEl === block) this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Bound directly on .doc-content's own keydown (see the modal
            // template below), which only ever fires while that specific
            // element is focused — the guard below additionally requires
            // document.activeElement to BE .doc-content itself (not one of
            // its children, like a drag handle, which is also focusable),
            // so this only ever intercepts Tab while genuinely editing text,
            // never while the caret is merely somewhere in the document
            // for positioning purposes. Everywhere else on the page (toolbar,
            // inputs, color pickers, the floating inspector, seal controls)
            // Tab is never touched at all, since keydown never reaches this
            // handler from there in the first place.
            handleContentKeydown(event) {
                if (event.key !== 'Tab') return;
                if (document.activeElement !== this.$refs.docEditor) return;
                event.preventDefault();
                if (event.shiftKey) this.decreaseIndentAtCaret();
                else this.increaseIndentAtCaret();
            },
            // Section spacing / movement panel (document-editor-spacing-panel.blade.php)
            // — operates on whatever block the admin last clicked inside
            // .doc-content (see findSelectableBlock() in dokyu-editor.js);
            // works identically across every Dokyu paper since it is purely
            // DOM-generic, never docKey-aware.
            selectSection(event) {
                if (!this.editingDocument || !this.$refs.docEditor) return;
                const block = window.DokyuEditor.findSelectableBlock(event.target, this.$refs.docEditor);
                if (!block) {
                    this.deselectSection();
                    return;
                }
                this.selectSectionElement(block);
            },
            // Shared by selectSection() above and selectParentSection() below
            // so both go through identical hover-cleanup/class bookkeeping.
            selectSectionElement(block) {
                if (this.selectedSectionEl === block) return;
                if (this.selectedSectionEl) {
                    this.selectedSectionEl.classList.remove('doc-section-selected');
                    if (this.selectedSectionEl !== this.hoveredSectionEl) this.detachDragHandle(this.selectedSectionEl);
                }
                if (block === this.hoveredSectionEl) {
                    block.classList.remove('doc-section-hover');
                    this.hoveredSectionEl = null;
                }
                this.selectedSectionEl = block;
                block.classList.add('doc-section-selected');
                this.attachDragHandle(block);
                this.refreshSectionSpacing();
            },
            deselectSection() {
                if (this.selectedSectionEl) {
                    this.selectedSectionEl.classList.remove('doc-section-selected');
                    if (this.selectedSectionEl !== this.hoveredSectionEl) this.detachDragHandle(this.selectedSectionEl);
                }
                this.selectedSectionEl = null;
            },
            // Whether the current selection is already one of .doc-content's
            // own direct children (a major section: letterhead, title, body,
            // signatures, footer, ...) rather than a paragraph nested inside
            // one of those.
            get isMajorSection() {
                return !!(this.selectedSectionEl && this.$refs.docEditor && this.selectedSectionEl.parentElement === this.$refs.docEditor);
            },
            // Select Whole Section in the panel — walks up from whatever
            // nested text block is currently selected to the enclosing major
            // section, since a click on the text itself always selects the
            // innermost element (needed so a click still places the text
            // cursor normally) and would otherwise make the parent
            // impossible to reach.
            selectParentSection() {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                let el = this.selectedSectionEl;
                while (el.parentElement && el.parentElement !== this.$refs.docEditor) {
                    el = el.parentElement;
                }
                this.selectSectionElement(el);
            },
            // Hover affordance — mirrors selectSection()'s own targeting but
            // only ever touches one element via JS-computed delegation
            // (mouseover bubbles, so this re-fires as the pointer crosses
            // nested descendants; each call recomputes the single nearest
            // selectable block and swaps the hover class onto only that one)
            // rather than a plain CSS :hover rule, which would highlight
            // every ancestor of the pointer at once.
            hoverSection(event) {
                if (!this.editingDocument || !this.$refs.docEditor) return;
                const block = window.DokyuEditor.findSelectableBlock(event.target, this.$refs.docEditor);
                if (block === this.hoveredSectionEl) return;
                this.clearHoverSection();
                if (block && block !== this.selectedSectionEl) {
                    block.classList.add('doc-section-hover');
                    this.hoveredSectionEl = block;
                    this.attachDragHandle(block);
                }
            },
            clearHoverSection() {
                if (this.hoveredSectionEl) {
                    this.hoveredSectionEl.classList.remove('doc-section-hover');
                    if (this.hoveredSectionEl !== this.selectedSectionEl) this.detachDragHandle(this.hoveredSectionEl);
                }
                this.hoveredSectionEl = null;
            },
            // Drag handle (document-editor-spacing-panel.blade.php's own
            // comment explains why this is a separate mechanism from
            // Spacing Before/After) — attached to whatever block-level
            // element findSelectableBlock() resolved (the same one hover/
            // click selection already targets), whether that's a major
            // section (a direct child of .doc-content) or a nested piece
            // inside one (a specific name/title/label paragraph) — so an
            // inner block like Punong Barangay can be repositioned
            // independently of the section it lives in, without making
            // individual words draggable (findSelectableBlock still only
            // ever matches whole block-level elements). Stored as a plain
            // property on the element itself (el.__dragHandle) rather than
            // a separate registry — the handle's lifetime is exactly tied
            // to its block's, and this avoids leaking references if a block
            // is ever removed from the DOM some other way.
            //
            // Positioning the handle (position:absolute, left of the block)
            // requires the block itself to be a positioning context; every
            // major section already carries the `relative` class, but a
            // plain nested <p> usually doesn't, so this sets it inline the
            // first time a handle is attached — inert with no offsets of
            // its own, so it changes nothing about how the block renders.
            attachDragHandle(el) {
                if (!el || el.__dragHandle || !this.$refs.docEditor) return;
                if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
                const handle = window.DokyuEditor.createDragHandleElement();
                handle.addEventListener('pointerdown', (event) => this.onDragHandlePointerDown(event, el, handle));
                handle.addEventListener('keydown', (event) => this.onDragHandleKeyDown(event));
                el.appendChild(handle);
                el.__dragHandle = handle;
            },
            detachDragHandle(el) {
                if (el && el.__dragHandle) {
                    el.__dragHandle.remove();
                    el.__dragHandle = null;
                }
            },
            // Pointer Events (not mouse-specific handlers) so the same code
            // path covers mouse, trackpad, and pen/touch where available —
            // see requirement for touch/pointer support. setPointerCapture
            // redirects every subsequent pointermove/pointerup to the
            // handle itself regardless of where the pointer physically ends
            // up, which is the standard, reliable way to implement a drag
            // gesture without needing window-level listeners.
            onDragHandlePointerDown(event, el, handle) {
                if (!this.editingDocument) return;
                event.preventDefault();
                try { handle.setPointerCapture(event.pointerId); } catch (error) { /* not fatal: pointermove/up below still work via normal bubbling if capture is unavailable */ }
                this.selectSectionElement(el);
                window.DokyuEditor.startSectionDrag(el, event, this.$refs.docEditor, this.zoomLevel);
                this.isDraggingSection = true;
                el.classList.add('doc-section-dragging');
                const onMove = (moveEvent) => {
                    const next = window.DokyuEditor.updateSectionDrag(moveEvent);
                    if (next) {
                        this.sectionOffsetX = next.x;
                        this.sectionOffsetY = next.y;
                    }
                    this.autoScrollDuringDrag(moveEvent);
                };
                const onUp = () => {
                    handle.removeEventListener('pointermove', onMove);
                    handle.removeEventListener('pointerup', onUp);
                    handle.removeEventListener('pointercancel', onUp);
                    window.DokyuEditor.endSectionDrag();
                    this.isDraggingSection = false;
                    el.classList.remove('doc-section-dragging');
                    this.refreshOverflowWarning();
                };
                handle.addEventListener('pointermove', onMove);
                handle.addEventListener('pointerup', onUp);
                handle.addEventListener('pointercancel', onUp);
            },
            // Fine keyboard nudging after a drag — deliberately scoped to
            // when the handle itself has DOM focus (a deliberate click/tab
            // onto it), never globally while a section is merely selected,
            // so arrow keys keep their normal caret-movement meaning
            // everywhere else, including while typing in the very same
            // section's own text.
            onDragHandleKeyDown(event) {
                if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const step = event.shiftKey ? 10 : 1;
                if (event.key === 'ArrowUp') this.adjustSectionOffsetY(-step);
                else if (event.key === 'ArrowDown') this.adjustSectionOffsetY(step);
                else if (event.key === 'ArrowLeft') this.adjustSectionOffsetX(-step);
                else if (event.key === 'ArrowRight') this.adjustSectionOffsetX(step);
            },
            // Auto-scrolls the zoom viewport when dragging near its top/bottom
            // edge, so a section can be moved farther than what's currently
            // visible — capped low (12px per pointermove) so it never feels
            // like it's racing away from the pointer.
            autoScrollDuringDrag(pointerEvent) {
                const viewport = this.$refs.zoomViewport;
                if (!viewport) return;
                const rect = viewport.getBoundingClientRect();
                const edgeZone = 40;
                const maxSpeed = 12;
                if (pointerEvent.clientY < rect.top + edgeZone) {
                    viewport.scrollTop -= maxSpeed * ((rect.top + edgeZone - pointerEvent.clientY) / edgeZone);
                } else if (pointerEvent.clientY > rect.bottom - edgeZone) {
                    viewport.scrollTop += maxSpeed * ((pointerEvent.clientY - (rect.bottom - edgeZone)) / edgeZone);
                }
            },
            refreshSectionSpacing() {
                if (!this.selectedSectionEl) return;
                const spacing = window.DokyuEditor.readSpacing(this.selectedSectionEl);
                const move = window.DokyuEditor.canMove(this.selectedSectionEl);
                this.sectionSpacing = { before: spacing.before, after: spacing.after, lineHeight: spacing.lineHeight, canMoveUp: move.up, canMoveDown: move.down };
                this.sectionOffsetY = window.DokyuEditor.readVerticalOffset(this.selectedSectionEl);
                this.sectionOffsetX = window.DokyuEditor.readHorizontalOffset(this.selectedSectionEl);
            },
            // A short, human-readable hint for the floating panel's own
            // Selected line — every Dokyu paper has different named
            // pieces (Punong Barangay, an officiant, a payment table row...)
            // with no shared data attribute to hang a label off, so this
            // just previews the block's own text rather than requiring every
            // template to be annotated by hand for this one purpose. Reads
            // from a clone with the drag handle stripped out first — the
            // handle is a child of the very element being labeled, so its
            // own glyph would otherwise get pulled into textContent too.
            get selectedSectionLabel() {
                if (!this.selectedSectionEl) return '';
                const clone = this.selectedSectionEl.cloneNode(true);
                clone.querySelectorAll('.doc-drag-handle').forEach((node) => node.remove());
                const text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
                if (!text) return this.isMajorSection ? 'Section' : 'Block';
                return text.length > 42 ? text.slice(0, 42) + '…' : text;
            },
            adjustSectionOffsetX(delta) {
                if (!this.selectedSectionEl) return;
                this.setSectionOffsetXDirect(this.sectionOffsetX + delta);
            },
            setSectionOffsetXDirect(value) {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                const bounds = window.DokyuEditor.computeHorizontalBounds(this.selectedSectionEl, this.$refs.docEditor, this.zoomLevel / 100);
                const clamped = Math.max(bounds.min, Math.min(bounds.max, Math.round(Number(value) || 0)));
                window.DokyuEditor.setHorizontalOffset(this.selectedSectionEl, clamped);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustSpacingBefore(delta) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setSpacingBefore(this.selectedSectionEl, this.sectionSpacing.before + delta);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustSpacingAfter(delta) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setSpacingAfter(this.selectedSectionEl, this.sectionSpacing.after + delta);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustLineHeight(step) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setLineHeight(this.selectedSectionEl, this.sectionSpacing.lineHeight + step);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Free vertical repositioning — see setVerticalOffset()'s own
            // comment in dokyu-editor.js for why this exists as a separate
            // mechanism from Spacing Before/After above. Dragging (see
            // onDragHandlePointerDown()) is the primary way to use this now;
            // these numeric/button/keyboard paths are the fine-tuning
            // fallback, so all of them (including the drag itself) share
            // the exact same computeOffsetBounds() clamp — a section can
            // never be pushed out of the printable area through any control.
            adjustSectionOffsetY(delta) {
                if (!this.selectedSectionEl) return;
                this.setSectionOffsetYDirect(this.sectionOffsetY + delta);
            },
            setSectionOffsetYDirect(value) {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                const bounds = window.DokyuEditor.computeOffsetBounds(this.selectedSectionEl, this.$refs.docEditor, this.zoomLevel / 100);
                const clamped = Math.max(bounds.min, Math.min(bounds.max, Math.round(Number(value) || 0)));
                window.DokyuEditor.setVerticalOffset(this.selectedSectionEl, clamped);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            moveSelectedSection(direction) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.moveElement(this.selectedSectionEl, direction);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            resetSectionSpacing() {
                if (!this.selectedSectionEl) return;
                this.selectedSectionEl.style.marginTop = '';
                this.selectedSectionEl.style.marginBottom = '';
                this.selectedSectionEl.style.lineHeight = '';
                this.selectedSectionEl.style.transform = '';
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Dragging the floating Position & Spacing window itself (by its
            // title bar) — a plain screen-space UI drag, unrelated to
            // dragging a document section: no zoom conversion, no A4
            // boundary clamping, since this window floats above the paper
            // (position:fixed) and never affects the document at all. Plain
            // window-level listeners are enough here (no pointer capture
            // needed) since there's no hit-testing precision to protect.
            startPanelDrag(event) {
                if (event.target.closest('button')) return;
                event.preventDefault();
                const startX = event.clientX;
                const startY = event.clientY;
                const originX = this.spacingPanelPos.x;
                const originY = this.spacingPanelPos.y;
                const onMove = (moveEvent) => {
                    this.spacingPanelPos = { x: originX + (moveEvent.clientX - startX), y: originY + (moveEvent.clientY - startY) };
                };
                const onUp = () => {
                    window.removeEventListener('pointermove', onMove);
                    window.removeEventListener('pointerup', onUp);
                };
                window.addEventListener('pointermove', onMove);
                window.addEventListener('pointerup', onUp);
            },
            refreshOverflowWarning() {
                const paperEl = document.querySelector('#dp-print-area .doc-paper');
                this.documentOverflow = window.DokyuEditor.checkOverflow(paperEl);
            },
            // Viewer zoom (document-editor-zoom-controls.blade.php) — a pure
            // display feature; see the header comment on the zoom exports in
            // resources/js/dokyu-editor.js for why transform:scale() on
            // .doc-zoom-scale never affects the real document, the overflow
            // check above, print, or PDF. .doc-zoom-sizer's explicit
            // width/height (recomputeZoomSizer()) is what makes
            // .doc-zoom-viewport scrollable to the full scaled size, since a
            // transformed element never grows its own ancestors' scrollable
            // area by itself.
            setZoomLevel(level) {
                this.zoomLevel = window.DokyuEditor.clampZoom(level);
                this.$nextTick(() => this.recomputeZoomSizer());
            },
            zoomIn() {
                this.setZoomLevel(window.DokyuEditor.nextZoomLevel(this.zoomLevel, 'in'));
            },
            zoomOut() {
                this.setZoomLevel(window.DokyuEditor.nextZoomLevel(this.zoomLevel, 'out'));
            },
            resetZoom() {
                this.setZoomLevel(100);
            },
            fitPage() {
                const viewportEl = this.$refs.zoomViewport;
                const scaleEl = this.$refs.zoomScale;
                if (!viewportEl || !scaleEl) return;
                this.setZoomLevel(window.DokyuEditor.computeFitZoom(scaleEl, viewportEl.clientWidth, viewportEl.clientHeight));
            },
            recomputeZoomSizer() {
                const scaleEl = this.$refs.zoomScale;
                const sizerEl = this.$refs.zoomSizer;
                if (!scaleEl || !sizerEl) return;
                const size = window.DokyuEditor.computeZoomSizerSize(scaleEl, this.zoomLevel);
                sizerEl.style.width = size.width + 'px';
                sizerEl.style.height = size.height + 'px';
            },
            handleZoomShortcut(event) {
                if (!this.docPreviewOpen) return;
                if (!(event.ctrlKey || event.metaKey)) return;
                if (event.key === '+' || event.key === '=') {
                    event.preventDefault();
                    this.zoomIn();
                } else if (event.key === '-') {
                    event.preventDefault();
                    this.zoomOut();
                } else if (event.key === '0') {
                    event.preventDefault();
                    this.resetZoom();
                }
            },
            // Header seal panel (document-editor-header-seal-panel.blade.php)
            // — targets the header-seal--left / header-seal--right markers
            // every template renders (see barangay-certificate-preview.blade.php
            // / municipal-request-preview.blade.php); municipal papers only
            // render a left seal, so hasRightSeal() lets the panel hide the
            // Right Seal block when there is nothing to control.
            findSealImg(side) {
                const root = document.querySelector('#dp-print-area .doc-content');
                return root ? root.querySelector('.header-seal--' + side) : null;
            },
            hasRightSeal() {
                return !!this.findSealImg('right');
            },
            refreshSealOffsets() {
                ['left', 'right'].forEach((side) => {
                    const img = this.findSealImg(side);
                    this.sealOffsets[side] = img ? window.DokyuEditor.readSealOffset(img) : { x: 0, y: 0 };
                });
            },
            async handleSealUpload(side, event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const img = this.findSealImg(side);
                if (!img) return;
                const result = await window.DokyuEditor.readImageFile(file);
                if (!result.ok) {
                    this.$dispatch('toast', { message: result.error, variant: 'danger' });
                    return;
                }
                img.src = result.dataUrl;
                img.style.display = '';
            },
            removeSeal(side) {
                const img = this.findSealImg(side);
                if (img) img.style.display = 'none';
            },
            restoreSeal(side) {
                const img = this.findSealImg(side);
                if (!img) return;
                img.style.display = '';
                img.src = img.getAttribute('data-default-src') || img.src;
                img.style.width = '';
                img.style.height = '';
                img.style.transform = '';
                this.refreshSealOffsets();
            },
            setSealSize(side, sizeKey) {
                const img = this.findSealImg(side);
                if (img) window.DokyuEditor.applySealSize(img, sizeKey);
            },
            adjustSealOffset(side, axis, delta) {
                const img = this.findSealImg(side);
                if (!img) return;
                const current = window.DokyuEditor.readSealOffset(img);
                const next = { x: current.x, y: current.y };
                next[axis] = current[axis] + delta;
                window.DokyuEditor.applySealOffset(img, next.x, next.y);
                this.refreshSealOffsets();
            },
            // Change Background panel (document-editor-background-panel.blade.php)
            setWatermarkOpacity(v) {
                this.ensureBackground();
                this.selected.background.opacity = Number(v);
            },
            setWatermarkSize(v) {
                this.ensureBackground();
                this.selected.background.size = v;
            },
            setWatermarkPosition(v) {
                this.ensureBackground();
                this.selected.background.position = v;
            },
            setPaperColor(hex) {
                this.ensureBackground();
                this.selected.background.paperColor = hex;
            },
            resetPaperColor() {
                this.ensureBackground();
                this.selected.background.paperColor = '#FFFFFF';
            },
            removeWatermark() {
                this.ensureBackground();
                this.selected.background.src = null;
            },
            restoreDefaultWatermark() {
                this.ensureBackground();
                this.selected.background.src = this.defaultWatermarkSrc;
            },
            resetBackground() {
                this.selected.background = { ...this.defaultBackground };
            },
            async handleWatermarkUpload(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const result = await window.DokyuEditor.readImageFile(file);
                if (!result.ok) {
                    this.$dispatch('toast', { message: result.error, variant: 'danger' });
                    return;
                }
                this.ensureBackground();
                this.selected.background.src = result.dataUrl;
            },
            saveDocumentEdits() {
                if (this.$refs.docEditor) {
                    this.selected.customDocumentHtml = window.DokyuEditor.cleanHtml(this.$refs.docEditor.innerHTML);
                }
                this.documentEditBackup = null;
                this.backgroundEditBackup = null;
                this.editingDocument = false;
                this.resetEditorUiState();
                this.$dispatch('toast', { message: 'Document changes saved.', variant: 'success' });
                this.$nextTick(() => this.recomputeZoomSizer());
            },
            printDocument() {
                if (this.editingDocument) this.saveDocumentEdits();
                document.body.classList.add('dp-printing');
                window.print();
            },
            async downloadDocument() {
                if (this.editingDocument) this.saveDocumentEdits();
                const el = document.getElementById('dp-print-area');
                if (!el) return;
                if (!window.html2pdf) {
                    this.$dispatch('toast', { message: 'PDF generator is still loading. Please try again.', variant: 'warning' });
                    return;
                }
                const citizenName = (this.selected?.citizen || 'Citizen').replace(/[^a-z0-9]+/gi, '-');
                const docName = (this.resolvedDocument?.certTitle || this.selected?.type || 'Document').replace(/[^a-z0-9]+/gi, '-');
                document.body.classList.add('dp-printing');
                await this.$nextTick();
                try {
                    await window.html2pdf().set({
                        margin: 0,
                        filename: 'Esperanza-' + docName + '-' + citizenName + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'], avoid: ['.doc-paper'] },
                    }).from(el).save();
                } catch (error) {
                    console.error('Prepared document PDF download failed:', error);
                    this.$dispatch('toast', { message: 'Unable to download the PDF. Please try Print and choose Save as PDF.', variant: 'danger' });
                } finally {
                    document.body.classList.remove('dp-printing');
                }
            },
            requests: @js($requests),
            citizenAvatars: @js($citizenAvatars),
            citizenBio: @js($citizenBio),
            actionStatuses: @js($actionStatuses),
            doneStatuses: @js($doneStatuses),
            paymentByRef: @js($paymentByRef),
            get selectedPayment() {
                return this.selected?.ref ? this.paymentByRef[this.selected.ref] : null;
            },
            standardReasons: @js($validationGuide['standard_reasons']),
            decisionOutcomes: @js($validationGuide['decision_outcomes']),
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
            // Arriving from a resident's profile via Open in Dokyu — pre-filter to
            // their name and surface a way back to exactly where they came from.
            init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                const citizen = params.get('citizen');
                if (ref || citizen) {
                    this.search = ref || citizen;
                    this.view = 'requests';
                    this.tab = 'all';
                }
                const stored = sessionStorage.getItem('returnToProfile');
                if (stored) {
                    try { this.returnToProfile = JSON.parse(stored); } catch (e) { this.returnToProfile = null; }
                    sessionStorage.removeItem('returnToProfile');
                }
                this.restoreProgress();
                // Arriving from a notification (see components/admin/topbar.blade.php)
                // — jump straight into that exact request, not just the filtered list.
                if (ref) {
                    const match = this.requests.find(r => r.ref === ref);
                    if (match) this.$nextTick(() => this.openRequest(match));
                }
                // Closing the process modal is the signal that time has
                // passed in this simulation — see resolvePendingResubmissions.
                this.$watch('open', (isOpen) => { if (!isOpen) this.resolvePendingResubmissions(); });
            },
            // Verification is a resumable flow, not a single sitting — an
            // admin who closes the tab mid-review (or mid-multi-step
            // requirement decision) comes back to exactly the stage/status
            // they left, not a reset request. FRONTEND SIMULATION ONLY:
            // localStorage stands in for what a real backend would persist
            // server-side; this project has no database. Keyed per-ref so
            // it survives the mock $requests array being edited/extended
            // between sessions — only the progress fields below get
            // overlaid onto whatever the current mock data defines, never
            // the whole record.
            restoreProgress() {
                let saved = {};
                try { saved = JSON.parse(localStorage.getItem('esperanza_dokyu_progress') || '{}'); } catch (e) { saved = {}; }
                this.requests.forEach((r) => {
                    if (saved[r.ref]) Object.assign(r, saved[r.ref]);
                });
            },
            // Escape hatch for stale saved progress — every status change,
            // flag, or resubmission an admin makes gets written to
            // localStorage (see persistProgress) so review work survives a
            // reload. That is normally exactly what you want, but it also
            // means old demo interactions never age out on their own and
            // can end up showing status labels or documents that no longer
            // match the current mock data. This clears the saved progress
            // for both Dokyu and Tulong and reloads clean.
            resetDemoData() {
                localStorage.removeItem('esperanza_dokyu_progress');
                localStorage.removeItem('esperanza_tulong_progress');
                window.location.href = window.location.pathname;
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
                try { localStorage.setItem('esperanza_dokyu_progress', JSON.stringify(data)); } catch (e) {}
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
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Processing': ['bg-brand-50 text-brand-700 ring-brand-200', 'bg-brand-500'],
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
                if (this.tab === 'done') return this.doneStatuses.includes(r.status);
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
                const map = { 'Submitted': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Completed': 4, 'Rejected': 0 };
                return map[status] ?? 0;
            },
            // Opening a freshly-submitted request IS starting its review —
            // no separate 'Begin Verification' click first. A brand-new
            // Submitted request auto-advances to Under Verification the
            // moment an admin actually opens it; anything already past
            // that stays exactly where it is.
            openRequest(r) {
                if (r.status === 'Submitted') {
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
                    icon, color, portal: 'dokyu', ref: this.selected.ref, title, body,
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
                    this.$dispatch('toast', { message: 'Request ' + this.selected.ref + ' rejected.', variant: 'error' });
                    this.notifyStatus('Request Rejected', 'Your ' + this.selected.type + ' request (' + this.selected.ref + ') was rejected: ' + this.selected.remarks, 'circle-x', 'rose');
                }, 700);
            },
            rejectReasons: ['Invalid document', 'Incorrect document', 'Unreadable', 'Expired', 'Wrong document', 'Missing information', 'Does not meet requirement', 'Other'],
            // Plain-language read on what to do next, shown in the System
            // Advice panel beside the stepper — mirrors the Esperanza Mobile
            // app's own VerificationStatusPanel pattern (icon + title +
            // explanation) so the admin isn't left to infer it from the raw
            // requirement statuses alone.
            // Distinct advice per actual stage — not just per document-review
            // outcome. Once all requirements clear Approved, the request still
            // has to move through Approved -> Mark to Release -> Released,
            // and each of those calls for a genuinely different next action
            // (prepare the document, capture Proof of Release, hand it over),
            // so the panel keeps saying something new instead of repeating
            // 'Ready to Approve' all the way to the end.
            get processAdvice() {
                if (!this.selected) return { icon: 'info', tone: 'brand', title: 'Reviewing', message: '' };
                if (this.selected.status === 'Rejected') {
                    return { icon: 'circle-x', tone: 'rose', title: 'Rejected', message: this.selected.remarks || 'This request was rejected.' };
                }
                if (this.terminalStatuses.includes(this.selected.status)) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Finalized', message: 'This request is complete — no further action is needed.' };
                }
                if (this.selected.status === 'Approved') {
                    return { icon: 'package-check', tone: 'indigo', title: 'Prepare Document', message: 'Verification is complete. Prepare the requested document, then mark it Mark to Release.' };
                }
                if (this.selected.status === 'Mark to Release') {
                    return this.selected.release_evidence
                        ? { icon: 'circle-check', tone: 'emerald', title: 'Ready to Release', message: 'Proof of Release is on file. You can now release this document to the citizen.' }
                        : { icon: 'camera', tone: 'amber', title: 'Awaiting Proof of Release', message: 'Capture or upload Proof of Release (see the panel below) before this can be released to the citizen.' };
                }
                if (this.selected.status === 'Under Review') {
                    const flagged = (this.selected.requirements || []).filter(r => r.status === 'Flagged for Replacement').length;
                    const resubmitted = (this.selected.requirements || []).filter(r => r.status === 'Resubmitted').length;
                    if (flagged === 0 && resubmitted > 0) {
                        return { icon: 'circle-check', tone: 'sky', title: 'Ready to Approve', message: 'The citizen resubmitted corrected documents and everything now checks out. You can approve this request directly.' };
                    }
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Citizen', message: (flagged ? flagged + ' document(s) flagged for replacement — ' : '') + 'this request is waiting for the citizen to resubmit corrected documents.' };
                }
                // Under Verification — the actual document-review phase,
                // where the advice reflects whatever is concretely blocking
                // approval right now.
                const reqs = this.selected.requirements || [];
                const rejected = reqs.filter(r => r.status === 'Rejected').length;
                const pending = reqs.filter(r => r.status === 'Submitted' || r.status === 'Under Review').length;
                if (rejected > 0) {
                    return { icon: 'file-x', tone: 'rose', title: 'Action Needed', message: rejected + ' of ' + reqs.length + ' document(s) were rejected — the citizen must resubmit before this can proceed.' };
                }
                if (pending > 0) {
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Review', message: pending + ' of ' + reqs.length + ' document(s) still need review before this can be approved.' };
                }
                if (reqs.length && reqs.every(r => r.status === 'Approved')) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Ready to Approve', message: 'All submitted documents are verified and complete. You can proceed to the next stage.' };
                }
                return { icon: 'info', tone: 'brand', title: 'In Progress', message: 'Review the submitted application and documents below, then choose a decision.' };
            },
            // Reject is available on the first pass (Under Verification) and
            // also while a request is sitting Under Review — a citizen who
            // never corrects, or whose original submission turns out to be
            // unsalvageable, shouldn't be stuck waiting forever for a
            // resubmission that may never come.
            get canReject() {
                return this.selected && ['Under Verification', 'Under Review'].includes(this.selected.status);
            },
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
            // can be confirmed, same as the existing whole-request reject.
            startRequirementDecision(req, outcome) {
                req._decidingOutcome = outcome;
                req._reason = '';
                req._remarks = '';
            },
            // Flagging a requirement stays flagged while this modal is open
            // — the citizen resubmitting only happens once you leave (see
            // resolvePendingResubmissions, fired by the open-state watcher
            // in init()). Without that delay, flagging and the citizen
            // fixing it would appear to happen in the same instant, which
            // reads as if the citizen were watching the admin's screen.
            confirmRequirementDecision(req) {
                if (!req._reason) return;
                const outcome = req._decidingOutcome;
                req.versions = req.versions || [];
                req.versions.push({ file: req.file, status: outcome, date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }), reject_reason: req._reason, remarks: req._remarks || '' });
                req.status = outcome;
                req.reject_reason = req._reason;
                req.remarks = req._remarks || '';
                req._pendingResubmit = outcome === 'Flagged for Replacement';
                req._decidingOutcome = null;
                req._reason = '';
                req._remarks = '';
                // A single requirement needing more work never silently
                // rejects the whole request — it rolls the request up into
                // whichever review state that requirement now needs.
                if (this.selected.status !== 'Rejected') {
                    this.selected.status = (outcome === 'Rejected' || outcome === 'Flagged for Replacement') ? 'Under Review' : outcome;
                }
                const variant = outcome === 'Rejected' ? 'error' : outcome === 'Flagged for Replacement' ? 'warning' : 'info';
                this.persistProgress();
                this.$dispatch('toast', { message: req.name + ' marked ' + outcome + '.', variant });
            },
            // Resolves any requirement flagged during this viewing into
            // Resubmitted — called when the process modal closes (see the
            // $watch('open', ...) in init()), so the citizen's correction
            // only becomes visible on the NEXT time this request is opened,
            // not instantly while the admin is still looking at it.
            resolvePendingResubmissions() {
                if (!this.selected) return;
                const request = this.selected;
                let changed = false;
                (request.requirements || []).forEach((req) => {
                    if (!req._pendingResubmit) return;
                    changed = true;
                    const ext = (req.file || '').toLowerCase().endsWith('.pdf') ? '.pdf' : '.jpg';
                    req.file = req.name.replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '') + '_revised' + ext;
                    req.status = 'Resubmitted';
                    req.reject_reason = null;
                    req.remarks = null;
                    req._pendingResubmit = false;
                    this.$store.adminNotifications.push({
                        icon: 'refresh-ccw', color: 'sky', portal: 'dokyu', ref: request.ref,
                        title: request.citizen + ' Resubmitted ' + req.name,
                        body: request.citizen + ' replaced ' + req.name + ' for the ' + request.type + ' request (' + request.ref + ').',
                    });
                });
                if (changed) this.persistProgress();
            },
            statusOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            terminalStatuses: ['Rejected', 'Released', 'Completed', 'Archived', 'Cancelled'],
            // What clicking the advance button actually does at each stage:
            // Submitted -> Under Verification -> Approved -> Mark to Release
            // -> Released, with a correction branch off Under Verification
            // (Under Review, where the per-requirement panel is what the
            // admin actually works from — once every flagged item is
            // resubmitted and approved there, this same button approves the
            // whole request directly, no separate resubmitted hold state).
            stageActionLabels: {
                'Submitted': 'Begin Verification',
                'Under Verification': 'Approve Request',
                'Under Review': 'Approve Request',
                'Approved': 'Mark to Release',
                'Mark to Release': 'Release to Citizen',
            },
            get nextStageLabel() {
                if (!this.selected) return '';
                return this.stageActionLabels[this.selected.status] ?? 'Approve & Continue';
            },
            // FRONTEND SIMULATION — no real camera/file access. Stands in
            // with the citizen's own on-file photo (a real asset, not a
            // fabricated one) since a genuine handover snapshot isn't
            // available in this mock dataset; a real backend would store
            // whatever image the admin actually captured/uploaded.
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
            // approves the request directly — once the admin has approved
            // every resubmitted requirement at the per-requirement level
            // (see confirmRequirementDecision), there is no further
            // whole-request gate to pass.
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
                        'Approved': ['Request Approved', 'has been approved and is being prepared.', 'package-check', 'emerald'],
                        'Mark to Release': ['Ready for Release', 'is ready for release! Visit the office to claim it.', 'circle-check', 'teal'],
                        'Released': ['Request Released', 'has been released. Thank you!', 'check-check', 'cyan'],
                    }[this.selected.status];
                    if (stageNotice) {
                        this.notifyStatus(stageNotice[0], 'Your ' + this.selected.type + ' request (' + this.selected.ref + ') ' + stageNotice[1], stageNotice[2], stageNotice[3]);
                    }
                }, 700);
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('dokyu')" x-cloak>
            <x-admin.access-restricted module="Dokyu (Document Requests)" icon="file-text" />
        </div>

        <div x-show="$store.session.can('dokyu')" x-cloak>

        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mb-4 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing Dokyu requests for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Profile
            </button>
        </div>

        <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto mb-4">
            <button
                type="button" @click="view = 'requests'"
                class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                :class="view === 'requests' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            ><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Document Requests</button>
            <a
                href="{{ route('admin.papeles') }}"
                class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap text-slate-500 hover:text-slate-700"
            ><i data-lucide="folder-open" class="w-3.5 h-3.5"></i> Citizen Document Storage <i data-lucide="arrow-up-right" class="w-3 h-3"></i></a>
        </div>

        <div x-show="view === 'requests'">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Total Requests" value="{{ count($requests) }}" valueBind="requests.length" icon="file-text" color="brand" sublabel="All statuses" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="{{ collect($requests)->whereIn('status', $actionStatuses)->count() }}" valueBind="requests.filter(r => actionStatuses.includes(r.status)).length" icon="inbox" color="orange" sublabel="Review or verification" :delay="40" />
            <x-ui.stat-card @click="tab = 'release'" class="cursor-pointer" label="Mark to Release" value="{{ collect($requests)->where('status', 'Mark to Release')->count() }}" valueBind="requests.filter(r => r.status === 'Mark to Release').length" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card :href="route('admin.analytics')" label="Avg. Processing Time" value="2.4 days" icon="timer" color="purple" trendDirection="down" trend="-0.6 days" sublabel="Within RA 11032 limits" :delay="120" />
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
                    :class="showAdvancedFilters || officeFilter || levelFilter || assignedFilter || barangayFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
                >
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced
                </button>
                <button
                    type="button" @click="resetDemoData()"
                    title="Clears saved review progress for every Dokyu/Tulong request in this browser and reloads with the current mock data — use this if a request's status or documents look out of date."
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border border-slate-200 bg-slate-50 text-slate-500 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-colors shrink-0"
                >
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset Demo Data
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
                <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' requests'"></p>
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
                    <option value="Barangay">Barangay</option>
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
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Document Type</th>
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
                                :class="{ 'bg-teal-50 text-teal-700': r.level === 'Barangay', 'bg-brand-50 text-brand-700': r.level === 'Municipal', 'bg-purple-50 text-purple-700': r.level === 'Multi-Office' }"
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
                            <button @click="openRequest(r)" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Process</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No requests match these filters.</p>
        </div>

        <x-ui.modal title="Process Document Request" maxWidth="4xl">
            <template x-if="selected">
                {{-- Three isolated regions so a long submission never buries the
                     stepper or the advice off-screen: the stepper (left) and
                     System Advice (right) stay pinned in place while only the
                     middle column — fields, the submitted form, the validation
                     guide, and the documents list — scrolls. --}}
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
                                            :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'bg-brand-600 text-white shadow-card ring-4 ring-brand-100' : 'bg-slate-100 text-slate-400')"
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

                    {{-- MIDDLE — everything about this specific request; the
                         only column that scrolls. --}}
                    <div class="flex-1 min-w-0 sm:overflow-y-auto sm:min-h-0 scrollbar-thin sm:pr-1">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Document</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Processing Office</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-teal-50 text-teal-600 border-teal-100': selected.level === 'Barangay', 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assigned Personnel</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
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
                        <p class="text-xs text-slate-400">This request hasn't been routed to a processor yet.</p>
                        <x-ui.button
                            variant="secondary" size="sm" icon="user-plus"
                            @click="selected.assigned = $store.session.account?.name || 'You'; $dispatch('toast', { message: 'Assigned to yourself.', variant: 'success' })"
                        >Assign to me</x-ui.button>
                    </div>

                    {{-- Payment method + Official Payment Receipt — same source of
                         truth and same visual design as the dedicated Payments
                         page and the Esperanza Mobile app, so a processor never
                         has to leave this modal to see how (and whether) this
                         request's fee was settled. Shown for every Dokyu request
                         once a payment method has actually been chosen (not for
                         a bare "Under Review" record where nothing's been
                         picked yet); the compact summary below always shows the
                         chosen method/amount/status, and the full receipt further
                         down (see x-admin.payment-receipt) only once a receipt
                         has actually been uploaded/generated. --}}
                    <div class="mt-4" x-show="selectedPayment?.method">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Payment</p>
                            <button
                                type="button"
                                @click="window.location.href = '/admin/payments?ref=' + encodeURIComponent(selected.ref)"
                                class="text-[11px] font-medium text-brand-600 hover:underline whitespace-nowrap"
                            >View in Payments</button>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 mb-2 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-[#0072CE]/10': selectedPayment?.method === 'GCash',
                                        'bg-[#00A85A]/10': selectedPayment?.method === 'PayMaya',
                                        'bg-brand-50 text-brand-600': !['GCash','PayMaya'].includes(selectedPayment?.method),
                                    }"
                                >
                                    <img x-show="selectedPayment?.method === 'GCash'" src="{{ asset('images/payment-methods/gcash-logo.svg') }}" alt="GCash" class="h-[18px] w-5 object-contain">
                                    <img x-show="selectedPayment?.method === 'PayMaya'" src="{{ asset('images/payment-methods/maya-logo.webp') }}" alt="Maya" class="h-3.5 w-[34px] object-contain">
                                    <i x-show="!['GCash','PayMaya'].includes(selectedPayment?.method)" data-lucide="store" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-navy-900" x-text="(selectedPayment?.method === 'Payment in Center' ? 'Onsite — Municipal Office' : selectedPayment?.method) + ' · ' + selectedPayment?.amount"></p>
                                    <p class="text-[11px] text-slate-400" x-text="selectedPayment?.receipt ? 'Receipt on file' : 'Awaiting receipt'"></p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap shrink-0"
                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': selectedPayment?.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': selectedPayment?.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': selectedPayment?.status === 'Submitted' || selectedPayment?.status === 'Under Review' }"
                            ><span x-text="selectedPayment?.status === 'Approved' ? 'Verified' : selectedPayment?.status"></span></span>
                        </div>
                        <x-admin.payment-receipt recordExpr="selectedPayment" />
                    </div>

                    <x-admin.request-form-review />

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Submitted Documents</p>
                            <p class="text-[11px] text-slate-400" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="req in (selected.requirements || [])" :key="req.key">
                                <div class="rounded-xl border p-3" :class="{ 'border-emerald-200 bg-emerald-50/30': req.status === 'Approved', 'border-rose-200 bg-rose-50/30': req.status === 'Rejected', 'border-amber-200 bg-amber-50/30': req.status === 'Flagged for Replacement', 'border-sky-200 bg-sky-50/30': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' }">
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
                                                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-colors"
                                                title="View submitted document"
                                            ><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
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
                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="(req.status === 'Submitted' || req.status === 'Resubmitted') && !req._decidingOutcome">
                                        <button type="button" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Approve</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Flagged for Replacement')" class="text-[11px] font-medium text-amber-600 hover:underline">Flag for Replacement</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Rejected')" class="text-[11px] font-medium text-rose-600 hover:underline">Reject</button>
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
                            x-model="rejectRemarks" rows="2" placeholder="e.g. Missing Fire Safety Inspection Certificate. Please resubmit with this requirement attached."
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
                                'bg-brand-50/60 border-brand-200': processAdvice.tone === 'brand',
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
                                        'bg-brand-100 text-brand-700': processAdvice.tone === 'brand',
                                    }"
                                ><i :data-lucide="processAdvice.icon" class="w-4.5 h-4.5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide"
                                        :class="{
                                            'text-emerald-700': processAdvice.tone === 'emerald',
                                            'text-rose-700': processAdvice.tone === 'rose',
                                            'text-amber-700': processAdvice.tone === 'amber',
                                            'text-indigo-700': processAdvice.tone === 'indigo',
                                            'text-brand-700': processAdvice.tone === 'brand',
                                        }"
                                    >System Advice — <span x-text="processAdvice.title"></span></p>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed" x-text="processAdvice.message"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Proof of Release — a photo showing the citizen actually
                             received the document, captured/attached by the admin
                             at handover. Only relevant once the request has reached
                             Mark to Release; stays visible afterward once captured
                             so Released/Completed requests still show their own
                             proof on reopen. FRONTEND SIMULATION — no real camera
                             access, mirrors the same "simulated attach" pattern
                             already used for payment receipts on the Payments page. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i> Proof of Release
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.release_evidence">Photo evidence that the citizen actually received this document — required before releasing.</p>

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
                                <button type="button" @click="captureReleaseEvidence('camera')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 py-3 text-slate-500 hover:text-brand-600 transition-colors">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Take Photo</span>
                                </button>
                                <button type="button" @click="captureReleaseEvidence('upload')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 py-3 text-slate-500 hover:text-brand-600 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Upload Photo</span>
                                </button>
                            </div>
                        </div>

                        {{-- Reference Form — the actual official form for this
                             service, so the admin can check the prepared
                             document against it before handover. Only shown
                             once the request reaches Mark to Release, same
                             visibility condition as Proof of Release above. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Reference Form
                                <span x-show="selected.formNo" class="normal-case font-normal text-slate-400" x-text="'— ' + selected.formNo"></span>
                            </p>
                            <template x-if="selected.reference_pdf">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50" style="height: 220px;">
                                        <iframe :src="selected.reference_pdf" class="w-full h-full" title="Reference form preview"></iframe>
                                    </div>
                                    <a :href="selected.reference_pdf" target="_blank" rel="noopener" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium inline-flex items-center gap-1 mt-1.5">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> Open in new tab
                                    </a>
                                </div>
                            </template>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.reference_pdf">No reference form on file for this service.</p>
                        </div>

                        {{-- Document — the citizen's own prepared/issued
                             certificate (not the blank Reference Form above),
                             so staff can preview, print, or download exactly
                             what will be handed over. Same visibility window
                             as Proof of Release / Reference Form. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="['Approved', 'Mark to Release', 'Released'].includes(selected.status)" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="file-check-2" class="w-3.5 h-3.5"></i> Document
                            </p>
                            <template x-if="documentReady">
                                <div class="mt-2.5">
                                    <p class="text-[11px] text-slate-400 leading-relaxed mb-2">Preview, print, or download the prepared document before handing it over.</p>
                                    <x-ui.button size="sm" variant="secondary" icon="eye" class="w-full justify-center" @click="editingDocument = false; documentEditBackup = null; docPreviewOpen = true">View Document</x-ui.button>
                                </div>
                            </template>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!documentReady">No prepared document on file for this request.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button on any
                 submitted requirement above. Fixed to the viewport (not nested
                 inside the scrollable middle column) so it sits above the
                 whole Process modal regardless of scroll position. --}}
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
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>This request is finalized — no further status changes.
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

        {{-- Document Preview: reuses the exact same letterhead components the
             citizen wizard uses for its own live pre-submission preview,
             bound here to the request's frozen formSnapshot so staff see
             precisely what the citizen will receive. Same #dp-print-area /
             dp-printing print isolation as the citizen portal (shared rules
             in app.css). --}}
        <div
            x-data="{ get open() { return docPreviewOpen }, set open(v) { docPreviewOpen = v; if (!v) { editingDocument = false; documentEditBackup = null; backgroundEditBackup = null; resetEditorUiState(); } else { zoomLevel = 100; $nextTick(() => recomputeZoomSizer()); } } }"
            @keydown.window="handleZoomShortcut($event)"
        >
            <x-ui.modal title="Prepared Document" maxWidth="3xl">
                <template x-if="selected && documentReady">
                    <div>
                        <div class="flex items-center justify-between mb-3 dp-no-print" x-show="!editingDocument">
                            <p class="text-[11px] text-slate-400 leading-relaxed">Review the prepared document below, or edit its wording and formatting before printing or downloading.</p>
                            <x-ui.button size="sm" variant="secondary" icon="pencil" @click="startEditingDocument()">Edit Document</x-ui.button>
                        </div>

                        <x-citizen.document-editor-zoom-controls />
                        <x-citizen.document-editor-toolbar />
                        <x-citizen.document-editor-background-panel />
                        <x-citizen.document-editor-spacing-panel />
                        <x-citizen.document-editor-header-seal-panel />

                        <div id="dp-print-area">
                            <div class="flex items-center justify-between mb-4 text-[11px] text-slate-400 dp-no-print">
                                <span x-text="selected.ref"></span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-medium ring-1 ring-inset"
                                      :class="selected.status === 'Released' ? 'bg-cyan-50 text-cyan-700 ring-cyan-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                                      x-text="selected.status"></span>
                            </div>

                            {{-- Zoom is purely a view affordance — it wraps
                                 both rendering branches below without
                                 changing either. doc-zoom-viewport is the
                                 scrollable window; doc-zoom-sizer's explicit
                                 width/height (recomputeZoomSizer() in the
                                 x-data above) reserves exactly the scaled
                                 paper's footprint so the viewport actually
                                 has something to scroll; doc-zoom-scale
                                 carries the transform itself. All three are
                                 neutralized during print/PDF (see app.css)
                                 so the exported page is always true,
                                 unscaled A4. --}}
                            <div class="doc-zoom-viewport max-h-[60vh]" x-ref="zoomViewport">
                                <div class="doc-zoom-sizer" x-ref="zoomSizer">
                                    <div class="doc-zoom-scale" x-ref="zoomScale" :style="'transform: scale(' + (zoomLevel / 100) + ');'">
                                        {{-- Not yet customized — render the live
                                             template exactly as before
                                             (unchanged appearance/behavior). --}}
                                        <template x-if="!selected.customDocumentHtml">
                                            <div>
                                                <template x-if="resolvedDocument?.previewType === 'barangay'">
                                                    <x-citizen.barangay-certificate-preview
                                                        docKey="resolvedDocument.docKey"
                                                        bind="resolvedDocument.snapshot"
                                                        title="resolvedDocument.certTitle"
                                                        subtitle="resolvedDocument.certSubtitle"
                                                        barangay="selected.barangay"
                                                        seal="null"
                                                        punongBarangay="resolvedDocument.barangayOfficials?.punong_barangay"
                                                        barangaySecretary="resolvedDocument.barangayOfficials?.barangay_secretary"
                                                    />
                                                </template>
                                                <template x-if="resolvedDocument?.previewType === 'municipal_request'">
                                                    <x-citizen.municipal-request-preview
                                                        docKey="resolvedDocument.docKey"
                                                        bind="resolvedDocument.snapshot"
                                                        title="resolvedDocument.certTitle"
                                                        office="selected.office"
                                                        formNo="selected.formNo"
                                                        officiant="selected.officiant"
                                                        officiantTitle="selected.officiantTitle"
                                                        technician="selected.technician"
                                                        technicianTitle="selected.technicianTitle"
                                                        verifier="selected.verifier"
                                                        verifierTitle="selected.verifierTitle"
                                                    />
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Customized (or currently being
                                             edited) — the frozen/edited HTML
                                             from startEditingDocument()/
                                             saveDocumentEdits() above, with
                                             the watermark rendered as an
                                             independent sibling layer driven
                                             by documentBackground so Change
                                             Background never touches the
                                             editable text, and vice versa. --}}
                                        <template x-if="selected.customDocumentHtml">
                                            <div class="doc-paper rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden mx-auto flex flex-col p-6 sm:p-10" :style="paperStyle()">
                                                <img class="doc-watermark" alt="" aria-hidden="true" x-show="documentBackground.src" x-cloak :src="documentBackground.src" :style="watermarkStyle()">
                                                <div
                                                    class="doc-content relative flex flex-col flex-1"
                                                    x-ref="docEditor"
                                                    :contenteditable="editingDocument ? 'true' : 'false'"
                                                    x-html="selected.customDocumentHtml"
                                                    @click="selectSection($event)"
                                                    @mouseover="hoverSection($event)"
                                                    @mouseleave="clearHoverSection()"
                                                    @keydown="handleContentKeydown($event)"
                                                    @input="refreshOverflowWarning(); recomputeZoomSizer()"
                                                ></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button size="sm" variant="secondary" icon="printer" @click="printDocument()">Print</x-ui.button>
                    <x-ui.button size="sm" icon="download" @click="downloadDocument()">Download PDF</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        </div>

        </div>
    </div>
</x-layouts.admin>
