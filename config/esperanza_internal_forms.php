<?php

// Static reference data for INTERNAL staff/office forms — these are
// LGU-personnel-facing (leave applications, property custodian slips, field
// monitoring reports, etc.), not citizen-facing, so they live in the
// Administrative Portal's "Internal Forms" module rather than Dokyu/Tulong.
// Same category/shape as config/esperanza_municipal_documents.php: shared
// display data consumed by a wizard + preview, just scoped to staff.
//
// Field data was extracted from the actual office forms provided
// (MHRMO/MGSO/Municipal Agriculture Office). Since 2026-11-03 the MHRMO and
// MGSO forms follow the papers the offices emailed, office parts included,
// and a filed form prints as its paper. The shape (read by the backend's
// InternalFormsSeeder and the Internal Forms page):
//
// - fields[]: key, label, type (text, number, date, time, textarea, select,
//   checkbox, checkbox-group, table), required, options, default, hint, help,
//   wide, columns (tables), and `section`: the paper's printed heading.
//   Consecutive fields of one section are one wizard step and one printed block.
// - process[]: 'Submit …' first, then each approval step as its label, or as
//   ['label', 'office' (the form's office by default; 'submitter' = the
//   employee's own department; or another office), 'part' (the printed
//   heading of that office's part), 'signatory' (the caption under its
//   signature line), 'fields' (what its officer fills when approving)].
//   Reject ends the chain.
// - paper: what prints besides the answers: corner, subtitle, letterhead
//   ([] = none), stamp, statements (['<section>' => text, '_end' => text]),
//   signatures ([role, name: 'submitter' | 'step:<label>' | null]), notes,
//   copies (the page prints once per label).

