<?php

// Real portrait assets + the generated-avatar fallback, keyed by citizen
// account id — reused by every admin listing that shows a citizen's name
// outside their own Constituents profile (Dokyu, Tulong, Payments, Papeles)
// so a resident's picture is consistent wherever their name appears.
//
// Deliberately kept as PURE static data with no call to config() for any
// other file: Laravel loads every file in config/ during its own bootstrap,
// in filename order, before any of them can rely on another one already
// being registered — "esperanza_citizen_avatars" sorts before
// "esperanza_constituents" alphabetically, so a config('esperanza_constituents')
// call made from inside this file's own top level would silently resolve to
// null (this was a real bug caught while building this exact feature: two
// almost-identical maps both went silently empty for that reason). Building
// the final name-keyed map is left to each consuming Blade view instead,
// which runs at request time — long after every config file is loaded —
// exactly the same way admin/constituents.blade.php already safely
// resolves config('esperanza_constituents') and config('esperanza_citizens')
// together in its own @php block.

$residentPhotoAsset = [
    'ESP-RES-2024-1102' => asset('images/esperanza/residents/ronaldo-profile.png'),
    'ESP-RES-2024-1044' => asset('images/esperanza/residents/cristy-profile.png'),
    'ESP-RES-2026-2101' => asset('images/esperanza/residents/theodoro-profile.png'),
    'ESP-RES-2025-1001' => asset('images/esperanza/residents/citizen-01-profile.png'),
    'ESP-RES-2025-1002' => asset('images/esperanza/residents/citizen-02-profile.png'),
    'ESP-RES-2025-1003' => asset('images/esperanza/residents/citizen-03-profile.png'),
    'ESP-RES-2025-1004' => asset('images/esperanza/residents/citizen-04-profile.png'),
    'ESP-RES-2025-1005' => asset('images/esperanza/residents/citizen-05-profile.png'),
    'ESP-RES-2025-1006' => asset('images/esperanza/residents/citizen-06-profile.png'),
    'ESP-RES-2025-1007' => asset('images/esperanza/residents/citizen-07-profile.png'),
    'ESP-RES-2025-1008' => asset('images/esperanza/residents/citizen-08-profile.png'),
    'ESP-RES-2025-1009' => asset('images/esperanza/residents/citizen-09-profile.png'),
    'ESP-RES-2025-1010' => asset('images/esperanza/residents/citizen-10-profile.png'),
    'ESP-RES-2024-1056' => asset('images/esperanza/residents/lorna-profile.jpg'),
    'ESP-RES-2024-1071' => asset('images/esperanza/residents/divina-profile.jpg'),
    'ESP-RES-2024-1145' => asset('images/esperanza/residents/analiza-profile.jpg'),
];

$avatarBackgrounds = ['EEF4FF', 'FDF9E7', 'F1F5F9', 'E0F2FE', 'FCE7F3', 'ECFDF5', 'FFF7ED'];

return [
    'photoByAccountId' => $residentPhotoAsset,
    'backgrounds' => $avatarBackgrounds,
];
