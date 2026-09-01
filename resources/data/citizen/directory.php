<?php

/**
 * Fixture data for resources/views/citizen/directory.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/directory.php');
 */

return [
    'offices' => [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph', 'icon' => 'landmark', 'color' => 'from-navy-600 to-navy-900'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph', 'icon' => 'hand-heart', 'color' => 'from-purple-500 to-purple-700'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph', 'icon' => 'wallet', 'color' => 'from-gold-500 to-gold-700'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph', 'icon' => 'stethoscope', 'color' => 'from-emerald-500 to-emerald-700'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Narec N. Conag', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph', 'icon' => 'file-badge', 'color' => 'from-rose-500 to-rose-700'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph', 'icon' => 'monitor-cog', 'color' => 'from-brand-500 to-brand-700'],
        ['name' => 'Business Permits & Licensing Office', 'head' => 'Mr. Reynaldo B. Solano', 'contact' => '(056) 333-1078', 'email' => 'bplo@esperanza.gov.ph', 'icon' => 'briefcase', 'color' => 'from-blue-500 to-blue-700'],
        ['name' => 'Municipal Disaster Risk Reduction & Management Office', 'head' => 'Mr. Arnulfo C. Diaz', 'contact' => '(056) 333-1091', 'email' => 'mdrrmo@esperanza.gov.ph', 'icon' => 'shield-alert', 'color' => 'from-orange-500 to-orange-700'],
        ['name' => 'Office of Senior Citizens Affairs', 'head' => 'Mrs. Adoracion V. Ferrer', 'contact' => '(056) 333-1085', 'email' => 'osca@esperanza.gov.ph', 'icon' => 'users', 'color' => 'from-teal-500 to-teal-700'],
        ['name' => 'Municipal Agriculture Office', 'head' => 'Engr. Jever L. Malaras', 'contact' => '(056) 333-1052', 'email' => 'agriculture@esperanza.gov.ph', 'icon' => 'sprout', 'color' => 'from-lime-500 to-lime-700'],
        ['name' => 'Public Employment Service Office', 'head' => 'Ms. Cristina M. Bayoneta', 'contact' => '(056) 333-1063', 'email' => 'mpeso@esperanza.gov.ph', 'icon' => 'briefcase-business', 'color' => 'from-indigo-500 to-indigo-700'],
    ],
];
