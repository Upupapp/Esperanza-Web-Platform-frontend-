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
            // Open requests by barangay, for the module the switch shows (README
            // 'Open requests by barangay'); the bar is relative to the busiest.
            // An account with neither module (Constituents only) sees all open.
            brgyTab: $store.session.can('dokyu') ? 'dokyu' : ($store.session.can('tulong') ? 'tulong' : 'all'),
            get barangayRows() {
                const key = this.brgyTab;
                const rows = (this.dash?.requests?.by_barangay || [])
                    .map(r => ({ name: r.barangay, count: (key === 'dokyu' ? (r.dokyu ?? r.open) : key === 'tulong' ? r.tulong : r.open) ?? 0 }))
                    .filter(r => r.count > 0);
                const max = Math.max(1, ...rows.map(r => r.count));
                return rows.map(r => ({ ...r, pct: Math.round((r.count / max) * 100) }));
            },
            get trendData() {
                const t = this.dash?.requests?.trend;
                if (!t) return { labels: [], series: [] };
                return { labels: t.labels || [], series: [
                    { label: 'Dokyu', values: t.documents || [], color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.38 },
                    { label: 'Tulong', values: t.assistance || [], color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.45 },
                ] };
            },
            // One icon per kind of action (README 'Recent activity').
            activityIcon(a) {
                const k = String(a.action || '').toLowerCase();
                if (k.includes('login') || k.includes('sign')) return 'log-in';
                if (k.includes('publish') || k.includes('alert')) return 'bell-ring';
                if (k.includes('draft') || k.includes('creat') || k.includes('updat') || k.includes('edit')) return 'pencil-line';
                if (k.includes('read') || k.includes('view')) return 'eye';
                return 'activity';
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
        class="flex flex-col gap-[22px]"
    >

        {{-- Clay design, the staff dashboard (design_handoff_dashboard_clay/README.md):
             greeting banner, live KPI coins, doughnuts, open requests by barangay,
             the six-month trend, Sakuna alerts and recent activity. --}}

        <!-- Greeting banner — reflects the signed-in account -->
        <div x-show="$store.session.account" x-cloak class="hero-banner animate-fade-up px-6 py-7 sm:px-[34px] sm:py-[30px]">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                <div class="flex items-center gap-4 sm:gap-5 flex-1 min-w-0">
                    <div class="w-14 h-14 sm:w-[72px] sm:h-[72px] rounded-full clay-gold grid place-items-center text-xl sm:text-2xl font-extrabold shrink-0" x-text="$store.session.initials()"></div>
                    <div class="min-w-0">
                        {{-- Today's date in the browser: now() here was the day the site was built. --}}
                        <p class="text-xs font-bold uppercase tracking-[.1em] text-gold-400" x-text="new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' })"></p>
                        <h2 class="mt-1 text-[22px] sm:text-[28px] font-extrabold leading-tight tracking-[-.01em] text-white text-balance sm:truncate">
                            <span x-text="'Good day, ' + $store.session.account?.name"></span>
                        </h2>
                        <p class="mt-1 text-[15px] text-[#dce5ff] flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span x-text="$store.session.account?.role + ' · ' + $store.session.account?.dept"></span>
                            <span class="text-white/40">·</span>
                            <span class="font-mono text-[13.5px]" x-text="$store.session.account?.id"></span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end lg:basis-[460px] lg:shrink">
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-[12.5px] font-semibold text-white bg-white/14 backdrop-blur-[10px] border border-white/28">
                        <i :data-lucide="$store.session.account?.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-[13px] h-[13px]"></i>
                        <span x-text="$store.session.account?.scope === 'Municipal' ? 'Municipality-wide access' : 'Brgy. ' + $store.session.account?.scope + ' only'"></span>
                    </span>
                    <template x-for="mod in myModules.slice(0, 3)" :key="mod.key">
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-[12.5px] font-semibold text-white bg-white/14 backdrop-blur-[10px] border border-white/28">
                            <i :data-lucide="mod.icon" class="w-[13px] h-[13px]"></i>
                            <span x-text="mod.name"></span>
                        </span>
                    </template>
                    <span
                        x-show="myModules.length > 3"
                        x-cloak
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-[12.5px] font-semibold text-white/85 bg-white/14 backdrop-blur-[10px] border border-white/28"
                        x-text="'+' + (myModules.length - 3) + ' more'"
                        :title="myModules.slice(3).map(m => m.name).join(', ')"
                    ></span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[14.5px] text-ink-body">Live counts, refreshed each time this page opens.</p>
            <x-ui.button :href="route('admin.reports')" icon="download">Export Report</x-ui.button>
        </div>

        <!-- Skeleton state -->
        <div x-show="!ready" x-transition.opacity.duration.300ms class="flex flex-col gap-5">
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                @for ($i = 0; $i < 6; $i++)
                    <div class="surface-glass rounded-[22px] min-h-[136px] p-4 space-y-3">
                        <x-ui.skeleton class="h-3 w-24" />
                        <x-ui.skeleton class="h-9 w-16" />
                        <x-ui.skeleton class="h-6 w-28 rounded-full" />
                    </div>
                @endfor
            </div>
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                @for ($i = 0; $i < 3; $i++)
                    <div class="surface-glass rounded-[26px] p-5 space-y-4">
                        <x-ui.skeleton class="h-4 w-40" />
                        <x-ui.skeleton class="h-[200px] w-[200px] rounded-full mx-auto" />
                    </div>
                @endfor
            </div>
        </div>

        <div x-show="failed" x-cloak>
            <x-ui.error-state messageExpr="'Could not load the dashboard.'" retryExpr="init()" :retryable="true" />
        </div>

        <!-- Real content -->
        <div x-show="ready && !failed" x-cloak class="flex flex-col gap-[22px]" x-transition:enter="transition duration-[300ms] ease-standard" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            <!-- KPI cards: all six in one row on a wide screen (owner, 2026-10-09), so compact -->
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <div x-show="$store.session.can('constituents')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.constituents')" label="Registered Residents" valueBind="registeredResidents ?? '—'" icon="users" color="brand" sublabelBind="scopeLabel" :delay="0" /></div>
                <div x-show="$store.session.can('dokyu')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests') . '?open=1'" label="Dokyu Open" valueBind="openDokyu" icon="file-text" color="light" sublabelBind="docStatusTotal + ' in all, any status'" :delay="40" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests') . '?open=1'" label="Waiting on Staff" valueBind="($store.session.can('dokyu') ? waitingDokyu : 0) + ($store.session.can('tulong') ? waitingTulong : 0)" icon="inbox" color="orange" sublabelBind="[$store.session.can('dokyu') ? 'Dokyu ' + waitingDokyu : null, $store.session.can('tulong') ? 'Tulong ' + waitingTulong : null].filter(Boolean).join(' · ')" :delay="80" /></div>
                <div x-show="$store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.assistance-requests') . '?open=1'" label="Tulong Open" valueBind="openTulong" icon="hand-heart" color="deep" sublabelBind="assistStatusTotal + ' in all, any status'" :delay="120" /></div>
                <div x-show="$store.session.can('dokyu')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests')" label="Released This Month" valueBind="dash?.requests?.released_this_month ?? 0" icon="package-check" color="slate" sublabel="Document requests" :delay="160" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests')" label="Submitted Today" valueBind="dash?.requests?.submitted_today ?? 0" icon="calendar-clock" color="gold" sublabel="Dokyu + Tulong" :delay="200" /></div>
            </div>

            <!-- Row 1: doughnuts + barangays -->
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start">
                <div x-show="$store.session.can('dokyu')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Dokyu</h3>
                        <a href="{{ route('admin.document-requests') }}" class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View details</a>
                    </div>
                    <x-ui.clay-donut rows="docStatus" total-label="Total requests" empty="No Dokyu requests yet." />
                </x-ui.card>
                </div>

                <div x-show="$store.session.can('tulong')" x-cloak>
                <x-ui.card>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Tulong</h3>
                        <a href="{{ route('admin.assistance-requests') }}" class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View details</a>
                    </div>
                    <x-ui.clay-donut rows="assistStatus" total-label="Total cases" empty="No Tulong requests yet." />
                </x-ui.card>
                </div>

                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong') || $store.session.can('constituents')" x-cloak>
                <x-ui.card>
                    <div class="flex flex-col items-start gap-3 mb-5">
                        <h3 class="text-lg font-extrabold text-ink leading-tight">Open requests by barangay</h3>
                        <div x-show="$store.session.can('dokyu') && $store.session.can('tulong')" class="inline-flex p-1 rounded-xl surface-pressed-sm shrink-0" role="tablist" aria-label="Module">
                            <button type="button" role="tab" x-show="$store.session.can('dokyu')" :aria-selected="brgyTab === 'dokyu'" @click="brgyTab = 'dokyu'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'dokyu' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_3px_rgb(11_23_48/.08),inset_0_1px_0_#fff]' : 'font-semibold text-ink-muted hover:text-ink'">Dokyu</button>
                            <button type="button" role="tab" x-show="$store.session.can('tulong')" :aria-selected="brgyTab === 'tulong'" @click="brgyTab = 'tulong'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'tulong' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_3px_rgb(11_23_48/.08),inset_0_1px_0_#fff]' : 'font-semibold text-ink-muted hover:text-ink'">Tulong</button>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <template x-for="brgy in barangayRows" :key="brgy.name">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <span class="text-[14.5px] font-semibold text-ink-body truncate" x-text="'Brgy. ' + brgy.name"></span>
                                    <span class="text-sm text-ink-muted shrink-0" x-text="brgy.count + (brgy.count === 1 ? ' request' : ' requests')"></span>
                                </div>
                                <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600 shadow-[inset_0_1px_0_rgb(255_255_255/.3)] transition-all duration-[600ms] ease-standard" :style="'width:' + brgy.pct + '%'"></div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!barangayRows.length" class="surface-pressed rounded-2xl px-4 py-5 text-center">
                            <p class="text-[14.5px] font-bold text-ink" x-text="{ dokyu: 'No open Dokyu requests', tulong: 'No open Tulong requests' }[brgyTab] || 'No open requests'"></p>
                            <p class="mt-1 text-[12.5px] text-ink-muted">Every request in your scope is released, completed or closed.</p>
                        </div>
                        {{-- The open requests of the module shown, one click away. --}}
                        <a x-show="brgyTab !== 'all'" :href="brgyTab === 'dokyu' ? '{{ route('admin.document-requests') }}?open=1' : '{{ route('admin.assistance-requests') }}?open=1'" class="inline-flex items-center gap-1 text-[13.5px] font-bold text-brand-600 hover:text-brand-700">
                            <span x-text="brgyTab === 'dokyu' ? 'Open Dokyu requests' : 'Open Tulong requests'"></span><i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </x-ui.card>
                </div>
            </div>

            <!-- Row 2: trend, alerts, activity -->
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start">
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak>
                <x-ui.card>
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
                </div>

                <x-ui.card x-show="$store.session.can('sakuna')" x-cloak>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Sakuna alerts</h3>
                        <span class="min-w-[26px] h-[26px] px-2 rounded-full clay-gold grid place-items-center text-[12.5px] font-extrabold" x-text="(dash?.sakuna?.alerts || []).length"></span>
                    </div>
                    <div class="space-y-3">
                        <template x-for="alert in (dash?.sakuna?.alerts || [])" :key="alert.ref">
                            <a href="{{ route('admin.sakuna.alerts') }}" class="flex items-start gap-3 rounded-[18px] bg-white/55 border border-white/80 px-3.5 py-3 shadow-glass-sm hover:-translate-y-0.5 transition-transform duration-200">
                                <span class="w-[42px] h-[42px] rounded-2xl clay-gold-light grid place-items-center shrink-0">
                                    <i data-lucide="triangle-alert" class="w-5 h-5"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[15px] font-bold text-ink leading-snug truncate" x-text="alert.title?.en"></p>
                                    <p class="text-[12.5px] text-ink-muted" x-text="alert.type + ' · ' + alert.level"></p>
                                    <p class="text-[11.5px] text-ink-faint mt-0.5" x-text="alert.published_at ? new Date(alert.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''"></p>
                                </div>
                                <span class="shrink-0 surface-pressed-sm rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-[.04em] text-ink-body" x-text="alert.level"></span>
                            </a>
                        </template>
                        <p class="py-6 text-center text-sm text-ink-muted" x-show="!(dash?.sakuna?.alerts || []).length">No active alerts.</p>
                        <a href="{{ route('admin.sakuna.alerts') }}" class="inline-block text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View all alerts</a>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Recent activity</h3>
                        <a href="{{ route('admin.audit-logs') }}" class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View all</a>
                    </div>
                    <div>
                        <template x-for="(activity, i) in (dash?.activities || []).slice(0, 5)" :key="i">
                            <div class="flex items-start gap-3 px-1 py-3.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <span class="w-[38px] h-[38px] rounded-full surface-pressed grid place-items-center text-brand-600 shrink-0">
                                    <i :data-lucide="activityIcon(activity)" class="w-[17px] h-[17px]"></i>
                                </span>
                                <div class="min-w-0">
                                    {{-- In words: a failed sign-in read 'null — auth.login_failed' (QA run 2026-10-05). --}}
                                    <p class="text-sm leading-snug"><span class="font-bold text-ink" x-text="activity.actor || 'System'"></span><span class="text-ink-muted"> — </span><span class="font-semibold text-ink-muted" x-text="activity.summary || activity.action"></span></p>
                                    <p class="font-mono text-xs text-brand-600 mt-0.5 break-all" x-text="(activity.module || '') + (activity.target ? ' · ' + activity.target : '')"></p>
                                    <p class="text-[11.5px] text-ink-faint mt-0.5" x-text="activity.at ? new Date(activity.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></p>
                                </div>
                            </div>
                        </template>
                        <p class="py-6 text-center text-sm text-ink-muted" x-show="!(dash?.activities || []).length">No recent activity.</p>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.admin>
