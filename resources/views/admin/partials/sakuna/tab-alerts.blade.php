{{--
    tab-alerts.blade.php
    Public Alerts & Advisories
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: alertSearch/alertTypeFilter/alertLevelFilter/alertStatusFilter,
    alertsList/alertsLoading/alertsFailed, selectedAlert/showAlertDetail, showCreateAlert/
    alertFormMode/alertForm/alertSaving, showCancelAlert/cancelReason/cancelSaving.
    Helpers: can(submodule, action), exportCSV(rows, filename), toast(msg, variant),
    statusBadge(st), alertLevelBadge(level), loadAlerts(), alertTypes(), openCreateAlert(),
    openEditAlert(a), toggleAlertFormBarangay(b), toggleAlertFormChannel(c), saveAlert(),
    openAlertDetail(row), publishAlert(a), openCancelAlert(a), submitCancelAlert().

    GET/POST /sakuna/alerts, GET/PUT /sakuna/alerts/{ref} (detail + edit added in backend
    commit 15dfabe -- body_fil/body_en were real and writable since drafting an alert
    first shipped, but no authenticated route ever read either back), POST .../publish,
    POST .../cancel.

    The real status axis is three values -- Draft, Released (published), Cancelled --
    not the mock's eight-value kanban (Draft/For Review/Approved/Scheduled/Published/
    Expired/Cancelled/Archived): no review/approval/scheduling/expiry concept exists
    anywhere in AlertService. `review`/`approve`/`archive` are declared sakuna_alerts
    actions with nothing behind them (only create/edit/publish/cancel exist) -- left
    alone rather than invented, matching the restraint already applied to sakuna_relief's
    `approve`, sakuna_resources' `assign` and sakuna_damage's `validate`/`approve`. The
    real channel vocabulary is exactly in_app/sms/email (AlertService::publish()'s own
    channel match), not the mock's SMS/Mobile Push/Barangay Notification/Portal Banner/
    Social Media list. target_barangays is the only real audience axis -- there is no
    demographic/occupational "target group" concept (All Residents/Farmers/Senior
    Citizens/PWD Households/etc.) anywhere in the schema; a null/empty target means
    municipality-wide. Editing is restricted to Draft (the backend's own guard) -- a
    Released alert already has a materialised audience, and a Cancelled one is final.
--}}

<div x-data="{
    get filteredAlerts() {
        const q = alertSearch.toLowerCase();
        return alertsList.filter(a =>
            (!q || a.ref.toLowerCase().includes(q) || (a.title?.en || '').toLowerCase().includes(q) || (a.title?.fil || '').toLowerCase().includes(q)) &&
            (!alertTypeFilter || a.type === alertTypeFilter) &&
            (!alertLevelFilter || a.level === alertLevelFilter) &&
            (!alertStatusFilter || a.status === alertStatusFilter)
        );
    },
}" x-init="loadAlerts(); composeOnArrival()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-ink-body">Public Alerts &amp; Advisories</h2>
        <p class="text-sm text-ink-muted mt-0.5">Draft, publish and cancel bilingual public alerts. A cancellation reaches exactly the audience the alert reached.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredAlerts.map(a => ({ ref: a.ref, type: a.type, level: a.level, title_en: a.title?.en, status: a.status, recipients: a.recipients })), 'alerts-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-card hover:border-slate-300 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export
        </button>
        <button x-show="can('sakuna_alerts', 'create')" @click="openCreateAlert()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Alert
        </button>
    </div>
</div>

<div x-show="alertsLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="alertsFailed" x-cloak><x-ui.error-state messageExpr="'Could not load alerts.'" retryExpr="loadAlerts()" :retryable="true" /></div>

<div x-show="!alertsLoading && !alertsFailed" x-cloak class="space-y-4">

