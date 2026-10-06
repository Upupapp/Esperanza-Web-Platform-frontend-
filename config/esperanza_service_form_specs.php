<?php

// Data-driven Dokyu/Tulong form field specs, ported field-for-field from the
// Esperanza Mobile app's own ServiceFormSpec definitions
// (lib/models/service_form_spec.dart, lib/services/mock_catalog.dart) —
// sourced from the real Esperanza forms audited in that project's
// docs/DOKYU_TULONG_FORM_AUDIT.md. Almost every service below has both a
// real mobile formSpec AND existing mock requests on the Web Admin side;
// every other Dokyu/Tulong item keeps the generic Purpose + Attachments
// preview, exactly mirroring how mobile itself falls back to
// NewRequestScreen for any catalog item with no sourced formSpec. One
// exception: Certificate of Fetal Death has a sourced formSpec but no mock
// request row anywhere — added for completeness/future use rather than
// fabricating a fetal-death scenario for the one demo citizen (Cristy).
//
// Keyed by this project's own request `type` string (several admin type
// strings map to the same mobile catalog item — e.g. "Barangay Residency"
// and "Certificate of Residency" are the same form under two names already
// used in the admin's own mock data).
//
// Field `type` values mirror ServiceFieldType exactly: text, textarea,
// number, date, select, multiselect, checkbox, derivedAge (a read-only
// "N years old" computed from another field, never independently answered).

