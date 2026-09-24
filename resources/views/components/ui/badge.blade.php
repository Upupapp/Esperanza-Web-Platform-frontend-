{{--
    statusBind: an Alpine expression (e.g. "req.status") for a badge whose
    status is not known until an API response loads, so it cannot be resolved
    server-side the way every other call site's plain `status` string is
    (production-readiness programme, 2026-09-25). Mirrors $styles below in
    JS rather than reusing PHP's copy, because the two run in different
    engines; MUST be kept in sync with it by hand -- see
    tests/Feature/StatusVocabularyTest.php, which asserts against the PHP
    side and would need the same addition here noted if it grows a JS check.
--}}
@props(['status' => 'Draft', 'statusBind' => null])

@php
    // Universal status system — colors are fixed per status name across the whole
    // platform. THREE axes live here (see CLAUDE.md "Universal status system"):
    // the 15 request-lifecycle statuses, the 2-value ACCOUNT access tier, and
    // the 4-rung RECORD verification ladder. A colour may repeat ACROSS axes —
    // Verified reuses Approved's emerald — because two names from different
    // axes never appear in the same column.
    $styles = [
        'Draft' => ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
        'Submitted' => ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
        'Pending Review' => ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'Under Verification' => ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
        'Assigned' => ['bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
        'Processing' => ['bg-brand-50 text-brand-700 ring-brand-200', 'bg-brand-500'],
        'Under Review' => ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
        'Resubmitted' => ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'Approved' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        // Account access tier + record verification ladder. The ladder reads as a
        // progression: Unverified (amber) -> For Validation (sky) -> Partially
        // Verified (violet) -> Verified (emerald). Partially Verified is the
        // SECOND MOST COMMON verification value in the data and had no style at
        // all, so it fell through to Draft's grey and read as inactive.
        'Verified' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        'Partially Verified' => ['bg-violet-50 text-violet-700 ring-violet-200', 'bg-violet-500'],
        'For Validation' => ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'Unverified' => ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'Rejected' => ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
        'Mark to Release' => ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
        'Released' => ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
        'Completed' => ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
        'Cancelled' => ['bg-slate-100 text-slate-500 ring-slate-200', 'bg-slate-400'],
        'Archived' => ['bg-slate-100 text-slate-400 ring-slate-200', 'bg-slate-300'],
    ];

    // An unlisted status used to fall through to Draft's styling, which is the
    // dangerous shape: the label reads correctly while the COLOUR says
    // "inactive". That is how a value missing from the vocabulary looks like a
    // styling nit rather than a data defect. Unknown values now get their own
    // neutral treatment and a data-unknown-status marker, so a rendered-DOM
    // sweep can find them instead of a person noticing the wrong shade.
    $known = array_key_exists($status, $styles);
    [$classes, $dot] = $known
        ? $styles[$status]
        : ['bg-white text-slate-500 ring-slate-300 border-dashed', 'bg-slate-300'];

    $badgeAttributes = ['class' => "inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap $classes"];

    // Merged in PHP rather than written as a Blade conditional inside the tag:
    // an @unless there leaves its surrounding whitespace in the output, which
    // changed every badge on 14 pages for no reason.
    if (! $known) {
        $badgeAttributes['data-unknown-status'] = $status;
    }
@endphp

@if($statusBind)
    <span
        x-data="{
            styles: @js(collect($styles)->map(fn ($v) => ['classes' => $v[0], 'dot' => $v[1]])),
            get known() { return Object.prototype.hasOwnProperty.call(this.styles, {{ $statusBind }}); },
            get style() { return this.known ? this.styles[{{ $statusBind }}] : { classes: 'bg-white text-slate-500 ring-slate-300 border-dashed', dot: 'bg-slate-300' }; },
        }"
        {{ $attributes->class(['inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap']) }}
        :class="style.classes"
        :data-unknown-status="!known ? {{ $statusBind }} : null"
    >
        <span class="w-1.5 h-1.5 rounded-full" :class="style.dot"></span>
        <span x-text="{{ $statusBind }}"></span>
    </span>
@else
    <span {{ $attributes->merge($badgeAttributes) }}>
        <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
        {{ $status }}
    </span>
@endif