{{-- ── Summary Cards ───────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Total Alerts</p>
        <p class="text-2xl font-bold text-ink-body mt-1" x-text="alertsList.length"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Draft</p>
        <p class="text-2xl font-bold text-slate-600 mt-1" x-text="alertsList.filter(a => a.status === 'Draft').length"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Released</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1" x-text="alertsList.filter(a => a.status === 'Released').length"></p>
    </div>
    <div class="surface-glass rounded-[26px] p-4">
        <p class="text-xs text-ink-muted font-medium uppercase tracking-wide">Cancelled</p>
        <p class="text-2xl font-bold text-rose-600 mt-1" x-text="alertsList.filter(a => a.status === 'Cancelled').length"></p>
    </div>
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] p-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
            <input aria-label="Search by title or ref" type="text" x-model="alertSearch" placeholder="Search by title or ref…"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        </div>
        <select aria-label="Alert type filter" x-model="alertTypeFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Types</option>
            <template x-for="t in alertTypes()" :key="t"><option :value="t" x-text="t"></option></template>
        </select>
        <select aria-label="Alert level filter" x-model="alertLevelFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Levels</option>
            @foreach(['Information','Advisory','Watch','Warning','Critical'] as $lvl)
                <option value="{{ $lvl }}">{{ $lvl }}</option>
            @endforeach
        </select>
        <select aria-label="Alert status filter" x-model="alertStatusFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Statuses</option>
            <option value="Draft">Draft</option>
            <option value="Released">Released</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <button @click="alertSearch = ''; alertTypeFilter = ''; alertLevelFilter = ''; alertStatusFilter = ''"
            class="text-sm text-ink-muted hover:text-slate-700 px-3 py-2 rounded-2xl hover:bg-slate-100 transition-colors">
            Clear filters
        </button>
    </div>
</div>

{{-- ── Alert List ───────────────────────────────────────────────────────────────────────── --}}
<div class="space-y-3">
    <template x-for="a in filteredAlerts" :key="a.ref">
        <div class="bg-white rounded-2xl shadow-card border border-brand-600/10 p-4 hover:shadow-md transition-shadow cursor-pointer" @click="openAlertDetail(a)">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-xs font-mono text-ink-muted" x-text="a.ref"></span>
                        <span :class="alertLevelBadge(a.level)" x-text="a.level"></span>
                        <span :class="statusBadge(a.status)" x-text="a.status"></span>
                        <span class="text-xs text-ink-muted bg-slate-100 px-2 py-0.5 rounded-full" x-text="a.type"></span>
                    </div>
                    <p class="text-sm font-semibold text-ink-body mb-1 truncate" x-text="a.title?.en"></p>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs text-ink-muted flex items-center gap-1">
                            <i data-lucide="target" class="w-3 h-3"></i>
                            <span x-text="(a.barangays && a.barangays.length) ? a.barangays.join(', ') : 'Municipality-Wide'"></span>
                        </span>
                        <template x-for="ch in (a.channels || [])" :key="ch">
                            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full" x-text="ch"></span>
                        </template>
                    </div>
                    <p class="text-xs text-ink-muted mt-1.5">
                        <span x-text="a.status === 'Released' ? ('Published ' + new Date(a.published_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) + ' — ' + a.recipients + ' resident(s)') : (a.status === 'Cancelled' && a.cancelled_at ? ('Cancelled ' + new Date(a.cancelled_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) + (a.published_at ? ' — it had reached ' + a.recipients + ' resident(s)' : '')) : 'Not yet published')"></span>
                    </p>
                </div>
                <div class="flex gap-2 flex-shrink-0 flex-col items-end" @click.stop>
                    <button @click="openAlertDetail(a)" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View</button>
                    <button x-show="can('sakuna_alerts','edit') && a.status === 'Draft'" @click="openEditAlert(a)" class="text-xs text-slate-600 hover:text-ink-body">Edit</button>
                    <button x-show="can('sakuna_alerts','publish') && a.status === 'Draft'" @click="publishAlert(a)" class="text-xs text-brand-600 font-medium hover:text-brand-700">Publish Now</button>
                    <button x-show="can('sakuna_alerts','cancel') && a.status === 'Released'" @click="openCancelAlert(a)" class="text-xs text-rose-600 hover:text-rose-700">Cancel Alert</button>
                </div>
            </div>
        </div>
    </template>
    <div x-show="filteredAlerts.length === 0" class="flex flex-col items-center gap-3 text-ink-muted surface-glass rounded-[26px] py-12">
        <i data-lucide="siren" class="w-10 h-10 opacity-30"></i>
        <p class="text-sm font-medium text-ink-muted">No alerts match your filters.</p>
        <button @click="alertSearch = ''; alertTypeFilter = ''; alertLevelFilter = ''; alertStatusFilter = ''" class="text-sm text-brand-600 hover:underline">Clear Filters</button>
    </div>
</div>

</div>{{-- /loaded content --}}

{{-- ── Alert Detail Modal ──────────────────────────────────────────────────────────────── --}}
<div x-show="showAlertDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showAlertDetail = false"
        x-trap="showAlertDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showAlertDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-2xl shadow-float w-full max-w-xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs text-ink-muted" x-text="selectedAlert?.ref"></span>
                    <span :class="alertLevelBadge(selectedAlert?.level ?? '')" x-text="selectedAlert?.level"></span>
                    <span :class="statusBadge(selectedAlert?.status ?? '')" x-text="selectedAlert?.status"></span>
                </div>
                <h3 class="font-semibold text-ink-body mt-1" x-text="selectedAlert?.title?.en"></h3>
            </div>
            <button aria-label="Close" @click="showAlertDetail = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2">English</h4>
                <p class="text-sm font-medium text-ink-body" x-text="selectedAlert?.title?.en"></p>
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-line" x-text="selectedAlert?.body?.en"></p>
            </div>
            <div class="pt-3 border-t border-brand-600/10">
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2">Filipino</h4>
                <p class="text-sm font-medium text-ink-body" x-text="selectedAlert?.title?.fil"></p>
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-line" x-text="selectedAlert?.body?.fil"></p>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-brand-600/10">
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Target</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedAlert?.barangays && selectedAlert.barangays.length) ? selectedAlert.barangays.join(', ') : 'Municipality-Wide'"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Channels</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedAlert?.channels || []).join(', ')"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Residents Reached</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedAlert?.recipients ?? 0) + (selectedAlert?.reach ? ' · ' + Object.entries(selectedAlert.reach).map(([c, n]) => ({ in_app: 'in-app', sms: 'SMS', email: 'email' }[c] || c) + ' ' + n).join(', ') : '')"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Published</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAlert?.published_at ? new Date(selectedAlert.published_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '—'"></p></div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex flex-wrap gap-2 items-center">
            <button x-show="can('sakuna_alerts','edit') && selectedAlert?.status === 'Draft'" @click="showAlertDetail = false; openEditAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
            <button x-show="can('sakuna_alerts','publish') && selectedAlert?.status === 'Draft'" @click="publishAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 transition-colors ml-auto">
                <i data-lucide="send" class="w-4 h-4"></i> Publish Now
            </button>
            <button x-show="can('sakuna_alerts','cancel') && selectedAlert?.status === 'Released'" @click="showAlertDetail = false; openCancelAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-2xl px-3 py-2 hover:bg-rose-100 transition-colors ml-auto">
                <i data-lucide="octagon-x" class="w-4 h-4"></i> Cancel Alert
            </button>
        </div>
    </div>
