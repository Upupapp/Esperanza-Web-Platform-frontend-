<?php

/**
 * Fixture data for resources/views/citizen/events.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/events.php');
 */

return [
    'events' => [
        ['title' => 'Fiesta ng Esperanza — Opening Program', 'date' => 'Aug 14, 2026', 'time' => '6:00 PM', 'venue' => 'Municipal Plaza', 'category' => 'Festival', 'barangay' => null],
        ['title' => 'Barangay Poblacion Medical Mission', 'date' => 'Jul 18, 2026', 'time' => '8:00 AM', 'venue' => 'Poblacion Covered Court', 'category' => 'Health', 'barangay' => 'Poblacion'],
        ['title' => 'Livelihood Skills Training — Batch 2', 'date' => 'Jul 25, 2026', 'time' => '1:00 PM', 'venue' => 'Municipal Hall Conference Room', 'category' => 'Livelihood', 'barangay' => null],
        ['title' => 'Brigada Eskwela 2026', 'date' => 'Aug 3, 2026', 'time' => '7:00 AM', 'venue' => 'Esperanza Central Elementary School', 'category' => 'Education', 'barangay' => 'Poblacion'],
        ['title' => 'Quarterly Barangay Assembly — Poblacion', 'date' => 'Aug 9, 2026', 'time' => '2:00 PM', 'venue' => 'Poblacion Barangay Hall', 'category' => 'Governance', 'barangay' => 'Poblacion'],
    ],

    'categoryColor' => [
        'Festival' => 'bg-gold-500',
        'Health' => 'bg-emerald-500',
        'Livelihood' => 'bg-purple-500',
        'Education' => 'bg-blue-500',
        'Governance' => 'bg-brand-500',
    ],
];
