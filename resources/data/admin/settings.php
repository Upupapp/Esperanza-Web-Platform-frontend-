<?php

/**
 * Fixture data for resources/views/admin/settings.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/settings.php');
 */

return [
    'notifPrefs' => [
        ['label' => 'New document requests', 'desc' => 'Notify when a citizen submits a new request.', 'checked' => true],
        ['label' => 'New assistance requests', 'desc' => 'Notify when a citizen applies for assistance.', 'checked' => true],
        ['label' => 'Overdue requests', 'desc' => 'Alert when requests exceed SLA processing time.', 'checked' => true],
        ['label' => 'Weekly summary report', 'desc' => 'Email digest of municipal service activity.', 'checked' => false],
    ],

    'palette' => [
        ['name' => 'Brand Blue', 'hex' => '#2F62F5', 'class' => 'bg-brand-500'],
        ['name' => 'Navy', 'hex' => '#0B1730', 'class' => 'bg-navy-900'],
        ['name' => 'Gold', 'hex' => '#ECC02A', 'class' => 'bg-gold-400'],
        ['name' => 'Emerald', 'hex' => '#22C55E', 'class' => 'bg-emerald-500'],
    ],

    'logs' => [
        ['user' => 'Juan Dela Cruz', 'action' => 'Approved document request DR-2026-2154', 'module' => 'Dokyu', 'time' => 'Jul 10, 2026 · 9:42 AM', 'ip' => '203.177.42.11'],
        ['user' => 'Corazon Villareal', 'action' => 'Published announcement "Free Medical & Dental Mission"', 'module' => 'Balita', 'time' => 'Jul 9, 2026 · 4:15 PM', 'ip' => '203.177.42.19'],
        ['user' => 'Paolo Reyes', 'action' => 'Created personnel account for Angeline Mercado', 'module' => 'User Management', 'time' => 'Jul 9, 2026 · 2:03 PM', 'ip' => '203.177.42.02'],
        ['user' => 'Bienvenido Salazar', 'action' => 'Released document request DR-2026-2187', 'module' => 'Dokyu', 'time' => 'Jul 8, 2026 · 11:27 AM', 'ip' => '203.177.42.33'],
        ['user' => 'Leilani Domingo', 'action' => 'Approved assistance request AR-2026-0142', 'module' => 'Tulong', 'time' => 'Jul 8, 2026 · 10:05 AM', 'ip' => '203.177.42.08'],
        ['user' => 'System', 'action' => 'Automated backup completed successfully', 'module' => 'System', 'time' => 'Jul 8, 2026 · 2:00 AM', 'ip' => 'internal'],
        ['user' => 'Juan Dela Cruz', 'action' => 'Updated municipality branding colors', 'module' => 'Branding', 'time' => 'Jul 7, 2026 · 3:41 PM', 'ip' => '203.177.42.11'],
        ['user' => 'Marivic Ong', 'action' => 'Rejected document request DR-2026-1655', 'module' => 'Dokyu', 'time' => 'Jul 6, 2026 · 1:18 PM', 'ip' => '203.177.42.27'],
    ],

    'supportIconOptions' => ['phone', 'mail', 'map-pin', 'clock', 'life-buoy', 'message-circle', 'facebook', 'globe'],
];
