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
        class="text-sm border border-slate-200 rounded-2xl px-3 py-2 bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
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
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Incidents</p>
        <p class="text-2xl font-bold text-ink-body mt-1" x-text="reportScopedIncidents.length"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Incidents Resolved</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1" x-text="reportScopedIncidents.length ? Math.round(reportScopedIncidents.filter(i => ['Completed','Archived'].includes(i.status)).length / reportScopedIncidents.length * 100) + '%' : '—'"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Evacuees Sheltered</p>
        <p class="text-2xl font-bold text-ink-body mt-1" x-text="reportScopedCenters.reduce((s,c) => s + (c.individuals || 0), 0)"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Avg. Readiness Score</p>
        <p class="text-2xl font-bold text-ink-body mt-1" x-text="reportScopedVulnerability.length ? Math.round(reportScopedVulnerability.reduce((s,v) => s + (v.readiness_score || 0), 0) / reportScopedVulnerability.length) : '—'"></p>
    </div>
</div>

{{-- ── Charts (2×2) ─────────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-5">
        <h3 class="text-sm font-bold text-ink-body mb-0.5">Incident Trend</h3>
        <p class="text-xs text-ink-muted mb-4">Last 14 days · <span x-text="reportBarangayFilter || 'All Barangays'"></span></p>
        <div x-data="lineChart({ labels: reportTrendLabels(), datasets: [{ label: 'Incidents', data: reportTrendData(), color: '#2f62f5', fill: 'rgba(47,98,245,0.08)' }] })" class="relative">
            <canvas x-ref="canvas" style="height:200px"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-5">
        <h3 class="text-sm font-bold text-ink-body mb-0.5">Incidents by Type</h3>
        <p class="text-xs text-ink-muted mb-4">Current data</p>
        <template x-if="reportTypeBreakdown().length">
            <div x-data="donutChart({ labels: reportTypeBreakdown().map(t => t[0]), values: reportTypeBreakdown().map(t => t[1]), colors: ['#2f62f5','#f59e0b','#f97316','#ec4899','#22c55e','#8b5cf6','#64748b','#94a3b8','#14b8a6','#e11d48'] })" class="relative">
                <canvas x-ref="canvas" style="height:180px"></canvas>
            </div>
        </template>
        <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-3 pt-3 border-t border-slate-50">
            <template x-for="t in reportTypeBreakdown()" :key="t[0]">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-ink-muted" x-text="t[0]"></span>
                    <span class="text-xs font-semibold text-slate-700" x-text="t[1]"></span>
                </div>
            </template>
        </div>
        <p x-show="!reportTypeBreakdown().length" class="text-xs text-ink-muted italic">No incidents to chart.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-5">
        <h3 class="text-sm font-bold text-ink-body mb-0.5">Center Occupancy vs Capacity</h3>
        <p class="text-xs text-ink-muted mb-4" x-text="reportScopedCenters.length + ' center(s)'"></p>
        <template x-if="reportScopedCenters.length">
            <div x-data="barChart({ labels: reportScopedCenters.map(c => c.name), datasets: [{ label: 'Occupancy', data: reportScopedCenters.map(c => c.individuals || 0), color: '#2f62f5' }, { label: 'Capacity', data: reportScopedCenters.map(c => c.capacity || 0), color: '#e2e8f0' }], showLegend: true })" class="relative">
                <canvas x-ref="canvas" style="height:200px"></canvas>
            </div>
        </template>
        <p x-show="!reportScopedCenters.length" class="text-xs text-ink-muted italic">No centers to chart.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-5">
        <h3 class="text-sm font-bold text-ink-body mb-0.5">Readiness Score — Lowest Barangays</h3>
        <p class="text-xs text-ink-muted mb-4">Score out of 100</p>
        <template x-if="reportLowestReadiness(8).length">
            <div x-data="barChart({ horizontal: true, labels: reportLowestReadiness(8).map(v => v.barangay), values: reportLowestReadiness(8).map(v => v.readiness_score), colors: reportLowestReadiness(8).map(v => v.readiness_score < 50 ? '#f43f5e' : v.readiness_score < 70 ? '#f59e0b' : '#2f62f5'), label: 'Readiness Score' })" class="relative">
                <canvas x-ref="canvas" style="height:220px"></canvas>
            </div>
        </template>
        <p x-show="!reportLowestReadiness(8).length" class="text-xs text-ink-muted italic">No assessed barangays to chart.</p>
    </div>
</div>

{{-- ── Export ───────────────────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-5">
    <h3 class="text-sm font-bold text-ink-body mb-3">Export Data</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <button @click="exportCSV(reportScopedIncidents, 'incidents-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 transition-colors">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0"><i data-lucide="flame" class="w-4 h-4"></i></span>
            <span>
                <span class="block text-sm font-medium text-slate-700">Incidents</span>
                <span class="block text-xs text-ink-muted" x-text="reportScopedIncidents.length + ' records'"></span>
            </span>
        </button>
        <button @click="exportCSV(reportScopedCenters, 'centers-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 transition-colors">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0"><i data-lucide="house" class="w-4 h-4"></i></span>
            <span>
                <span class="block text-sm font-medium text-slate-700">Evacuation Centers</span>
                <span class="block text-xs text-ink-muted" x-text="reportScopedCenters.length + ' records'"></span>
            </span>
        </button>
        <button @click="exportCSV(reportScopedVulnerability, 'vulnerability-report.csv')"
            class="flex items-center gap-3 text-left p-3 rounded-2xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 transition-colors">
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
