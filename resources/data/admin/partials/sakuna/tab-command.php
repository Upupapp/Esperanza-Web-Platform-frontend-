<?php

/**
 * Fixture data for resources/views/admin/partials/sakuna/tab-command.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-command.php');
 */

return [
    'mapMarkers' => [
        ['name' => 'Agoho',        'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '2,140', 'incidents' => 0, 'evacuees' => 0,   'center' => 'Agoho Covered Court (Standby)', 'pulse' => false],
        ['name' => 'Almero',       'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '940',   'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Baras',        'status' => 'High Alert', 'statusLabel' => 'Flash Flood',          'dotColor' => 'bg-orange-500',  'ringColor' => 'ring-orange-300',  'pop' => '2,890', 'incidents' => 2, 'evacuees' => 87,  'center' => 'Baras Barangay Hall (Open)', 'pulse' => true],
        ['name' => 'Domorog',      'status' => 'High Alert', 'statusLabel' => 'Landslide',            'dotColor' => 'bg-orange-500',  'ringColor' => 'ring-orange-300',  'pop' => '1,980', 'incidents' => 1, 'evacuees' => 76,  'center' => 'Domorog Multi-Purpose Hall (Open)', 'pulse' => true],
        ['name' => 'Guadalupe',    'status' => 'Monitoring', 'statusLabel' => 'Agri Damage',          'dotColor' => 'bg-amber-400',   'ringColor' => 'ring-amber-200',   'pop' => '1,180', 'incidents' => 1, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Iligan',       'status' => 'Monitoring', 'statusLabel' => 'Power Interruption',   'dotColor' => 'bg-slate-300',   'ringColor' => 'ring-slate-200',   'pop' => '1,640', 'incidents' => 1, 'evacuees' => 54,  'center' => 'Iligan Elementary School (Open)', 'pulse' => false],
        ['name' => 'Labangtaytay', 'status' => 'High Alert', 'statusLabel' => 'Stranded Residents',  'dotColor' => 'bg-amber-500',   'ringColor' => 'ring-amber-300',   'pop' => '1,210', 'incidents' => 1, 'evacuees' => 0,   'center' => '—', 'pulse' => true],
        ['name' => 'Labrador',     'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '1,480', 'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Libertad',     'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '1,320', 'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Magsaysay',    'status' => 'Monitoring', 'statusLabel' => 'Flooding (subsiding)', 'dotColor' => 'bg-amber-500',   'ringColor' => 'ring-amber-300',   'pop' => '1,750', 'incidents' => 1, 'evacuees' => 103, 'center' => 'Magsaysay Brgy. Hall (Near Capacity)', 'pulse' => false],
        ['name' => 'Masbaranon',   'status' => 'Critical',   'statusLabel' => 'Severe Flooding',      'dotColor' => 'bg-rose-500',    'ringColor' => 'ring-rose-300',    'pop' => '3,210', 'incidents' => 3, 'evacuees' => 189, 'center' => 'Masbaranon Elem. School (Near Capacity)', 'pulse' => true],
        ['name' => 'Poblacion',    'status' => 'Monitoring', 'statusLabel' => 'Medical Emergency',    'dotColor' => 'bg-amber-400',   'ringColor' => 'ring-amber-200',   'pop' => '4,821', 'incidents' => 2, 'evacuees' => 519, 'center' => 'Poblacion Covered Court + Mun. Gymnasium', 'pulse' => false],
        ['name' => 'Potingbato',   'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '980',   'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Rizal',        'status' => 'Normal',     'statusLabel' => 'Flood Resolved',       'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '1,120', 'incidents' => 1, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'San Roque',    'status' => 'High Alert', 'statusLabel' => 'Missing Person',       'dotColor' => 'bg-orange-400',  'ringColor' => 'ring-orange-200',  'pop' => '1,390', 'incidents' => 1, 'evacuees' => 0,   'center' => 'San Roque Parish Hall (Standby)', 'pulse' => true],
        ['name' => 'Santiago',     'status' => 'Monitoring', 'statusLabel' => 'Road Damage',          'dotColor' => 'bg-amber-400',   'ringColor' => 'ring-amber-200',   'pop' => '1,650', 'incidents' => 1, 'evacuees' => 0,   'center' => 'Santiago Brgy. Hall (Closed — No Inspection)', 'pulse' => false],
        ['name' => 'Sorosimbajan', 'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '890',   'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Tawad',        'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '760',   'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Tunga',        'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '1,040', 'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
        ['name' => 'Villa1',       'status' => 'Normal',     'statusLabel' => 'No Active Incidents',  'dotColor' => 'bg-emerald-400', 'ringColor' => 'ring-emerald-200', 'pop' => '830',   'incidents' => 0, 'evacuees' => 0,   'center' => '—', 'pulse' => false],
    ],
];
