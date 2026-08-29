<?php

// Static reference data for sample Citizen Portal accounts used to demo
// "log in as this resident" — same category as config/esperanza_rbac.php's
// admin accounts, but for citizens. One sample resident per barangay, so the
// barangay picker on the citizen login screen has someone to sign in as for
// every barangay in config/esperanza.php.
//
// Account status vocabulary, the two flagship demo accounts (Ronaldo
// Bautista / Marites Ferrer, including their exact emails), and the 6-step
// registration wizard shape are sourced directly from the Esperanza-Mobile
// Flutter app source (lib/models/citizen_account.dart, access_level.dart,
// screens/auth/register_screen.dart, services/citizen_session_service.dart,
// docs/USER_SIGNUP_AND_VERIFICATION_FLOW.md). Real status labels are
// Pending Review -> Under Verification -> Approved / Rejected, with Draft as
// a fallback for an unrecognized status (never a real persisted state — the
// mobile source is explicit that Draft is not reachable); each maps to one
// of three access tiers (Guest / Unverified / Verified) that gates
// Dokyu/Tulong access. A fresh registration always starts at exactly 35%
// profileCompleteness (hardcoded in register_screen.dart's submit step).
//
// Honesty note: `registeredAt`, `idUploaded`, `faceVerified`, and
// `termsAccepted` are NOT fields the mobile app actually persists on its
// CitizenAccount model — Terms/ID/Face-scan are ephemeral wizard state that
// only exist during registration itself, and no signup timestamp is stored
// anywhere in the mobile source. They're admin-side inferences added here
// so the Web Admin can show a meaningful onboarding record: reaching
// 'Pending Review' or any later status is only possible after passing all
// three gates (the wizard blocks "Continue" otherwise), so back-filling
// them as true for every account here is a safe, justified inference —
// just not a literal mobile data field.

