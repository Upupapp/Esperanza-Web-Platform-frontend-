<?php

/**
 * Fixture data for resources/views/admin/communications.blade.php.
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
 *     $__fixtures = require base_path('resources/data/admin/communications.php');
 */

return [
    'announcements' => [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'category' => 'Community', 'author' => 'Corazon Villareal', 'date' => 'Jul 9, 2026', 'status' => 'Draft', 'image' => 'esperanza_festival.jpg', 'scope' => 'Municipal'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'category' => 'Health', 'author' => 'Dr. Leilani Domingo', 'date' => 'Jul 7, 2026', 'status' => 'Pending Review', 'image' => 'esperanza_people.jpg', 'scope' => 'Poblacion'],
        ['title' => 'Online Document Requests now accept GCash payment', 'category' => 'Public Service', 'author' => 'Paolo Reyes', 'date' => 'Jul 5, 2026', 'status' => 'Approved', 'image' => 'rectangle_cityhall.jpg', 'scope' => 'Municipal'],
        ['title' => 'Typhoon Preparedness Advisory', 'category' => 'Advisory', 'author' => 'MDRRMO', 'date' => 'Jul 3, 2026', 'status' => 'Completed', 'image' => 'esperanza-aerial.jpg', 'scope' => 'Municipal'],
        ['title' => 'Free Skills Training for Out-of-School Youth', 'category' => 'Livelihood', 'author' => 'Corazon Villareal', 'date' => 'Jun 28, 2026', 'status' => 'Completed', 'image' => 'esperanza_community2.jpg', 'scope' => 'Municipal'],
        ['title' => '2025 Barangay Election Results Advisory', 'category' => 'Governance', 'author' => 'Atty. Marivic Ong', 'date' => 'Jan 15, 2026', 'status' => 'Archived', 'image' => 'rectangle_lgu.png', 'scope' => 'Municipal'],
        ['title' => 'Brgy. Labangtaytay Clean-Up Drive This Weekend', 'category' => 'Community', 'author' => 'Jenneth A. Cachila', 'date' => 'Jul 11, 2026', 'status' => 'Pending Review', 'image' => 'esperanza_community3.jpg', 'scope' => 'Labangtaytay'],
    ],

    'events' => [
        ['title' => 'Fiesta ng Esperanza — Opening Program', 'date' => 'Aug 14, 2026', 'time' => '6:00 PM', 'venue' => 'Municipal Plaza', 'category' => 'Festival', 'barangay' => null, 'author' => 'Corazon Villareal', 'status' => 'Approved'],
        ['title' => 'Barangay Poblacion Medical Mission', 'date' => 'Jul 18, 2026', 'time' => '8:00 AM', 'venue' => 'Poblacion Covered Court', 'category' => 'Health', 'barangay' => 'Poblacion', 'author' => 'Dr. Leilani Domingo', 'status' => 'Approved'],
        ['title' => 'Livelihood Skills Training — Batch 2', 'date' => 'Jul 25, 2026', 'time' => '1:00 PM', 'venue' => 'Municipal Hall Conference Room', 'category' => 'Livelihood', 'barangay' => null, 'author' => 'Paolo Reyes', 'status' => 'Pending Review'],
        ['title' => 'Brigada Eskwela 2026', 'date' => 'Aug 3, 2026', 'time' => '7:00 AM', 'venue' => 'Esperanza Central Elementary School', 'category' => 'Education', 'barangay' => 'Poblacion', 'author' => 'Corazon Villareal', 'status' => 'Approved'],
        ['title' => 'Quarterly Barangay Assembly — Poblacion', 'date' => 'Aug 9, 2026', 'time' => '2:00 PM', 'venue' => 'Poblacion Barangay Hall', 'category' => 'Governance', 'barangay' => 'Poblacion', 'author' => 'Jenneth A. Cachila', 'status' => 'Draft'],
        ['title' => 'Free Rabies Vaccination Drive — Brgy. Baras', 'date' => 'Aug 21, 2026', 'time' => '9:00 AM', 'venue' => 'Baras Barangay Hall', 'category' => 'Health', 'barangay' => 'Baras', 'author' => 'Dr. Leilani Domingo', 'status' => 'Pending Review'],
        ['title' => 'Founding Anniversary Fun Run', 'date' => 'Jun 5, 2026', 'time' => '5:00 AM', 'venue' => 'Municipal Plaza to Esperanza Port', 'category' => 'Festival', 'barangay' => null, 'author' => 'Paolo Reyes', 'status' => 'Completed'],
    ],

    'eventCategoryColor' => [
        'Festival' => 'bg-gold-500',
        'Health' => 'bg-emerald-500',
        'Livelihood' => 'bg-purple-500',
        'Education' => 'bg-blue-500',
        'Governance' => 'bg-brand-500',
    ],

    'offices' => [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph', 'icon' => 'landmark', 'color' => 'from-navy-600 to-navy-900'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph', 'icon' => 'hand-heart', 'color' => 'from-purple-500 to-purple-700'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph', 'icon' => 'wallet', 'color' => 'from-gold-500 to-gold-700'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph', 'icon' => 'stethoscope', 'color' => 'from-emerald-500 to-emerald-700'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Atty. Marivic S. Ong', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph', 'icon' => 'file-text', 'color' => 'from-rose-500 to-rose-700'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph', 'icon' => 'cpu', 'color' => 'from-brand-500 to-brand-700'],
    ],

    'directoryFirstNames' => ['Roberto', 'Elena', 'Manuel', 'Fe', 'Domingo', 'Rosario', 'Alfredo', 'Teresita'],

    'directorySurnames' => ['Cruz', 'Santos', 'Reyes', 'Bautista', 'Delos Reyes', 'Villanueva'],

    'communityPosts' => [
        ['author' => 'Elmer Bantillo', 'barangay' => 'Santiago', 'body' => 'Successful po ang blood donation drive natin ngayong araw dito sa Brgy. Santiago! Umabot ng 40+ na donors. Maraming salamat sa lahat ng dumalo at sa Red Cross Masbate!', 'image' => 'esperanza_community1.jpg', 'time' => '3 hrs ago', 'likes' => 47, 'comments' => 1, 'shares' => 6, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Josefina Marbella', 'barangay' => 'Baras', 'body' => 'Gusto ko lang po magpasalamat sa MSWDO at OSCA sa mabilis na pag-asikaso ng aking social pension application. Simple lang pero malaking tulong na po sa amin. Maraming salamat po talaga!', 'image' => null, 'time' => '1 day ago', 'likes' => 83, 'comments' => 1, 'shares' => 4, 'reports' => 0, 'featured' => true, 'status' => 'Approved'],
        ['author' => 'Michael Bacus', 'barangay' => 'Iligan', 'body' => 'May nakakita po ba nito? Nawala malapit sa covered court kagabi. Itim na may puting paa, mahiyain pero friendly naman. Tulungan niyo po ako i-share.', 'image' => 'esperanza_event3.jpg', 'time' => '1 day ago', 'likes' => 29, 'comments' => 0, 'shares' => 22, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Angelica Fajardo', 'barangay' => 'Rizal', 'body' => 'Coverage ng aming fiesta rehearsal kagabi! Ang saya-saya, sana dumagsa kayo sa opening program.', 'image' => 'esperanza_event2.jpg', 'time' => '2 days ago', 'likes' => 61, 'comments' => 0, 'shares' => 9, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Teresita Salazar', 'barangay' => 'Agoho', 'body' => 'Tanong lang po, kailan po ulit ang susunod na free medical mission? Gusto ko sanang isama ang lola ko na senior citizen.', 'image' => null, 'time' => '4 days ago', 'likes' => 12, 'comments' => 1, 'shares' => 1, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Rodrigo Palma', 'barangay' => 'Domorog', 'body' => 'Bumili kayo sa amin, best price sa buong Masbate! Message niyo lang po ako dito para sa mga detalye at promo ngayong buwan.', 'image' => null, 'time' => '6 hrs ago', 'likes' => 2, 'comments' => 0, 'shares' => 0, 'reports' => 3, 'featured' => false, 'status' => 'Pending Review'],
    ],

    'balitaMedia' => [
        ['name' => 'esperanza_festival.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_people.jpg', 'category' => 'IMG'],
        ['name' => 'rectangle_cityhall.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza-aerial.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_community2.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_community3.jpg', 'category' => 'IMG'],
        ['name' => 'fiesta-countdown.gif', 'category' => 'GIF'],
        ['name' => 'medical-mission-recap.mp4', 'category' => 'VID'],
        ['name' => 'barangay-cleanup-highlights.mp4', 'category' => 'VID'],
    ],

    'blotterEntries' => [
        ['ref' => 'BLT-2026-0041', 'barangay' => 'Labangtaytay', 'date' => 'Jul 10, 2026', 'type' => 'Noise Complaint', 'details' => 'Kapitbahay reported ang malakas na videoke sa Purok 10 hanggang alas-dose ng gabi. Pinag-usapan ang magkabilang panig, nagkasundo na tatapusin ang videoke bago mag-alas-diyes.', 'reportedBy' => 'Jenneth A. Cachila', 'status' => 'Resolved'],
        ['ref' => 'BLT-2026-0038', 'barangay' => 'Labangtaytay', 'date' => 'Jun 22, 2026', 'type' => 'Property Dispute', 'details' => 'Away sa hangganan ng lote sa pagitan ng dalawang magkapitbahay sa Purok 9. Na-refer sa Lupong Tagapamayapa para sa mediation.', 'reportedBy' => 'Jenneth A. Cachila', 'status' => 'Under Mediation'],
        ['ref' => 'BLT-2026-0025', 'barangay' => 'Poblacion', 'date' => 'May 30, 2026', 'type' => 'Lost Item', 'details' => 'Nawalang cellphone na-report sa Barangay Hall, na-turn over ng nakakita ilang oras pagkatapos.', 'reportedBy' => 'Barangay Secretary', 'status' => 'Resolved'],
        ['ref' => 'BLT-2026-0019', 'barangay' => 'Masbaranon', 'date' => 'May 12, 2026', 'type' => 'Domestic Dispute', 'details' => 'Away mag-asawa na-report ng kapitbahay. Pinuntahan ng Barangay Tanod, pinayapa ang magkabilang panig, walang isinampang reklamo.', 'reportedBy' => 'Barangay Secretary', 'status' => 'Closed'],
    ],

    'bannerThemes' => [
        'from-gold-500 to-orange-600' => 'Gold → Orange',
        'from-brand-600 to-navy-800' => 'Brand Blue → Navy',
        'from-emerald-600 to-teal-700' => 'Emerald → Teal',
        'from-cyan-700 to-navy-900' => 'Cyan → Navy',
        'from-purple-600 to-navy-800' => 'Purple → Navy',
        'from-rose-600 to-navy-900' => 'Rose → Navy',
    ],

    'bannerCtaRoutes' => [
        'citizen.dashboard' => 'Dashboard',
        'citizen.document-requests' => 'Dokyu (Document Requests)',
        'citizen.assistance-requests' => 'Tulong (Assistance Requests)',
        'citizen.announcements' => 'Balita (Announcements)',
        'citizen.events' => 'Events',
        'citizen.directory' => 'Government Directory',
    ],
];
