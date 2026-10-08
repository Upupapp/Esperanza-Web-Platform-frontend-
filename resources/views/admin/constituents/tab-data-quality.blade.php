{{-- tab-data-quality.blade.php — Data Quality Management Tab --}}
{{-- Rendered inside constituents.blade.php Alpine x-data scope --}}
{{-- GET /admin/data-quality via loadRegistry(); real types are Duplicate Suspected,
     Orphaned Record, Incomplete Profile only (DataQualityService); real statuses are
     Open, In Review, Resolved only (DataQualityVocabulary) — confirmed against the
     backend rather than kept from the mock's wider, partly-invented lists. --}}

<div class="space-y-6">

    {{-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────── --}}
    <div class="grid gap-5 grid-cols-[repeat(auto-fill,minmax(300px,1fr))]">
        <x-ui.stat-card
            label="Open Issues"
            valueBind="qualityIssues.filter(i => i.status === 'Open').length"
            icon="circle-alert"
            color="orange"
            :delay="0"
        />
        <x-ui.stat-card
            label="Critical / High"
            valueBind="qualityIssues.filter(i => ['Critical','High'].includes(i.severity)).length"
            icon="flame"
            color="red"
            :delay="75"
        />
        <x-ui.stat-card
            label="In Review"
            valueBind="qualityIssues.filter(i => i.status === 'In Review').length"
            icon="search"
            color="brand"
            :delay="150"
        />
        <x-ui.stat-card
            label="Resolved"
            valueBind="qualityIssues.filter(i => i.status === 'Resolved').length"
            icon="circle-check"
            color="green"
            :delay="225"
        />
    </div>

    {{-- ── FILTER BAR ──────────────────────────────────────────────────────── --}}
    <x-ui.card class="px-5 py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

            {{-- Search --}}
            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-ink-muted">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </span>
                <input aria-label="Search by name, record ID, barangay"
                    type="text"
                    x-model="search"
                    placeholder="Search by name, record ID, barangay…"
                    class="w-full pl-8 pr-4 py-2 text-sm rounded-xl placeholder-slate-400 text-ink focus:outline-none transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                />
            </div>

            {{-- Type filter --}}
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-ink-muted">
                    <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                </span>
                <select aria-label="Data quality type filter"
                    x-model="dqTypeFilter"
                    class="pl-8 pr-8 py-2 text-sm rounded-xl text-ink appearance-none focus:outline-none transition cursor-pointer border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                >
                    <option value="">All Types</option>
                    <option value="Duplicate Suspected">Duplicate Suspected</option>
                    <option value="Orphaned Record">Orphaned Record</option>
                    <option value="Incomplete Profile">Incomplete Profile</option>
                </select>
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-ink-muted">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </span>
            </div>

            {{-- Status filter --}}
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-ink-muted">
                    <i data-lucide="list-filter" class="w-3.5 h-3.5"></i>
                </span>
                <select aria-label="Data quality status filter"
                    x-model="dqStatusFilter"
                    class="pl-8 pr-8 py-2 text-sm rounded-xl text-ink appearance-none focus:outline-none transition cursor-pointer border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                >
                    <option value="">All Statuses</option>
                    <option value="Open">Open</option>
                    <option value="In Review">In Review</option>
                    <option value="Resolved">Resolved</option>
                </select>
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-ink-muted">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </span>
            </div>

            {{-- Clear button --}}
            <button
                @click="search=''; dqTypeFilter=''; dqStatusFilter=''"
                x-show="search !== '' || dqTypeFilter !== '' || dqStatusFilter !== ''"
                x-cloak
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border border-slate-200 text-slate-600 bg-white hover:bg-white/60 transition shrink-0"
                title="Clear all filters"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
            </button>

            {{-- Run detection scan --}}
            <button
                x-show="can('const_data_quality','verify')"
                @click="runDataQualityDetection()"
                :disabled="dqScanning"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-navy-900 text-white hover:bg-navy-800 transition shrink-0 disabled:opacity-60 disabled:pointer-events-none"
                title="Scan resident records for duplicates, orphaned links, and incomplete profiles"
            >
                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="dqScanning" x-cloak></i>
                <i data-lucide="radar" class="w-3.5 h-3.5" x-show="!dqScanning"></i>
                <span x-text="dqScanning ? 'Scanning…' : 'Run Scan'"></span>
            </button>

        </div>

        {{-- Result count --}}
        <div class="mt-3 pt-3 border-t border-brand-600/10 flex items-center justify-between">
            <p class="text-xs text-ink-muted">
                Showing <span class="font-semibold text-ink" x-text="filteredQualityIssues.length"></span>
                <span x-text="filteredQualityIssues.length === 1 ? 'issue' : 'issues'"></span>
                <span x-show="search !== '' || dqTypeFilter !== '' || dqStatusFilter !== ''" x-cloak>
                    &mdash; filtered from <span class="font-semibold" x-text="qualityIssues.length"></span> total
                </span>
            </p>
            <button
                @click="exportCSV(filteredQualityIssues, 'data-quality-export.csv')"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700 transition"
            >
                <i data-lucide="download" class="w-3 h-3"></i> Export
            </button>
        </div>
    </x-ui.card>

    {{-- ── ISSUES TABLE ─────────────────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden">
        <x-ui.table>
            <table class="min-w-full divide-y divide-brand-600/[.08] text-sm">
                <thead class="bg-well/70">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Issue ID</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Type</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Severity</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Record</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Assigned To</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Created</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-right text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08] bg-white">

                    <template x-for="issue in filteredQualityIssues" :key="issue.ref">
                        <tr
                            @click="openDQIssue(issue)"
                            class="hover:bg-slate-50/80 transition-colors cursor-pointer group"
                            tabindex="0"
                            @keydown.enter.prevent="openDQIssue(issue)"
                            @keydown.space.prevent="openDQIssue(issue)">
                            {{-- Issue ref --}}
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell" @click.stop>
                                <span class="font-mono text-xs font-semibold text-ink" x-text="issue.ref"></span>
                            </td>

                            {{-- Type chip --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1"
                                    :class="{
                                        'text-rose-700 bg-rose-50 ring-rose-200':       issue.type === 'Duplicate Suspected',
                                        'text-orange-700 bg-orange-50 ring-orange-200': issue.type === 'Orphaned Record',
                                        'text-amber-700 bg-amber-50 ring-amber-200':    issue.type === 'Incomplete Profile',
                                    }"
                                    x-text="issue.type"
                                ></span>
                            </td>

                            {{-- Severity badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1"
                                    :class="severityBadge(issue.severity)"
                                    x-text="issue.severity"
                                ></span>
                            </td>

                            {{-- Barangay --}}
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700 hidden 2xl:table-cell">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-ink-muted shrink-0"></i>
                                    <span x-text="issue.barangay"></span>
                                </span>
                            </td>

                            {{-- Record --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-medium text-ink" x-text="dqRecordName(issue.record) || '—'"></div>
                                <div class="text-[10px] text-ink-muted font-mono" x-text="issue.record"></div>
                            </td>

                            {{-- Assigned to --}}
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 text-[9px] font-bold flex items-center justify-center uppercase shrink-0"
                                        x-text="issue.assigned_to ? issue.assigned_to.charAt(0) : '?'"
                                    ></span>
                                    <span class="text-xs text-slate-700" x-text="issue.assigned_to || '—'"></span>
                                </div>
                            </td>

                            {{-- Created date --}}
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-ink-muted hidden 2xl:table-cell"
                                x-text="issue.created ? new Date(issue.created).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></td>

                            {{-- Status badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1"
                                    :class="{
                                        'text-rose-700 bg-rose-50 ring-rose-200':          issue.status === 'Open',
                                        'text-amber-700 bg-amber-50 ring-amber-200':       issue.status === 'In Review',
                                        'text-emerald-700 bg-emerald-50 ring-emerald-200': issue.status === 'Resolved',
                                    }"
                                    x-text="issue.status"
                                ></span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Review button --}}
                                    <button
                                        @click="openDQIssue(issue)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-medium text-brand-700 bg-brand-50 hover:bg-brand-100 ring-1 ring-brand-200 transition"
                                        title="Open issue detail"
                                    >
                                        <i data-lucide="search" class="w-3 h-3"></i> Review
                                    </button>

                                    {{-- Compare & Merge (only for Duplicate Suspected) --}}
                                    <button
                                        x-show="can('const_data_quality','merge') && issue.type === 'Duplicate Suspected' && issue.merge_candidate"
                                        @click="selectedDQIssue = issue; showMerge = true; $nextTick(() => window.renderIcons?.())"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 ring-1 ring-rose-200 transition"
                                        title="Compare records and merge"
                                    >
                                        <i data-lucide="git-merge" class="w-3 h-3"></i> Merge
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <tr x-show="filteredQualityIssues.length === 0" x-cloak>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                                    <i data-lucide="circle-check" class="w-6 h-6 text-emerald-500"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-ink">No issues found</p>
                                    <p class="text-xs text-ink-muted mt-1">
                                        <span x-show="search !== '' || dqTypeFilter !== '' || dqStatusFilter !== ''">
                                            No data quality issues match your current filters.
                                            <button @click="search=''; dqTypeFilter=''; dqStatusFilter=''" class="text-brand-600 underline hover:text-brand-700 ml-1">Clear filters</button>
                                        </span>
                                        <span x-show="search === '' && dqTypeFilter === '' && dqStatusFilter === ''">
                                            All data quality issues have been resolved. Run a scan to check for new ones.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </x-ui.table>

        {{-- Table footer --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 px-5 py-3 border-t border-brand-600/10 bg-slate-50/50">
            <p class="text-xs text-ink-muted">
                <span class="font-semibold text-ink" x-text="filteredQualityIssues.length"></span> issue(s) displayed
                &mdash; Click any row to view full issue detail
            </p>
            <div class="flex items-center gap-3 text-[11px] text-ink-muted">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span> Open
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> In Review
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Resolved
                </span>
            </div>
        </div>
    </x-ui.card>

    {{-- ── INFO BANNER ──────────────────────────────────────────────────────── --}}
    <div class="flex items-start gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-sm text-blue-800">
        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0 text-blue-500"></i>
        <div>
            <p class="font-semibold">How this queue fills up</p>
            <p class="text-xs text-blue-700 mt-0.5">
                Issues are raised by Run Scan, which checks resident records for suspected duplicate names,
                residents with no linked household, and profiles missing required fields. The scan is
                idempotent — running it again will not raise the same open issue twice.
            </p>
        </div>
    </div>

</div>