return [
    'Barangay Clearance' => [
        'source' => 'BRGY.CLEARANCE NEW.docx',
        'steps' => [
            ['label' => 'Clearance Details', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => [
                    'Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal ID Application', 'Bank Requirement', 'NBI Clearance', 'Loan Purpose', 'Medical / Financial Assistance', 'Others',
                ]],
            ]],
        ],
    ],

    'Certificate of Residency' => [
        'source' => 'BRGY. RESIDENCY.docx',
        'steps' => [
            ['label' => 'Residency Details', 'fields' => [
                ['key' => 'residencyType', 'label' => 'Residency Type', 'type' => 'select', 'options' => ['Permanent Resident', 'Temporary Resident', 'Renter / Lessee']],
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => [
                    'Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal ID Application', 'Bank Requirement', 'NBI Clearance', 'Loan Purpose', 'Medical / Financial Assistance', 'Others',
                ]],
            ]],
        ],
    ],
    // Same form as above, just requested under the admin's own older label.
    'Barangay Residency' => ['alias' => 'Certificate of Residency'],

    'Certificate of Indigency' => [
        'source' => 'BRGY.INDIGENCY 2024-1.docx',
        'steps' => [
            ['label' => 'Indigency Details', 'fields' => [
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => [
                    'Medical Assistance', 'Financial Assistance', 'Burial Assistance', 'Educational Assistance (e.g. Scholarship)', 'Others',
                ]],
            ]],
        ],
    ],
    'Barangay Indigency' => ['alias' => 'Certificate of Indigency'],

    'Barangay Business Clearance' => [
        'source' => 'BRGY. BUSINESS CLEARANCE.docx',
        'steps' => [
            ['label' => 'Business Details', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'businessName', 'label' => 'Business Name', 'type' => 'text'],
                ['key' => 'businessNature', 'label' => 'Nature of Business', 'type' => 'text'],
                ['key' => 'yearsOperating', 'label' => 'Years Operating', 'type' => 'number'],
                ['key' => 'capitalAmount', 'label' => 'Capital Amount (₱)', 'type' => 'number'],
            ]],
        ],
    ],

    'Barangay Certification (General Purpose)' => [
        'source' => 'Barangay Certification.docx / PSA CERTIFICATION.docx',
        'steps' => [
            ['label' => 'Certification Details', 'fields' => [
                ['key' => 'purpose', 'label' => 'State the purpose of this certification', 'type' => 'textarea', 'hint' => 'e.g. proof of residency, unemployment for TESDA, property ownership, event permit...'],
            ]],
        ],
    ],

    'First Time Jobseeker Certificate (RA 11261)' => [
        'source' => 'First Time Job Seeker Certificate.docx',
        'steps' => [
            ['label' => 'Applicant Details', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'confirmFirstTime', 'label' => 'I confirm this is my first time seeking employment and I have never been previously employed.', 'type' => 'checkbox'],
            ]],
        ],
    ],

    'Educational Assistance' => [
        'source' => 'Placeholders/Tulong/2F5.Application-for-Scholarship.pdf',
        'steps' => [
            ['label' => 'Student Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married']],
                ['key' => 'schoolName', 'label' => 'School Name', 'type' => 'text'],
                // A choice since the Esperanza QA of 2026-10-07 (the app's own
                // MockCatalog.schoolGradeLevels + collegeYearLevels); the app asks
                // for the course, and requires it, only for a college year.
                ['key' => 'yearOrGradeLevel', 'label' => 'Year / Grade Level', 'type' => 'select', 'options' => [
                    'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                    'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
                    '1st Year College', '2nd Year College', '3rd Year College', '4th Year College', '5th Year College',
                ]],
                ['key' => 'degreeProgramOrCourse', 'label' => 'Degree Program / Course (college)', 'type' => 'text', 'required' => false],
                ['key' => 'lastSchoolAverageGrade', 'label' => 'Last School Year Average Grade', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Family Background', 'fields' => [
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text', 'required' => false],
                ['key' => 'fatherOccupation', 'label' => "Father's Occupation", 'type' => 'text', 'required' => false],
                // One income per parent (Esperanza QA, 2026-10-07). A request filed
                // before carries one parentsMonthlyIncome, which the review lists
                // under Other details as it does any answer this spec does not name.
                ['key' => 'fatherMonthlyIncome', 'label' => "Father's Monthly Income (₱)", 'type' => 'number', 'required' => false],
                ['key' => 'motherName', 'label' => "Mother's Name", 'type' => 'text', 'required' => false],
                ['key' => 'motherOccupation', 'label' => "Mother's Occupation", 'type' => 'text', 'required' => false],
                ['key' => 'motherMonthlyIncome', 'label' => "Mother's Monthly Income (₱)", 'type' => 'number', 'required' => false],
            ]],
            ['label' => 'Additional Information', 'fields' => [
                ['key' => 'communityInvolvement', 'label' => 'Community / School Involvement', 'type' => 'textarea', 'required' => false],
                ['key' => 'postGraduationPlans', 'label' => 'Plans After Graduation', 'type' => 'textarea', 'required' => false],
            ]],
        ],
    ],
    'Educational Assistance (Continuing Studies)' => ['alias' => 'Educational Assistance'],

    'Barangay Certification (Late Registration)' => [
        'source' => 'BRGY.CERTIFICATION LATE REGISTRATION.docx',
        'steps' => [
            ['label' => "Person's Details", 'fields' => [
                ['key' => 'personFullName', 'label' => 'Full Name', 'type' => 'text'],
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text'],
                ['key' => 'motherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text'],
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text'],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'required' => false],
                ['key' => 'spouseName', 'label' => "Spouse's Name (if married)", 'type' => 'text', 'required' => false],
            ]],
        ],
    ],

    'Barangay Certification (Registration of Death)' => [
        'source' => 'BRGY.CERTIFICATION REGISTRATION OF DEATH.docx',
        'steps' => [
            ['label' => "Deceased's Details", 'fields' => [
                ['key' => 'deceasedFullName', 'label' => 'Full Name', 'type' => 'text'],
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text', 'required' => false],
                ['key' => 'motherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text', 'required' => false],
                ['key' => 'dateOfDeath', 'label' => 'Date of Death', 'type' => 'date'],
                ['key' => 'placeOfDeath', 'label' => 'Place of Death', 'type' => 'text'],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
            ]],
        ],
    ],

    'Senior Citizen ID' => [
        'source' => 'MSWD - Senior Citizen Application Form.docx, OSCA Membership Application',
        'steps' => [
            ['label' => 'Personal Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'educationalAttainment', 'label' => 'Educational Attainment', 'type' => 'select', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                ['key' => 'presentOccupation', 'label' => 'Present Occupation', 'type' => 'text', 'required' => false],
                ['key' => 'annualIncome', 'label' => 'Annual Income (₱)', 'type' => 'number', 'required' => false],
                ['key' => 'receivingPension', 'label' => 'Currently Receiving a Pension', 'type' => 'checkbox', 'required' => false],
                ['key' => 'philsysIdNumber', 'label' => 'PhilSys ID Number', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Government Service Record', 'description' => 'Fill in if you previously worked in government.', 'fields' => [
                ['key' => 'lastGovtOffice', 'label' => 'Last Government Office', 'type' => 'text', 'required' => false],
                ['key' => 'lastGovtPosition', 'label' => 'Position', 'type' => 'text', 'required' => false],
                ['key' => 'lastGovtYear', 'label' => 'Year', 'type' => 'text', 'required' => false],
            ]],
        ],
    ],

    'Solo Parent Cash Assistance' => [
        'source' => 'MSWD - SOLO Parent Application Form.xlsx (DSWD Annex B, 2023)',
        'steps' => [
            ['label' => 'Identifying Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'educationalAttainment', 'label' => 'Educational Attainment', 'type' => 'select', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated', 'Annulled']],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'required' => false],
                ['key' => 'monthlyIncome', 'label' => 'Monthly Income (₱)', 'type' => 'number', 'required' => false],
                ['key' => 'employmentStatus', 'label' => 'Employment Status', 'type' => 'select', 'options' => ['Employed', 'Self-Employed', 'Unemployed']],
                ['key' => 'isPantawidBeneficiary', 'label' => '4Ps (Pantawid) Beneficiary', 'type' => 'checkbox', 'required' => false],
                ['key' => 'isIndigenousPerson', 'label' => 'Indigenous Person / IP Member', 'type' => 'checkbox', 'required' => false],
            ]],
            ['label' => 'Family Composition', 'description' => 'List your children / dependents.', 'fields' => [
                ['key' => 'familyComposition', 'label' => 'Children / Dependents (name, age, relationship)', 'type' => 'textarea'],
            ]],
            ['label' => 'Classification', 'description' => 'Select the circumstance that best describes your situation as a solo parent.', 'fields' => [
                ['key' => 'soloParentClassification', 'label' => 'Circumstance of Being a Solo Parent', 'type' => 'select', 'options' => [
                    'Birth from rape / exploitation', 'Spouse of OFW abroad', 'Widow / widower',
                    'Unmarried parent who chose to keep the child', 'Spouse of a person detained or imprisoned',
                    'Legal guardian / adoptive / foster parent', 'Spouse with permanent incapacity',
                    'Relative caring for a child within the 4th degree of consanguinity', 'Legal or de facto separation',
                    'Pregnant woman who will raise the child alone', 'Annulment / nullity of marriage',
                    'Abandonment by spouse for at least 1 year',
                ]],
            ]],
            ['label' => 'Needs & Emergency Contact', 'fields' => [
                ['key' => 'needsOrProblems', 'label' => 'Needs / Problems You Would Like Assistance With', 'type' => 'textarea', 'required' => false],
                ['key' => 'emergencyContactName', 'label' => 'Emergency Contact Name', 'type' => 'text'],
                ['key' => 'emergencyContactNumber', 'label' => 'Emergency Contact Number', 'type' => 'text'],
            ]],
        ],
    ],

    'Application for Marriage License' => [
        'source' => 'Office of the Municipal Civil Registrar — Application for Marriage License (PSA/LCR standard form)',
        'steps' => [
            ['label' => "First Party's Information", 'fields' => [
                ['key' => 'party1FullName', 'label' => 'Full Name', 'type' => 'text'],
                ['key' => 'party1DateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'party1PlaceOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'party1Citizenship', 'label' => 'Citizenship', 'type' => 'text'],
                ['key' => 'party1Residence', 'label' => 'Residence', 'type' => 'text'],
                ['key' => 'party1Religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['key' => 'party1CivilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Widowed', 'Divorced / Annulled']],
                ['key' => 'party1FatherName', 'label' => "Father's Name", 'type' => 'text'],
                ['key' => 'party1MotherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text'],
            ]],
            ['label' => "Second Party's Information", 'fields' => [
                ['key' => 'party2FullName', 'label' => 'Full Name', 'type' => 'text'],
                ['key' => 'party2DateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'party2PlaceOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'party2Citizenship', 'label' => 'Citizenship', 'type' => 'text'],
                ['key' => 'party2Residence', 'label' => 'Residence', 'type' => 'text'],
                ['key' => 'party2Religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['key' => 'party2CivilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Widowed', 'Divorced / Annulled']],
                ['key' => 'party2FatherName', 'label' => "Father's Name", 'type' => 'text'],
                ['key' => 'party2MotherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text'],
            ]],
            ['label' => 'Consent / Advice', 'description' => 'Required only if either party is 18–25 years old.', 'fields' => [
                ['key' => 'consentGuardianName', 'label' => 'Name of person giving consent/advice', 'type' => 'text', 'required' => false],
                ['key' => 'consentGuardianRelationship', 'label' => 'Relationship to applicant', 'type' => 'text', 'required' => false],
            ]],
        ],
    ],

    'Certified Copy of Marriage Certificate' => [
        'source' => 'Office of the Municipal Civil Registrar — Request for Certified True Copy of Marriage Certificate',
        'steps' => [
            ['label' => 'Marriage Record Information', 'fields' => [
                ['key' => 'husbandFullName', 'label' => "Husband's Full Name", 'type' => 'text'],
                ['key' => 'wifeFullName', 'label' => "Wife's Full Name", 'type' => 'text'],
                ['key' => 'dateOfMarriage', 'label' => 'Date of Marriage', 'type' => 'date'],
                ['key' => 'placeOfMarriage', 'label' => 'Place of Marriage', 'type' => 'text', 'hint' => 'City/Municipality, Province'],
                ['key' => 'registryNumber', 'label' => 'Registry Number', 'type' => 'text', 'required' => false, 'hint' => 'If known'],
                ['key' => 'numberOfCopies', 'label' => 'Number of Copies', 'type' => 'number', 'required' => false, 'hint' => 'Defaults to 1 if left blank'],
            ]],
        ],
    ],

    'Delayed Registration of Birth' => [
        'source' => 'Office of the Municipal Civil Registrar — Delayed Registration of Birth (RA 3753)',
        'steps' => [
            ['label' => 'Birth Details', 'fields' => [
                ['key' => 'childFullName', 'label' => "Child's Full Name", 'type' => 'text'],
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'fatherFullName', 'label' => "Father's Full Name", 'type' => 'text'],
                ['key' => 'motherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text'],
                ['key' => 'reasonForDelay', 'label' => 'Reason for Delayed Registration', 'type' => 'textarea'],
            ]],
        ],
    ],

    'Delayed Registration of Death' => [
        'source' => 'Office of the Municipal Civil Registrar — Delayed Registration of Death',
        'steps' => [
            ['label' => 'Death Details', 'fields' => [
                ['key' => 'deceasedFullName', 'label' => "Deceased's Full Name", 'type' => 'text'],
                ['key' => 'dateOfDeath', 'label' => 'Date of Death', 'type' => 'date'],
                ['key' => 'placeOfDeath', 'label' => 'Place of Death', 'type' => 'text'],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['key' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text', 'required' => false],
                ['key' => 'motherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text', 'required' => false],
                ['key' => 'reasonForDelay', 'label' => 'Reason for Delayed Registration', 'type' => 'textarea'],
            ]],
        ],
    ],

    'Certificate of Fetal Death' => [
        // No mock request row anywhere uses this key yet — added for
        // completeness/future use since Dokyu's own catalog defines it (see
        // this file's own header comment above); deliberately not paired
        // with a fabricated fetal-death scenario for the one demo citizen.
        'source' => 'Office of the Municipal Civil Registrar — Certificate of Fetal Death (PSA Municipal Form No. 103)',
        'steps' => [
            ['label' => 'Fetal Information', 'fields' => [
                ['key' => 'fetusFullName', 'label' => "Fetus's Name", 'type' => 'text', 'required' => false, 'hint' => 'Leave blank if the fetus was not given a name'],
                ['key' => 'fetusSex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female', 'Undetermined']],
                ['key' => 'dateOfDelivery', 'label' => 'Date of Delivery', 'type' => 'date'],
                ['key' => 'placeOfDelivery', 'label' => 'Place of Delivery', 'type' => 'text', 'hint' => 'Hospital/Clinic/Institution, Barangay, City/Municipality, Province'],
                ['key' => 'typeOfDelivery', 'label' => 'Type of Delivery', 'type' => 'select', 'options' => ['Single', 'Twin', 'Triplet', 'Quadruplet', 'Other']],
                ['key' => 'multipleDeliveryOrder', 'label' => 'If Multiple Delivery, Fetus Was', 'type' => 'select', 'options' => ['First', 'Second', 'Third', 'Fourth', 'Other']],
                ['key' => 'methodOfDelivery', 'label' => 'Method of Delivery', 'type' => 'text', 'hint' => 'e.g. Normal spontaneous vertex; specify if others'],
                ['key' => 'birthOrder', 'label' => 'Birth Order', 'type' => 'number', 'required' => false, 'hint' => 'Live births and fetal deaths including this delivery'],
                ['key' => 'weightOfFetus', 'label' => 'Weight of Fetus (grams)', 'type' => 'number', 'required' => false],
            ]],
            ['label' => "Mother's Information", 'fields' => [
                ['key' => 'motherMaidenName', 'label' => "Mother's Maiden Name", 'type' => 'text'],
                ['key' => 'motherCitizenship', 'label' => "Mother's Citizenship", 'type' => 'text'],
                ['key' => 'motherReligion', 'label' => "Mother's Religion", 'type' => 'text', 'required' => false],
                ['key' => 'motherOccupation', 'label' => "Mother's Occupation", 'type' => 'text', 'required' => false],
                ['key' => 'motherAgeAtDelivery', 'label' => "Mother's Age at the Time of Delivery", 'type' => 'number'],
                ['key' => 'childrenBornAlive', 'label' => 'Total Number of Children Born Alive', 'type' => 'number', 'required' => false],
                ['key' => 'childrenStillLiving', 'label' => 'Number of Children Still Living', 'type' => 'number', 'required' => false],
                ['key' => 'childrenBornAliveNowDead', 'label' => 'Number of Children Born Alive but Now Dead', 'type' => 'number', 'required' => false],
                ['key' => 'motherResidence', 'label' => "Mother's Residence", 'type' => 'text', 'hint' => 'House No., Street, Barangay, City/Municipality, Province'],
            ]],
            ['label' => "Father's Information", 'description' => 'Leave blank if not applicable.', 'fields' => [
                ['key' => 'fatherFullName', 'label' => "Father's Name", 'type' => 'text', 'required' => false],
                ['key' => 'fatherCitizenship', 'label' => "Father's Citizenship", 'type' => 'text', 'required' => false],
                ['key' => 'fatherReligion', 'label' => "Father's Religion", 'type' => 'text', 'required' => false],
                ['key' => 'fatherOccupation', 'label' => "Father's Occupation", 'type' => 'text', 'required' => false],
                ['key' => 'fatherAgeAtDelivery', 'label' => "Father's Age at the Time of Delivery", 'type' => 'number', 'required' => false],
                ['key' => 'parentsMarriageDate', 'label' => 'Date of Marriage of Parents', 'type' => 'date', 'required' => false],
                ['key' => 'parentsMarriagePlace', 'label' => 'Place of Marriage of Parents', 'type' => 'text', 'required' => false, 'hint' => 'City/Municipality, Province, Country'],
            ]],
            ['label' => 'Supporting Information', 'fields' => [
                ['key' => 'fetusDiedWhen', 'label' => 'Fetus Died', 'type' => 'select', 'options' => ['Before Labor', 'During Labor/Delivery', 'Unknown']],
                ['key' => 'lengthOfPregnancy', 'label' => 'Length of Pregnancy (completed weeks)', 'type' => 'number', 'hint' => "Gestational age — not the mother's or father's age"],
                ['key' => 'fetusAttendedStatus', 'label' => 'Was the Delivery Attended', 'type' => 'select', 'options' => ['Attended', 'Not Attended']],
                ['key' => 'attendedByName', 'label' => 'Attended By', 'type' => 'text', 'hint' => 'Physician, Nurse, Midwife, Hilot/Traditional Birth Attendant, etc.'],
                ['key' => 'causeOfFetalDeath', 'label' => 'Cause of Fetal Death', 'type' => 'textarea', 'hint' => 'As known to you — describe in your own words'],
                ['key' => 'reasonForDelay', 'label' => 'Reason for the Delay in Registering This Fetal Death', 'type' => 'textarea'],
                ['key' => 'corpseDisposal', 'label' => 'Corpse Disposal', 'type' => 'select', 'required' => false, 'options' => ['Burial', 'Cremation', 'Other']],
                ['key' => 'placeOfBurialOrCremation', 'label' => 'Place of Burial/Cremation', 'type' => 'text', 'required' => false],
                ['key' => 'dateOfBurialOrCremation', 'label' => 'Date of Burial/Cremation', 'type' => 'date', 'required' => false],
            ]],
        ],
    ],

    'Pet Registration' => [
        'source' => 'Municipal Agriculture Office — Pet Registration Form (Anti-Rabies Ordinance)',
        'steps' => [
            ['label' => 'Pet Information', 'fields' => [
                ['key' => 'petName', 'label' => "Pet's Name", 'type' => 'text'],
                ['key' => 'species', 'label' => 'Species', 'type' => 'select', 'options' => ['Dog', 'Cat', 'Other']],
                ['key' => 'breed', 'label' => 'Breed', 'type' => 'text', 'required' => false],
                ['key' => 'colorMarkings', 'label' => 'Color / Markings', 'type' => 'text'],
                ['key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'dobOrApproxAge', 'label' => 'Date of Birth / Approximate Age', 'type' => 'text'],
                ['key' => 'microchipNumber', 'label' => 'Microchip Number', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Health Information', 'fields' => [
                ['key' => 'lastRabiesVaccinationDate', 'label' => 'Last Rabies Vaccination Date', 'type' => 'date', 'required' => false],
                ['key' => 'otherVaccinations', 'label' => 'Other Vaccinations', 'type' => 'textarea', 'required' => false],
                ['key' => 'spayedNeutered', 'label' => 'Spayed / Neutered', 'type' => 'select', 'options' => ['Yes', 'No', 'Unknown']],
            ]],
            ['label' => 'Owner & Emergency Contact', 'fields' => [
                ['key' => 'ownerAddress', 'label' => "Owner's Address", 'type' => 'text'],
                ['key' => 'emergencyContactName', 'label' => 'Emergency Contact Name', 'type' => 'text'],
                ['key' => 'emergencyContactNumber', 'label' => 'Emergency Contact Number', 'type' => 'text'],
                ['key' => 'additionalInfo', 'label' => 'Additional Information', 'type' => 'textarea', 'required' => false],
            ]],
        ],
    ],

    'Locational Clearance' => [
        'source' => 'Municipal Planning & Development Office (MPDO) — Locational Clearance Application',
        'steps' => [
            ['label' => 'Applicant & Representative', 'fields' => [
                ['key' => 'applicantType', 'label' => 'Applicant Type', 'type' => 'select', 'options' => ['Individual', 'Corporation', 'Partnership', 'Other']],
                ['key' => 'authorizedRepName', 'label' => "Authorized Representative's Name", 'type' => 'text', 'required' => false],
                ['key' => 'authorizedRepAddress', 'label' => "Authorized Representative's Address", 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Project Details', 'fields' => [
                ['key' => 'natureOfApplication', 'label' => 'Nature of Application', 'type' => 'select', 'options' => ['New Development', 'Improvement / Renovation', 'Change of Use', 'Other']],
                ['key' => 'projectTitle', 'label' => 'Project Title', 'type' => 'text'],
                ['key' => 'projectLocation', 'label' => 'Project Location', 'type' => 'text'],
                ['key' => 'floorArea', 'label' => 'Floor Area (sqm)', 'type' => 'number', 'required' => false],
                ['key' => 'buildingHeight', 'label' => 'Building Height (m)', 'type' => 'number', 'required' => false],
                ['key' => 'numberOfStoreys', 'label' => 'Number of Storeys', 'type' => 'number', 'required' => false],
            ]],
            ['label' => 'Lot Information', 'fields' => [
                ['key' => 'lotArea', 'label' => 'Lot Area (sqm)', 'type' => 'number'],
                ['key' => 'titleNumber', 'label' => 'TCT / OCT No.', 'type' => 'text'],
                ['key' => 'rightOverLand', 'label' => 'Right Over Land', 'type' => 'select', 'options' => ['Owner', 'Lessee', 'Other']],
                ['key' => 'projectTenure', 'label' => 'Project Tenure', 'type' => 'text', 'required' => false],
                ['key' => 'zoningClassification', 'label' => 'Land Use / Zoning Classification', 'type' => 'multiselect', 'options' => [
                    'Residential', 'Commercial', 'Industrial', 'Institutional', 'Agricultural', 'Open Space', 'Forestry', 'Other',
                ]],
            ]],
        ],
    ],

    'PWD Registration (PRPWD)' => [
        'source' => 'Persons with Disability Affairs Office — PRPWD Application Form (DOH/NCDA standard form)',
        'steps' => [
            ['label' => 'Personal Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'bloodType', 'label' => 'Blood Type', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Disability Information', 'fields' => [
                ['key' => 'disabilityType', 'label' => 'Type of Disability', 'type' => 'multiselect', 'options' => [
                    'Deaf / Hard of Hearing', 'Intellectual Disability', 'Learning Disability', 'Mental Disability', 'Orthopedic Disability', 'Physical Disability (Non-Orthopedic)', 'Psychosocial Disability', 'Speech and Language Impairment', 'Visual Disability',
                ]],
                ['key' => 'causeOfDisability', 'label' => 'Cause of Disability', 'type' => 'multiselect', 'options' => [
                    'Acquired', 'Cancer', 'Chronic Illness', 'Congenital / Inborn', 'Injury', 'Rare Disease', 'Autism Spectrum Disorder',
                ]],
            ]],
            ['label' => 'Education & Employment', 'fields' => [
                ['key' => 'educationalAttainment', 'label' => 'Educational Attainment', 'type' => 'select', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                ['key' => 'employmentStatus', 'label' => 'Employment Status', 'type' => 'select', 'options' => ['Employed', 'Unemployed', 'Self-Employed', 'Student', 'Not Applicable']],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Family Background', 'description' => 'Fill in whichever is applicable.', 'fields' => [
                ['key' => 'fatherName', 'label' => "Father's Name", 'type' => 'text', 'required' => false],
                ['key' => 'motherName', 'label' => "Mother's Name", 'type' => 'text', 'required' => false],
                ['key' => 'guardianName', 'label' => "Guardian's Name", 'type' => 'text', 'required' => false],
            ]],
        ],
    ],

    'TUPAD Emergency Employment' => [
        'source' => 'DOLE TUPAD (Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers) — Enrollment / Application Form',
        'steps' => [
            ['label' => 'Worker Classification', 'fields' => [
                ['key' => 'typeOfWorker', 'label' => 'Type of Worker', 'type' => 'select', 'options' => [
                    'Underemployed', 'Laid-off due to natural calamity', 'Laid-off due to economic crisis', 'Laid-off due to armed conflict', 'Self-employed with lost livelihood (seasonality)',
                ]],
                ['key' => 'specificBeneficiaryType', 'label' => 'Specific Type of Beneficiary', 'type' => 'select', 'options' => [
                    'Crop Grower / Farmer', 'Homebased Worker', 'Transport Driver', 'Vendor / Self-Employed', 'Livestock / Poultry Raiser', 'Fisherfolk', 'Laborer', 'PWD', 'Other',
                ]],
            ]],
            ['label' => 'Personal & Household Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'spouseName', 'label' => "Spouse's Name", 'type' => 'text', 'required' => false],
                ['key' => 'monthlyIncome', 'label' => 'Monthly Income (₱)', 'type' => 'number'],
                ['key' => 'numberOfDependents', 'label' => 'Number of Dependents', 'type' => 'number'],
                ['key' => 'currentOrPreviousEmployer', 'label' => 'Current / Previous Employer', 'type' => 'text', 'required' => false],
                ['key' => 'highestEducationalAttainment', 'label' => 'Highest Educational Attainment', 'type' => 'select', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
            ]],
            ['label' => 'Skills Training', 'fields' => [
                ['key' => 'intentionToAvailSkillsTraining', 'label' => 'I intend to avail of skills training after this project', 'type' => 'checkbox', 'required' => false],
            ]],
        ],
    ],

    'TESDA Skills Training Registration' => [
        'source' => "TESDA — Learner's Enrollment Form (OP-CO-05-F09)",
        'steps' => [
            ['label' => 'Personal Information', 'fields' => [
                ['key' => 'email', 'label' => 'Email / Facebook', 'type' => 'text', 'required' => false],
                ['key' => 'nationality', 'label' => 'Nationality', 'type' => 'text'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'employmentStatusBeforeTraining', 'label' => 'Employment Status Before Training', 'type' => 'select', 'options' => ['Employed', 'Unemployed', 'Self-Employed']],
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'placeOfBirth', 'label' => 'Place of Birth', 'type' => 'text'],
                ['key' => 'educationalAttainmentBeforeTraining', 'label' => 'Educational Attainment Before Training', 'type' => 'select', 'options' => ['Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                ['key' => 'parentGuardianName', 'label' => "Parent / Guardian's Name", 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Learner Classification', 'fields' => [
                ['key' => 'learnerClassification', 'label' => 'Learner Classification', 'type' => 'multiselect', 'options' => [
                    '4Ps Beneficiary', 'Agrarian Reform Beneficiary', 'Balik Probinsya', 'Displaced Worker', 'Farmer / Fisherman', 'Indigenous People / Cultural Community', 'Industry Worker', 'OFW / OFW Dependent', 'Out-of-School Youth', 'Rebel Returnee', 'Repatriated OFW', 'Student', 'TESDA Alumni', 'Uniformed Personnel', 'Victim of Natural Disaster', 'Other',
                ]],
            ]],
            ['label' => 'Training Preference', 'fields' => [
                ['key' => 'courseOrQualification', 'label' => 'Course / Qualification', 'type' => 'text'],
                ['key' => 'scholarshipPackage', 'label' => 'Scholarship Package', 'type' => 'select', 'options' => ['TWSP', 'PESFA', 'STEP', 'Other / None']],
            ]],
        ],
    ],

    'ERPAT Program Registration' => [
        // Mobile catalog names this "ERPAT Program Registration (Fathers'
        // Empowerment)" — kept here under the admin's own shortened type
        // string per the existing $typeToServiceGuideKey mapping in
        // assistance-requests.blade.php, not renamed to match mobile.
        'source' => 'Municipal Social Welfare & Development Office — ERPAT (Empowerment and Reaffirmation of Paternal Abilities) Program Registration Form',
        'steps' => [
            ['label' => 'Personal Information', 'fields' => [
                ['key' => 'dateOfBirth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'derivedAge', 'required' => false, 'derivedFromKey' => 'dateOfBirth'],
                ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                ['key' => 'civilStatus', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'required' => false],
                ['key' => 'religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
            ]],
            ['label' => 'Family & Background', 'fields' => [
                ['key' => 'familyComposition', 'label' => 'Household Members (name, age, relationship)', 'type' => 'textarea'],
                ['key' => 'educationalAttainment', 'label' => 'Educational Attainment', 'type' => 'select', 'options' => ['None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate']],
                ['key' => 'specialSkillsHobbies', 'label' => 'Special Abilities / Skills / Hobbies', 'type' => 'textarea', 'required' => false],
                ['key' => 'communityInvolvement', 'label' => 'Community Involvement', 'type' => 'textarea', 'required' => false],
            ]],
        ],
    ],
];
