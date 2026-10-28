{{-- ============================================================
     "Record a service" (welfare round, 2026-10-29): a service given to a
     person - a counseling session, a home visit, a referral, assistance from
     another program - written to the services-rendered records
     (POST /admin/services-rendered). From the Services rendered view the
     officer finds the person (GET /admin/people/search) or names one not in
     the list; from a case the record is the case's (request_ref) and the
     person comes from it. A sensitive service's record is always
     confidential. Opened by the 'welfare-record-service' window event.
     Logic: serviceRecordForm() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $rfLabel = 'block text-xs font-semibold text-slate-700 mb-1';
    $rfInput = 'w-full rounded-2xl border-0 surface-pressed-sm py-2 px-3.5 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard';
@endphp
<div x-data="serviceRecordForm()" x-on:welfare-record-service.window="openRecord($event.detail || {})">
    <div x-data="{ get open() { return rfOpen }, set open(v) { rfOpen = v } }">
    <x-ui.modal title="Record a service" maxWidth="lg">
        <div class="space-y-4">
            {{-- The person --}}
            <div x-show="rfRequestRef" class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-700">
                For <span class="font-semibold" x-text="rfFixedPerson || 'the case\'s person'"></span>, on case <span class="font-mono font-semibold" x-text="rfRequestRef"></span>.
            </div>
            <div x-show="!rfRequestRef">
                <div x-show="pMode === 'search'">
                    <label for="rf-person-q" class="{{ $rfLabel }}">Person *</label>
                    <input id="rf-person-q" type="search" x-model="pq" @input.debounce.300ms="pSearch()" autocomplete="off" placeholder="Name, RES- or ESP- number (at least 2 letters)" class="{{ $rfInput }}">
                    <p class="sr-only" aria-live="polite" x-text="pLoading ? 'Searching' : (pSearched ? pResults.length + ' found' : '')"></p>
                    <p x-show="pFailed" x-cloak class="mt-1 text-xs text-rose-700">The search did not answer. <button type="button" class="underline" @click="pSearch()">Try again</button></p>
                    <p x-show="pSearched && !pLoading && !pResults.length" x-cloak class="mt-1 text-xs text-ink-muted">No one found.</p>
                    <ul x-show="pResults.length && !person" x-cloak class="mt-1.5 max-h-48 overflow-y-auto space-y-1">
                        <template x-for="p in pResults" :key="(p.individual_ref || '') + '|' + (p.account_no || '')">
                            <li><button type="button" @click="pPick(p)" class="w-full text-left rounded-xl border border-slate-200 hover:border-brand-200 hover:bg-white/70 px-3 py-1.5">
                                <span class="block text-xs font-semibold text-ink" x-text="p.name"></span>
                                <span class="block text-[11px] text-ink-muted" x-text="pLine(p)"></span>
                            </button></li>
                        </template>
                    </ul>
                    <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2">
                        <p x-show="person" x-cloak class="text-xs text-emerald-700"><span x-text="'For ' + (person?.name || '') + ' · ' + pLine(person)"></span> <button type="button" class="ml-1 underline text-brand-700" @click="person = null">Change</button></p>
                        <button type="button" @click="pMode = 'new'; person = null" class="text-xs font-semibold text-brand-700 hover:underline">Not in the list</button>
                    </div>
                </div>
                <div x-show="pMode === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="rf-new-name" class="{{ $rfLabel }}">Full name *</label>
                        <input id="rf-new-name" type="text" x-model="pNew.name" maxlength="150" class="{{ $rfInput }}">
                    </div>
                    <div>
                        <label for="rf-new-brgy" class="{{ $rfLabel }}">Barangay *</label>
                        <select id="rf-new-brgy" x-model="pNew.barangay" class="{{ $rfInput }}">
                            <option value="">—</option>
                            <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="pNew.barangay === b"></option></template>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="pNew.add_to_registry" class="rounded text-brand-600 focus:ring-brand-200"> Add this person to the registry</label>
                    <button type="button" @click="pMode = 'search'" class="text-xs font-semibold text-brand-700 hover:underline text-left sm:text-right">Back to the search</button>
                </div>
            </div>

            {{-- The service --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label for="rf-service" class="{{ $rfLabel }}">Service given *</label>
                    <select id="rf-service" x-model="rfKey" @change="rfChosen()" :disabled="!!rfRequestRef && !!rfKey && rfKey !== '__other'" class="{{ $rfInput }}">
                        <option value="">—</option>
                        <template x-for="t in rfServices()" :key="t.key"><option :value="t.key" x-text="t.name + (t.sensitive ? ' (confidential)' : '')" :selected="rfKey === t.key"></option></template>
                        <option value="__other" :selected="rfKey === '__other'">Other (not in the list)</option>
                    </select>
                </div>
                <div x-show="rfKey === '__other'" x-cloak class="sm:col-span-2">
                    <label for="rf-other" class="{{ $rfLabel }}">Name of the service *</label>
                    <input id="rf-other" type="text" x-model="rfOther" maxlength="150" placeholder="e.g. Home visit, Case conference, Supplementary feeding" class="{{ $rfInput }}">
                </div>
                <div>
                    <label for="rf-kind" class="{{ $rfLabel }}">Kind</label>
                    <select id="rf-kind" x-model="rfKind" class="{{ $rfInput }}">
                        <template x-for="k in rfKinds" :key="k[0]"><option :value="k[0]" x-text="k[1]" :selected="rfKind === k[0]"></option></template>
                    </select>
                </div>
                <div>
                    <label for="rf-date" class="{{ $rfLabel }}">Date given *</label>
                    <input id="rf-date" type="date" x-model="rfDate" :max="rfToday()" class="{{ $rfInput }}">
                </div>
                <div>
                    <label for="rf-mode" class="{{ $rfLabel }}">Where</label>
                    <select id="rf-mode" x-model="rfMode" class="{{ $rfInput }}">
                        <template x-for="m in rfModes" :key="m[0]"><option :value="m[0]" x-text="m[1]" :selected="rfMode === m[0]"></option></template>
                    </select>
                </div>
                <div>
                    <label for="rf-source" class="{{ $rfLabel }}">Referred by</label>
                    <select id="rf-source" x-model="rfSource" class="{{ $rfInput }}">
                        <option value="">—</option>
                        <template x-for="s in rfSources" :key="s[0]"><option :value="s[0]" x-text="s[1]" :selected="rfSource === s[0]"></option></template>
                    </select>
                </div>
                <div>
                    <label for="rf-referred-to" class="{{ $rfLabel }}">Referred to (agency)</label>
                    <input id="rf-referred-to" type="text" x-model="rfReferredTo" maxlength="150" placeholder="e.g. DSWD Field Office V, PAO, RHU" class="{{ $rfInput }}">
                </div>
                <div>
                    <label for="rf-amount" class="{{ $rfLabel }}">Amount (₱, if any)</label>
                    <input id="rf-amount" type="number" min="0" step="0.01" x-model="rfAmount" class="{{ $rfInput }}">
                </div>
                <div class="sm:col-span-2">
                    <label for="rf-details" class="{{ $rfLabel }}">Details</label>
                    <textarea id="rf-details" x-model="rfDetails" rows="4" maxlength="2000" placeholder="What was done, and what follows. Keep it to what the record needs." class="{{ $rfInput }}"></textarea>
                    <p class="text-[10.5px] text-ink-faint text-right" x-text="rfDetails.length + ' / 2000'"></p>
                </div>
            </div>
            <label class="flex items-start gap-2 text-xs text-slate-700">
                <input type="checkbox" x-model="rfConfidential" :disabled="rfSensitive()" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200">
                <span><span class="font-semibold">Confidential</span> <span class="text-ink-muted" x-text="rfSensitive() ? '- always, for this service: shown only to officers of its office.' : '- shown only to officers of this office.'"></span></span>
            </label>
        </div>
        <x-slot:footer>
            <p class="mr-auto min-w-0 text-xs text-rose-600" role="alert" aria-live="assertive" x-text="rfError"></p>
            <x-ui.button variant="secondary" size="sm" @click="rfOpen = false">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" loading="rfBusy" @click="rfSave()">Save the record</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
