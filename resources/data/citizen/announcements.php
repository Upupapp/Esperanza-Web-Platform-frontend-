<?php

/**
 * Fixture data for resources/views/citizen/announcements.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/announcements.php');
 */

return [
    'upcomingEvents' => [
        ['month' => 'Aug', 'day' => '14', 'title' => 'Fiesta ng Esperanza — Opening Program', 'venue' => 'Municipal Plaza'],
        ['month' => 'Jul', 'day' => '18', 'title' => 'Barangay Poblacion Medical Mission', 'venue' => 'Poblacion Covered Court'],
        ['month' => 'Jul', 'day' => '25', 'title' => 'Livelihood Skills Training', 'venue' => 'Municipal Hall'],
    ],
];
