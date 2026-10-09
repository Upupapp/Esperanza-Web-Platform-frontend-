{{--
    tab-reports.blade.php
    Disaster Reports & Analytics
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: reportBarangayFilter, reportsLoading/reportsFailed,
    reportIncidents/reportCenters/reportVulnerability (raw) and reportScopedIncidents/
    reportScopedCenters/reportScopedVulnerability (barangay-filtered getters).
    Helpers: exportCSV(rows, filename), loadReports(), reportTrendLabels(),
    reportTrendData(), reportTypeBreakdown(), reportLowestReadiness(n).

    Reads GET /sakuna/incidents, GET /sakuna/centers, GET /sakuna/vulnerability --
    no new backend endpoint was needed for this tab; every chart and KPI below is
    computed client-side from data those three already-real endpoints return.

    The mock's 4 charts were entirely hardcoded (a 14-day incident trend, an
    incidents-by-category donut, a center occupancy bar chart and a barangay
    readiness ranking, none reading from any of the other tabs' real data) and its
    "Performance Metrics" section invented four response-time KPIs -- Avg.
    Validation/Dispatch/Arrival Time -- that nothing in the schema tracks:
    SakunaIncidentEvent timestamps each verb transition, but computing a real
    department-wide average from that would mean fetching every incident's full
    detail (its timeline lives only on GET /sakuna/incidents/{ref}, not the list),
    an N+1 cost this tab doesn't take on. "Top Requesting Barangays" mapped to no
    real concept at all -- there is no relief "request" step, only a distribution
    created directly (see tab-relief.blade.php). Both are dropped rather than
    faked. The "Report Catalogue" (Generate/Export/Print buttons that only ever
    toasted, no real PDF generation anywhere in the codebase) is replaced with
    real CSV exports of the three lists this tab already has in memory.
--}}

<div x-init="loadReports()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-ink-body">Disaster Reports &amp; Analytics</h2>
        <p class="text-sm text-ink-muted mt-0.5">Live analytics derived from incidents, evacuation centers and vulnerability assessments.</p>
    </div>
    <select aria-label="Report barangay filter" x-model="reportBarangayFilter"
        class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        <option value="">All Barangays</option>
        @foreach($barangays as $brgy)
            <option value="{{ $brgy }}">{{ $brgy }}</option>
        @endforeach
    </select>
</div>

<div x-show="reportsLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="reportsFailed" x-cloak><x-ui.error-state messageExpr="'Could not load report data.'" retryExpr="loadReports()" :retryable="true" /></div>

<template x-if="!reportsLoading && !reportsFailed">
<div class="space-y-4">

