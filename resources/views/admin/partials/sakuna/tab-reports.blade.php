{{--
    tab-reports.blade.php — Disaster Reports (owner, 2026-10-28/30).
    Its own component (resources/js/sakuna-report.js): every card, chart and
    table row comes from the server's one calculation under the same filters
    (GET /admin/sakuna/reports/summary and /incidents), so they agree with
    each other and with the export (POST /admin/sakuna/reports/export, needs
    sakuna_reports.export; checked and logged on the server). People,
    families and incidents are counted apart and never added together; what
    the records cannot say is said in the notes, not estimated.
--}}
@php
    // SakunaVocabulary::TYPES on the backend; the filter is checked there too.
    $reportTypes = ['Flood', 'Fire', 'Landslide', 'Earthquake', 'Vehicular Accident', 'Medical Emergency', 'Missing Person',
        'Stranded Resident', 'Fallen Tree', 'Downed Power Line', 'Road Obstruction', 'Damaged Bridge', 'Rescue Request',
        'Drowning or Water Rescue', 'Agricultural Damage', 'Structural Damage', 'Water Interruption', 'Power Interruption',
        'Public Health Emergency', 'Other'];
    // The filter sends the status on record; it shows the incident's own word
    // for it, as the Incidents tab does (critique 2026-11-03).
    $reportStatuses = ['Submitted' => 'Reported', 'Under Verification' => 'Validated', 'Assigned' => 'Assigned', 'Processing' => 'Dispatched', 'Completed' => 'Resolved', 'Archived' => 'Closed', 'Rejected' => 'Rejected'];
    // case_kind: the Casualties tab's kinds of case (injured since 2026-11-14).
    $reportCaseKinds = ['' => 'All cases', 'missing' => 'Missing', 'injured' => 'Injured', 'deceased' => 'Deceased', 'unidentified' => 'Unidentified'];
@endphp

