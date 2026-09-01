<?php

/**
 * Fixture data for resources/views/admin/partials/sakuna/tab-reports.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-reports.php');
 */

return [
    'reportCategories' => [
        [
            'title' => 'Vulnerability & Preparedness',
            'icon' => 'map-pin',
            'icon_color' => 'bg-rose-50 text-rose-600',
            'reports' => [
                ['name' => 'Barangay Vulnerability and Readiness Report',  'desc' => 'Comprehensive vulnerability index across all barangays.'],
                ['name' => 'Hazard-Exposed Population Report',             'desc' => 'Population by hazard type and exposure level.'],
                ['name' => 'Assisted Evacuation Priority List',            'desc' => 'Households requiring transport for evacuation.'],
            ],
        ],
        [
            'title' => 'Evacuation',
            'icon' => 'house',
            'icon_color' => 'bg-sky-50 text-sky-600',
            'reports' => [
                ['name' => 'Evacuation Center Capacity Report',  'desc' => 'Capacity, utilization, and readiness status per center.'],
                ['name' => 'Evacuation Center Occupancy Report', 'desc' => 'Current and historical evacuee counts by center.'],
                ['name' => 'Evacuee Demographic Summary',        'desc' => 'Age, sex, and vulnerability breakdown of current evacuees.'],
            ],
        ],
        [
            'title' => 'Incident & Response',
            'icon' => 'flame',
            'icon_color' => 'bg-orange-50 text-orange-600',
            'reports' => [
                ['name' => 'Active Incident Report',         'desc' => 'All unresolved incidents with status, location, and assigned teams.'],
                ['name' => 'Incident Response-Time Report',  'desc' => 'Validation, dispatch, and arrival times per incident.'],
                ['name' => 'Rescue Deployment Report',       'desc' => 'Rescue unit assignments and deployment logs.'],
                ['name' => 'Resource Availability Report',   'desc' => 'Current inventory and deployment status of emergency resources.'],
            ],
        ],
        [
            'title' => 'Relief & Logistics',
            'icon' => 'package',
            'icon_color' => 'bg-teal-50 text-teal-600',
            'reports' => [
                ['name' => 'Relief Inventory Report',       'desc' => 'Current stock levels of relief goods and supplies.'],
                ['name' => 'Relief Distribution Report',    'desc' => 'Distribution records by barangay and beneficiary.'],
                ['name' => 'Duplicate Assistance Review',   'desc' => 'Cross-check report to detect duplicate beneficiary claims.'],
            ],
        ],
        [
            'title' => 'Damage & Recovery',
            'icon' => 'clipboard-list',
            'icon_color' => 'bg-amber-50 text-amber-600',
            'reports' => [
                ['name' => 'Affected Families and Individuals Report', 'desc' => 'Count and profile of disaster-affected households.'],
                ['name' => 'Damage and Needs Assessment Report',       'desc' => 'Assessed damage per sector and priority needs.'],
                ['name' => 'Agricultural Damage Report',               'desc' => 'Crop, livestock, and fishery damage by barangay.'],
                ['name' => 'Casualty and Missing Persons Report',      'desc' => 'Fatalities, injuries, and missing persons log.'],
            ],
        ],
        [
            'title' => 'Operational',
            'icon' => 'radio-tower',
            'icon_color' => 'bg-violet-50 text-violet-600',
            'reports' => [
                ['name' => 'Daily Situation Report (SitRep)', 'desc' => 'Official 24-hour situation summary for PDRRMC submission.'],
                ['name' => 'Operational Period Report',       'desc' => 'Full summary for the active operational period.'],
                ['name' => 'After-Action Report (AAR)',       'desc' => 'Post-operation lessons learned and recommendations.'],
            ],
        ],
    ],
];
