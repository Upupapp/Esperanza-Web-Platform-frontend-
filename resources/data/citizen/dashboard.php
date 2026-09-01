<?php

/**
 * Fixture data for resources/views/citizen/dashboard.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/dashboard.php');
 */

return [
    'requests' => [
        ['ref' => 'DR-2026-2210', 'type' => 'Cedula (Community Tax Certificate)', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2187', 'type' => 'Barangay Clearance', 'submitted' => 'Jul 3, 2026', 'status' => 'Mark to Release'],
        ['ref' => 'AR-2026-0142', 'type' => 'Medical Assistance', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-1998', 'type' => 'Certificate of Residency', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
    ],

    'events' => [
        ['title' => 'Fiesta ng Esperanza — Opening Program', 'date' => 'Aug 14, 2026', 'time' => '6:00 PM', 'venue' => 'Municipal Plaza'],
        ['title' => 'Barangay Poblacion Medical Mission', 'date' => 'Jul 18, 2026', 'time' => '8:00 AM', 'venue' => 'Poblacion Covered Court'],
        ['title' => 'Livelihood Skills Training', 'date' => 'Jul 25, 2026', 'time' => '1:00 PM', 'venue' => 'Municipal Hall Conference Room'],
    ],
];
