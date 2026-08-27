<?php

// Resolves a request's plain 'type' string (e.g. "Barangay Clearance") to
// the docKey/previewType/certTitle the Preview/Download/Print feature
// needs, for any Approved+ request that was never submitted through the
// citizen wizard (i.e. has no hand-captured formSnapshot) — which is most
// of the seeded demo data in admin/document-requests.blade.php and
// citizen/document-requests.blade.php, since those model an already-
// established queue, not a live submission history.
//
// Without this, only the couple of requests someone manually gave a
// formSnapshot to could ever show a document — every other Approved
// request would dead-end on "No prepared document on file" regardless of
// how many docKey templates exist. This table lets the Document panel
// synthesize a best-effort snapshot instead: the citizen's own name (and,
// where the docKey has one, a 'purpose' field) mapped onto whichever field
// that docKey's body template treats as the primary requester/subject name
// — every other field simply renders blank (the same dotted-line blank the
// templates already show for any field nobody filled in), which is honest:
// it's not fabricating case details, just skipping the "no document at
// all" dead end for data this platform was never actually given.
//
// certTitle is copied verbatim from the 'title' each docKey already carries
// in esperanza_barangay_documents.php / esperanza_municipal_documents.php —
// kept here too (rather than cross-referenced at render time) since it's a
// handful of static strings and this table is already the explicit,
// hand-authored source for everything else about the mapping.
//
// 'nameField' is omitted for docKeys with no single unambiguous subject
// (e.g. a marriage license has a groom AND a bride) — those still resolve
// and render, just without the name pre-filled.
return [
    // --- Barangay-level ---
    'Barangay Clearance' => ['docKey' => 'brgy_clearance', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CLEARANCE', 'nameField' => 'requester_name', 'purposeField' => 'purpose', 'purposeIsArray' => true],
    'Barangay Business Clearance' => ['docKey' => 'brgy_business_clearance', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY BUSINESS CLEARANCE', 'nameField' => 'applicant_name'],
    'Barangay Residency' => ['docKey' => 'brgy_residency', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY RESIDENCY', 'nameField' => 'requester_name', 'purposeField' => 'purpose', 'purposeIsArray' => true],
    'Barangay Indigency' => ['docKey' => 'brgy_indigency', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY INDIGENCY', 'nameField' => 'requester_name', 'purposeField' => 'purpose', 'purposeIsArray' => true],
    'First Time Jobseeker Certificate (RA 11261)' => ['docKey' => 'brgy_first_time_jobseeker', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'certSubtitle' => 'First Time Jobseekers Assistance Act (RA 11261)', 'nameField' => 'requester_name'],
    'Barangay Certification (General Purpose)' => ['docKey' => 'brgy_cert_general', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'nameField' => 'subject_name', 'purposeField' => 'purpose'],
    'Barangay Certification (Late Registration)' => ['docKey' => 'brgy_cert_late_registration', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'certSubtitle' => 'Supporting a Late/Delayed Civil Registration', 'nameField' => 'subject_name'],
    'Barangay Certification (Registration of Death)' => ['docKey' => 'brgy_cert_death_registration', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'certSubtitle' => 'Supporting a Death Registration', 'nameField' => 'subject_name'],
    // No dedicated docKey/Reference_forms source exists for these two — they
    // are content-shape-identical to a general barangay certification
    // ("this certifies that [name]... is of good standing... for [purpose]"),
    // so they resolve to the same general-purpose template rather than
    // dead-ending.
    'Certificate of Good Moral Character' => ['docKey' => 'brgy_cert_general', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'certSubtitle' => 'Good Moral Character', 'nameField' => 'subject_name', 'purposeField' => 'purpose'],
    'Certificate of Widowhood' => ['docKey' => 'brgy_cert_general', 'previewType' => 'barangay', 'certTitle' => 'BARANGAY CERTIFICATION', 'certSubtitle' => 'Widowhood', 'nameField' => 'subject_name', 'purposeField' => 'purpose'],

    // --- Municipal-level ---
    'Cedula' => ['docKey' => 'dokyu_cedula', 'previewType' => 'municipal_request', 'certTitle' => 'CEDULA (COMMUNITY TAX CERTIFICATE)', 'nameField' => 'full_name'],
    'Cedula (Community Tax Certificate)' => ['docKey' => 'dokyu_cedula', 'previewType' => 'municipal_request', 'certTitle' => 'CEDULA (COMMUNITY TAX CERTIFICATE)', 'nameField' => 'full_name'],
    'Certificate of Residency' => ['docKey' => 'dokyu_residency', 'previewType' => 'municipal_request', 'certTitle' => 'CERTIFICATE OF RESIDENCY', 'nameField' => 'requester_name', 'purposeField' => 'purpose'],
    'Certificate of Indigency' => ['docKey' => 'dokyu_indigency', 'previewType' => 'municipal_request', 'certTitle' => 'CERTIFICATE OF INDIGENCY', 'nameField' => 'requester_name', 'purposeField' => 'purpose'],
    'Business Permit (New)' => ['docKey' => 'dokyu_business_new', 'previewType' => 'municipal_request', 'certTitle' => 'BUSINESS PERMIT (NEW APPLICATION)', 'nameField' => 'applicant_name'],
    'Business Permit Renewal' => ['docKey' => 'dokyu_business_renewal', 'previewType' => 'municipal_request', 'certTitle' => 'BUSINESS PERMIT RENEWAL', 'nameField' => 'applicant_name'],
    'Real Property Tax Clearance' => ['docKey' => 'dokyu_rpt', 'previewType' => 'municipal_request', 'certTitle' => 'REAL PROPERTY TAX CLEARANCE', 'nameField' => 'owner_name'],
    'Locational Clearance' => ['docKey' => 'locational_clearance', 'previewType' => 'municipal_request', 'certTitle' => 'APPLICATION FOR LOCATIONAL CLEARANCE'],
    'Pet Registration' => ['docKey' => 'municipal_pet_registration', 'previewType' => 'municipal_request', 'certTitle' => 'PET REGISTRATION FORM', 'nameField' => 'owner_name'],
    'Senior Citizen ID' => ['docKey' => 'osca_senior_citizen', 'previewType' => 'municipal_request', 'certTitle' => 'APPLICATION FOR MEMBERSHIP', 'nameField' => 'full_name'],
    'Certificate of Live Birth (Certified Copy)' => ['docKey' => 'mcro_live_birth', 'previewType' => 'municipal_request', 'certTitle' => 'REQUEST FOR CERTIFICATE OF LIVE BIRTH'],
    'Certified Copy of Marriage Certificate' => ['docKey' => 'mcro_marriage', 'previewType' => 'municipal_request', 'certTitle' => 'REQUEST FOR CERTIFICATE OF MARRIAGE', 'nameField' => 'requester_name'],
    'Application for Marriage License' => ['docKey' => 'mcro_marriage_license', 'previewType' => 'municipal_request', 'certTitle' => 'APPLICATION FOR MARRIAGE LICENSE'],
    'Delayed Registration of Birth' => ['docKey' => 'mcro_delayed_registration', 'previewType' => 'municipal_request', 'certTitle' => 'AFFIDAVIT FOR DELAYED REGISTRATION', 'nameField' => 'subject_name'],
    'Delayed Registration of Death' => ['docKey' => 'mcro_delayed_registration', 'previewType' => 'municipal_request', 'certTitle' => 'AFFIDAVIT FOR DELAYED REGISTRATION', 'nameField' => 'subject_name'],
];
