{{-- The resident's guardian (welfare round 2026-10-29): who to call for a
     minor or a PWD found alone. GET /admin/residents/{ref} carries
     `guardian_applicable` and `guardian` ({name, relationship, contact, address,
     source, individual_ref, updated_by, updated_at}; contact and address come
     only with view_sensitive). An officer with const_resident edit sets one -
     typed, or another registry record found by search, whose name is copied -
     or clears it, after which the other sources (the ID application, the
     household) answer again. PUT / DELETE /admin/residents/{ref}/guardian.
     Component residentGuardian (resources/js/scope-admin-records.js); its own
     fields are gd-prefixed so none shadows the modal's (editing, edit, saving). --}}
<div x-data="residentGuardian" x-show="selectedIndividual && (selectedIndividual.guardian || selectedIndividual.guardian_applicable || gdMayEdit)" x-cloak class="surface-glass rounded-[26px] p-4" data-guardian-section>
    <div class="flex items-center justify-between gap-2 mb-3">
        <h3 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="shield-user" class="w-3.5 h-3.5"></i>Guardian</h3>
        <div class="flex items-center gap-1" x-show="gdMayEdit && !gdOpen">
            <button type="button" @click="gdStart()" class="text-xs font-semibold text-brand-700 hover:underline px-1.5 py-1" x-text="gdCurrent?.source === 'record' ? 'Change' : 'Set guardian'"></button>
            <button type="button" x-show="gdCurrent?.source === 'record' && !gdConfirmClear" @click="gdConfirmClear = true" class="text-xs font-semibold text-rose-700 hover:underline px-1.5 py-1">Clear</button>
        </div>
    </div>

    {{-- Clearing asks here, not in a browser pop-up --}}
    <div x-show="gdConfirmClear" x-cloak role="alertdialog" aria-label="Clear the guardian" class="mb-3 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-900 space-y-2">
        <p>Clear the guardian the office recorded? If the person's ID application or household names someone, that answers again.</p>
        <div class="flex items-center gap-2">
            <x-ui.button size="sm" variant="danger" x-bind:disabled="gdBusy" @click="gdClear()"><span x-text="gdBusy ? 'Clearing…' : 'Clear guardian'"></span></x-ui.button>
            <x-ui.button size="sm" variant="secondary" @click="gdConfirmClear = false">Keep it</x-ui.button>
        </div>
    </div>

    {{-- What is on record --}}
    <div x-show="!gdOpen">
        <template x-if="gdCurrent">
            <dl class="space-y-1.5 text-sm">
                <div class="flex items-baseline justify-between gap-3"><dt class="text-xs text-ink-muted shrink-0">Name</dt><dd class="font-semibold text-ink text-right break-words" x-text="gdCurrent.name"></dd></div>
                <div class="flex items-baseline justify-between gap-3" x-show="gdCurrent.relationship"><dt class="text-xs text-ink-muted shrink-0">Relationship</dt><dd class="text-ink text-right" x-text="gdCurrent.relationship"></dd></div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-xs text-ink-muted shrink-0">Contact</dt>
                    <dd class="text-right">
                        <span x-show="gdCurrent.contact" class="inline-flex items-center gap-2">
                            <a x-show="gdTel(gdCurrent.contact)" :href="gdTel(gdCurrent.contact)" :aria-label="'Call ' + gdCurrent.contact" class="text-brand-600 hover:text-brand-700"><i data-lucide="phone" class="w-3.5 h-3.5"></i></a>
                            <span class="font-mono font-semibold text-ink select-all" x-text="gdCurrent.contact"></span>
                        </span>
                        <span x-show="!gdCurrent.contact" class="text-xs text-ink-muted">Not on record, or hidden for your role</span>
                    </dd>
                </div>
                <div class="flex items-baseline justify-between gap-3" x-show="gdCurrent.address"><dt class="text-xs text-ink-muted shrink-0">Address</dt><dd class="text-ink text-right break-words min-w-0" x-text="gdCurrent.address"></dd></div>
                <p class="pt-1 text-[11px] text-ink-muted" x-text="gdSourceLine"></p>
                <a x-show="gdCurrent.individual_ref" :href="'/admin/constituents?ref=' + encodeURIComponent(gdCurrent.individual_ref || '')" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="user-round-search" class="w-3.5 h-3.5"></i><span x-text="'Open ' + gdCurrent.individual_ref"></span></a>
            </dl>
        </template>
        <p x-show="!gdCurrent && selectedIndividual?.guardian_applicable" class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">No guardian on record. A minor or a PWD found alone is easier to bring home with one.</p>
        <p x-show="!gdCurrent && !selectedIndividual?.guardian_applicable" class="text-xs text-ink-muted">None recorded. A guardian matters most for a minor or a PWD; you can still record one.</p>
    </div>

    {{-- Setting one --}}
    <form x-show="gdOpen" x-cloak @submit.prevent="gdSave()" class="space-y-3" @keydown.escape.stop="gdOpen = false">
        <div class="flex gap-1 rounded-xl bg-slate-100 p-1" role="radiogroup" aria-label="How to give the guardian">
            <button type="button" role="radio" :aria-checked="gdMode === 'typed' ? 'true' : 'false'" @click="gdMode = 'typed'" class="flex-1 h-8 rounded-lg text-xs font-semibold transition-colors" :class="gdMode === 'typed' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">Type the details</button>
            <button type="button" role="radio" :aria-checked="gdMode === 'linked' ? 'true' : 'false'" @click="gdMode = 'linked'; $nextTick(() => document.getElementById('gd-search')?.focus())" class="flex-1 h-8 rounded-lg text-xs font-semibold transition-colors" :class="gdMode === 'linked' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">A registry record</button>
        </div>

        <div x-show="gdMode === 'linked'" class="space-y-2">
            <div x-show="!gdPicked">
                <label for="gd-search" class="block text-xs font-medium text-slate-700 mb-1">Find the guardian in the registry</label>
                <input id="gd-search" type="search" x-model="gdQuery" @input.debounce.350ms="gdSearch()" autocomplete="off" placeholder="Name or reference (at least 2 characters)" class="w-full rounded-xl py-2 px-3 text-sm text-ink-body border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <p x-show="gdSearching" class="text-[11px] text-ink-muted mt-1">Searching…</p>
                <p x-show="!gdSearching && gdSearched && !gdResults.length" class="text-[11px] text-ink-muted mt-1">No resident record matches.</p>
                <ul x-show="gdResults.length" class="mt-1.5 max-h-48 overflow-y-auto scrollbar-thin rounded-xl border border-slate-200 bg-white divide-y divide-slate-100">
                    <template x-for="r in gdResults" :key="r.ref">
                        <li><button type="button" @click="gdPick(r)" class="w-full text-left px-3 py-2 hover:bg-brand-50 focus:bg-brand-50 outline-none">
                            <span class="block text-sm font-semibold text-ink" x-text="r.name"></span>
                            <span class="block text-[11px] text-ink-muted" x-text="[r.ref, r.age != null ? r.age + ' yrs' : null, r.relation, r.barangay ? 'Brgy. ' + r.barangay : null].filter(Boolean).join(' · ')"></span>
                        </button></li>
                    </template>
                </ul>
            </div>
            <div x-show="gdPicked" class="flex items-center justify-between gap-2 rounded-xl bg-brand-50 border border-brand-100 px-3 py-2">
                <p class="text-sm min-w-0"><span class="font-semibold text-ink" x-text="gdPicked?.name"></span> <span class="text-[11px] font-mono text-ink-muted" x-text="gdPicked?.ref"></span></p>
                <button type="button" @click="gdPicked = null; $nextTick(() => document.getElementById('gd-search')?.focus())" class="text-xs font-semibold text-brand-700 hover:underline shrink-0">Change</button>
            </div>
        </div>

        <div x-show="gdMode === 'typed'">
            <label for="gd-name" class="block text-xs font-medium text-slate-700 mb-1">Name</label>
            <input id="gd-name" type="text" x-model="gdForm.name" maxlength="150" class="w-full rounded-xl py-2 px-3 text-sm text-ink-body border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
            <p x-show="gdError('name')" class="text-[11px] text-rose-700 mt-0.5" x-text="gdError('name')"></p>
        </div>
        <p x-show="gdError('individual_ref')" class="text-[11px] text-rose-700" x-text="gdError('individual_ref')"></p>
        <div>
            <label for="gd-relationship" class="block text-xs font-medium text-slate-700 mb-1">Relationship to the resident</label>
            <input id="gd-relationship" type="text" x-model="gdForm.relationship" list="gd-relationships" maxlength="60" placeholder="e.g. Mother" class="w-full rounded-xl py-2 px-3 text-sm text-ink-body border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
            <datalist id="gd-relationships">
                @foreach(['Mother', 'Father', 'Grandmother', 'Grandfather', 'Sister', 'Brother', 'Aunt', 'Uncle', 'Spouse', 'Son', 'Daughter', 'Legal guardian', 'Caregiver', 'Other relative'] as $relation)
                    <option value="{{ $relation }}"></option>
                @endforeach
            </datalist>
            <p x-show="gdError('relationship')" class="text-[11px] text-rose-700 mt-0.5" x-text="gdError('relationship')"></p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="gd-contact" class="block text-xs font-medium text-slate-700 mb-1">Contact number</label>
                <input id="gd-contact" type="tel" x-model="gdForm.contact" maxlength="40" placeholder="09XX XXX XXXX" class="w-full rounded-xl py-2 px-3 text-sm text-ink-body border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <p x-show="gdError('contact')" class="text-[11px] text-rose-700 mt-0.5" x-text="gdError('contact')"></p>
            </div>
            <div>
                <label for="gd-address" class="block text-xs font-medium text-slate-700 mb-1">Address</label>
                <input id="gd-address" type="text" x-model="gdForm.address" maxlength="250" class="w-full rounded-xl py-2 px-3 text-sm text-ink-body border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
            </div>
        </div>
        <p x-show="gdMode === 'linked'" class="text-[11px] text-ink-muted">The name comes from the linked record. Leave the number or address empty to use the record's own.</p>
        <div class="flex items-center gap-2">
            <x-ui.button type="submit" size="sm" icon="check" x-bind:disabled="gdBusy || !gdReady"><span x-text="gdBusy ? 'Saving…' : 'Save guardian'"></span></x-ui.button>
            <x-ui.button size="sm" variant="secondary" @click="gdOpen = false">Cancel</x-ui.button>
        </div>
    </form>
</div>
