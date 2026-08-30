<?php

/**
 * Fixture data for resources/views/components/citizen/topbar.blade.php.
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
 *     $__fixtures = require base_path('resources/data/components/citizen/topbar.php');
 */

return [
    'filTitles' => [
        'dashboard' => 'Home',
        'document-requests' => 'Dokyu',
        'assistance-requests' => 'Tulong',
        'announcements' => 'Balita',
        'events' => 'Mga Kaganapan',
        'directory' => 'Direktoryo ng Pamahalaan',
        'help' => 'Suporta',
        'profile' => 'Aking Profile',
        'notifications' => 'Mga Abiso',
        'settings' => 'Mga Setting',
    ],

    'filSubtitles' => [
        'dashboard' => 'Narito ang buod ng iyong account.',
        'document-requests' => 'Humiling at subaybayan ang mga dokumento ng munisipyo online.',
        'assistance-requests' => 'Magsumite at subaybayan ang mga kahilingan para sa tulong.',
        'announcements' => 'Pinakabagong balita at abiso mula sa munisipyo.',
        'events' => 'Mga paparating na kaganapan sa Esperanza.',
        'directory' => 'Hanapin ang mga opisina at contact ng pamahalaan.',
        'help' => 'Mga tanong, gabay, at suporta.',
        'profile' => 'Pamahalaan ang iyong impormasyon bilang residente.',
        'notifications' => 'Lahat ng update sa iyong account, nasa isang lugar.',
        'settings' => 'Pamahalaan ang iyong mga kagustuhan sa account.',
    ],

    'myIds' => [
        ['type' => 'PhilSys National ID', 'masked' => '•••• •••• 4821', 'status' => 'Verified'],
        ['type' => "Driver's License (LTO)", 'masked' => '•••• •••• 1203', 'status' => 'Verified'],
        ['type' => "Voter's ID", 'masked' => '•••• •••• 9087', 'status' => 'Unverified'],
    ],

    'notifications' => [
        ['icon' => 'cake', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'Happy Birthday, Maria Fe! 🎉', 'body' => 'The Municipality of Esperanza wishes you a wonderful birthday!', 'time' => 'Today', 'pinned' => true, 'popup' => 'show-birthday-popup'],
        ['icon' => 'circle-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Barangay Clearance is ready for release', 'body' => 'Claim it at the Poblacion barangay hall.', 'time' => '18 min ago', 'pinned' => false, 'popup' => null],
        ['icon' => 'file-text', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cedula request under verification', 'body' => 'Your request #DR-2026-2210 is being reviewed.', 'time' => '2 hours ago', 'pinned' => false, 'popup' => null],
        ['icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'New announcement published', 'body' => '"Fiesta ng Esperanza 2026" schedule is now live.', 'time' => 'Yesterday', 'pinned' => false, 'popup' => 'show-fiesta-popup'],
        ['icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Assistance request approved', 'body' => 'Your medical assistance request was approved.', 'time' => '2 days ago', 'pinned' => false, 'popup' => null],
    ],
];
