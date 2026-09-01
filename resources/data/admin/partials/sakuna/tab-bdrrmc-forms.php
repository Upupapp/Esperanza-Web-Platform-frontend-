<?php

/**
 * Fixture data for resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-bdrrmc-forms.php');
 */

return [
    'bdrrmcForms' => [
        [
            'key' => 'evacuees', 'code' => 'Dromic Form 1.2', 'name' => 'Profile of Evacuees Inside the Evacuation Center',
            'desc' => 'Per-family headcount by age bracket and sex, plus assistance extended, for evacuees inside a center.',
            'icon' => 'users', 'color' => 'bg-sky-50 text-sky-600',
        ],
        [
            'key' => 'human-housing', 'code' => 'BDRRMC Capture Form', 'name' => '"Human, Housing & Flooding" Calamity/Damages Capture and Assessment Form',
            'desc' => 'Casualties, missing, rescued, and housing/flood damage per affected household.',
            'icon' => 'house', 'color' => 'bg-rose-50 text-rose-600',
        ],
        [
            'key' => 'agriculture', 'code' => 'BDRRMC Capture Form', 'name' => '"Agricultural Crops & Livestock" Calamity Damages Captured Assessment Form',
            'desc' => 'Livestock heads and crop production loss per affected farmer.',
            'icon' => 'wheat', 'color' => 'bg-amber-50 text-amber-600',
        ],
        [
            'key' => 'fishing', 'code' => 'BDRRMC Capture Form', 'name' => '"Fishing" Calamity Damages Captured Assessment Form',
            'desc' => 'Fishing gear/equipment damage per affected owner.',
            'icon' => 'anchor', 'color' => 'bg-cyan-50 text-cyan-600',
        ],
        [
            'key' => 'business', 'code' => 'BDRRMC Capture Form', 'name' => '"Business" Calamity Damages Captured Assessment Form',
            'desc' => 'Merchandise, equipment, and structural damage per affected business owner.',
            'icon' => 'store', 'color' => 'bg-violet-50 text-violet-600',
        ],
        [
            'key' => 'rds', 'code' => 'RDS', 'name' => 'Relief Distribution Sheet',
            'desc' => 'Beneficiary-level record of relief assistance released, with signature/thumbmark acknowledgement.',
            'icon' => 'clipboard-check', 'color' => 'bg-emerald-50 text-emerald-600',
        ],
    ],
];
