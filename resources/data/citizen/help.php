<?php

/**
 * Fixture data for resources/views/citizen/help.blade.php.
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
 *     $__fixtures = require base_path('resources/data/citizen/help.php');
 */

return [
    'faqs' => [
        ['q' => 'How do I request a document online?', 'a' => 'Go to Dokyu in your sidebar, click "New Dokyu Request," choose the document type, fill in the purpose, and submit. You\'ll be notified once it\'s ready for release.', 'category' => 'Documents'],
        ['q' => 'How long does it take to process a document request?', 'a' => 'Most documents like Barangay Clearance and Certificate of Residency are processed within 1-2 working days. Business Permits may take 3-5 working days.', 'category' => 'Documents'],
        ['q' => 'What are the accepted payment methods for document fees?', 'a' => 'You may pay in cash at the Municipal Hall cashier, or online via GCash or PayMaya when submitting your request.', 'category' => 'Payments'],
        ['q' => 'Who is qualified to apply for assistance programs?', 'a' => 'Residents of Esperanza with a verified profile may apply. Some programs, like Medical and Burial Assistance, require supporting documents such as hospital bills or a death certificate.', 'category' => 'Assistance'],
        ['q' => 'How do I apply for Tulong (assistance) online?', 'a' => 'Go to Tulong in your sidebar, click "New Tulong Request," choose the assistance program, describe your situation, attach requirements, then submit.', 'category' => 'Assistance'],
        ['q' => 'How do I update my resident profile information?', 'a' => 'Go to My Profile and click "Edit Profile." Changes to your official records may require verification by the MSWDO or your barangay office.', 'category' => 'Account'],
        ['q' => 'I forgot my password. How do I reset it?', 'a' => 'Click "Forgot password?" on the login page. This is a frontend preview, so password resets are simulated for demonstration purposes.', 'category' => 'Account'],
        ['q' => 'Can I track the status of my request in real time?', 'a' => 'Yes. Visit Dokyu or Tulong and click any request to see its current status, from submission to release.', 'category' => 'Documents'],
        ['q' => 'The site is loading slowly or showing an error — what do I do?', 'a' => 'Try refreshing the page first. If the problem continues, submit a Support Ticket below under "Technical Issue" and our ICT Office will assist you.', 'category' => 'Technical'],
    ],

    'tutorials' => [
        ['title' => 'How to Request a Barangay Clearance Online', 'duration' => '3:24', 'category' => 'Documents', 'icon' => 'file-text', 'views' => 412],
        ['title' => 'Completing Your Resident Profile Verification', 'duration' => '4:10', 'category' => 'Account', 'icon' => 'user-round-check', 'views' => 298],
        ['title' => 'Applying for Medical or Financial Assistance', 'duration' => '5:02', 'category' => 'Assistance', 'icon' => 'hand-heart', 'views' => 356],
        ['title' => 'Tracking Your Document Request Status', 'duration' => '2:18', 'category' => 'Documents', 'icon' => 'search', 'views' => 187],
        ['title' => 'Paying Document Fees via GCash', 'duration' => '3:41', 'category' => 'Payments', 'icon' => 'credit-card', 'views' => 264],
        ['title' => 'Managing Notifications & Announcements', 'duration' => '2:47', 'category' => 'Account', 'icon' => 'bell', 'views' => 143],
    ],

    'ticketCategories' => ['Account & Login', 'Dokyu', 'Tulong', 'Technical Issue', 'Other'],

    'tickets' => [
        ['ref' => 'TCK-2026-0071', 'subject' => 'Unable to upload valid ID during registration', 'status' => 'Processing', 'time' => 'Jul 5, 2026'],
        ['ref' => 'TCK-2026-0043', 'subject' => 'Wrong barangay listed on my profile', 'status' => 'Completed', 'time' => 'Jun 12, 2026'],
    ],

    'availableFiles' => [
        ['name' => 'Error Screenshot.png', 'category' => 'IMG'],
        ['name' => 'Registration Form.pdf', 'category' => 'PDF'],
    ],
];
