<?php

/**
 * Fixture data for resources/views/citizen/settings.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/settings.php');
 */

return [
    'notifPrefs' => [
        ['label' => 'Document request updates', 'labelFil' => 'Update sa mga kahilingang dokumento', 'desc' => 'Get notified when a request status changes.', 'descFil' => 'Maaabisuhan ka kapag nagbago ang status ng iyong kahilingan.', 'checked' => true],
        ['label' => 'Assistance request updates', 'labelFil' => 'Update sa mga kahilingang tulong', 'desc' => 'Get notified about assistance program progress.', 'descFil' => 'Maaabisuhan ka tungkol sa progreso ng iyong tulong.', 'checked' => true],
        ['label' => 'Municipal announcements', 'labelFil' => 'Mga balita ng munisipyo', 'desc' => 'News and advisories from the municipality.', 'descFil' => 'Balita at abiso mula sa munisipyo.', 'checked' => true],
        ['label' => 'Event reminders', 'labelFil' => 'Paalala sa mga kaganapan', 'desc' => 'Reminders before events you\'re following.', 'descFil' => 'Paalala bago magsimula ang mga sinusundan mong kaganapan.', 'checked' => false],
        ['label' => 'SMS notifications', 'labelFil' => 'Abiso sa SMS', 'desc' => 'Receive critical updates via SMS as backup.', 'descFil' => 'Tumanggap ng mahahalagang update sa SMS bilang backup.', 'checked' => false],
    ],
];