return [
    // Mock submission log — shared source of truth for both the Internal Forms
    // module (staff-facing "My Submissions" list) and the Reports & Analytics
    // module (office/status breakdowns), so the two stay in sync.
    'submissions' => [
        ['ref' => 'IF-2026-1042', 'officeKey' => 'hr', 'office' => 'MHRMO', 'form' => 'Application for Leave', 'submittedBy' => 'Angeline Mercado', 'submitted' => 'Jul 10, 2026', 'status' => 'Approved'],
        ['ref' => 'IF-2026-1039', 'officeKey' => 'hr', 'office' => 'MHRMO', 'form' => 'Pass Slip for Personal Business', 'submittedBy' => 'Rowena Aguilar', 'submitted' => 'Jul 9, 2026', 'status' => 'Approved'],
        ['ref' => 'IF-2026-1035', 'officeKey' => 'gso', 'office' => 'MGSO', 'form' => "Driver's Trip Ticket", 'submittedBy' => 'Bienvenido Salazar', 'submitted' => 'Jul 8, 2026', 'status' => 'Pending Review'],
        ['ref' => 'IF-2026-1031', 'officeKey' => 'gso', 'office' => 'MGSO', 'form' => "Borrower's Slip", 'submittedBy' => 'Paolo Reyes', 'submitted' => 'Jul 6, 2026', 'status' => 'Released'],
        ['ref' => 'IF-2026-1028', 'officeKey' => 'agriculture', 'office' => 'Agriculture', 'form' => 'Dog Vaccination Report', 'submittedBy' => 'Antonio G. Ejes', 'submitted' => 'Jul 4, 2026', 'status' => 'Completed'],
        ['ref' => 'IF-2026-1020', 'officeKey' => 'hr', 'office' => 'MHRMO', 'form' => 'Personal Data Sheet', 'submittedBy' => 'Dexter Villamor', 'submitted' => 'Jun 28, 2026', 'status' => 'Completed'],
        ['ref' => 'IF-2026-1045', 'officeKey' => 'mswd', 'office' => 'MSWD', 'form' => 'General Intake Sheet', 'submittedBy' => 'Antonio G. Ejes', 'submitted' => 'Jul 11, 2026', 'status' => 'Pending Review'],
    ],

    'offices' => [
        // MHRMO internal forms, following the office's own papers (2026-11-03):
        // public/documents/email documents/MHRMO/ (CS Form 6 Rev. 2020, CS Form 212
        // Rev. 2025, CS Form 211 Rev. 2025, CS Form 7 Rev. 2018, Locator Slip 2026,
        // Pass Slip 2026). See the shape notes at the top of this file.
        'hr' => [
            'label' => 'Human Resource Management Office',
            'shortLabel' => 'MHRMO',
            'icon' => 'users-round',
            'forms' => [

                // ------------------------------------------------------------------
                // CS Form No. 6, Revised 2020 — Application for Leave
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_application_for_leave',
                    'name' => 'Application for Leave',
                    'formNo' => 'Civil Service Form No. 6, Revised 2020',
                    'title' => 'APPLICATION FOR LEAVE',
                    'days' => '1-3 working days',
                    'requirements' => [
                        'Medical certificate: Sick Leave filed in advance or exceeding five (5) days (an affidavit if no medical consultation was availed of)',
                        'Updated Solo Parent Identification Card: Solo Parent Leave',
                        'Clearance from money, property and work-related accountabilities: leave of thirty (30) calendar days or more, and terminal leave',
                    ],
                    'paper' => [
                        'corner' => 'ANNEX A',
                        'stamp' => 'Stamp of Date of Receipt',
                        'signatures' => [
                            ['role' => '(Signature of Applicant)', 'name' => 'submitter'],
                            ['role' => '7.A (Authorized Officer)', 'name' => 'step:Certification of Leave Credits'],
                            ['role' => '7.B (Authorized Officer)', 'name' => 'step:Head of Office Recommendation'],
                            ['role' => '7.C / 7.D (Authorized Official)', 'name' => 'step:Approval'],
                        ],
                        // Page 2, "INSTRUCTIONS AND REQUIREMENTS" (abridged).
                        'notes' => [
                            'Application for any type of leave shall be made on this Form and to be accomplished at least in duplicate with documentary requirements.',
                            'Vacation leave: filed five (5) days in advance, whenever possible. Vacation or special privilege leave within the Philippines or abroad shall be indicated for purposes of securing travel authority and completing clearance from money and work accountabilities.',
                            'Sick leave: filed immediately upon return. If filed in advance or exceeding five (5) days, accompanied by a medical certificate; an affidavit if medical consultation was not availed of.',
                            "Maternity leave (105 days): proof of pregnancy, e.g. ultrasound, doctor's certificate on the expected date of delivery; Notice of Allocation of Maternity Leave Credits (CS Form No. 6a), if needed.",
                            "Paternity leave (7 days): proof of child's delivery, e.g. birth certificate, medical certificate and marriage contract.",
                            'Special privilege leave (3 days): filed/approved at least one (1) week prior to availment, except on emergency cases.',
                            'Solo parent leave (7 days): filed five (5) days before, with updated Solo Parent Identification Card.',
                            "Study leave (up to 6 months): the agency's internal requirements, if any; contract between the agency head or authorized representative and the employee.",
                            'VAWC leave (10 days): BPO, TPO/PPO, a certification that the application for one was filed, or a police report and medical certificate.',
                            'Rehabilitation leave (up to 6 months): within one (1) week from the accident; letter request with police report, if any; medical certificate; written concurrence of a government physician if the attending physician is a private practitioner.',
                            'Special leave benefits for women (up to 2 months): medical certificate with clinical summary, histopathological report, operative technique, duration of the surgery and estimated period of recuperation.',
                            'Special emergency (calamity) leave (up to 5 days): within thirty (30) days from the calamity, once a year.',
                            'Monetization of fifty percent (50%) or more of leave credits: letter request to the head of the agency stating the valid and justifiable reasons.',
                            "Terminal leave: proof of the employee's resignation or retirement or separation from the service.",
                            'Adoption leave: authenticated copy of the Pre-Adoptive Placement Authority issued by DSWD.',
                            '* For leave of absence for thirty (30) calendar days or more and terminal leave, application shall be accompanied by a clearance from money, property and work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985).',
                        ],
                    ],
                    'process' => [
                        'Submit Application',
                        [
                            'label' => 'Certification of Leave Credits',
                            'part' => '7.A CERTIFICATION OF LEAVE CREDITS',
                            'signatory' => '(Authorized Officer)',
                            'fields' => [
                                ['key' => 'credits_as_of', 'label' => 'As of', 'type' => 'date'],
                                ['key' => 'vl_total_earned', 'label' => 'Vacation Leave: Total Earned', 'type' => 'number'],
                                ['key' => 'sl_total_earned', 'label' => 'Sick Leave: Total Earned', 'type' => 'number'],
                                ['key' => 'vl_less_this_application', 'label' => 'Vacation Leave: Less this application', 'type' => 'number'],
                                ['key' => 'sl_less_this_application', 'label' => 'Sick Leave: Less this application', 'type' => 'number'],
                                ['key' => 'vl_balance', 'label' => 'Vacation Leave: Balance', 'type' => 'number'],
                                ['key' => 'sl_balance', 'label' => 'Sick Leave: Balance', 'type' => 'number'],
                            ],
                        ],
                        [
                            // On paper a "For disapproval" recommendation still goes up to 7.C/7.D,
                            // so it is a field here (Approve forwards it), not a Reject.
                            // Not required: the chain tests approve every leave step without data.
                            'label' => 'Head of Office Recommendation',
                            'office' => 'submitter',
                            'part' => '7.B RECOMMENDATION',
                            'signatory' => '(Authorized Officer)',
                            'fields' => [
                                ['key' => 'recommendation', 'label' => 'Recommendation', 'type' => 'select', 'options' => ['For approval', 'For disapproval due to']],
                                ['key' => 'disapproval_reason', 'label' => 'For disapproval due to', 'type' => 'textarea'],
                            ],
                        ],
                        [
                            // 7.D DISAPPROVED DUE TO = Reject, with its reason as remarks.
                            'label' => 'Approval',
                            'office' => 'Office of the Municipal Mayor',
                            'part' => '7.C APPROVED FOR: / 7.D DISAPPROVED DUE TO:',
                            'signatory' => '(Authorized Official)',
                            'fields' => [
                                ['key' => 'days_with_pay', 'label' => 'days with pay', 'type' => 'number'],
                                ['key' => 'days_without_pay', 'label' => 'days without pay', 'type' => 'number'],
                                ['key' => 'approved_others', 'label' => 'others (Specify)', 'type' => 'text'],
                            ],
                        ],
                    ],
                    'fields' => [
                        // Items 1-5 have no printed heading on the paper: left unsectioned
                        // (six fields, so still one wizard step). 3. DATE OF FILING prints from the filing.
                        ['key' => 'office_department', 'label' => '1. Office/Department', 'type' => 'text', 'required' => true],
                        ['key' => 'last_name', 'label' => '2. Name (Last)', 'type' => 'text'],
                        ['key' => 'first_name', 'label' => 'Name (First)', 'type' => 'text'],
                        ['key' => 'middle_name', 'label' => 'Name (Middle)', 'type' => 'text'],
                        ['key' => 'position', 'label' => '4. Position', 'type' => 'text'],
                        ['key' => 'salary', 'label' => '5. Salary', 'type' => 'text'],
                        ['key' => 'leave_type', 'label' => '6.A Type of Leave to be Availed Of', 'type' => 'select', 'section' => '6. DETAILS OF APPLICATION', 'options' => [
                            'Vacation Leave', 'Mandatory/Forced Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave',
                            'Special Privilege Leave', 'Solo Parent Leave', 'Study Leave', '10-Day VAWC Leave',
                            'Rehabilitation Privilege', 'Special Leave Benefits for Women', 'Special Emergency (Calamity) Leave', 'Adoption Leave',
                            'Others',
                        ]],
                        ['key' => 'leave_type_others', 'label' => 'Others (specify)', 'type' => 'text', 'section' => '6. DETAILS OF APPLICATION'],
                        ['key' => 'leave_details_case', 'label' => '6.B Details of Leave', 'type' => 'select', 'section' => '6. DETAILS OF APPLICATION', 'options' => [
                            'Within the Philippines', 'Abroad (Specify)',
                            'In Hospital (Specify Illness)', 'Out Patient (Specify Illness)',
                            "Completion of Master's Degree", 'BAR/Board Examination Review',
                            'Monetization of Leave Credits', 'Terminal Leave',
                        ], 'help' => 'Vacation/Special Privilege Leave: within the Philippines or abroad. Sick Leave: in hospital or out patient. Study Leave: the purpose. Other purpose: monetization or terminal leave.'],
                        ['key' => 'leave_details', 'label' => '6.B Specify (place, country or illness)', 'type' => 'text', 'section' => '6. DETAILS OF APPLICATION', 'help' => 'For Special Leave Benefits for Women, the illness.'],
                        ['key' => 'number_of_days', 'label' => '6.C Number of Working Days Applied For', 'type' => 'number', 'section' => '6. DETAILS OF APPLICATION'],
                        ['key' => 'inclusive_dates', 'label' => 'Inclusive Dates', 'type' => 'text', 'section' => '6. DETAILS OF APPLICATION'],
                        ['key' => 'commutation', 'label' => '6.D Commutation', 'type' => 'select', 'options' => ['Not Requested', 'Requested'], 'section' => '6. DETAILS OF APPLICATION'],
                    ],
                ],

                // ------------------------------------------------------------------
                // CS Form No. 212, Revised 2025 — Personal Data Sheet (C1-C4)
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_personal_data_sheet',
                    'name' => 'Personal Data Sheet',
                    'formNo' => 'CS Form No. 212, Revised 2025',
                    'title' => 'PERSONAL DATA SHEET',
                    'days' => 'N/A — for HR 201 file',
                    'requirements' => [
                        'Passport-sized unfiltered digital picture taken within the last 6 months (4.5 cm. X 3.5 cm)',
                        'Work Experience Sheet (description of duties), attached to the PDS',
                        "Government Issued ID (i.e. Passport, GSIS, SSS, PRC, Driver's License, etc.), with ID number and date of issuance",
                    ],
                    'paper' => [
                        'letterhead' => [],
                        'subtitle' => 'WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.',
                        'statements' => [
                            'I. PERSONAL INFORMATION' => 'READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM. Print legibly if accomplished through own handwriting. Tick appropriate boxes ( ) and use separate sheet if necessary. Indicate N/A if not applicable. DO NOT ABBREVIATE.',
                            'Questions 34 to 40' => "Item 40 is asked pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861).",
                            '_end' => 'SUBSCRIBED AND SWORN to before me this ____________, affiant exhibiting his/her validly issued government ID as indicated above.',
                        ],
                        'signatures' => [
                            ['role' => 'Signature (Sign inside the box) / Date Accomplished', 'name' => 'submitter'],
                            ['role' => 'Right Thumbmark', 'name' => null],
                            ['role' => 'Person Administering Oath', 'name' => 'step:HR Verification'],
                        ],
                        'notes' => [
                            'PHOTO: Passport-sized unfiltered digital picture taken within the last 6 months, 4.5 cm. X 3.5 cm (affixed on the printed copy).',
                            'Signatures: wet signature/e-signature/digital certificate (except for notary public).',
                            'CS FORM 212 (Revised 2025)',
                        ],
                    ],
                    'process' => [
                        'Submit PDS',
                        [
                            'label' => 'HR Verification',
                            'signatory' => 'Person Administering Oath',
                            'fields' => [
                                ['key' => 'sworn_date', 'label' => 'Subscribed and sworn to before me this (date)', 'type' => 'date'],
                            ],
                        ],
                        'Encoding to 201 File',
                    ],
                    'fields' => [
                        // ---- C1. I. PERSONAL INFORMATION
                        ['key' => 'surname', 'label' => '1. Surname', 'type' => 'text', 'required' => true, 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'first_name', 'label' => '2. First Name', 'type' => 'text', 'required' => true, 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'name_extension', 'label' => 'Name Extension (Jr., Sr)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'middle_name', 'label' => 'Middle Name', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'date_of_birth', 'label' => '3. Date of Birth', 'type' => 'date', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'place_of_birth', 'label' => '4. Place of Birth', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'sex', 'label' => '5. Sex at Birth', 'type' => 'select', 'options' => ['Male', 'Female'], 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'civil_status', 'label' => '6. Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated', 'Other/s'], 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'civil_status_other', 'label' => 'Civil Status: Other/s (specify)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'height', 'label' => '7. Height (m)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'weight', 'label' => '8. Weight (kg)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'blood_type', 'label' => '9. Blood Type', 'type' => 'select', 'options' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'umid_no', 'label' => '10. UMID ID No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'pagibig_no', 'label' => '11. PAG-IBIG ID No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'philhealth_no', 'label' => '12. PhilHealth No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'philsys_no', 'label' => '13. PhilSys Number (PSN)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'tin_no', 'label' => '14. TIN No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'agency_employee_no', 'label' => '15. Agency Employee No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'citizenship', 'label' => '16. Citizenship', 'type' => 'select', 'options' => ['Filipino', 'Dual Citizenship'], 'default' => 'Filipino', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'dual_citizenship_by', 'label' => 'If holder of dual citizenship: by birth / by naturalization', 'type' => 'select', 'options' => ['by birth', 'by naturalization'], 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'dual_citizenship_country', 'label' => 'If holder of dual citizenship, pls. indicate country', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_house_no', 'label' => '17. Residential Address: House/Block/Lot No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_street', 'label' => 'Residential Address: Street', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_subdivision', 'label' => 'Residential Address: Subdivision/Village', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_barangay', 'label' => 'Residential Address: Barangay', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_city', 'label' => 'Residential Address: City/Municipality', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_province', 'label' => 'Residential Address: Province', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'res_zip', 'label' => 'Residential Address: ZIP Code', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_house_no', 'label' => '18. Permanent Address: House/Block/Lot No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_street', 'label' => 'Permanent Address: Street', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_subdivision', 'label' => 'Permanent Address: Subdivision/Village', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_barangay', 'label' => 'Permanent Address: Barangay', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_city', 'label' => 'Permanent Address: City/Municipality', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_province', 'label' => 'Permanent Address: Province', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'perm_zip', 'label' => 'Permanent Address: ZIP Code', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'telephone_no', 'label' => '19. Telephone No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'mobile_no', 'label' => '20. Mobile No.', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],
                        ['key' => 'email_address', 'label' => '21. E-mail Address (if any)', 'type' => 'text', 'section' => 'I. PERSONAL INFORMATION'],

                        // ---- C1. II. FAMILY BACKGROUND
                        ['key' => 'spouse_surname', 'label' => "22. Spouse's Surname", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_first_name', 'label' => "Spouse's First Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_name_extension', 'label' => "Spouse's Name Extension (Jr., Sr)", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_middle_name', 'label' => "Spouse's Middle Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_occupation', 'label' => "Spouse's Occupation", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_employer', 'label' => "Spouse's Employer/Business Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_business_address', 'label' => "Spouse's Business Address", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'spouse_telephone_no', 'label' => "Spouse's Telephone No.", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'father_surname', 'label' => "24. Father's Surname", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'father_first_name', 'label' => "Father's First Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'father_name_extension', 'label' => "Father's Name Extension (Jr., Sr)", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'father_middle_name', 'label' => "Father's Middle Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'mother_surname', 'label' => "25. Mother's Maiden Name: Surname", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'mother_first_name', 'label' => "Mother's Maiden Name: First Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'mother_middle_name', 'label' => "Mother's Maiden Name: Middle Name", 'type' => 'text', 'section' => 'II. FAMILY BACKGROUND'],
                        ['key' => 'children', 'label' => '23. Name of Children (Write full name and list all)', 'type' => 'table', 'rowIcon' => 'baby', 'rowLabel' => 'Child', 'section' => 'II. FAMILY BACKGROUND', 'columns' => [
                            ['key' => 'name', 'label' => 'Full Name'],
                            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
                        ]],

                        // ---- C1. III. EDUCATIONAL BACKGROUND
                        ['key' => 'educational_background', 'label' => '26. Educational Background (Elementary, Secondary, Vocational / Trade Course, College, Graduate Studies)', 'type' => 'table', 'rowIcon' => 'graduation-cap', 'rowLabel' => 'School', 'section' => 'III. EDUCATIONAL BACKGROUND', 'columns' => [
                            ['key' => 'level', 'label' => 'Level'],
                            ['key' => 'school', 'label' => 'Name of School (Write in full)'],
                            ['key' => 'degree', 'label' => 'Basic Education/Degree/Course (Write in full)'],
                            ['key' => 'from', 'label' => 'Period of Attendance: From'],
                            ['key' => 'to', 'label' => 'Period of Attendance: To'],
                            ['key' => 'highest_level_units', 'label' => 'Highest Level/Units Earned (if not graduated)'],
                            ['key' => 'year_graduated', 'label' => 'Year Graduated'],
                            ['key' => 'honors', 'label' => 'Scholarship/Academic Honors Received'],
                        ]],

                        // ---- C2. IV. CIVIL SERVICE ELIGIBILITY
                        ['key' => 'civil_service_eligibility', 'label' => '27. Civil Service Eligibility', 'type' => 'table', 'rowIcon' => 'award', 'rowLabel' => 'Eligibility', 'section' => 'IV. CIVIL SERVICE ELIGIBILITY', 'columns' => [
                            ['key' => 'eligibility', 'label' => 'CES/CSEE/Career Service/RA 1080 (Board/Bar)/Under Special Laws/Category II/IV Eligibility and Eligibilities for Uniformed Personnel'],
                            ['key' => 'rating', 'label' => 'Rating (If Applicable)'],
                            ['key' => 'date_of_exam', 'label' => 'Date of Examination / Conferment'],
                            ['key' => 'place_of_exam', 'label' => 'Place of Examination / Conferment'],
                            ['key' => 'license_number', 'label' => 'License (if applicable): Number'],
                            ['key' => 'license_valid_until', 'label' => 'License: Valid Until', 'type' => 'date'],
                        ]],

                        // ---- C2. V. WORK EXPERIENCE (2025: no salary columns; "To" may be "Present")
                        ['key' => 'work_experience', 'label' => '28. Work Experience (Include private employment. Start from your recent work.)', 'type' => 'table', 'rowIcon' => 'briefcase', 'rowLabel' => 'Position', 'section' => 'V. WORK EXPERIENCE', 'columns' => [
                            ['key' => 'from', 'label' => 'Inclusive Dates: From'],
                            ['key' => 'to', 'label' => 'Inclusive Dates: To'],
                            ['key' => 'position_title', 'label' => 'Position Title (Write in full/Do not abbreviate)'],
                            ['key' => 'department_agency', 'label' => 'Department / Agency / Office / Company (Write in full/Do not abbreviate)'],
                            ['key' => 'status_of_appointment', 'label' => 'Status of Appointment'],
                            ['key' => 'govt_service', 'label' => "Gov't Service (Y/N)"],
                        ]],

                        // ---- C3. VI. VOLUNTARY WORK
                        ['key' => 'voluntary_work', 'label' => '29. Voluntary Work or Involvement', 'type' => 'table', 'rowIcon' => 'heart-handshake', 'rowLabel' => 'Engagement', 'section' => 'VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S', 'columns' => [
                            ['key' => 'organization', 'label' => 'Name & Address of Organization (Write in full)'],
                            ['key' => 'from', 'label' => 'Inclusive Dates: From', 'type' => 'date'],
                            ['key' => 'to', 'label' => 'Inclusive Dates: To', 'type' => 'date'],
                            ['key' => 'hours', 'label' => 'Number of Hours', 'type' => 'number'],
                            ['key' => 'position', 'label' => 'Position / Nature of Work'],
                        ]],

                        // ---- C3. VII. LEARNING AND DEVELOPMENT
                        ['key' => 'training_programs', 'label' => '30. Learning and Development Interventions/Training Programs Attended', 'type' => 'table', 'rowIcon' => 'graduation-cap', 'rowLabel' => 'Training', 'section' => 'VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED', 'columns' => [
                            ['key' => 'title', 'label' => 'Title of Learning and Development Interventions/Training Programs (Write in full)'],
                            ['key' => 'from', 'label' => 'Inclusive Dates of Attendance: From', 'type' => 'date'],
                            ['key' => 'to', 'label' => 'Inclusive Dates of Attendance: To', 'type' => 'date'],
                            ['key' => 'hours', 'label' => 'Number of Hours', 'type' => 'number'],
                            ['key' => 'ld_type', 'label' => 'Type of L&D (Managerial/Supervisory/Technical/etc)'],
                            ['key' => 'conducted_by', 'label' => 'Conducted/Sponsored By (Write in full)'],
                        ]],

                        // ---- C3. VIII. OTHER INFORMATION (three parallel columns on paper)
                        ['key' => 'other_information', 'label' => '31-33. Other Information', 'type' => 'table', 'rowIcon' => 'star', 'rowLabel' => 'Entry', 'section' => 'VIII. OTHER INFORMATION', 'columns' => [
                            ['key' => 'special_skills', 'label' => '31. Special Skills and Hobbies'],
                            ['key' => 'distinctions', 'label' => '32. Non-Academic Distinctions / Recognition (Write in full)'],
                            ['key' => 'memberships', 'label' => '33. Membership in Association/Organization (Write in full)'],
                        ]],

                        // ---- C4. 34-40. The paper prints no heading here; the section name is Esperanza's.
                        ['key' => 'q34a_third_degree', 'label' => '34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed, a. within the third degree?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q34b_fourth_degree', 'label' => '34. b. within the fourth degree (for Local Government Unit - Career Employees)?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q34_details', 'label' => '34. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35a_admin_offense', 'label' => '35. a. Have you ever been found guilty of any administrative offense?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35a_details', 'label' => '35. a. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35b_criminally_charged', 'label' => '35. b. Have you been criminally charged before any court?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35b_details', 'label' => '35. b. If YES, give details', 'type' => 'text', 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35b_date_filed', 'label' => '35. b. Date Filed', 'type' => 'date', 'section' => 'Questions 34 to 40'],
                        ['key' => 'q35b_case_status', 'label' => '35. b. Status of Case/s', 'type' => 'text', 'section' => 'Questions 34 to 40'],
                        ['key' => 'q36_convicted', 'label' => '36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q36_details', 'label' => '36. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q37_separated', 'label' => '37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q37_details', 'label' => '37. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q38a_candidate', 'label' => '38. a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q38a_details', 'label' => '38. a. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q38b_resigned_to_campaign', 'label' => '38. b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q38b_details', 'label' => '38. b. If YES, give details', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q39_immigrant', 'label' => '39. Have you acquired the status of an immigrant or permanent resident of another country?', 'type' => 'select', 'options' => ['Yes', 'No'], 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q39_country', 'label' => '39. If YES, give details (country)', 'type' => 'text', 'wide' => true, 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40a_indigenous', 'label' => '40. a. Are you a member of any indigenous group?', 'type' => 'select', 'options' => ['Yes', 'No'], 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40a_details', 'label' => '40. a. If YES, please specify', 'type' => 'text', 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40b_pwd', 'label' => '40. b. Are you a person with disability?', 'type' => 'select', 'options' => ['Yes', 'No'], 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40b_id_no', 'label' => '40. b. If YES, please specify ID No', 'type' => 'text', 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40c_solo_parent', 'label' => '40. c. Are you a solo parent?', 'type' => 'select', 'options' => ['Yes', 'No'], 'section' => 'Questions 34 to 40'],
                        ['key' => 'q40c_id_no', 'label' => '40. c. If YES, please specify ID No', 'type' => 'text', 'section' => 'Questions 34 to 40'],

                        // ---- C4. 41 (heading verbatim from the paper)
                        ['key' => 'references', 'label' => '41. References', 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Reference', 'section' => 'REFERENCES (Person not related by consanguinity or affinity to applicant /appointee)', 'columns' => [
                            ['key' => 'name', 'label' => 'Name'],
                            ['key' => 'address', 'label' => 'Office / Residential Address'],
                            ['key' => 'contact', 'label' => 'Contact No. and/or Email'],
                        ]],

                        // ---- C4. 42 and the ID (the section name is Esperanza's)
                        // Required on paper; left optional because only surname + first_name may be required.
                        ['key' => 'declaration', 'label' => '42. I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.', 'type' => 'checkbox', 'wide' => true, 'section' => 'Declaration and Government Issued ID'],
                        ['key' => 'govt_id_type', 'label' => "Government Issued ID (i.e. Passport, GSIS, SSS, PRC, Driver's License, etc.)", 'type' => 'text', 'section' => 'Declaration and Government Issued ID'],
                        ['key' => 'govt_id_no', 'label' => 'ID/License/Passport No.', 'type' => 'text', 'section' => 'Declaration and Government Issued ID'],
                        ['key' => 'govt_id_issuance', 'label' => 'Date/Place of Issuance', 'type' => 'text', 'section' => 'Declaration and Government Issued ID'],
                    ],
                ],

                // ------------------------------------------------------------------
                // CS Form No. 211, Revised 2025 — Medical Certificate (For Employment)
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_medical_certificate',
                    'name' => 'Medical Certificate',
                    'formNo' => 'CS Form No. 211, Revised 2025',
                    'title' => 'MEDICAL CERTIFICATE',
                    'days' => '1 working day',
                    'requirements' => [
                        'Results of the pre-employment medical/physical/psychological examinations: Blood Test, Urinalysis, Chest X-Ray, Drug Test, Psychological Examination, Neuro-Psychiatric Examination (if applicable)',
                        'Accomplished by a licensed government or private physician',
                        'Attach this certificate to original appointment, transfer, and reemployment',
                    ],
                    'paper' => [
                        'letterhead' => [],
                        'subtitle' => '(For Employment)',
                        'statements' => [
                            'FOR THE LICENSED GOVERNMENT OR PRIVATE PHYSICIAN' => 'I hereby certify that I have reviewed and evaluated the attached examination results, personally examined the above named individual and found him/her to be physically and medically FIT / UNFIT for employment.',
                        ],
                        // The appointee does not sign this form; the physician alone does.
                        'signatures' => [
                            ['role' => 'SIGNATURE over PRINTED NAME of Licensed Government or Private Physician', 'name' => "step:Physician's Certification"],
                        ],
                        'notes' => [
                            'a. This medical certificate should be accomplished by a licensed government or private physician.',
                            'b. Attach this certificate to original appointment, transfer, and reemployment.',
                            'c. The results of the following pre-employment medical/physical/psychological examinations must be attached to this form: Blood Test, Urinalysis, Chest X-Ray, Drug Test, Psychological Examination, Neuro-Psychiatric Examination (if applicable).',
                        ],
                    ],
                    'process' => [
                        'Submit Request',
                        [
                            // FIT/UNFIT is a finding, recorded on Approve; Reject is not a finding.
                            'label' => "Physician's Certification",
                            'office' => 'Municipal Health Office',
                            'part' => 'FOR THE LICENSED GOVERNMENT OR PRIVATE PHYSICIAN',
                            'signatory' => 'SIGNATURE over PRINTED NAME of Licensed Government or Private Physician',
                            'fields' => [
                                ['key' => 'fitness', 'label' => 'Found him/her to be physically and medically ... for employment', 'type' => 'select', 'options' => ['FIT', 'UNFIT'], 'required' => true],
                                ['key' => 'physician_name', 'label' => 'Printed Name of Licensed Government or Private Physician', 'type' => 'text', 'required' => true],
                                ['key' => 'physician_agency', 'label' => 'Agency/Affiliation of the Licensed Government or Private Physician', 'type' => 'text'],
                                ['key' => 'license_no', 'label' => 'License No.', 'type' => 'text'],
                                ['key' => 'official_designation', 'label' => 'Official Designation', 'type' => 'text'],
                                ['key' => 'height', 'label' => 'Height (m), Bare Foot', 'type' => 'text'],
                                ['key' => 'weight', 'label' => 'Weight (kg), Stripped', 'type' => 'text'],
                                ['key' => 'blood_type', 'label' => 'Blood Type', 'type' => 'text'],
                                ['key' => 'date_examined', 'label' => 'Date Examined', 'type' => 'date'],
                            ],
                        ],
                    ],
                    'fields' => [
                        ['key' => 'full_name', 'label' => 'Name (Last Name, First Name, Name Extension (if any) and Middle Name)', 'type' => 'text', 'required' => true, 'wide' => true, 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'agency_address', 'label' => 'Agency / Address', 'type' => 'text', 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'address', 'label' => 'Address', 'type' => 'text', 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'age', 'label' => 'Age', 'type' => 'number', 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female'], 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated'], 'section' => 'FOR THE PROPOSED APPOINTEE'],
                        ['key' => 'proposed_position', 'label' => 'Proposed Position', 'type' => 'text', 'section' => 'FOR THE PROPOSED APPOINTEE'],
                    ],
                ],

                // ------------------------------------------------------------------
                // CS Form No. 7, Revised 2018 — Clearance Form
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_clearance',
                    'name' => 'Clearance Form',
                    'formNo' => 'CS Form No. 7, Revised 2018',
                    'title' => 'CLEARANCE FORM',
                    'days' => '2-3 working days',
                    'requirements' => [
                        'For employees who are retiring, being separated, transferring to other agencies, leaving the Philippines and going on leave of absence for more than 30 days',
                        'To be duly accomplished before paying the last salary or any money due the employee',
                    ],
                    'paper' => [
                        'statements' => [
                            'I. PURPOSE' => 'TO: Municipality of Esperanza. I hereby request clearance from money, property and work-related accountabilities for:',
                            'II. CLEARANCE FROM WORK-RELATED ACCOUNTABILITIES' => 'We hereby certify that this employee is cleared / not cleared of work-related accountabilities from this Unit/Office/Dept.',
                            'V. CERTIFICATION' => 'I hereby certify that this employee is cleared of work-related, money and property accountabilities from this agency. This certification includes no pending administrative case from this agency.',
                        ],
                        'signatures' => [
                            ['role' => 'Name and Signature of Employee', 'name' => 'submitter'],
                            ['role' => 'Immediate Supervisor', 'name' => 'step:Immediate Supervisor'],
                            ['role' => 'Head of Office', 'name' => 'step:Head of Office'],
                            ['role' => 'Name of Clearing Officer/Official', 'name' => 'step:Money and Property Accountabilities'],
                            ['role' => 'Internal Affairs Office/Legal Affairs Office', 'name' => 'step:No Pending Administrative Case'],
                            ['role' => 'Signature over Printed Name of Agency Head', 'name' => 'step:Agency Head Certification'],
                        ],
                        // The paper's back page (Instructions).
                        'notes' => [
                            '1. Employees who are retiring, being separated, transferring to other agencies, leaving the Philippines and going on leave of absence for more than 30 days shall prepare this form in quadruplicate.',
                            '2. This clearance should be duly accomplished before paying the last salary or any money due the employees. (Specify which type of clearance: maternity leave, retirement, transfer, etc.)',
                            '3. If the employees are cleared from a unit/office/department, the clearing/authorized official may attach to this clearance the pertinent document/s that shall prove that the employees are cleared of any obligation or accountability from their office, if any, and tick the box under the "Cleared" column before affixing their signatures.',
                            '4. If the employees appear to have uncleared accountability/ies from a unit/office/department, the clearing/authorized official shall attach to this clearance the pertinent document/s that shall prove that the employees have remaining obligation or accountability from their office further indicating the necessary action/s that the employee must satisfy in order to be cleared, and tick the box under the "Uncleared" column. The clearing/authorized official must only sign this clearance corresponding to their name once the employee have complied the necessary requirements and cleared of all the obligation/s and accountability/ies from their office. They must also tick the box under the "Cleared" column.',
                            '5. The HRMO shall distribute copies of approved clearance as follows: original to the employee; duplicate to be attached to the payroll or voucher; triplicate to human resource unit file; and fourth copy to accounting/auditing office.',
                            '6. Processing of clearance certificate shall follow the order of number indicated.',
                        ],
                    ],
                    // Paper order (instruction 6): II, III, IV, V; then the HRMO distributes the copies.
                    'process' => [
                        'Submit Request',
                        [
                            'label' => 'Immediate Supervisor',
                            'office' => 'submitter',
                            'part' => 'II. CLEARANCE FROM WORK-RELATED ACCOUNTABILITIES',
                            'signatory' => 'Immediate Supervisor',
                        ],
                        [
                            // Same part II as the supervisor's; its own heading is the label.
                            'label' => 'Head of Office',
                            'office' => 'submitter',
                            'signatory' => 'Head of Office',
                        ],
                        [
                            // The paper's units are a central office's; MHRMO to name the LGU's
                            // (then one process entry per clearing office, in the listed order).
                            'label' => 'Money and Property Accountabilities',
                            'part' => 'III. CLEARANCE FROM MONEY AND PROPERTY ACCOUNTABILITIES',
                            'signatory' => 'Name of Clearing Officer/Official',
                            'fields' => [
                                ['key' => 'units_cleared', 'label' => 'Cleared', 'type' => 'checkbox-group', 'options' => [
                                    '1.a Supply and Property Procurement and Management Services',
                                    '1.b Human Resource Welfare & Assistance',
                                    '1.c Agency-accredited Union/Cooperative',
                                    '2.a Legal Office Library',
                                    '2.b Library Services',
                                    '3.a Financial Services',
                                    '3.b Transaction, Processing & Billing Services',
                                    '3.c Payroll & Remittance Services',
                                    '4.a Scholarship Services',
                                ]],
                            ],
                        ],
                        [
                            // Paper: "a. Internal Affairs Office/Legal Affairs Office". Esperanza has no
                            // legal office, so the form's own office (MHRMO) takes it. None ticked = no pending case.
                            'label' => 'No Pending Administrative Case',
                            'part' => 'IV. CERTIFICATION OF NO PENDING ADMINISTRATIVE CASE',
                            'signatory' => 'Internal Affairs Office/Legal Affairs Office',
                            'fields' => [
                                ['key' => 'pending_case', 'label' => 'a. Internal Affairs Office/Legal Affairs Office', 'type' => 'checkbox-group', 'options' => ['with pending administrative case', 'with ongoing investigation (no formal charge yet)']],
                            ],
                        ],
                        [
                            'label' => 'Agency Head Certification',
                            'office' => 'Office of the Municipal Mayor',
                            'part' => 'V. CERTIFICATION',
                            'signatory' => 'Signature over Printed Name of Agency Head',
                        ],
                        'Release', // instruction 5: the HRMO distributes the four copies
                    ],
                    'fields' => [
                        ['key' => 'full_name', 'label' => 'Name of Employee', 'type' => 'text', 'required' => true, 'section' => 'I. PURPOSE'],
                        ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => ['Transfer', 'Retirement', 'Resignation', 'Leave', 'Other Mode of Separation'], 'section' => 'I. PURPOSE'],
                        ['key' => 'purpose_other', 'label' => 'Other Mode of Separation: Please specify', 'type' => 'text', 'section' => 'I. PURPOSE'],
                        ['key' => 'date_of_effectivity', 'label' => 'Date of Effectivity', 'type' => 'date', 'section' => 'I. PURPOSE'],
                        ['key' => 'office_to_clear', 'label' => 'Office of Assignment', 'type' => 'text', 'section' => 'I. PURPOSE'],
                        ['key' => 'position_sg_step', 'label' => 'Position/SG/Step', 'type' => 'text', 'section' => 'I. PURPOSE'],
                    ],
                ],

                // ------------------------------------------------------------------
                // MHRMO Locator Slip (2026). The paper carries no form number.
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_locator_slip',
                    'name' => 'Locator Slip',
                    'formNo' => null,
                    'title' => 'LOCATOR SLIP',
                    'days' => 'Same day',
                    'requirements' => ['Purpose of travel must be supported by attachments'],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'MUNICIPALITY OF ESPERANZA'],
                        'statements' => [
                            '_end' => 'CERTIFICATION. To the concerned: This is to certify that the above-named LGU official/personnel has visited or appeared in this Office/place for the purpose and during the date and time stated purpose. Name And Signature: ____________________ Position/Designation: ____________________ Office: ____________________',
                        ],
                        'signatures' => [
                            ['role' => 'SIGNATURE OF REQUESTING EMPLOYEE', 'name' => 'submitter'],
                            ['role' => 'SIGNATURE OF DEPARTMENT HEAD/UNIT HEAD/AUTHORIZED REPRESENTATIVE', 'name' => 'step:Department Head Approval'],
                        ],
                    ],
                    'process' => [
                        'Submit Request',
                        [
                            'label' => 'Department Head Approval',
                            'office' => 'submitter',
                            'signatory' => 'SIGNATURE OF DEPARTMENT HEAD/UNIT HEAD/AUTHORIZED REPRESENTATIVE',
                        ],
                    ],
                    'fields' => [
                        ['key' => 'full_name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                        ['key' => 'position_designation', 'label' => 'Position/Designation', 'type' => 'text'],
                        ['key' => 'unit_department', 'label' => 'Unit/Department', 'type' => 'text'],
                        ['key' => 'purpose_of_travel', 'label' => 'Purpose of Travel (Must be supported by attachments)', 'type' => 'textarea'],
                        ['key' => 'travel_type', 'label' => 'Please Check', 'type' => 'select', 'options' => ['Official Business', 'Official Time']],
                        ['key' => 'date_and_time', 'label' => 'Date and Time', 'type' => 'text'],
                        ['key' => 'destination', 'label' => 'Destination', 'type' => 'text'],
                    ],
                ],

                // ------------------------------------------------------------------
                // MHRMO Pass Slip for Personal Business (2026). No form number on the paper.
                // ------------------------------------------------------------------
                [
                    'key' => 'hr_pass_slip',
                    'name' => 'Pass Slip for Personal Business',
                    'formNo' => null,
                    'title' => 'PASS SLIP FOR PERSONAL BUSINESS',
                    'days' => 'Same day',
                    'requirements' => [],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'MUNICIPALITY OF ESPERANZA'],
                        'signatures' => [
                            ['role' => 'Printed Name and Signature of Employee', 'name' => 'submitter'],
                            ['role' => 'APPROVED: Head of Office/Authorized Representative', 'name' => 'step:Head of Office Approval'],
                            // The paper names the signer: MENA YVES D. BURDEOS, MGADH 1 (MHRMO).
                            ['role' => 'MHRMO', 'name' => 'step:HRMO Entry'],
                        ],
                    ],
                    'process' => [
                        'Submit Request',
                        [
                            'label' => 'Head of Office Approval',
                            'office' => 'submitter',
                            'signatory' => 'Head of Office/Authorized Representative',
                        ],
                        [
                            'label' => 'HRMO Entry',
                            'part' => '(To be filled up by HRMO)',
                            'signatory' => 'MHRMO',
                            'fields' => [
                                ['key' => 'hours_out', 'label' => 'Hour/s Out', 'type' => 'text'],
                                ['key' => 'deduction_from_vl', 'label' => 'Deduction from VL', 'type' => 'text'],
                            ],
                        ],
                    ],
                    'fields' => [
                        ['key' => 'full_name', 'label' => 'Printed Name of Employee', 'type' => 'text', 'required' => true],
                        ['key' => 'slip_date', 'label' => 'Date', 'type' => 'date'],
                        ['key' => 'permission_type', 'label' => 'Permission is hereby requested to', 'type' => 'select', 'options' => ['Leave the premises during Office hours', 'Deviate from my fixed time of arrival']],
                        ['key' => 'time_from', 'label' => 'From', 'type' => 'time'],
                        ['key' => 'time_to', 'label' => 'To', 'type' => 'time'],
                        ['key' => 'purpose', 'label' => 'Purpose (Optional)', 'type' => 'textarea'],
                    ],
                ],
            ],
        ],
        // MGSO internal forms, following the six papers the client emailed
        // (public/documents/email documents/MGSO), 2026-11-03.
        //
        // - Requester fields carry the paper's printed heading as `section`. Fields
        //   with no heading on the paper are left unsectioned.
        // - The office parts (driver's log, inspector's findings, ICS No., record of
        //   sales, disposition) are step fields, filled by that step's officer when
        //   approving.
        // - Step fields cannot be tables, so per-item office data is a textarea, one
        //   line per item.
        // - Certifications made by a step's officer are a `checkbox` with the paper's
        //   sentence, because statements print only under requester headings.
        'gso' => [
            'label' => 'General Services Office',
            'shortLabel' => 'MGSO',
            'icon' => 'warehouse',
            'forms' => [

                // ---------------------------------------------------------------
                // MGSO - Borrower's Slip.xlsx (Folio, MGSO COPY | BORROWER'S COPY)
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_borrowers_slip',
                    'name' => "Borrower's Slip",
                    'formNo' => "MGSO Borrower's Slip",
                    'title' => "BORROWER'S SLIP",
                    'days' => 'Same day',
                    'requirements' => ['Valid government-issued ID'],
                    'process' => [
                        'Submit Request',
                        ['label' => 'MGSO Approval', 'part' => 'APPROVAL', 'signatory' => 'Authorized MGSO Personnel'],
                    ],
                    'fields' => [
                        ['key' => 'borrower_name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'section' => "BORROWER'S INFORMATION"],
                        ['key' => 'office_agency', 'label' => 'Office/Agency', 'type' => 'text', 'section' => "BORROWER'S INFORMATION"],
                        ['key' => 'address', 'label' => 'Address', 'type' => 'text', 'section' => "BORROWER'S INFORMATION"],
                        ['key' => 'contact_no', 'label' => 'Contact No.', 'type' => 'text', 'section' => "BORROWER'S INFORMATION"],
                        ['key' => 'items', 'label' => 'Property Information', 'type' => 'table', 'section' => 'PROPERTY INFORMATION', 'rowIcon' => 'package', 'rowLabel' => 'Item', 'columns' => [
                            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number'],
                            ['key' => 'unit', 'label' => 'Unit of Measure'],
                            ['key' => 'description', 'label' => 'Item Description'],
                            ['key' => 'remarks', 'label' => 'Remarks'],
                        ]],
                        ['key' => 'date_of_return', 'label' => 'Date of Return', 'type' => 'date', 'section' => 'PROPERTY INFORMATION'],
                        ['key' => 'acknowledged', 'label' => 'The borrower acknowledges that the items are in good condition and is responsible and liable for any damages or loss to the item(s) borrowed.', 'type' => 'checkbox', 'section' => 'ACKNOWLEDGMENT'],
                    ],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza', 'MUNICIPAL GENERAL SERVICES OFFICE'],
                        'signatures' => [
                            ['role' => 'Signature of the Borrower', 'name' => null],
                            ['role' => 'Authorized MGSO Personnel', 'name' => 'step:MGSO Approval'],
                        ],
                        'copies' => ['MGSO COPY', "BORROWER'S COPY"],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - Driver's Trip Ticket-blue.docx, page 1
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_trip_ticket',
                    'name' => "Driver's Trip Ticket",
                    'formNo' => "MGSO Driver's Trip Ticket",
                    'title' => "DRIVER'S TRIP TICKET",
                    'days' => 'Same day',
                    'requirements' => ['Authorization from Administrative Official'],
                    'process' => [
                        'Submit Request',
                        ['label' => 'MGSO Authorization', 'signatory' => 'MGSO - Designate'],
                        ['label' => "Driver's Report", 'part' => 'To be filled up by the Driver:', 'fields' => [
                            ['key' => 'time_departure_garage', 'label' => '1. Time of Departure from office garage', 'type' => 'time', 'required' => true],
                            ['key' => 'time_arrival_destination', 'label' => '2. Time of arrival (per no. 4 above)', 'type' => 'time', 'required' => true],
                            ['key' => 'time_departure_destination', 'label' => '3. Time of departure (per no. 4 above)', 'type' => 'time', 'required' => true],
                            ['key' => 'time_arrival_garage', 'label' => '4. Time of arrival back at office garage', 'type' => 'time', 'required' => true],
                            ['key' => 'approx_distance', 'label' => '5. Approximate distance traveled (to and from) (miles/kms.)', 'type' => 'number'],
                            ['key' => 'fuel_balance_start', 'label' => '6. Gasoline issued, purchased and consumed: a. Balance in tank (liter/s)', 'type' => 'number'],
                            ['key' => 'fuel_issued_from_stock', 'label' => 'Add: b. Issued by office from stock (liter/s)', 'type' => 'number'],
                            ['key' => 'fuel_purchased', 'label' => 'c. Purchased during trip (liter/s)', 'type' => 'number'],
                            ['key' => 'fuel_total', 'label' => 'Total (liter/s)', 'type' => 'number'],
                            ['key' => 'fuel_used', 'label' => 'Deduct: d. Used during trip (to and from) (liter/s)', 'type' => 'number'],
                            ['key' => 'fuel_balance_end', 'label' => 'e. Balance in tank at the end of trip (liter/s)', 'type' => 'number'],
                            ['key' => 'gear_oil_used', 'label' => '7. Gear oil used (liter/s)', 'type' => 'number'],
                            ['key' => 'lubricant_oil_used', 'label' => '8. Lubricant oil used (liter/s)', 'type' => 'number'],
                            ['key' => 'grease_issued', 'label' => '9. Grease issued (liter/s)', 'type' => 'number'],
                            ['key' => 'speedometer_start', 'label' => '10. Speed meter readings, if any: At beginning of trip (miles/kms.)', 'type' => 'number'],
                            ['key' => 'speedometer_end', 'label' => 'At end of trip (miles/kms.)', 'type' => 'number'],
                            ['key' => 'distance_traveled', 'label' => 'Distance traveled (per No.5 above) (miles/kms.)', 'type' => 'number'],
                            ['key' => 'remarks', 'label' => '11. Remarks', 'type' => 'textarea'],
                            // Paper: "We HEREBY CERTIFIY that…" (typo corrected).
                            ['key' => 'travel_certified', 'label' => 'We HEREBY CERTIFY that we used this car on official business as stated.', 'type' => 'checkbox'],
                        ]],
                    ],
                    'fields' => [
                        ['key' => 'driver_name', 'label' => '1. Name of driver of the vehicle', 'type' => 'text', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                        ['key' => 'vehicle_plate_no', 'label' => '2. Government car to be used, Plate No.', 'type' => 'text', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                        ['key' => 'passengers', 'label' => '3. Name of authorized passenger/s', 'type' => 'textarea', 'hint' => 'One name per line', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                        ['key' => 'places_to_visit', 'label' => '4. Place/s to be visited', 'type' => 'text', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                        ['key' => 'purpose', 'label' => '5. Purpose/s', 'type' => 'textarea', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                        ['key' => 'trip_date', 'label' => '6. Date', 'type' => 'date', 'required' => true, 'section' => 'To be filled up by the Administrative Officials Authorizing Official Travel'],
                    ],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza'],
                        'signatures' => [
                            ['role' => 'MGSO - Designate', 'name' => 'step:MGSO Authorization'],
                            ['role' => 'Driver', 'name' => null],
                            ['role' => 'Passenger', 'name' => null],
                            ['role' => 'Passenger', 'name' => null],
                        ],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - Driver's Trip Ticket-blue.docx, page 2 (new form)
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_generator_trip_ticket',
                    'name' => "Generator's Trip Ticket",
                    'formNo' => "MGSO Generator's Trip Ticket",
                    'title' => "GENERATOR'S TRIP TICKET",
                    'days' => 'Same day',
                    'requirements' => ['Authorization from Administrative Official'],
                    'process' => [
                        'Submit Request',
                        'MGSO Authorization',
                        ['label' => "Operator's Report", 'part' => 'To be Filled-out by the Operator', 'fields' => [
                            // Paper: two 10-row tables "POWER ON | POWER OFF | DURATION". Step fields cannot be tables.
                            ['key' => 'power_log', 'label' => 'POWER ON / POWER OFF / DURATION', 'type' => 'textarea', 'required' => true, 'hint' => 'One line per run, e.g. 8:00 AM / 11:30 AM / 3 h 30 min'],
                            ['key' => 'fuel_balance_start', 'label' => 'Gasoline/Diesel issued, purchased and consumed: Balance in tank (Liters)', 'type' => 'number'],
                            ['key' => 'fuel_issued_from_stock', 'label' => 'Issued by office from stock (Liters)', 'type' => 'number'],
                            ['key' => 'fuel_purchased', 'label' => 'Add purchased during operation (Liters)', 'type' => 'number'],
                            ['key' => 'fuel_total', 'label' => 'TOTAL (Liters)', 'type' => 'number'],
                            ['key' => 'fuel_used', 'label' => 'DEDUCT: Used during whole operation (Liters)', 'type' => 'number'],
                            ['key' => 'fuel_balance_end', 'label' => 'Balance in tank at the end of the operation (Liters)', 'type' => 'number'],
                            ['key' => 'gear_oil_issued', 'label' => 'Gear Oil issued (Liters)', 'type' => 'number'],
                            // Paper: "Lubrucant" (typo corrected).
                            ['key' => 'lubricant_oil_issued', 'label' => 'Lubricant Oil issued (Liters)', 'type' => 'number'],
                            ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                            ['key' => 'operation_certified', 'label' => 'I HEREBY CERTIFY to the correctness of the above statement of record of operation.', 'type' => 'checkbox'],
                        ]],
                    ],
                    'fields' => [
                        ['key' => 'operator_name', 'label' => 'Name of the Operator', 'type' => 'text', 'required' => true, 'section' => 'To be filled up by the Administrative Officials Authorizing Official Use:'],
                        ['key' => 'equipment_type', 'label' => 'Type of Equipment', 'type' => 'text', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Use:'],
                        ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'textarea', 'section' => 'To be filled up by the Administrative Officials Authorizing Official Use:'],
                    ],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza'],
                        'signatures' => [
                            ['role' => 'Operator', 'name' => null],
                        ],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - FUEL PASS.xlsx (current tab 'fuel pass': one box = one pass)
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_fuel_pass',
                    'name' => 'Fuel Pass',
                    'formNo' => 'MGSO Fuel Pass',
                    'title' => 'FUEL PASS',
                    'days' => 'Same day',
                    'requirements' => ["Approved Driver's Trip Ticket"],
                    'process' => [
                        'Submit Request',
                        ['label' => 'GSO Approval', 'part' => 'Approved by:', 'signatory' => 'General Services Officer'],
                    ],
                    'fields' => [
                        ['key' => 'requesting_office', 'label' => 'Requesting Office', 'type' => 'text', 'required' => true],
                        ['key' => 'requestor_name', 'label' => "Requestor's Name", 'type' => 'text'],
                        ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'textarea'],
                        ['key' => 'fuel_type', 'label' => 'Fuel', 'type' => 'select', 'options' => ['Diesel', 'Gasoline', 'Others (Specify)']],
                        ['key' => 'fuel_type_other', 'label' => 'Others (Specify)', 'type' => 'text'],
                        // "____LITERS" is on the older DIESEL/GASOLINE tabs only, not the current one.
                        ['key' => 'liters', 'label' => 'Liters', 'type' => 'number'],
                    ],
                    'paper' => [
                        'letterhead' => ['LGU-ESPERANZA'],
                        'signatures' => [
                            ['role' => "Requestor's Name (Signature over Printed Name)", 'name' => 'submitter'],
                            ['role' => 'Approved by: General Services Officer', 'name' => 'step:GSO Approval'],
                        ],
                        'copies' => ['Lgu-copy'],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - Inventory Custodian slip.xlsx (Annex A.3, COA Circular 2022-004)
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_inventory_custodian_slip',
                    'name' => 'Inventory Custodian Slip',
                    'formNo' => 'Annex A.3',
                    'title' => 'INVENTORY CUSTODIAN SLIP',
                    'days' => '1-2 working days',
                    'requirements' => ['Approved Purchase Request / Delivery Receipt, if applicable'],
                    'process' => [
                        'Submit Request',
                        ['label' => 'Received from Property Custodian', 'part' => 'Received from:', 'signatory' => 'Signature Over Printed Name', 'fields' => [
                            ['key' => 'ics_no', 'label' => 'ICS No.', 'type' => 'text', 'required' => true],
                            ['key' => 'received_from', 'label' => 'Received from (printed name)', 'type' => 'text', 'help' => 'Leave blank to print your own name.'],
                        ]],
                    ],
                    'fields' => [
                        ['key' => 'entity_name', 'label' => 'Entity Name', 'type' => 'text', 'required' => true, 'default' => 'Municipal Government of Esperanza'],
                        ['key' => 'fund_cluster', 'label' => 'Fund Cluster', 'type' => 'text'],
                        // Item No. and Estimated Useful Life are the custodian's per-item entries. Step fields cannot be
                        // tables, so they stay columns here; the custodian checks them at approval.
                        ['key' => 'items', 'label' => 'Item Information', 'type' => 'table', 'rowIcon' => 'package', 'rowLabel' => 'Item', 'columns' => [
                            ['key' => 'quantity', 'label' => 'Qnty', 'type' => 'number'],
                            ['key' => 'unit', 'label' => 'Unit'],
                            ['key' => 'unit_cost', 'label' => 'Amount: Unit Cost', 'type' => 'number'],
                            ['key' => 'total_cost', 'label' => 'Amount: Total Cost', 'type' => 'number'],
                            ['key' => 'description', 'label' => 'Description'],
                            ['key' => 'item_no', 'label' => 'Item No.'],
                            ['key' => 'useful_life', 'label' => 'Estimated Useful Life'],
                        ]],
                        ['key' => 'alloted_amount', 'label' => 'ALLOTED AMOUNT', 'type' => 'number'],
                        ['key' => 'position_office', 'label' => 'Position/Office', 'type' => 'text', 'section' => 'Received by:'],
                    ],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'MUNICIPAL GOVERNMENT OF ESPERANZA', 'Esperanza, Masbate'],
                        'signatures' => [
                            ['role' => 'Received from: Signature Over Printed Name', 'name' => 'step:Received from Property Custodian'],
                            ['role' => 'Received by: Signature Over Printed Name', 'name' => 'submitter'],
                        ],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - Return Slip.xlsx (Folio, MGSO COPY | RETURNEE'S COPY)
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_return_slip',
                    'name' => 'Return Slip',
                    'formNo' => 'MGSO Return Slip',
                    'title' => 'RETURN SLIP',
                    'days' => 'Same day',
                    'requirements' => ["Original Borrower's Slip or Inventory Custodian Slip"],
                    'process' => [
                        'Submit Return',
                        ['label' => 'Item Inspection', 'part' => 'INSPECTION', 'signatory' => 'Name and Signature of the Inspector', 'fields' => [
                            ['key' => 'good_condition', 'label' => 'I hereby certify that the above items are in good condition.', 'type' => 'checkbox'],
                        ]],
                        ['label' => 'Received by MGSO', 'part' => 'RECEIVED BY:', 'signatory' => 'Authorized MGSO Personnel'],
                    ],
                    'fields' => [
                        ['key' => 'date_returned', 'label' => 'Date Returned', 'type' => 'date'],
                        ['key' => 'returned_by', 'label' => 'Returned by', 'type' => 'text', 'required' => true],
                        ['key' => 'office_address', 'label' => 'Office/Address', 'type' => 'text'],
                        ['key' => 'contact_no', 'label' => 'Contact No.', 'type' => 'text'],
                        ['key' => 'items', 'label' => 'Information on Returned Property', 'type' => 'table', 'section' => 'INFORMATION ON RETURNED PROPERTY', 'rowIcon' => 'package', 'rowLabel' => 'Item', 'columns' => [
                            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number'],
                            ['key' => 'unit', 'label' => 'Unit of Measure'],
                            ['key' => 'description', 'label' => 'Item Description'],
                            ['key' => 'remarks', 'label' => 'Remarks'],
                        ]],
                    ],
                    'paper' => [
                        'letterhead' => ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza', 'MUNICIPAL GENERAL SERVICES OFFICE'],
                        'signatures' => [
                            ['role' => 'Returned by: Signature', 'name' => null],
                            ['role' => 'Name and Signature of the Inspector', 'name' => 'step:Item Inspection'],
                            ['role' => 'Received by: Authorized MGSO Personnel', 'name' => 'step:Received by MGSO'],
                        ],
                        'copies' => ['MGSO COPY', "RETURNEE'S COPY"],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - For Inspection (Pre-Post and Waste Materials).docx, page 1
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_waste_materials_report',
                    'name' => 'Waste Materials Report',
                    'formNo' => 'Appendix 65',
                    'title' => 'WASTE MATERIALS REPORT',
                    'days' => '3-5 working days',
                    'requirements' => [],
                    'process' => [
                        'Submit Report',
                        ['label' => 'Certified Correct', 'signatory' => 'Signature over Printed Name of Supply and/or Property Custodian'],
                        ['label' => 'Disposal Approved', 'office' => 'Office of the Municipal Mayor', 'signatory' => 'Signature over Printed Name of Head of Agency/Entity or his/her Authorized Representative'],
                        ['label' => 'Certificate of Inspection', 'part' => 'CERTIFICATE OF INSPECTION: I hereby certify that the property enumerated above was disposed of as follows:', 'signatory' => 'Signature over Printed Name of Inspector Officer', 'fields' => [
                            // The paper's own four disposition lines; each takes the item number(s).
                            ['key' => 'items_destroyed', 'label' => 'Item ____ Destroyed', 'type' => 'text'],
                            ['key' => 'items_sold_private', 'label' => 'Item ____ Sold at Private sale', 'type' => 'text'],
                            ['key' => 'items_sold_auction', 'label' => 'Item ____ Sold at public auction', 'type' => 'text'],
                            ['key' => 'items_transferred', 'label' => 'Item ____ Transferred without cost to MGSO', 'type' => 'text'],
                            // Paper: per-item columns "Record of Sales: Official Receipt No. | Date | Amount" and a TOTAL row.
                            ['key' => 'record_of_sales', 'label' => 'Record of Sales: Official Receipt No., Date and Amount, per item sold', 'type' => 'textarea', 'hint' => 'One line per item, e.g. Item 2 / OR 1234567 / Nov 3, 2026 / 1,500.00'],
                            ['key' => 'sales_total', 'label' => 'TOTAL (Amount)', 'type' => 'number'],
                        ]],
                    ],
                    'fields' => [
                        ['key' => 'entity_name', 'label' => 'Entity Name', 'type' => 'text', 'required' => true, 'default' => 'Municipal Government of Esperanza'],
                        ['key' => 'fund_cluster', 'label' => 'Fund Cluster', 'type' => 'text'],
                        ['key' => 'place_of_storage', 'label' => 'Place of Storage', 'type' => 'text'],
                        ['key' => 'items', 'label' => 'Items for Disposal', 'type' => 'table', 'section' => 'ITEMS FOR DISPOSAL', 'rowIcon' => 'package', 'rowLabel' => 'Item', 'columns' => [
                            ['key' => 'item', 'label' => 'Item'],
                            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number'],
                            ['key' => 'unit', 'label' => 'Unit'],
                            ['key' => 'description', 'label' => 'Description'],
                        ]],
                    ],
                    'paper' => [
                        'letterhead' => [],
                        'signatures' => [
                            ['role' => 'Certified Correct: Signature over Printed Name of Supply and/or Property Custodian', 'name' => 'step:Certified Correct'],
                            ['role' => 'Disposal Approved: Signature over Printed Name of Head of Agency/Entity or his/her Authorized Representative', 'name' => 'step:Disposal Approved'],
                            ['role' => 'Certified Correct: Signature over Printed Name of Inspector Officer', 'name' => 'step:Certificate of Inspection'],
                            ['role' => 'Witness to Disposal: Signature over Printed Name of Witness', 'name' => null],
                        ],
                    ],
                ],

                // ---------------------------------------------------------------
                // MGSO - For Inspection (Pre-Post and Waste Materials).docx, page 2
                // ---------------------------------------------------------------
                [
                    'key' => 'gso_pre_post_repair_inspection',
                    'name' => 'Pre-Post Repair Inspection Report',
                    'formNo' => 'MGSO Pre-Post Repair Inspection Report',
                    'title' => 'Pre-Post Repair Inspection Report',
                    'days' => '3-5 working days',
                    'requirements' => ['Property/Equipment for repair'],
                    'process' => [
                        'Submit Repair Request',
                        ['label' => 'Head of Office Attestation', 'office' => 'submitter', 'part' => 'Attested:', 'signatory' => 'Head of Office'],
                        ['label' => 'Certified LGU Property', 'part' => 'CERTIFIED THAT THE ITEM FOR REPAIR IS LGU PROPERTY', 'signatory' => 'Chief, Supply and Property Management Office'],
                        ['label' => 'Pre-Repair Inspection', 'part' => 'PRE-REPAIR INSPECTION', 'signatory' => 'Print Name/Signature of Inspector', 'fields' => [
                            ['key' => 'inspector_position', 'label' => 'I certify under penalty of law that as ____ (position) of LGU I have carefully examined the above mentioned property of the LGU', 'type' => 'text', 'required' => true, 'hint' => 'Your position'],
                            ['key' => 'pre_repair_findings', 'label' => 'In my opinion, to make it useful again, the following should be done:', 'type' => 'textarea', 'required' => true],
                            // Paper: table "Materials | Quantitty". Step fields cannot be tables.
                            ['key' => 'materials_needed', 'label' => 'That the materials needed are as follows:', 'type' => 'textarea', 'hint' => 'One line per material: Materials / Quantity'],
                        ]],
                        ['label' => 'Post-Repair Inspection', 'part' => 'POST-REPAIR INSPECTION', 'signatory' => 'Print Name/Signature of Inspector', 'fields' => [
                            ['key' => 'pr_po_no', 'label' => 'Covered by approved PR and PO No.', 'type' => 'text', 'required' => true],
                            ['key' => 'materials_used', 'label' => 'I hereby certify under penalty of law that all of the following materials were used for property No. ____ covered by approved PR and PO No. ____', 'type' => 'textarea', 'required' => true, 'hint' => 'One material per line (1., 2., 3. …)'],
                            ['key' => 'good_working_condition', 'label' => 'I also certify that the said property is already in good working condition.', 'type' => 'checkbox'],
                        ]],
                    ],
                    'fields' => [
                        ['key' => 'property_description', 'label' => 'DESCRIPTION OF PROPERTY (vehicle / lab equipment etc.)', 'type' => 'text', 'required' => true, 'wide' => true],
                        ['key' => 'property_no', 'label' => 'Property No.', 'type' => 'text'],
                        ['key' => 'motor_no', 'label' => 'Motor No.', 'type' => 'text'],
                        ['key' => 'acquisition_date', 'label' => 'Acquisition Date', 'type' => 'date'],
                        ['key' => 'brand_make_model', 'label' => 'Brand/make/model', 'type' => 'text'],
                        ['key' => 'chassis_no', 'label' => 'Chassis No.', 'type' => 'text'],
                        ['key' => 'acquisition_cost', 'label' => 'Acquisition Cost', 'type' => 'text'],
                        ['key' => 'plate_no', 'label' => 'Plate No.', 'type' => 'text'],
                        ['key' => 'serial_no', 'label' => 'Serial No.', 'type' => 'text'],
                        ['key' => 'year_last_registration', 'label' => 'Year of last registration', 'type' => 'text'],
                        ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
                        ['key' => 'requesting_unit', 'label' => 'Requesting Unit', 'type' => 'text', 'section' => 'A. Requesting Unit'],
                        ['key' => 'reason_for_repair', 'label' => 'That it can no longer be used for the purpose it was intended due to the following:', 'type' => 'textarea', 'section' => 'A. Requesting Unit'],
                    ],
                    'paper' => [
                        'letterhead' => ['Municipality of Esperanza (LGU)'],
                        'statements' => [
                            'A. Requesting Unit' => 'I Certify under penalty of law that I have carefully examined the above mentioned property of the LGU.',
                        ],
                        'signatures' => [
                            ['role' => 'Chief, Supply and Property Management Office', 'name' => 'step:Certified LGU Property'],
                            ['role' => 'Print Name/Signature of end-user/Property Officer', 'name' => 'submitter'],
                            ['role' => 'Head of Office', 'name' => 'step:Head of Office Attestation'],
                            ['role' => 'Print Name/Signature of Inspector (Pre-Repair)', 'name' => 'step:Pre-Repair Inspection'],
                            ['role' => 'Printed Name/Signature of Office Head (Pre-Repair)', 'name' => null],
                            ['role' => 'Print Name/Signature of Inspector (Post-Repair)', 'name' => 'step:Post-Repair Inspection'],
                            ['role' => 'Printed Name/Signature of Office Head (Post-Repair)', 'name' => null],
                        ],
                    ],
                ],
            ],
        ],
        'agriculture' => [
            'label' => 'Municipal Agriculture Office',
            'shortLabel' => 'Agriculture',
            'icon' => 'wheat',
            'forms' => [
                [
                    'key' => 'agri_animal_health_monitoring',
                    'name' => 'Animal Health Monitoring',
                    'formNo' => 'Animal Health Monitoring Log',
                    'title' => 'ANIMAL HEALTH MONITORING',
                    'days' => 'Same day (field log)',
                    'requirements' => [],
                    'process' => ['Field Visit', 'Log Entry', 'Submission to Livestock Coordinator'],
                    'fields' => [
                        ['key' => 'log_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text'],
                        ['key' => 'entries', 'label' => 'Monitoring Entries', 'type' => 'table', 'rowIcon' => 'paw-print', 'rowLabel' => 'Animal', 'columns' => [
                            ['key' => 'farmer_name', 'label' => 'Name of Farmer'],
                            ['key' => 'species', 'label' => 'Species'],
                            ['key' => 'sex', 'label' => 'Sex'],
                            ['key' => 'age', 'label' => 'Age'],
                            ['key' => 'antibiotic', 'label' => 'Anti-biotic'],
                            ['key' => 'vitamins', 'label' => 'Vitamins'],
                            ['key' => 'deworming', 'label' => 'Deworming'],
                            ['key' => 'remarks', 'label' => 'Remarks'],
                        ]],
                    ],
                ],
                [
                    'key' => 'agri_dog_vaccination_report',
                    'name' => 'Dog Vaccination Report',
                    'formNo' => 'DA Form V2017-01 (Bureau of Animal Industry)',
                    'title' => 'DOG VACCINATION REPORT',
                    'days' => 'Same day (field log)',
                    'requirements' => [],
                    'process' => ['Vaccination Drive', 'Log Entry', 'Submission to Bureau of Animal Industry'],
                    'fields' => [
                        ['key' => 'date_reported', 'label' => 'Date Reported', 'type' => 'date', 'required' => true],
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text'],
                        ['key' => 'entries', 'label' => 'Client & Animal Information', 'type' => 'table', 'rowIcon' => 'dog', 'rowLabel' => 'Client', 'columns' => [
                            ['key' => 'first_name', 'label' => 'Owner First Name'],
                            ['key' => 'last_name', 'label' => 'Owner Last Name'],
                            ['key' => 'contact_no', 'label' => 'Contact No.'],
                            ['key' => 'animal_name', 'label' => 'Animal Name/Color/Breed'],
                            ['key' => 'species', 'label' => 'Species'],
                            ['key' => 'sex', 'label' => 'Sex'],
                            ['key' => 'age', 'label' => 'Age'],
                        ]],
                    ],
                ],
                [
                    'key' => 'agri_livestock_inventory',
                    'name' => 'Livestock and Poultry Inventory',
                    'formNo' => 'XII - Livestock and Poultry Inventory',
                    'title' => 'LIVESTOCK AND POULTRY INVENTORY',
                    'days' => 'N/A (annual survey)',
                    'requirements' => [],
                    'process' => ['Barangay Survey', 'Data Consolidation', 'Submission to Provincial Agriculture Office'],
                    'fields' => [
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text', 'required' => true],
                        // Trimmed to the most common livestock/poultry categories for
                        // usability — the paper form's full column set (Carabao,
                        // Baka, Swine, Goat, Karnero, Chicken, Pabo, Gangsa, Ducks,
                        // Horse, Anay/Butakal, Boto, Piso, Mungaan, Sonoy, Pato,
                        // Itik, Rabbit) can be added as "Other" line items.
                        ['key' => 'entries', 'label' => 'Farmer Inventory', 'type' => 'table', 'rowIcon' => 'wheat', 'rowLabel' => 'Farmer', 'columns' => [
                            ['key' => 'farmer_name', 'label' => 'Name of Farmer'],
                            ['key' => 'carabao', 'label' => 'Carabao (M/F)'],
                            ['key' => 'cattle', 'label' => 'Cattle/Baka (M/F)'],
                            ['key' => 'swine', 'label' => 'Swine'],
                            ['key' => 'goat', 'label' => 'Goat (M/F)'],
                            ['key' => 'chicken', 'label' => 'Chicken'],
                            ['key' => 'duck', 'label' => 'Duck/Itik (M/F)'],
                            ['key' => 'other', 'label' => 'Other'],
                            ['key' => 'total', 'label' => 'Total'],
                        ]],
                    ],
                ],
                [
                    'key' => 'agri_annual_target',
                    'name' => 'Livestock and Poultry Annual Target',
                    'formNo' => 'Livestock & Poultry Annual Target',
                    'title' => 'LIVESTOCK AND POULTRY ANNUAL TARGET',
                    'days' => 'N/A (annual planning)',
                    'requirements' => [],
                    'process' => ['Draft Target', 'Municipal Agriculturist Review', 'Submission to Provincial Office'],
                    'fields' => [
                        ['key' => 'targets', 'label' => 'Targets by Program', 'type' => 'table', 'rowIcon' => 'target', 'rowLabel' => 'Program', 'columns' => [
                            ['key' => 'particular', 'label' => 'Particular'],
                            ['key' => 'program', 'label' => 'Program (HEMSEP / Rabies / Hog Cholera / Newcastle)'],
                            ['key' => 'annual_target', 'label' => 'Annual Target'],
                            ['key' => 'notes', 'label' => 'Notes'],
                        ]],
                    ],
                ],
                [
                    'key' => 'agri_accomplishment_report',
                    'name' => 'Accomplishment Report',
                    'formNo' => 'MAO Accomplishment Report',
                    'title' => 'ACCOMPLISHMENT REPORT',
                    'days' => 'N/A (periodic report)',
                    'requirements' => [],
                    'process' => ['Draft Report', 'Submit to Municipal Agriculturist', 'Noted & Filed'],
                    'fields' => [
                        ['key' => 'period_covered', 'label' => 'Period Covered', 'type' => 'text', 'required' => true],
                        ['key' => 'employee_name', 'label' => 'Name', 'type' => 'text'],
                        ['key' => 'designation', 'label' => 'Designation/Assignment', 'type' => 'text'],
                        ['key' => 'activities', 'label' => 'Activities', 'type' => 'table', 'rowIcon' => 'calendar-check', 'rowLabel' => 'Activity', 'columns' => [
                            ['key' => 'date', 'label' => 'Date'],
                            ['key' => 'activity', 'label' => 'Activities Undertaken/Conducted'],
                            ['key' => 'output', 'label' => 'Output/Accomplishment'],
                        ]],
                    ],
                ],
            ],
        ],
        'mswd' => [
            'label' => 'Social Welfare and Development Office',
            'shortLabel' => 'MSWD',
            'icon' => 'hand-heart',
            'forms' => [
                [
                    'key' => 'mswd_erpat_form',
                    'name' => 'ERPAT Association Activity Form',
                    'formNo' => 'ERPAT Federation of Esperanza, Masbate',
                    'title' => 'EMPOWERMENT AND RE-AFFIRMATION OF PATERNAL ABILITIES (ERPAT)',
                    'days' => '1-2 working days',
                    'requirements' => ['List of ERPAT Association Officers for the barangay'],
                    'process' => ['Submit Activity Form', 'MSWD Review', 'Filing'],
                    'fields' => [
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text', 'required' => true],
                        ['key' => 'activity', 'label' => 'Activity', 'type' => 'text'],
                        ['key' => 'activity_date', 'label' => 'Date', 'type' => 'date'],
                        ['key' => 'venue', 'label' => 'Venue', 'type' => 'text'],
                        ['key' => 'president', 'label' => 'President', 'type' => 'text'],
                        ['key' => 'vice_president', 'label' => 'Vice President', 'type' => 'text'],
                        ['key' => 'secretary', 'label' => 'Secretary', 'type' => 'text'],
                        ['key' => 'treasurer', 'label' => 'Treasurer', 'type' => 'text'],
                        ['key' => 'attendees', 'label' => 'Attendees', 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Attendee', 'columns' => [
                            ['key' => 'name', 'label' => 'Name'],
                            ['key' => 'occupation', 'label' => 'Occupation'],
                            ['key' => 'age', 'label' => 'Age'],
                        ]],
                    ],
                ],
                [
                    // MSWD General Intake Sheet — primary intake/profile form ahead of any assistance processing.
                    'key' => 'mswd_general_intake_sheet',
                    'name' => 'General Intake Sheet',
                    'formNo' => 'MSWDO Case No.',
                    'title' => 'MSWDO General Intake Sheet',
                    'days' => 'Same day',
                    'requirements' => ['One (1) valid government-issued ID of client', 'Barangay Certification (if referred)'],
                    'process' => ['Interview', 'Social Worker Assessment', 'Recommendation of Services', 'Approval by Unit Head'],
                    'fields' => [
                        ['key' => 'client_last_name', 'label' => "Client's Last Name", 'type' => 'text', 'required' => true],
                        ['key' => 'client_first_name', 'label' => "Client's First Name", 'type' => 'text', 'required' => true],
                        ['key' => 'client_middle_name', 'label' => "Client's Middle Name", 'type' => 'text'],
                        ['key' => 'client_ext', 'label' => 'Ext. (Jr., Sr.)', 'type' => 'text'],
                        ['key' => 'client_sex', 'label' => 'Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                        ['key' => 'client_dob', 'label' => 'Date of Birth', 'type' => 'date'],
                        ['key' => 'client_age', 'label' => 'Age', 'type' => 'number'],
                        ['key' => 'present_address', 'label' => 'Present Address (Region/Province/City/Barangay/No.-St.-Purok)', 'type' => 'text'],
                        ['key' => 'relationship_to_beneficiary', 'label' => 'Relationship to Beneficiary', 'type' => 'text'],
                        ['key' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Separated', 'Other']],
                        ['key' => 'religion', 'label' => 'Religion', 'type' => 'text'],
                        ['key' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'default' => 'Filipino'],
                        ['key' => 'educational_attainment', 'label' => 'Highest Educational Attainment', 'type' => 'text'],
                        ['key' => 'skills_occupation', 'label' => 'Skills / Occupation', 'type' => 'text'],
                        ['key' => 'monthly_income', 'label' => 'Estimated Monthly Income', 'type' => 'text'],
                        ['key' => 'philhealth_no', 'label' => 'PhilHealth No.', 'type' => 'text'],
                        ['key' => 'mode_of_admission', 'label' => 'Mode of Admission', 'type' => 'select', 'options' => ['Walk-in', 'Referral']],
                        ['key' => 'referring_party', 'label' => 'Referring Party / Address', 'type' => 'text'],
                        ['key' => 'contact_no', 'label' => 'Contact No.', 'type' => 'text'],
                        ['key' => 'beneficiary_last_name', 'label' => "Beneficiary's Last Name", 'type' => 'text'],
                        ['key' => 'beneficiary_first_name', 'label' => "Beneficiary's First Name", 'type' => 'text'],
                        ['key' => 'beneficiary_middle_name', 'label' => "Beneficiary's Middle Name", 'type' => 'text'],
                        ['key' => 'beneficiary_ext', 'label' => 'Beneficiary Ext. (Jr., Sr.)', 'type' => 'text'],
                        ['key' => 'beneficiary_sex', 'label' => 'Beneficiary Sex', 'type' => 'select', 'options' => ['Male', 'Female']],
                        ['key' => 'beneficiary_dob', 'label' => 'Beneficiary Date of Birth', 'type' => 'date'],
                        ['key' => 'beneficiary_place_of_birth', 'label' => 'Beneficiary Place of Birth', 'type' => 'text'],
                        ['key' => 'family_composition', 'label' => "Beneficiary's Family Composition", 'type' => 'table', 'rowIcon' => 'user-round', 'rowLabel' => 'Family Member', 'columns' => [
                            ['key' => 'name', 'label' => 'Name (Last, First, Middle)'],
                            ['key' => 'age', 'label' => 'Age'],
                            ['key' => 'sex', 'label' => 'Sex'],
                            ['key' => 'birthdate', 'label' => 'Birthdate'],
                            ['key' => 'civil_status', 'label' => 'Civil Status'],
                            ['key' => 'relationship', 'label' => 'Relationship'],
                            ['key' => 'educational_attainment', 'label' => 'Educational Attainment'],
                            ['key' => 'occupation', 'label' => 'Skills / Occupation'],
                            ['key' => 'monthly_income', 'label' => 'Est. Monthly Income'],
                        ]],
                        ['key' => 'problems_presented', 'label' => 'Problem/s Presented', 'type' => 'text'],
                        ['key' => 'social_worker_assessment', 'label' => "Social Worker's Assessment", 'type' => 'text'],
                        ['key' => 'client_category', 'label' => 'Client Category', 'type' => 'select', 'options' => ['Children in Need of Special Protection', 'Youth in Need of Special Protection', 'Women in Especially Difficult Circumstances', 'Person with Disability', 'Family Head and Other Needy Adult']],
                        ['key' => 'client_sub_category', 'label' => 'Client Sub-Category', 'type' => 'text'],
                        ['key' => 'nature_of_service', 'label' => 'Nature of Service / Assistance', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => ['Counseling', 'Legal Assistance', 'Referral', 'Financial Assistance', 'Food Subsidy', 'Livelihood', 'Educational', 'Medical', 'Burial', 'Transportation', 'Others']],
                        ['key' => 'financial_assistance_amount', 'label' => 'Amount of Financial Assistance (₱)', 'type' => 'number'],
                        ['key' => 'mode_of_financial_assistance', 'label' => 'Mode of Financial Assistance', 'type' => 'select', 'options' => ['Cash', 'Check', 'Guarantee Letter']],
                        ['key' => 'source_of_assistance', 'label' => 'Source of Assistance', 'type' => 'select', 'options' => ['Regular Funds', 'Donation', 'Priority Development Assistance Fund', 'Legislator', 'Others']],
                        ['key' => 'material_assistance', 'label' => 'Material Assistance', 'type' => 'checkbox-group', 'allowOther' => true, 'options' => ['Food Pack', 'Used Clothing', 'Hot Meal', 'Assistance Device', 'Others']],
                        ['key' => 'name_of_payee', 'label' => 'Name of Payee', 'type' => 'text'],
                        ['key' => 'address_of_payee', 'label' => 'Address of Payee', 'type' => 'text'],
                    ],
                ],
                [
                    // Staff-side ledger for the DSWD Family Assistance Card in Emergencies and Disasters (FACED) —
                    // pairs with the citizen-facing FACED registration under Tulong's Food/Relief Assistance.
                    'key' => 'mswd_faced_assistance_record',
                    'name' => 'FACED — Family Assistance Record',
                    'formNo' => 'DSWD FACED — Official Use Only',
                    'title' => 'Family Assistance Card in Emergencies and Disasters (FACED)',
                    'days' => 'Per distribution',
                    'requirements' => ['Registered FACED serial number of the family head'],
                    'process' => ['Locate FACED Serial Number', 'Log Assistance Released', 'Family Head / Representative Signs'],
                    'fields' => [
                        ['key' => 'serial_number', 'label' => 'FACED Serial Number', 'type' => 'text', 'required' => true],
                        ['key' => 'family_head_name', 'label' => 'Name of Family Head', 'type' => 'text', 'required' => true],
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text', 'default' => 'Labangtaytay'],
                        ['key' => 'assistance_record', 'label' => 'Family Assistance Record', 'type' => 'table', 'rowIcon' => 'package', 'rowLabel' => 'Release', 'columns' => [
                            ['key' => 'date', 'label' => 'Date'],
                            ['key' => 'receiving_member', 'label' => 'Receiving Family Member'],
                            ['key' => 'emergency_disaster', 'label' => 'Emergency / Disaster'],
                            ['key' => 'assistance', 'label' => 'Assistance'],
                            ['key' => 'unit', 'label' => 'Unit'],
                            ['key' => 'quantity', 'label' => 'Quantity'],
                            ['key' => 'cost', 'label' => 'Cost'],
                            ['key' => 'provider', 'label' => 'Provider'],
                        ]],
                    ],
                ],
                [
                    'key' => 'erpat_association_officers',
                    'name' => 'ERPAT Association Officers',
                    'formNo' => 'ERPAT Federation of Esperanza, Masbate',
                    'title' => 'ERPAT Association Officers',
                    'days' => '1-2 working days',
                    'requirements' => ['Minutes of the officers\' election'],
                    'process' => ['Submit Officers List', 'MSWD Review', 'Filing'],
                    'fields' => [
                        ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'text', 'required' => true],
                        ['key' => 'president', 'label' => 'President', 'type' => 'text'],
                        ['key' => 'vice_president', 'label' => 'Vice President', 'type' => 'text'],
                        ['key' => 'secretary', 'label' => 'Secretary', 'type' => 'text'],
                        ['key' => 'treasurer', 'label' => 'Treasurer', 'type' => 'text'],
                        ['key' => 'auditor', 'label' => 'Auditor', 'type' => 'text'],
                        ['key' => 'public_relations_officers', 'label' => 'Public Relations Officers', 'type' => 'table', 'rowIcon' => 'megaphone', 'rowLabel' => 'PRO', 'columns' => [
                            ['key' => 'name', 'label' => 'Name'],
                        ]],
                        ['key' => 'peace_courting_officers', 'label' => 'Peace Courting Officers', 'type' => 'table', 'rowIcon' => 'handshake', 'rowLabel' => 'Officer', 'columns' => [
                            ['key' => 'name', 'label' => 'Name'],
                        ]],
                    ],
                ],
                [
                    'key' => 'erpat_quarterly_meeting',
                    'name' => 'ERPAT Quarterly Meeting Record',
                    'formNo' => 'ERPAT Sessions/Meetings',
                    'title' => 'Recording of ERPAT Sessions / Meetings',
                    'days' => 'Same day',
                    'requirements' => ['Attendance Sheet (attach separately)'],
                    'process' => ['Conduct Meeting', 'ERPAT Focal Person Records Minutes', 'Filing'],
                    'fields' => [
                        ['key' => 'meeting_no', 'label' => 'Quarterly Meeting No.', 'type' => 'text', 'required' => true],
                        ['key' => 'meeting_date', 'label' => 'Date', 'type' => 'date'],
                        ['key' => 'venue', 'label' => 'Venue', 'type' => 'text'],
                        ['key' => 'agenda', 'label' => 'Agenda of the Meeting', 'type' => 'table', 'rowIcon' => 'list-checks', 'rowLabel' => 'Agenda Item', 'columns' => [
                            ['key' => 'item', 'label' => 'Agenda Item'],
                        ]],
                        ['key' => 'learning_highlights', 'label' => 'Learning Highlights / Insights Identified by Participants', 'type' => 'text'],
                        ['key' => 'issues_concerns', 'label' => 'Identified Issues and Concerns', 'type' => 'text'],
                        ['key' => 'recommendation', 'label' => 'Recommendation', 'type' => 'text'],
                        ['key' => 'next_session_date', 'label' => 'Date of Next Session', 'type' => 'date'],
                        ['key' => 'next_session_venue', 'label' => 'Venue', 'type' => 'text'],
                    ],
                ],
                [
                    'key' => 'erpat_bvaw_kalipi_meeting',
                    'name' => 'BVAW Desk / KALIPI Quarterly Meeting Record',
                    'formNo' => 'MSWDO — BVAW Desk Officers / KALIPI',
                    'title' => 'Recording of BVAW Desk Officers / KALIPI Quarterly Meeting',
                    'days' => 'Same day',
                    'requirements' => ['Attendance Sheet (attach separately)'],
                    'process' => ['Conduct Meeting', 'BVAW Desk / KALIPI Secretary Records Minutes', 'Filing'],
                    'fields' => [
                        ['key' => 'meeting_no', 'label' => 'Quarter Meeting No.', 'type' => 'text', 'required' => true],
                        ['key' => 'meeting_date', 'label' => 'Date', 'type' => 'date'],
                        ['key' => 'venue', 'label' => 'Venue', 'type' => 'text'],
                        ['key' => 'agenda', 'label' => 'Agenda', 'type' => 'table', 'rowIcon' => 'list-checks', 'rowLabel' => 'Agenda Item', 'columns' => [
                            ['key' => 'item', 'label' => 'Agenda Item'],
                        ]],
                        ['key' => 'objectives', 'label' => 'Objectives of the Session / Meeting', 'type' => 'text'],
                        ['key' => 'report_of_results', 'label' => 'Report of Results of Activities Carried Out', 'type' => 'text'],
                        ['key' => 'learning_highlights', 'label' => 'Learning Highlights / Insights Identified by Participants', 'type' => 'text'],
                        ['key' => 'plan_of_action', 'label' => 'Plan of Action', 'type' => 'text'],
                        ['key' => 'next_meeting_date', 'label' => 'Date of Next Meeting', 'type' => 'date'],
                        ['key' => 'next_meeting_agenda', 'label' => 'Agenda', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ],
];
