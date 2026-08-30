<?php

/**
 * Fixture data for resources/views/citizen/notifications.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/notifications.php');
 */

return [
    'notifications' => [
        ['icon' => 'cake', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'Happy Birthday, Maria Fe! 🎉', 'body' => 'The Municipality of Esperanza wishes you good health, happiness, and blessings on your special day.', 'time' => 'Today', 'unread' => true, 'pinned' => true, 'popup' => 'show-birthday-popup', 'category' => 'account'],
        // Real Cristy Bonghanoy Dokyu/Tulong submissions, sourced from her
        // actual record (config/esperanza_constituents.php) — static/
        // guaranteed entries, not dependent on visiting another page first.
        // See also the "Recent Activity" feed on her admin Constituents
        // Overview tab, which shows the same underlying data.
        ['icon' => 'file-plus', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cristy submitted Barangay Business Clearance', 'body' => 'Your request for a Barangay Business Clearance (market stall permit renewal) has been submitted and is now in the queue. Ref: DR-2026-2233', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2233'],
        ['icon' => 'file-plus', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cristy submitted Barangay Clearance', 'body' => 'Your Barangay Clearance request has been submitted and is now in the queue. Ref: DR-2026-2255', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2255'],
        ['icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Cristy submitted Educational Assistance', 'body' => 'Your Educational Assistance (scholarship program) application has been submitted and is now in the queue. Ref: AR-2026-0224', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0224'],
        ['icon' => 'circle-check', 'color' => 'text-green-600 bg-green-50', 'title' => 'Financial Assistance completed', 'body' => 'Cristy\'s livelihood capital support request has been marked Completed. Ref: AR-2026-0163', 'time' => 'May 18, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0163'],
        ['icon' => 'package-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Barangay Clearance is ready for release', 'body' => 'Claim your document at the Poblacion barangay hall. Bring a valid ID. Ref: DR-2026-2187', 'time' => '18 minutes ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2187'],
        ['icon' => 'loader', 'color' => 'text-orange-600 bg-orange-50', 'title' => 'Cedula request under verification', 'body' => 'Your request #DR-2026-2210 is being reviewed by the Treasurer\'s Office.', 'time' => '2 hours ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2210'],
        ['icon' => 'package-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Educational Assistance is ready for release', 'body' => 'Claim your Educational Assistance at the Mayor\'s Office. Bring a valid ID. Ref: AR-2026-0098', 'time' => '5 hours ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0098'],
        ['icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'New announcement published', 'body' => '"Fiesta ng Esperanza 2026" schedule of activities is now live.', 'time' => 'Yesterday, 4:12 PM', 'unread' => true, 'pinned' => false, 'popup' => 'show-fiesta-popup', 'category' => 'community'],
        ['icon' => 'badge-check', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Medical Assistance request approved', 'body' => 'Your Tulong request #AR-2026-0142 was approved. We\'ll notify you when it\'s ready for release.', 'time' => '2 days ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0142'],
        ['icon' => 'loader', 'color' => 'text-orange-600 bg-orange-50', 'title' => 'Financial Assistance is being processed', 'body' => 'MSWDO is preparing your livelihood capital support. Ref #AR-2026-0061 — you\'ll be notified once it\'s released.', 'time' => '2 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0061'],
        ['icon' => 'file-x', 'color' => 'text-rose-600 bg-rose-50', 'title' => 'Business Permit request needs attention', 'body' => 'Ref #DR-2026-1655 was rejected — missing Fire Safety Inspection Certificate. Please resubmit.', 'time' => '3 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-1655'],
        ['icon' => 'calendar-days', 'color' => 'text-indigo-600 bg-indigo-50', 'title' => 'Upcoming event reminder', 'body' => 'Barangay Poblacion Medical Mission is happening in 5 days.', 'time' => '3 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'events'],
        ['icon' => 'user-round-check', 'color' => 'text-cyan-600 bg-cyan-50', 'title' => 'Profile verification completed', 'body' => 'Your resident profile has been verified by the MSWDO.', 'time' => 'Jun 30, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'account'],
        ['icon' => 'file-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Certificate of Residency released', 'body' => 'Request #DR-2026-1998 has been marked as completed.', 'time' => 'Jun 14, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-1998'],
        ['icon' => 'circle-check', 'color' => 'text-green-600 bg-green-50', 'title' => 'Social Pension enrollment completed', 'body' => 'Your quarterly social pension request #AR-2026-0037 is complete and on schedule.', 'time' => 'Feb 12, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0037'],
    ],

    'categoryMeta' => [
        'dokyu' => ['fil' => 'Dokyu', 'en' => 'Dokyu', 'pill' => 'bg-brand-50 text-brand-700'],
        'tulong' => ['fil' => 'Tulong', 'en' => 'Tulong', 'pill' => 'bg-purple-50 text-purple-700'],
        'account' => ['fil' => 'Account', 'en' => 'Account', 'pill' => 'bg-gold-50 text-gold-700'],
        'community' => ['fil' => 'Balita', 'en' => 'Community', 'pill' => 'bg-slate-100 text-slate-600'],
        'events' => ['fil' => 'Kaganapan', 'en' => 'Events', 'pill' => 'bg-indigo-50 text-indigo-700'],
    ],
];
