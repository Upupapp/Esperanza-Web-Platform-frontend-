{{-- A counseling, referral or session case's "Service records" (welfare
     round, 2026-10-29): the work done on the case, each session or service
     recorded with its date, kind and details (GET
     /admin/services-rendered?request_ref=), "Add a record", and - for
     counseling and referral, once at least one record is on it - "Close the
     case" with a closing summary (POST .../complete-service). A session's
     case is closed by finishing its session. Inside the Tulong case
     window; logic: caseServiceRecords() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling. --}}
<div x-data="caseServiceRecords()" x-effect="crSync()" x-show="crShown()" x-cloak
     x-on:welfare-records-changed.window="if ($event.detail?.request_ref && $event.detail.request_ref === crFor) crLoad()"
     class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-3.5 mt-4" data-testid="case-service-records">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i> Service records</p>
        <div class="flex items-center gap-2">
            <x-ui.button size="sm" variant="secondary" icon="plus" x-show="crCanAdd()" @click="crAdd()">Add a record</x-ui.button>
            <x-ui.button size="sm" icon="circle-check" x-show="crMayClose() && !crClosing" x-bind:disabled="!crLive()" x-bind:title="crLive() ? null : 'Record at least one service first.'" @click="crClosing = true; crError = ''; $nextTick(() => document.getElementById('cr-note')?.focus())">Close the case</x-ui.button>
        </div>
    </div>
    <p class="text-[11px] text-ink-muted mt-1" x-text="processingKind === 'session' ? 'Written for each participant when the session is finished. Record anything else given here.' : 'Record each session, home visit or referral as it happens. The records are the case\'s work.'"></p>
    <p x-show="crMayClose() && !crLive() && !crLoading" x-cloak class="text-[11px] text-amber-700 mt-1">Record at least one service before closing the case.</p>

    <div x-show="crLoading && !crRows.length" class="mt-2"><x-ui.skeleton class="h-10" /></div>
    <p x-show="crFailed" x-cloak class="mt-2 text-xs text-rose-700" role="alert">Could not load the records. <button type="button" class="underline" @click="crLoad()">Try again</button></p>
    <p x-show="!crLoading && !crFailed && !crRows.length" x-cloak class="mt-2 text-xs text-ink-muted">No service recorded on this case yet.</p>
    <ol x-show="crRows.length" x-cloak class="mt-2 space-y-1.5">
        <template x-for="r in crRows" :key="r.ref">
            <li class="rounded-xl border bg-white/80 px-3 py-2" :class="r.voided_at ? 'border-slate-200 opacity-60' : 'border-indigo-100'">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <p class="text-xs font-semibold text-ink" :class="r.voided_at && 'line-through'" x-text="day(r.rendered_on) + ' · ' + r.service_name"></p>
                    <span class="text-[10px] text-ink-muted" x-text="[kindLabel(r.kind), modeLabel(r.mode), r.rendered_by_name].filter(Boolean).join(' · ')"></span>
                </div>
                <p x-show="r.details" class="text-xs text-slate-600 whitespace-pre-line mt-0.5" x-text="r.details"></p>
                <p x-show="r.referred_to" class="text-[11px] text-ink-muted" x-text="'Referred to ' + r.referred_to"></p>
                <p x-show="r.amount" class="text-[11px] text-ink-muted" x-text="'Amount ' + peso(r.amount)"></p>
                <p x-show="r.voided_at" class="text-[11px] text-rose-700" x-text="'Voided' + (r.void_reason ? ': ' + r.void_reason : '')"></p>
                <button type="button" x-show="crCanVoid() && !r.voided_at && !r._voiding" @click="r._voiding = true; r._reason = ''" class="text-[11px] font-medium text-rose-600 hover:underline">Void</button>
                <div x-show="r._voiding" x-cloak class="mt-1.5 space-y-1.5">
                    <label :for="'cr-void-' + r.ref" class="block text-[11px] font-semibold text-rose-800">Reason for voiding</label>
                    <input :id="'cr-void-' + r.ref" type="text" x-model="r._reason" maxlength="250" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-rose-100">
                    <div class="flex gap-2">
                        <button type="button" :disabled="r._reason.trim().length < 5 || r._busy" @click="crVoid(r)" class="rounded-lg bg-rose-600 px-2 py-1 text-[11px] font-medium text-white disabled:opacity-40" x-text="r._busy ? 'Voiding…' : 'Void'"></button>
                        <button type="button" @click="r._voiding = false" class="text-[11px] text-ink-muted">Keep it</button>
                    </div>
                </div>
            </li>
        </template>
    </ol>

    <div x-show="crClosing" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 space-y-2">
        <label for="cr-note" class="block text-xs font-semibold text-emerald-900">Closing summary *</label>
        <textarea id="cr-note" x-model="crNote" rows="3" maxlength="2000" placeholder="What was done, the outcome, and any referral made." class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-emerald-100"></textarea>
        <p class="text-[11px] text-emerald-800">The case moves to Completed. The resident is told it is closed; the records stay with the office.</p>
        <p x-show="crError" class="text-xs text-rose-700" role="alert" x-text="crError"></p>
        <div class="flex flex-wrap gap-2">
            <x-ui.button size="sm" icon="circle-check" loading="crBusy" @click="crClose()">Close the case</x-ui.button>
            <x-ui.button size="sm" variant="secondary" @click="crClosing = false">Not yet</x-ui.button>
        </div>
    </div>
</div>
