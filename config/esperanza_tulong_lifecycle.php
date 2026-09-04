<?php

// Tulong (assistance-request) CASE-status vocabulary — a CLOSED set, declared
// here so it is part of the contract rather than living only in prose and in
// view fixtures.
//
// Why this file exists: the eleven names below were hand-typed in
// resources/views/admin/assistance-requests.blade.php, and PROCESS_TAB_FLOW.txt
// PART 2 was the only place the full set was written down. A consuming codebase
// cannot read this repo's Blade or its .txt files — it reads config/ — so the
// backend lane measured six of these as unvendorable and was blocked on them.
// CLAUDE.md's own rule is that "a hand-typed list of barangays, statuses,
// permissions or fees is a bug, even if it happens to be correct today".
//
// THIS IS NOT AN EXTENSION OF THE 15-VALUE REQUEST LIFECYCLE. CLAUDE.md's
// "Universal status system" documents Tulong as a deliberate exception that
// "runs its own extended, case-management-specific status vocabulary instead of
// this list". Tulong carries a SEPARATE closed set; the request lifecycle stays
// at fifteen. Do not merge the two into one enum of twenty-two — four names
// (Submitted, Rejected, Cancelled, Completed) are common to both by coincidence
// of English, not because the machines are shared, and seven are Tulong-only.
//
// Two nesting levels use the key `status` in the Tulong fixtures and they are
// DIFFERENT vocabularies. This file declares the CASE level only:
//   case level        (rows keyed by an `AR-` ref)  — the eleven below
//   requirement level (rows keyed by `req_*`)       — Submitted, Approved,
//                     Rejected, Flagged for Replacement; shared with Dokyu,
//                     and NOT declared here because it is not Tulong-local.
// Flattening the two produces an enum that is wrong at both levels.
//
// Source of truth: PROCESS_TAB_FLOW.txt PART 2, sections 8, 9 and 10.
// tests/Feature/TulongLifecycleTest.php pins this file to that spec and to the
// stepper orders in the Blade, so none of the three can drift alone.

return [

    // Section 8 — the main stage sequence, in order. This is also the citizen
    // stepper's seven steps (citizen/assistance-requests.blade.php stepOrder)
    // and the admin statusOrder.
    'main_sequence' => [
        'Submitted',
        'Initial Screening',
        'Assessment',
        'For Approval',
        'Assistance Processing',
        'Ready for Release',
        'Completed',
    ],

    // Section 9 — side states. These branch OFF the main line and return to it;
    // they are not steps in it. In the stepper they render as a pill under the
    // parent step, not as a numbered stage.
    'side_states' => [
        // branches off Initial Screening, returns to Initial Screening
        'Needs Requirements',
        // branches off For Approval, returns to Assessment
        'Returned for Reassessment',
    ],

    // Section 9 — the only terminal states. Completed is terminal AND the last
    // step of the main sequence, so it appears in both lists on purpose.
    'terminal' => [
        'Completed',
        'Rejected',
        'Cancelled',
    ],

    // The full closed set: main_sequence + side_states + terminal, deduplicated.
    // Consumers that need one flat enum should read THIS key. Eleven values.
    'all' => [
        'Submitted',
        'Initial Screening',
        'Assessment',
        'For Approval',
        'Assistance Processing',
        'Ready for Release',
        'Completed',
        'Needs Requirements',
        'Returned for Reassessment',
        'Rejected',
        'Cancelled',
    ],

    // The seven names that exist ONLY in Tulong — i.e. that are not among the
    // fifteen request-lifecycle statuses in CLAUDE.md. Recorded explicitly
    // because a consuming lane that diffs the two sets by hand is exactly the
    // step where one gets missed: `Assessment` reads like a common word and
    // already appears in config/ as display copy inside `process` arrays
    // ("MSWDO Assessment"), so a substring search finds it and a status search
    // does not.
    'tulong_only' => [
        'Initial Screening',
        'Assessment',
        'For Approval',
        'Assistance Processing',
        'Ready for Release',
        'Needs Requirements',
        'Returned for Reassessment',
    ],

    // Section 10 — whole-case transitions, as `from => [to, ...]`. A status
    // absent as a key is terminal. Reject and Cancel always require a typed
    // reason; so does Return for Reassessment.
    'transitions' => [
        'Submitted' => ['Initial Screening', 'Cancelled'],
        'Initial Screening' => ['Assessment', 'Needs Requirements', 'Rejected', 'Cancelled'],
        'Needs Requirements' => ['Initial Screening', 'Cancelled'],
        'Assessment' => ['For Approval', 'Rejected', 'Cancelled'],
        'For Approval' => ['Assistance Processing', 'Returned for Reassessment', 'Rejected'],
        'Returned for Reassessment' => ['Assessment'],
        'Assistance Processing' => ['Ready for Release'],
        'Ready for Release' => ['Completed'],
    ],

    // Transitions that cannot be taken without an operator-typed reason.
    'requires_reason' => [
        'Rejected',
        'Cancelled',
        'Returned for Reassessment',
    ],

    // Ready for Release -> Completed is additionally blocked until Proof of
    // Release evidence is captured (section 8, and section 10's footnote).
    'blocked_until_release_evidence' => [
        'Ready for Release' => 'Completed',
    ],
];
