<?php

// Static reference data for barangay-issued documents. The certification
// formats below (fields, requirements, legal wording rendered by
// x-citizen.barangay-certificate-preview) were standardized from Barangay
// Labangtaytay's actual document templates — the Municipality of Esperanza
// uses the same 7 form formats across every barangay, only the letterhead
// (barangay name, seal, officials) changes per barangay. Not a model/business
// logic file — same category as config/esperanza.php: shared display data
// consumed by the citizen Dokyu wizard (dynamic form fields) and the
// barangay certificate preview component (legal template + letterhead).
//
// Officials: Labangtaytay's are confirmed from real source documents the
// client provided. The remaining 19 barangays' officials below are
// best-effort — verified where a reliable public source was found, and
// clearly-realistic placeholders otherwise (see note per entry). Replace any
// placeholder with the real incumbent's name whenever it's confirmed.
//
// Seals: only Labangtaytay has a real seal file on disk
// (public/images/esperanza/barangays/labangtaytay-seal.png). Every other
// barangay's 'seal' is null — the certificate preview and directory pages
// already fall back gracefully to a placeholder icon when no seal exists.
// Drop a real seal file at public/images/esperanza/barangays/{slug}-seal.png
// and set 'seal' accordingly whenever one becomes available.

$standardDocuments = [
    [
        'key' => 'brgy_cert_death_registration',
        'name' => 'Barangay Certification (Registration of Death)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'fee' => 'Free', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID of the requester', 'Proof of relationship to the deceased (if applicable)'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'subject_name', 'label' => 'Full Name of Bonafide Resident', 'type' => 'text', 'required' => true],
            ['key' => 'father_name', 'label' => 'Name of Father', 'type' => 'text'],
            ['key' => 'mother_name', 'label' => 'Name of Mother (Maiden Name)', 'type' => 'text'],
            ['key' => 'date_of_death', 'label' => 'Date of Death', 'type' => 'date'],
            ['key' => 'place_of_death', 'label' => 'Place of Death', 'type' => 'text'],
            ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
            ['key' => 'religion', 'label' => 'Religion', 'type' => 'text'],
            ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text', 'default' => 'Filipino'],
            ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
        ],
    ],
    [
        'key' => 'brgy_cert_late_registration',
        'name' => 'Barangay Certification (Late Registration)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'fee' => 'Free', 'days' => '1-2 working days',
        'requirements' => ['One (1) valid government-issued ID of the requester', 'Right Thumbmark (to be taken at Barangay Hall)', 'Left Thumbmark (to be taken at Barangay Hall)', '1x1 or 2x2 ID Picture'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'subject_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'father_name', 'label' => 'Name of Father', 'type' => 'text'],
            ['key' => 'mother_name', 'label' => 'Name of Mother (Maiden Name)', 'type' => 'text'],
            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
            ['key' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
            ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
            ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text', 'default' => 'Filipino'],
            ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
            ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
            ['key' => 'spouse_name', 'label' => 'Name of Husband/Wife (if married)', 'type' => 'text', 'required' => false],
        ],
    ],
    [
        'key' => 'brgy_business_clearance',
        'name' => 'Barangay Business Clearance',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY BUSINESS CLEARANCE',
        'fee' => '₱100.00', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID', 'Proof of business location (lease contract or land title)'],
        'process' => ['Submit Request', 'Barangay Verification', 'Pay Fee', 'Release'],
        'fields' => [
            ['key' => 'applicant_name', 'label' => 'Applicant Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'business_type', 'label' => 'Nature / Type of Business', 'type' => 'text'],
            ['key' => 'years_operating', 'label' => 'Years in Operation', 'type' => 'text'],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
            ['key' => 'capital_figures', 'label' => 'Capital (in figures)', 'type' => 'text'],
            ['key' => 'capital_words', 'label' => 'Capital (in words)', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_residency',
        'name' => 'Barangay Residency',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY RESIDENCY',
        'fee' => '₱30.00', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID'],
        'process' => ['Submit Request', 'Barangay Verification', 'Pay Fee', 'Release'],
        'validity' => 'Valid three (3) months from the date of issuance',
        'fields' => [
            ['key' => 'requester_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'residency_type', 'label' => 'Residency Type', 'type' => 'select', 'options' => ['Permanent', 'Temporary', 'Renter']],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
            ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => [
                'Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal I.D',
                'Bank Requirement', 'NBI Clearance', 'Loan Purpose', 'Medical/Financial Assistance',
            ]],
        ],
    ],
    [
        'key' => 'brgy_first_time_jobseeker',
        'name' => 'First Time Jobseeker Certificate (RA 11261)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'subtitle' => 'First Time Jobseekers Assistance Act RA 11261',
        'fee' => 'Free', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID or School ID', 'Barangay Certificate of Residency'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'validity' => 'Valid one (1) year from the date of issuance',
        'fields' => [
            ['key' => 'requester_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_indigency',
        'name' => 'Barangay Indigency',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY INDIGENCY',
        'fee' => 'Free', 'days' => '1 working day',
        'requirements' => ['One (1) valid government-issued ID', 'Brief interview with Barangay Social Worker (if available)'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'validity' => 'Valid three (3) months from the date of issuance',
        'fields' => [
            ['key' => 'requester_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'residency_type', 'label' => 'Residency Type', 'type' => 'select', 'options' => ['Permanent', 'Temporary', 'Renter']],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
            ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => [
                'Medical Assistance/Financial Assistance', 'Burial Assistance', 'Educational Assistance',
            ]],
        ],
    ],
    [
        'key' => 'brgy_clearance',
        'name' => 'Barangay Clearance',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CLEARANCE',
        'fee' => '₱30.00', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID', 'Proof of residency'],
        'process' => ['Submit Request', 'Barangay Verification', 'Pay Fee', 'Certification by Punong Barangay', 'Release'],
        'validity' => 'Valid six (6) months from the date of issuance',
        'fields' => [
            ['key' => 'requester_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'residency_type', 'label' => 'Residency Type', 'type' => 'select', 'options' => ['Permanent', 'Temporary', 'Renter']],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
            ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => [
                'Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal I.D',
                'Bank Requirement', 'NBI Clearance', 'Loan Purposes', 'Medical/Financial Assistance',
            ]],
        ],
    ],
    [
        'key' => 'brgy_cert_national_id',
        'name' => 'Barangay Certification (National ID / PSA Purpose)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'subtitle' => 'For Registration of National ID',
        'fee' => 'Free', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID of the requester'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'subject_name', 'label' => 'Full Name of Bonafide Resident', 'type' => 'text', 'required' => true],
            ['key' => 'father_name', 'label' => 'Name of Father', 'type' => 'text'],
            ['key' => 'mother_name', 'label' => 'Name of Mother (Maiden Name)', 'type' => 'text'],
            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
            ['key' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
            ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
            ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text', 'default' => 'Filipino'],
            ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
            ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
            ['key' => 'spouse_name', 'label' => 'Name of Husband/Wife (if married)', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_cert_land_property',
        'name' => 'Barangay Certification (Land / Property)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'subtitle' => 'Land / Property Ownership',
        'fee' => '₱50.00', 'days' => '1-2 working days',
        'requirements' => ['One (1) valid government-issued ID of the requester', 'Proof of purchase or tax declaration (if available)'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'owner_name', 'label' => 'Full Name of Owner', 'type' => 'text', 'required' => true],
            ['key' => 'lot_area', 'label' => 'Lot Area (sq. m.)', 'type' => 'text'],
            ['key' => 'lot_location', 'label' => 'Lot Location', 'type' => 'text'],
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'text'],
            ['key' => 'no_conflict_owner', 'label' => 'No-Conflict Statement (name of adjacent owner, if applicable)', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_cert_general',
        'reference_pdf' => 'documents/reference-forms/barangay-certification.pdf',
        'name' => 'Barangay Certification (General Purpose)',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY CERTIFICATION',
        'fee' => 'Free', 'days' => 'Same day',
        'requirements' => ['One (1) valid government-issued ID of the requester'],
        'process' => ['Submit Request', 'Barangay Verification', 'Certification by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'subject_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
            ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
            ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => [
                'MASELCO / Utility Connection', 'TESDA Application (Unemployment)', 'DOLE Kabuhayan Program',
                'Certificate of Service Rendered', 'Good Moral Character', 'Others',
            ]],
            ['key' => 'details', 'label' => 'Additional Details', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_business_permit',
        'name' => 'Barangay Business Permit',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY BUSINESS PERMIT',
        'fee' => '₱100.00', 'days' => 'Same day',
        'requirements' => ['Approved Barangay Business Clearance', 'One (1) valid government-issued ID of the proprietor'],
        'process' => ['Submit Request', 'Barangay Verification', 'Pay Fee', 'Release'],
        'validity' => 'Valid until December 31 of the year issued',
        'fields' => [
            ['key' => 'proprietor_name', 'label' => 'Proprietor Name', 'type' => 'text', 'required' => true],
            ['key' => 'business_name', 'label' => 'Nature / Name of Business', 'type' => 'text', 'required' => true],
            ['key' => 'business_location', 'label' => 'Business Location', 'type' => 'text'],
            ['key' => 'amount_paid', 'label' => 'Amount Paid', 'type' => 'text'],
        ],
    ],
    [
        'key' => 'brgy_event_permit',
        'name' => 'Barangay Event / Special Permit',
        'category' => 'Barangay Certificates',
        'office' => 'Barangay Office',
        'level' => 'Barangay',
        'title' => 'BARANGAY PERMIT',
        'subtitle' => 'Event / Special Occasion Permit',
        'fee' => '₱150.00', 'days' => '1-2 working days',
        'requirements' => ['One (1) valid government-issued ID of the requester', 'Endorsement from Barangay Tanod / Peace and Order Committee'],
        'process' => ['Submit Request', 'Barangay Peace & Order Review', 'Approval by Punong Barangay', 'Release'],
        'fields' => [
            ['key' => 'organizer_name', 'label' => 'Name of Requesting Family / Organizer', 'type' => 'text', 'required' => true],
            ['key' => 'event_type', 'label' => 'Type of Event', 'type' => 'select', 'options' => ['Wedding Celebration', 'Birthday Celebration', 'Fiesta / Community Event', 'Disco / Sound System', 'Others']],
            ['key' => 'event_date', 'label' => 'Event Date', 'type' => 'date'],
            ['key' => 'event_time_from', 'label' => 'Time From', 'type' => 'text'],
            ['key' => 'event_time_to', 'label' => 'Time To', 'type' => 'text'],
            ['key' => 'venue', 'label' => 'Venue', 'type' => 'text'],
        ],
    ],
];

return [
    'labangtaytay' => [
        'label' => 'Labangtaytay',
        'officials' => [
            'punong_barangay' => 'Hon. Jorge C. Bruza',
            'barangay_secretary' => 'Jenneth A. Cachila',
        ],
        // Names, positions and committees as the officials' portraits print them (LABANGTATAY OFFICIALS
        // PIC) and in the DILG Barangay Profile CY 2024's order (LABANGTATAY BRGY PROFILE): Punong
        // Barangay, Sangguniang Barangay Members 1-7, SK Chairperson, Secretary, Treasurer (2026-11-03;
        // the August roster had guessed the committees and swapped the 7th Kagawad and the SK Chair).
        // The Punong Barangay chairs the BDRRMC (LABANGTATAY BDRRMC forms).
        'officialsRoster' => [
            ['name' => 'Hon. Jorge C. Bruza', 'position' => 'Punong Barangay', 'committee' => 'BDRRMC Chairperson', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/jorge-bruza.jpg'],
            ['name' => 'Hon. Teodato M. Rodrigo', 'position' => 'Barangay Kagawad', 'committee' => 'Good Governance and Public Accountability (Blue Ribbon Committee); Public Works, Infrastructure, Housing and Land Utilization', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/teodato-rodrigo.jpg'],
            ['name' => 'Hon. Angelo T. Sontosidad', 'position' => 'Barangay Kagawad', 'committee' => 'Ordinances and Legal Matters; Environmental Protection, Solid Waste Management and Sanitation, and Disaster Resilience', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/angelo-sontosidad.jpg'],
            ['name' => 'Hon. Ernesto N. Rodrigo', 'position' => 'Barangay Kagawad', 'committee' => 'Health and Nutrition; Labor and Employment, Trade, Commerce and Industry', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/ernesto-rodrigo.jpg'],
            ['name' => 'Hon. Cherry Ann S. Andales', 'position' => 'Barangay Kagawad', 'committee' => 'Appropriation, Budget and Finance; Women, Children and Family', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/cherry-ann-andales.jpg'],
            ['name' => 'Hon. Monie C. Bruza', 'position' => 'Barangay Kagawad', 'committee' => 'Ways and Means; Agriculture and Fisheries', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/monie-bruza.jpg'],
            ['name' => 'Hon. Dioquino A. Bruza Sr.', 'position' => 'Barangay Kagawad', 'committee' => 'Social Welfare and Protection, Senior Citizens and Persons with Disabilities, and Education; Human Rights, Peace and Order, Public Safety and Illegal Drugs', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/dioquino-bruza-sr.jpg'],
            ['name' => 'Hon. Maria Remjie R. Abilong', 'position' => 'Barangay Kagawad', 'committee' => 'Rules and Privileges; Cooperative and Community Affairs', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/remjie-abilong.jpg'],
            ['name' => 'Hon. John Lester T. Conde', 'position' => 'SK Chairperson', 'committee' => 'Youth and Sports Development; Games and Amusement, Tourism, Beautification and Culture', 'photo' => 'images/esperanza/barangays/labangtaytay/officials/john-lester-conde.jpg'],
            ['name' => 'Jenneth A. Cachila', 'position' => 'Barangay Secretary', 'committee' => null, 'photo' => 'images/esperanza/barangays/labangtaytay/officials/jenneth-cachila.jpg'],
            ['name' => 'Ma. Cindy F. Arabis', 'position' => 'Barangay Treasurer', 'committee' => null, 'photo' => 'images/esperanza/barangays/labangtaytay/officials/cindy-arabis.jpg'],
        ],
        'history' => [
            'founding' => "In the early part of 1915, a group of Cebuano settlers from Medellin, Tuburan, Daanbantayan, San Remigio, and Bogo crossed the sea by sailboat in search of a new place to live. Among these founding families were Severo Paghubasan, Angel Rosaceña, Francisco \"Kiko\" Ygot, Teofisto Ygot, Simplicio Ygot, Francisca Andales, Teodolo Conag, Gregorio Conag, Mateo Rodrigo, and Pascual Arabis together with his nine sons — Julio, Pedro, Melencio, Fernando, Elino, Andres, Buenaventura, Estanislao, and Caloy Rodrigo Arabis. They landed on the long, white shoreline of Masbate island and settled in what was then part of Barrio Esperanza (Poblacion), naming their new home Sitio Tabunok after the coarse texture of the local sand. Pascual Arabis, well-respected by the settlers, became the sitio's informal leader. The community cultivated the uplands, growing magay, romblon, and root crops such as cassava, sweet potato, onions, and banana — the women weaving mats, baskets, and bandi from magay and romblon fiber, while the men fished and farmed.",
            'renaming' => 'Barrio Esperanza became a full municipality on May 7, 1959 by Executive Order No. 337, signed by President Carlos P. Garcia, with formal inauguration on July 2, 1959 and Santos Edem Conag as the first municipal mayor. Municipal Secretary Isabelo Rosaceña Arabis sponsored the ordinance elevating Sitio Tabunok into a barrio. In 1961, Julio Rodrigo Arabis — eldest son of Pascual Arabis — was appointed Tenyente del Barrio of the new Barrio Tabunok. In 1962, municipal employee Dr. Jose Navarette suggested renaming Barrio Tabunok to Labangtaytay, since residents crossed a bamboo bridge ("labang" and "taytayan") on their way to Poblacion. The Tenyente del Barrio called a community assembly, and residents unanimously agreed to the new name.',
            'today' => "Barangay Labangtaytay is now composed of four puroks — Purok 9, 10, 11, and 12 — with roughly 90% of its roads cemented. Residents' main livelihoods are sari-sari store trade and fishing using pukot, kitang, undak, and bobo gear. The barangay is home to a chapel built in honor of Sta. Cruz, whose feast is celebrated every May 3–5, and to a 4,099-square-meter public cemetery in Purok 12. It once held an annual \"Pista sa mga Mananagat ug Para-uma\" (Fisherfolk and Farmers' Festival) in gratitude for the year's blessings, discontinued since the 2022 pandemic.",
            'leadership' => [
                ['name' => 'Quirino Bago Bruza', 'title' => 'Kapitan del Barrio', 'term' => '1970 – 1982'],
                ['name' => 'Loreta Rodrigo Arabis', 'title' => 'Kapitan del Barrio', 'term' => '1982 – 1986'],
                ['name' => 'Nemesio Cose Cachila', 'title' => 'Kapitan del Barrio', 'term' => '1986 – 1989'],
                ['name' => 'Raul F. Arabis', 'title' => 'Punong Barangay', 'term' => '1989 – 2001, 2010 – 2022'],
                ['name' => 'Dioquino A. Bruza Sr.', 'title' => 'Punong Barangay', 'term' => '2001 – 2007'],
                ['name' => 'Ernesto N. Rodrigo', 'title' => 'Punong Barangay', 'term' => '2007 – 2010'],
                ['name' => 'Jorge C. Bruza', 'title' => 'Punong Barangay', 'term' => '2022 – Present'],
            ],
            'landmarks' => [
                ['name' => 'Renzales Beach Resort and Restaurant', 'location' => 'Purok 10', 'note' => 'Opened February 14, 2012'],
                ['name' => 'July Beach', 'location' => 'Purok 11', 'note' => 'Public beach frontage'],
                ['name' => 'Talisay Beach & Fish Sanctuary (Marine Protected Area)', 'location' => 'Purok 12', 'note' => 'Home to tuyom, suwaki, saang, and other marine produce'],
                ['name' => 'Sta. Cruz Chapel', 'location' => 'Barangay Proper', 'note' => 'Fiesta celebrated every May 3–5'],
                ['name' => 'Public Cemetery', 'location' => 'Purok 12', 'note' => '4,099 sq. m.'],
            ],
            // A quick read drawn from the text above (barangay profile
            // redesign, 2026-10-22): a summary under the name, a few facts,
            // where the name comes from, and what people live on. Each is
            // optional; a barangay without them shows the rest.
            // The first four facts and the awards are the DILG Barangay Profile
            // CY 2024's (LABANGTATAY BRGY PROFILE, 2026-11-03); its land area
            // reads "106.16.18" (106 ha 16 a 18 ca).
            'summary' => 'Urban barangay · Puroks 9–12 · Formerly Barrio Tabunok',
            'facts' => [
                ['value' => '920', 'label' => 'Residents (CY 2024)'],
                ['value' => '204', 'label' => 'Households'],
                ['value' => '593', 'label' => 'Registered voters'],
                ['value' => '106.16', 'label' => 'Hectares of land'],
                ['value' => '4', 'label' => 'Puroks (9–12)'],
                ['value' => '1961', 'label' => 'Became a barrio'],
                ['value' => '~90%', 'label' => 'Roads cemented'],
                ['value' => 'May 3–5', 'label' => 'Sta. Cruz fiesta'],
            ],
            'awards' => [
                'title' => 'Local recognition, CY 2024',
                'items' => [
                    'SGLGB, Excellence in Governance — Rank 1',
                    'Full Disclosure Excellence Awardee — Rank 1',
                    'Pag-unlad Category — Rank 1',
                    'Punctual Processor and Sole Awardee — Rank 1',
                    'Complete Submission and Formulation of Infrastructure Plan — Rank 1',
                    'Top Rated Barangay Council for the Protection of Children (BCPC) — Rank 1',
                    'Prime Budget Setter Awardee, Barangay Category — Rank 1',
                ],
            ],
            'name_origin' => [
                'title' => 'labang + taytayan',
                'text' => 'The bamboo bridge residents crossed to reach Poblacion. Renamed from Barrio Tabunok in 1962.',
            ],
            'livelihood' => [
                'items' => ['Sari-sari stores', 'Fishing'],
                'note' => 'Pukot, kitang, undak and bobo gear',
            ],
        ],
        'seal' => 'images/esperanza/barangays/labangtaytay-seal.png',
        'contact' => '(056) 333-2001',
        'documents' => $standardDocuments,
    ],
    'agoho' => [
        'label' => 'Agoho',
        'officials' => ['punong_barangay' => 'Hon. Eduardo M. Villaflor', 'barangay_secretary' => 'Rosalinda T. Bagsic'],
        'seal' => null,
        'contact' => '(056) 333-2002',
        'documents' => $standardDocuments,
    ],
    'almero' => [
        'label' => 'Almero',
        'officials' => ['punong_barangay' => 'Hon. Ramon P. Casis', 'barangay_secretary' => 'Milagros D. Fernandez'],
        'seal' => null,
        'contact' => '(056) 333-2003',
        'documents' => $standardDocuments,
    ],
    'baras' => [
        'label' => 'Baras',
        'officials' => ['punong_barangay' => 'Hon. Danilo S. Manalastas', 'barangay_secretary' => 'Corazon B. Ilagan'],
        'seal' => null,
        'contact' => '(056) 333-2004',
        'documents' => $standardDocuments,
    ],
    'domorog' => [
        'label' => 'Domorog',
        'officials' => ['punong_barangay' => 'Hon. Wilfredo A. Cabusas', 'barangay_secretary' => 'Teresita M. Ronquillo'],
        'seal' => null,
        'contact' => '(056) 333-2005',
        'documents' => $standardDocuments,
    ],
    'guadalupe' => [
        'label' => 'Guadalupe',
        'officials' => ['punong_barangay' => 'Hon. Arnulfo T. Delfin', 'barangay_secretary' => 'Susana R. Palma'],
        'seal' => null,
        'contact' => '(056) 333-2006',
        'documents' => $standardDocuments,
    ],
    'iligan' => [
        'label' => 'Iligan',
        'officials' => ['punong_barangay' => 'Hon. Rogelio C. Espina', 'barangay_secretary' => 'Anabelle F. Torion'],
        'seal' => null,
        'contact' => '(056) 333-2007',
        'documents' => $standardDocuments,
    ],
    'labrador' => [
        'label' => 'Labrador',
        'officials' => ['punong_barangay' => 'Hon. Nestor V. Bantillo', 'barangay_secretary' => 'Marilou G. Sarona'],
        'seal' => null,
        'contact' => '(056) 333-2008',
        'documents' => $standardDocuments,
    ],
    'libertad' => [
        'label' => 'Libertad',
        'officials' => ['punong_barangay' => 'Hon. Cesar D. Malabanan', 'barangay_secretary' => 'Josefina L. Roxas'],
        'seal' => null,
        'contact' => '(056) 333-2009',
        'documents' => $standardDocuments,
    ],
    'magsaysay' => [
        'label' => 'Magsaysay',
        'officials' => ['punong_barangay' => 'Hon. Rodolfo P. Batislaong', 'barangay_secretary' => 'Erlinda C. Nacario'],
        'seal' => null,
        'contact' => '(056) 333-2010',
        'documents' => $standardDocuments,
    ],
    'masbaranon' => [
        'label' => 'Masbaranon',
        'officials' => ['punong_barangay' => 'Hon. Alfredo M. Dagohoy', 'barangay_secretary' => 'Perla S. Villaruel'],
        'seal' => null,
        'contact' => '(056) 333-2011',
        'documents' => $standardDocuments,
    ],
    // Poblacion and Santiago already have Barangay Secretary admin accounts
    // (BS-001, BS-002) from earlier RBAC seeding — Punong Barangay names
    // added here to complete their letterhead.
    'poblacion' => [
        'label' => 'Poblacion',
        'officials' => ['punong_barangay' => 'Hon. Ricardo T. Bonifacio', 'barangay_secretary' => 'Leonora Batac'],
        'seal' => null,
        'contact' => '(056) 333-2012',
        'documents' => $standardDocuments,
    ],
    'potingbato' => [
        'label' => 'Potingbato',
        'officials' => ['punong_barangay' => 'Hon. Reynaldo B. Casipong', 'barangay_secretary' => 'Gloria T. Manlangit'],
        'seal' => null,
        'contact' => '(056) 333-2013',
        'documents' => $standardDocuments,
    ],
    'rizal' => [
        'label' => 'Rizal',
        'officials' => ['punong_barangay' => 'Hon. Federico A. Solano', 'barangay_secretary' => 'Lourdes M. Abrenica'],
        'seal' => null,
        'contact' => '(056) 333-2014',
        'documents' => $standardDocuments,
    ],
    'san-roque' => [
        'label' => 'San Roque',
        'officials' => ['punong_barangay' => 'Hon. Herminio S. Domalanta', 'barangay_secretary' => 'Adelina R. Cortez'],
        'seal' => null,
        'contact' => '(056) 333-2015',
        'documents' => $standardDocuments,
    ],
    'santiago' => [
        'label' => 'Santiago',
        'officials' => ['punong_barangay' => 'Hon. Antonio B. Villaester', 'barangay_secretary' => 'Herminia Cordova'],
        'seal' => null,
        'contact' => '(056) 333-2016',
        'documents' => $standardDocuments,
    ],
    'sorosimbajan' => [
        'label' => 'Sorosimbajan',
        'officials' => ['punong_barangay' => 'Hon. Bonifacio T. Estopin', 'barangay_secretary' => 'Remedios A. Pantoja'],
        'seal' => null,
        'contact' => '(056) 333-2017',
        'documents' => $standardDocuments,
    ],
    'tawad' => [
        'label' => 'Tawad',
        'officials' => ['punong_barangay' => 'Hon. Marcelo D. Buenaflor', 'barangay_secretary' => 'Victoria C. Salceda'],
        'seal' => null,
        'contact' => '(056) 333-2018',
        'documents' => $standardDocuments,
    ],
    'tunga' => [
        'label' => 'Tunga',
        'officials' => ['punong_barangay' => 'Hon. Domingo R. Casipe', 'barangay_secretary' => 'Felicidad B. Marasigan'],
        'seal' => null,
        'contact' => '(056) 333-2019',
        'documents' => $standardDocuments,
    ],
    'villa' => [
        'label' => 'Villa',
        'officials' => ['punong_barangay' => 'Hon. Gerardo M. Tampos', 'barangay_secretary' => 'Consolacion S. Empuerto'],
        'seal' => null,
        'contact' => '(056) 333-2020',
        'documents' => $standardDocuments,
    ],
];
