@php
    // Module catalog (key/name/icon) is reference metadata for the RBAC
    // system, not a per-account business record -- the account's own real
    // moduleKeys/permissions come from GET /auth/me (see resources/js/app.js
    // $store.session), so this is only used to look up a display name/icon
    // for whichever keys that response actually grants.
    $permissionModules = config('esperanza_rbac.permission_modules');
@endphp

<x-layouts.admin title="Dashboard" subtitle="Requests, residents and alerts in your scope." active="dashboard">
    <div
        x-data="{
            ready: false,
            loading: true,
            failed: false,
            modules: @js($permissionModules),
            dash: null,
            coverage: null,
            statusColors: {
                'Draft': '#94a3b8', 'Submitted': '#3b82f6', 'Pending Review': '#f59e0b',
                'Under Verification': '#6366f1', 'Assigned': '#a855f7', 'Processing': '#2f62f5',
                'Under Review': '#f97316', 'Resubmitted': '#0ea5e9', 'Approved': '#10b981',
                'Rejected': '#f43f5e', 'Mark to Release': '#14b8a6', 'Released': '#06b6d4',
                'Completed': '#22c55e', 'Cancelled': '#94a3b8', 'Archived': '#cbd5e1',
                // Tulong's own case statuses (config/esperanza_tulong_lifecycle.php),
                // the same hues as its pages.
                'Initial Screening': '#2563eb', 'Needs Requirements': '#f59e0b', 'Assessment': '#6366f1',
                'For Approval': '#d97706', 'Returned for Reassessment': '#f97316',
                'Assistance Processing': '#a855f7', 'Ready for Release': '#14b8a6',
            },
            alertColors: {
                'Critical': 'bg-rose-50 text-rose-600', 'Warning': 'bg-orange-50 text-orange-600',
                'Watch': 'bg-amber-50 text-amber-600', 'Advisory': 'bg-teal-50 text-teal-600',
                'Information': 'bg-sky-50 text-sky-600',
            },
            get myModules() { return this.modules.filter(m => this.$store.session.can(m.key)); },
            get docStatus() {
                const by = this.dash?.requests?.documents_by_status || {};
                return Object.entries(by).map(([label, value]) => ({ label, value, color: this.statusColors[label] || '#94a3b8' }));
            },
            get docStatusTotal() { return this.docStatus.reduce((n, r) => n + r.value, 0); },
            get assistStatus() {
                const by = this.dash?.requests?.assistance_by_status || {};
                return Object.entries(by).map(([label, value]) => ({ label, value, color: this.statusColors[label] || '#94a3b8' }));
            },
            get assistStatusTotal() { return this.assistStatus.reduce((n, r) => n + r.value, 0); },
            // Open = not Released / Rejected / Completed / Cancelled / Archived. The
            // Tulong tile said Currently active over every status, 11 closed
            // cases (live test 2026-10-07, BUG-2).
            get openDokyu() { return this.dash?.requests?.open_by_type?.dokyu ?? 0; },
            get openTulong() { return this.dash?.requests?.open_by_type?.tulong ?? 0; },
            get waitingDokyu() { return this.dash?.requests?.needing_action_by_type?.dokyu ?? this.dash?.requests?.needing_action ?? 0; },
            get waitingTulong() { return this.dash?.requests?.needing_action_by_type?.tulong ?? 0; },
            get scopeLabel() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal' ? 'Across 20 barangays' : 'In Brgy. ' + s;
            },
            get barangayRequests() {
                const rows = this.dash?.requests?.by_barangay || [];
                const max = Math.max(1, ...rows.map(r => r.open));
                return rows.map(r => ({ name: r.barangay, count: r.open, dokyu: r.dokyu ?? null, tulong: r.tulong ?? null, pct: Math.round((r.open / max) * 100) }));
            },
            get registeredResidents() {
                return this.coverage?.meta?.municipal?.residents ?? this.coverage?.data?.[0]?.residents ?? null;
            },
            async init() {
                this.loading = true;
                this.failed = false;
                try {
                    const [dash, coverage] = await Promise.all([
                        $api.get('/admin/dashboard'),
                        this.$store.session.can('constituents') ? $api.get('/admin/coverage') : Promise.resolve(null),
                    ]);
                    this.dash = dash.data;
                    this.coverage = coverage;
                } catch {
                    this.failed = true;
                } finally {
                    this.loading = false;
                    setTimeout(() => (this.ready = true), 150);
                }
            },
        }"
    >

        <!-- Welcome hero — reflects the signed-in account -->
        <div x-show="$store.session.account" x-cloak class="relative rounded-2xl overflow-hidden mb-4 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-700 shadow-card animate-fade-up">
            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,theme(colors.white),transparent_55%)] pointer-events-none"></div>
            <div class="relative px-5 py-6 sm:px-7 sm:py-7 flex flex-col lg:flex-row lg:items-center gap-5">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-14 h-14 text-lg rounded-full bg-white/15 backdrop-blur border-2 border-white/40 text-white flex items-center justify-center font-semibold shadow-lg shrink-0" x-text="$store.session.initials()"></div>
                    <div class="min-w-0">
                        {{-- Today's date in the browser: now() here was the day the site was built. --}}
                        <p class="text-[11px] font-medium text-white/50 uppercase tracking-wide" x-text="new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' })"></p>
                        <h2 class="text-xl font-semibold text-white truncate">
                            <span x-text="'Good day, ' + $store.session.account?.name"></span>
                        </h2>
                        <p class="text-sm text-white/60 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span x-text="$store.session.account?.role + ' · ' + $store.session.account?.dept"></span>
                            <span class="text-white/30">·</span>
                            <span class="font-mono" x-text="$store.session.account?.id"></span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 lg:max-w-xs xl:max-w-sm lg:justify-end">
                    <span
                        class="inline-flex items-center gap-1.5 text-[11px] font-medium rounded-full px-2.5 py-1 shrink-0 bg-white/10 text-white border border-white/20"
                    >
                        <i :data-lucide="$store.session.account?.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-3 h-3"></i>
                        <span x-text="$store.session.account?.scope === 'Municipal' ? 'Municipality-wide access' : 'Brgy. ' + $store.session.account?.scope + ' only'"></span>
                    </span>
                    <template x-for="mod in myModules.slice(0, 3)" :key="mod.key">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/10 text-white/80 text-[10.5px] font-medium border border-white/10 shrink-0">
                            <i :data-lucide="mod.icon" class="w-3 h-3"></i>
                            <span x-text="mod.name"></span>
                        </span>
                    </template>
                    <span
                        x-show="myModules.length > 3"
                        x-cloak
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/10 text-white/60 text-[10.5px] font-medium border border-white/10 shrink-0"
                        x-text="'+' + (myModules.length - 3) + ' more'"
                        :title="myModules.slice(3).map(m => m.name).join(', ')"
                    ></span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-500">Live counts, refreshed each time this page opens.</p>
            <x-ui.button :href="route('admin.reports')" variant="secondary" size="sm" icon="download">Export Report</x-ui.button>
        </div>

        <!-- Skeleton state -->
        <div x-show="!ready" x-transition.opacity.duration.300ms>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 space-y-3">
                        <x-ui.skeleton class="w-10 h-10 rounded-xl" />
                        <x-ui.skeleton class="h-6 w-16" />
                        <x-ui.skeleton class="h-3 w-24" />
                    </div>
                @endfor
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                @for ($i = 0; $i < 3; $i++)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-5 space-y-4">
                        <x-ui.skeleton class="h-4 w-40" />
                        <x-ui.skeleton class="h-40 w-40 rounded-full mx-auto" />
                    </div>
                @endfor
            </div>
        </div>

        <div x-show="failed" x-cloak>
            <x-ui.error-state messageExpr="'Could not load the dashboard.'" retryExpr="init()" :retryable="true" />
        </div>

        <!-- Real content -->
        <div x-show="ready && !failed" x-cloak x-transition:enter="transition duration-[300ms] ease-standard" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            <!-- KPI row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-4">
                <div x-show="$store.session.can('constituents')" x-cloak><x-ui.stat-card :href="route('admin.constituents')" label="Registered Residents" valueBind="registeredResidents ?? '—'" icon="users" color="brand" sublabelBind="scopeLabel" :delay="0" /></div>
                <div x-show="$store.session.can('dokyu')" x-cloak><x-ui.stat-card :href="route('admin.document-requests') . '?open=1'" label="Dokyu Open" valueBind="openDokyu" icon="file-text" color="green" sublabelBind="docStatusTotal + ' in all, any status'" :delay="40" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card :href="route('admin.document-requests') . '?open=1'" label="Waiting on Staff" valueBind="($store.session.can('dokyu') ? waitingDokyu : 0) + ($store.session.can('tulong') ? waitingTulong : 0)" icon="inbox" color="orange" sublabelBind="[$store.session.can('dokyu') ? 'Dokyu ' + waitingDokyu : null, $store.session.can('tulong') ? 'Tulong ' + waitingTulong : null].filter(Boolean).join(' · ')" :delay="80" /></div>
                <div x-show="$store.session.can('tulong')" x-cloak><x-ui.stat-card :href="route('admin.assistance-requests') . '?open=1'" label="Tulong Open" valueBind="openTulong" icon="hand-heart" color="purple" sublabelBind="assistStatusTotal + ' in all, any status'" :delay="120" /></div>
                <div x-show="$store.session.can('dokyu')" x-cloak><x-ui.stat-card :href="route('admin.document-requests')" label="Released This Month" valueBind="dash?.requests?.released_this_month ?? 0" icon="package-check" color="teal" sublabel="Document requests" :delay="160" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card :href="route('admin.document-requests')" label="Submitted Today" valueBind="dash?.requests?.submitted_today ?? 0" icon="calendar-clock" color="red" sublabel="Dokyu + Tulong" :delay="200" /></div>
            </div>

            <!-- Middle row: donuts + barangay list -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <div x-show="$store.session.can('dokyu')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-navy-900">Dokyu</h3>
                        <a href="{{ route('admin.document-requests') }}" class="text-xs font-medium text-brand-600 hover:underline">View Details</a>
                    </div>
                    <div class="relative w-40 h-40 mx-auto" x-data="centerDonut()" x-effect="draw(docStatus)" @mouseleave="hover = null">
                        <canvas x-ref="canvas"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center px-7">
                            <span class="text-2xl font-semibold text-navy-900 tabular-nums" x-text="hover ? hover.value.toLocaleString() : docStatusTotal.toLocaleString()"></span>
                            <span x-show="!hover" class="text-[11px] text-slate-500">Total Requests</span>
                            <span x-show="hover" x-cloak class="inline-flex items-start gap-1 text-[11px] font-medium text-navy-900 leading-tight"><span class="w-2 h-2 rounded-full shrink-0 mt-[3px]" :style="'background-color:' + (hover?.color || '')"></span><span x-text="hover?.label"></span></span>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2.5">
                        <template x-for="row in docStatus" :key="row.label">
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-slate-500">
                                    <span class="w-2 h-2 rounded-full" :style="'background-color:' + row.color"></span>
                                    <span x-text="row.label"></span>
                                </span>
                                <span class="font-medium text-navy-900 tabular-nums" x-text="row.value.toLocaleString()"></span>
                            </div>
                        </template>
                        <p class="text-xs text-slate-500" x-show="!docStatus.length">No Dokyu requests yet.</p>
                    </div>
                </x-ui.card>
                </div>

                <div x-show="$store.session.can('tulong')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-navy-900">Tulong</h3>
                        <a href="{{ route('admin.assistance-requests') }}" class="text-xs font-medium text-brand-600 hover:underline">View Details</a>
                    </div>
                    <div class="relative w-40 h-40 mx-auto" x-data="centerDonut()" x-effect="draw(assistStatus)" @mouseleave="hover = null">
                        <canvas x-ref="canvas"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center px-7">
                            <span class="text-2xl font-semibold text-navy-900 tabular-nums" x-text="hover ? hover.value.toLocaleString() : assistStatusTotal.toLocaleString()"></span>
                            <span x-show="!hover" class="text-[11px] text-slate-500">Total Cases</span>
                            <span x-show="hover" x-cloak class="inline-flex items-start gap-1 text-[11px] font-medium text-navy-900 leading-tight"><span class="w-2 h-2 rounded-full shrink-0 mt-[3px]" :style="'background-color:' + (hover?.color || '')"></span><span x-text="hover?.label"></span></span>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2.5">
                        <template x-for="row in assistStatus" :key="row.label">
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-slate-500">
                                    <span class="w-2 h-2 rounded-full" :style="'background-color:' + row.color"></span>
                                    <span x-text="row.label"></span>
                                </span>
                                <span class="font-medium text-navy-900 tabular-nums" x-text="row.value.toLocaleString()"></span>
                            </div>
                        </template>
                        <p class="text-xs text-slate-500" x-show="!assistStatus.length">No Tulong requests yet.</p>
                    </div>
                </x-ui.card>
                </div>

                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong') || $store.session.can('constituents')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-navy-900">Open Requests by Barangay</h3>
                        {{-- It opened the Government Directory (under Balita). Each count
                             opens the list it counts: open requests, by module. --}}
                        <div class="flex items-center gap-2 text-xs font-medium">
                            <a x-show="$store.session.can('dokyu')" href="{{ route('admin.document-requests') }}?open=1" class="text-brand-600 hover:underline">Dokyu</a>
                            <a x-show="$store.session.can('tulong')" href="{{ route('admin.assistance-requests') }}?open=1" class="text-brand-600 hover:underline">Tulong</a>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <template x-for="brgy in barangayRequests" :key="brgy.name">
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="text-slate-600 font-medium" x-text="'Brgy. ' + brgy.name"></span>
                                    <span class="text-slate-500" x-text="brgy.count + (brgy.count === 1 ? ' request' : ' requests')"></span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400 transition-all duration-[600ms] ease-standard" :style="'width:' + brgy.pct + '%'"></div>
                                </div>
                            </div>
                        </template>
                        <p class="text-xs text-slate-500" x-show="!barangayRequests.length">No open requests.</p>
                    </div>
                </x-ui.card>
                </div>
            </div>

            <!-- Bottom row: trend, alerts, activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-semibold text-navy-900">Requests Trend (Last 6 Months)</h3>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500 mb-4">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Dokyu</span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-purple-500"></span>Tulong</span>
                    </div>
                    <div class="h-52" x-data="{ chart: null }" x-init="$watch('dash', async (d) => { if (!d?.requests?.trend) return; const Chart = await window.loadChart(); const canvas = $refs.canvas; if (!canvas || !canvas.isConnected) return; if (chart) { chart.destroy(); chart = null; } Chart.getChart?.(canvas)?.destroy(); const t = d.requests.trend; chart = new Chart(canvas, { type: 'line', data: { labels: t.labels, datasets: [ { label: 'Dokyu', data: t.documents, borderColor: '#2f62f5', backgroundColor: 'rgba(47,98,245,0.08)', tension: 0.4, fill: true, pointRadius: 0, borderWidth: 2.5 }, { label: 'Tulong', data: t.assistance, borderColor: '#a855f7', backgroundColor: 'rgba(168,85,247,0.06)', tension: 0.4, fill: true, pointRadius: 0, borderWidth: 2.5 } ] }, options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } } } }); }, { immediate: true })">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </x-ui.card>
                </div>

                <x-ui.card padded="false" x-show="$store.session.can('sakuna')" x-cloak>
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="font-semibold text-navy-900">Sakuna Alerts</h3>
                        <span class="text-xs font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full" x-text="(dash?.sakuna?.alerts || []).length"></span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="alert in (dash?.sakuna?.alerts || [])" :key="alert.ref">
                            <a href="{{ route('admin.sakuna.alerts') }}" class="flex items-start gap-3 px-4 py-2.5 hover:bg-slate-50/70 transition-colors">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="alertColors[alert.level] || 'bg-slate-50 text-slate-600'">
                                    <i data-lucide="triangle-alert" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="alert.title?.en"></p>
                                    <p class="text-xs text-slate-500 mt-0.5" x-text="alert.type + ' · ' + alert.level"></p>
                                    <p class="text-[11px] text-slate-300 mt-1" x-text="alert.published_at ? new Date(alert.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''"></p>
                                </div>
                            </a>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!(dash?.sakuna?.alerts || []).length">No active alerts.</p>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false">
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="font-semibold text-navy-900">Recent Activity</h3>
                        <a href="{{ route('admin.audit-logs') }}" class="text-xs font-medium text-brand-600 hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="(activity, i) in (dash?.activities || [])" :key="i">
                            <div class="flex items-start gap-3 px-4 py-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="activity" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    {{-- In words: a failed sign-in read 'null — auth.login_failed' (QA run 2026-10-05). --}}
                                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="(activity.actor || 'System') + ' — ' + (activity.summary || activity.action)"></p>
                                    <p class="text-xs text-slate-500 mt-0.5" x-text="(activity.module || '') + (activity.target ? ' · ' + activity.target : '')"></p>
                                    <p class="text-[11px] text-slate-300 mt-1" x-text="activity.at ? new Date(activity.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></p>
                                </div>
                            </div>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!(dash?.activities || []).length">No recent activity.</p>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.admin>
