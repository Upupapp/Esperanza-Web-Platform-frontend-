{{-- tab-missing.blade.php — Casualties (client request, 2026-11-14), the Missing
     Persons tab (owner, 2026-10-30) grown into the casualty record: the dead, the
     injured and the missing, including people who are not from Esperanza and
     residents hurt or killed outside it. Logic: resources/js/sakuna-casualties.js
     (x-data="sakunaCasualties"). Written by the MDRRMO and the Super Administrator
     (sakuna_missing create/edit/close); a closed case is kept, never deleted. --}}

<div class="space-y-3" x-data="sakunaCasualties" @sakuna-missing-reload.window="load(page); loadSummary()">

    <div x-show="off" x-cloak class="sk-card sk-empty" style="padding: 24px;">
        Casualties is not switched on here yet. It holds personal details of people in a disaster, so it opens once the LGU's Data Protection Officer agrees.
    </div>

    <div x-show="!off" class="space-y-3">
        {{-- Figures as the NDRRMC situation report counts them: people, not cases. --}}
        <div class="sk-pagebar">
            <div class="sk-stats">
                <div class="sk-stat"><span class="sk-slab">Dead</span><span class="sk-sval tabular-nums" x-text="cat('dead')?.total ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab">Injured</span><span class="sk-sval tabular-nums" x-text="cat('injured')?.total ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="cat('missing')?.total"></span>Still missing</span><span class="sk-sval tabular-nums" x-text="cat('missing')?.total ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab">Found safe</span><span class="sk-sval tabular-nums" x-text="summary?.casualties?.safe ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="summary?.for_validation"></span>For validation</span><span class="sk-sval tabular-nums" x-text="summary?.for_validation ?? '—'"></span></div>
            </div>
            <div class="sk-actions">
                <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About Casualties" title="The dead, the injured and the missing in a disaster, as the NDRRMC situation report counts them: residents and people from other towns, in Esperanza or outside it. A resolved case is closed and kept.">
                    <i data-lucide="info"></i>
                </button>
                <div class="sk-btngrp" x-show="can('sakuna_missing','edit')" role="group" aria-label="Casualty list">
                    <button type="button" class="sk-btn" @click="exportList('csv')" title="The casualty list in the NDRRMC name list's columns"><i data-lucide="download"></i> Casualty list (CSV)</button>
                    <button type="button" class="sk-btn" @click="exportList('pdf')" aria-label="Casualty list as PDF">PDF</button>
                </div>
                <button type="button" class="sk-btn sk-btn-p disabled:opacity-50" x-show="can('sakuna_missing','create')" @click="startAdd()" :disabled="onLastCopy" :title="onLastCopy ? 'Waits for the connection' : ''">
                    <i data-lucide="user-plus"></i> Record a person
                </button>
            </div>
        </div>

        <section class="sk-card overflow-hidden">
            <h2 class="sr-only">Dead, injured and missing persons</h2>
            {{-- One look per category, like the Reports tab's sections. --}}
            <div class="sk-toolbar" style="border-bottom: 0; padding-bottom: 0;">
                <div class="sk-segwrap" role="group" aria-label="Category">
                    <template x-for="c in categories" :key="c[0]">
                        <button type="button" class="sk-seg" :class="f.category === c[0] && 'sk-seg-on'" :aria-pressed="f.category === c[0]" @click="setCategory(c[0])" x-text="c[1]"></button>
                    </template>
                </div>
            </div>
            <div class="sk-toolbar">
                <div class="sk-search">
                    <label for="mp-q" class="sr-only">Search name or case number</label>
                    <i data-lucide="search"></i>
                    <input id="mp-q" type="search" x-model="f.q" @input.debounce.400ms="load(1)" placeholder="Name or case number…">
                </div>
                <select aria-label="Lives in" x-model="f.lives" @change="load(1)" class="sk-sel">
                    <option value="">Lives anywhere</option><option value="esperanza">Lives in Esperanza</option><option value="outside">Not from Esperanza</option><option value="unknown">Home not known</option>
                </select>
                <select aria-label="Where it happened" x-model="f.happened" @change="load(1)" class="sk-sel">
                    <option value="">Happened anywhere</option><option value="esperanza">In Esperanza</option><option value="outside">Outside Esperanza</option>
                </select>
                <select aria-label="Validation" x-model="f.validation" @change="load(1)" class="sk-sel">
                    <option value="">Validated or not</option><option>For validation</option><option>Validated</option>
                </select>
                <button type="button" class="sk-clear" @click="more = !more" :aria-expanded="more" x-text="more ? 'Fewer filters' : 'More filters'"></button>
                <button type="button" @click="clear()" class="sk-clear">Clear</button>
                <span class="sk-spacer"></span>
                <span class="sk-csub" x-show="!loading && !failed" x-text="total + (total === 1 ? ' person' : ' people')"></span>
            </div>
            <div class="sk-toolbar" x-show="more" x-cloak>
                <select aria-label="Status" x-model="f.status" @change="load(1)" class="sk-sel">
                    <option value="">All statuses</option><option value="active">Still missing or unidentified</option><option value="found">Found</option>
                    <option>Missing</option><option>Found safe</option><option>Found injured</option><option>Found deceased</option>
                    <option>Injured</option><option>Died of injuries</option><option>Deceased</option><option>Unidentified</option><option>Identified</option><option>Closed</option>
                </select>
                <select aria-label="Barangay" x-model="f.barangay" @change="filtered()" class="sk-sel">
                    <option value="">All barangays</option>
                    @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
                </select>
                <div class="sk-search" style="flex: 0 1 150px; min-width: 120px;">
                    <label for="mp-incident" class="sr-only">Incident number</label>
                    <i data-lucide="hash"></i>
                    <input id="mp-incident" type="text" x-model="f.incident" @change="filtered()" placeholder="INC-2026-…">
                </div>
                <label class="sk-chk">Recorded from
                    <input type="date" x-model="f.from" @change="filtered()" class="sk-sel" style="width: auto; height: 32px; accent-color: auto;">
                </label>
                <label class="sk-chk">to
                    <input type="date" x-model="f.to" :min="f.from" @change="filtered()" class="sk-sel" style="width: auto; height: 32px; accent-color: auto;">
                </label>
            </div>

            <div x-show="failed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load the casualty records. ' + offlineWhy" retryExpr="load(page)" :retryable="true" /></div>
            <div x-show="!loading && !failed && rows.length === 0" x-cloak class="sk-empty">No one matches these filters.</div>

            <div x-show="rows.length" class="sk-scroll" tabindex="0" role="region" aria-label="Casualties table (scrolls sideways)">
                <table class="sk-tbl sk-tbl-act">
                    <thead>
                        <tr>
                            <th scope="col">Case</th>
                            <th scope="col">Person</th>
                            <th scope="col">Category</th>
                            <th scope="col">Age and sex</th>
                            <th scope="col">Where it happened</th>
                            <th scope="col">Cause</th>
                            <th scope="col">Validation</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in rows" :key="r.ref">
                            <tr @click="show(r.ref)" @keydown.enter.prevent="show(r.ref)" tabindex="0">
                                <td class="whitespace-nowrap"><span class="sk-mono block" x-text="r.ref"></span><span class="sk-sub block" x-text="r.incident ? r.incident.ref : 'No incident'"></span></td>
                                <td><span class="sk-b block" x-text="personName(r)"></span><span class="sk-sub block" x-text="livesLine(r)"></span></td>
                                <td class="whitespace-nowrap"><span class="sk-pill" :class="categoryTone[r.category]" x-text="categoryLabel[r.category] || r.category"></span><span class="sk-sub block" x-show="statusDiffers(r)" x-text="r.status"></span></td>
                                <td class="whitespace-nowrap" x-text="ageSex(r)"></td>
                                <td x-text="placeLine(r)"></td>
                                <td x-text="r.cause || '—'"></td>
                                <td><span class="sk-pill" :class="r.validation === 'Validated' ? 'sk-p-teal' : 'sk-p-slate'" x-text="r.validation"></span></td>
                                <td class="whitespace-nowrap" @click.stop>
                                    <button type="button" @click="show(r.ref)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + r.ref"><i data-lucide="eye"></i> View</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="lastPage > 1" class="sk-tfoot flex items-center justify-end gap-2">
                <button type="button" :disabled="page <= 1" @click="load(page - 1)" class="sk-btn sk-btn-sm">Previous</button>
                <span x-text="'Page ' + page + ' of ' + lastPage + ' · ' + total + ' people'"></span>
                <button type="button" :disabled="page >= lastPage" @click="load(page + 1)" class="sk-btn sk-btn-sm">Next</button>
            </div>
        </section>

        {{-- One person: a window like every other tab's (owner, 2026-11-04). --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
            @click.self="open = null" @keydown.escape.window="open = null">
        <section x-trap="open" role="dialog" aria-modal="true" aria-label="Person details" class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto overflow-hidden">
            <div class="sk-chead" style="padding: 16px 24px;">
                <div>
                    <p class="sk-csub" x-text="open ? open.ref + ' · ' + kinds[open.kind] : ''"></p>
                    <div class="flex flex-wrap items-center gap-2 mt-0.5">
                        <h2 class="sk-ctitle" style="font-size: 15px;" x-text="open ? personName(open) : ''"></h2>
                        <span class="sk-pill" :class="open ? categoryTone[open.category] : ''" x-text="open ? (categoryLabel[open.category] || open.category) : ''"></span>
                        <span class="sk-pill" :class="open && open.validation === 'Validated' ? 'sk-p-teal' : 'sk-p-slate'" x-text="open ? open.validation : ''"></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="sk-btn sk-btn-sm" x-show="open && open.status !== 'Closed' && can('sakuna_missing','edit')" @click="startEdit()"><i data-lucide="pencil"></i> Edit details</button>
                    <button type="button" aria-label="Close window" @click="open = null" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-1.5">Person</p>
                    <dl class="grid gap-3 sm:grid-cols-2 text-[12.5px]">
                        <div><dt class="sk-ssub">Lives in</dt><dd x-text="open ? (open.lives.label || livesLine(open)) : ''"></dd></div>
                        <div><dt class="sk-ssub">Registry record</dt><dd x-text="open?.registry_ref || 'Not in the registry'"></dd></div>
                        <div><dt class="sk-ssub">Sex and age</dt><dd x-text="open ? ageSex(open) : ''"></dd></div>
                        <div x-show="!open?.restricted"><dt class="sk-ssub">Next of kin</dt><dd x-text="open ? ([open.kin_name, open.kin_relation].filter(Boolean).join(', ') + (open.kin_contact ? ' · ' + open.kin_contact : '') || '—') : ''"></dd></div>
                        <div class="sm:col-span-2" x-show="open?.description"><dt class="sk-ssub">Identifying description (internal)</dt><dd x-text="open?.description || '—'"></dd></div>
                    </dl>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-1.5">What happened</p>
                    <dl class="grid gap-3 sm:grid-cols-2 text-[12.5px]">
                        <div><dt class="sk-ssub">Where</dt><dd x-text="open ? placeLine(open, true) : ''"></dd></div>
                        <div><dt class="sk-ssub">When</dt><dd x-text="open ? when(whenOf(open)) : ''"></dd></div>
                        <div><dt class="sk-ssub">Cause</dt><dd x-text="open?.cause || '—'"></dd></div>
                        <div><dt class="sk-ssub">Incident</dt><dd x-text="open?.incident ? open.incident.ref + ' · ' + open.incident.title : 'None (happened outside Esperanza)'"></dd></div>
                        <template x-if="open && open.category === 'injured' && !open.restricted">
                            <div class="sm:col-span-2"><dt class="sk-ssub">Injury and treatment</dt><dd x-text="[open.injury, open.treatment].filter(Boolean).join(' · ') || '—'"></dd></div>
                        </template>
                        <template x-if="open && open.category === 'dead' && !open.restricted">
                            <div class="sm:col-span-2"><dt class="sk-ssub">Remains</dt><dd x-text="[open.remains_ref ? 'Body ref. ' + open.remains_ref : null, open.remains_location, open.released_to ? 'Released to ' + open.released_to + (open.released_at ? ' on ' + when(open.released_at) : '') : null, open.death_registry_no ? 'Death registry no. ' + open.death_registry_no : null].filter(Boolean).join(' · ') || '—'"></dd></div>
                        </template>
                        <template x-if="open && open.kind === 'missing'">
                            <div class="sm:col-span-2"><dt class="sk-ssub" x-text="open.found_place || open.found_at ? 'Last seen, and found' : 'Last seen'"></dt><dd x-text="(open.last_seen_place || '—') + (open.found_place ? ' · found at ' + open.found_place : '') + (open.found_at ? ' · ' + when(open.found_at) : '')"></dd></div>
                        </template>
                    </dl>
                </div>

                {{-- Validation, as the situation report shows it; allowed on a closed case too. --}}
                <div class="rounded-lg border border-[#E3E8F1] p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-800">Validation</p>
                        <span class="text-xs sk-muted" x-text="open ? 'Source of data: ' + (open.source || 'not given') : ''"></span>
                    </div>
                    <p class="text-xs" x-show="open && open.validation === 'Validated'" x-text="open ? 'Validated ' + when(open.validated_at) + (open.validated_by ? ' by ' + open.validated_by : '') + (open.validation_note ? ' · ' + open.validation_note : '') : ''"></p>
                    <p class="text-xs sk-muted" x-show="open && open.validation !== 'Validated'" x-text="open ? validationHelp(open) : ''"></p>
                    <div class="flex flex-wrap items-center gap-2" x-show="can('sakuna_missing','edit')">
                        <label class="sr-only" for="mp-valid-note">Validation note</label>
                        <input id="mp-valid-note" type="text" x-model="validNote" maxlength="255" :placeholder="open ? validationExample(open) : ''" class="sk-sel flex-1" style="min-width: 200px;" x-show="open && open.validation !== 'Validated'">
                        <button type="button" class="sk-btn sk-btn-sm sk-btn-p" x-show="open && open.validation !== 'Validated'" :disabled="saving" @click="setValidation('Validated')"><i data-lucide="badge-check"></i> Mark validated</button>
                        <button type="button" class="sk-btn sk-btn-sm" x-show="open && open.validation === 'Validated'" :disabled="saving" @click="setValidation('For validation')">Back to For validation</button>
                    </div>
                </div>

                <div x-show="open && open.next_statuses.length && can('sakuna_missing','edit')" class="rounded-lg bg-[#F8FAFD] border border-[#EEF2F7] p-3 space-y-2">
                    <p class="text-xs font-semibold text-slate-800">Update the case</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="s in (open ? open.next_statuses : [])" :key="s">
                            <button type="button" @click="move.status = s" :aria-pressed="move.status === s"
                                    x-show="s !== 'Closed' || can('sakuna_missing','close')"
                                    class="sk-btn sk-btn-sm" :class="move.status === s ? 'sk-btn-soft' : ''" x-text="s === 'Closed' ? 'Close the case' : s"></button>
                        </template>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2" x-show="move.status">
                        <input type="text" x-show="move.status.startsWith('Found')" x-model="move.found_place" placeholder="Where found" aria-label="Where found" class="sk-sel w-full">
                        <input type="text" x-model="move.note" placeholder="Note for the history" aria-label="Note for the history" class="sk-sel w-full">
                    </div>
                    <button type="button" class="sk-btn sk-btn-sm sk-btn-p" x-show="move.status" :disabled="saving" @click="moveTo()"><i data-lucide="check"></i><span x-text="saving ? 'Saving…' : (move.status === 'Closed' ? 'Close the case' : 'Mark as ' + move.status)"></span></button>
                </div>

                {{-- Balita (owner, 2026-10-30): never for an injured person, and only
                     with the reporting party's written consent (PNP MC 2016-033). --}}
                <div class="rounded-lg border border-[#E3E8F1] p-3 space-y-2" x-show="open && open.kind !== 'injured'">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-800">Balita posts</p>
                        <button type="button" class="sk-btn sk-btn-sm" x-show="open && open.post_consent && open.status !== 'Closed' && can('sakuna_missing','edit') && can('comm_news','create')" :disabled="saving" @click="draftPost('{{ route('admin.communications') }}')"><i data-lucide="newspaper"></i> Prepare a Balita post</button>
                    </div>
                    <p class="text-xs sk-muted">Nothing is posted by itself. A draft holds only what may be made public (no description, address or contact details) and needs approval by the Information Officer, the Super Admin or the MDRRMO Head.</p>
                    <p class="text-xs" x-show="open && open.post_consent" x-text="open ? 'Written consent to post recorded ' + when(open.post_consent_at) + (open.post_consent_by ? ', given by ' + open.post_consent_by : '') + '.' : ''"></p>
                    <div class="flex flex-wrap items-center gap-2" x-show="open && !open.post_consent && open.status !== 'Closed' && can('sakuna_missing','edit')">
                        <label class="sr-only" for="mp-consent">Who gave written consent</label>
                        <input id="mp-consent" type="text" x-model="consentBy" maxlength="160" placeholder="Who gave written consent (e.g. the mother)" class="sk-sel flex-1" style="min-width: 220px;">
                        <button type="button" class="sk-btn sk-btn-sm" :disabled="saving || !consentBy.trim()" @click="giveConsent()"><i data-lucide="file-signature"></i> Record written consent</button>
                    </div>
                    <ul class="space-y-1 text-xs">
                        <template x-for="a in (open ? (open.announcements || []) : [])" :key="a.id">
                            <li class="flex flex-wrap gap-x-2">
                                <a :href="'{{ route('admin.communications') }}?tab=balita&post=' + a.id" class="sk-link" style="font-size: 12px;" x-text="a.title"></a>
                                <span class="sk-muted" x-text="a.status"></span>
                                <span class="sk-muted" x-show="a.published" x-text="a.published ? 'published ' + when(a.published.released_at) + (a.published.released_by ? ' by ' + a.published.released_by : '') : ''"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-800 mb-1.5">History</p>
                    <ol class="text-xs">
                        <template x-for="(h, i) in (open ? open.history : [])" :key="i">
                            <li class="flex flex-wrap gap-x-2 py-1.5 border-b border-[#EEF2F7] last:border-b-0"><span class="sk-muted" x-text="when(h.at)"></span><span class="font-semibold" x-text="h.verb === 'consent' ? 'Written consent to post recorded' : (h.to ? (h.from ? h.from + ' → ' + h.to : h.to) : 'Details updated')"></span><span class="sk-muted" x-text="h.by ? 'by ' + h.by : ''"></span><span x-text="h.note || ''"></span></li>
                        </template>
                    </ol>
                </div>
            </div>
        </section>
        </div>

        {{-- Record (or correct) a person: four plain questions, top to bottom. --}}
        <div x-show="adding" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
            @click.self="adding = false" @keydown.escape.window="adding = false">
        <section x-trap="adding" role="dialog" aria-modal="true" :aria-label="editing ? 'Edit details' : 'Record a person'" class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto overflow-hidden">
            <div class="sk-chead" style="padding: 16px 24px;"><h2 class="sk-ctitle" style="font-size: 15px;" x-text="editing ? 'Edit details · ' + editing : 'Record a person'"></h2><button type="button" aria-label="Close window" @click="adding = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <div class="px-6 py-4 space-y-5">

                {{-- 1. What happened to the person --}}
                <fieldset x-show="!editing">
                    <legend class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2">1 · What happened to the person</legend>
                    <div class="grid gap-2 grid-cols-2 sm:grid-cols-4">
                        <template x-for="k in kindChoices" :key="k[0]">
                            <button type="button" @click="form.kind = k[0]" :aria-pressed="form.kind === k[0]"
                                    class="flex flex-col justify-start text-left rounded-2xl border px-3 py-2 transition-colors" :class="form.kind === k[0] ? 'border-brand-600 bg-brand-50' : 'border-[#E3E8F1] bg-white hover:bg-slate-50'">
                                <span class="block text-[13px] font-semibold text-ink" x-text="k[1]"></span>
                                <span class="block text-[11.5px] text-ink-muted" x-text="k[2]"></span>
                            </button>
                        </template>
                    </div>
                </fieldset>

                {{-- 2. Who --}}
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2">2 · Who</legend>
                    <div class="sk-segwrap mb-3" role="group" aria-label="Where the person lives">
                        <button type="button" class="sk-seg" :class="form.lives === 'esperanza' && 'sk-seg-on'" :aria-pressed="form.lives === 'esperanza'" @click="form.lives = 'esperanza'">Lives in Esperanza</button>
                        <button type="button" class="sk-seg" :class="form.lives === 'outside' && 'sk-seg-on'" :aria-pressed="form.lives === 'outside'" @click="form.lives = 'outside'; unpickResident()">Not from Esperanza</button>
                        <button type="button" class="sk-seg" :class="form.lives === 'unknown' && 'sk-seg-on'" :aria-pressed="form.lives === 'unknown'" @click="form.lives = 'unknown'; unpickResident()">Not known yet</button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2" x-show="form.lives === 'esperanza' && form.kind !== 'unidentified' && can('const_resident','view')">
                            <label class="text-xs text-[#4B5568]" for="mp-resident">Find the resident in the registry (optional)</label>
                            <div class="flex items-center gap-2 mt-1" x-show="form.residentLabel">
                                <span class="sk-pill sk-p-blue" x-text="form.residentLabel"></span>
                                <button type="button" class="sk-clear" @click="unpickResident()" x-show="!editing">Change</button>
                            </div>
                            <input id="mp-resident" type="search" x-show="!form.residentLabel" x-model="residentQuery" @input.debounce.400ms="findResident()" placeholder="Name or RES number" class="sk-sel w-full mt-1">
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <template x-for="p in residents" :key="p.ref">
                                    <button type="button" @click="pickResident(p)" class="sk-btn sk-btn-sm" x-text="p.name + ' · ' + p.barangay"></button>
                                </template>
                            </div>
                            <p class="text-xs sk-muted mt-1" x-show="form.individual_ref">The name, sex, age and barangay come from the registry record.</p>
                        </div>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1 sm:col-span-2" x-show="!form.individual_ref && form.kind !== 'unidentified'">Full name <span class="sr-only">(required)</span>
                            <input type="text" x-model="form.name" maxlength="160" placeholder="First name, middle name, surname" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Sex
                            <select x-model="form.sex" class="sk-sel w-full"><option value="">—</option><option>Male</option><option>Female</option></select>
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1"><span x-text="form.kind === 'unidentified' ? 'Estimated age' : 'Age'"></span>
                            <input type="number" min="0" max="130" x-model="form.age" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.lives === 'esperanza' && !form.individual_ref">Barangay
                            <select x-model="form.barangay" class="sk-sel w-full">
                                <option value="">Choose the barangay</option>
                                @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.lives !== 'unknown'" :class="form.lives === 'outside' && 'sm:col-span-2'"><span x-text="form.lives === 'outside' ? 'Street and barangay' : 'Purok, sitio or street'"></span>
                            <input type="text" x-model="form.home_address" maxlength="255" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.lives === 'outside'"><span>Town or city <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <input type="text" x-model="form.home_town" maxlength="120" placeholder="e.g. Bulan" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.lives === 'outside'">Province
                            <input type="text" x-model="form.home_province" maxlength="120" placeholder="e.g. Sorsogon" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1 sm:col-span-2" x-show="form.kind === 'unidentified' || form.kind === 'missing'">Identifying description (internal, not for publication)
                            <textarea x-model="form.description" rows="2" maxlength="2000" placeholder="Clothing, marks, height…" class="w-full text-[12.5px] border border-[#E3E8F1] rounded-lg px-2 py-1.5"></textarea>
                        </label>
                    </div>
                </fieldset>

                {{-- 3. Where and when --}}
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2">3 · Where and when</legend>
                    <div class="sk-segwrap mb-3" role="group" aria-label="Where it happened">
                        <button type="button" class="sk-seg" :class="form.place_scope === 'esperanza' && 'sk-seg-on'" :aria-pressed="form.place_scope === 'esperanza'" @click="form.place_scope = 'esperanza'">In Esperanza</button>
                        <button type="button" class="sk-seg" :class="form.place_scope === 'outside' && 'sk-seg-on'" :aria-pressed="form.place_scope === 'outside'" @click="form.place_scope = 'outside'">Outside Esperanza</button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1 sm:col-span-2" x-show="!editing"><span x-text="form.place_scope === 'esperanza' ? 'Incident' : 'Incident (optional: only if it belongs to one)'"></span>
                            <select x-model="form.incident" @change="incidentChosen()" class="sk-sel w-full">
                                <option value="" x-text="form.place_scope === 'esperanza' ? 'Choose the incident' : 'None'"></option>
                                <template x-for="i in incidents" :key="i.ref"><option :value="i.ref" x-text="i.ref + ' · ' + i.type + ' · ' + i.barangay"></option></template>
                            </select>
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.place_scope === 'esperanza'">Barangay where it happened
                            <select x-model="form.place_barangay" class="sk-sel w-full">
                                <option value="">Same as the incident</option>
                                @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.place_scope === 'outside'"><span>Town or city <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <input type="text" x-model="form.place_town" maxlength="120" placeholder="e.g. Masbate City" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.place_scope === 'outside'">Province
                            <input type="text" x-model="form.place_province" maxlength="120" placeholder="e.g. Masbate" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.kind !== 'missing'">Exact place
                            <input type="text" x-model="form.place_detail" maxlength="255" placeholder="Sitio, landmark, road…" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.kind !== 'missing'">When it happened
                            <input type="datetime-local" x-model="form.occurred_at" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.kind === 'missing'">Last seen at
                            <input type="text" x-model="form.last_seen_place" maxlength="255" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="form.kind === 'missing'">Last seen on
                            <input type="datetime-local" x-model="form.last_seen_at" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Cause
                            <input type="text" x-model="form.cause" list="mp-causes" maxlength="160" :placeholder="{ injured: 'e.g. Falling debris', missing: 'e.g. Flooding' }[form.kind] || 'e.g. Drowning'" class="sk-sel w-full">
                        </label>
                        <datalist id="mp-causes"><template x-for="c in causes" :key="c"><option :value="c"></option></template></datalist>
                    </div>
                </fieldset>

                {{-- 4. Details for the category --}}
                <fieldset x-show="form.kind === 'injured' || dead">
                    <legend class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2" x-text="form.kind === 'injured' ? '4 · The injury' : '4 · The remains'"></legend>
                    <div class="grid gap-3 sm:grid-cols-2" x-show="form.kind === 'injured'">
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Injury
                            <input type="text" x-model="form.injury" maxlength="1000" placeholder="e.g. Fractured left arm" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Treatment given, and where
                            <input type="text" x-model="form.treatment" maxlength="255" placeholder="e.g. Treated at the RHU, sent home" class="sk-sel w-full">
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2" x-show="dead">
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Where the remains are
                            <input type="text" x-model="form.remains_location" maxlength="255" placeholder="Funeral home, hospital, home…" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Body reference no.
                            <input type="text" x-model="form.remains_ref" maxlength="80" placeholder="The tag that stays with the body" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Released to (name, relationship)
                            <input type="text" x-model="form.released_to" maxlength="160" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Released on
                            <input type="datetime-local" x-model="form.released_at" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Death registry no. (civil registrar)
                            <input type="text" x-model="form.death_registry_no" maxlength="80" class="sk-sel w-full">
                        </label>
                    </div>
                </fieldset>

                {{-- 5. Next of kin and source --}}
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2" x-text="(form.kind === 'injured' || dead ? '5' : '4') + ' · Next of kin and source'"></legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Next of kin
                            <input type="text" x-model="form.kin_name" maxlength="160" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Relationship
                            <input type="text" x-model="form.kin_relation" maxlength="60" placeholder="e.g. Wife" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1">Contact number
                            <input type="tel" x-model="form.kin_contact" maxlength="60" class="sk-sel w-full">
                        </label>
                        <label class="text-xs text-[#4B5568] flex flex-col gap-1 sm:col-span-3">Source of data (who reported or confirmed it)
                            <input type="text" x-model="form.source" list="mp-sources" maxlength="160" placeholder="e.g. MHO (death certificate)" class="sk-sel w-full">
                        </label>
                        <datalist id="mp-sources"><template x-for="s in sources" :key="s"><option :value="s"></option></template></datalist>
                    </div>
                </fieldset>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 px-6 py-4 border-t border-brand-600/10 bg-slate-50/60">
                <p class="text-xs sk-muted mr-auto" x-show="missingBits.length" x-text="'Still needed: ' + missingBits.join(', ') + '.'"></p>
                <button type="button" class="sk-btn" @click="adding = false">Cancel</button>
                <button type="button" class="sk-btn sk-btn-p" :disabled="saving || missingBits.length > 0" @click="save()"><i data-lucide="check"></i><span x-text="saving ? 'Saving…' : (editing ? 'Save changes' : 'Record')"></span></button>
            </div>
        </section>
        </div>
    </div>
</div>
