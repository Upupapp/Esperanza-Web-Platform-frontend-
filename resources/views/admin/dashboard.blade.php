<x-layouts.admin title="Dashboard" subtitle="Requests, residents and alerts in your scope." active="dashboard">
    {{-- Tabs (owner, 2026-10-14): the overview, the office dashboards and the
         barangay dashboards, on this one page rather than new sidebar entries.
         One dashboard per role (owner, 18 Nov): the server lists the views an
         account has, its home first (GET /admin/dashboard/subjects views), and
         the tab bar shows only with more than one. The Front Desk lands on
         "Front desk", the Records Officer on "Records", the Information Officer
         and MIO Staff on Balita, the MDRRMO on Sakuna, an office's head and
         staff on their office, a barangay's account on its barangay. Every view
         sits inside the Overview's x-data below, so its activity and Sakuna
         alerts (GET /admin/dashboard) serve every view (dashboardHost,
         resources/js/dashboards.js). --}}
    <div x-data="dashboardHost()">
    <div
        x-data="{
            ready: false,
            loading: true,
            failed: false,
            dash: null,
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
                return rows.map(r => ({ key: r.name, label: 'Brgy. ' + r.name, value: r.count, text: r.count + (r.count === 1 ? ' request' : ' requests'), pct: Math.round((r.count / max) * 100) }));
            },
            get trendData() {
                const t = this.dash?.requests?.trend;
                if (!t) return { labels: [], series: [] };
                return { labels: t.labels || [], series: [
                    this.$store.session.can('dokyu') ? { label: 'Dokyu', values: t.documents || [], color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.38 } : null,
                    this.$store.session.can('tulong') ? { label: 'Tulong', values: t.assistance || [], color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.45 } : null,
                ].filter(Boolean) };
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
            // The module in words (UI sweep 2026-11-18): the card showed the raw
            // audit key, 'user_management · IO-901'. Named as the sidebar does.
            moduleNames: {
                dokyu: 'Dokyu', tulong: 'Tulong', sakuna: 'Sakuna', constituents: 'Constituents',
                communications: 'Balita', payments: 'Payments', papeles: 'Papeles', appointments: 'Appointments',
                reports: 'Reports & Analytics', user_management: 'User Management', users: 'Test accounts',
                identity: 'Sign-in & accounts', support: 'Support', privacy: 'Privacy', settings: 'Settings',
            },
            moduleLabel(m) {
                if (!m) return '';
                const s = String(m);
                return this.moduleNames[s] || (s.charAt(0).toUpperCase() + s.slice(1)).replace(/_/g, ' ');
            },
            get registeredResidents() { return this.dash?.residents?.total ?? null; },
            // Released and filed name only the modules the account holds (a
            // Dokyu-only role read Tulong in them, 18 Nov).
            get modulesText() {
                return [this.$store.session.can('dokyu') ? 'Dokyu' : null, this.$store.session.can('tulong') ? 'Tulong' : null].filter(Boolean).join(' + ');
            },
            get releasedText() {
                return [this.$store.session.can('dokyu') ? 'Dokyu released' : null, this.$store.session.can('tulong') ? 'Tulong completed' : null].filter(Boolean).join(' + ');
            },
            // An account that cannot open Audit Logs gets only its own actions
            // (activities_scope own): its title says so, and no View All.
            get activityTitle() {
                return { own: 'Your recent activity', office: 'Recent activity in your office', barangay: 'Recent activity in your barangay' }[this.dash?.activities_scope] || 'Recent activity';
            },
            get mayAudit() {
                const scope = this.dash?.activities_scope;
                return scope ? scope !== 'own' : this.$store.session.canPerm('um_audit', 'view');
            },
            get hasCounts() { return this.$store.session.can('dokyu') || this.$store.session.can('tulong'); },
            async init() {
                // Live updates (2026-11-15): any change anywhere reloads the
                // figures quietly. Every view shows their activity and alerts.
                this.$store.live.on(this.$el, 'any', () => this.load(true));
                await this.load();
            },
            // quiet (a live update): the figures on screen stay until the new
            // ones come, and a failed reload keeps them.
            async load(quiet = false) {
                if (!quiet) {
                    this.loading = true;
                    this.failed = false;
                }
                try {
                    const dash = await $api.get('/admin/dashboard');
                    this.dash = dash.data;
                    this.failed = false;
                } catch {
                    if (!quiet) this.failed = true;
                } finally {
                    this.loading = false;
                    if (!quiet) setTimeout(() => (this.ready = true), 150);
                    this.$nextTick(() => window.renderIcons?.());
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
                        {{-- Wraps rather than cuts ("Good day, Test Dep…", 2026-10-15). --}}
                        <h2 class="mt-1 text-[22px] sm:text-[28px] font-extrabold leading-tight tracking-[-.01em] text-white text-balance break-words">
                            <span x-text="'Good day, ' + $store.session.account?.name"></span>
                        </h2>
                        <p class="mt-1 text-[15px] text-[#dce5ff] flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span x-text="$store.session.account?.role + ' · ' + $store.session.account?.dept"></span>
                            <span class="text-white/40 hidden sm:inline">·</span>
                            <span class="font-mono text-[13.5px]" x-text="$store.session.account?.id"></span>
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <div x-show="showTabs" x-cloak class="flex flex-wrap items-center gap-3">
            <div class="inline-flex flex-wrap p-1 rounded-2xl surface-pressed-sm max-w-full" role="tablist" aria-label="Dashboard">
                <template x-for="v in views" :key="v">
                    <button type="button" role="tab" :aria-selected="view === v" @click="show(v)" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[13.5px] transition-all duration-200" :class="view === v ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'"><i :data-lucide="tabIcon(v)" class="w-4 h-4"></i><span x-text="tabLabel(v)"></span></button>
                </template>
            </div>
        </div>

        <div x-show="subjectsReady && view === 'overview'" x-cloak class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[14.5px] text-ink-body" x-show="$store.session.can('constituents') || $store.session.can('dokyu') || $store.session.can('tulong')">Live counts, updated as they change.</p>
            <x-ui.button :href="route('admin.reports')" icon="download" x-show="$store.session.can('reports')" class="ml-auto">Export Report</x-ui.button>
        </div>

        <!-- Skeleton state -->
        <div x-show="!subjectsReady || (!ready && view === 'overview')" x-transition.opacity.duration.300ms class="flex flex-col gap-5">
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

        <div x-show="failed && view === 'overview'" x-cloak>
            <x-ui.error-state messageExpr="'Could not load the dashboard.'" retryExpr="load()" :retryable="true" />
        </div>

        <template x-if="subjectsReady && view === 'department'">
            <x-admin.dashboards.department />
        </template>
        <template x-if="subjectsReady && view === 'barangay'">
            <x-admin.dashboards.barangay />
        </template>
        <template x-if="subjectsReady && view === 'desk'">
            <x-admin.dashboards.desk />
        </template>
        <template x-if="subjectsReady && view === 'records'">
            <x-admin.dashboards.records />
        </template>
        <template x-if="subjectsReady && view === 'balita'">
            <x-admin.dashboards.balita />
        </template>
        <template x-if="subjectsReady && view === 'sakuna'">
            <x-admin.dashboards.sakuna />
        </template>

        <!-- Real content -->
        <div x-show="subjectsReady && ready && !failed && view === 'overview'" x-cloak class="flex flex-col gap-[22px]" x-transition:enter="transition duration-[300ms] ease-standard" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            <!-- KPI cards: all six in one row on a wide screen (owner, 2026-10-09), so compact -->
            {{-- A role with no counts (MIO, 2026-11-17) gets no empty grid: its gap left a blank band. --}}
            <div x-show="$store.session.can('constituents') || $store.session.can('dokyu') || $store.session.can('tulong')" class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <div x-show="dash?.residents" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.constituents')" label="Registered residents" valueBind="(registeredResidents ?? 0).toLocaleString('en-PH')" icon="users" color="brand" sublabelBind="scopeLabel" :delay="0" /></div>
                <div x-show="$store.session.can('dokyu')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests') . '?open=1'" label="Open Dokyu requests" valueBind="openDokyu" icon="file-text" color="light" sublabelBind="docStatusTotal + ' in all, any status'" :delay="40" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests') . '?open=1'" label="Waiting on staff" valueBind="($store.session.can('dokyu') ? waitingDokyu : 0) + ($store.session.can('tulong') ? waitingTulong : 0)" icon="inbox" color="orange" sublabelBind="[$store.session.can('dokyu') ? 'Dokyu ' + waitingDokyu : null, $store.session.can('tulong') ? 'Tulong ' + waitingTulong : null].filter(Boolean).join(' · ')" :delay="80" /></div>
                <div x-show="$store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.assistance-requests') . '?open=1'" label="Open Tulong cases" valueBind="openTulong" icon="hand-heart" color="deep" sublabelBind="assistStatusTotal + ' in all, any status'" :delay="120" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests')" label="Released this month" valueBind="dash?.requests?.released_this_month ?? 0" icon="package-check" color="slate" sublabelBind="releasedText" :delay="160" /></div>
                <div x-show="$store.session.can('dokyu') || $store.session.can('tulong')" x-cloak><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.document-requests')" label="Filed today" valueBind="dash?.requests?.submitted_today ?? 0" icon="calendar-clock" color="gold" sublabelBind="modulesText" :delay="200" /></div>
            </div>

            <!-- Row 1: doughnuts + barangays -->
            <div x-show="hasCounts" class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <div x-show="$store.session.can('dokyu')" x-cloak>
                <x-ui.card class="h-full">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Dokyu</h3>
                        <a href="{{ route('admin.document-requests') }}" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View Details</a>
                    </div>
                    <x-ui.clay-donut rows="docStatus" total-label="Total requests" empty="No Dokyu requests yet." />
                </x-ui.card>
                </div>

                <div x-show="$store.session.can('tulong')" x-cloak>
                <x-ui.card class="h-full">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Tulong</h3>
                        <a href="{{ route('admin.assistance-requests') }}" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View Details</a>
                    </div>
                    <x-ui.clay-donut rows="assistStatus" total-label="Total cases" empty="No Tulong requests yet." />
                </x-ui.card>
                </div>

                <div x-show="hasCounts" x-cloak>
                <x-ui.card class="h-full">
                    <div class="flex flex-col items-start gap-3 mb-5">
                        <h3 class="text-lg font-extrabold text-ink leading-tight">Open requests by barangay</h3>
                        <div x-show="$store.session.can('dokyu') && $store.session.can('tulong')" class="inline-flex p-1 rounded-xl surface-pressed-sm shrink-0" role="tablist" aria-label="Module">
                            <button type="button" role="tab" x-show="$store.session.can('dokyu')" :aria-selected="brgyTab === 'dokyu'" @click="brgyTab = 'dokyu'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'dokyu' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'">Dokyu</button>
                            <button type="button" role="tab" x-show="$store.session.can('tulong')" :aria-selected="brgyTab === 'tulong'" @click="brgyTab = 'tulong'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'tulong' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'">Tulong</button>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <x-ui.clay-bars rows="barangayRows" x-show="barangayRows.length" />
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

            {{-- A role with no figures here still has its work and its activity. --}}
            <div x-show="!hasCounts && !dash?.residents" class="surface-pressed rounded-2xl px-4 py-5 text-center">
                <p class="text-[14.5px] font-bold text-ink">Your role has no figures on this dashboard.</p>
                <p class="mt-1 text-[12.5px] text-ink-muted">What waits on you and your recent activity are below.</p>
            </div>

            <!-- Row 2: the trend and what waits on this account -->
            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <div x-show="hasCounts" x-cloak class="xl:col-span-3">
                <x-ui.card class="flex flex-col h-full">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Requests trend</h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span>Last 6 months</span>
                            <span x-show="$store.session.can('dokyu')" class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Dokyu</span>
                            <span x-show="$store.session.can('tulong')" class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Tulong</span>
                        </p>
                    </div>
                    <x-ui.clay-trend class="my-auto" data="trendData" label="Dokyu and Tulong requests filed in each of the last six months" />
                </x-ui.card>
                </div>

                <x-admin.dashboards.action-list ::class="hasCounts ? 'xl:col-span-2' : 'xl:col-span-5'" />
            </div>

            <!-- Row 3: Sakuna alerts and activity -->
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-admin.dashboards.alerts x-show="$store.session.can('sakuna')" x-cloak />
                <x-admin.dashboards.activity />
            </div>
        </div>
    </div>
    <x-admin.report-sheet />
    </div>
</x-layouts.admin>
