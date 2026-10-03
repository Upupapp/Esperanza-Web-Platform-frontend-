<?php

// Esperanza Web Admin — Manual Document Validation & Application Decision Guide.
// Transcribed from the LGU-supplied "Esperanza_Web_Admin_Manual_Document_
// Validation_Application_Decision_Guide.pdf" so the admin review UI can
// surface the same universal framework, per-service checklists, and
// standard reasons the guide defines. Frontend reference data only — the
// guide itself is explicit that this supports a human admin's manual
// decision, not an automatic approval engine, so nothing here scores,
// authenticates, or decides anything on its own.

return [

    // ── Section: Decision Outcomes — the four outcomes every review ends in.
    // Keyed by the app's existing universal status name so a decision maps
    // straight onto a real status with no new status vocabulary invented.
    'decision_outcomes' => [
        'Approved' => [
            'label' => 'Approve',
            'icon' => 'check',
            'use_when' => 'All mandatory requirements are present; files are readable and relevant; identity/record details are materially consistent; eligibility is met; no unresolved authenticity concern remains.',
            'admin_action' => 'Proceed to the next processing milestone.',
        ],
        'Under Review' => [
            'label' => 'Needs Additional / Stronger Documents',
            'icon' => 'file-plus-2',
            'use_when' => 'The application is potentially valid but a required item is missing, unreadable, expired where validity matters, too weak to prove a fact, or lacks a needed front/back/page/signature/seal.',
            'admin_action' => 'Return to applicant with a specific document request and clear reason.',
        ],
        // Requirement-level only — one submitted file/photo is wrong,
        // mismatched, or unreadable and needs to be swapped out, as opposed
        // to the whole-application "Under Review" outcome above (which can
        // mean several things, not just a bad file). Rolls the whole
        // request up into "Under Review" (see confirmRequirementDecision)
        // so this stays a requirement-level label only, not a new
        // whole-request status.
        'Flagged for Replacement' => [
            'label' => 'Flag for Replacement',
            'icon' => 'flag',
            'use_when' => 'The specific file submitted for this requirement is the wrong document, mismatched, unreadable, or otherwise unusable, and the applicant needs to upload a corrected copy.',
            'admin_action' => 'Flag the exact requirement and reason; the applicant re-uploads and the item returns to review as Resubmitted.',
        ],
        'Under Verification' => [
            'label' => 'Needs Manual Verification',
            'icon' => 'search-check',
            'use_when' => 'Documents may be complete, but there is a material mismatch, authenticity concern, conflicting official records, complex eligibility question, or a fact that should be checked against the source office/original registry.',
            'admin_action' => 'Hold decision; verify with the issuing office, original record, interview/case assessment, or authorized supervisor.',
        ],
        'Rejected' => [
            'label' => 'Reject',
            'icon' => 'x',
            'use_when' => 'Clear ineligibility; wrong/unrelated applicant or service; confirmed fraudulent/altered document; mandatory requirement remains unsatisfied after reasonable opportunity to correct; or the program/service rules clearly prohibit approval.',
            'admin_action' => 'Record the objective reason. Do not use vague remarks such as "invalid" without explanation.',
        ],
    ],

    // ── Section: Source Labels — how confidently each service checklist
    // below is grounded in an actual Esperanza-supplied form.
    'source_labels' => [
        'Official Esperanza source' => 'A client-supplied Esperanza form/reference exists and was used as the primary basis.',
        'Placeholder-supported' => 'No complete official Esperanza form was available; the current frontend uses a placeholder/reference form for field shape or process guidance.',
        'Catalog + general guidance' => 'The current Esperanza frontend has a service/requirements list but no complete official citizen-fillable source form. Requirements in the app are treated as the working basis; general validation rules fill only procedural gaps.',
    ],

    // ── Section 1.1: Universal Document Checklist — applies to every
    // uploaded attachment regardless of service.
    'universal_checklist' => [
        ['check' => 'Readable', 'pass' => 'Text, photo, numbers, signatures and key details can be read at normal zoom.', 'if_not' => 'Needs Additional Documents: ask for a clearer scan/photo.'],
        ['check' => 'Complete', 'pass' => 'All required pages/sides are present; corners and important fields are not cropped.', 'if_not' => 'Needs Additional Documents: request missing page/front/back.'],
        ['check' => 'Correct type', 'pass' => 'The upload is the exact required document or an explicitly acceptable alternative.', 'if_not' => 'Needs Additional Documents if an acceptable substitute exists; Reject only if the applicant cannot/will not provide a mandatory document.'],
        ['check' => 'Applicant relevance', 'pass' => 'Name/relationship/record clearly connects the document to the applicant or beneficiary.', 'if_not' => 'Manual Verification for explainable mismatch; Reject if clearly unrelated/wrong applicant.'],
        ['check' => 'Consistency', 'pass' => 'Name, DOB, address and other key facts materially match the Master Profile and other records.', 'if_not' => 'Manual Verification or stronger proof for discrepancies; never reject on minor spelling/format differences alone.'],
        ['check' => 'Validity', 'pass' => 'Document is not expired when current validity is required; date is appropriate to the transaction.', 'if_not' => 'Request updated document; Reject only when current validity is mandatory and cannot be satisfied.'],
        ['check' => 'Issuer / official marks', 'pass' => 'Required seal, signature, license/registry/reference details are visible where the document type normally requires them.', 'if_not' => 'Request a complete/certified copy or manually verify with issuer.'],
        ['check' => 'No obvious alteration', 'pass' => 'No unexplained erasures, inconsistent fonts, overwritten data, cut-and-paste artifacts, or contradictory entries.', 'if_not' => 'Manual Verification first; Reject if alteration/fraud is confirmed.'],
        ['check' => 'File safety/format', 'pass' => 'File opens normally and is within supported type/size; no password lock that prevents review.', 'if_not' => 'Request a new accessible file.'],
    ],

    // ── Section 1.2: Evidence Strength Hierarchy.
    'evidence_hierarchy' => [
        ['level' => 1, 'name' => 'Self-declared / weak', 'evidence' => 'Typed statement, simple photo, screenshot, unsigned note.', 'use' => 'Useful as supporting context but normally insufficient alone for identity, civil status, eligibility, or official record claims.'],
        ['level' => 2, 'name' => 'Local / institutional', 'evidence' => 'Barangay certification, school record, employer certificate, utility/lease record, locally issued supporting document.', 'use' => 'Stronger support for residency, enrollment, employment, local circumstances.'],
        ['level' => 3, 'name' => 'Government / regulated source', 'evidence' => 'Government-issued ID, PSA civil-registry record, hospital/doctor record, official tax/land/business record.', 'use' => 'Preferred for identity, civil status, birth/death/marriage, medical facts, tax/property and regulated transactions.'],
        ['level' => 4, 'name' => 'Direct source verification', 'evidence' => 'Original registry lookup, issuing-office confirmation, in-person verification/interview/case assessment.', 'use' => 'Use when records conflict, authenticity is uncertain, eligibility is complex, or stronger proof is required.'],
    ],

    // ── Section 1.3: Mismatch Rule.
    'mismatch_rule' => 'If uploaded documents do not match the Resident Master Profile, do NOT automatically reject. First determine whether the mismatch is minor/correctable (spelling, old address, married name, encoding difference) or material (different person, incompatible DOB, unrelated record). Use Needs Additional Documents or Needs Manual Verification before rejection unless the mismatch clearly proves ineligibility or fraud.',

    // ── Section 1.4: Decision Tree.
    'decision_tree' => [
        'Is the required document present and readable? If NO → Needs Additional / Stronger Documents.',
        'Is it the correct document type and clearly connected to the applicant/beneficiary? If NO but correctable → Additional Documents; if clearly unrelated → Reject.',
        'Are key facts materially consistent with the Master Profile and other records? If NO → Manual Verification or stronger proof.',
        'Is the document current/valid where required and are required pages, signatures, seals or issuer details visible? If NO → Additional Documents or Manual Verification.',
        'Is there an authenticity/tampering concern? If YES → Manual Verification; if fraud/alteration is confirmed → Reject.',
        'For Tulong: does the applicant meet the program eligibility rules and purpose? If unclear → Manual Verification; if clearly ineligible → Reject.',
        'If all mandatory checks pass and no unresolved concern remains → Approve.',
    ],

    // ── Section 2 intro table: general Tulong validation checks (applies to
    // every assistance application in addition to its own service checklist).
    'tulong_general_checklist' => [
        ['check' => 'Esperanza residency / service coverage', 'approve' => 'Required residency evidence is present and consistent.', 'uncertain' => 'Request Barangay Residency/Certification or manually verify address.', 'reject' => 'Program explicitly requires Esperanza residency and applicant is confirmed outside coverage with no applicable exception.'],
        ['check' => 'Identity / claimant authority', 'approve' => 'Applicant/claimant identity and relationship to beneficiary are supported.', 'uncertain' => 'Request stronger ID/relationship proof.', 'reject' => 'Confirmed wrong claimant, unrelated record, or unauthorized application.'],
        ['check' => 'Program purpose', 'approve' => 'Facts and documents match the assistance being requested.', 'uncertain' => 'Request program-specific proof (medical, school, death, livelihood, etc.).', 'reject' => 'Request is clearly outside the program purpose.'],
        ['check' => 'Eligibility category', 'approve' => 'Applicant fits the relevant category based on current program rules.', 'uncertain' => 'Manual review/interview/case assessment.', 'reject' => 'Clearly ineligible under the applicable program rule.'],
        ['check' => 'Duplicate / active assistance', 'approve' => 'No prohibited duplicate active claim is identified.', 'uncertain' => 'Manual verification to determine whether multiple assistance is permitted.', 'reject' => 'Confirmed prohibited duplicate claim under the program rules.'],
        ['check' => 'Income / indigency where applicable', 'approve' => 'Required indigency/income evidence and assessment support eligibility.', 'uncertain' => 'Request stronger proof or social case assessment.', 'reject' => 'Clearly exceeds a mandatory eligibility threshold confirmed by current policy.'],
    ],

    // ── Section 3 intro table: general Dokyu validation checks.
    'dokyu_general_checklist' => [
        ['check' => 'Applicant identity', 'approve' => 'Required ID is readable and belongs to applicant/authorized requester.', 'uncertain' => 'Request stronger ID/authorization/relationship proof.', 'reject' => 'Confirmed wrong/unrelated applicant or fraudulent identity evidence.'],
        ['check' => 'Record/service fit', 'approve' => 'Uploaded evidence corresponds to the requested document/service.', 'uncertain' => 'Request correct supporting record.', 'reject' => 'Request cannot legally/administratively be served to this applicant or evidence is unrelated.'],
        ['check' => 'Record details', 'approve' => 'Names, dates, places, property/business details are sufficient for verification.', 'uncertain' => 'Request missing details or manual registry/source-office verification.', 'reject' => 'Confirmed no eligible/locatable record where the service requires an existing record.'],
        ['check' => 'Supporting requirements', 'approve' => 'All mandatory service requirements are present.', 'uncertain' => 'Request only the missing/stronger items.', 'reject' => 'Mandatory requirements remain unsatisfied after reasonable opportunity to correct.'],
        ['check' => 'Office verification', 'approve' => 'Required office checks (residency, tax, zoning, civil registry, etc.) clear.', 'uncertain' => 'Needs Manual Verification.', 'reject' => 'Authoritative office verification confirms ineligibility/invalidity.'],
    ],

    // ── Section 5: Standard Copy-Ready Reasons — selectable canned messages
    // an admin can drop straight into their remarks, then edit as needed.
    'standard_reasons' => [
        ['label' => 'Unreadable upload', 'message' => 'The uploaded document is not readable enough to verify the required information. Please upload a clearer photo or scan showing the complete document.'],
        ['label' => 'Cropped / incomplete', 'message' => 'The uploaded document is incomplete or cropped. Please upload the full page and all required sides/pages.'],
        ['label' => 'Missing front/back', 'message' => 'Please upload both the front and back of the required ID/document.'],
        ['label' => 'Wrong document type', 'message' => 'The uploaded file does not match the required document. Please upload: [required document].'],
        ['label' => 'Expired document', 'message' => 'The uploaded document is expired and a current document is required for this application. Please upload an updated copy.'],
        ['label' => 'Name mismatch', 'message' => 'The name on the uploaded document does not match the resident profile. Please provide a supporting document explaining the difference or update your resident profile if needed.'],
        ['label' => 'DOB mismatch', 'message' => 'The date of birth on the uploaded document conflicts with the resident profile. The application is on hold for verification. Please provide a stronger official record such as a PSA certificate or valid government ID.'],
        ['label' => 'Address mismatch', 'message' => 'The address on the uploaded document does not match the current resident profile. Please upload current proof of residency or supporting address documentation.'],
        ['label' => 'Missing signature/seal', 'message' => 'A required signature, seal, certification, or issuing-office detail is not visible. Please upload a complete certified copy.'],
        ['label' => 'Relationship proof needed', 'message' => 'The submitted documents do not sufficiently establish your relationship to the beneficiary/deceased/record holder. Please upload appropriate civil-registry or authorization evidence.'],
        ['label' => 'Stronger identity proof', 'message' => 'The current upload is not sufficient to confirm identity. Please provide a clear government-issued ID or another accepted official identity document.'],
        ['label' => 'Manual issuer verification', 'message' => 'The document appears complete, but the issuing office/registry must verify the record before a decision can be made.'],
        ['label' => 'Confirmed ineligible', 'message' => 'After review, the application does not meet the eligibility requirements for this service/program: [specific criterion].'],
        ['label' => 'Confirmed fraudulent/altered', 'message' => 'The submitted document was confirmed to be altered, fraudulent, or not issued for this applicant. The application is rejected and may be referred for appropriate administrative action.'],
    ],

    // ── Section 6: Admin Review Checklist — a general reference, not
    // per-service, shown once as a reminder of the review process.
    'admin_review_checklist' => [
        'Confirm the correct service/program and responsible office.',
        'Confirm applicant identity and, where relevant, relationship/authority.',
        'Check every mandatory attachment against the service-specific list.',
        'Check readability, completeness, validity, signatures/seals, and correct document type.',
        'Compare key facts with the Resident Master Profile and other uploaded records.',
        'If there is a mismatch, classify it as minor/correctable or material; do not reject automatically.',
        'For Tulong, confirm residency/coverage, program purpose, beneficiary category, duplicate-active-application rules, and income/indigency criteria where applicable.',
        'For Dokyu, confirm that records/details are sufficient for the issuing office to verify or issue the requested document.',
        'If authenticity or eligibility is uncertain, choose Needs Manual Verification and state exactly what must be verified.',
        'If more evidence is needed, request the minimum specific stronger document needed — do not ask for unrelated paperwork.',
        'Use Reject only for confirmed ineligibility, confirmed wrong/fraudulent material, or unresolved mandatory requirements after reasonable opportunity to correct.',
        'Record reviewer, date/time, decision, reason, and requested follow-up documents.',
    ],

    // ── Section 7: Source Basis and Known Gaps — surfaced per-service via
    // each entry's own 'source_basis' field above; this is the guide's own
    // running list of which areas are still provisional.
    'known_gaps' => [
        ['area' => 'Locational Clearance', 'status' => 'Placeholder-supported; no official Esperanza locational-clearance form in supplied files.', 'action' => 'Use this guide provisionally; replace with official MPDO rules/form once supplied.'],
        ['area' => 'Educational Assistance detailed form', 'status' => 'Placeholder-supported; general scholarship shape only.', 'action' => 'Use current frontend requirements provisionally; confirm official program criteria/academic thresholds.'],
        ['area' => 'Medical/Burial/Financial/Food Relief AICS', 'status' => 'No complete official Esperanza citizen application form found.', 'action' => 'Use catalog requirements + MSWDO/MDRRMO case assessment; confirm current program circulars before production.'],
        ['area' => 'Social Pension', 'status' => 'No official Esperanza pension-specific application form found.', 'action' => 'Use current catalog as demo/working rule; confirm eligibility/fees/requirements with OSCA/DSWD.'],
        ['area' => 'Cedula / RPT Clearance / Live Birth Copy', 'status' => 'No separate citizen-fillable request form found.', 'action' => 'Use current catalog requirements and issuing-office verification.'],
        ['area' => 'Business Permit New', 'status' => 'Generic placeholder exists; no complete official Esperanza source used in current form audit.', 'action' => 'Confirm current BPLO checklist/ordinance before production.'],
    ],

    // ── Section 2 & 3: per-service checklists, keyed by the exact service
    // name the guide uses. Each Dokyu/Tulong request's 'type' is mapped to
    // one of these keys (see $dokyuServiceGuideMap / $tulongServiceGuideMap
    // in document-requests.blade.php / assistance-requests.blade.php);
    // where a mock request's type has no equivalent service defined by the
    // LGU-supplied guide, the general Dokyu/Tulong checklist above is used
    // instead and the gap is surfaced rather than invented.
    'services' => [

        // ── Tulong services ─────────────────────────────────────────────
        'Medical Assistance (AICS)' => [
            'group' => 'Tulong', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'No complete official Esperanza AICS application form found.',
            'checks' => [
                ['check' => 'Valid government ID', 'accept' => 'ID is readable, current where required, and identifies applicant/claimant.', 'weak' => 'Request clearer/current ID or additional identity proof.', 'reject' => 'Confirmed wrong/unrelated identity or fraudulent ID.'],
                ['check' => 'Medical need', 'accept' => 'Medical Abstract or doctor prescription is readable, relates to beneficiary, and is reasonably current for the request.', 'weak' => 'Request updated medical abstract/prescription, diagnosis note, or source verification.', 'reject' => 'Medical document is unrelated/confirmed false or assistance purpose is clearly non-medical.'],
                ['check' => 'Financial obligation', 'accept' => 'Hospital bill or Statement of Account identifies patient/provider and outstanding or relevant charges.', 'weak' => 'Request current SOA/itemized bill if amount/status is unclear.', 'reject' => 'Bill is unrelated, fabricated, or no eligible medical expense exists.'],
                ['check' => 'Indigency', 'accept' => 'Barangay Certificate of Indigency is present where required and materially consistent with profile.', 'weak' => 'Request current/clear certificate or social case assessment.', 'reject' => 'Confirmed ineligible under applicable AICS/LGU criteria after assessment.'],
            ],
        ],
        'Burial Assistance (AICS)' => [
            'group' => 'Tulong', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'Placeholder burial material exists but no complete official Esperanza AICS form.',
            'checks' => [
                ['check' => 'Death Certificate', 'accept' => 'Certificate identifies the deceased and is readable/credible.', 'weak' => 'Request clearer/certified copy or manual registry verification.', 'reject' => 'Confirmed unrelated/false record.'],
                ['check' => 'Claimant ID', 'accept' => 'Valid ID identifies claimant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Wrong/unrelated claimant or fraudulent ID.'],
                ['check' => 'Relationship to deceased', 'accept' => 'Relationship is supported by civil record, affidavit, barangay certification or other appropriate proof.', 'weak' => 'Request stronger relationship evidence/manual verification.', 'reject' => 'Claimant is confirmed unauthorized/ineligible.'],
                ['check' => 'Indigency', 'accept' => 'Barangay Certificate of Indigency supports the request where required.', 'weak' => 'Request current certificate/case assessment.', 'reject' => 'Confirmed ineligible after assessment.'],
            ],
        ],
        'Educational Assistance' => [
            'group' => 'Tulong', 'office' => 'Office of the Municipal Mayor',
            'source_basis' => 'Placeholder-supported', 'source_note' => 'General scholarship field shape; official Esperanza detailed application form unavailable.',
            'checks' => [
                ['check' => 'Enrollment', 'accept' => 'Certificate of Enrollment is current and names applicant/school.', 'weak' => 'Request current enrollment certification.', 'reject' => 'Applicant is confirmed not enrolled when enrollment is mandatory.'],
                ['check' => 'Academic record', 'accept' => 'Report Card/GWA evidence is readable and meets the working catalog criterion (GWA 80+), subject to current program confirmation.', 'weak' => 'Request complete/current report card or school verification.', 'reject' => 'Confirmed below a mandatory threshold in the current approved program rules.'],
                ['check' => 'Indigency', 'accept' => 'Certificate of Indigency is present where required.', 'weak' => 'Request current/stronger indigency proof.', 'reject' => 'Confirmed ineligible under the program criteria.'],
                ['check' => 'Residency', 'accept' => 'Barangay Residency Certificate supports Esperanza residency.', 'weak' => 'Request current residency proof/manual verification.', 'reject' => 'Confirmed outside required coverage with no exception.'],
            ],
        ],
        'Financial Assistance (AICS)' => [
            'group' => 'Tulong', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'No complete official Esperanza AICS form found.',
            'checks' => [
                ['check' => 'Identity', 'accept' => 'Valid government ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Indigency', 'accept' => 'Barangay Certificate of Indigency is readable and relevant.', 'weak' => 'Request current certificate.', 'reject' => 'Confirmed ineligible after assessment.'],
                ['check' => 'Case basis', 'accept' => 'Interview/social case study supports the stated financial need.', 'weak' => 'Needs Manual Verification / social case assessment.', 'reject' => 'Case assessment confirms no eligible basis or false material information.'],
            ],
        ],
        'Food / Relief Assistance' => [
            'group' => 'Tulong', 'office' => 'Municipal Disaster Risk Reduction and Management Office',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'Disaster assistance references exist but current mobile item is generic.',
            'checks' => [
                ['check' => 'Barangay Certification', 'accept' => 'Certification supports residence/affected status as required.', 'weak' => 'Request current/clear certification.', 'reject' => 'Confirmed unrelated/non-covered applicant.'],
                ['check' => 'Identity', 'accept' => 'Valid government ID identifies applicant/household representative.', 'weak' => 'Request stronger ID or barangay verification.', 'reject' => 'Confirmed false/wrong identity.'],
                ['check' => 'Affected/eligible status', 'accept' => 'Incident/household circumstances match current relief criteria.', 'weak' => 'Manual verification with barangay/MDRRMO.', 'reject' => 'Confirmed not within eligible incident/program coverage.'],
            ],
        ],
        'Social Pension (Indigent Senior Citizen)' => [
            'group' => 'Tulong', 'office' => 'Office for Senior Citizens Affairs',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'No official Esperanza pension-specific application form found.',
            'checks' => [
                ['check' => 'Senior Citizen status', 'accept' => 'Senior Citizen ID or equivalent age/identity proof is valid.', 'weak' => 'Request stronger age/identity proof or OSCA verification.', 'reject' => 'Confirmed not eligible by age/category under current program rules.'],
                ['check' => 'No pension/income/support declaration', 'accept' => 'Required affidavit is complete and signed as applicable.', 'weak' => 'Request corrected/notarized/updated affidavit or manual assessment.', 'reject' => 'Confirmed materially false declaration or ineligible pension/income status under current rules.'],
                ['check' => 'Barangay Certification', 'accept' => 'Current certification supports residence/indigency as required.', 'weak' => 'Request current certification.', 'reject' => 'Confirmed outside coverage/ineligible.'],
                ['check' => 'Program verification', 'accept' => 'OSCA/DSWD verification confirms enrollment eligibility.', 'weak' => 'Needs Manual Verification with OSCA/DSWD.', 'reject' => 'Authoritative verification confirms ineligibility.'],
            ],
        ],
        'Solo Parent Cash Assistance' => [
            'group' => 'Tulong', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MSWD - SOLO Parent Application Form.xlsx',
            'checks' => [
                ['check' => 'Solo Parent ID', 'accept' => 'Valid/current Solo Parent ID is present where required.', 'weak' => 'Request current ID or proof of pending/valid solo-parent status.', 'reject' => 'Confirmed not recognized as eligible solo parent under current rules.'],
                ['check' => 'Children civil records', 'accept' => 'PSA Birth Certificate(s) identify child/children and support relationship.', 'weak' => 'Request clearer/certified copy or additional relationship proof.', 'reject' => 'Records are unrelated/confirmed false.'],
                ['check' => 'Barangay Certification', 'accept' => 'Certification supports local residence/status as required.', 'weak' => 'Request current certification.', 'reject' => 'Confirmed outside eligibility/coverage.'],
                ['check' => 'Circumstance / classification', 'accept' => 'Declared solo-parent circumstance is supported by available evidence and assessment.', 'weak' => 'Manual verification / request circumstance-specific evidence.', 'reject' => 'Confirmed circumstance does not meet current eligibility rules.'],
            ],
        ],
        'Senior Citizen ID Application (OSCA Membership)' => [
            'group' => 'Tulong', 'office' => 'Office for Senior Citizens Affairs',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MSWD - Senior Citizen Application Form.docx',
            'checks' => [
                ['check' => 'Age / DOB', 'accept' => 'PSA Birth Certificate or valid ID clearly proves date of birth and applicable senior age.', 'weak' => 'Request stronger/certified age proof.', 'reject' => 'Confirmed below required age with no valid exception.'],
                ['check' => 'Photo', 'accept' => 'Required recent ID photos are clear and show applicant.', 'weak' => 'Request compliant recent photo.', 'reject' => 'Reject only if applicant refuses/cannot provide mandatory photo after correction opportunity.'],
                ['check' => 'Barangay Certification', 'accept' => 'Certification supports local residency.', 'weak' => 'Request current/clear certification.', 'reject' => 'Confirmed non-resident where residency is mandatory.'],
                ['check' => 'Identity consistency', 'accept' => 'Name/DOB/civil data materially match profile.', 'weak' => 'Manual verification for married-name/encoding differences.', 'reject' => 'Confirmed different person/fraud.'],
            ],
        ],
        'PWD Registration (PRPWD)' => [
            'group' => 'Tulong', 'office' => 'Persons with Disability Affairs Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MSWD - PRPWD Form 2.pdf',
            'checks' => [
                ['check' => 'Disability evidence', 'accept' => 'Medical Certificate or school/employer assessment clearly identifies applicant and supports disability.', 'weak' => 'Request qualified/updated assessment or manual PDAO verification.', 'reject' => 'Confirmed no qualifying basis or fraudulent assessment.'],
                ['check' => 'Photo', 'accept' => 'Required 1x1 photo clearly shows applicant.', 'weak' => 'Request compliant photo.', 'reject' => 'Reject only after failure/refusal to provide mandatory photo.'],
                ['check' => 'Barangay Certification', 'accept' => 'Certification supports residency as required.', 'weak' => 'Request current certification.', 'reject' => 'Confirmed outside coverage if residency is mandatory.'],
                ['check' => 'Identity/profile match', 'accept' => 'Personal details materially match government ID/profile.', 'weak' => 'Manual verification for discrepancies.', 'reject' => 'Confirmed different person/fraud.'],
            ],
        ],
        'TUPAD Emergency Employment' => [
            'group' => 'Tulong', 'office' => 'Municipal Public Employment Service Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MPESO - DOLE TUPAD PROFILE FORM.pdf',
            'checks' => [
                ['check' => 'Identity', 'accept' => 'Valid government ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed false/wrong identity.'],
                ['check' => 'Residency', 'accept' => 'Barangay Certification of Residency is current and consistent.', 'weak' => 'Request current certification.', 'reject' => 'Confirmed outside eligible coverage.'],
                ['check' => 'Indigency if applicable', 'accept' => 'Certificate of Indigency is provided when required for applicant category.', 'weak' => 'Request certificate or MPESO assessment.', 'reject' => 'Confirmed ineligible under current TUPAD screening rules.'],
                ['check' => 'Worker/beneficiary classification', 'accept' => 'Declared worker classification is supported by circumstances and screening.', 'weak' => 'Manual MPESO verification/interview.', 'reject' => 'Confirmed category is not eligible under current program rules.'],
            ],
        ],
        'TESDA Skills Training Registration' => [
            'group' => 'Tulong', 'office' => 'Municipal Public Employment Service Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MPESO - TESDA-DPA Form 1 Registration Form (MIS 03-01).pdf',
            'checks' => [
                ['check' => 'Identity / birth record', 'accept' => 'Valid ID or Birth Certificate identifies applicant.', 'weak' => 'Request clearer/stronger identity evidence.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Photo', 'accept' => 'Required 2x2 photo is clear and appropriate.', 'weak' => 'Request compliant photo.', 'reject' => 'Reject only after failure/refusal to provide mandatory photo.'],
                ['check' => 'Training profile', 'accept' => 'Course/qualification and learner classification fields are complete enough for screening.', 'weak' => 'Request missing profile information/manual MPESO-TESDA screening.', 'reject' => 'Confirmed applicant does not meet the selected training/program criteria.'],
            ],
        ],
        'ERPAT Program Registration' => [
            'group' => 'Tulong', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MSWD - ERPAT FORMS.docx (registration portion)',
            'checks' => [
                ['check' => 'Identity', 'accept' => 'Valid government ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Barangay Certification', 'accept' => 'Certification supports local residence.', 'weak' => 'Request current certification.', 'reject' => 'Confirmed outside required coverage.'],
                ['check' => 'Program profile', 'accept' => 'Family/background and registration information are complete and plausible.', 'weak' => 'Request missing data or MSWDO interview.', 'reject' => 'Confirmed ineligible under current ERPAT participation rules.'],
            ],
        ],

        // ── Dokyu services ──────────────────────────────────────────────
        'Cedula (Community Tax Certificate)' => [
            'group' => 'Dokyu', 'office' => "Treasurer's Office",
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'No citizen-fillable official source found.',
            'checks' => [
                ['check' => 'Identity', 'accept' => 'Valid government-issued ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Income basis if applicable', 'accept' => 'Proof of income/Certificate of Employment is credible where needed to compute tax.', 'weak' => 'Request current/stronger income proof or Treasurer assessment.', 'reject' => 'Confirmed false material income evidence.'],
                ['check' => 'Assessment/payment', 'accept' => 'Treasurer computation and required fee are completed.', 'weak' => 'Manual Treasurer review for unclear tax basis.', 'reject' => 'Reject only if transaction cannot lawfully proceed or mandatory requirements remain unsatisfied.'],
            ],
        ],
        'Barangay Clearance' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY.CLEARANCE NEW .docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'Readable valid government ID identifies applicant.', 'weak' => 'Request clearer/current ID.', 'reject' => 'Wrong/unrelated identity or confirmed fraudulent ID.'],
                ['check' => 'Residency', 'accept' => 'Proof of residency supports current/local residence.', 'weak' => 'Request barangay certification, utility/lease record, or manual barangay verification.', 'reject' => 'Confirmed non-resident when local residency is required.'],
                ['check' => 'Purpose', 'accept' => 'Purpose is stated and not inconsistent with the clearance requested.', 'weak' => 'Request clarification.', 'reject' => 'Reject only when the requested use is clearly outside lawful/available service scope.'],
            ],
        ],
        'Certificate of Residency' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY. RESIDENCY.docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Barangay Clearance', 'accept' => 'Clearance is current/relevant and identifies applicant.', 'weak' => 'Request current/clear clearance.', 'reject' => 'Confirmed unrelated/false clearance.'],
                ['check' => 'Proof of residency', 'accept' => 'Utility bill, lease contract, or acceptable equivalent supports address/residency type.', 'weak' => 'Request stronger address proof/manual verification.', 'reject' => 'Confirmed applicant does not meet residency requirement.'],
            ],
        ],
        'Certificate of Indigency' => [
            'group' => 'Dokyu', 'office' => 'Municipal Social Welfare and Development Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY.INDIGENCY 2024-1.docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'ID identifies applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Barangay indigency certification', 'accept' => 'Certification supports local indigency claim.', 'weak' => 'Request current/clear certificate.', 'reject' => 'Confirmed false/unrelated certificate.'],
                ['check' => 'Interview/case assessment', 'accept' => 'MSWDO assessment supports indigency for stated purpose.', 'weak' => 'Needs Manual Verification / interview.', 'reject' => 'Assessment confirms ineligibility or material misrepresentation.'],
            ],
        ],
        'Business Permit (New Application)' => [
            'group' => 'Dokyu', 'office' => 'Business Permits and Licensing Office',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'Generic placeholder exists but no complete official Esperanza citizen source used.',
            'checks' => [
                ['check' => 'Business registration', 'accept' => 'DTI or SEC Registration matches business/applicant.', 'weak' => 'Request correct/current registration.', 'reject' => 'Confirmed unregistered/invalid business where registration is mandatory.'],
                ['check' => 'Barangay Business Clearance', 'accept' => 'Clearance matches business name/location.', 'weak' => 'Request current/correct clearance.', 'reject' => 'Confirmed unrelated/false clearance.'],
                ['check' => 'Locational/Zoning Clearance', 'accept' => 'Clearance supports proposed business location/use.', 'weak' => 'Needs additional clearance or zoning verification.', 'reject' => 'Authoritative zoning decision disallows use and cannot be cured.'],
                ['check' => 'Sanitary Permit', 'accept' => 'Current permit provided where required.', 'weak' => 'Request permit/inspection result.', 'reject' => 'Confirmed failure of mandatory sanitary requirement after due process.'],
                ['check' => 'Cedula', 'accept' => 'Current Cedula is present if required.', 'weak' => 'Request current Cedula.', 'reject' => 'Mandatory requirement remains unsatisfied.'],
            ],
        ],
        'Real Property Tax Clearance' => [
            'group' => 'Dokyu', 'office' => "Treasurer's Office",
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'No citizen-fillable official source found.',
            'checks' => [
                ['check' => 'Tax Declaration', 'accept' => 'Latest Tax Declaration identifies property/owner.', 'weak' => 'Request current/certified declaration.', 'reject' => 'Confirmed unrelated property/false record.'],
                ['check' => 'Last RPT receipt', 'accept' => 'Official Receipt supports payment history.', 'weak' => 'Request readable/current receipt or Treasurer verification.', 'reject' => 'Confirmed unpaid balance when clearance requires full settlement and balance is not settled.'],
                ['check' => 'Identity/authority', 'accept' => 'Valid ID and authority/relationship to property are adequate.', 'weak' => 'Request authorization/ownership proof.', 'reject' => 'Confirmed unauthorized requester.'],
            ],
        ],
        'Certificate of Live Birth (Certified Copy)' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Catalog + general guidance', 'source_note' => 'Official issued-output record exists; no separate citizen request form.',
            'checks' => [
                ['check' => 'Requester ID', 'accept' => 'Valid ID identifies requester.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong/fraudulent identity.'],
                ['check' => 'Record-locating details', 'accept' => 'Name, DOB, place/parents or other requested details are sufficient to locate record.', 'weak' => 'Request missing details or manual registry search.', 'reject' => 'Authoritative registry search confirms no record and service cannot be fulfilled as a certified-copy request.'],
                ['check' => 'Requester authority if restricted', 'accept' => 'Relationship/authorization is sufficient under current civil-registry release rules.', 'weak' => 'Request relationship/authorization proof/manual registrar review.', 'reject' => 'Confirmed requester is not authorized to receive the record.'],
            ],
        ],
        'Barangay Business Clearance' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY. BUSINESS CLEARANCE .docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'ID identifies owner/applicant.', 'weak' => 'Request stronger ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Business location', 'accept' => 'Lease contract, title or equivalent proves location/authority.', 'weak' => 'Request stronger location proof.', 'reject' => 'Confirmed no lawful connection to stated business location.'],
                ['check' => 'Business details', 'accept' => 'Business name/nature/capital/operating details are complete and plausible.', 'weak' => 'Request clarification/manual barangay verification.', 'reject' => 'Confirmed materially false information.'],
            ],
        ],
        'Barangay Certification (General Purpose)' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'Barangay Certification.docx; PSA CERTIFICATION.docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'ID identifies requester.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Purpose / fact to certify', 'accept' => 'Requested fact/purpose is clear and within barangay knowledge/authority.', 'weak' => 'Request supporting proof or manual barangay verification.', 'reject' => 'Barangay cannot lawfully/accurately certify the stated fact.'],
            ],
        ],
        'First Time Job Seeker Certificate (RA 11261)' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'First Time Job Seeker Certificate.docx',
            'checks' => [
                ['check' => 'Identity / birth record', 'accept' => 'Valid ID or Birth Certificate identifies applicant.', 'weak' => 'Request stronger/clearer identity proof.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Barangay residency', 'accept' => 'Proof supports required barangay residency.', 'weak' => 'Request current residency proof/manual barangay verification.', 'reject' => 'Confirmed not a qualified resident.'],
                ['check' => 'First-time status declaration', 'accept' => 'Applicant confirms first-time jobseeker status; barangay can verify as required.', 'weak' => 'Needs manual barangay verification if prior employment is uncertain.', 'reject' => 'Confirmed not eligible under applicable first-time jobseeker rules.'],
            ],
        ],
        'Application for Marriage License' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MCRO - Application for Marriage License.pdf',
            'checks' => [
                ['check' => 'PSA Birth Certificates', 'accept' => "Both parties' records are readable and match identities.", 'weak' => 'Request certified/clearer copies/manual registry verification.', 'reject' => 'Confirmed identity/age impediment that cannot legally be cured.'],
                ['check' => 'Cedula of both parties', 'accept' => 'Current/required community tax certificates are present.', 'weak' => 'Request missing/current Cedula.', 'reject' => 'Mandatory requirement remains unsatisfied.'],
                ['check' => 'CENOMAR', 'accept' => 'Current PSA CENOMAR(s) are present as required.', 'weak' => 'Request current/complete CENOMAR.', 'reject' => 'Authoritative record shows legal impediment unless properly resolved.'],
                ['check' => 'Parental consent/advice when age-triggered', 'accept' => 'Required consent/advice is present for applicable ages.', 'weak' => 'Request correct consent/advice/supporting proof.', 'reject' => 'Mandatory legal requirement remains unsatisfied.'],
                ['check' => 'Photos / identity consistency', 'accept' => 'Required 2x2 photos and identities of both parties are clear/consistent.', 'weak' => 'Request compliant photos or manual verification.', 'reject' => 'Confirmed different person/fraud.'],
            ],
        ],
        'Certified Copy of Marriage Certificate' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'Certified Copy of Marriage certificate.pdf (issued-output record visually inspected)',
            'checks' => [
                ['check' => 'Requester ID', 'accept' => 'Valid ID identifies requester.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Wrong/fraudulent identity.'],
                ['check' => 'Marriage record details', 'accept' => 'Names of spouses, date/place of marriage, and registry number if known are sufficient for lookup.', 'weak' => 'Request additional locating details/manual registry search.', 'reject' => 'Authoritative search confirms no matching record or requester is not authorized.'],
                ['check' => 'Relationship/authority where required', 'accept' => 'Requester is a party or otherwise authorized under current release rules.', 'weak' => 'Request authorization/relationship proof.', 'reject' => 'Confirmed unauthorized requester.'],
            ],
        ],
        'Delayed Registration of Birth' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MCRO - Certificate of Live Birth.pdf (delayed-registration affidavit pages)',
            'checks' => [
                ['check' => 'PSA non-registration certification', 'accept' => 'Certificate of Non-Registration supports delayed-registration case.', 'weak' => 'Request current/certified result/manual PSA verification.', 'reject' => 'Authoritative record shows an existing registration that makes delayed registration inappropriate.'],
                ['check' => 'Barangay late-registration certification', 'accept' => 'Certification supports identity/residence/birth circumstances.', 'weak' => 'Request current/clear certification.', 'reject' => 'Confirmed unrelated/false certification.'],
                ['check' => 'Supporting early-life records', 'accept' => 'Baptismal/school/other contemporaneous records are consistent where available.', 'weak' => 'Request stronger corroborating evidence.', 'reject' => 'Confirmed contradictory/fraudulent evidence that cannot be resolved.'],
                ['check' => 'Affidavit of two disinterested persons', 'accept' => 'Affidavit is complete and signatories are appropriate.', 'weak' => 'Request corrected/notarized affidavit/manual registrar review.', 'reject' => 'Confirmed false affidavit/material misrepresentation.'],
            ],
        ],
        'Delayed Registration of Death' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MCRO - Certificate of Death.pdf (delayed-registration affidavit page)',
            'checks' => [
                ['check' => 'PSA non-registration certification', 'accept' => 'Certificate of Non-Registration supports delayed case.', 'weak' => 'Request current/certified result/manual verification.', 'reject' => 'Existing registered death record makes delayed registration inappropriate.'],
                ['check' => 'Barangay death-registration certification', 'accept' => 'Certification supports death/local circumstances.', 'weak' => 'Request current/clear certification.', 'reject' => 'Confirmed unrelated/false certification.'],
                ['check' => 'Medical/death records', 'accept' => 'Physician/hospital records support death details where available.', 'weak' => 'Request stronger medical/death evidence or manual verification.', 'reject' => 'Confirmed fabricated/contradictory record that cannot be resolved.'],
                ['check' => 'Affidavit of two disinterested persons', 'accept' => 'Affidavit is complete/credible.', 'weak' => 'Request corrected affidavit/manual registrar review.', 'reject' => 'Confirmed false affidavit/material misrepresentation.'],
            ],
        ],
        'Certificate of Fetal Death' => [
            'group' => 'Dokyu', 'office' => 'Office of the Municipal Civil Registrar',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'MCRO - Certificate of Fetal Death.pdf',
            'checks' => [
                ['check' => 'Medical Certificate of Fetal Death', 'accept' => 'Signed by appropriate attending health professional/traditional birth attendant and identifies the case.', 'weak' => 'Request complete/signed medical certificate or source verification.', 'reject' => 'Confirmed false/unrelated medical certificate.'],
                ['check' => 'Burial/Cremation Permit', 'accept' => 'Permit is readable and relates to the fetal-death record.', 'weak' => 'Request complete/current permit/manual verification.', 'reject' => 'Confirmed unrelated/false permit.'],
                ['check' => 'Informant ID', 'accept' => 'Valid government ID identifies applicant/informant.', 'weak' => 'Request stronger ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Delayed registration affidavit when applicable', 'accept' => 'Two-disinterested-person affidavit is supplied where the case is delayed.', 'weak' => 'Request affidavit/correction/manual registrar verification.', 'reject' => 'Mandatory delayed-registration requirement remains unsatisfied or affidavit is confirmed false.'],
            ],
        ],
        'Barangay Certification for Late Registration of Birth' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY.CERTIFICATION LATE REGISTRATION.docx',
            'checks' => [
                ['check' => 'Valid ID', 'accept' => 'ID identifies applicant/requester.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Birth corroboration', 'accept' => 'Baptismal Certificate or School Record supports identity/birth facts where available.', 'weak' => 'Request stronger corroborating evidence/manual barangay verification.', 'reject' => 'Confirmed unrelated/false evidence.'],
                ['check' => 'Barangay knowledge/verification', 'accept' => 'Barangay can verify facts to be certified.', 'weak' => 'Needs Manual Verification.', 'reject' => 'Barangay cannot substantiate the requested certification.'],
            ],
        ],
        'Barangay Certification for Registration of Death' => [
            'group' => 'Dokyu', 'office' => 'Barangay Hall',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'BRGY.CERTIFICATION REGISTRATION OF DEATH.docx',
            'checks' => [
                ['check' => 'Claimant ID', 'accept' => 'ID identifies claimant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Relationship proof', 'accept' => 'Civil record/other proof supports relationship to deceased.', 'weak' => 'Request stronger relationship evidence.', 'reject' => 'Confirmed unauthorized/unrelated claimant.'],
                ['check' => 'Barangay verification', 'accept' => 'Barangay can verify death/local facts.', 'weak' => 'Needs Manual Verification.', 'reject' => 'Facts cannot be substantiated or are confirmed false.'],
            ],
        ],
        'Pet Registration' => [
            'group' => 'Dokyu', 'office' => 'Municipal Agriculture Office',
            'source_basis' => 'Official Esperanza source', 'source_note' => 'PET REGISTRATION FORM.docx',
            'checks' => [
                ['check' => 'Owner ID', 'accept' => 'Valid ID identifies pet owner/applicant.', 'weak' => 'Request stronger/current ID.', 'reject' => 'Confirmed wrong identity/fraud.'],
                ['check' => 'Rabies vaccination', 'accept' => 'Proof of vaccination is present when applicable and identifies pet/date/provider.', 'weak' => 'Request current vaccination proof or MAO verification.', 'reject' => 'Confirmed mandatory vaccination requirement is not satisfied under current rules.'],
                ['check' => 'Pet details', 'accept' => 'Species/sex/color/age/identifying details are complete enough for registration.', 'weak' => 'Request clarification/photo/manual MAO check.', 'reject' => 'Confirmed false/unregistrable case under current municipal rules.'],
            ],
        ],
        'Locational Clearance' => [
            'group' => 'Dokyu', 'office' => 'Municipal Planning & Development Office (MPDO)',
            'source_basis' => 'Placeholder-supported', 'source_note' => 'CPDO Application for Locational Clearance adapted to municipal context; no official Esperanza form available.',
            'checks' => [
                ['check' => 'Property right', 'accept' => 'TCT/OCT or Tax Declaration supports property/site and applicant authority.', 'weak' => 'Request current/certified property proof or authorization.', 'reject' => 'Confirmed no lawful authority over site.'],
                ['check' => 'Barangay Clearance', 'accept' => 'Clearance supports project/site.', 'weak' => 'Request current/correct clearance.', 'reject' => 'Confirmed unrelated/false clearance.'],
                ['check' => 'Location/Vicinity Map', 'accept' => 'Map clearly identifies site and access/location.', 'weak' => 'Request clearer/correct map.', 'reject' => 'Reject only if required location cannot be established after correction opportunity.'],
                ['check' => 'Site Development Plan when applicable', 'accept' => 'Plan is complete enough for evaluation of new construction/project.', 'weak' => 'Request complete plan or technical clarification.', 'reject' => 'Project cannot satisfy mandatory planning/zoning requirements after official evaluation.'],
                ['check' => 'Zoning/site evaluation', 'accept' => 'MPDO evaluation finds proposed use compatible or conditionally approvable.', 'weak' => 'Needs Manual Verification/site evaluation.', 'reject' => 'Authoritative zoning/site decision confirms non-compliance that cannot be cured.'],
            ],
        ],
    ],
];
