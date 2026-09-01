<?php

/**
 * Fixture data for resources/views/admin/reports.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/reports.php');
 */

return [
    'reportTypes' => [
        ['title' => 'Document Requests Summary', 'desc' => 'Volume, processing time, and status breakdown.', 'icon' => 'file-text', 'color' => 'text-brand-600 bg-brand-50'],
        ['title' => 'Assistance Programs Report', 'desc' => 'Budget utilization and beneficiaries per program.', 'icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50'],
        ['title' => 'Resident Profiling Coverage', 'desc' => 'Completion rate per barangay.', 'icon' => 'user-round-search', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['title' => 'Revenue & Fees Collected', 'desc' => 'Document processing fees by month.', 'icon' => 'wallet', 'color' => 'text-gold-700 bg-gold-50'],
        ['title' => 'Barangay Activity Report', 'desc' => 'Requests and engagement by barangay.', 'icon' => 'map-pin', 'color' => 'text-indigo-600 bg-indigo-50'],
        ['title' => 'Citizen Satisfaction Survey', 'desc' => 'Quarterly service feedback summary.', 'icon' => 'smile', 'color' => 'text-rose-600 bg-rose-50'],
        ['title' => 'Internal Forms Submissions', 'desc' => 'Staff form activity across HR, GSO, Agriculture, and MSWD.', 'icon' => 'clipboard-list', 'color' => 'text-indigo-600 bg-indigo-50'],
        ['title' => 'Sakuna Incident Reports', 'desc' => 'Disaster incident volume, severity, and response status.', 'icon' => 'triangle-alert', 'color' => 'text-orange-600 bg-orange-50'],
    ],

    'recent' => [
        ['name' => 'Document Requests Summary — Q2 2026', 'generated' => 'Jul 8, 2026', 'by' => 'Juan Dela Cruz', 'format' => 'PDF'],
        ['name' => 'Barangay Activity Report — June 2026', 'generated' => 'Jul 2, 2026', 'by' => 'Corazon Villareal', 'format' => 'XLSX'],
        ['name' => 'Assistance Programs Report — Q2 2026', 'generated' => 'Jun 30, 2026', 'by' => 'Juan Dela Cruz', 'format' => 'PDF'],
        ['name' => 'Resident Profiling Coverage — May 2026', 'generated' => 'Jun 3, 2026', 'by' => 'Paolo Reyes', 'format' => 'XLSX'],
        ['name' => 'Internal Forms Submissions — July 2026', 'generated' => 'Jul 11, 2026', 'by' => 'Angeline Mercado', 'format' => 'XLSX'],
        ['name' => 'Sakuna Incident Reports — SWM-2026-07', 'generated' => 'Jul 15, 2026', 'by' => 'Sgt. Ramiro Dela Vega', 'format' => 'PDF'],
    ],

    'officeColors' => ['MHRMO' => '#2f62f5', 'MGSO' => '#f59e0b', 'Agriculture' => '#22c55e', 'MSWD' => '#a855f7'],

    'incidentSeveritySplit' => [
        ['label' => 'Critical', 'value' => 2, 'color' => '#e11d48'],
        ['label' => 'High',     'value' => 5, 'color' => '#f97316'],
        ['label' => 'Medium',   'value' => 4, 'color' => '#f59e0b'],
        ['label' => 'Low',      'value' => 1, 'color' => '#64748b'],
    ],

    'trendLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],

    'trendDocuments' => [140, 168, 190, 205, 230, 255, 268],

    'trendAssistance' => [40, 52, 58, 66, 71, 80, 86],

    'channelSplit' => [
        ['label' => 'Online Portal', 'value' => 812, 'color' => '#2f62f5'],
        ['label' => 'Walk-in', 'value' => 348, 'color' => '#f59e0b'],
        ['label' => 'Barangay-assisted', 'value' => 124, 'color' => '#22c55e'],
    ],

    'topBarangays' => [
        ['name' => 'Poblacion', 'pct' => 92],
        ['name' => 'Masbaranon', 'pct' => 78],
        ['name' => 'Baras', 'pct' => 71],
        ['name' => 'Domorog', 'pct' => 64],
        ['name' => 'Agoho', 'pct' => 58],
    ],
];