// identityCheck models the LGU verification officer's manual comparison of
// the onboarding selfie against the uploaded ID photo — separate from the
// wizard's own idUploaded/faceVerified completion flags above, which only
// mean "the resident finished those steps," not "a human confirmed they
// match." 'Not Reviewed' is the default for anything still awaiting staff
// action; 'Match' / 'Mismatch' are the two possible verification outcomes.
$accounts = [
    [
        'id' => 'ESP-RES-2024-1102',
        'firstName' => 'Ronaldo',
        'lastName' => 'Bautista',
        'fullName' => 'Ronaldo Panganiban Bautista',
        'email' => 'ronaldo.bautista@email.com',
        'mobile' => '0918 224 5567',
        'barangay' => 'Labangtaytay',
        'purok' => 'Purok 2',
        'address' => 'Purok 2, Barangay Labangtaytay, Esperanza, Masbate',
        'birthdate' => 'August 22, 1990',
        'sex' => 'Male',
        'civilStatus' => 'Married',
        'occupation' => 'Fisherman',
        'residentSince' => 2015,
        'profileCompleteness' => 41,
        'status' => 'Pending Review',
        'registeredAt' => 'July 28, 2026',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2024-1044',
        'firstName' => 'Cristy',
        'lastName' => 'Bonghanoy',
        'fullName' => 'Cristy Pareja Bonghanoy',
        'email' => 'cristy.bonghanoy@email.com',
        'mobile' => '0919 502 7734',
        'barangay' => 'Baras',
        'purok' => 'Purok 2',
        'address' => 'Purok 2, Barangay Baras, Esperanza, Masbate',
        'birthdate' => 'March 15, 2001',
        'sex' => 'Female',
        'civilStatus' => 'Single',
        'occupation' => 'Student',
        'residentSince' => 2012,
        'profileCompleteness' => 90,
        'status' => 'Approved',
        'registeredAt' => 'January 20, 2025',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2024-1056',
        'firstName' => 'Lorna',
        'lastName' => 'Dimaano',
        'fullName' => 'Lorna Reyes Dimaano',
        'email' => 'lorna.dimaano@email.com',
        'mobile' => '0917 809 1265',
        'barangay' => 'Guadalupe',
        'purok' => 'Purok 1',
        'address' => 'Purok 1, Barangay Guadalupe, Esperanza, Masbate',
        'birthdate' => 'July 21, 1979',
        'sex' => 'Female',
        'civilStatus' => 'Widowed',
        'occupation' => 'Sari-Sari Store Owner',
        'residentSince' => 2001,
        'profileCompleteness' => 88,
        'status' => 'Approved',
        'registeredAt' => 'February 8, 2025',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2024-1071',
        'firstName' => 'Divina',
        'lastName' => 'Galvez',
        'fullName' => 'Divina Torres Galvez',
        'email' => 'divina.galvez@email.com',
        'mobile' => '0918 992 5501',
        'barangay' => 'Labrador',
        'purok' => 'Purok 3',
        'address' => 'Purok 3, Barangay Labrador, Esperanza, Masbate',
        'birthdate' => 'October 12, 1986',
        'sex' => 'Female',
        'civilStatus' => 'Married',
        'occupation' => 'Public School Teacher',
        'residentSince' => 2011,
        'profileCompleteness' => 95,
        'status' => 'Approved',
        'registeredAt' => 'May 5, 2025',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2024-1145',
        'firstName' => 'Analiza',
        'lastName' => 'Ibarra',
        'fullName' => 'Analiza Bautista Ibarra',
        'email' => 'analiza.ibarra@email.com',
        'mobile' => '0917 664 2278',
        'barangay' => 'Magsaysay',
        'purok' => 'Purok 5',
        'address' => 'Purok 5, Barangay Magsaysay, Esperanza, Masbate',
        'birthdate' => 'May 3, 1991',
        'sex' => 'Female',
        'civilStatus' => 'Married',
        'occupation' => 'OFW (on home leave)',
        'residentSince' => 2016,
        'profileCompleteness' => 68,
        'status' => 'Approved',
        'registeredAt' => 'July 14, 2025',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2026-2101',
        'firstName' => 'Teodoro',
        'lastName' => 'Villaflor',
        'fullName' => 'Teodoro Villaflor',
        'email' => 'teodoro.villaflor@email.com',
        'mobile' => '0918 442 1190',
        'barangay' => 'Libertad',
        'purok' => 'Purok 3',
        'address' => 'Purok 3, Barangay Libertad, Esperanza, Masbate',
        'birthdate' => 'May 14, 1992',
        'sex' => 'Male',
        'civilStatus' => 'Single',
        'occupation' => 'Tricycle Driver',
        'residentSince' => 2015,
        'profileCompleteness' => 48,
        'status' => 'Pending Review',
        'registeredAt' => 'July 3, 2026',
        'idUploaded' => true,
        'faceVerified' => true,
        'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1001', 'firstName' => 'Juan Miguel', 'lastName' => 'Dela Cruz',
        'fullName' => 'Juan Miguel Santos Dela Cruz', 'email' => 'juanmiguel.delacruz@email.com',
        'mobile' => '0917 220 3301', 'barangay' => 'Poblacion', 'purok' => 'Purok 1',
        'address' => 'Purok 1, Barangay Poblacion, Esperanza, Masbate', 'birthdate' => 'July 15, 1998',
        'sex' => 'Male', 'civilStatus' => 'Single', 'occupation' => 'Municipal Office Clerk',
        'residentSince' => 2012, 'profileCompleteness' => 88, 'status' => 'Approved',
        'registeredAt' => 'February 14, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1002', 'firstName' => 'Maria Angela', 'lastName' => 'Reyes',
        'fullName' => 'Maria Angela Dimaculangan Reyes', 'email' => 'mariaangela.reyes@email.com',
        'mobile' => '0918 336 4412', 'barangay' => 'Agoho', 'purok' => 'Purok 2',
        'address' => 'Purok 2, Barangay Agoho, Esperanza, Masbate', 'birthdate' => 'March 22, 1997',
        'sex' => 'Female', 'civilStatus' => 'Single', 'occupation' => 'Public School Teacher',
        'residentSince' => 2009, 'profileCompleteness' => 91, 'status' => 'Approved',
        'registeredAt' => 'April 3, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1003', 'firstName' => 'Christian Paolo', 'lastName' => 'De Leon',
        'fullName' => 'Christian Paolo Santos De Leon', 'email' => 'christianpaolo.deleon@email.com',
        'mobile' => '0919 442 5523', 'barangay' => 'Almero', 'purok' => 'Purok 4',
        'address' => 'Purok 4, Barangay Almero, Esperanza, Masbate', 'birthdate' => 'November 9, 1995',
        'sex' => 'Male', 'civilStatus' => 'Married', 'occupation' => 'Tricycle Driver',
        'residentSince' => 2006, 'profileCompleteness' => 85, 'status' => 'Approved',
        'registeredAt' => 'January 20, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1004', 'firstName' => 'Katrina Mae', 'lastName' => 'Villanueva',
        'fullName' => 'Katrina Mae Garcia Villanueva', 'email' => 'katrinamae.villanueva@email.com',
        'mobile' => '0920 558 6634', 'barangay' => 'Baras', 'purok' => 'Purok 3',
        'address' => 'Purok 3, Barangay Baras, Esperanza, Masbate', 'birthdate' => 'May 30, 1996',
        'sex' => 'Female', 'civilStatus' => 'Single', 'occupation' => 'Nurse (Rural Health Unit)',
        'residentSince' => 2011, 'profileCompleteness' => 93, 'status' => 'Approved',
        'registeredAt' => 'June 11, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1005', 'firstName' => 'Rafael John', 'lastName' => 'Mendoza',
        'fullName' => 'Rafael John Villanueva Mendoza', 'email' => 'rafaeljohn.mendoza@email.com',
        'mobile' => '0906 664 7745', 'barangay' => 'Domorog', 'purok' => 'Purok 1',
        'address' => 'Purok 1, Barangay Domorog, Esperanza, Masbate', 'birthdate' => 'September 12, 1994',
        'sex' => 'Male', 'civilStatus' => 'Married', 'occupation' => 'Farmer',
        'residentSince' => 2004, 'profileCompleteness' => 80, 'status' => 'Approved',
        'registeredAt' => 'August 28, 2024', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1006', 'firstName' => 'Janine Nicole', 'lastName' => 'Alcantara',
        'fullName' => 'Janine Nicole Bautista Alcantara', 'email' => 'janinenicole.alcantara@email.com',
        'mobile' => '0908 770 8856', 'barangay' => 'Guadalupe', 'purok' => 'Purok 2',
        'address' => 'Purok 2, Barangay Guadalupe, Esperanza, Masbate', 'birthdate' => 'January 8, 1998',
        'sex' => 'Female', 'civilStatus' => 'Single', 'occupation' => 'Call Center Agent (Work-from-home)',
        'residentSince' => 2013, 'profileCompleteness' => 87, 'status' => 'Approved',
        'registeredAt' => 'October 5, 2024', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1007', 'firstName' => 'Mark Anthony', 'lastName' => 'Ramos',
        'fullName' => 'Mark Anthony Cruz Ramos', 'email' => 'markanthony.ramos@email.com',
        'mobile' => '0916 881 9967', 'barangay' => 'Iligan', 'purok' => 'Purok 1',
        'address' => 'Purok 1, Barangay Iligan, Esperanza, Masbate', 'birthdate' => 'June 18, 1993',
        'sex' => 'Male', 'civilStatus' => 'Married', 'occupation' => 'Fisherman',
        'residentSince' => 2003, 'profileCompleteness' => 78, 'status' => 'Approved',
        'registeredAt' => 'March 17, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1008', 'firstName' => 'Rizza Mae', 'lastName' => 'Labrador',
        'fullName' => 'Rizza Mae Tan Labrador', 'email' => 'rizzamae.labrador@email.com',
        'mobile' => '0922 992 0078', 'barangay' => 'Magsaysay', 'purok' => 'Purok 4',
        'address' => 'Purok 4, Barangay Magsaysay, Esperanza, Masbate', 'birthdate' => 'December 25, 1997',
        'sex' => 'Female', 'civilStatus' => 'Single', 'occupation' => 'Sari-Sari Store Owner',
        'residentSince' => 2010, 'profileCompleteness' => 89, 'status' => 'Approved',
        'registeredAt' => 'May 9, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1009', 'firstName' => 'Michael John', 'lastName' => 'Torres',
        'fullName' => 'Michael John Valdez Torres', 'email' => 'michaeljohn.torres@email.com',
        'mobile' => '0926 003 1189', 'barangay' => 'Rizal', 'purok' => 'Purok 2',
        'address' => 'Purok 2, Barangay Rizal, Esperanza, Masbate', 'birthdate' => 'August 3, 1995',
        'sex' => 'Male', 'civilStatus' => 'Single', 'occupation' => 'Carpenter',
        'residentSince' => 2008, 'profileCompleteness' => 82, 'status' => 'Approved',
        'registeredAt' => 'November 22, 2024', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
    [
        'id' => 'ESP-RES-2025-1010', 'firstName' => 'Shaira Louise', 'lastName' => 'Paguio',
        'fullName' => 'Shaira Louise Mendoza Paguio', 'email' => 'shairalouise.paguio@email.com',
        'mobile' => '0928 114 2290', 'barangay' => 'Santiago', 'purok' => 'Purok 3',
        'address' => 'Purok 3, Barangay Santiago, Esperanza, Masbate', 'birthdate' => 'October 14, 1996',
        'sex' => 'Female', 'civilStatus' => 'Single', 'occupation' => 'Bookkeeper (Cooperative)',
        'residentSince' => 2014, 'profileCompleteness' => 90, 'status' => 'Approved',
        'registeredAt' => 'July 30, 2025', 'idUploaded' => true, 'faceVerified' => true, 'termsAccepted' => true,
    ],
];

// Special-cased identity check outcomes — everyone else defaults to a
// same-day automatic 'Match' once their status moves past Pending Review.
$identityChecks = [
    // Still in the LGU's review queue — not yet compared by a verification officer.
    'ESP-RES-2024-1102' => ['faceMatch' => 'Not Reviewed', 'remarks' => null, 'reviewedBy' => null, 'reviewedAt' => null],
];
foreach ($accounts as &$account) {
    $account['identityCheck'] = $identityChecks[$account['id']] ?? [
        'faceMatch' => in_array($account['status'], ['Pending Review', 'Under Verification'], true) ? 'Not Reviewed' : 'Match',
        'remarks' => null,
        'reviewedBy' => null,
        'reviewedAt' => $account['registeredAt'],
    ];
}
unset($account);

// The account previously rejected over an identity mismatch (Precious
// Manalo, ESP-RES-2024-1311) was removed along with the other demo accounts
// that only had a generated avatar — kept as an empty map (rather than
// deleting this block) so a future "Rejected over identity mismatch" demo
// account can be added here again without re-deriving the wiring.
$rejectRemarksById = [];
foreach ($accounts as &$account) {
    if (isset($rejectRemarksById[$account['id']])) {
        $account['rejectRemarks'] = $rejectRemarksById[$account['id']];
    }
}
unset($account);

// Verified government IDs on file — only meaningful once the wizard's own ID
// step is done, and only shown to admins once an officer has actually
// approved the registration (an unapproved ID upload isn't "verified" yet).
// Most residents have more than one valid ID on file (common in practice —
// e.g. PhilSys plus a Voter's ID), so each account carries 1-3, cycling
// through common Philippine ID types for realistic variety; masked numbers
// are derived from the account ID so they stay deterministic.
$idTypeOptions = ['PhilSys National ID', "Driver's License (LTO)", 'UMID', "Voter's ID", 'Philippine Passport', 'Postal ID', 'PRC ID'];
foreach ($accounts as $i => &$account) {
    $last4 = substr(preg_replace('/\D/', '', $account['id']), -4);
    $idCount = 1 + ($i % 3);
    $account['verifiedIds'] = collect(range(0, $idCount - 1))->map(function ($j) use ($idTypeOptions, $i, $last4) {
        $idx = ($i + $j) % count($idTypeOptions);

        return [
            'type' => $idTypeOptions[$idx],
            'masked' => '•••• •••• '.str_pad((string) (((int) $last4 + $j * 17) % 10000), 4, '0', STR_PAD_LEFT),
        ];
    })->all();
    // A mobile number was required and OTP-confirmed during the mobile
    // wizard's Contact & Security step for every account that has one on file.
    $account['mobileVerified'] = ! empty($account['mobile']);
}
unset($account);

return [
    'accounts' => $accounts,
];
