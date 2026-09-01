<?php

/**
 * Citizen name -> avatar URL, and citizen name -> biographical facts.
 *
 * ONE seam for four views. admin/document-requests, admin/assistance-requests,
 * admin/papeles and admin/payments each built these maps themselves, from the
 * same source, with the same code -- so a resident's picture or date of birth
 * could drift between screens by editing one copy and not the others.
 *
 * Why this is a data file under resources/data/ and NOT a config file, which is
 * the pattern the other 29 views follow: a config file cannot safely call
 * config() on another config file from its own top level, because Laravel loads
 * them in filename order during bootstrap (see the header of
 * config/esperanza_citizen_avatars.php). This file is required at RENDER time,
 * when the whole config tree and asset() are both available, so it has neither
 * problem. That constraint is the reason the original code lived in the views.
 *
 * Consistency is the point: a resident's real picture and real profile facts
 * (dob / sex / civil status / occupation) must be the same wherever their name
 * appears -- not just on their own Constituents profile, and not re-randomised
 * per document. That was the "Cristy is 25 on her profile but 53 on her
 * scholarship form" bug.
 *
 * Usage -- require, never require_once, because a second require_once returns
 * true rather than the array:
 *
 *     $citizenDirectory = require base_path('resources/data/citizen-directory.php');
 *     $citizenAvatars = $citizenDirectory['avatars'];
 *     $citizenBio = $citizenDirectory['bio'];
 *
 * @return array{avatars: array<string, string>, bio: array<string, array<string, mixed>>}
 */
$citizenAvatarData = config('esperanza_citizen_avatars');

$generatedAvatarFor = function ($acctId) use ($citizenAvatarData) {
    $bg = $citizenAvatarData['backgrounds'][crc32($acctId) % count($citizenAvatarData['backgrounds'])];

    return 'https://api.dicebear.com/9.x/personas/svg?seed='.urlencode($acctId).'&backgroundColor='.$bg;
};

$avatars = [];
$bio = [];

foreach (config('esperanza_constituents')['individuals'] as $ind) {
    if (empty($ind['citizen_account_id'])) {
        continue;
    }

    $acctId = $ind['citizen_account_id'];

    $avatars[$ind['name']] = isset($citizenAvatarData['photoByAccountId'][$acctId])
        ? asset($citizenAvatarData['photoByAccountId'][$acctId])
        : $generatedAvatarFor($acctId);

    $bio[$ind['name']] = [
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

return ['avatars' => $avatars, 'bio' => $bio];
