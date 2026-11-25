{{-- ============================================================
     The session window and the New / Edit session form (welfare round,
     2026-10-29). The window: when and where, the facilitators, the
     participants (role, case, account, attendance, certificate), Add
     waiting cases, a walk-in filed through Apply for New Tulong, Remove,
     Save attendance, Edit, Cancel with a reason, and "Finish and issue
     certificates" - confirmed here on the page, saying who receives one.
     The form: service, title, date, start and end, venue, capacity, notes,
     and the facilitators (name, position, role; a counselor's DSWD
     accreditation number and its validity). Placed after the case window,
     so it opens above it. Logic: sessionDesk() in
     resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
<div x-data="sessionDesk()"
     x-on:tulong-open-session.window="sdShow($event.detail?.ref)"
     x-on:tulong-new-session.window="sfNew()"
     x-on:welfare-session-changed.window="if ($event.detail?.source !== 'desk' && ses && sdOpen && $event.detail?.ref === ses.ref) { $event.detail.session ? sdTake($event.detail.session) : sdShow(ses.ref) }">

    {{-- The windows load apart from the page: lazy/tulong-session-desk. --}}
    <x-lazy-partial name="tulong-session-desk" when="sdOpen || sfOpen" />
</div>