{{-- ── KPI Tiles ────────────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-ui.stat-card layout="compact" tone="rose" object="flame" label="Incidents" valueBind="reportScopedIncidents.length" />
    <x-ui.stat-card layout="compact" tone="teal" object="stamp" label="Incidents Resolved" valueBind="reportScopedIncidents.length ? Math.round(reportScopedIncidents.filter(i => ['Completed','Archived'].includes(i.status)).length / reportScopedIncidents.length * 100) + '%' : '—'" />
    <x-ui.stat-card layout="compact" tone="brand" object="people" label="Evacuees Sheltered" valueBind="reportScopedCenters.reduce((s,c) => s + (c.individuals || 0), 0)" />
    <x-ui.stat-card layout="compact" tone="violet" object="ring" label="Avg. Readiness Score" valueBind="reportScopedVulnerability.length ? Math.round(reportScopedVulnerability.reduce((s,v) => s + (v.readiness_score || 0), 0) / reportScopedVulnerability.length) : '—'" />
</div>

{{-- ── Charts (2×2): the dashboard's clay charts (owner, 2026-10-08) ───────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">

    <div class="surface-glass rounded-[26px] p-5">
        <h3 class="text-lg font-extrabold text-ink">Incident Trend</h3>
        <p class="text-[13px] text-ink-muted mb-3">Last 14 days · <span x-text="reportBarangayFilter || 'All Barangays'"></span></p>
        <x-ui.clay-trend data="{ labels: reportTrendLabels(), series: [{ label: 'Incidents', values: reportTrendData(), color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.38 }] }" label="Incidents reported on each of the last 14 days" />
    </div>

    <div class="surface-glass rounded-[26px] p-5">
        <h3 class="text-lg font-extrabold text-ink">Incidents by Type</h3>
        <p class="text-[13px] text-ink-muted mb-4">Current data</p>
        <x-ui.clay-donut rows="reportTypeBreakdown().map(t => ({ label: t[0], value: t[1] }))" total-label="Incidents" empty="No incidents to chart." />
    </div>

    <div class="surface-glass rounded-[26px] p-5">
        <h3 class="text-lg font-extrabold text-ink">Center Occupancy vs Capacity</h3>
        <p class="text-[13px] text-ink-muted mb-4" x-text="reportScopedCenters.length + ' center(s)'"></p>
        <div class="space-y-4">
            <template x-for="c in reportScopedCenters" :key="c.name">
                <div x-data="{ get pct() { return c.capacity ? Math.round((c.individuals || 0) / c.capacity * 100) : 0 } }">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-[14.5px] font-semibold text-ink-body truncate" x-text="c.name"></span>
                        <span class="text-sm text-ink-muted shrink-0 tabular-nums" x-text="(c.individuals || 0) + ' / ' + (c.capacity || 0) + ' · ' + pct + '%'"></span>
                    </div>
                    <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                        <div class="h-full rounded-full bg-gradient-to-r transition-all duration-[600ms] ease-standard" :class="pct >= 100 ? 'from-rose-400 to-rose-600' : (pct >= 75 ? 'from-amber-400 to-amber-600' : 'from-brand-400 to-brand-600')" :style="'width:' + Math.min(100, pct) + '%'"></div>
                    </div>
                </div>
            </template>
            <div x-show="!reportScopedCenters.length" class="surface-pressed rounded-2xl px-4 py-5 text-center text-[14.5px] font-bold text-ink">No centers to chart.</div>
        </div>
    </div>

    <div class="surface-glass rounded-[26px] p-5">
        <h3 class="text-lg font-extrabold text-ink">Readiness Score — Lowest Barangays</h3>
        <p class="text-[13px] text-ink-muted mb-4">Score out of 100</p>
        <div class="space-y-4">
            <template x-for="v in reportLowestReadiness(8)" :key="v.barangay">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-[14.5px] font-semibold text-ink-body truncate" x-text="'Brgy. ' + v.barangay"></span>
                        <span class="text-sm text-ink-muted shrink-0 tabular-nums" x-text="(v.readiness_score || 0) + ' / 100'"></span>
                    </div>
                    <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                        <div class="h-full rounded-full bg-gradient-to-r transition-all duration-[600ms] ease-standard" :class="v.readiness_score < 50 ? 'from-rose-400 to-rose-600' : (v.readiness_score < 70 ? 'from-amber-400 to-amber-600' : 'from-brand-400 to-brand-600')" :style="'width:' + Math.min(100, v.readiness_score || 0) + '%'"></div>
                    </div>
                </div>
            </template>
            <div x-show="!reportLowestReadiness(8).length" class="surface-pressed rounded-2xl px-4 py-5 text-center text-[14.5px] font-bold text-ink">No assessed barangays to chart.</div>
        </div>
    </div>
</div>

{{-- ── Export ───────────────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] p-5">
    <h3 class="text-lg font-extrabold text-ink mb-3">Export Data</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <button @click="exportCSV(reportScopedIncidents, 'incidents-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl bg-white border border-brand-600/10 shadow-[0_1px_2px_rgb(11_23_48/.04),0_4px_12px_rgb(29_71_214/.05)] hover:border-brand-300 hover:-translate-y-px transition-all">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0"><i data-lucide="flame" class="w-4 h-4"></i></span>
            <span>
                <span class="block text-sm font-medium text-slate-700">Incidents</span>
                <span class="block text-xs text-ink-muted" x-text="reportScopedIncidents.length + ' records'"></span>
            </span>
        </button>
        <button @click="exportCSV(reportScopedCenters, 'centers-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl bg-white border border-brand-600/10 shadow-[0_1px_2px_rgb(11_23_48/.04),0_4px_12px_rgb(29_71_214/.05)] hover:border-brand-300 hover:-translate-y-px transition-all">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0"><i data-lucide="house" class="w-4 h-4"></i></span>
            <span>
                <span class="block text-sm font-medium text-slate-700">Evacuation Centers</span>
                <span class="block text-xs text-ink-muted" x-text="reportScopedCenters.length + ' records'"></span>
            </span>
        </button>
        <button @click="exportCSV(reportScopedVulnerability, 'vulnerability-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl bg-white border border-brand-600/10 shadow-[0_1px_2px_rgb(11_23_48/.04),0_4px_12px_rgb(29_71_214/.05)] hover:border-brand-300 hover:-translate-y-px transition-all">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0"><i data-lucide="shield-alert" class="w-4 h-4"></i></span>
            <span>
                <span class="block text-sm font-medium text-slate-700">Vulnerability Assessments</span>
                <span class="block text-xs text-ink-muted" x-text="reportScopedVulnerability.length + ' records'"></span>
            </span>
        </button>
    </div>
</div>

</div>
</template>

</div>{{-- end wrapper --}}
