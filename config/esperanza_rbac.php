<?php

// Static reference data describing the Esperanza LGU role-based access
// control model: modules → submodules → permission types, the 8 default
// system roles, and 11 sample personnel accounts used to demo "log in as
// this role" across the Administrative Portal. Not a model/business logic —
// same category as config/esperanza.php: shared display data consumed by
// Blade views and embedded as JSON for the frontend session simulation.

// ---------------------------------------------------------------
// Module → Submodule structure. Mirrors the real Dokyu / Tulong
// catalogues used elsewhere in the app so the RBAC model reflects
// actual Esperanza LGU workflows rather than generic placeholders.
// ---------------------------------------------------------------
$permissionModules = [
    [
        'key' => 'dokyu', 'name' => 'Dokyu (Document Requests)', 'icon' => 'file-text',
        // Every Dokyu and Tulong service declares 'reject' (QA run 2026-10-05).
        // 29 of them did not, so nobody could refuse a Cedula, a barangay
        // certificate or a pension application, though any request can be
        // rejected. Roles that approve a service also reject it by default.
        'submodules' => [
            // Flagged for review (see the published classification plan): this looks like
            // a duplicate of 'brgy_clearance' below from before the citizen catalogue and
            // RBAC model were connected. Left in place rather than silently removed since
            // it already has role grants attached.
            ['key' => 'dokyu_barangay_clearance', 'name' => 'Barangay Clearance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'dokyu_cedula', 'name' => 'Cedula (Community Tax Certificate)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'dokyu_residency', 'name' => 'Certificate of Residency', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'dokyu_indigency', 'name' => 'Certificate of Indigency', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'dokyu_business_new', 'name' => 'Business Permit (New Application)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'dokyu_business_renewal', 'name' => 'Business Permit Renewal', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'dokyu_rpt', 'name' => 'Real Property Tax Clearance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'dokyu_senior_id', 'name' => 'Senior Citizen ID', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            // Additional municipal documents (see config/esperanza_municipal_documents.php).
            // Keys match the citizen-facing document 'key' exactly so both sides stay in sync.
            ['key' => 'mcro_marriage', 'name' => 'Certificate of Marriage (Certified Copy / Registration)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'mcro_marriage_license', 'name' => 'Application for Marriage License', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'mcro_live_birth', 'name' => 'Certificate of Live Birth (Certified Copy / Registration)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'mcro_death', 'name' => 'Certificate of Death (Certified Copy / Registration)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'mcro_delayed_registration', 'name' => 'Affidavit for Delayed Registration', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'municipal_pet_registration', 'name' => 'Pet Registration', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'msw_pwd_id', 'name' => 'PWD ID (Person with Disability Registration)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'osca_senior_citizen', 'name' => 'Senior Citizen ID (OSCA Membership)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            // Barangay-issued documents (see config/esperanza_barangay_documents.php).
            // Keys match the citizen-facing document 'key' exactly so both sides stay in sync.
            // No 'assign' — a Barangay Secretary handles their own barangay's queue directly.
            ['key' => 'brgy_cert_death_registration', 'name' => 'Barangay Certification (Registration of Death)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_cert_late_registration', 'name' => 'Barangay Certification (Late Registration)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_business_clearance', 'name' => 'Barangay Business Clearance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_residency', 'name' => 'Barangay Residency', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_first_time_jobseeker', 'name' => 'First Time Jobseeker Certificate (RA 11261)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_indigency', 'name' => 'Barangay Indigency', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_clearance', 'name' => 'Barangay Clearance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_cert_national_id', 'name' => 'Barangay Certification (National ID / PSA Purpose)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_cert_land_property', 'name' => 'Barangay Certification (Land / Property)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_cert_general', 'name' => 'Barangay Certification (General Purpose)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_business_permit', 'name' => 'Barangay Business Permit', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
            ['key' => 'brgy_event_permit', 'name' => 'Barangay Event / Special Permit', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive']],
        ],
    ],
    [
        'key' => 'tulong', 'name' => 'Tulong (Citizen Assistance)', 'icon' => 'hand-heart',
        'submodules' => [
            ['key' => 'tulong_medical', 'name' => 'Medical Assistance (AICS)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'tulong_burial', 'name' => 'Burial Assistance (AICS)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'tulong_educational', 'name' => 'Educational Assistance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'tulong_financial', 'name' => 'Financial Assistance (AICS)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release', 'archive']],
            ['key' => 'tulong_food', 'name' => 'Food / Relief Assistance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_pension', 'name' => 'Social Pension (Indigent Senior Citizen)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_solo_parent', 'name' => 'Solo Parent Cash Assistance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_erpat', 'name' => 'ERPAT Membership Registration', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_tupad', 'name' => 'DOLE TUPAD Emergency Employment', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_tesda', 'name' => 'TESDA Skills Training Registration', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_livelihood_mapping', 'name' => 'Livelihood Business Mapping', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_kabuhayan', 'name' => 'Kabuhayan Program Beneficiary Application', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_business_plan', 'name' => 'Individual Business Plan', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_transport', 'name' => 'Transportation Assistance (AICS)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_shelter', 'name' => 'Emergency Shelter Assistance', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_assistive_device', 'name' => 'Assistive Device for Persons with Disability', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_counseling', 'name' => 'Individual Counseling and Psychosocial Support', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_marriage_counseling', 'name' => 'Marriage and Family Counseling', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_pmo', 'name' => 'Pre-Marriage Orientation (PMOC)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_pmc', 'name' => 'Pre-Marriage Counseling (Family Code Art. 16)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_pes', 'name' => 'Parent Effectiveness Service (PES)', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_social_case_study', 'name' => 'Social Case Study Report', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_referral_letter', 'name' => 'Referral or Guarantee Letter', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_cdc', 'name' => 'Child Development Center Enrollment', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_pfa', 'name' => 'Psychological First Aid / Stress Debriefing', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_cicl', 'name' => 'Children in Conflict with the Law', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_child_protection', 'name' => 'Children at Risk and Child Protection', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            ['key' => 'tulong_vawc', 'name' => 'VAWC Assistance and Referral', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'assign', 'release']],
            // Welfare round 2026-10-29: the office's group sessions (and the
            // certificates issued when one is closed), and its log of services
            // given to a person. release = close a session and issue certificates;
            // edit = void a record with a reason.
            ['key' => 'tulong_sessions', 'name' => 'Group Sessions & Certificates', 'perms' => ['view', 'create', 'edit', 'release']],
            ['key' => 'tulong_records', 'name' => 'Services Rendered Records', 'perms' => ['view', 'create', 'edit']],
        ],
    ],
    [
        'key' => 'constituents', 'name' => 'Constituent Management', 'icon' => 'users',
        'submodules' => [
            ['key' => 'const_resident', 'name' => 'Resident Profiles', 'perms' => ['view', 'create', 'edit', 'delete', 'export']],
            ['key' => 'const_household', 'name' => 'Household Profiles', 'perms' => ['view', 'create', 'edit', 'delete', 'export']],
            ['key' => 'const_family', 'name' => 'Family Profiles', 'perms' => ['view', 'create', 'edit', 'delete']],
            ['key' => 'const_barangay', 'name' => 'Barangay Profiles', 'perms' => ['view', 'edit', 'export']],
            ['key' => 'const_data_quality', 'name' => 'Data Quality Management', 'perms' => ['view', 'verify', 'assign', 'merge', 'resolve', 'view_sensitive', 'export']],
            ['key' => 'const_citizen_verification', 'name' => 'Citizen Account Verification', 'perms' => ['view', 'verify', 'reject']],
        ],
    ],
    [
        'key' => 'papeles', 'name' => 'Papeles (Document Repository)', 'icon' => 'folder-open',
        'submodules' => [
            ['key' => 'papeles_documents', 'name' => 'Citizen Documents', 'perms' => ['view', 'view_sensitive', 'verify', 'edit', 'export']],
        ],
    ],
    [
        'key' => 'payments', 'name' => 'Payments', 'icon' => 'receipt',
        'submodules' => [
            ['key' => 'pay_dokyu', 'name' => 'Dokyu Invoices & Receipts', 'perms' => ['view', 'review', 'approve', 'reject', 'export', 'print']],
            ['key' => 'pay_tulong', 'name' => 'Tulong Invoices & Receipts', 'perms' => ['view', 'review', 'approve', 'reject', 'export', 'print']],
        ],
    ],
    [
        'key' => 'communications', 'name' => 'Announcements & Directory', 'icon' => 'megaphone',
        'submodules' => [
            // B-011: 'reject' added. The announcement lifecycle is
            // Draft -> Review -> Publish -> Archive, but with no reject action
            // declared an announcement under review could never be rejected —
            // under deny-by-default there was no triple to grant.
            ['key' => 'comm_news', 'name' => 'News & Announcements', 'perms' => ['view', 'create', 'edit', 'delete', 'review', 'approve', 'reject', 'archive']],
            // B-037: 'reject' added. LATENT at time of writing — nothing
            // transitions an event yet — so this closes the gap before an
            // events transition endpoint can expose it, rather than after.
            ['key' => 'comm_events', 'name' => 'Events', 'perms' => ['view', 'create', 'edit', 'delete', 'review', 'approve', 'reject', 'archive']],
            ['key' => 'comm_directory', 'name' => 'Government Directory', 'perms' => ['view', 'create', 'edit', 'delete']],
            ['key' => 'comm_barangay_directory', 'name' => 'Barangay Directory', 'perms' => ['view', 'edit']],
            // B-011: 'review' and 'approve' added, bringing Banners in line with
            // News and Events. Without them a banner could be created and
            // archived but never published, because publishing goes through
            // review -> approve and neither action existed to grant.
            // B-037: 'reject' added, and this one was LIVE. Banners do go
            // through the editorial transition, so with reject undeclared it
            // resolved to `approve` — semantically wrong, though it failed safe
            // (approve is the stronger permission, so nobody gained authority).
            ['key' => 'comm_banners', 'name' => 'Banners', 'perms' => ['view', 'create', 'edit', 'delete', 'review', 'approve', 'reject', 'archive']],
            // Residents' feedback and support tickets (scope round 2026-10-15):
            // routed to an office, assigned, answered in a conversation and
            // resolved. They rode on set_general before, so only the two
            // administrators could answer them.
            ['key' => 'comm_feedback', 'name' => 'Feedback & Support Tickets', 'perms' => ['view', 'assign', 'edit']],
        ],
    ],
    [
        // Appointments (scope round 2026-10-15): an office or a barangay hall
        // sets the days, hours and slots it receives residents; residents
        // book a slot; staff confirm, complete or cancel. Shown as a tab on
        // the Dashboard, so no sidebar entry of its own.
        'key' => 'appointments', 'name' => 'Appointments', 'icon' => 'calendar-clock',
        'submodules' => [
            ['key' => 'appt_schedules', 'name' => 'Schedules & Slots', 'perms' => ['view', 'create', 'edit', 'delete']],
            ['key' => 'appt_bookings', 'name' => 'Bookings', 'perms' => ['view', 'approve', 'reject', 'edit']],
        ],
    ],
    [
        'key' => 'reports', 'name' => 'Reports & Analytics', 'icon' => 'chart-column',
        'submodules' => [
            ['key' => 'rep_population', 'name' => 'Population Reports', 'perms' => ['view', 'export', 'print']],
            ['key' => 'rep_assistance', 'name' => 'Assistance Reports', 'perms' => ['view', 'export', 'print']],
            ['key' => 'rep_document', 'name' => 'Document Reports', 'perms' => ['view', 'export', 'print']],
            ['key' => 'rep_financial', 'name' => 'Financial / Payment Reports', 'perms' => ['view', 'export', 'print']],
        ],
    ],
    [
        'key' => 'user_management', 'name' => 'User Management', 'icon' => 'user-cog',
        'submodules' => [
            ['key' => 'um_personnel', 'name' => 'Personnel Accounts', 'perms' => ['view', 'create', 'edit', 'delete']],
            ['key' => 'um_roles', 'name' => 'Roles & Permissions', 'perms' => ['view', 'create', 'edit', 'delete']],
            ['key' => 'um_audit', 'name' => 'Audit Logs', 'perms' => ['view', 'export']],
        ],
    ],
    [
        'key' => 'settings', 'name' => 'System Settings', 'icon' => 'settings',
        'submodules' => [
            ['key' => 'set_general', 'name' => 'General Settings', 'perms' => ['view', 'edit']],
            ['key' => 'set_branding', 'name' => 'Municipality Branding', 'perms' => ['view', 'edit']],
            ['key' => 'set_site_content', 'name' => 'Site Content (Privacy, Terms, Support)', 'perms' => ['view', 'edit']],
            ['key' => 'set_service_catalog', 'name' => 'Service Catalog (Dokyu/Tulong Fees & Requirements)', 'perms' => ['view', 'edit']],
            // System status, integrations and backups (scope round 2026-10-15):
            // for technical administrators only, so no default role but the
            // Super Administrator holds it (municipal_admin's settings bundle
            // leaves it out below).
            ['key' => 'set_system', 'name' => 'System Status & Integrations', 'perms' => ['view', 'edit']],
        ],
    ],
    [
        'key' => 'sakuna', 'name' => 'Sakuna (Disaster Risk & Emergency Ops)', 'icon' => 'shield-alert',
        'submodules' => [
            ['key' => 'sakuna_command', 'name' => 'Command Center', 'perms' => ['view', 'create', 'edit', 'dispatch', 'escalate', 'close', 'export']],
            ['key' => 'sakuna_vulnerability', 'name' => 'Vulnerability Assessment', 'perms' => ['view', 'create', 'edit', 'approve', 'export']],
            ['key' => 'sakuna_incidents', 'name' => 'Incidents', 'perms' => ['view', 'create', 'edit', 'validate', 'assign', 'dispatch', 'escalate', 'resolve', 'close', 'export']],
            ['key' => 'sakuna_centers', 'name' => 'Evacuation Centers', 'perms' => ['view', 'create', 'edit', 'open', 'close', 'assign', 'export']],
            ['key' => 'sakuna_evacuees', 'name' => 'Evacuees', 'perms' => ['view', 'create', 'edit', 'check_in', 'transfer', 'check_out', 'export']],
            ['key' => 'sakuna_resources', 'name' => 'Resources', 'perms' => ['view', 'create', 'edit', 'assign', 'deploy', 'return', 'export']],
            ['key' => 'sakuna_relief', 'name' => 'Relief Operations', 'perms' => ['view', 'create', 'edit', 'approve', 'release', 'export']],
            ['key' => 'sakuna_damage', 'name' => 'Damage Assessment', 'perms' => ['view', 'create', 'edit', 'validate', 'approve', 'export']],
            ['key' => 'sakuna_alerts', 'name' => 'Public Alerts', 'perms' => ['view', 'create', 'edit', 'review', 'approve', 'publish', 'cancel', 'archive', 'export']],
            ['key' => 'sakuna_reports', 'name' => 'Reports', 'perms' => ['view', 'export', 'print']],
            // Missing, found, deceased and unidentified persons, each case linked
            // to an incident (owner, 2026-10-30). close = close a resolved case.
            ['key' => 'sakuna_missing', 'name' => 'Missing Persons', 'perms' => ['view', 'create', 'edit', 'close']],
        ],
    ],
];

// ---------------------------------------------------------------
// Preset levels used to derive each default role's starting grant
// per module, so the 8 system roles stay realistic without having
// to hand-type every submodule/permission combination.
// ---------------------------------------------------------------
$presetLevels = [
    'full' => fn ($perms) => $perms,
    'process' => fn ($perms) => array_values(array_intersect($perms, ['view', 'create', 'edit'])),
    'approve' => fn ($perms) => array_values(array_intersect($perms, ['view', 'review', 'approve', 'reject', 'assign', 'release'])),
    'view_export' => fn ($perms) => array_values(array_intersect($perms, ['view', 'export', 'print'])),
    'view' => fn ($perms) => array_values(array_intersect($perms, ['view'])),
];

$buildPermissions = function (array $moduleLevels) use ($permissionModules, $presetLevels) {
    $grants = [];
    foreach ($permissionModules as $mod) {
        if (! isset($moduleLevels[$mod['key']])) {
            continue;
        }
        $apply = $presetLevels[$moduleLevels[$mod['key']]];
        foreach ($mod['submodules'] as $sub) {
            $grants[$sub['key']] = $apply($sub['perms']);
        }
    }

    return $grants;
};

$superAdminPermissions = [];
foreach ($permissionModules as $mod) {
    foreach ($mod['submodules'] as $sub) {
        $superAdminPermissions[$sub['key']] = $sub['perms'];
    }
}

// ---------------------------------------------------------------
// Default system roles. Super Administrator is the only one that
// is not editable, per the LGU governance model.
// ---------------------------------------------------------------
$roles = [
    [
        'key' => 'super_admin', 'name' => 'Super Administrator', 'icon' => 'crown',
        'color' => 'text-navy-700 bg-navy-50', 'users' => 1, 'editable' => false,
        'desc' => 'Unrestricted access to every module, including personnel, roles, and system configuration. This role cannot be edited, renamed, or deleted.',
        'permissions' => $superAdminPermissions,
    ],
    [
        'key' => 'municipal_admin', 'name' => 'Municipal Administrator', 'icon' => 'shield-check',
        'color' => 'text-brand-700 bg-brand-50', 'users' => 2, 'editable' => true,
        'desc' => 'Full operational control across Dokyu, Tulong, Constituents, Payments, and Communications, plus personnel and role management for the municipality. For the Office of the Mayor, leads the Municipal Information Officer: reviews what the Information Office sends for a second look, and can do everything it does.',
        'permissions' => $buildPermissions([
            'dokyu' => 'full', 'tulong' => 'full', 'constituents' => 'full', 'payments' => 'full',
            'communications' => 'full', 'reports' => 'view_export', 'user_management' => 'full', 'settings' => 'full',
            'sakuna' => 'full', 'papeles' => 'full',
        ]),
    ],
    [
        'key' => 'department_head', 'name' => 'Department Head', 'icon' => 'briefcase',
        'color' => 'text-purple-700 bg-purple-50', 'users' => 6, 'editable' => true,
        'desc' => 'Reviews, approves, and releases requests handled by their assigned department (e.g. MSWDO, Treasurer\'s Office, Civil Registrar). Approves and publishes Balita posts, and moderates their comments.',
        'permissions' => $buildPermissions([
            'dokyu' => 'approve', 'tulong' => 'approve', 'constituents' => 'view', 'payments' => 'approve', 'reports' => 'view_export',
        ]),
    ],
    [
        'key' => 'department_staff', 'name' => 'Department Staff', 'icon' => 'keyboard',
        'color' => 'text-emerald-700 bg-emerald-50', 'users' => 24, 'editable' => true,
        'desc' => 'Handles day-to-day encoding and processing of document and assistance requests within their department. Writes Balita posts and sends them for review.',
        // Prints their office's reports; Excel and the masterlist with names
        // stay with the Department Head (dashboards, 2026-10-14).
        'permissions' => array_merge(
            $buildPermissions([
                'dokyu' => 'process', 'tulong' => 'process', 'constituents' => 'view', 'payments' => 'view',
            ]),
            ['rep_assistance' => ['view', 'print'], 'rep_document' => ['view', 'print']]
        ),
    ],
    [
        'key' => 'front_desk', 'name' => 'Front Desk Officer', 'icon' => 'concierge-bell',
        'color' => 'text-orange-700 bg-orange-50', 'users' => 8, 'editable' => true,
        'desc' => 'First point of contact at the municipal hall — intakes new document and assistance requests and assists walk-in constituents. Writes Balita posts and sends them for review.',
        'permissions' => array_merge(
            $buildPermissions([
                'dokyu' => 'process', 'tulong' => 'process', 'constituents' => 'process', 'payments' => 'view', 'papeles' => 'view',
            ]),
            ['const_citizen_verification' => ['view', 'verify', 'reject']]
        ),
    ],
    [
        'key' => 'records_officer', 'name' => 'Records Officer', 'icon' => 'archive',
        'color' => 'text-cyan-700 bg-cyan-50', 'users' => 3, 'editable' => true,
        'desc' => 'Maintains resident, household, and barangay records, and prepares official copies of civil registry documents. Writes Balita posts and sends them for review.',
        'permissions' => $buildPermissions([
            'constituents' => 'full', 'dokyu' => 'process', 'reports' => 'view_export', 'papeles' => 'full',
        ]),
    ],
    [
        'key' => 'info_officer', 'name' => 'Municipal Information Officer', 'icon' => 'megaphone',
        'color' => 'text-rose-700 bg-rose-50', 'users' => 2, 'editable' => true,
        'desc' => 'Heads the Municipal Information Office: writes, reviews and publishes Balita, banners, occasions and events for the whole municipality, and reviews what its staff and the other offices write. Led by the Municipal Administrator, for the Office of the Mayor.',
        'permissions' => $buildPermissions([
            'communications' => 'full', 'reports' => 'view',
        ]),
    ],
    // The Municipal Information Office as a department (user, 2026-11-02):
    // its head is the Municipal Information Officer above, its staff write
    // and send for review. Held to their own office, like a department's.
    [
        'key' => 'mio_staff', 'name' => 'MIO Staff', 'icon' => 'pen-line',
        'color' => 'text-rose-700 bg-rose-50', 'users' => 3, 'editable' => true,
        'desc' => 'Municipal Information Office staff: write Balita posts, banners, occasions and events and send them for review. The Municipal Information Officer, the office head, approves, publishes, archives and deletes them.',
        'permissions' => [
            'comm_news' => ['view', 'create', 'edit', 'review'],
            'comm_banners' => ['view', 'create', 'edit', 'review'],
            'comm_events' => ['view', 'create', 'edit', 'review'],
        ],
    ],
    // The MDRRMO's two roles (owner, 2026-11-05): in the contract now, with the
    // grants they held on staging, where they were first made by hand. Held to
    // their own office, like a department's. Hand-authored, so none of the
    // loops below changes them.
    [
        'key' => 'mdrrmo_head', 'name' => 'MDRRMO Head', 'icon' => 'shield-alert',
        'color' => 'text-amber-700 bg-amber-50', 'users' => 1, 'editable' => true,
        'desc' => 'Heads the Municipal Disaster Risk Reduction and Management Office: runs Sakuna (incidents, evacuation, relief, resources, damage, public alerts and missing persons) and assigns its staff. Approves and publishes the MDRRMO\'s own Balita posts and posts about missing persons, and answers the office\'s feedback.',
        'permissions' => [
            'comm_feedback' => ['view', 'assign', 'edit'],
            'comm_news' => ['view', 'create', 'edit', 'delete', 'review', 'approve', 'reject', 'archive'],
            'const_barangay' => ['view'],
            'const_data_quality' => ['view'],
            'const_family' => ['view'],
            'const_household' => ['view'],
            'const_resident' => ['view'],
            'sakuna_alerts' => ['view', 'create', 'edit', 'review', 'approve', 'publish', 'cancel', 'archive', 'export'],
            'sakuna_centers' => ['view', 'create', 'edit', 'open', 'close', 'assign', 'export'],
            'sakuna_command' => ['view', 'create', 'edit', 'dispatch', 'escalate', 'close', 'export'],
            'sakuna_damage' => ['view', 'create', 'edit', 'validate', 'approve', 'export'],
            'sakuna_evacuees' => ['view', 'create', 'edit', 'check_in', 'transfer', 'check_out', 'export'],
            'sakuna_incidents' => ['view', 'create', 'edit', 'validate', 'assign', 'dispatch', 'escalate', 'resolve', 'close', 'export'],
            'sakuna_missing' => ['view', 'create', 'edit', 'close'],
            'sakuna_relief' => ['view', 'create', 'edit', 'approve', 'release', 'export'],
            'sakuna_reports' => ['view', 'export', 'print'],
            'sakuna_resources' => ['view', 'create', 'edit', 'assign', 'deploy', 'return', 'export'],
            'sakuna_vulnerability' => ['view', 'create', 'edit', 'approve', 'export'],
        ],
    ],
    [
        'key' => 'mdrrmo_staff', 'name' => 'MDRRMO Staff', 'icon' => 'life-buoy',
        'color' => 'text-amber-700 bg-amber-50', 'users' => 4, 'editable' => true,
        'desc' => 'Municipal Disaster Risk Reduction and Management Office staff: record incidents, evacuees, relief, resources, damage and missing persons in Sakuna, and draft public alerts. Write Balita posts and send them for review; the MDRRMO Head approves and publishes them.',
        'permissions' => [
            'comm_news' => ['view', 'create', 'edit', 'review'],
            'const_barangay' => ['view'],
            'const_data_quality' => ['view'],
            'const_family' => ['view'],
            'const_household' => ['view'],
            'const_resident' => ['view'],
            'sakuna_alerts' => ['view', 'create', 'edit'],
            'sakuna_centers' => ['view', 'create', 'edit'],
            'sakuna_command' => ['view', 'create', 'edit'],
            'sakuna_damage' => ['view', 'create', 'edit'],
            'sakuna_evacuees' => ['view', 'create', 'edit', 'check_in', 'transfer', 'check_out'],
            'sakuna_incidents' => ['view', 'create', 'edit', 'validate'],
            'sakuna_missing' => ['view', 'create', 'edit', 'close'],
            'sakuna_relief' => ['view', 'create', 'edit', 'release'],
            'sakuna_reports' => ['view', 'export', 'print'],
            'sakuna_resources' => ['view', 'create', 'edit'],
            'sakuna_vulnerability' => ['view', 'create', 'edit'],
        ],
    ],
    [
        'key' => 'barangay_secretary', 'name' => 'Barangay Secretary', 'icon' => 'map-pinned',
        'color' => 'text-teal-700 bg-teal-50', 'users' => 20, 'editable' => true,
        'desc' => 'Processes and releases their barangay\'s own certifications and clearances (residency, indigency, business clearance, etc.), manages their barangay\'s directory profile, and views resident records for their barangay. Writes Balita posts for their barangay and sends them for review. Always Barangay-scoped, never Municipal-wide.',
        'permissions' => array_merge(
            $buildPermissions(['constituents' => 'view']),
            [
                'comm_barangay_directory' => ['view', 'edit'],
                // Their barangay's dashboard and printed reports (2026-10-14).
                'rep_population' => ['view', 'print'],
                'rep_assistance' => ['view', 'print'],
                'rep_document' => ['view', 'print'],
                'rep_financial' => ['view', 'print'],
                'papeles_documents' => ['view', 'verify'],
                'const_citizen_verification' => ['view', 'verify', 'reject'],
                'brgy_cert_death_registration' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_cert_late_registration' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_business_clearance' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_residency' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_first_time_jobseeker' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_indigency' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_clearance' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_cert_national_id' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_cert_land_property' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_cert_general' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_business_permit' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
                'brgy_event_permit' => ['view', 'create', 'edit', 'review', 'approve', 'reject', 'release', 'archive'],
            ]
        ),
    ],
    [
        'key' => 'citizen', 'name' => 'Citizen', 'icon' => 'user-round',
        'color' => 'text-slate-600 bg-slate-100', 'users' => 8420, 'editable' => true,
        'desc' => 'Default role for residents registered on the Citizen Portal. Citizens manage only their own requests and profile — they have no access to Administrative Portal modules.',
        'permissions' => [],
    ],
];

// ---------------------------------------------------------------
// Sakuna (Disaster Risk Reduction & Emergency Operations) grants —
// hand-authored per submodule rather than the generic preset levels above,
// since Sakuna's action vocabulary (dispatch, validate, check_in, deploy...)
// doesn't map onto the view/create/edit/approve preset shape. Merged into
// each role's 'permissions' by role name below. Super Administrator gets
// full access automatically (its permissions cover every submodule of
// every module in $permissionModules); Municipal Administrator gets full
// access via the 'sakuna' => 'full' preset above.
// ---------------------------------------------------------------
$sakunaGrantsByRoleName = [
    'Department Head' => [
        'sakuna_command' => ['view', 'dispatch', 'escalate', 'close', 'export'],
        'sakuna_vulnerability' => ['view', 'approve', 'export'],
        'sakuna_incidents' => ['view', 'validate', 'assign', 'dispatch', 'escalate', 'resolve', 'close', 'export'],
        'sakuna_centers' => ['view', 'open', 'close', 'assign', 'export'],
        'sakuna_evacuees' => ['view', 'check_in', 'transfer', 'check_out', 'export'],
        'sakuna_resources' => ['view', 'assign', 'deploy', 'return', 'export'],
        'sakuna_relief' => ['view', 'approve', 'release', 'export'],
        'sakuna_damage' => ['view', 'validate', 'approve', 'export'],
        'sakuna_alerts' => ['view', 'approve', 'export'],
        'sakuna_reports' => ['view', 'export'],
    ],
    'Department Staff' => [
        'sakuna_command' => ['view', 'create', 'edit'],
        'sakuna_vulnerability' => ['view', 'create', 'edit'],
        'sakuna_incidents' => ['view', 'create', 'edit', 'validate', 'assign'],
        'sakuna_centers' => ['view', 'create', 'edit'],
        'sakuna_evacuees' => ['view', 'create', 'edit', 'check_in'],
        'sakuna_resources' => ['view', 'create', 'edit', 'assign'],
        'sakuna_relief' => ['view', 'create', 'edit'],
        'sakuna_damage' => ['view', 'create', 'edit', 'validate'],
        'sakuna_alerts' => ['view', 'create', 'edit'],
        'sakuna_reports' => ['view'],
    ],
    'Front Desk Officer' => [
        'sakuna_incidents' => ['view', 'create'],
        'sakuna_evacuees' => ['view', 'create', 'check_in'],
    ],
    'Records Officer' => [
        'sakuna_vulnerability' => ['view', 'export'],
        'sakuna_evacuees' => ['view', 'export'],
        'sakuna_damage' => ['view', 'export'],
        'sakuna_reports' => ['view', 'export', 'print'],
    ],
    'Municipal Information Officer' => [
        'sakuna_command' => ['view'],
        'sakuna_incidents' => ['view'],
        'sakuna_centers' => ['view'],
        'sakuna_evacuees' => ['view'],
        'sakuna_alerts' => ['view', 'create', 'edit', 'review', 'approve', 'publish', 'cancel', 'archive'],
        'sakuna_reports' => ['view', 'export', 'print'],
    ],
];

foreach ($roles as &$role) {
    if (isset($sakunaGrantsByRoleName[$role['name']])) {
        $role['permissions'] = array_merge($role['permissions'], $sakunaGrantsByRoleName[$role['name']]);
    }
}
unset($role);

// ---------------------------------------------------------------
// Scope round 2026-10-15: feedback, appointments and system status.
// An office's head runs its schedule and answers its feedback; staff and
// the front desk handle bookings and reply to feedback; a barangay
// secretary does both for the barangay hall. System status is for the
// Super Administrator (technical administrator) only.
// ---------------------------------------------------------------
$scopeRoundGrants = [
    'municipal_admin' => ['appt_schedules' => ['view', 'create', 'edit', 'delete'], 'appt_bookings' => ['view', 'approve', 'reject', 'edit']],
    'department_head' => ['appt_schedules' => ['view', 'create', 'edit', 'delete'], 'appt_bookings' => ['view', 'approve', 'reject', 'edit'], 'comm_feedback' => ['view', 'assign', 'edit']],
    'department_staff' => ['appt_schedules' => ['view'], 'appt_bookings' => ['view', 'approve', 'reject', 'edit'], 'comm_feedback' => ['view', 'edit']],
    'front_desk' => ['appt_schedules' => ['view'], 'appt_bookings' => ['view', 'approve', 'reject', 'edit'], 'comm_feedback' => ['view', 'edit']],
    'barangay_secretary' => ['appt_schedules' => ['view', 'create', 'edit', 'delete'], 'appt_bookings' => ['view', 'approve', 'reject', 'edit'], 'comm_feedback' => ['view', 'edit']],
];
foreach ($roles as &$role) {
    if ($role['key'] === 'municipal_admin') {
        unset($role['permissions']['set_system']);
    }
    if (isset($scopeRoundGrants[$role['key']])) {
        $role['permissions'] = array_merge($role['permissions'], $scopeRoundGrants[$role['key']]);
    }
}
unset($role);

// ---------------------------------------------------------------
// Balita for every officer (owner, 2026-10-19): "balita is something
// similar to a facebook newsfeed/posting only even the residents can post
// ... they should just differ in the level of things that they can do, for
// example only heads can approve a post or delete or archive".
//
// Every officer reads Balita, writes a post with photos and videos, and
// sends it for review; the server lets them change only their own posts,
// and a Barangay Secretary's go to their barangay. A Department Head also
// approves and publishes, sends back, archives and moderates the comments.
// The Information Officer, the Municipal Administrator and the Super
// Administrator keep everything, events and banners too.
//
// Residents do not post (owner, 2026-10-20: they "can only see and browse"
// Balita): comm_community, the moderation of residents' posts, is gone.
// ---------------------------------------------------------------
$balitaWriter = ['comm_news' => ['view', 'create', 'edit', 'review']];
$balitaGrants = [
    'department_head' => ['comm_news' => $superAdminPermissions['comm_news']],
    'department_staff' => $balitaWriter,
    'front_desk' => $balitaWriter,
    'records_officer' => $balitaWriter,
    'barangay_secretary' => $balitaWriter,
];
foreach ($roles as &$role) {
    if (isset($balitaGrants[$role['key']])) {
        $role['permissions'] = array_merge($role['permissions'], $balitaGrants[$role['key']]);
    }
}
unset($role);

// ---------------------------------------------------------------
// Welfare round 2026-10-29: a Department Head runs the office's sessions,
// closes them (the certificates go out then) and keeps the services-rendered
// log too; its 'approve' preset would give it no create on either.
// ---------------------------------------------------------------
$welfareGrants = [
    // ... and (owner, 2026-10-30: "in dokyu screen they can edit, add, modify
    // the dokyu offered") manages its own office's Dokyu and Tulong types from
    // those tabs; the backend keeps a head to their own office's types.
    'department_head' => ['tulong_sessions' => ['view', 'create', 'edit', 'release'], 'tulong_records' => ['view', 'create', 'edit'], 'set_service_catalog' => ['view', 'edit']],
];
foreach ($roles as &$role) {
    if (isset($welfareGrants[$role['key']])) {
        $role['permissions'] = array_merge($role['permissions'], $welfareGrants[$role['key']]);
    }
}
unset($role);

// ---------------------------------------------------------------
// Missing Persons (owner, 2026-10-30): only the MDRRMO's roles (above) and
// the Super Administrator create and update cases. Who reads them (owner,
// 2026-11-05): those three, the Municipal Administrator (whose
// 'sakuna' => 'full' preset would give it everything; it keeps view only)
// and the Municipal Information Officer. Department Head, Department Staff
// and Front Desk no longer see the cases or the Command Center widget.
// ---------------------------------------------------------------
$missingPersonsViewers = ['municipal_admin', 'info_officer'];
foreach ($roles as &$role) {
    if (in_array($role['key'], $missingPersonsViewers, true)) {
        $role['permissions'] = array_merge($role['permissions'], ['sakuna_missing' => ['view']]);
    }
}
unset($role);

// ---------------------------------------------------------------
// Sample personnel accounts — one (or more, to contrast Municipal vs.
// Barangay scope) per role, used to demo "log in as this role" on the
// LGU Personnel Login screen. Employee IDs are prefixed per role
// (SA/MA/DH/DS/FD/RO/IO) so the role is recognizable from the ID alone.
// ---------------------------------------------------------------
$accounts = [
    ['id' => 'SA-001', 'name' => 'Ricardo Villanueva', 'roleKey' => 'super_admin', 'dept' => 'Office of the Municipal Mayor', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'MA-001', 'name' => 'Juan Dela Cruz', 'roleKey' => 'municipal_admin', 'dept' => 'Office of the Municipal Mayor', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'DH-001', 'name' => 'Corazon Villareal', 'roleKey' => 'department_head', 'dept' => 'Municipal Social Welfare & Development Office', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'DH-002', 'name' => 'Marivic Ong', 'roleKey' => 'department_head', 'dept' => 'Office of the Municipal Civil Registrar', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'DH-003', 'name' => 'Leilani Domingo', 'roleKey' => 'department_head', 'dept' => 'Municipal Health Office', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'DS-001', 'name' => 'Ferdinand Cortez', 'roleKey' => 'department_staff', 'dept' => "Municipal Treasurer's Office", 'scope' => 'Municipal', 'status' => 'Archived'],
    ['id' => 'DS-002', 'name' => 'Michelle Anne Bordado', 'roleKey' => 'department_staff', 'dept' => 'Municipal Social Welfare & Development Office', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'FD-001', 'name' => 'Angeline Mercado', 'roleKey' => 'front_desk', 'dept' => 'Office of the Municipal Mayor', 'scope' => 'Municipal', 'status' => 'Pending Review'],
    ['id' => 'FD-002', 'name' => 'Rowena Aguilar', 'roleKey' => 'front_desk', 'dept' => 'Barangay Poblacion', 'scope' => 'Poblacion', 'status' => 'Approved'],
    ['id' => 'FD-003', 'name' => 'Michael Cabuslay', 'roleKey' => 'front_desk', 'dept' => 'Office of the Municipal Civil Registrar', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'RO-001', 'name' => 'Bienvenido Salazar', 'roleKey' => 'records_officer', 'dept' => "Municipal Treasurer's Office", 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'RO-002', 'name' => 'Dexter Villamor', 'roleKey' => 'records_officer', 'dept' => 'Barangay Santiago', 'scope' => 'Santiago', 'status' => 'Approved'],
    ['id' => 'IO-001', 'name' => 'Paolo Reyes', 'roleKey' => 'info_officer', 'dept' => 'ICT Office', 'scope' => 'Municipal', 'status' => 'Approved'],
    ['id' => 'BS-001', 'name' => 'Leonora Batac', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Poblacion', 'scope' => 'Poblacion', 'status' => 'Approved'],
    ['id' => 'BS-002', 'name' => 'Herminia Cordova', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Santiago', 'scope' => 'Santiago', 'status' => 'Approved'],
    // Labangtaytay is the first barangay fully onboarded (real document templates,
    // officials, and seal). Names below match config/esperanza_barangay_documents.php's
    // 'barangay_secretary' entry per barangay — keep the two in sync.
    ['id' => 'BS-003', 'name' => 'Jenneth A. Cachila', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Labangtaytay', 'scope' => 'Labangtaytay', 'status' => 'Approved'],
    ['id' => 'BS-004', 'name' => 'Rosalinda T. Bagsic', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Agoho', 'scope' => 'Agoho', 'status' => 'Approved'],
    ['id' => 'BS-005', 'name' => 'Milagros D. Fernandez', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Almero', 'scope' => 'Almero', 'status' => 'Approved'],
    ['id' => 'BS-006', 'name' => 'Corazon B. Ilagan', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Baras', 'scope' => 'Baras', 'status' => 'Approved'],
    ['id' => 'BS-007', 'name' => 'Teresita M. Ronquillo', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Domorog', 'scope' => 'Domorog', 'status' => 'Approved'],
    ['id' => 'BS-008', 'name' => 'Susana R. Palma', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Guadalupe', 'scope' => 'Guadalupe', 'status' => 'Approved'],
    ['id' => 'BS-009', 'name' => 'Anabelle F. Torion', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Iligan', 'scope' => 'Iligan', 'status' => 'Approved'],
    ['id' => 'BS-010', 'name' => 'Marilou G. Sarona', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Labrador', 'scope' => 'Labrador', 'status' => 'Approved'],
    ['id' => 'BS-011', 'name' => 'Josefina L. Roxas', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Libertad', 'scope' => 'Libertad', 'status' => 'Approved'],
    ['id' => 'BS-012', 'name' => 'Erlinda C. Nacario', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Magsaysay', 'scope' => 'Magsaysay', 'status' => 'Approved'],
    ['id' => 'BS-013', 'name' => 'Perla S. Villaruel', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Masbaranon', 'scope' => 'Masbaranon', 'status' => 'Approved'],
    ['id' => 'BS-014', 'name' => 'Gloria T. Manlangit', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Potingbato', 'scope' => 'Potingbato', 'status' => 'Approved'],
    ['id' => 'BS-015', 'name' => 'Lourdes M. Abrenica', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Rizal', 'scope' => 'Rizal', 'status' => 'Approved'],
    ['id' => 'BS-016', 'name' => 'Adelina R. Cortez', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay San Roque', 'scope' => 'San Roque', 'status' => 'Approved'],
    ['id' => 'BS-017', 'name' => 'Remedios A. Pantoja', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Sorosimbajan', 'scope' => 'Sorosimbajan', 'status' => 'Approved'],
    ['id' => 'BS-018', 'name' => 'Victoria C. Salceda', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Tawad', 'scope' => 'Tawad', 'status' => 'Approved'],
    ['id' => 'BS-019', 'name' => 'Felicidad B. Marasigan', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Tunga', 'scope' => 'Tunga', 'status' => 'Approved'],
    ['id' => 'BS-020', 'name' => 'Consolacion S. Empuerto', 'roleKey' => 'barangay_secretary', 'dept' => 'Barangay Villa', 'scope' => 'Villa', 'status' => 'Approved'],
];

$roleByKey = collect($roles)->keyBy('key');

$accounts = array_map(function ($account) use ($roleByKey, $permissionModules) {
    $role = $roleByKey[$account['roleKey']];
    $account['role'] = $role['name'];
    $account['roleIcon'] = $role['icon'];
    $account['roleColor'] = $role['color'];
    $account['permissions'] = $role['permissions'];
    $account['moduleKeys'] = collect($permissionModules)
        ->filter(fn ($mod) => collect($mod['submodules'])->contains(fn ($sub) => ! empty($account['permissions'][$sub['key']])))
        ->pluck('key')
        ->values()
        ->all();

    return $account;
}, $accounts);

return [
    'permission_types' => [
        'view' => 'View', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete',
        'review' => 'Review', 'approve' => 'Approve', 'reject' => 'Reject', 'release' => 'Release',
        'export' => 'Export', 'print' => 'Print', 'archive' => 'Archive',
        'verify' => 'Verify', 'assign' => 'Assign', 'merge' => 'Merge', 'resolve' => 'Resolve', 'view_sensitive' => 'View Sensitive',
        'dispatch' => 'Dispatch', 'escalate' => 'Escalate', 'validate' => 'Validate', 'open' => 'Open',
        // B-001: 'close' was in use on sakuna_command / sakuna_incidents /
        // sakuna_centers but never declared here — 27 declared against 28 used.
        // Harmless in this UI (a missing label), but an undeclared action is
        // ungrantable server-side, so Sakuna's close verb was unreachable.
        'close' => 'Close',
        'check_in' => 'Check In', 'transfer' => 'Transfer', 'check_out' => 'Check Out', 'deploy' => 'Deploy',
        'return' => 'Return', 'publish' => 'Publish', 'cancel' => 'Cancel',
    ],
    'permission_modules' => $permissionModules,
    'roles' => $roles,
    'accounts' => $accounts,
];
