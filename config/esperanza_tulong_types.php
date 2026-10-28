<?php

// Tulong (assistance-request) catalog — relocated from the hardcoded
// $assistanceTypes array that used to sit at the top of
// citizen/assistance-requests.blade.php, so this file is the single source
// of truth for every Tulong fee/requirement/form-field definition, the same
// way config/esperanza_municipal_documents.php and
// config/esperanza_barangay_documents.php already are for Dokyu — consumed
// by the citizen Tulong wizard and by admin Settings > Service Catalog.

return [
    'types' => [
        [
            // processingKind drives which fields the admin's Assistance
            // Processing/Release sections show (see
            // admin/assistance-requests.blade.php) — cash/goods/medical get
            // a disbursement-shaped flow, registration (the Employment &
            // Livelihood enrollment-style programs below) gets a lighter
            // enrollment-confirmation flow instead of a payout.
            'key' => 'tulong_medical', 'category' => 'Medical & Health', 'level' => 'Municipal', 'processingKind' => 'medical',
            'name' => 'Medical Assistance (AICS)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => '₱1,000 – ₱150,000 per case', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'stethoscope',
            'desc' => 'Hospital bills, medicines, and treatment support under DSWD\'s Assistance to Individuals in Crisis Situation.',
            'requirements' => ['One (1) valid government-issued ID', "Medical Abstract or Doctor's prescription", 'Hospital bill or Statement of Account', 'Barangay Certificate of Indigency'],
            'process' => ['Submit Request', 'MSWDO Assessment', 'Approval', 'Cash / Guarantee Letter Release'],
        ],
        [
            'key' => 'tulong_burial', 'category' => 'Burial & Bereavement', 'level' => 'Municipal', 'processingKind' => 'cash',
            'reference_pdf' => 'documents/reference-forms/burial-assistance.pdf',
            'name' => 'Burial Assistance (AICS)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Up to ₱5,000', 'fee' => 'Free', 'days' => '2-3 working days', 'icon' => 'flower',
            'desc' => 'Financial aid for funeral and burial expenses. Must be filed within 15 days from date of death.',
            'requirements' => ['Death Certificate', 'Valid ID of claimant', 'Proof of relationship to the deceased', 'Funeral contract or bill', 'Barangay Certificate of Indigency'],
            'process' => ['Submit Request', 'Verify Death Certificate', 'Approval', 'Release'],
        ],
        [
            'key' => 'tulong_educational', 'category' => 'Education', 'level' => 'Multi-Office', 'processingKind' => 'cash',
            'name' => 'Educational Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => "MSWDO / Mayor's Office", 'amount' => 'Tuition + allowance per semester', 'fee' => 'Free', 'days' => '10-15 working days', 'icon' => 'graduation-cap',
            'desc' => 'Municipal scholarship and school supplies support for indigent students in good academic standing.',
            'requirements' => ['Certificate of Enrollment', 'Report Card / Form 138 (GWA 80 and above)', 'Certificate of Indigency (family income below ₱120,000/year)', 'Barangay Residency Certificate'],
            'process' => ['Submit Requirements', 'Scholarship Committee Review', 'Approval', 'Disbursement'],
            // The scholarship form, as the app asks it (esperanza_service_form_specs:
            // 'Educational Assistance'), so a website applicant gives the same
            // details (QA2, 2026-10-07). The course is asked, and required, only
            // for a college year (showIf); the average grade stays optional.
            'fields' => [
                ['key' => 'schoolName', 'label' => 'School Name', 'type' => 'text', 'required' => true],
                ['key' => 'yearOrGradeLevel', 'label' => 'Year / Grade Level', 'type' => 'select', 'required' => true, 'options' => [
                    'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                    'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
                    '1st Year College', '2nd Year College', '3rd Year College', '4th Year College', '5th Year College',
                ]],
                ['key' => 'degreeProgramOrCourse', 'label' => 'Degree Program / Course', 'type' => 'text', 'required' => true, 'showIf' => ['field' => 'yearOrGradeLevel', 'contains' => 'College']],
                ['key' => 'lastSchoolAverageGrade', 'label' => 'Last School Year Average Grade', 'type' => 'number'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text'],
                ['key' => 'fatherOccupation', 'label' => "Father's Occupation", 'type' => 'text'],
                ['key' => 'fatherMonthlyIncome', 'label' => "Father's Monthly Income (₱)", 'type' => 'number'],
                ['key' => 'motherName', 'label' => "Mother's Name", 'type' => 'text'],
                ['key' => 'motherOccupation', 'label' => "Mother's Occupation", 'type' => 'text'],
                ['key' => 'motherMonthlyIncome', 'label' => "Mother's Monthly Income (₱)", 'type' => 'number'],
            ],
        ],
        [
            'key' => 'tulong_financial', 'category' => 'Financial Assistance', 'level' => 'Municipal', 'processingKind' => 'cash',
            'name' => 'Financial Assistance (AICS)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Based on social worker assessment', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'wallet',
            'desc' => 'Cash assistance for individuals and families in crisis situations.',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certificate of Indigency', 'Brief interview / social case study with MSWDO'],
            'process' => ['Submit Request', 'Social Case Study', 'Approval', 'Release'],
        ],
        [
            // DSWD Family Assistance Card in Emergencies and Disasters (FACED)
            'key' => 'tulong_food', 'category' => 'Food & Relief', 'level' => 'Multi-Office', 'processingKind' => 'goods',
            'name' => 'Food / Relief Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO / MDRRMO', 'amount' => 'Relief goods package', 'fee' => 'Free', 'days' => '1-2 working days', 'icon' => 'package',
            'desc' => 'Food packs and relief goods for families affected by calamities or emergencies.',
            'requirements' => ['Barangay Certification', 'One (1) valid government-issued ID'],
            'process' => ['Submit Request', 'Barangay Verification', 'Approval', 'Distribution'],
            'certTitle' => 'Family Assistance Card in Emergencies and Disasters (FACED)',
            'certSubtitle' => 'Head of Family details, per DSWD FACED card',
            // Checked against the MSWD's copy of the card ("FACED Form.pdf",
            // 2026-10-21): the district, occupation, the permanent address
            // parts and the data privacy declaration (item 33) were added
            // (2026-10-26). The serial number is the office's, given when the
            // family is registered in Sakuna, where its relief is recorded.
            'fields' => [
                ['key' => 'evacuation_center', 'label' => 'Evacuation Center / Site (if applicable)', 'type' => 'text'],
                ['key' => 'district', 'label' => 'District (if any)', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'mother_maiden_name', 'label' => "Mother's Maiden Name", 'type' => 'text'],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text'],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
                ['key' => 'monthly_income', 'label' => 'Monthly Family Net Income', 'type' => 'text'],
                ['key' => 'house_block_lot', 'label' => 'Permanent Address — House / Block / Lot No.', 'type' => 'text'],
                ['key' => 'street', 'label' => 'Permanent Address — Street', 'type' => 'text'],
                ['key' => 'subdivision', 'label' => 'Permanent Address — Subdivision / Village', 'type' => 'text'],
                ['key' => 'zip_code', 'label' => 'Permanent Address — Zip Code', 'type' => 'text'],
                ['key' => 'id_card_presented', 'label' => 'ID Card Presented', 'type' => 'text'],
                ['key' => 'id_card_number', 'label' => 'ID Card Number', 'type' => 'text'],
                ['key' => 'contact_alternate', 'label' => 'Alternate Contact Number', 'type' => 'tel'],
                ['key' => 'type_of_vulnerability', 'label' => 'Type of Vulnerability', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => ['4Ps Beneficiary', 'Indigenous Person', 'Person with Disability', 'Senior Citizen', 'Solo Parent']],
                ['key' => 'ip_ethnicity', 'label' => 'If Indigenous Person, Type of Ethnicity', 'type' => 'text'],
                ['key' => 'house_ownership', 'label' => 'House Ownership', 'type' => 'select', 'options' => ['Owner', 'Renter', 'Sharer', 'Informal Settler']],
                ['key' => 'shelter_damage', 'label' => 'Shelter Damage Classification', 'type' => 'select', 'options' => ['None', 'Partially Damaged', 'Totally Damaged']],
                ['key' => 'bank_ewallet', 'label' => 'Bank / E-Wallet', 'type' => 'text'],
                ['key' => 'account_name', 'label' => 'Account Name', 'type' => 'text'],
                ['key' => 'account_type', 'label' => 'Account Type', 'type' => 'text'],
                ['key' => 'account_number', 'label' => 'Account Number', 'type' => 'text'],
                ['key' => 'family_members', 'label' => 'Family Members', 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Family Member', 'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'relation', 'label' => 'Relation to Head', 'options' => ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Grandchild', 'Other Relative']],
                    ['key' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                    ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
                    ['key' => 'sex', 'label' => 'Sex', 'options' => ['Male', 'Female']],
                    ['key' => 'education', 'label' => 'Educational Attainment', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                    ['key' => 'occupation', 'label' => 'Occupation'],
                    ['key' => 'vulnerability', 'label' => 'Vulnerability', 'options' => ['None', '4Ps Beneficiary', 'Indigenous Person', 'Person with Disability', 'Senior Citizen', 'Solo Parent']],
                ]],
                ['key' => 'data_privacy_consent', 'label' => 'I agree that this information is used to identify my family for disaster risk reduction and management programs, under the Data Privacy Act of 2012 (RA 10173).', 'type' => 'checkbox', 'required' => true],
            ],
        ],
        [
            'key' => 'tulong_pension', 'category' => 'Social Protection', 'level' => 'Municipal', 'processingKind' => 'cash',
            'reference_pdf' => 'documents/reference-forms/social-pension-intake.pdf',
            'name' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'Office of Senior Citizens Affairs', 'office_short' => 'OSCA', 'amount' => '₱1,000/month (₱3,000 per quarter)', 'fee' => '₱100.00', 'days' => '5-7 working days', 'icon' => 'users',
            'desc' => 'Monthly stipend for indigent senior citizens 60 and above with no pension or regular income.',
            'requirements' => ['Senior Citizen ID', 'Affidavit of no pension, income, or family support (notarized)', 'Barangay Certification'],
            'process' => ['Submit Requirements', 'OSCA / DSWD Verification', 'Enrollment', 'Quarterly Release'],
        ],
        [
            // DSWD Application Form for Solo Parent, Annex B (2023), per RA 11861
            'key' => 'tulong_solo_parent', 'category' => 'Social Protection', 'level' => 'Municipal', 'processingKind' => 'cash',
            'reference_pdf' => 'documents/reference-forms/solo-parent-application.pdf',
            'name' => 'Solo Parent Cash Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Cash grant + goods, per assessment', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'heart-handshake',
            'desc' => 'Support for solo parents under the Expanded Solo Parents Welfare Act (RA 11861).',
            'requirements' => ['Solo Parent ID', 'PSA Birth Certificate(s) of children', 'Barangay Certification'],
            'process' => ['Submit Requirements', 'MSWDO Assessment', 'Approval', 'Release'],
            'certTitle' => 'Application Form for Solo Parent',
            'certSubtitle' => 'Annex B (2023) — Local Social Welfare and Development Office',
            'fields' => [
                ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated', 'Annulled']],
                ['key' => 'educational_attainment', 'label' => 'Educational Attainment', 'type' => 'text'],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text'],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
                ['key' => 'company_agency', 'label' => 'Company / Agency', 'type' => 'text'],
                ['key' => 'employment_status', 'label' => 'Employment Status', 'type' => 'select', 'options' => ['Employed', 'Self-employed', 'Not employed']],
                ['key' => 'monthly_income', 'label' => 'Monthly Income', 'type' => 'text'],
                ['key' => 'is_pantawid_beneficiary', 'label' => 'Pantawid (4Ps) Beneficiary?', 'type' => 'select', 'options' => ['Yes', 'No']],
                ['key' => 'is_indigenous_person', 'label' => 'Indigenous Person?', 'type' => 'select', 'options' => ['Yes', 'No']],
                ['key' => 'is_lgbtq', 'label' => 'LGBTQ+?', 'type' => 'select', 'options' => ['Yes', 'No']],
                ['key' => 'solo_parent_circumstance', 'label' => 'Circumstance of Being a Solo Parent', 'type' => 'text'],
                ['key' => 'solo_parent_needs', 'label' => 'Needs / Problems as a Solo Parent', 'type' => 'text'],
                ['key' => 'emergency_contact_name', 'label' => 'In Case of Emergency — Name', 'type' => 'text'],
                ['key' => 'emergency_contact_relationship', 'label' => 'In Case of Emergency — Relationship', 'type' => 'text'],
                ['key' => 'emergency_contact_address', 'label' => 'In Case of Emergency — Address', 'type' => 'text'],
                ['key' => 'family_composition', 'label' => 'Family Composition', 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Family Member', 'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'relationship', 'label' => 'Relationship', 'options' => ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Grandchild', 'Other Relative']],
                    ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
                    ['key' => 'birthday', 'label' => 'Birthday', 'type' => 'date'],
                    ['key' => 'civil_status', 'label' => 'Civil Status', 'options' => ['Single', 'Married', 'Widowed', 'Separated', 'Annulled']],
                    ['key' => 'educational_attainment', 'label' => 'Educational Attainment', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                    ['key' => 'occupation', 'label' => 'Occupation'],
                    ['key' => 'monthly_income', 'label' => 'Monthly Income'],
                ]],
            ],
        ],
        [
            // DOLE TUPAD — Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers
            'key' => 'tulong_tupad', 'category' => 'Employment & Livelihood', 'level' => 'Municipal', 'processingKind' => 'registration',
            'reference_pdf' => 'documents/reference-forms/tupad-profile-form.pdf',
            // Named to match the admin mock data, validation guide, and
            // service-form-specs, which all agree on this shorter form —
            // this citizen catalog entry was the one out of step.
            'name' => 'TUPAD Emergency Employment', 'office' => 'Public Employment Service Office', 'office_short' => 'MPESO', 'amount' => 'Minimum wage rate, 10-30 days of work', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'hard-hat',
            'desc' => 'Short-term emergency employment for displaced, underemployed, or self-employed workers who lost livelihood.',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certification of Residency'],
            'process' => ['Submit Profile Form', 'MPESO Screening', 'DOLE RO5 Validation', 'Project Deployment'],
            'certTitle' => 'TUPAD Beneficiary Profile Form',
            'certSubtitle' => 'TSSD-EFIS03-010, Rev. 02 — Department of Labor and Employment',
            'fields' => [
                ['key' => 'nature_of_work', 'label' => 'Nature of Work', 'type' => 'text'],
                ['key' => 'type_of_workers', 'label' => 'Type of Workers', 'type' => 'select', 'options' => ['Underemployed (Formal Sector)', 'Laid-off / Terminated Workers (Formal Sector)', 'Self-employed with lost livelihood — natural calamities/disasters', 'Self-employed with lost livelihood — economic crisis', 'Self-employed with lost livelihood — armed conflicts', 'Self-employed with lost livelihood — seasonality of work']],
                ['key' => 'specific_beneficiary_type', 'label' => 'Specific Type of Beneficiary', 'type' => 'select', 'options' => ['Crop Grower', 'Alternative/Small Transport Driver', 'Livestock/Poultry Raiser', 'Fisherfolk', 'Homebased Worker', 'Vendor', 'Laborer', 'PWD', 'Others']],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Separated', 'Widow/Widower', 'Others']],
                ['key' => 'spouse_name', 'label' => 'Name of Spouse', 'type' => 'text'],
                ['key' => 'spouse_occupation', 'label' => 'Occupation of Spouse', 'type' => 'text'],
                ['key' => 'educational_attainment', 'label' => 'Highest Educational Attainment', 'type' => 'select', 'options' => ['Elementary', 'High School', 'College', 'Vocational', 'Others']],
                ['key' => 'monthly_income', 'label' => 'Monthly Income', 'type' => 'text'],
                ['key' => 'no_of_dependents', 'label' => 'No. of Dependents', 'type' => 'number'],
                ['key' => 'skills_training_interest', 'label' => 'Intention to Avail Skills Training After the Project', 'type' => 'select', 'options' => ['Not Interested', 'Interested']],
                ['key' => 'skills_training_specify', 'label' => 'If Interested, Specify Training', 'type' => 'text'],
                ['key' => 'dependents', 'label' => "Beneficiary's Dependents (for insurance purposes only)", 'type' => 'table', 'rowIcon' => 'users', 'rowLabel' => 'Dependent', 'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'dob', 'label' => 'Date of Birth'],
                    ['key' => 'age', 'label' => 'Age'],
                    ['key' => 'relationship', 'label' => 'Relationship to Beneficiary'],
                ]],
            ],
        ],
        [
            'key' => 'tulong_tesda', 'category' => 'Employment & Livelihood', 'level' => 'Municipal', 'processingKind' => 'registration',
            'reference_pdf' => 'documents/reference-forms/tesda-registration.pdf',
            'name' => 'TESDA Skills Training Registration', 'office' => 'Public Employment Service Office', 'office_short' => 'MPESO', 'amount' => 'Free training slot (scholarship-based)', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'graduation-cap',
            'desc' => 'Register for a TESDA technical-vocational training slot through the Municipal Public Employment Service Office.',
            'requirements' => ['One (1) 1x1 ID picture', 'One (1) valid government-issued ID'],
            'process' => ['Submit Registration Form', 'MPESO Endorsement to TESDA', 'Enrollment'],
            'certTitle' => "TESDA Registration Form — Learner's Profile",
            'certSubtitle' => 'MIS 03-01, ver. 2021 — Technical Education and Skills Development Authority',
            'fields' => [
                ['key' => 'email_facebook', 'label' => 'Email Address / Facebook Account', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Separated/Divorced/Annulled', 'Widow/er', 'Common Law/Live-in']],
                ['key' => 'employment_status', 'label' => 'Employment Status', 'type' => 'select', 'options' => ['Wage-Employed', 'Underemployed', 'Self-Employed', 'Unemployed']],
                ['key' => 'birthplace', 'label' => 'Birthplace', 'type' => 'text'],
                ['key' => 'educational_attainment', 'label' => 'Educational Attainment Before the Training', 'type' => 'select', 'options' => ['No Grade Completed', 'Elementary Undergraduate', 'Elementary Graduate', 'High School Undergraduate', 'High School Graduate', 'Junior High (K-12)', 'Senior High (K-12)', 'Post-Secondary/Tech-Voc Undergraduate', 'Post-Secondary/Tech-Voc Graduate', 'College Undergraduate', 'College Graduate', 'Masteral', 'Doctorate']],
                ['key' => 'parent_guardian', 'label' => 'Parent / Guardian Name & Address', 'type' => 'text'],
                ['key' => 'client_classification', 'label' => 'Learner/Trainee Classification', 'type' => 'select', 'options' => ['4Ps Beneficiary', 'Agrarian Reform Beneficiary', 'Balik Probinsya', 'Displaced Workers', 'Farmers and Fishermen', 'Indigenous People & Cultural Communities', 'Industry Workers', 'Out-of-School-Youth', 'OFW Dependent', 'Returning/Repatriated OFW', 'Student', 'TESDA Alumni', 'Uniformed Personnel', 'Victim of Natural Disasters and Calamities', 'Others']],
                ['key' => 'course_qualification', 'label' => 'Name of Course / Qualification', 'type' => 'text', 'required' => true],
                ['key' => 'scholarship_package', 'label' => 'Scholarship Package (TWSP, PESFA, STEP, others)', 'type' => 'text'],
                ['key' => 'privacy_consent', 'label' => 'Privacy Consent and Disclaimer', 'type' => 'select', 'options' => ['Agree', 'Disagree']],
            ],
        ],
        [
            'key' => 'tulong_livelihood_mapping', 'category' => 'Employment & Livelihood', 'level' => 'Municipal', 'processingKind' => 'registration',
            'reference_pdf' => 'documents/reference-forms/livelihood-business-mapping.pdf',
            'name' => 'Livelihood Business Mapping', 'office' => 'Public Employment Service Office', 'office_short' => 'MPESO', 'amount' => 'Self-assessment worksheet — no monetary grant', 'fee' => 'Free', 'days' => '1-2 working days', 'icon' => 'compass',
            'desc' => 'Rate your business interests and skills to find the most suitable livelihood project before applying for a MPESO livelihood grant.',
            'requirements' => ['None — self-assessment worksheet'],
            'process' => ['Applicant Self-Rates Business Interests', 'MPESO Livelihood Focal Reviews', 'Filing'],
            'certTitle' => 'Pagmamapa ng Negosyo (Business Mapping)',
            'certSubtitle' => 'TSSD-EFIS02-002, Rev. 01 — Department of Labor and Employment',
            'fields' => [
                ['key' => 'business_interests', 'label' => 'Business Interests (rate 1-5 per criterion: Customer Demand, Raw Materials, Skills Level, Revolving Capital, Management Skills)', 'type' => 'table', 'rowIcon' => 'store', 'rowLabel' => 'Business Interest', 'columns' => [
                    ['key' => 'interest', 'label' => 'Business Interest'],
                    ['key' => 'customer_demand', 'label' => 'Customer Demand (1-5)'],
                    ['key' => 'raw_materials', 'label' => 'Raw Materials (1-5)'],
                    ['key' => 'skills_level', 'label' => 'Skills Level (1-5)'],
                    ['key' => 'revolving_capital', 'label' => 'Revolving Capital (1-5)'],
                    ['key' => 'management_skills', 'label' => 'Management Skills (1-5)'],
                    ['key' => 'total_score', 'label' => 'Total Score'],
                ]],
                ['key' => 'most_interested', 'label' => 'Most Interested Livelihood Choice', 'type' => 'text'],
                ['key' => 'most_capable', 'label' => 'Most Capable Livelihood Choice', 'type' => 'text'],
                ['key' => 'sustainable_capital', 'label' => 'Livelihood Sustainable with Available Capital', 'type' => 'text'],
                ['key' => 'chosen_livelihood', 'label' => 'Chosen Livelihood Project', 'type' => 'text', 'required' => true],
            ],
        ],
        [
            'key' => 'tulong_kabuhayan', 'category' => 'Employment & Livelihood', 'level' => 'Municipal', 'processingKind' => 'registration',
            'reference_pdf' => 'documents/reference-forms/kabuhayan-beneficiary-profile.pdf',
            'name' => 'Kabuhayan Program Beneficiary Application', 'office' => 'Public Employment Service Office', 'office_short' => 'MPESO', 'amount' => 'Livelihood package, per DOLE assessment', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'sprout',
            'desc' => 'Apply for a DOLE-MPESO livelihood/kabuhayan program grant to start, restore, or grow a small business.',
            'requirements' => ['One (1) 1x1 ID picture', 'PWD ID (if applicable)'],
            'process' => ['Submit Profile Form', 'MPESO Screening', 'DOLE Endorsement'],
            'certTitle' => 'Kabuhayan Program Beneficiary Profile Form',
            'certSubtitle' => 'TSSD-EFIS02-003, Rev. 07 — Department of Labor and Employment',
            'fields' => [
                ['key' => 'project_type', 'label' => 'Type of Project', 'type' => 'select', 'options' => ['Group', 'Individual']],
                ['key' => 'program_component', 'label' => 'Program Component', 'type' => 'select', 'options' => ['Formation', 'Restoration', 'Enhancement']],
                ['key' => 'mode_of_implementation', 'label' => 'Mode of Implementation', 'type' => 'select', 'options' => ['Accredited Co-Partner / LGU', 'Direct Administration']],
                ['key' => 'project_title', 'label' => 'Name / Title of Individual Project', 'type' => 'text', 'required' => true],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'text'],
                ['key' => 'has_disability', 'label' => 'Have Disability?', 'type' => 'select', 'options' => ['Yes', 'No']],
                ['key' => 'pwd_id_no', 'label' => 'PWD ID No. (if applicable)', 'type' => 'text'],
                ['key' => 'type_of_beneficiary', 'label' => 'Type of Beneficiary', 'type' => 'text'],
                ['key' => 'monthly_income', 'label' => 'Monthly Income', 'type' => 'text'],
                ['key' => 'no_of_dependents', 'label' => 'No. of Dependents', 'type' => 'number'],
                ['key' => 'spouse_name', 'label' => 'Name of Spouse', 'type' => 'text'],
                ['key' => 'spouse_occupation', 'label' => 'Occupation of Spouse', 'type' => 'text'],
                ['key' => 'educational_attainment', 'label' => 'Highest Educational Attainment', 'type' => 'select', 'options' => ['Elementary', 'High School', 'Vocational', 'College Course']],
                ['key' => 'related_trainings', 'label' => 'Trainings Attended Relative to the Proposed Project', 'type' => 'text'],
                ['key' => 'is_4ps_beneficiary', 'label' => 'Pantawid Pamilyang Pilipino Program (4Ps) Beneficiary?', 'type' => 'select', 'options' => ['Oo', 'Hindi']],
                ['key' => 'gsis_no', 'label' => 'GSIS No.', 'type' => 'text'],
                ['key' => 'pagibig_no', 'label' => 'Pag-IBIG No.', 'type' => 'text'],
                ['key' => 'philhealth_no', 'label' => 'PhilHealth No.', 'type' => 'text'],
                ['key' => 'sss_no', 'label' => 'SSS No.', 'type' => 'text'],
            ],
        ],
        [
            'key' => 'tulong_business_plan', 'category' => 'Employment & Livelihood', 'level' => 'Municipal', 'processingKind' => 'registration',
            'reference_pdf' => 'documents/reference-forms/individual-business-plan.pdf',
            'name' => 'Individual Business Plan', 'office' => 'Public Employment Service Office', 'office_short' => 'MPESO', 'amount' => 'Start-up kit value, per DOLE assessment', 'fee' => 'Free', 'days' => '3-5 working days', 'icon' => 'notebook-pen',
            'desc' => 'Submit your business plan to support a DOLE livelihood grant application — required alongside the Business Mapping and Beneficiary Profile forms.',
            'requirements' => ['Completed Livelihood Business Mapping', 'Completed Kabuhayan Program Beneficiary Application'],
            'process' => ['Submit Business Plan', 'Provincial Livelihood Focal Person Evaluation', 'Approval / Disapproval'],
            'certTitle' => 'Individual Business Plan',
            'certSubtitle' => 'TSSD-EFIS02-004, Rev. 05 — Department of Labor and Employment',
            'fields' => [
                ['key' => 'project_component', 'label' => 'Project Component', 'type' => 'select', 'options' => ['Formation (new business)', 'Enhancement (existing business)', 'Restoration (lost livelihood)']],
                ['key' => 'proposed_business', 'label' => 'Proposed Livelihood Project / Business', 'type' => 'text', 'required' => true],
                ['key' => 'business_type', 'label' => 'Type of Business', 'type' => 'select', 'options' => ['Food Processing/Preservation', 'Selling of Goods', 'Manufacturing & Selling of Products', 'Service', 'Others']],
                ['key' => 'skills_experience', 'label' => 'Skills / Experience on Chosen Business', 'type' => 'text'],
                ['key' => 'product_service', 'label' => 'Product / Service Offered', 'type' => 'text'],
                ['key' => 'target_customers', 'label' => 'Target Customers', 'type' => 'text'],
                ['key' => 'competitors', 'label' => 'Competitors (who & where)', 'type' => 'text'],
                ['key' => 'selling_method', 'label' => 'Method of Selling', 'type' => 'select', 'options' => ['Tingi (Retail)', 'Maramihan/Bultuhan (Wholesale)', 'Made-to-Order', 'On-call', 'Pagtayo ng Shop', 'Paglalako', 'Online Selling', 'Others']],
                ['key' => 'location', 'label' => 'Business Location', 'type' => 'text'],
                ['key' => 'location_ownership', 'label' => 'Location Ownership', 'type' => 'select', 'options' => ['Owned', 'Rented', 'Shared Use']],
                ['key' => 'promotion', 'label' => 'Promotion Method', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => ['Karatula/Flyers', 'Diskwento', 'Rekomendasyon ng Iba', 'Others']],
                ['key' => 'expenses', 'label' => 'Expenses (Revolving Fund)', 'type' => 'table', 'rowIcon' => 'receipt', 'rowLabel' => 'Item', 'columns' => [
                    ['key' => 'item', 'label' => 'Product/Service'],
                    ['key' => 'materials', 'label' => 'Needed Materials/Equipment'],
                    ['key' => 'daily_capital', 'label' => 'Est. Daily Capital'],
                    ['key' => 'monthly_capital', 'label' => 'Est. Monthly Capital'],
                ]],
                ['key' => 'startup_items', 'label' => 'Start-Up Items Requested from DOLE', 'type' => 'table', 'rowIcon' => 'package', 'rowLabel' => 'Item', 'columns' => [
                    ['key' => 'item', 'label' => 'Item (Raw Materials, Equipment, Tools)'],
                    ['key' => 'quantity', 'label' => 'Quantity'],
                    ['key' => 'unit_cost', 'label' => 'Unit Cost'],
                    ['key' => 'total_cost', 'label' => 'Total Cost'],
                ]],
                ['key' => 'monthly_operating_expenses', 'label' => 'Monthly Operating Expenses', 'type' => 'table', 'rowIcon' => 'wallet', 'rowLabel' => 'Expense', 'columns' => [
                    ['key' => 'item', 'label' => 'Item (Water, Electricity, Rent, Salary)'],
                    ['key' => 'amount', 'label' => 'Amount (₱)'],
                ]],
                ['key' => 'projected_monthly_sales', 'label' => 'Projected Total Sales/Income per Month', 'type' => 'number'],
                ['key' => 'projected_monthly_expenses', 'label' => 'Projected Total Expenses per Month', 'type' => 'number'],
                ['key' => 'projected_net_income', 'label' => 'Projected Net Income per Month', 'type' => 'number'],
                ['key' => 'opportunities', 'label' => 'Opportunities and Benefits in the Business', 'type' => 'text'],
                ['key' => 'risks', 'label' => 'Risks and Challenges in the Business', 'type' => 'text'],
            ],
        ],
        [
            // EMPOWERMENT AND RE-AFFIRMATION OF PATERNAL ABILITIES — Esperanza Masbate Federation
            'key' => 'tulong_erpat', 'category' => 'Social Protection', 'level' => 'Municipal', 'processingKind' => 'registration',
            // Same reasoning as TUPAD above — matches the admin/validation-
            // guide/form-specs name rather than the mobile catalog's own
            // longer "ERPAT Program Registration (Fathers' Empowerment)".
            'name' => 'ERPAT Program Registration', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free membership', 'fee' => 'Free', 'days' => '1-2 working days', 'icon' => 'user-round-plus',
            'desc' => 'Register as a member of the ERPAT (Empowerment and Re-affirmation of Paternal Abilities) Federation of Esperanza, Masbate.',
            'requirements' => ['One (1) valid government-issued ID'],
            'process' => ['Accomplish Registration Form', 'MSWD Review', 'Filing with ERPAT Federation'],
            'certTitle' => 'ERPAT Registration Form',
            'certSubtitle' => 'Empowerment and Re-affirmation of Paternal Abilities — ERPAT Federation of Esperanza, Masbate',
            'fields' => [
                ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text'],
                ['key' => 'educational_attainment', 'label' => 'Educational Attainment', 'type' => 'select', 'options' => ['Elementary', 'High School', 'Vocational', 'College', 'Others']],
                ['key' => 'skills_talent', 'label' => 'Skills / Talent', 'type' => 'text'],
                ['key' => 'hobbies', 'label' => 'Hobbies', 'type' => 'text'],
                ['key' => 'involvement_community', 'label' => 'Community Involvement', 'type' => 'text'],
                ['key' => 'family_composition', 'label' => 'Family Composition', 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Family Member', 'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'sex', 'label' => 'Sex', 'options' => ['Male', 'Female']],
                    ['key' => 'relationship', 'label' => 'Relationship', 'options' => ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Other Relative']],
                    ['key' => 'age', 'label' => 'Age', 'type' => 'number'],
                ]],
                ['key' => 'seminars_trainings', 'label' => 'Seminars / Trainings Attended', 'type' => 'table', 'rowIcon' => 'graduation-cap', 'rowLabel' => 'Training', 'columns' => [
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'date', 'label' => 'Date'],
                    ['key' => 'organizer', 'label' => 'Organizer'],
                ]],
            ],
        ],
        // ------------------------------------------------------------------
        // The MSWDO's services beyond assistance (welfare round, 2026-10-29),
        // from the research in the backend's docs/SOCIAL_WELFARE_SERVICES.md
        // (citizen's charters of Philippine MSWDOs, DSWD MC 16 s. 2022 as
        // amended, DSWD MC 01 s. 2019, RA 10354 s. 15, Family Code art. 16).
        //
        // New keys a type may carry:
        // - processingKind 'counseling' (one-to-one, couple or family
        //   sessions and case work: the case's service records are the work
        //   done), 'session' (a scheduled group session with attendance; a
        //   certificate may follow) or 'referral' (the social worker writes a
        //   letter or report).
        // - staffOnly: filed only by an officer (a referral from the police,
        //   the barangay or a school); residents never see it in their list.
        // - sensitive: its records stay with the office (RA 9344 s. 43,
        //   RA 9262 s. 44, RA 7610, RA 11036, RA 10173 s. 3(l)).
        // - certificate (session kind): the e-certificate issued to everyone
        //   who attended once the session is closed; requiresAccreditation
        //   means a facilitator must hold a DSWD accreditation valid on the
        //   session day (pre-marriage counseling, DSWD MC 01 s. 2019).
        // - sessionCouple: the request names a partner; both must attend and
        //   the certificate names both.
        // ------------------------------------------------------------------
        [
            'key' => 'tulong_transport', 'category' => 'Financial Assistance', 'level' => 'Municipal', 'processingKind' => 'cash',
            'name' => 'Transportation Assistance (AICS)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Fare or ticket, based on assessment', 'fee' => 'Free', 'days' => '1-2 working days', 'icon' => 'bus',
            'desc' => 'Fare for a resident stranded away from home, travelling for medical treatment, or returning to their home province, under DSWD\'s Assistance to Individuals in Crisis Situation.',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certification', 'Police blotter (if stranded, robbed or lost)', 'Ticket, itinerary or referral (if travelling for treatment)'],
            'process' => ['Submit Request', 'MSWDO Interview', 'Approval', 'Fare or Ticket Release'],
            'fields' => [
                ['key' => 'destination', 'label' => 'Destination', 'type' => 'text', 'required' => true],
                ['key' => 'travel_reason', 'label' => 'Reason for travel', 'type' => 'select', 'required' => true, 'options' => ['Stranded away from home', 'Medical treatment', 'Return to home province', 'Other']],
                ['key' => 'travel_date', 'label' => 'Planned travel date', 'type' => 'date'],
                ['key' => 'companions', 'label' => 'Number of companions', 'type' => 'number'],
            ],
        ],
        [
            'key' => 'tulong_shelter', 'category' => 'Disaster Assistance', 'level' => 'Municipal', 'processingKind' => 'cash',
            'name' => 'Emergency Shelter Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO / MDRRMO', 'amount' => 'Cash or materials, based on damage', 'fee' => 'Free', 'days' => '5-10 working days', 'icon' => 'house-plus',
            'desc' => 'Help to repair or rebuild a house damaged by fire, typhoon, flood, earthquake or landslide.',
            'requirements' => ['Barangay Certification of the incident', 'Fire report from the BFP (if fire)', 'Photos of the damaged house', 'One (1) valid government-issued ID'],
            'process' => ['Submit Request', 'Home Visit and Damage Assessment', 'Approval', 'Cash or Materials Release'],
            'fields' => [
                ['key' => 'incident', 'label' => 'What happened', 'type' => 'select', 'required' => true, 'options' => ['Fire', 'Typhoon', 'Flood', 'Earthquake', 'Landslide', 'Other']],
                ['key' => 'incident_date', 'label' => 'Date of the incident', 'type' => 'date', 'required' => true],
                ['key' => 'damage', 'label' => 'Damage to the house', 'type' => 'select', 'required' => true, 'options' => ['Partially damaged', 'Totally damaged']],
                ['key' => 'house_ownership', 'label' => 'House ownership', 'type' => 'select', 'options' => ['Owner', 'Renter', 'Sharer', 'Informal Settler']],
                ['key' => 'household_members', 'label' => 'Number of household members', 'type' => 'number'],
            ],
        ],
        [
            'key' => 'tulong_assistive_device', 'category' => 'Medical & Health', 'level' => 'Municipal', 'processingKind' => 'goods', 'sensitive' => true,
            'name' => 'Assistive Device for Persons with Disability', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Device, as available', 'fee' => 'Free', 'days' => '10-15 working days', 'icon' => 'accessibility',
            'desc' => 'A wheelchair, crutches, walker, cane, hearing aid or eyeglasses for a person with disability who needs one.',
            'requirements' => ["Doctor's certification of the device needed", 'PWD ID', 'Barangay Certificate of Indigency'],
            'process' => ['Submit Request', 'MSWDO Assessment', 'Approval', 'Device Release'],
            'fields' => [
                ['key' => 'device', 'label' => 'Device needed', 'type' => 'select', 'required' => true, 'allowOther' => true, 'options' => ['Wheelchair', 'Crutches', 'Walker', 'Cane', 'Hearing aid', 'Eyeglasses', 'Other']],
                ['key' => 'pwd_id_number', 'label' => 'PWD ID number', 'type' => 'text'],
                ['key' => 'prescribing_doctor', 'label' => 'Doctor who prescribed it', 'type' => 'text'],
            ],
        ],
        [
            'key' => 'tulong_counseling', 'category' => 'Counseling & Case Management', 'level' => 'Municipal', 'processingKind' => 'counseling', 'sensitive' => true,
            'name' => 'Individual Counseling and Psychosocial Support', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => 'Scheduled with you', 'icon' => 'message-circle-heart',
            'desc' => 'Talk privately with a social worker about a personal, family or emotional problem, a loss, or stress after an incident. What you share stays with the MSWDO.',
            'requirements' => [],
            'process' => ['Submit Request', 'Intake Interview', 'Counseling Sessions', 'Closing'],
            'fields' => [
                ['key' => 'concern', 'label' => 'What would you like help with?', 'type' => 'select', 'required' => true, 'options' => ['Family problem', 'Personal or emotional concern', 'Grief or loss', 'Stress after a disaster or incident', 'Other']],
                ['key' => 'concern_details', 'label' => 'Anything you want the social worker to know first (only what you are comfortable sharing)', 'type' => 'textarea'],
                ['key' => 'preferred_contact', 'label' => 'How should we reach you?', 'type' => 'select', 'required' => true, 'options' => ['Call me', 'Text me', 'I will visit the office']],
                ['key' => 'safe_to_contact', 'label' => 'It is safe for the MSWDO to call or text me at my number.', 'type' => 'checkbox'],
            ],
        ],
        [
            'key' => 'tulong_marriage_counseling', 'category' => 'Marriage & Family', 'level' => 'Municipal', 'processingKind' => 'counseling', 'sensitive' => true,
            'name' => 'Marriage and Family Counseling', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => 'Scheduled with you', 'icon' => 'heart-handshake',
            'desc' => 'Counseling for couples and families going through conflict or difficult changes. Cases of violence are handled by the MSWDO\'s protection services instead.',
            'requirements' => ['One (1) valid government-issued ID of each person attending'],
            'process' => ['Submit Request', 'Intake Interview', 'Counseling Sessions', 'Closing'],
            'fields' => [
                ['key' => 'who_attends', 'label' => 'Who will attend?', 'type' => 'select', 'required' => true, 'options' => ['My spouse or partner and I', 'Our family', 'Only me for now']],
                ['key' => 'partner_name', 'label' => "Spouse or partner's name", 'type' => 'text'],
                ['key' => 'concern', 'label' => 'Main concern', 'type' => 'select', 'required' => true, 'options' => ['Communication', 'Finances', 'Parenting', 'Separation or reconciliation', 'Other']],
                ['key' => 'preferred_contact', 'label' => 'How should we reach you?', 'type' => 'select', 'required' => true, 'options' => ['Call me', 'Text me', 'I will visit the office']],
                ['key' => 'safe_to_contact', 'label' => 'It is safe for the MSWDO to call or text me at my number.', 'type' => 'checkbox'],
            ],
        ],
        [
            'key' => 'tulong_pmo', 'category' => 'Marriage & Family', 'level' => 'Municipal', 'processingKind' => 'session', 'sessionCouple' => true,
            'name' => 'Pre-Marriage Orientation (PMOC)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO / PMOC Team', 'amount' => 'Free (RA 10354)', 'fee' => 'Free', 'days' => 'Next scheduled session', 'icon' => 'heart',
            'desc' => 'The orientation every couple must attend before the Civil Registrar issues a marriage license, on responsible parenthood, family planning, breastfeeding and infant nutrition. Both of you attend; the Certificate of Compliance is sent to your account after the session.',
            'requirements' => ['One (1) valid government-issued ID of each partner', 'Referral slip from the Civil Registrar (if you already applied for a marriage license)'],
            'process' => ['Register as a Couple', 'Scheduled to a Session', 'Attend Together', 'Certificate of Compliance'],
            'certificate' => [
                'title' => 'Certificate of Compliance',
                'subtitle' => 'Pre-Marriage Orientation and Counseling (PMOC)',
                'body' => 'This is to certify that :names attended the Pre-Marriage Orientation on :date at :venue, and received adequate instructions and information on responsible parenthood, family planning, breastfeeding and infant nutrition, as required by Section 15 of Republic Act No. 10354 and Presidential Decree No. 965.',
                'signedBy' => 'team',
            ],
            'fields' => [
                ['key' => 'partner_name', 'label' => "Partner's full name", 'type' => 'text', 'required' => true],
                ['key' => 'partner_birth_date', 'label' => "Partner's date of birth", 'type' => 'date', 'required' => true],
                ['key' => 'partner_sex', 'label' => "Partner's sex", 'type' => 'select', 'required' => true, 'options' => ['Male', 'Female']],
                ['key' => 'partner_address', 'label' => "Partner's address", 'type' => 'text', 'required' => true],
                ['key' => 'partner_contact', 'label' => "Partner's mobile number", 'type' => 'tel'],
                ['key' => 'partner_account_no', 'label' => "Partner's Esperanza account number (if they have one, so they get the certificate too)", 'type' => 'text'],
                ['key' => 'planned_wedding_date', 'label' => 'Planned wedding date (if set)', 'type' => 'date'],
                ['key' => 'license_status', 'label' => 'Marriage license', 'type' => 'select', 'options' => ['Already applied at the Civil Registrar', 'Not yet applied']],
            ],
        ],
        [
            'key' => 'tulong_pmc', 'category' => 'Marriage & Family', 'level' => 'Municipal', 'processingKind' => 'session', 'sessionCouple' => true,
            'name' => 'Pre-Marriage Counseling (Family Code Art. 16)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => 'Next scheduled session', 'icon' => 'heart-handshake',
            'desc' => 'Counseling by a DSWD-accredited counselor, required when one or both of you are 18 to 25 years old. Both of you attend; the Certificate of Marriage Counseling is sent to your account after the session.',
            'requirements' => ['Certificate of Compliance from the Pre-Marriage Orientation', 'One (1) valid government-issued ID of each partner'],
            'process' => ['Register as a Couple', 'Scheduled to a Session', 'Attend Together', 'Certificate of Marriage Counseling'],
            'certificate' => [
                'title' => 'Certificate of Marriage Counseling',
                'subtitle' => 'Article 16, Family Code of the Philippines',
                'body' => 'This is to certify that :names have undergone pre-marriage counseling on :date. This certification is issued as a pre-requisite for securing the marriage license of the above couple as provided for in Article 16 of the Family Code.',
                'signedBy' => 'counselor',
                'requiresAccreditation' => true,
            ],
            'fields' => [
                ['key' => 'partner_name', 'label' => "Partner's full name", 'type' => 'text', 'required' => true],
                ['key' => 'partner_birth_date', 'label' => "Partner's date of birth", 'type' => 'date', 'required' => true],
                ['key' => 'partner_sex', 'label' => "Partner's sex", 'type' => 'select', 'required' => true, 'options' => ['Male', 'Female']],
                ['key' => 'partner_address', 'label' => "Partner's address", 'type' => 'text', 'required' => true],
                ['key' => 'partner_contact', 'label' => "Partner's mobile number", 'type' => 'tel'],
                ['key' => 'partner_account_no', 'label' => "Partner's Esperanza account number (if they have one, so they get the certificate too)", 'type' => 'text'],
                ['key' => 'pmo_certificate_no', 'label' => 'Certificate of Compliance number (if from another municipality)', 'type' => 'text'],
                ['key' => 'planned_wedding_date', 'label' => 'Planned wedding date (if set)', 'type' => 'date'],
            ],
        ],
        [
            'key' => 'tulong_pes', 'category' => 'Marriage & Family', 'level' => 'Municipal', 'processingKind' => 'session',
            'name' => 'Parent Effectiveness Service (PES)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => 'Next scheduled session', 'icon' => 'users-round',
            'desc' => 'Group sessions for parents and caregivers on raising children: child development, positive discipline, and family communication. A Certificate of Completion is sent to your account after the session.',
            'requirements' => [],
            'process' => ['Register', 'Scheduled to a Session', 'Attend', 'Certificate of Completion'],
            'certificate' => [
                'title' => 'Certificate of Completion',
                'subtitle' => 'Parent Effectiveness Service',
                'body' => 'This is to certify that :names completed the Parent Effectiveness Service session on :date at :venue, conducted by the Municipal Social Welfare and Development Office.',
                'signedBy' => 'team',
            ],
            'fields' => [
                ['key' => 'role_in_family', 'label' => 'You are the child\'s', 'type' => 'select', 'required' => true, 'options' => ['Mother', 'Father', 'Grandparent', 'Guardian or caregiver']],
                ['key' => 'children_count', 'label' => 'Number of children in your care', 'type' => 'number'],
                ['key' => 'youngest_child_age', 'label' => 'Age of the youngest child', 'type' => 'number'],
            ],
        ],
        [
            'key' => 'tulong_social_case_study', 'category' => 'Certifications & Referrals', 'level' => 'Municipal', 'processingKind' => 'referral', 'sensitive' => true,
            'name' => 'Social Case Study Report', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => '3-7 working days', 'icon' => 'file-user',
            'desc' => 'A report prepared by a registered social worker on your family\'s situation, often required by hospitals, the PCSO, the DSWD Field Office, courts or schools.',
            'requirements' => ['Barangay Certificate of Indigency', "The requesting agency's form or letter (if any)", 'Medical documents (if for medical assistance)', 'One (1) valid government-issued ID'],
            'process' => ['Submit Request', 'Interview and Home Visit', 'Report Prepared and Signed', 'Release'],
            'fields' => [
                ['key' => 'purpose', 'label' => 'The report is for', 'type' => 'select', 'required' => true, 'allowOther' => true, 'options' => ['Hospital or medical assistance', 'PCSO', 'DSWD Field Office', 'Court', 'School or scholarship', 'Other']],
                ['key' => 'requesting_agency', 'label' => 'Name of the agency or office asking for it', 'type' => 'text', 'required' => true],
                ['key' => 'purpose_details', 'label' => 'More about why it is needed', 'type' => 'textarea'],
            ],
        ],
        [
            'key' => 'tulong_referral_letter', 'category' => 'Certifications & Referrals', 'level' => 'Municipal', 'processingKind' => 'referral', 'sensitive' => true,
            'name' => 'Referral or Guarantee Letter', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Letter to the agency or provider', 'fee' => 'Free', 'days' => '1-3 working days', 'icon' => 'mail-check',
            'desc' => 'A letter from the MSWDO referring you to another agency (hospital, PCSO, DSWD, PAO, shelter), or guaranteeing payment to a hospital, pharmacy or funeral home.',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certification', 'Supporting documents (bill, quotation, prescription, blotter or medical certificate)'],
            'process' => ['Submit Request', 'MSWDO Interview', 'Letter Prepared and Signed', 'Release'],
            'fields' => [
                ['key' => 'letter_type', 'label' => 'Letter needed', 'type' => 'select', 'required' => true, 'options' => ['Referral letter', 'Guarantee letter']],
                ['key' => 'addressed_to', 'label' => 'Addressed to (agency, hospital or provider)', 'type' => 'text', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'textarea', 'required' => true],
                ['key' => 'amount_requested', 'label' => 'Amount on the bill or quotation (₱, for a guarantee letter)', 'type' => 'number'],
            ],
        ],
        [
            'key' => 'tulong_cdc', 'category' => 'Children & Youth', 'level' => 'Municipal', 'processingKind' => 'registration',
            'name' => 'Child Development Center Enrollment', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free enrollment', 'fee' => 'Free', 'days' => '1-3 working days', 'icon' => 'baby',
            'desc' => 'Enroll a child aged 3 to 4 in the barangay\'s Child Development Center (day care), which includes supplementary feeding.',
            'requirements' => ["Child's PSA birth certificate", "Child's immunization card", "Parent or guardian's valid ID"],
            'process' => ['Submit Enrollment', 'Child Development Worker Review', 'Enrollment Confirmed'],
            'fields' => [
                ['key' => 'child_name', 'label' => "Child's full name", 'type' => 'text', 'required' => true],
                ['key' => 'child_birth_date', 'label' => "Child's date of birth", 'type' => 'date', 'required' => true],
                ['key' => 'child_sex', 'label' => "Child's sex", 'type' => 'select', 'required' => true, 'options' => ['Male', 'Female']],
                ['key' => 'relationship', 'label' => 'You are the child\'s', 'type' => 'select', 'required' => true, 'options' => ['Mother', 'Father', 'Grandparent', 'Guardian']],
                ['key' => 'center', 'label' => 'Child Development Center (barangay)', 'type' => 'text', 'required' => true],
                ['key' => 'health_notes', 'label' => 'Allergies or health needs the center should know', 'type' => 'textarea'],
            ],
        ],
        [
            'key' => 'tulong_pfa', 'category' => 'Counseling & Case Management', 'level' => 'Municipal', 'processingKind' => 'counseling', 'staffOnly' => true, 'sensitive' => true,
            'name' => 'Psychological First Aid / Stress Debriefing', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Free service', 'fee' => 'Free', 'days' => 'Same day', 'icon' => 'life-buoy',
            'desc' => 'Psychological first aid and stress debriefing for people affected by a disaster or a critical incident, given by the MSWDO in the field or at an evacuation center.',
            'requirements' => [],
            'process' => ['Recorded by the MSWDO', 'Session', 'Referral if Needed', 'Closing'],
            'fields' => [
                ['key' => 'incident', 'label' => 'Incident or disaster', 'type' => 'text', 'required' => true],
                ['key' => 'setting', 'label' => 'Where it was given', 'type' => 'select', 'options' => ['Evacuation center', 'Home visit', 'MSWDO office', 'Field']],
                ['key' => 'referred_by', 'label' => 'Referred by', 'type' => 'text'],
            ],
        ],
        [
            'key' => 'tulong_cicl', 'category' => 'Children & Youth', 'level' => 'Municipal', 'processingKind' => 'counseling', 'staffOnly' => true, 'sensitive' => true,
            'name' => 'Children in Conflict with the Law (Intervention and Diversion)', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Case management', 'fee' => 'Free', 'days' => 'Per intervention plan', 'icon' => 'shield-half',
            'desc' => 'Case management of a child in conflict with the law under RA 9344 as amended by RA 10630: initial assessment, intervention or diversion plan, and monitoring. Records are confidential (RA 9344 s. 43).',
            'requirements' => ['Referral or initial contact report (PNP, barangay or court)', "Child's birth certificate or proof of age", 'Medical certificate (if any)', 'School records (if any)'],
            'process' => ['Referral Received', 'Initial Assessment', 'Intervention or Diversion', 'Closing Summary'],
            'fields' => [
                ['key' => 'referred_by', 'label' => 'Referred by', 'type' => 'select', 'required' => true, 'allowOther' => true, 'options' => ['PNP', 'Barangay (BCPC)', 'Court', 'School', 'Parent or guardian', 'Other']],
                ['key' => 'referral_reference', 'label' => 'Referral or blotter number', 'type' => 'text'],
                ['key' => 'referral_date', 'label' => 'Date referred', 'type' => 'date', 'required' => true],
                ['key' => 'parent_present', 'label' => 'Parent or guardian present at intake', 'type' => 'select', 'options' => ['Yes', 'No']],
            ],
        ],
        [
            'key' => 'tulong_child_protection', 'category' => 'Children & Youth', 'level' => 'Municipal', 'processingKind' => 'counseling', 'staffOnly' => true, 'sensitive' => true,
            'name' => 'Children at Risk and Child Protection', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Case management', 'fee' => 'Free', 'days' => 'Per case plan', 'icon' => 'shield-plus',
            'desc' => 'Protection and case management for a child who is abused, neglected, exploited, abandoned or at risk (RA 7610, RA 11596): rescue, protective custody or referral, case conferences and monitoring. Records are confidential.',
            'requirements' => ['Referral or blotter (PNP, barangay BCPC, school or hospital)', "Child's birth certificate or proof of age", 'Medico-legal report (if any)'],
            'process' => ['Referral Received', 'Intake and Safety Assessment', 'Intervention and Referral', 'Closing Summary'],
            'fields' => [
                ['key' => 'referred_by', 'label' => 'Referred by', 'type' => 'select', 'required' => true, 'allowOther' => true, 'options' => ['PNP-WCPD', 'Barangay (BCPC)', 'School', 'Hospital', 'Neighbor or relative', 'Other']],
                ['key' => 'referral_reference', 'label' => 'Referral or blotter number', 'type' => 'text'],
                ['key' => 'referral_date', 'label' => 'Date referred', 'type' => 'date', 'required' => true],
                ['key' => 'protective_custody', 'label' => 'Child placed in protective custody', 'type' => 'select', 'options' => ['No', 'Yes, with relatives', 'Yes, in a shelter or center']],
            ],
        ],
        [
            'key' => 'tulong_vawc', 'category' => 'Women & Children Protection', 'level' => 'Municipal', 'processingKind' => 'counseling', 'staffOnly' => true, 'sensitive' => true,
            'name' => 'VAWC Assistance and Referral', 'office' => 'Municipal Social Welfare & Development Office', 'office_short' => 'MSWDO', 'amount' => 'Case management', 'fee' => 'Free', 'days' => 'Same day', 'icon' => 'shield-alert',
            'desc' => 'Help for women and their children who suffer violence (RA 9262) or are in especially difficult circumstances: crisis intervention, safety planning, counseling, and referral to the PNP-WCPD, medico-legal, a shelter, a Barangay Protection Order or the PAO. Records are confidential (RA 9262 s. 44).',
            'requirements' => ['Referral from the PNP-WCPD or the Barangay VAW Desk (if any)', 'Medico-legal report (if any)'],
            'process' => ['Intake and Safety Assessment', 'Crisis Intervention', 'Referral and Counseling', 'Closing Summary'],
            'fields' => [
                ['key' => 'referred_by', 'label' => 'Referred by', 'type' => 'select', 'required' => true, 'allowOther' => true, 'options' => ['Walk-in', 'PNP-WCPD', 'Barangay VAW Desk', 'Hospital', 'Other']],
                ['key' => 'referral_reference', 'label' => 'Referral or blotter number', 'type' => 'text'],
                ['key' => 'children_involved', 'label' => 'Children involved', 'type' => 'number'],
                ['key' => 'safe_contact', 'label' => 'A safe way to contact her (never the shared phone if unsafe)', 'type' => 'text'],
            ],
        ],
    ],
];
