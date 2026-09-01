<?php

/**
 * Fixture data for resources/views/admin/users.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/users.php');
 */

return [
    'departments' => [
        'Office of the Municipal Mayor', 'Municipal Social Welfare & Development Office', "Municipal Treasurer's Office",
        'Office of the Municipal Civil Registrar', 'Municipal Health Office', 'ICT Office',
        'Business Permits & Licensing Office', 'Office of Senior Citizens Affairs',
        'Municipal Disaster Risk Reduction & Management Office', 'Municipal Agriculture Office', 'Public Employment Service Office',
    ],
];