<div x-data="sakunaReport" class="space-y-3">

    {{-- Sakuna Redesign (2026-11-02): filters and export in one row, as the
         design draws it; when they don't fit, the row wraps and the export
         buttons drop to a second line (2026-11-05). The dates sit behind one
         button (presets, From/To, Apply). All the filters apply together. --}}
    <section class="sk-card relative" @keydown.escape.window="cancelRange()">
        <div class="sk-toolbar sk-toolbar-row">
            <select x-model="f.barangay" @change="load(1)" aria-label="Barangay" class="sk-sel" style="flex: 1 1 126px; min-width: 96px;">
                <option value="">All barangays</option>
                @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
            </select>
            <button type="button" class="sk-daterange" @click="openRange($el)" :aria-expanded="rangeOpen ? 'true' : 'false'" aria-haspopup="dialog" aria-label="Date range" :title="rangeLabel">
                <i data-lucide="calendar"></i><span x-text="rangeLabel">All dates</span><i data-lucide="chevron-down"></i>
            </button>
            <select x-model="f.type" @change="load(1)" aria-label="Disaster type" class="sk-sel" style="flex: 1 1 96px; min-width: 90px;">
                <option value="">All types</option>
                @foreach($reportTypes as $t)<option>{{ $t }}</option>@endforeach
            </select>
            <select x-model="f.status" @change="load(1)" aria-label="Incident status" class="sk-sel" style="flex: 1 1 114px; min-width: 96px;">
                <option value="">All statuses</option>
                @foreach($reportStatuses as $s => $label)<option value="{{ $s }}">{{ $label }}</option>@endforeach
            </select>
            {{-- The kind of person case (sheet item 13, owner 2026-11-05), for
                 whoever sees the Missing Persons tab: the same condition. --}}
            <select x-model="f.case_kind" @change="load(1)" x-show="permsReady && tabShown('missing-persons')" x-cloak aria-label="Case kind" class="sk-sel" style="flex: 1 0 auto; min-width: 118px;">
                @foreach($reportCaseKinds as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <select x-model="f.livelihood_group" @change="groupChanged()" aria-label="Livelihood group" class="sk-sel" style="flex: 1 1 128px; min-width: 110px;">
                <option value="">All livelihoods</option>
                <template x-for="g in $store.livelihood.groups" :key="g.code"><option :value="g.code" x-text="g.code + ' · ' + g.label"></option></template>
            </select>
            <select x-model="f.livelihood_code" @change="load(1)" :disabled="!f.livelihood_group" aria-label="Occupation or livelihood" class="sk-sel" style="flex: 1 1 138px; min-width: 100px;">
                <option value="" x-text="f.livelihood_group ? 'All in the group' : 'All occupations'">All occupations</option>
                <template x-for="i in $store.livelihood.items(f.livelihood_group)" :key="i.code"><option :value="i.code" x-text="(i.heading ? i.heading + ' › ' : '') + i.label"></option></template>
            </select>
            <button type="button" @click="clear()" class="sk-clear">Clear</button>
            <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare"
                aria-label="About this report"
                title="The situation report: incidents, evacuation, relief, resources, damage, missing persons and alerts, from the records, under the filters.">
                <i data-lucide="info"></i>
            </button>
            <div class="sk-btngrp" role="group" aria-label="Export" x-show="can('sakuna_reports','export')" style="flex-shrink: 0;">
                <button type="button" class="sk-btn" :disabled="!!exporting || !!rangeError" @click="exportAs('XLSX')"><i data-lucide="download"></i><span x-text="exporting === 'XLSX' ? 'Exporting…' : 'Export XLSX'"></span></button>
                <button type="button" class="sk-btn" :disabled="!!exporting || !!rangeError" @click="exportAs('PDF')"><i data-lucide="file-text"></i><span x-text="exporting === 'PDF' ? 'Exporting…' : 'Export PDF'"></span></button>
            </div>
        </div>

        {{-- The date range: presets apply at once; From/To wait for Apply. --}}
        <div x-show="rangeOpen" x-cloak x-transition.opacity.duration.100ms @click.outside="if (!$event.target.closest('.sk-daterange')) cancelRange()"
            role="dialog" aria-label="Choose the date range" class="sk-card sk-popover" :style="rangePos">
            <div class="flex flex-wrap gap-1.5">
                <button type="button" class="sk-btn sk-btn-sm" @click="presetRange('week')">Last 7 days</button>
                <button type="button" class="sk-btn sk-btn-sm" @click="presetRange('month30')">Last 30 days</button>
                <button type="button" class="sk-btn sk-btn-sm" @click="presetRange('thisMonth')">This month</button>
                <button type="button" class="sk-btn sk-btn-sm" @click="presetRange('all')">All dates</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex flex-col gap-1 text-xs text-[#4B5568]">From
                    <input type="date" x-model="f.from" class="sk-sel">
                </label>
                <label class="flex flex-col gap-1 text-xs text-[#4B5568]">To
                    <input type="date" x-model="f.to" :min="f.from" class="sk-sel">
                </label>
            </div>
            <p class="text-xs text-rose-700" x-show="rangeError" x-text="rangeError"></p>
            <div class="flex justify-end gap-1.5">
                <button type="button" class="sk-btn sk-btn-sm" @click="cancelRange()">Cancel</button>
                <button type="button" class="sk-btn sk-btn-sm sk-btn-p" :disabled="!!rangeError" @click="applyRange()">Apply</button>
            </div>
        </div>
    </section>
    <p x-show="failed" x-cloak class="text-sm text-rose-700">The report could not load. Check the filters and try again.</p>
    <p x-show="loading && !summary" class="sk-empty">Loading the report…</p>

    <template x-if="summary">
    <div class="space-y-3">
        {{-- Figures: incidents, people and families each on their own. --}}
        <section class="sk-card overflow-hidden">
            <div class="sk-g6">
                {{-- The person tiles only with their figures (figureCards, sakuna-report.js). --}}
                <template x-for="card in figureCards()" :key="card[0]">
                    <div class="sk-stat">
                        <span class="sk-slab"><span x-show="card[2] && card[1]" class="sk-dot" :class="card[2]"></span><span x-text="card[0]"></span></span>
                        <span class="sk-sval tabular-nums" x-text="card[1].toLocaleString()"></span>
                    </div>
                </template>
            </div>
        </section>

        {{-- The situation report's sections (2026-10-30): the same tables the
             XLSX and PDF carry. Counts only; names stay in their own tabs. --}}
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <div class="sk-segwrap" role="tablist" aria-label="Report sections">
                <template x-for="tab in shownSectionTabs()" :key="tab[0]">
                    <button type="button" role="tab" :aria-selected="section === tab[0]" @click="section = tab[0]"
                        class="sk-seg" :class="section === tab[0] ? 'sk-seg-on' : ''"
                        x-text="tab[1]"></button>
                </template>
            </div>
            <span class="sk-csub" x-text="new Date(summary.period.from + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' – ' + new Date(summary.period.to + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + (f.barangay || 'all barangays')"></span>
        </div>
        <div x-show="section !== 'overview'" x-cloak class="space-y-3" data-report-section x-effect="if (section === 'missing' && !hasPersons()) section = 'overview'">
            <template x-for="tbl in tablesFor(section)" :key="tbl.heading">
                <section class="sk-card overflow-hidden">
                    <div class="sk-chead">
                        <h2 class="sk-ctitle" x-text="tbl.heading"></h2>
                        <span class="sk-csub" x-show="tbl.note" x-text="tbl.note"></span>
                    </div>
                    <div class="sk-scroll" tabindex="0" role="region" :aria-label="tbl.heading + ' table (scrolls sideways)'">
                        <table class="sk-tbl">
                            <thead>
                                <tr><template x-for="c in tbl.columns" :key="c.key"><th scope="col" :class="c.num ? 'sk-r' : ''" x-text="c.label"></th></template></tr>
                            </thead>
                            <tbody>
                                <template x-for="(r, i) in tbl.rows" :key="i">
                                    <tr>
                                        <template x-for="c in tbl.columns" :key="c.key"><td :class="c.num ? 'sk-r tabular-nums' : ''" x-text="c.money ? '₱' + money(r[c.key]) : cell(r[c.key])"></td></template>
                                    </tr>
                                </template>
                                <tr x-show="!tbl.rows.length"><td :colspan="tbl.columns.length"><div class="sk-empty">None recorded.</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>

        <div x-show="section === 'overview'" class="space-y-3">
        <div class="sk-g4">
            <section class="sk-card sk-span2 flex flex-col">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 0;"><h2 class="sk-ctitle">Incidents over time</h2><span class="sk-csub">Per day</span></div>
                <div class="px-3.5 pb-3.5 pt-1.5">
                    <x-ui.clay-trend data="{ labels: reportTrendLabels(), bars: true, series: [{ label: 'Incidents', values: reportTrendData(), color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.18 }] }" label="Incidents reported in the period" />
                </div>
            </section>
            <section class="sk-card">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 0;"><h2 class="sk-ctitle">By disaster type</h2></div>
                <x-ui.clay-donut rows="reportTypeBreakdown().map(t => ({ label: t[0], value: t[1], color: incidentTypeColor(t[0]) }))" :keep-colors="true" total-label="Incidents" empty="No incidents to chart." class="px-3.5 pb-3.5 pt-2.5" />
            </section>
            <section class="sk-card">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 0;"><h2 class="sk-ctitle">By status</h2></div>
                <x-ui.clay-donut rows="statusRows()" :keep-colors="true" total-label="Incidents" empty="No incidents to chart." class="px-3.5 pb-3.5 pt-2.5" />
            </section>
        </div>

        {{-- Where, and the livelihoods of the people evacuated, as recorded at check-in. --}}
        <div class="sk-g4">
            <section class="sk-card">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 4px;"><h2 class="sk-ctitle">By barangay</h2></div>
                <div class="px-3.5 pb-3.5 pt-1.5 flex flex-col gap-2.5 text-xs">
                    <p class="sk-csub" x-show="!barangayRows().length">No incidents to chart.</p>
                    <template x-for="b in barangayRows()" :key="b.label">
                        <div>
                            <div class="flex justify-between gap-2"><span class="truncate" x-text="b.label"></span><b class="tabular-nums" x-text="b.count"></b></div>
                            <div class="h-1.5 rounded-full bg-[#EEF2F7] mt-1"><div class="h-full rounded-full bg-[#2563EB]" :style="'width:' + b.pct + '%'"></div></div>
                        </div>
                    </template>
                </div>
            </section>
            <section class="sk-card">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 4px;"><h2 class="sk-ctitle">Evacuated by livelihood group</h2></div>
                <x-ui.clay-donut rows="livelihoodRows()" total-label="People" empty="No classified livelihoods among the people evacuated." class="px-3.5 pb-3.5 pt-1.5" />
            </section>
            <section class="sk-card overflow-hidden">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 4px;"><h2 class="sk-ctitle">Evacuated by livelihood</h2><span class="sk-csub">People</span></div>
                <table class="sk-tbl">
                    <thead><tr><th scope="col">Livelihood</th><th scope="col" class="sk-r">People</th></tr></thead>
                    <tbody>
                        <template x-for="i in summary.livelihood.items" :key="i.code">
                            <tr><td><span class="sk-mono" x-text="i.code"></span> <span x-text="i.label"></span></td><td class="sk-r tabular-nums" x-text="i.people"></td></tr>
                        </template>
                        <tr><td class="sk-muted">Not yet classified</td><td class="sk-r tabular-nums" x-text="summary.livelihood.unclassified"></td></tr>
                    </tbody>
                </table>
            </section>
            <section class="sk-card">
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 4px;"><h2 class="sk-ctitle">Damage</h2></div>
                <div class="grid grid-cols-2 gap-x-1.5 gap-y-2.5 px-3.5 pb-3.5 pt-1.5">
                    <div><div class="sk-b tabular-nums text-base" x-text="summary.damage.units_affected.toLocaleString()"></div><div class="sk-ssub">Units affected</div></div>
                    <div><div class="sk-b tabular-nums text-base" x-text="'₱' + summary.damage.estimated_cost.toLocaleString('en-PH')"></div><div class="sk-ssub">Estimated cost</div></div>
                    <div class="col-span-2"><div class="sk-b text-xs" x-text="summary.damage.by_sector.map((s) => s.sector + ' ' + s.reports).join(', ') || '—'"></div><div class="sk-ssub">By sector</div></div>
                </div>
            </section>
        </div>

        {{-- The incidents themselves, and what the figures do and do not count. --}}
        <section class="sk-card overflow-hidden">
            <div class="sk-chead">
                <h2 class="sk-ctitle">Incidents in this period</h2>
                <span class="sk-csub" x-text="total + (total === 1 ? ' incident' : ' incidents')"></span>
            </div>
            <div class="sk-scroll" tabindex="0" role="region" aria-label="Incidents in this period table (scrolls sideways)">
                <table class="sk-tbl">
                    <thead>
                        <tr>
                            <th scope="col">Incident</th>
                            <th scope="col">Reported</th>
                            <th scope="col">Type</th>
                            <th scope="col">Barangay</th>
                            <th scope="col">Summary</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="sk-r" x-show="hasPersons()">Missing</th>
                            <th scope="col" class="sk-r">Damage reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in rows" :key="r.ref">
                            <tr>
                                <td><span class="sk-mono" x-text="r.ref"></span></td>
                                <td class="whitespace-nowrap" x-text="new Date(r.reported).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })"></td>
                                <td x-text="r.type"></td>
                                <td x-text="r.barangay"></td>
                                <td x-text="r.title"></td>
                                <td><span class="sk-pill" :class="statusBadge(r.status)" x-text="incidentStatusLabel(r.status)" :title="'Status on record: ' + r.status"></span></td>
                                <td class="sk-r tabular-nums" x-show="hasPersons()" x-text="cell(r.missing)"></td>
                                <td class="sk-r tabular-nums" x-text="r.damage_reports"></td>
                            </tr>
                        </template>
                        <tr x-show="!rows.length"><td :colspan="hasPersons() ? 8 : 7"><div class="sk-empty">No incidents match these filters.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div x-show="lastPage > 1" class="sk-tfoot flex items-center justify-end gap-2">
                <button type="button" :disabled="page <= 1" @click="load(page - 1)" class="sk-btn sk-btn-sm">Previous</button>
                <span x-text="'Page ' + page + ' of ' + lastPage + ' · ' + total + ' incidents'"></span>
                <button type="button" :disabled="page >= lastPage" @click="load(page + 1)" class="sk-btn sk-btn-sm">Next</button>
            </div>
            <details class="sk-about" x-show="summary.notes.length">
                <summary><i data-lucide="info"></i>About these figures</summary>
                <ul>
                    <template x-for="(n, i) in summary.notes" :key="i"><li x-text="n"></li></template>
                </ul>
            </details>
        </section>
        </div>
    </div>
    </template>
</div>
