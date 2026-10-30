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
    $reportStatuses = ['Submitted', 'Under Verification', 'Assigned', 'Processing', 'Completed', 'Archived', 'Rejected'];
@endphp

<div x-data="sakunaReport" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-ink-body">Disaster Reports</h2>
            <p class="text-sm text-ink-muted mt-0.5">The situation report: incidents, evacuation, relief, resources, damage, missing persons and alerts, from the records, under the filters below.</p>
        </div>
        <div class="flex items-center gap-2" x-show="can('sakuna_reports','export')">
            <x-ui.button size="sm" variant="secondary" icon="file-spreadsheet" x-bind:disabled="!!exporting || !!rangeError" @click="exportAs('XLSX')"><span x-text="exporting === 'XLSX' ? 'Exporting…' : 'Export XLSX'"></span></x-ui.button>
            <x-ui.button size="sm" variant="secondary" icon="file-down" x-bind:disabled="!!exporting || !!rangeError" @click="exportAs('PDF')"><span x-text="exporting === 'PDF' ? 'Exporting…' : 'Export PDF'"></span></x-ui.button>
        </div>
    </div>

    {{-- Filters: all of them apply together. --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 rounded-2xl border border-slate-200 bg-white p-4">
        <label class="text-xs text-ink-muted">Barangay
            <select x-model="f.barangay" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                <option value="">All barangays</option>
                @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs text-ink-muted">From
            <input type="date" x-model="f.from" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
        </label>
        <label class="text-xs text-ink-muted">To
            <input type="date" x-model="f.to" :min="f.from" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
        </label>
        <label class="text-xs text-ink-muted">Disaster type
            <select x-model="f.type" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                <option value="">All types</option>
                @foreach($reportTypes as $t)<option>{{ $t }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs text-ink-muted">Incident status
            <select x-model="f.status" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                <option value="">All statuses</option>
                @foreach($reportStatuses as $s)<option>{{ $s }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs text-ink-muted">Livelihood group
            <select x-model="f.livelihood_group" @change="groupChanged()" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                <option value="">All groups</option>
                <template x-for="g in $store.livelihood.groups" :key="g.code"><option :value="g.code" x-text="g.code + ' · ' + g.label"></option></template>
            </select>
        </label>
        <label class="text-xs text-ink-muted">Occupation or livelihood
            <select x-model="f.livelihood_code" @change="load(1)" :disabled="!f.livelihood_group" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2 disabled:opacity-60">
                <option value="">All in the group</option>
                <template x-for="i in $store.livelihood.items(f.livelihood_group)" :key="i.code"><option :value="i.code" x-text="(i.heading ? i.heading + ' › ' : '') + i.label"></option></template>
            </select>
        </label>
        <div class="flex items-end gap-3">
            <button type="button" @click="clear()" class="text-xs text-ink-muted hover:text-rose-600">Clear filters</button>
            <span class="text-xs text-ink-muted" x-show="summary" x-text="summary ? summary.period.from + ' to ' + summary.period.to : ''"></span>
        </div>
        <p class="sm:col-span-2 lg:col-span-4 text-xs text-rose-700" x-show="rangeError" x-text="rangeError"></p>
    </div>

    <p x-show="failed" x-cloak class="text-sm text-rose-700">The report could not load. Check the filters and try again.</p>
    <p x-show="loading && !summary" class="text-sm text-ink-muted">Loading the report…</p>

    <template x-if="summary">
    <div class="space-y-5">
        {{-- Figures: incidents, people and families each on their own. --}}
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-4 xl:grid-cols-6">
            <template x-for="card in [
                ['Incidents', summary.incidents.total, 'text-ink'],
                ['Evacuated individuals', summary.evacuated.individuals, 'text-brand-700'],
                ['Evacuated families', summary.evacuated.families, 'text-brand-700'],
                ['Inside centres now', summary.evacuation.now.persons_inside, 'text-brand-700'],
                ['Still missing', summary.persons.missing, 'text-rose-700'],
                ['Found', summary.persons.found, 'text-emerald-700'],
                ['Deceased', summary.persons.deceased, 'text-slate-700'],
                ['Unidentified', summary.persons.unidentified, 'text-amber-700'],
                ['Relief released', summary.relief.totals.released, 'text-emerald-700'],
                ['Resources deployed now', summary.resources.now.deployed, 'text-ink'],
                ['Damage reports', summary.damage.reports, 'text-violet-700'],
                ['Alerts issued', summary.alerts.issued, 'text-amber-700'],
            ]" :key="card[0]">
                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5">
                    <p class="text-[11px] text-ink-muted" x-text="card[0]"></p>
                    <p class="text-xl font-extrabold tabular-nums" :class="card[2]" x-text="card[1].toLocaleString()"></p>
                </div>
            </template>
        </div>

        {{-- The situation report's sections (2026-10-30): the same tables the
             XLSX and PDF carry. Counts only; names stay in their own tabs. --}}
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Report sections">
            <template x-for="tab in sectionTabs" :key="tab[0]">
                <button type="button" role="tab" :aria-selected="section === tab[0]" @click="section = tab[0]"
                    class="text-sm font-medium rounded-xl px-3 py-1.5 border transition-colors"
                    :class="section === tab[0] ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-200 text-ink-body hover:border-brand-300'"
                    x-text="tab[1]"></button>
            </template>
        </div>
        <div x-show="section !== 'overview'" x-cloak class="space-y-4" data-report-section>
            <template x-for="tbl in tablesFor(section)" :key="tbl.heading">
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-4 pt-3 pb-2">
                        <p class="text-sm font-semibold text-ink" x-text="tbl.heading"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-show="tbl.note" x-text="tbl.note"></p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-ink-muted bg-slate-50">
                                <tr><template x-for="c in tbl.columns" :key="c.key"><th scope="col" class="font-medium px-3 py-2" :class="c.num ? 'text-right' : 'text-left'" x-text="c.label"></th></template></tr>
                            </thead>
                            <tbody>
                                <template x-for="(r, i) in tbl.rows" :key="i">
                                    <tr class="border-t border-slate-100">
                                        <template x-for="c in tbl.columns" :key="c.key"><td class="px-3 py-2" :class="c.num ? 'text-right tabular-nums' : ''" x-text="c.money ? '₱' + money(r[c.key]) : cell(r[c.key])"></td></template>
                                    </tr>
                                </template>
                                <tr x-show="!tbl.rows.length"><td :colspan="tbl.columns.length" class="px-3 py-5 text-center text-sm text-ink-muted">None recorded.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="section === 'overview'" class="space-y-5">
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-2">Incidents over time</p>
                <x-ui.clay-trend data="{ labels: reportTrendLabels(), series: [{ label: 'Incidents', values: reportTrendData(), color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.38 }] }" label="Incidents reported in the period" />
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-2">Incidents by disaster type</p>
                <x-ui.clay-donut rows="reportTypeBreakdown().map(t => ({ label: t[0], value: t[1] }))" total-label="Incidents" empty="No incidents to chart." />
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-3">Incidents by barangay</p>
                <p class="text-sm text-ink-muted" x-show="!barangayRows().length">No incidents to chart.</p>
                <ul class="space-y-1.5">
                    <template x-for="b in barangayRows()" :key="b.label">
                        <li class="grid grid-cols-[8rem_1fr_2.5rem] items-center gap-2 text-xs">
                            <span class="truncate" x-text="b.label"></span>
                            <span class="h-2.5 rounded-full bg-slate-100"><span class="block h-full rounded-full bg-brand-500" :style="'width:' + b.pct + '%'"></span></span>
                            <span class="text-right tabular-nums font-semibold" x-text="b.count"></span>
                        </li>
                    </template>
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-2">Incidents by status</p>
                <x-ui.clay-donut rows="statusRows()" total-label="Incidents" empty="No incidents to chart." />
            </div>
        </div>

        {{-- Livelihoods of the people evacuated, as recorded at check-in. --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-2">Evacuated people by livelihood group</p>
                <x-ui.clay-donut rows="livelihoodRows()" total-label="People" empty="No classified livelihoods among the people evacuated." />
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-ink mb-2">Evacuated people by livelihood</p>
                <table class="w-full text-sm">
                    <thead class="text-xs text-ink-muted"><tr><th scope="col" class="text-left font-medium py-1">Livelihood</th><th scope="col" class="text-right font-medium py-1">People</th></tr></thead>
                    <tbody>
                        <template x-for="i in summary.livelihood.items" :key="i.code">
                            <tr class="border-t border-slate-100"><td class="py-1"><span class="font-mono text-xs text-ink-muted" x-text="i.code"></span> <span x-text="i.label"></span></td><td class="py-1 text-right tabular-nums" x-text="i.people"></td></tr>
                        </template>
                        <tr class="border-t border-slate-100"><td class="py-1 text-ink-muted">Not yet classified</td><td class="py-1 text-right tabular-nums" x-text="summary.livelihood.unclassified"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 grid gap-2 sm:grid-cols-3 text-sm">
            <div><p class="text-xs text-ink-muted">Damage: units affected</p><p class="font-bold tabular-nums" x-text="summary.damage.units_affected.toLocaleString()"></p></div>
            <div><p class="text-xs text-ink-muted">Damage: estimated cost</p><p class="font-bold tabular-nums" x-text="'₱' + summary.damage.estimated_cost.toLocaleString('en-PH', { minimumFractionDigits: 2 })"></p></div>
            <div><p class="text-xs text-ink-muted">By sector</p><p class="text-xs" x-text="summary.damage.by_sector.map((s) => s.sector + ' ' + s.reports).join(', ') || '—'"></p></div>
        </div>

        <ul class="rounded-2xl bg-slate-50 p-4 text-xs text-ink-muted space-y-1 list-disc pl-8">
            <template x-for="(n, i) in summary.notes" :key="i"><li x-text="n"></li></template>
        </ul>

        {{-- The incidents themselves. --}}
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="text-xs text-ink-muted uppercase tracking-wide bg-slate-50">
                    <tr>
                        <th scope="col" class="text-left font-medium px-3 py-2">Incident</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Reported</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Type</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Barangay</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Summary</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Status</th>
                        <th scope="col" class="text-right font-medium px-3 py-2">Missing</th>
                        <th scope="col" class="text-right font-medium px-3 py-2">Damage reports</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rows" :key="r.ref">
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2 font-mono text-xs" x-text="r.ref"></td>
                            <td class="px-3 py-2 text-xs" x-text="new Date(r.reported).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })"></td>
                            <td class="px-3 py-2" x-text="r.type"></td>
                            <td class="px-3 py-2" x-text="r.barangay"></td>
                            <td class="px-3 py-2" x-text="r.title"></td>
                            <td class="px-3 py-2 text-xs" x-text="r.status"></td>
                            <td class="px-3 py-2 text-right tabular-nums" x-text="r.missing"></td>
                            <td class="px-3 py-2 text-right tabular-nums" x-text="r.damage_reports"></td>
                        </tr>
                    </template>
                    <tr x-show="!rows.length"><td colspan="8" class="px-3 py-6 text-center text-sm text-ink-muted">No incidents match these filters.</td></tr>
                </tbody>
            </table>
        </div>
        <div x-show="lastPage > 1" class="flex items-center justify-end gap-2 text-xs">
            <button type="button" :disabled="page <= 1" @click="load(page - 1)" class="px-3 py-1.5 rounded-xl border border-slate-200 disabled:opacity-40">Previous</button>
            <span x-text="'Page ' + page + ' of ' + lastPage + ' · ' + total + ' incidents'"></span>
            <button type="button" :disabled="page >= lastPage" @click="load(page + 1)" class="px-3 py-1.5 rounded-xl border border-slate-200 disabled:opacity-40">Next</button>
        </div>
        </div>
    </div>
    </template>
</div>
