{{-- ============================================================
     "Apply for New Tulong" (welfare round, 2026-10-29): an officer files a
     case for a person at the office - a walk-in, or a referral from the
     police, the barangay or a school - or records assistance already given.
     Four steps: the person (GET /admin/people/search, or "Not in the list"),
     the service (grouped by category; the ones only officers file are marked
     Office only), the service's form (the citizen wizard's field types with
     their show-if rules), then the requirements seen at the office, the
     person's consent, and "Record assistance already given".
     POST /admin/tulong/requests, then the new case opens. A walk-in at a
     group session files through the same steps (the service is the
     session's): POST /admin/tulong/sessions/{ref}/participants/walk-in.
     Logic: tulongApply() in resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $apUi = [
        'label' => 'block text-xs font-semibold text-slate-700 mb-1',
        'input' => 'w-full rounded-2xl border-0 surface-pressed-sm py-2 px-3.5 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard',
    ];
@endphp
<div class="contents" x-data="tulongApply()" x-on:tulong-apply.window="openApply($event.detail || {})">
    <button type="button" x-show="apCanApply()" x-cloak @click="openApply()"
            title="File a Tulong case for a person at the office, or record assistance already given."
            class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-2xl px-3 py-2 clay-blue btn-shine hover:brightness-95 transition-colors shrink-0">
        <i data-lucide="file-plus-2" class="w-3.5 h-3.5"></i> Apply for New Tulong
    </button>

    <div x-data="{ get open() { return apOpen }, set open(v) { apOpen = v } }">
    <x-ui.modal title="Apply for New Tulong" maxWidth="xl">
        {{-- The four steps --}}
        <ol class="grid grid-cols-4 gap-2 mb-5" aria-label="Steps">
            <template x-for="(label, i) in apSteps" :key="i">
                <li class="flex flex-col items-center text-center gap-1" :aria-current="apStep === i + 1 ? 'step' : null">
                    <span class="w-7 h-7 rounded-full grid place-items-center text-[11px] font-bold transition-colors"
                          :class="apStep > i + 1 ? 'bg-emerald-500 text-white' : (apStep === i + 1 ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'bg-slate-100 text-ink-muted')"
                          x-text="apStep > i + 1 ? '✓' : i + 1"></span>
                    <span class="text-[11px] leading-tight" :class="apStep === i + 1 ? 'text-ink font-semibold' : 'text-ink-muted'" x-text="label"></span>
                </li>
            </template>
        </ol>
        <p x-show="apSessionRef" x-cloak class="mb-4 rounded-xl bg-indigo-50 border border-indigo-100 px-3 py-2 text-xs text-indigo-800">
            Walk-in participant for the session <span class="font-semibold" x-text="apSessionTitle || apSessionRef"></span>. The case is filed and added to the session.
        </p>

        <h4 id="ap-step-title" tabindex="-1" class="text-sm font-bold text-ink mb-3 outline-none" x-text="apStep === 3 ? (apType?.name || 'The form') : ['Who is the request for?', 'Which service?', '', 'Requirements and consent'][apStep - 1]"></h4>

        {{-- 1. Person --}}
        <section x-show="apStep === 1">
            <div x-show="pMode === 'search'">
                <label for="ap-person-q" class="{{ $apUi['label'] }}">Search residents and citizen accounts</label>
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="ap-person-q" type="search" x-model="pq" @input.debounce.300ms="pSearch()" autocomplete="off" placeholder="Name, RES- or ESP- number (at least 2 letters)" class="{{ $apUi['input'] }} pl-9">
                </div>
                <p class="sr-only" aria-live="polite" x-text="pLoading ? 'Searching' : (pSearched ? pResults.length + ' found' : '')"></p>
                <p x-show="pLoading" class="mt-2 text-xs text-ink-muted">Searching…</p>
                <p x-show="pFailed" x-cloak class="mt-2 text-xs text-rose-700" role="alert">The search did not answer. <button type="button" class="underline" @click="pSearch()">Try again</button></p>
                <p x-show="pSearched && !pLoading && !pResults.length" x-cloak class="mt-2 text-xs text-ink-muted">No one found. Check the spelling, or add the person with "Not in the list".</p>
                <ul x-show="pResults.length" x-cloak class="mt-2 max-h-64 overflow-y-auto space-y-1.5 pr-1">
                    <template x-for="p in pResults" :key="(p.individual_ref || '') + '|' + (p.account_no || '')">
                        <li>
                            <button type="button" @click="pPick(p)" :aria-pressed="person === p"
                                    class="w-full text-left rounded-xl border px-3 py-2 transition-colors"
                                    :class="person === p ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:border-brand-200 hover:bg-white/70'">
                                <span class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-sm font-semibold text-ink" x-text="p.name"></span>
                                    <span x-show="p.has_account || p.account_no" class="text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-emerald-50 text-emerald-700">Has an account</span>
                                    <span x-show="p.individual_ref" class="text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-slate-100 text-slate-600">Registry</span>
                                </span>
                                <span class="block text-[11px] text-ink-muted" x-text="pLine(p)"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <p x-show="person" x-cloak class="text-xs text-emerald-700 flex items-center gap-1.5"><i data-lucide="user-check" class="w-3.5 h-3.5"></i><span x-text="'Filing for ' + (person?.name || '')"></span></p>
                    <button type="button" @click="pMode = 'new'; person = null; $nextTick(() => document.getElementById('ap-new-name')?.focus())" class="text-xs font-semibold text-brand-700 hover:underline">Not in the list</button>
                </div>
            </div>
            <div x-show="pMode === 'new'" x-cloak class="space-y-3">
                <p class="text-xs text-ink-muted">A person the registry does not have yet. Add them to the registry so their record follows them; they stay unverified until an officer checks them.</p>
                <div>
                    <label for="ap-new-name" class="{{ $apUi['label'] }}">Full name *</label>
                    <input id="ap-new-name" type="text" x-model="pNew.name" maxlength="150" autocomplete="off" class="{{ $apUi['input'] }}">
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="ap-new-sex" class="{{ $apUi['label'] }}">Sex</label>
                        <select id="ap-new-sex" x-model="pNew.sex" class="{{ $apUi['input'] }}"><option value="">—</option><option value="Male">Male</option><option value="Female">Female</option></select>
                    </div>
                    <div>
                        <label for="ap-new-dob" class="{{ $apUi['label'] }}">Date of birth</label>
                        <input id="ap-new-dob" type="date" x-model="pNew.birth_date" :max="new Date().toISOString().slice(0, 10)" class="{{ $apUi['input'] }}">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="ap-new-brgy" class="{{ $apUi['label'] }}">Barangay *</label>
                        <select id="ap-new-brgy" x-model="pNew.barangay" class="{{ $apUi['input'] }}">
                            <option value="">—</option>
                            <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="pNew.barangay === b"></option></template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                    <div>
                        <label for="ap-new-contact" class="{{ $apUi['label'] }}">Mobile number</label>
                        <input id="ap-new-contact" type="tel" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX" x-model="pNew.contact" @input="pNew.contact = window.phMobile.clean($event.target.value)" class="{{ $apUi['input'] }}">
                    </div>
                    <label class="flex items-start gap-2 text-xs text-slate-700 pb-2">
                        <input type="checkbox" x-model="pNew.add_to_registry" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200">
                        <span>Add this person to the registry</span>
                    </label>
                </div>
                <button type="button" @click="pMode = 'search'" class="text-xs font-semibold text-brand-700 hover:underline">Back to the search</button>
            </div>
        </section>

        {{-- 2. Service --}}
        <section x-show="apStep === 2" x-cloak>
            <div class="relative mb-3">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="apSearch" aria-label="Search the services" placeholder="Search the services" class="{{ $apUi['input'] }} pl-9">
            </div>
            <p x-show="!apGroups().length" class="text-xs text-ink-muted">No service matches, or you may not file any.</p>
            <div class="space-y-4 max-h-[48vh] overflow-y-auto pr-1">
                <template x-for="g in apGroups()" :key="g.category">
                    <fieldset>
                        <legend class="text-[11px] font-bold uppercase tracking-[.07em] text-ink-muted mb-1.5" x-text="g.category"></legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="t in g.items" :key="t.key">
                                <label class="flex items-start gap-2.5 rounded-xl border px-3 py-2 cursor-pointer transition-colors" :class="apKey === t.key ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:border-brand-200'">
                                    <input type="radio" name="ap-service" class="mt-0.5 text-brand-600 focus:ring-brand-200" :checked="apKey === t.key" @change="apChoose(t.key)">
                                    <span class="min-w-0">
                                        <span class="block text-xs font-semibold text-ink leading-snug" x-text="t.name"></span>
                                        <span class="flex flex-wrap gap-1 mt-0.5">
                                            <span class="text-[10px] text-ink-muted" x-text="t.office_short"></span>
                                            <span x-show="t.staffOnly" class="text-[10px] font-semibold rounded-full px-1.5 bg-amber-50 text-amber-700">Office only</span>
                                            <span x-show="t.sensitive" class="text-[10px] font-semibold rounded-full px-1.5 bg-rose-50 text-rose-700">Confidential</span>
                                        </span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                </template>
            </div>
        </section>

        {{-- 3. The service's form --}}
        <section x-show="apStep === 3" x-cloak>
            <p class="text-xs text-ink-muted mb-3 -mt-2" x-text="'For ' + pName() + (apType?.sessionCouple ? '. Both partners attend; the certificate names both.' : '.')"></p>
            <div x-show="!(apType?.fields || []).length">
                <label for="ap-purpose" class="{{ $apUi['label'] }}">Details of the request</label>
                <textarea id="ap-purpose" x-model="apForm.purpose" rows="3" maxlength="2000" class="{{ $apUi['input'] }}"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="f in (apType?.fields || [])" :key="f.key">
                    <div x-show="apShown(f)" :class="['textarea', 'checkbox-group', 'multiselect', 'table'].includes(f.type) ? 'sm:col-span-2' : ''">
                        <template x-if="['text', 'number', 'date', 'tel'].includes(f.type)">
                            <div>
                                <label :for="'ap-f-' + f.key" class="{{ $apUi['label'] }}" x-text="f.label + (f.required ? ' *' : '')"></label>
                                <input :id="'ap-f-' + f.key" :type="f.type" x-model="apForm[f.key]" :inputmode="f.type === 'tel' ? 'numeric' : null" :maxlength="f.type === 'tel' ? 11 : null" :placeholder="f.type === 'tel' ? '09XXXXXXXXX' : null"
                                       @input="if (f.type === 'tel') apForm[f.key] = window.phMobile.clean($event.target.value)" class="{{ $apUi['input'] }}">
                            </div>
                        </template>
                        <template x-if="f.type === 'textarea'">
                            <div>
                                <label :for="'ap-f-' + f.key" class="{{ $apUi['label'] }}" x-text="f.label + (f.required ? ' *' : '')"></label>
                                <textarea :id="'ap-f-' + f.key" x-model="apForm[f.key]" rows="3" class="{{ $apUi['input'] }}"></textarea>
                            </div>
                        </template>
                        <template x-if="f.type === 'select'">
                            <div>
                                <label :for="'ap-f-' + f.key" class="{{ $apUi['label'] }}" x-text="f.label + (f.required ? ' *' : '')"></label>
                                <select :id="'ap-f-' + f.key" x-model="apForm[f.key]" class="{{ $apUi['input'] }}">
                                    <option value="">—</option>
                                    <template x-for="opt in (f.options || [])" :key="opt"><option :value="opt" x-text="opt" :selected="apForm[f.key] === opt"></option></template>
                                </select>
                                <input x-show="f.allowOther && /^others?$/i.test(apForm[f.key] || '')" type="text" x-model="apForm[f.key + '_other']" maxlength="120" :aria-label="f.label + ': please specify'" placeholder="Please specify" class="{{ $apUi['input'] }} mt-2">
                            </div>
                        </template>
                        <template x-if="f.type === 'checkbox'">
                            <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 cursor-pointer">
                                <input type="checkbox" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200" :checked="apForm[f.key] === true || apForm[f.key] === 'Yes'" @change="apForm[f.key] = $event.target.checked ? 'Yes' : ''">
                                <span x-text="f.label + (f.required ? ' *' : '')"></span>
                            </label>
                        </template>
                        <template x-if="f.type === 'checkbox-group' || f.type === 'multiselect'">
                            <fieldset>
                                <legend class="{{ $apUi['label'] }}" x-text="f.label + (f.required ? ' *' : '')"></legend>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                                    <template x-for="opt in (f.options || [])" :key="opt">
                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 cursor-pointer" :class="(apForm[f.key] || []).includes(opt) && 'border-brand-300 bg-brand-50/60 text-brand-700'">
                                            <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-200" :checked="(apForm[f.key] || []).includes(opt)" @change="apToggle(f.key, opt)">
                                            <span x-text="opt"></span>
                                        </label>
                                    </template>
                                </div>
                            </fieldset>
                        </template>
                        <template x-if="f.type === 'derived_age'">
                            <div>
                                <p class="{{ $apUi['label'] }}" x-text="f.label"></p>
                                <p class="text-sm text-ink-muted py-2" x-text="apAge(f) !== null ? apAge(f) + ' years old' : 'Set the birth date first'"></p>
                            </div>
                        </template>
                        <template x-if="f.type === 'table'">
                            <div>
                                <p class="{{ $apUi['label'] }}" x-text="f.label"></p>
                                <div class="space-y-2">
                                    <template x-for="(row, ri) in (apForm[f.key] || [])" :key="ri">
                                        <div class="rounded-xl border border-slate-200 p-2.5 grid grid-cols-2 sm:grid-cols-3 gap-2 relative">
                                            <template x-for="col in (f.columns || [])" :key="col.key">
                                                <div>
                                                    <label :for="'ap-t-' + f.key + ri + col.key" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-0.5" x-text="col.label"></label>
                                                    <template x-if="col.options">
                                                        <select :id="'ap-t-' + f.key + ri + col.key" x-model="row[col.key]" class="{{ $apUi['input'] }} !text-xs !py-1.5">
                                                            <option value="">—</option>
                                                            <template x-for="opt in col.options" :key="opt"><option :value="opt" x-text="opt" :selected="row[col.key] === opt"></option></template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!col.options">
                                                        <input :id="'ap-t-' + f.key + ri + col.key" :type="col.type === 'date' ? 'date' : (col.type === 'number' ? 'number' : 'text')" x-model="row[col.key]" class="{{ $apUi['input'] }} !text-xs !py-1.5">
                                                    </template>
                                                </div>
                                            </template>
                                            <button type="button" @click="apForm[f.key].splice(ri, 1)" :aria-label="'Remove row ' + (ri + 1)" class="absolute top-1.5 right-1.5 text-ink-faint hover:text-rose-600"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                        </div>
                                    </template>
                                    <button type="button" @click="apAddRow(f)" class="w-full rounded-xl border-2 border-dashed border-slate-200 py-2 text-xs font-medium text-ink-muted hover:text-brand-700 hover:border-brand-300" x-text="'Add ' + (f.rowLabel || 'a row')"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </section>

        {{-- 4. Requirements, consent, and assistance already given --}}
        <section x-show="apStep === 4" x-cloak class="space-y-4">
            <fieldset>
                <legend class="text-sm font-bold text-ink mb-2">How did the request come in?</legend>
                <div class="flex flex-wrap gap-4 text-xs text-slate-700">
                    <label class="flex items-center gap-1.5"><input type="radio" name="ap-channel" value="walk_in" x-model="apChannel" class="text-brand-600 focus:ring-brand-200"> Walk-in at the office</label>
                    <label class="flex items-center gap-1.5"><input type="radio" name="ap-channel" value="referral" x-model="apChannel" class="text-brand-600 focus:ring-brand-200"> Referral</label>
                </div>
                <div x-show="apChannel === 'referral'" x-cloak class="mt-2">
                    <label for="ap-referred-by" class="{{ $apUi['label'] }}">Referred by *</label>
                    <input id="ap-referred-by" type="text" x-model="apReferredBy" maxlength="150" placeholder="e.g. PNP Esperanza, Brgy. Poblacion BCPC, a school" class="{{ $apUi['input'] }}">
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold text-ink mb-1">Requirements</legend>
                <p class="text-xs text-ink-muted mb-2" x-show="apShownReqs().length">Tick each document the person showed at the office. It is accepted with the note "Original presented at the office"; the rest can still be attached to the case later.</p>
                <p class="text-xs text-ink-muted" x-show="!apShownReqs().length">This service asks for no documents.</p>
                <div class="space-y-1.5">
                    <template x-for="r in apShownReqs()" :key="r.key">
                        <label class="flex items-start gap-2 rounded-xl border px-3 py-2 text-xs cursor-pointer transition-colors" :class="r.presented ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200'">
                            <input type="checkbox" x-model="r.presented" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-200">
                            <span class="min-w-0"><span class="text-slate-700" x-text="r.label"></span> <span class="text-ink-muted">· Presented at the office</span></span>
                        </label>
                    </template>
                </div>
            </fieldset>

            <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-3">
                <p class="text-xs text-slate-700 leading-relaxed">Read to the person: "The Municipality of Esperanza collects your personal information only to process this request and to keep the record of the service given, under the Data Privacy Act of 2012 (RA 10173). It is kept by the office handling your request and shared only as the law allows."</p>
                <label class="mt-2 flex items-start gap-2 text-xs font-semibold text-ink">
                    <input type="checkbox" x-model="apConsent" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200">
                    <span>The person heard this and agrees *</span>
                </label>
            </div>

            <div x-show="apMayRecord()" x-cloak class="rounded-xl border border-slate-200 p-3">
                <label class="flex items-start gap-2 text-xs font-semibold text-ink">
                    <input type="checkbox" x-model="apRecordOnly" @change="if (apRecordOnly && !apRecord.released_to) apRecord.released_to = pName()" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200">
                    <span>Record assistance already given <span class="font-normal text-ink-muted">(the case is filed as Completed)</span></span>
                </label>
                <div x-show="apRecordOnly" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                    <div>
                        <label for="ap-ro-date" class="{{ $apUi['label'] }}">Given on *</label>
                        <input id="ap-ro-date" type="date" x-model="apRecord.completed_on" :max="new Date().toISOString().slice(0, 10)" class="{{ $apUi['input'] }}">
                    </div>
                    <div x-show="apShowsAmount()">
                        <label for="ap-ro-amount" class="{{ $apUi['label'] }}">Amount (₱)</label>
                        <input id="ap-ro-amount" type="number" min="0" step="0.01" x-model="apRecord.amount" class="{{ $apUi['input'] }}">
                    </div>
                    <div>
                        <label for="ap-ro-to" class="{{ $apUi['label'] }}">Released to *</label>
                        <input id="ap-ro-to" type="text" x-model="apRecord.released_to" maxlength="150" class="{{ $apUi['input'] }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="ap-ro-note" class="{{ $apUi['label'] }}">Note</label>
                        <input id="ap-ro-note" type="text" x-model="apRecord.note" maxlength="500" class="{{ $apUi['input'] }}">
                    </div>
                </div>
            </div>
        </section>

        <x-slot:footer>
            <p class="mr-auto min-w-0 text-xs text-rose-600" role="alert" aria-live="assertive" x-text="apError"></p>
            <x-ui.button variant="secondary" size="sm" @click="apStep > 1 ? apBack() : (apOpen = false)"><span x-text="apStep > 1 ? 'Back' : 'Cancel'"></span></x-ui.button>
            <x-ui.button size="sm" x-show="apStep < 4" @click="apNext()">Continue</x-ui.button>
            <x-ui.button size="sm" icon="send" x-show="apStep === 4" loading="apBusy" @click="apSubmit()"><span x-text="apRecordOnly ? 'Save the record' : (apSessionRef ? 'File and add to the session' : 'File the request')"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
