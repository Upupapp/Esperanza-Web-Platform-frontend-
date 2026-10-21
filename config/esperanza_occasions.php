<?php

// Banners & Occasions (owner, 2026-10-21): the lists the Occasion & Pop-up
// manager and the banner form choose from. The backend reads every one of
// them from here (FrontendContract::occasions()); nothing below is typed a
// second time on either side.
//
// An occasion is a pop-up residents see when they open Esperanza - a
// birthday, a barangay or municipal fiesta, a holiday, an LGU event - on a
// schedule, to an audience, as often as its frequency allows. Balita posts
// are no longer split into greetings and regular posts: an occasion can
// point to a Balita post instead.

return [

    'types' => [
        ['key' => 'birthday', 'en' => 'Birthday', 'fil' => 'Kaarawan'],
        ['key' => 'barangay_fiesta', 'en' => 'Barangay Fiesta', 'fil' => 'Pista ng Barangay'],
        ['key' => 'municipal_fiesta', 'en' => 'Municipality Fiesta', 'fil' => 'Pista ng Bayan'],
        ['key' => 'national_holiday', 'en' => 'National Holiday', 'fil' => 'Pambansang Pista Opisyal'],
        ['key' => 'lgu_event', 'en' => 'LGU Event', 'fil' => 'Kaganapan ng LGU'],
        ['key' => 'custom', 'en' => 'Custom', 'fil' => 'Iba pa'],
    ],

    // When it shows. "window" is one date or a range; "annual" repeats that
    // range every year; "birthday" is each resident's own birthday (from the
    // birthdate on their account), inside the window if one is given.
    'triggers' => [
        ['key' => 'window', 'en' => 'Specific date or date range', 'fil' => 'Tiyak na petsa o hanay ng petsa'],
        ['key' => 'annual', 'en' => 'Every year on the same dates', 'fil' => 'Taon-taon sa parehong petsa'],
        ['key' => 'birthday', 'en' => "On each resident's birthday", 'fil' => 'Sa kaarawan ng bawat residente'],
    ],

    // Where an occasion stands, worked out from its status, its dates and its
    // pause switch. These are labels, not statuses: what is stored is one of
    // the platform's own (Draft, Pending Review, Approved, Released, Rejected,
    // Archived) plus whether it is paused.
    'states' => [
        ['key' => 'draft', 'en' => 'Draft', 'fil' => 'Draft'],
        ['key' => 'scheduled', 'en' => 'Scheduled', 'fil' => 'Nakaiskedyul'],
        ['key' => 'active', 'en' => 'Active', 'fil' => 'Aktibo'],
        ['key' => 'paused', 'en' => 'Paused', 'fil' => 'Naka-pause'],
        ['key' => 'completed', 'en' => 'Completed', 'fil' => 'Tapos na'],
        ['key' => 'archived', 'en' => 'Archived', 'fil' => 'Naka-archive'],
    ],

    // Who sees it, besides "every resident" and chosen barangays. Senior
    // citizens are worked out from the birthdate (60 or older on the day).
    // The other sectors come from the tags barangays keep on constituent
    // records, so a resident whose record is not linked or not tagged is
    // missed: the form says so.
    'audience_sectors' => [
        ['key' => 'senior', 'en' => 'Senior citizens (60+)', 'fil' => 'Mga senior citizen (60+)', 'source' => 'birthdate'],
        ['key' => 'pwd', 'en' => 'Persons with disability', 'fil' => 'Mga may kapansanan', 'source' => 'barangay_records'],
        ['key' => 'solo_parent', 'en' => 'Solo parents', 'fil' => 'Mga solo parent', 'source' => 'barangay_records'],
        ['key' => '4ps', 'en' => '4Ps beneficiaries', 'fil' => 'Mga benepisyaryo ng 4Ps', 'source' => 'barangay_records'],
        ['key' => 'ip', 'en' => 'Indigenous peoples', 'fil' => 'Mga katutubo', 'source' => 'barangay_records'],
    ],

    // How often one resident sees the pop-up while it is on. "once" is once
    // per occurrence: once a year for an annual occasion or a birthday.
    'popup_frequencies' => [
        ['key' => 'once', 'en' => 'Once per resident', 'fil' => 'Isang beses bawat residente'],
        ['key' => 'daily', 'en' => 'Once a day', 'fil' => 'Isang beses bawat araw'],
        ['key' => 'every_launch', 'en' => 'Every time Esperanza is opened', 'fil' => 'Tuwing bubuksan ang Esperanza'],
    ],

    // What closing it means. "seen": showing it counts, and the frequency
    // decides when it comes back. "until_dismissed": it comes back as the
    // frequency allows until the resident taps "Don't show again".
    'popup_dismissals' => [
        ['key' => 'seen', 'en' => 'Closing it counts as seen', 'fil' => 'Nakita na kapag isinara'],
        ['key' => 'until_dismissed', 'en' => "Show until the resident taps \"Don't show again\"", 'fil' => 'Ipakita hanggang pindutin ng residente ang "Huwag nang ipakita"'],
    ],

    // Where a banner appears. The Home carousel is on the resident's web
    // dashboard and the app's Home screen.
    'banner_placements' => [
        ['key' => 'home', 'en' => 'Home carousel', 'fil' => 'Carousel sa Home'],
    ],

    // How an occasion's banner shows while the occasion is on, when the two
    // are synchronised. The banner's own schedule is never changed.
    'banner_displays' => [
        ['key' => 'carousel', 'en' => 'In the carousel, in its usual place', 'fil' => 'Sa carousel, sa karaniwang puwesto'],
        ['key' => 'carousel_first', 'en' => 'First in the carousel', 'fil' => 'Una sa carousel'],
    ],

    // Banner and pop-up pictures. The server checks the real file type (not
    // its name) and its size; the browser checks the same first so nobody
    // waits for an upload that will be refused. The carousel is 3:1 on the
    // web and about 2.3:1 on a phone, centre-cropped.
    'image' => [
        'mime' => ['image/jpeg', 'image/png', 'image/webp'],
        // The largest picture taken, after this computer resizes it. Set per
        // deployment (ESPERANZA_MEDIA_MAX_IMAGE_MB); the server's own limit
        // is the same setting, and the server's is the one that decides.
        'max_bytes' => (int) env('ESPERANZA_MEDIA_MAX_IMAGE_MB', 10) * 1024 * 1024,
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'banner_recommended' => ['width' => 1800, 'height' => 600],
        'banner_min' => ['width' => 900, 'height' => 300],
        'popup_recommended' => ['width' => 1080, 'height' => 1080],
    ],

];
