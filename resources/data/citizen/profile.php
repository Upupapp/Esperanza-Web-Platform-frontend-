<?php

/**
 * Fixture data for resources/views/citizen/profile.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/profile.php');
 */

return [
    'household' => [
        ['name' => 'Ramon Bacaltos', 'relation' => 'Spouse', 'age' => 41],
        ['name' => 'Josiah Bacaltos', 'relation' => 'Son', 'age' => 16],
        ['name' => 'Andrea Bacaltos', 'relation' => 'Daughter', 'age' => 12],
    ],

    'documents' => [
        ['label' => 'Philippine National ID (PhilSys)', 'status' => 'Approved', 'icon' => 'badge-check'],
        ['label' => 'Proof of Residency — Barangay Certificate', 'status' => 'Approved', 'icon' => 'file-check'],
        ['label' => "Voter's ID", 'status' => 'Pending Review', 'icon' => 'file-clock'],
    ],

    'idTypes' => ["Voter's ID", "Driver's License", 'Passport', 'PRC ID', 'Senior Citizen ID', 'PWD ID', 'Postal ID', 'Other Valid ID'],

    'availableFiles' => [
        ['name' => "Voter's ID Scan.jpg", 'category' => 'IMG'],
        ['name' => "Driver's License Scan.jpg", 'category' => 'IMG'],
        ['name' => 'Passport Bio Page.pdf', 'category' => 'PDF'],
    ],
];
