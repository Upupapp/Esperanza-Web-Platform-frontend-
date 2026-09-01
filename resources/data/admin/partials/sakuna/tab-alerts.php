<?php

/**
 * Fixture data for resources/views/admin/partials/sakuna/tab-alerts.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-alerts.php');
 */

return [
    'levelBorder' => [
        'Information' => 'border-sky-400',
        'Advisory' => 'border-teal-400',
        'Watch' => 'border-amber-400',
        'Warning' => 'border-orange-400',
        'Critical' => 'border-rose-500',
    ],

    'alertTypes' => [
        'Flood Warning',
        'Storm Surge Advisory',
        'Landslide Watch',
        'Typhoon Advisory',
        'Earthquake Warning',
        'Volcanic Activity Advisory',
        'Tsunami Warning',
        'Mandatory Evacuation Order',
        'Pre-Emptive Evacuation Advisory',
        'Fire Emergency Alert',
        'Public Health Advisory',
        'Road and Infrastructure Closure',
        'Missing Person Alert',
        'Agricultural Damage Advisory',
        'Power Interruption Notice',
        'Water Service Interruption Notice',
        'Search and Rescue Operation Notice',
        'All-Clear / Situation Normal',
    ],

    'alertStatuses' => ['Draft', 'For Review', 'Approved', 'Scheduled', 'Published', 'Expired', 'Cancelled', 'Archived'],

    'targetGroups' => [
        'All Residents',
        'Evacuation Center Occupants',
        'Vulnerable Households',
        'Farmers',
        'Senior Citizens',
        'PWD Households',
        'MDRRMO Personnel',
    ],
];