</div>

{{-- ── Cancel Confirmation Modal ────────────────────────────────────────────────────────── --}}
<div x-show="showCancelAlert" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCancelAlert = false"
        x-trap="showCancelAlert"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCancelAlert = false">
    <div class="bg-white rounded-2xl shadow-float w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-rose-100 flex items-center justify-center flex-shrink-0"><i data-lucide="octagon-x" class="w-5 h-5 text-rose-600"></i></div>
            <div>
                <h3 class="text-base font-semibold text-ink-body">Cancel Alert</h3>
                <p class="text-xs text-ink-muted" x-text="selectedAlert?.ref"></p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">
            The cancellation will reach exactly the <strong x-text="selectedAlert?.recipients"></strong> resident(s) this alert reached, on the same channels.
        </p>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="cancel-reason">Reason <span class="text-rose-500">*</span></label>
        <textarea id="cancel-reason" x-model="cancelReason" rows="2" placeholder="Why is this alert being cancelled?"
            class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none mb-5 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
        <div class="flex gap-3">
            <button @click="showCancelAlert = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Back</button>
            <button :disabled="cancelSaving || !cancelReason.trim()" @click="submitCancelAlert()"
                class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 rounded-2xl hover:bg-rose-700 transition-colors disabled:opacity-40">
                <span x-text="cancelSaving ? 'Cancelling…' : 'Confirm Cancellation'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── Create / Edit Alert Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showCreateAlert" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showCreateAlert = false"
        x-trap="showCreateAlert"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateAlert = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-2xl shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 class="font-semibold text-ink-body" x-text="alertFormMode === 'create' ? 'Create Alert' : 'Edit Draft Alert'"></h3>
                <p class="text-xs text-ink-muted mt-0.5">Both languages are required — an emergency is the worst moment to send someone a language they don't read.</p>
            </div>
            <button aria-label="Close" @click="showCreateAlert = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-type">Type <span class="text-rose-500">*</span></label>
                    <input id="alert-type" type="text" x-model="alertForm.type" list="alert-type-suggestions" placeholder="e.g. Flood Warning"
                        class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <datalist id="alert-type-suggestions">
                        <template x-for="t in alertTypes()" :key="t"><option :value="t"></option></template>
                    </datalist>
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-level">Level <span class="text-rose-500">*</span></label>
                    <select id="alert-level" x-model="alertForm.level" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                        @foreach(['Information','Advisory','Watch','Warning','Critical'] as $lvl)
                            <option value="{{ $lvl }}">{{ $lvl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-title-en">Title (English) <span class="text-rose-500">*</span></label>
                    <input id="alert-title-en" type="text" x-model="alertForm.title_en" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-title-fil">Title (Filipino) <span class="text-rose-500">*</span></label>
                    <input id="alert-title-fil" type="text" x-model="alertForm.title_fil" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-body-en">Message (English) <span class="text-rose-500">*</span></label>
                    <textarea id="alert-body-en" x-model="alertForm.body_en" rows="3" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-body-fil">Message (Filipino) <span class="text-rose-500">*</span></label>
                    <textarea id="alert-body-fil" x-model="alertForm.body_fil" rows="3" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block">Target Barangays (leave empty for municipality-wide)</label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($barangays as $brgy)
                        <button type="button" @click="toggleAlertFormBarangay('{{ $brgy }}')"
                            :class="alertForm.target_barangays.includes('{{ $brgy }}') ? 'clay-blue border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
                            class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors">
                            {{ $brgy }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block">Channels</label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['in_app' => 'In-App', 'sms' => 'SMS', 'email' => 'Email'] as $key => $label)
                        <button type="button" @click="toggleAlertFormChannel('{{ $key }}')"
                            :class="alertForm.channels.includes('{{ $key }}') ? 'clay-blue border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button @click="showCreateAlert = false" class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-2xl hover:bg-white/60 transition-colors">Cancel</button>
            <button :disabled="alertSaving || !alertForm.type || !alertForm.title_fil || !alertForm.title_en || !alertForm.body_fil || !alertForm.body_en" @click="saveAlert()"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl transition-colors disabled:opacity-50">
                <span x-text="alertSaving ? 'Saving…' : (alertFormMode === 'create' ? 'Save as Draft' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}
