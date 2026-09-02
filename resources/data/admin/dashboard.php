<?php

/**
 * Fixture data for resources/views/admin/dashboard.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/dashboard.php');
 */

return [
    'barangayDashboardStats' => [],

    'docStatus' => [
        ['label' => 'Completed', 'value' => 612, 'color' => '#22c55e'],
        ['label' => 'Processing', 'value' => 348, 'color' => '#2f62f5'],
        ['label' => 'Submitted', 'value' => 254, 'color' => '#f59e0b'],
        ['label' => 'Rejected', 'value' => 70, 'color' => '#f43f5e'],
    ],

    // Tulong's own case-status vocabulary, not Dokyu's -- see
    // PROCESS_TAB_FLOW.txt PART 2. Values from the Tulong redesign (16fc534).
    'assistStatus' => [
        ['label' => 'Completed', 'value' => 168, 'color' => '#22c55e'],
        ['label' => 'Assistance Processing', 'value' => 96, 'color' => '#a855f7'],
        ['label' => 'Submitted', 'value' => 104, 'color' => '#3b82f6'],
        ['label' => 'Needs Requirements', 'value' => 49, 'color' => '#f59e0b'],
    ],

    'barangayRequests' => [
        ['name' => 'Poblacion', 'count' => 182, 'pct' => 78],
        ['name' => 'Masbaranon', 'count' => 146, 'pct' => 64],
        ['name' => 'Baras', 'count' => 121, 'pct' => 58],
        ['name' => 'Domorog', 'count' => 108, 'pct' => 52],
        ['name' => 'Agoho', 'count' => 97, 'pct' => 46],
        ['name' => 'Iligan', 'count' => 89, 'pct' => 41],
    ],

    'trendLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],

    'trendDocuments' => [140, 168, 190, 205, 230, 255, 268],

    'trendAssistance' => [40, 52, 58, 66, 71, 80, 86],

    'alerts' => [
        ['icon' => 'triangle-alert', 'color' => 'text-rose-600 bg-rose-50', 'title' => '23 document requests pending over 5 days', 'body' => 'Immediate attention required', 'time' => 'Jul 10, 2026', 'link' => 'admin.document-requests'],
        ['icon' => 'circle-alert', 'color' => 'text-amber-600 bg-amber-50', 'title' => 'Resident profiling in 4 barangays below 60%', 'body' => 'Field verification needed', 'time' => 'Jul 10, 2026', 'link' => 'admin.resident-profiling'],
        ['icon' => 'file-clock', 'color' => 'text-brand-600 bg-brand-50', 'title' => '9 assistance requests awaiting budget clearance', 'body' => 'Action needed to avoid delays', 'time' => 'Jul 9, 2026', 'link' => 'admin.assistance-requests'],
        ['icon' => 'circle-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Q2 citizen satisfaction survey results are in', 'body' => 'Ready to share with department heads', 'time' => 'Jul 8, 2026', 'link' => 'admin.reports'],
    ],

    'activities' => [
        ['icon' => 'circle-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Barangay Clearance released', 'body' => 'Request #DR-2026-1042 — Brgy. Poblacion', 'time' => '12 minutes ago', 'link' => 'admin.document-requests'],
        ['icon' => 'file-text', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'New document request submitted', 'body' => 'Business Permit — Rosemarie Tan, Brgy. Baras', 'time' => '38 minutes ago', 'link' => 'admin.document-requests'],
        ['icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Assistance request approved', 'body' => 'Medical Assistance — Rodrigo Palma, Brgy. Domorog', 'time' => '1 hour ago', 'link' => 'admin.assistance-requests'],
        ['icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'Announcement published', 'body' => '"Fiesta ng Esperanza 2026" schedule posted', 'time' => '3 hours ago', 'link' => 'admin.communications'],
        ['icon' => 'user-round-search', 'color' => 'text-indigo-600 bg-indigo-50', 'title' => 'Resident profile updated', 'body' => '12 new records added — Brgy. Masbaranon', 'time' => '5 hours ago', 'link' => 'admin.constituents'],
    ],
];
