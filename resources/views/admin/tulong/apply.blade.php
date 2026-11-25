{{-- ============================================================
     "Apply for New Tulong" (welfare round, 2026-10-29): an officer files a
     case for a person at the office - a walk-in, or a referral from the
     police, the barangay or a school - or records assistance already given.
     Four steps: the person (GET /admin/people/search, or "Not in the list"),
     the service (grouped by category; the ones only officers file are marked
     Office only), the service's form (the citizen wizard's field types with
     their show-if rules), then the requirements' documents (a file or a
     photo each), the person's consent, and "Record assistance already
     given". POST /admin/tulong/requests, then each chosen file to the new
     case (POST .../requirements/{key}/attach, round 2), then the case
     opens. A walk-in at a
     group session files through the same steps (the service is the
     session's): POST /admin/tulong/sessions/{ref}/participants/walk-in.
     Logic: tulongApply() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
{{-- The page's "Apply for New Tulong" button opens it with a 'tulong-apply'
     window event; placed after the session window, so a walk-in filed from a
     session opens above it. --}}
<div x-data="tulongApply()" x-on:tulong-apply.window="openApply($event.detail || {})">
    {{-- The window loads apart from the page: lazy/tulong-apply. --}}
    <x-lazy-partial name="tulong-apply" when="apOpen" />
</div>
