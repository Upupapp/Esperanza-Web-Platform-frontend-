<?php

/**
 * Fixture data for resources/views/admin/constituents.blade.php.
 *
 * TAB 04: the view declared these inline. Moved out so the view is presentation
 * only, and so the shapes have one home when they are swapped for API calls --
 * the field names here ARE the backend contract and must not be renamed.
 *
 * A render-time data file rather than a config file: config files are loaded in
 * filename order during bootstrap and cannot call config() on one another, and
 * several of these call config()/asset()/route(). See
 * config/esperanza_citizen_avatars.php's header for the original note.
 *
 * Required by the view as:
 *     $__fixtures = require base_path('resources/data/admin/constituents.php');
 */

return [
    'residentPhotoAsset' => [
        'ESP-RES-2024-1102' => asset('images/esperanza/residents/ronaldo-profile.png'),
        'ESP-RES-2024-1044' => asset('images/esperanza/residents/cristy-profile.png'),
        'ESP-RES-2026-2101' => asset('images/esperanza/residents/theodoro-profile.png'),
        // Ten real supplied photo+ID pairs (citizen-01..10) — each account
        // id below maps to its own matching pair, e.g. ESP-RES-2025-1001 →
        // citizen-01-profile.png + citizen-01-id.png, so the face on the
        // portrait and the face on the submitted ID are always the same
        // person for a given account.
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
        // A second supplied batch, matched by name to three already-existing
        // Verified residents (Lorna Reyes Dimaano, Divina Torres Galvez,
        // Analiza Bautista Ibarra) rather than new citizens — their ID cards
        // are printed with these exact names/barangays.
        'ESP-RES-2024-1056' => asset('images/esperanza/residents/lorna-profile.jpg'),
        'ESP-RES-2024-1071' => asset('images/esperanza/residents/divina-profile.jpg'),
        'ESP-RES-2024-1145' => asset('images/esperanza/residents/analiza-profile.jpg'),
    ],

    'residentIdImageAsset' => [
        'ESP-RES-2024-1102' => asset('images/esperanza/residents/ronaldo-id.png'),
        'ESP-RES-2024-1044' => asset('images/esperanza/residents/cristy-id.png'),
        'ESP-RES-2026-2101' => asset('images/esperanza/residents/theodoro-id.png'),
        'ESP-RES-2025-1001' => asset('images/esperanza/residents/citizen-01-id.png'),
        'ESP-RES-2025-1002' => asset('images/esperanza/residents/citizen-02-id.png'),
        'ESP-RES-2025-1003' => asset('images/esperanza/residents/citizen-03-id.png'),
        'ESP-RES-2025-1004' => asset('images/esperanza/residents/citizen-04-id.png'),
        'ESP-RES-2025-1005' => asset('images/esperanza/residents/citizen-05-id.png'),
        'ESP-RES-2025-1006' => asset('images/esperanza/residents/citizen-06-id.png'),
        'ESP-RES-2025-1007' => asset('images/esperanza/residents/citizen-07-id.png'),
        'ESP-RES-2025-1008' => asset('images/esperanza/residents/citizen-08-id.png'),
        'ESP-RES-2025-1009' => asset('images/esperanza/residents/citizen-09-id.png'),
        'ESP-RES-2025-1010' => asset('images/esperanza/residents/citizen-10-id.png'),
        'ESP-RES-2024-1056' => asset('images/esperanza/residents/lorna-id.jpg'),
        'ESP-RES-2024-1071' => asset('images/esperanza/residents/divina-id.jpg'),
        'ESP-RES-2024-1145' => asset('images/esperanza/residents/analiza-id.jpg'),
    ],

    'avatarBackgrounds' => ['EEF4FF', 'FDF9E7', 'F1F5F9', 'E0F2FE', 'FCE7F3', 'ECFDF5', 'FFF7ED'],

    'barangaySealAsset' => [
        'Labangtaytay' => asset('images/esperanza/barangays/labangtaytay-seal.png'),
    ],
];
