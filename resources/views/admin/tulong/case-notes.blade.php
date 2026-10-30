{{-- A counseling, referral or session case's "Case notes" (round 2,
     2026-10-30; the welfare round's "Service records"): each session held,
     home visit or referral written on the case with its date, how it was
     done and what was done (GET /admin/services-rendered?request_ref=, and
     "Add a note": POST /admin/services-rendered with the case's request_ref -
     the person and the Tulong type are the case's; a confidential type's
     notes stay with its office, as the server keeps them). For counseling and
     referral, once a note is on it, "Close the case" with a closing summary
     (POST .../complete-service); a session's case is closed by finishing its
     session. The owner folded the separate "Services rendered" view and
     "Record a service" window back into Tulong: these notes are the only
     place they are written now. Inside the Tulong case window; logic:
     caseNotes() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling. --}}
<div x-data="caseNotes()" x-effect="cnSync()" x-show="cnShown()" x-cloak
     class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-3.5 mt-4" data-testid="case-notes">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i> Case notes</p>
        <div class="flex items-center gap-2">
            <x-ui.button size="sm" variant="secondary" icon="plus" x-show="cnCanAdd() && !cnAdding" @click="cnStartAdding()">Add a note</x-ui.button>
            <x-ui.button size="sm" icon="circle-check" x-show="cnMayClose() && !cnClosing" x-bind:disabled="!cnLive()" x-bind:title="cnLive() ? null : 'Add a note first.'" @click="cnClosing = true; cnError = ''; $nextTick(() => document.getElementById('cn-close-note')?.focus())">Close the case</x-ui.button>
        </div>
    </div>
    <p class="text-[11px] text-ink-muted mt-1" x-text="processingKind === 'session' ? 'The session is noted here for each participant when it is finished. Add a note for anything else done on the case.' : 'Note each session held, home visit or referral as it happens: the notes are the case\'s work.'"></p>
    <p x-show="cnMayClose() && !cnLive() && !cnLoading" x-cloak class="text-[11px] text-amber-700 mt-1">Add at least one note before closing the case.</p>

    {{-- Add a note: when, how, what was done. --}}
    <div x-show="cnAdding" x-cloak class="mt-3 rounded-xl border border-indigo-100 bg-white/80 p-3 space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div>
                <label for="cn-date" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Date *</label>
                <input id="cn-date" type="date" x-model="cnDate" :max="cnToday()" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label for="cn-mode" class="block text-[11px] font-semibold text-slate-700 mb-0.5">How</label>
                <select id="cn-mode" x-model="cnMode" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-indigo-100">
                    <template x-for="m in cnModes" :key="m[0]"><option :value="m[0]" x-text="m[1]" :selected="cnMode === m[0]"></option></template>
                </select>
            </div>
        </div>
        <div>
            <label for="cn-details" class="block text-[11px] font-semibold text-slate-700 mb-0.5">What was done *</label>
            <textarea id="cn-details" x-model="cnDetails" rows="3" maxlength="2000" placeholder="The session or visit, what was discussed or given, any referral made." class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-indigo-100"></textarea>
        </div>
        <p x-show="cnSensitive()" x-cloak class="text-[11px] text-rose-700">Confidential: these notes are shown only to the officers of this office.</p>
        <p x-show="cnAddError" class="text-xs text-rose-700" role="alert" x-text="cnAddError"></p>
        <div class="flex flex-wrap gap-2">
            <x-ui.button size="sm" icon="check" loading="cnSaving" @click="cnSave()">Save the note</x-ui.button>
            <x-ui.button size="sm" variant="secondary" x-bind:disabled="cnSaving" @click="cnAdding = false">Cancel</x-ui.button>
        </div>
    </div>

    <div x-show="cnLoading && !cnRows.length" class="mt-2"><x-ui.skeleton class="h-10" /></div>
    <p x-show="cnFailed" x-cloak class="mt-2 text-xs text-rose-700" role="alert">Could not load the notes. <button type="button" class="underline" @click="cnLoad()">Try again</button></p>
    <p x-show="!cnLoading && !cnFailed && !cnRows.length" x-cloak class="mt-2 text-xs text-ink-muted">No note on this case yet.</p>
    <ol x-show="cnRows.length" x-cloak class="mt-2 space-y-1.5">
        <template x-for="r in cnRows" :key="r.ref">
            <li class="rounded-xl border bg-white/80 px-3 py-2" :class="r.voided_at ? 'border-slate-200 opacity-60' : 'border-indigo-100'">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <p class="text-xs font-semibold text-ink" :class="r.voided_at && 'line-through'" x-text="day(r.rendered_on) + (modeLabel(r.mode) ? ' · ' + modeLabel(r.mode) : '')"></p>
                    <span class="text-[10px] text-ink-muted" x-text="[r.rendered_by_name, r.confidential ? 'Confidential' : ''].filter(Boolean).join(' · ')"></span>
                </div>
                <p x-show="r.details" class="text-xs text-slate-600 whitespace-pre-line mt-0.5" x-text="r.details"></p>
                <p x-show="r.referred_to" class="text-[11px] text-ink-muted" x-text="'Referred to ' + r.referred_to"></p>
                <p x-show="r.amount" class="text-[11px] text-ink-muted" x-text="'Amount ' + peso(r.amount)"></p>
                <p x-show="r.voided_at" class="text-[11px] text-rose-700" x-text="'Voided' + (r.void_reason ? ': ' + r.void_reason : '')"></p>
                <button type="button" x-show="cnCanVoid() && !r.voided_at && !r._voiding" @click="r._voiding = true; r._reason = ''" class="text-[11px] font-medium text-rose-600 hover:underline">Void</button>
                <div x-show="r._voiding" x-cloak class="mt-1.5 space-y-1.5">
                    <label :for="'cn-void-' + r.ref" class="block text-[11px] font-semibold text-rose-800">Reason for voiding</label>
                    <input :id="'cn-void-' + r.ref" type="text" x-model="r._reason" maxlength="250" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-rose-100">
                    <div class="flex gap-2">
                        <button type="button" :disabled="r._reason.trim().length < 5 || r._busy" @click="cnVoid(r)" class="rounded-lg bg-rose-600 px-2 py-1 text-[11px] font-medium text-white disabled:opacity-40" x-text="r._busy ? 'Voiding…' : 'Void'"></button>
                        <button type="button" @click="r._voiding = false" class="text-[11px] text-ink-muted">Keep it</button>
                    </div>
                </div>
            </li>
        </template>
    </ol>

    <div x-show="cnClosing" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 space-y-2">
        <label for="cn-close-note" class="block text-xs font-semibold text-emerald-900">Closing summary *</label>
        <textarea id="cn-close-note" x-model="cnNote" rows="3" maxlength="2000" placeholder="What was done, the outcome, and any referral made." class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-emerald-100"></textarea>
        <p class="text-[11px] text-emerald-800">The case moves to Completed. The resident is told it is closed; the notes stay with the office.</p>
        <p x-show="cnError" class="text-xs text-rose-700" role="alert" x-text="cnError"></p>
        <div class="flex flex-wrap gap-2">
            <x-ui.button size="sm" icon="circle-check" loading="cnBusy" @click="cnClose()">Close the case</x-ui.button>
            <x-ui.button size="sm" variant="secondary" @click="cnClosing = false">Not yet</x-ui.button>
        </div>
    </div>
</div>
