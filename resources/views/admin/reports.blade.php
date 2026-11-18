@php
    $tab = $tab ?? 'reports';
@endphp

<x-layouts.admin title="Reports & Analytics" subtitle="Generate reports and explore municipal service insights." active="reports">
    <div
        x-data="{
            tab: '{{ $tab }}',
            loading: true,
            failed: false,
            reports: [],
            printable: null,
            runs: [],
            generating: null,
            dash: null,
            docSummary: null,
            assistSummary: null,
            satisfaction: null,
            internalForms: null,
            sakunaIncidents: null,
            barangayActivity: null,
            revenue: null,
            meta: {
                document_requests_summary: { icon: 'file-text', color: 'text-brand-600 bg-brand-50', desc: 'Volume, processing time, and status breakdown.' },
                assistance_programs: { icon: 'hand-heart', color: 'text-purple-600 bg-purple-50', desc: 'Volumes and processing time per program.' },
                resident_profiling_coverage: { icon: 'user-round-search', color: 'text-emerald-600 bg-emerald-50', desc: 'Completion rate per barangay.' },
                revenue_and_fees: { icon: 'wallet', color: 'text-gold-700 bg-gold-50', desc: 'Assessed vs. collected fees.' },
                barangay_activity: { icon: 'map-pin', color: 'text-indigo-600 bg-indigo-50', desc: 'Requests and residents by barangay.' },
                citizen_satisfaction: { icon: 'smile', color: 'text-rose-600 bg-rose-50', desc: 'Citizen feedback from completed requests.' },
                internal_forms_submissions: { icon: 'clipboard-list', color: 'text-indigo-600 bg-indigo-50', desc: 'Staff form activity by office.' },
                sakuna_incident_reports: { icon: 'triangle-alert', color: 'text-orange-600 bg-orange-50', desc: 'Disaster incident volume and severity by barangay.' },
            },
            officeColors: { MHRMO: '#2f62f5', MGSO: '#f59e0b', Agriculture: '#22c55e', MSWD: '#a855f7' },
            severityColors: { Critical: '#e11d48', High: '#f97316', Medium: '#f59e0b', Low: '#64748b' },
            get mayRuns() { return this.$store.session.canPerm('rep_population', 'view'); },
            async init() {
                this.loading = true;
                this.failed = false;
                try {
                    // Only what this account may read: a refused call shows a
                    // permission notice (Department Staff, 2026-10-28).
                    const [reportsRes, runsRes] = await Promise.all([
                        $api.get('/admin/reports'),
                        this.mayRuns ? $api.get('/admin/reports/runs', { params: { per_page: 10 } }).catch(() => ({ data: [] })) : Promise.resolve({ data: [] }),
                    ]);
                    this.reports = reportsRes.data || [];
                    this.printable = reportsRes.meta?.printable || null;
                    this.runs = runsRes.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
                // The analytics' eight report reads only when Analytics is
                // opened (2026-11-03): on every visit they used up the report
                // rate limit (20 a minute) before a report was even previewed.
                if (this.tab === 'analytics') this.loadAnalytics();
                this.$watch('tab', (t) => { if (t === 'analytics' && !this.analyticsLoaded) this.loadAnalytics(); });
            },
            analyticsLoaded: false,
            async loadAnalytics() {
                this.analyticsLoaded = true;
                // GET /admin/reports lists only the reports this account may view.
                const get = (key) => (this.reports.some((r) => r.key === key) ? $api.get('/admin/reports/' + key).then(r => r.data).catch(() => null) : Promise.resolve(null));
                const [dash, doc, assist, sat, forms, sakuna, brgy, rev] = await Promise.all([
                    $api.get('/admin/dashboard').then(r => r.data).catch(() => null),
                    get('document_requests_summary'), get('assistance_programs'), get('citizen_satisfaction'),
                    get('internal_forms_submissions'), get('sakuna_incident_reports'), get('barangay_activity'),
                    get('revenue_and_fees'),
                ]);
                this.dash = dash; this.docSummary = doc; this.assistSummary = assist; this.satisfaction = sat;
                this.internalForms = forms; this.sakunaIncidents = sakuna; this.barangayActivity = brgy; this.revenue = rev;
            },
            async generate(key, format = 'CSV') {
                if (this.generating) return;
                this.generating = key;
                try {
                    // The run used to be recorded and nothing handed over: no file,
                    // nothing to click (live test 2026-10-07, BUG-8). The rows it
                    // returns are saved as a CSV the officer can open in Excel.
                    // Or as a PDF (owner, 2026-10-15), recorded as a PDF run.
                    const res = await $api.post('/admin/reports/' + key + '/export', { format });
                    if (format === 'PDF') await this.savePdf(res.data); else this.saveCsv(res.data);
                    this.runs.unshift({
                        id: res.data.run_id, key: res.data.key, title: res.data.title,
                        by: this.$store.session.account?.name || 'You', scope: res.data.scope?.geographic,
                        format: res.data.format, row_count: res.data.row_count, status: res.data.status,
                        generated: res.data.generated_at,
                    });
                    this.$dispatch('toast', { message: res.data.title + ' generated.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not generate the report.', variant: 'error' });
                } finally { this.generating = null; }
            },
            savePdf(data) {
                const rows = Array.isArray(data?.rows) ? data.rows : [];
                const totals = data?.totals && typeof data.totals === 'object'
                    ? 'Totals: ' + Object.entries(data.totals).map(([k, v]) => k.replace(/_/g, ' ') + ' ' + v).join(', ')
                    : '';
                return window.tablePdf(rows.length ? rows : [{ note: 'No rows for this period.' }], {
                    filename: (data?.key || 'report') + '-' + new Date().toISOString().slice(0, 10) + '.pdf',
                    title: data?.title || 'Report',
                    subtitle: totals,
                    // The executive summary the server wrote (2026-11-03).
                    summary: Array.isArray(data?.summary) ? data.summary : [],
                });
            },
            saveCsv(data) {
                const rows = Array.isArray(data?.rows) ? data.rows : [];
                const flat = (v) => (v !== null && typeof v === 'object') ? JSON.stringify(v) : (v ?? '');
                const q = String.fromCharCode(34);
                const cell = (v) => { const t = String(flat(v)); return [',', q, String.fromCharCode(10), String.fromCharCode(13)].some((c) => t.includes(c)) ? q + t.split(q).join(q + q) + q : t; };
                const columns = [...new Set(rows.flatMap((r) => Object.keys(r || {})))];
                const lines = [columns.map(cell).join(',')].concat(rows.map((r) => columns.map((c) => cell(r?.[c])).join(',')));
                if (data?.totals && typeof data.totals === 'object') {
                    lines.push('');
                    Object.entries(data.totals).forEach(([k, v]) => lines.push(cell('Total: ' + k) + ',' + cell(v)));
                }
                const blob = new Blob([String.fromCharCode(0xFEFF) + lines.join(String.fromCharCode(13, 10))], { type: 'text/csv;charset=utf-8' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = (data?.key || 'report') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(a); a.click(); a.remove();
                setTimeout(() => URL.revokeObjectURL(a.href), 4000);
            },
            // Weighted by how many each kind released (2026-11-03: the plain mean
            // of the two means overweighted the smaller one).
            get avgResolutionDays() {
                const parts = [this.docSummary?.totals, this.assistSummary?.totals]
                    .filter((t) => t && t.avg_days_to_release !== null && t.avg_days_to_release !== undefined && t.released > 0);
                const released = parts.reduce((n, t) => n + t.released, 0);
                return released ? parts.reduce((sum, t) => sum + t.avg_days_to_release * t.released, 0) / released : null;
            },
            // 0.0 days read as a fault: under half a working day is the same day.
            get avgResolutionText() {
                const d = this.avgResolutionDays;
                if (d === null) return '—';
                if (d < 0.5) return 'Same day';
                return d.toFixed(1) + (d.toFixed(1) === '1.0' ? ' day' : ' days');
            },
            get totalRequests() { return (this.docSummary?.totals?.requests || 0) + (this.assistSummary?.totals?.requests || 0); },
            get trendData() {
                const t = this.dash?.requests?.trend;
                if (!t) return { labels: [], series: [] };
                return { labels: t.labels || [], series: [
                    { label: 'Dokyu', values: t.documents || [], color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.38 },
                    { label: 'Tulong', values: t.assistance || [], color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.45 },
                ] };
            },
            get incidentSeveritySplit() {
                if (!this.sakunaIncidents) return [];
                const totals = {};
                this.sakunaIncidents.rows.forEach(r => { Object.entries(r.by_severity || {}).forEach(([sev, n]) => { totals[sev] = (totals[sev] || 0) + n; }); });
                return Object.entries(totals).map(([label, value]) => ({ label, value, color: this.severityColors[label] || '#64748b' }));
            },
            get incidentTotal() { return this.sakunaIncidents?.totals?.incidents || 0; },
            get criticalHighCount() { return this.incidentSeveritySplit.filter(s => s.label === 'Critical' || s.label === 'High').reduce((sum, s) => sum + s.value, 0); },
            get internalOfficeSplit() {
                if (!this.internalForms) return [];
                return this.internalForms.rows.map(r => ({ label: r.office, value: r.submissions, color: this.officeColors[r.office] || '#64748b' }));
            },
            get officesCovered() { return this.internalForms?.rows?.length || 0; },
            get barangayRequestRows() {
                if (!this.barangayActivity) return [];
                const rows = this.barangayActivity.rows.map(r => ({ name: r.barangay, requests: r.dokyu + r.tulong }))
                    .filter(r => this.$store.session.inScope(r.name));
                const max = Math.max(1, ...rows.map(r => r.requests));
                // The barangays with requests, busiest first; those with none are
                // counted in one line under the bars (2026-11-03).
                return rows.filter((r) => r.requests > 0).map(r => ({ ...r, pct: Math.round((r.requests / max) * 100) })).sort((a, b) => b.requests - a.requests).slice(0, 8);
            },
            get barangaysWithout() {
                if (!this.barangayActivity) return 0;
                return this.barangayActivity.rows.filter((r) => this.$store.session.inScope(r.barangay) && !(r.dokyu + r.tulong)).length;
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('reports')" x-cloak>
            <x-admin.access-restricted module="Reports & Analytics" icon="chart-column" />
        </div>

        <div x-show="$store.session.can('reports')" x-cloak>

        <div x-show="$store.session.account && $store.session.account.scope !== 'Municipal'" x-cloak class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="map-pin" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Reports below are scoped to your assigned barangay where applicable.</p>
        </div>

        <div class="flex items-center gap-1.5 surface-pressed-sm rounded-2xl p-1 w-fit mb-4">
            <button @click="tab = 'reports'" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="tab === 'reports' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="file-chart-column" class="w-3.5 h-3.5"></i>Reports</button>
            <button @click="tab = 'analytics'" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="tab === 'analytics' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i>Analytics</button>
        </div>

        <!-- Reports -->
        <div x-show="tab === 'reports'">
            <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-4">
                <x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /><x-ui.skeleton class="h-24 opacity-60" />
            </div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load reports.'" retryExpr="init()" :retryable="true" /></div>

            <div x-show="!loading && !failed">

            {{-- The standard printed reports of an office or a barangay (owner,
                 2026-10-14): the LGU's report format, previewed, printed or
                 saved to Excel (resources/js/dashboards.js printableReports). --}}
            <div x-data="printableReports()" x-effect="printable && setCatalog(printable)" x-show="ready && catalog.reports.length" x-cloak class="mb-6">
                <h3 class="text-base font-extrabold text-ink mb-1">Printable Reports</h3>
                <p class="text-[13px] text-ink-muted mb-3">The office and barangay reports in the LGU's format: the letterhead, an executive summary, the figures with graphs and totals, and the signature lines. Choose the period, then the office or barangay, and open a report to preview, print, or save it as PDF or Excel.</p>
                <x-ui.card>
                    {{-- The period first: it applies to every report below. Each
                         group then has its own picker beside its reports (2026-11-03). --}}
                    <div class="flex flex-wrap items-end gap-x-3 gap-y-1 mb-5">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Period</span>
                            <x-admin.dashboards.period-picker />
                        </div>
                        <p class="text-[12px] text-ink-muted pb-2">Applies to every report below.</p>
                    </div>

                    <template x-for="group in [{ key: 'office', title: 'Office reports', who: officeEntry ? (officeEntry.short || officeEntry.name) : '', rows: office ? departmentReports : [] }, { key: 'barangay', title: 'Barangay reports', who: barangay ? 'Brgy. ' + barangay : '', rows: barangay ? barangayReports : [] }]" :key="group.title">
                        <div x-show="group.rows.length" class="mb-6 last:mb-0">
                            <div class="flex flex-wrap items-end gap-x-3 gap-y-2 mb-3 pb-3 border-b border-brand-600/10">
                                <p class="w-full text-[13px] font-extrabold text-ink"><span x-text="group.title"></span><span class="text-ink-muted font-semibold" x-text="group.who ? ' · ' + group.who : ''"></span></p>
                                {{-- x-if, not x-show: each picker exists once (one #pr-office on the page). --}}
                                <template x-if="group.key === 'office'">
                                {{-- As wide as the office's full name, up to the row, and an
                                     ellipsis past that on a phone (UI sweep 2026-11-18: 260 px
                                     cut 'MSWDO · Municipal Social Welfare &' with no ellipsis). --}}
                                <div x-show="catalog.offices.length > 1" class="flex flex-col gap-1 min-w-0 max-w-full">
                                    <label for="pr-office" class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Office</label>
                                    <select id="pr-office" x-model="office" @change="service = ''" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-full text-ellipsis">
                                        <template x-for="o in catalog.offices" :key="o.name">
                                            <option :value="o.name" x-text="(o.short ? o.short + ' · ' : '') + o.name" :selected="o.name === office"></option>
                                        </template>
                                    </select>
                                </div>
                                </template>
                                <template x-if="group.key === 'barangay'">
                                <div x-show="catalog.barangays.length > 1" class="flex flex-col gap-1">
                                    <label for="pr-barangay" class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Barangay</label>
                                    <select id="pr-barangay" x-model="barangay" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                        <template x-for="b in catalog.barangays" :key="b">
                                            <option :value="b" x-text="'Brgy. ' + b" :selected="b === barangay"></option>
                                        </template>
                                    </select>
                                </div>
                                </template>
                        {{-- An office's reports narrow as its dashboard does (2026-10-28). --}}
                        <template x-if="group.key === 'office'">
                        <div class="flex flex-col gap-1 min-w-0 max-w-full">
                            <span class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Narrowed to</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <label class="sr-only" for="pr-service">Program or document</label>
                                <select id="pr-service" x-model="service" x-show="services.length > 1" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-[240px] text-ellipsis">
                                    <option value="">All programs and documents</option>
                                    <template x-for="o in services" :key="o.key">
                                        <option :value="o.key" x-text="o.name" :selected="o.key === service"></option>
                                    </template>
                                </select>
                                <label class="sr-only" for="pr-sex">Sex</label>
                                <select id="pr-sex" x-model="sex" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <option value="">Any sex</option>
                                    <template x-for="o in sexOptions" :key="o.value"><option :value="o.value" x-text="o.label" :selected="o.value === sex"></option></template>
                                </select>
                                <label class="sr-only" for="pr-age">Age group</label>
                                <select id="pr-age" x-model="age" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <option value="">Any age</option>
                                    <template x-for="o in ageOptions" :key="o.value"><option :value="o.value" x-text="o.label" :selected="o.value === age"></option></template>
                                </select>
                                <label class="sr-only" for="pr-sector">Sector</label>
                                <select id="pr-sector" x-model="sector" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <option value="">Any sector</option>
                                    <template x-for="o in sectorOptions" :key="o.value"><option :value="o.value" x-text="o.label" :selected="o.value === sector"></option></template>
                                </select>
                                <button type="button" x-show="anyFilter()" x-cloak @click="service = ''; sex = ''; age = ''; sector = ''" class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-[12.5px] font-semibold text-brand-600 hover:bg-brand-50"><i data-lucide="x" class="w-3.5 h-3.5"></i>Clear</button>
                            </div>
                        </div>
                        </template>
                            </div>

                            <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                                <template x-for="r in group.rows" :key="r.key">
                                    <button type="button" @click="mayOpen(r) && open(r)" :disabled="!mayOpen(r)" class="flex items-start text-left rounded-[18px] bg-white/60 border border-white/80 px-4 py-3.5 shadow-glass-sm hover:-translate-y-0.5 transition-transform duration-200 disabled:opacity-55 disabled:hover:translate-y-0">
                                        <span class="flex items-start gap-3">
                                            <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 bg-brand-50 text-brand-600"><i :data-lucide="info(r.key).icon" class="w-5 h-5"></i></span>
                                            <span class="min-w-0">
                                                <span class="block text-[14px] font-bold text-ink" x-text="titleOf(r)"></span>
                                                <span class="block text-[12px] text-ink-muted mt-0.5" x-text="info(r.key).desc"></span>
                                                <span class="block text-[11.5px] font-semibold mt-1.5" :class="mayOpen(r) ? 'text-brand-600' : 'text-ink-faint'" x-text="mayOpen(r) ? 'Preview, print or save as PDF' : 'Department Head and administrators only'"></span>
                                            </span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </x-ui.card>
            </div>

            <h3 class="text-base font-extrabold text-ink mb-1">Data Exports</h3>
            <p class="text-[13px] text-ink-muted mb-3">The raw figures across the municipality, as a CSV file for Excel or as a PDF table. Each copy is recorded under your name.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-4">
                <template x-for="(report, i) in reports" :key="report.key">
                    <x-ui.card
                        class="group cursor-pointer animate-fade-up"
                        ::style="'animation-delay:' + (Math.min(i, 2) * 40) + 'ms'"
                        @click="report.can_export ? generate(report.key) : $dispatch('toast', { message: 'You do not have export permission for this report.', variant: 'error' })"
                    >
                        <div class="flex items-start gap-3.5">
                            <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-[220ms] ease-standard group-hover:scale-110" :class="(meta[report.key] || {}).color || 'text-slate-600 bg-slate-50'">
                                <i :data-lucide="(meta[report.key] || {}).icon || 'file'" class="w-5 h-5"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink" x-text="report.title"></p>
                                <p class="text-xs text-ink-muted mt-1" x-text="(meta[report.key] || {}).desc || ''"></p>
                            </div>
                        </div>
                        <div class="mt-3.5 pt-3.5 border-t border-brand-600/10 flex items-center gap-1.5 text-xs font-medium" :class="report.can_export ? 'text-brand-600' : 'text-ink-faint'">
                            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="generating === report.key"></i>
                            <i data-lucide="download" class="w-3.5 h-3.5" x-show="generating !== report.key"></i>
                            <span x-text="generating === report.key ? 'Generating…' : (report.can_export ? 'Generate Report (CSV)' : 'View only — no export permission')"></span>
                            <button type="button" x-show="report.can_export && generating !== report.key" @click.stop="generate(report.key, 'PDF')" class="ml-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-brand-600 hover:bg-brand-50" :aria-label="'Generate ' + report.title + ' as a PDF'"><i data-lucide="file-down" class="w-3.5 h-3.5"></i>PDF</button>
                        </div>
                    </x-ui.card>
                </template>
            </div>

            <div x-show="mayRuns">
            <h3 class="text-base font-extrabold text-ink mb-3">Recently Generated</h3>
            <x-ui.table>
                <thead>
                    <tr class="bg-well/70">
                        <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Report</th>
                        <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell">Generated By</th>
                        <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell">Date</th>
                        <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Format</th>
                        <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden md:table-cell">Rows</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Download</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="r in runs" :key="r.id">
                        <tr class="hover:bg-white/60 transition-colors">
                            <td class="px-4 py-3 text-slate-700 font-medium" x-text="r.title"></td>
                            <td class="px-4 py-3 text-ink-muted hidden sm:table-cell" x-text="r.by"></td>
                            <td class="px-4 py-3 text-ink-muted hidden sm:table-cell" x-text="r.generated ? new Date(r.generated).toLocaleDateString() : '—'"></td>
                            <td class="px-4 py-3"><span class="text-[11px] font-semibold text-ink-muted bg-slate-100 px-2 py-0.5 rounded-md" x-text="r.format"></span></td>
                            <td class="px-4 py-3 text-ink-muted hidden md:table-cell" x-text="r.row_count"></td>
                            <td class="px-4 py-3 text-right">
                                {{-- A fresh copy of the same report, as of now: each download is its own audited run. --}}
                                <button type="button" @click="generate(r.key, r.format === 'PDF' ? 'PDF' : 'CSV')" x-show="r.key && (reports.find((x) => x.key === r.key) || {}).can_export" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline" :disabled="!!generating">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>Download again
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!runs.length"><td colspan="6" class="px-4 py-6 text-center text-xs text-ink-muted">No reports generated yet.</td></tr>
                </tbody>
            </x-ui.table>
            </div>
            </div>
        </div>

        <!-- Analytics -->
        <div x-show="tab === 'analytics'" x-cloak>
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(160px,1fr))] mb-5">
                <x-ui.stat-card layout="compact" class="h-full" label="Avg. Satisfaction Score" valueBind="satisfaction?.totals?.average_rating ? (satisfaction.totals.average_rating + ' / 5') : '—'" icon="smile" color="brand" sublabelBind="satisfaction?.totals?.responses ? (satisfaction.totals.responses + ' responses') : (satisfaction?.totals?.note || '')" :delay="0" />
                <x-ui.stat-card layout="compact" class="h-full" label="Total Requests" valueBind="totalRequests.toLocaleString()" icon="inbox" color="light" sublabel="Dokyu + Tulong, all time" :delay="40" />
                <x-ui.stat-card layout="compact" class="h-full" label="Avg. Resolution Time" valueBind="avgResolutionText" icon="timer" color="deep" sublabel="Working days, filing to release" :delay="80" />
                <x-ui.stat-card layout="compact" class="h-full" label="Incidents Logged" valueBind="incidentTotal" icon="triangle-alert" color="orange" :delay="120" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start mb-5">
                <x-ui.card class="lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Requests trend</h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span>Last 6 months</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Dokyu</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Tulong</span>
                        </p>
                    </div>
                    <x-ui.clay-trend data="trendData" label="Dokyu and Tulong requests filed in each of the last six months" />
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-lg font-extrabold text-ink mb-4">Revenue &amp; Fees</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-muted">Assessed</span>
                            <span class="font-semibold text-ink tabular-nums" x-text="'₱' + Number(revenue?.totals?.assessed || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-muted">Collected</span>
                            <span class="font-semibold text-emerald-600 tabular-nums" x-text="'₱' + Number(revenue?.totals?.collected || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-muted">Outstanding</span>
                            <span class="font-semibold text-orange-600 tabular-nums" x-text="'₱' + Number(revenue?.totals?.outstanding || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm pt-3 border-t border-brand-600/10">
                            <span class="text-ink-muted">Receipts Issued</span>
                            <span class="font-semibold text-ink tabular-nums" x-text="revenue?.totals?.receipts || 0"></span>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card>
                <h3 class="text-lg font-extrabold text-ink mb-4">Requests by Barangay</h3>
                <div class="space-y-4">
                    <template x-for="b in barangayRequestRows" :key="b.name">
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <span class="text-[14.5px] font-semibold text-ink-body truncate" x-text="'Brgy. ' + b.name"></span>
                                <span class="text-sm text-ink-muted shrink-0" x-text="b.requests + (b.requests === 1 ? ' request' : ' requests')"></span>
                            </div>
                            <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600 transition-all duration-[600ms] ease-standard" :style="'width:' + b.pct + '%'"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="!barangayRequestRows.length" class="surface-pressed rounded-2xl px-4 py-5 text-center text-[14.5px] font-bold text-ink">No requests on file yet.</div>
                    <p x-show="barangaysWithout" x-cloak class="text-[12.5px] text-ink-muted" x-text="barangaysWithout + (barangaysWithout === 1 ? ' barangay has' : ' barangays have') + ' no requests on file.'"></p>
                </div>
            </x-ui.card>

        {{-- Analytics only (2026-11-03): it showed under the Reports tab too. --}}
        <h3 class="text-base font-extrabold text-ink mb-3 mt-6">Internal Forms &amp; Incident Reports</h3>
        <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(160px,1fr))] mb-5">
            <x-ui.stat-card layout="compact" class="h-full" label="Internal Forms Submitted" valueBind="internalForms?.totals?.submissions ?? 0" icon="clipboard-list" color="light" :delay="0" />
            <x-ui.stat-card layout="compact" class="h-full" label="Offices Covered" valueBind="officesCovered" icon="building-2" color="slate" :delay="40" />
            <x-ui.stat-card layout="compact" class="h-full" label="Incidents Logged" valueBind="incidentTotal" icon="triangle-alert" color="orange" :delay="80" />
            <x-ui.stat-card layout="compact" class="h-full" label="Critical / High Severity" valueBind="criticalHighCount" icon="siren" color="red" :delay="120" />
        </div>

        <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start mb-5">
            <x-ui.card>
                <h3 class="text-lg font-extrabold text-ink mb-4">Internal Forms by Office</h3>
                <x-ui.clay-donut rows="internalOfficeSplit" total-label="Submissions" empty="No submissions yet." />
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-lg font-extrabold text-ink mb-4">Incident Reports by Severity</h3>
                <x-ui.clay-donut rows="incidentSeveritySplit" total-label="Incidents" empty="No incidents logged yet." :keep-colors="true" />
            </x-ui.card>
        </div>
        </div>

        </div>
        <x-admin.report-sheet />
    </div>
</x-layouts.admin>
