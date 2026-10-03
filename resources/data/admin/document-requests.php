<?php

/**
 * Fixture data for resources/views/admin/document-requests.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/document-requests.php');
 */

return [
    'barangayCatalog' => [],

    'requests' => [
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
        ['ref' => 'DR-2026-2154', 'citizen' => 'Corazon Villareal', 'type' => 'Certificate of Residency', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Iligan', 'submitted' => 'Jun 29, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-2098', 'citizen' => 'Teresita Salazar', 'type' => 'Senior Citizen ID', 'office' => 'OSCA', 'barangay' => 'Agoho', 'submitted' => 'Jun 22, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-1998', 'citizen' => 'Josefina Marbella', 'type' => 'Certificate of Residency', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Baras', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
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
        ['ref' => 'DR-2026-2295', 'citizen' => 'Lorna Reyes Dimaano', 'type' => 'Certificate of Widowhood', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Guadalupe', 'submitted' => 'Jul 24, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-2248', 'citizen' => 'Bayani Cruz Espinosa', 'type' => 'Barangay Indigency', 'office' => 'Barangay Hall (Brgy. Iligan)', 'barangay' => 'Iligan', 'submitted' => 'Jul 9, 2026', 'status' => 'Under Review'],
        ['ref' => 'DR-2026-2301', 'citizen' => 'Divina Torres Galvez', 'type' => 'Certificate of Good Moral Character', 'office' => 'Barangay Hall (Brgy. Labrador)', 'barangay' => 'Labrador', 'submitted' => 'Jul 8, 2026', 'status' => 'Released'],
        ['ref' => 'DR-2026-2219', 'citizen' => 'Analiza Bautista Ibarra', 'type' => 'Certificate of Residency', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Magsaysay', 'submitted' => 'Jun 15, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-2309', 'citizen' => 'Grace Mendoza Kalaw', 'type' => 'Certificate of Residency', 'office' => 'Office of the Municipal Civil Registrar', 'barangay' => 'Poblacion', 'submitted' => 'Jun 30, 2026', 'status' => 'Released'],
        ['ref' => 'DR-2026-2261', 'citizen' => 'Precious Domingo Manalo', 'type' => 'First Time Jobseeker Certificate (RA 11261)', 'office' => 'Barangay Hall (Brgy. Rizal)', 'barangay' => 'Rizal', 'submitted' => 'Jul 16, 2026', 'status' => 'Submitted'],
        ['ref' => 'DR-2026-2277', 'citizen' => 'Ernesto Salazar Nierva', 'type' => 'Certificate of Good Moral Character', 'office' => 'Barangay Hall (Brgy. San Roque)', 'barangay' => 'San Roque', 'submitted' => 'Jul 18, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2313', 'citizen' => 'Josephine Castro Olivar', 'type' => 'Barangay Business Clearance', 'office' => 'Barangay Hall (Brgy. Santiago)', 'barangay' => 'Santiago', 'submitted' => 'Jul 20, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'DR-2026-2249', 'citizen' => 'Dante Marquez Robles', 'type' => 'Barangay Certification (General Purpose)', 'office' => 'Barangay Hall (Brgy. Tunga)', 'barangay' => 'Tunga', 'submitted' => 'Jul 10, 2026', 'status' => 'Mark to Release'],
    ],

    'typeToServiceGuideKey' => [
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
    ],

    'dokyuClassification' => [
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
    ],

    'assignedByRef' => [
        'DR-2026-2210' => 'Ferdinand Cortez', 'DR-2026-2154' => 'Marivic Ong', 'DR-2026-2098' => 'Teresita Fajardo',
        'DR-2026-2228' => 'Jenneth A. Cachila', 'DR-2026-2247' => 'Precious Manalo',
    ],

    'purposeByCategory' => [
        'Barangay Certificates' => 'For local transaction and identification purposes',
        'Business & Permits' => 'For business registration/renewal requirement',
        'Tax & Property' => 'For real property tax settlement',
        'Social Services' => 'For social welfare assistance application',
        'Civil Registry' => 'For submission to a requesting government agency',
        'Senior Citizen / PWD' => 'For special privileges and benefits application',
    ],

    'defaultRequirementsByCategory' => [
        'Barangay Certificates' => ['Valid Government-Issued ID', 'Barangay Residency Verification'],
        'Business & Permits' => ['DTI/SEC Registration', 'Barangay Business Clearance', 'Valid Government-Issued ID'],
        'Tax & Property' => ['Valid Government-Issued ID', 'Proof of Property / Tax Declaration'],
        'Social Services' => ['Valid Government-Issued ID', 'Barangay Certificate of Indigency'],
        'Civil Registry' => ['Valid Government-Issued ID', 'Proof of Residency'],
        'Senior Citizen / PWD' => ['Valid Government-Issued ID', 'Proof of Age / Birth Certificate'],
    ],

    'idRequirementNames' => ['Valid Government-Issued ID', 'Valid ID', 'Valid Government ID', 'Requester ID', 'Claimant ID', 'Owner ID', 'Identity / Birth Record', 'Age / Date of Birth Proof', 'Identity'],

    'idPreviewAssetByCitizen' => [
        'Cristy Pareja Bonghanoy' => asset('images/esperanza/residents/cristy-id.png'),
        'Ronaldo Panganiban Bautista' => asset('images/esperanza/residents/ronaldo-id.png'),
    ],

    'tabs' => ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Mark to Release', 'done' => 'Completed'],

    'actionStatuses' => ['Submitted', 'Under Verification', 'Under Review', 'Resubmitted'],

    'doneStatuses' => ['Completed', 'Released'],

    'dokyuTemplates' => [
        [
            'key' => 'esperanza_standard',
            'name' => 'Esperanza Standard',
            'description' => 'Default municipal design',
            'thumbnail' => asset('images/esperanza/esperanza-seal.png'),
            'leftSeal' => asset('images/esperanza/esperanza-seal.png'),
            'rightSeal' => asset('images/esperanza/esperanza-seal.png'),
            'watermarkSrc' => asset('images/esperanza/esperanza-seal.png'),
            'watermarkOpacity' => 5,
            'paperColor' => '#FFFFFF',
            'decorative' => null,
        ],
        [
            'key' => 'barangay_labangtaytay',
            'name' => 'Barangay Labangtaytay',
            'description' => 'Official Labangtaytay letterhead',
            'thumbnail' => asset('images/esperanza/templates/barangay-labangtaytay-seal.png'),
            'leftSeal' => asset('images/esperanza/templates/barangay-labangtaytay-seal.png'),
            'rightSeal' => asset('images/esperanza/templates/esperanza-municipal-seal-v2.png'),
            'watermarkSrc' => asset('images/esperanza/templates/barangay-labangtaytay-seal.png'),
            'watermarkOpacity' => 18,
            'paperColor' => '#FFFFFF',
            'decorative' => 'labangtaytay',
        ],
        [
            'key' => 'barangay_villa',
            'name' => 'Barangay Villa',
            'description' => 'Official Villa letterhead',
            'thumbnail' => asset('images/esperanza/templates/barangay-villa-seal.png'),
            'leftSeal' => asset('images/esperanza/templates/barangay-villa-seal.png'),
            'rightSeal' => asset('images/esperanza/templates/esperanza-municipal-seal-v2.png'),
            'watermarkSrc' => asset('images/esperanza/templates/barangay-villa-seal.png'),
            'watermarkOpacity' => 18,
            'paperColor' => '#FFFFFF',
            'decorative' => 'villa',
        ],
    ],
];
