<?php

/**
 * Fixture data for resources/views/admin/sakuna.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/sakuna.php');
 */

return [
    'tabRoutes' => [
        'command-center' => 'admin.sakuna',
        'vulnerability' => 'admin.sakuna.vulnerability',
        'incidents' => 'admin.sakuna.incidents',
        'missing-persons' => 'admin.sakuna.missing-persons',
        'evacuation-centers' => 'admin.sakuna.evacuation-centers',
        'evacuees' => 'admin.sakuna.evacuees',
        'resources' => 'admin.sakuna.resources',
        'relief' => 'admin.sakuna.relief',
        'damage-assessment' => 'admin.sakuna.damage-assessment',
        'bdrrmc-forms' => 'admin.sakuna.bdrrmc-forms',
        'alerts' => 'admin.sakuna.alerts',
        'reports' => 'admin.sakuna.reports',
    ],
];
