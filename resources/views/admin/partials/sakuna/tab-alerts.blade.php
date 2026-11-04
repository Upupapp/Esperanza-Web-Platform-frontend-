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
    real channel vocabulary is in_app/sms/email (in_app also pushes to residents' phones,
    2026-10-26), not the mock's SMS/Mobile Push/Barangay Notification/Portal Banner/
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
}" x-init="loadAlerts(); composeOnArrival()" class="space-y-3">

{{-- ── Stats + actions (Sakuna Redesign, 2026-11-02) ──────────────────────────────────── --}}
<div class="sk-pagebar">
    <div class="sk-stats">
        <div class="sk-stat"><span class="sk-slab">Total alerts</span><span class="sk-sval tabular-nums" x-text="fig(alertsList.length, alertsLoading || alertsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab">Draft</span><span class="sk-sval tabular-nums" x-text="fig(alertsList.filter(a => a.status === 'Draft').length, alertsLoading || alertsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab">Released</span><span class="sk-sval tabular-nums" x-text="fig(alertsList.filter(a => a.status === 'Released').length, alertsLoading || alertsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab">Cancelled</span><span class="sk-sval tabular-nums" x-text="fig(alertsList.filter(a => a.status === 'Cancelled').length, alertsLoading || alertsFailed)"></span></div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare"
            aria-label="About public alerts and advisories"
            title="Public alerts and advisories: draft, publish and cancel bilingual public alerts. A cancellation reaches exactly the audience the alert reached.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn" @click="exportCSV(filteredAlerts.map(a => ({ ref: a.ref, type: a.type, level: a.level, title_en: a.title?.en, status: a.status, recipients: a.recipients })), 'alerts-export.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn" @click="exportPDF(filteredAlerts.map(a => ({ ref: a.ref, type: a.type, level: a.level, title_en: a.title?.en, status: a.status, recipients: a.recipients })), 'alerts-export.csv')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-3 items-start">

<div class="min-w-0 space-y-3" style="flex: 2 1 560px;">
<div x-show="alertsLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="alertsFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load alerts. ' + offlineWhy" retryExpr="loadAlerts()" :retryable="true" /></div>

{{-- ── Alert list: filters, then one row per alert ───────────────────────────────────────── --}}
<section x-show="!alertsLoading && !alertsFailed" x-cloak class="sk-card overflow-hidden">
    <h2 class="sr-only">Public alerts</h2>
    <div class="sk-toolbar">
        <div class="sk-search">
            <label for="alert-search" class="sr-only">Search by title or ref</label>
            <i data-lucide="search"></i>
            <input id="alert-search" aria-label="Search by title or ref" type="search" x-model="alertSearch" placeholder="Search by title or ref…">
        </div>
        <select aria-label="Alert type filter" x-model="alertTypeFilter" class="sk-sel">
            <option value="">All types</option>
            <template x-for="t in alertTypes()" :key="t"><option :value="t" x-text="t"></option></template>
        </select>
        <select aria-label="Alert level filter" x-model="alertLevelFilter" class="sk-sel">
            <option value="">All levels</option>
            <template x-for="lvl in alertLevels()" :key="lvl"><option :value="lvl" x-text="lvl"></option></template>
        </select>
        <select aria-label="Alert status filter" x-model="alertStatusFilter" class="sk-sel">
            <option value="">All statuses</option>
            <option value="Draft">Draft</option>
            <option value="Released">Released</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <button type="button" @click="alertSearch = ''; alertTypeFilter = ''; alertLevelFilter = ''; alertStatusFilter = ''" class="sk-clear">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub" x-text="filteredAlerts.length + (filteredAlerts.length === 1 ? ' alert' : ' alerts')"></span>
    </div>

    <template x-for="a in filteredAlerts" :key="a.ref">
        <div class="flex gap-3 px-3.5 py-2.5 border-b border-[#EEF2F7] hover:bg-[#F8FAFE] transition-colors cursor-pointer" @click="openAlertDetail(a)">
            <div class="flex-grow min-w-0 flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="sk-mono" x-text="a.ref"></span>
                    <span :class="alertLevelBadge(a.level)" x-text="a.level"></span>
                    <span :class="statusBadge(a.status)" x-text="a.status"></span>
                    <span x-show="a.expired && a.status === 'Released'" class="sk-pill sk-p-slate">Expired: residents no longer see it</span>
                    <span class="sk-tag" x-text="a.type"></span>
                    <span class="sk-b text-[13px] ml-1 truncate" x-text="a.title?.en"></span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 text-xs sk-muted">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="target" class="w-3 h-3"></i>
                        <span x-text="(a.barangays && a.barangays.length) ? a.barangays.join(', ') : 'Municipality-Wide'"></span>
                    </span>
                    <template x-for="ch in (a.channels || [])" :key="ch">
                        <span class="sk-tag" x-text="channelLabel(ch)"></span>
                    </template>
                    <span x-text="(a.status === 'Released' ? ('Released ' + new Date(a.published_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) + ' — ' + a.recipients + (a.recipients === 1 ? ' resident' : ' residents')) : (a.status === 'Cancelled' && a.cancelled_at ? ('Cancelled ' + new Date(a.cancelled_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) + (a.published_at ? ' — it had reached ' + a.recipients + (a.recipients === 1 ? ' resident' : ' residents') : '')) : 'Not yet published'))"></span>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-1 shrink-0" @click.stop>
                <button type="button" x-show="can('sakuna_alerts','publish') && a.status === 'Draft'" @click="askPublishAlert(a)" class="sk-btn sk-btn-sm sk-btn-p"><i data-lucide="send"></i> Review and send</button>

                <button type="button" x-show="can('sakuna_alerts','cancel') && a.status === 'Released'" @click="openCancelAlert(a)" class="sk-btn sk-btn-sm">Cancel alert</button>
                <button type="button" @click="openAlertDetail(a)" class="sk-btn sk-btn-sm sk-btn-soft"><i data-lucide="eye"></i> View</button>
                <button type="button" x-show="can('sakuna_alerts','edit') && a.status === 'Draft'" @click="openEditAlert(a)" class="sk-btn sk-btn-sm"><i data-lucide="pencil"></i> Edit</button>
            </div>
        </div>
    </template>
    <div x-show="filteredAlerts.length === 0" class="sk-empty flex-col">
        <i data-lucide="siren"></i>
        <span>No alerts match your filters.</span>
        <button type="button" @click="alertSearch = ''; alertTypeFilter = ''; alertLevelFilter = ''; alertStatusFilter = ''" class="sk-clear">Clear Filters</button>
    </div>
    <div class="sk-tfoot" x-show="filteredAlerts.length" x-text="'Showing ' + filteredAlerts.length + ' of ' + alertsList.length + ' alerts'"></div>
</section>{{-- /loaded content --}}
</div>

{{-- ── Emergency hotlines ──────────────────────────────────────────────────────────────────
     The numbers residents see on their Sakuna page (GET /hotlines), kept here
     by the people who run Sakuna (owner, 2026-10-17: emergencies belong in
     Sakuna, not the Directory). /admin/sakuna/hotlines only ever touches
     hotline rows. --}}
<section class="sk-card overflow-hidden min-w-0" style="flex: 1 1 300px;" aria-labelledby="hotlines-admin-heading"
    x-data="{
        hotlines: [], hotlinesLoading: true, hotlinesFailed: false,
        hotlineForm: { id: null, office: '', position: '', contact: '' }, hotlineEditing: false, hotlineSaving: false, hotlineError: '',
        removingHotline: null,
        async loadHotlines() {
            this.hotlinesLoading = true; this.hotlinesFailed = false;
            try { this.hotlines = (await $api.get('/admin/sakuna/hotlines')).data || []; saveCopy('hotlines', this.hotlines); }
            catch { const copy = lastCopy('hotlines'); if (copy) this.hotlines = copy.data; else this.hotlinesFailed = true; }
            finally { this.hotlinesLoading = false; this.$nextTick(() => window.renderIcons?.()); }
        },
        startHotline(h = null) {
            this.hotlineForm = h ? { id: h.id, office: h.office || '', position: h.position || '', contact: h.contact || '' } : { id: null, office: '', position: '', contact: '' };
            this.hotlineError = '';
            this.hotlineEditing = true;
            this.$nextTick(() => document.getElementById('hotline-office')?.focus());
        },
        async saveHotline() {
            if (this.hotlineSaving || !this.hotlineForm.office.trim() || !this.hotlineForm.contact.trim()) return;
            this.hotlineSaving = true; this.hotlineError = '';
            const body = { office: this.hotlineForm.office.trim(), position: this.hotlineForm.position.trim() || null, contact: this.hotlineForm.contact.trim() };
            try {
                if (this.hotlineForm.id) await $api.put('/admin/sakuna/hotlines/' + this.hotlineForm.id, body);
                else await $api.post('/admin/sakuna/hotlines', body);
                this.toast(this.hotlineForm.id ? 'Hotline updated. Residents see the new number now.' : 'Hotline added. Residents see it on their Sakuna page now.', 'success');
                this.hotlineEditing = false;
                await this.loadHotlines();
            } catch (err) {
                this.hotlineError = err.localised ? err.localised('en') : 'Could not save this hotline.';
            } finally { this.hotlineSaving = false; }
        },
        async removeHotline(h) {
            try {
                await $api.delete('/admin/sakuna/hotlines/' + h.id);
                this.removingHotline = null;
                this.toast('Hotline removed.', 'success');
                await this.loadHotlines();
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not remove this hotline.', 'error');
            }
        },
    }" x-init="loadHotlines()">
    <div class="sk-chead">
        <div>
            <h3 id="hotlines-admin-heading" class="sk-ctitle inline-flex items-center gap-1.5"><i data-lucide="phone-call" class="w-4 h-4 text-brand-600"></i> Emergency Hotlines</h3>
            <div class="sk-csub">The numbers residents see on their Sakuna / Emergency page, below 911.</div>
        </div>
        <button type="button" x-show="can('sakuna_alerts', 'create') && !hotlineEditing" @click="startHotline()" class="sk-btn sk-btn-sm">
            <i data-lucide="plus"></i> Add hotline
        </button>
    </div>

    <form x-show="hotlineEditing" x-cloak @submit.prevent="saveHotline()" class="grid grid-cols-1 gap-2.5 p-3.5 bg-[#F8FAFD] border-b border-[#EEF2F7]">
        <label class="block">
            <span class="text-xs font-medium text-[#4B5568] mb-1 block">Office <span class="text-rose-600">*</span></span>
            <input id="hotline-office" type="text" x-model="hotlineForm.office" maxlength="160" required placeholder="e.g. MDRRMO Rescue" class="sk-sel w-full">
        </label>
        <label class="block">
            <span class="text-xs font-medium text-[#4B5568] mb-1 block">What it is for</span>
            <input type="text" x-model="hotlineForm.position" maxlength="120" placeholder="e.g. 24/7 rescue" class="sk-sel w-full">
        </label>
        <label class="block">
            <span class="text-xs font-medium text-[#4B5568] mb-1 block">Number <span class="text-rose-600">*</span></span>
            <input type="tel" x-model="hotlineForm.contact" maxlength="80" required placeholder="e.g. 0917 123 4567" class="sk-sel w-full">
        </label>
        <p x-show="hotlineError" x-cloak role="alert" class="text-xs text-rose-700" x-text="hotlineError"></p>
        <div class="flex flex-wrap gap-2 justify-end">
            <button type="button" @click="hotlineEditing = false" class="sk-btn sk-btn-sm">Cancel</button>
            <button type="submit" :disabled="hotlineSaving || !hotlineForm.office.trim() || !hotlineForm.contact.trim()" class="sk-btn sk-btn-sm sk-btn-p"
                x-text="hotlineSaving ? 'Saving…' : (hotlineForm.id ? 'Save hotline' : 'Add hotline')"></button>
        </div>
    </form>

    <div x-show="hotlinesLoading" class="p-3.5 space-y-2"><x-ui.skeleton class="h-10" /><x-ui.skeleton class="h-10 opacity-70" /></div>
    <div x-show="hotlinesFailed" x-cloak class="p-3.5"><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load the hotlines. ' + offlineWhy" retryExpr="loadHotlines()" :retryable="true" class="!py-6" /></div>
    <p x-show="!hotlinesLoading && !hotlinesFailed && !hotlines.length" x-cloak class="sk-empty">No hotlines yet. Residents see only 911 until one is added.</p>
    <ul x-show="!hotlinesLoading && hotlines.length" x-cloak>
        <template x-for="h in hotlines" :key="h.id">
            <li class="flex flex-wrap items-center gap-2.5 px-3.5 py-2 border-b border-[#EEF2F7] last:border-b-0 hover:bg-[#F8FAFE]">
                <div class="flex-grow min-w-0">
                    <p class="sk-b text-[12.5px]" x-text="h.office"></p>
                    <p class="sk-sub"><span x-text="h.position" x-show="h.position"></span><span x-show="h.position"> · </span><span class="sk-mono" style="color: #1D4ED8;" x-text="h.contact"></span></p>
                </div>
                <button type="button" x-show="can('sakuna_alerts', 'edit') && removingHotline !== h.id" @click="startHotline(h)" class="sk-iconbtn sk-iconbtn-bare" :aria-label="'Edit ' + h.office" title="Edit"><i data-lucide="pencil"></i></button>
                <button type="button" x-show="can('sakuna_alerts', 'edit') && removingHotline !== h.id" @click="removingHotline = h.id" class="sk-iconbtn sk-iconbtn-bare" :aria-label="'Remove ' + h.office" title="Remove"><i data-lucide="trash-2"></i></button>
                <span x-show="removingHotline === h.id" x-cloak class="flex items-center gap-2 text-xs">
                    <span class="text-ink-body">Remove from residents' page?</span>
                    <button type="button" @click="removeHotline(h)" class="font-semibold text-rose-700 hover:underline">Remove</button>
                    <button type="button" @click="removingHotline = null" class="text-slate-600 hover:underline">Keep</button>
                </span>
            </li>
        </template>
    </ul>
</section>

</div>{{-- /alerts | hotlines --}}


{{-- ── Alert Detail Modal ──────────────────────────────────────────────────────────────── --}}
<div x-show="showAlertDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showAlertDetail = false"
        x-trap="showAlertDetail"
        role="dialog"
        aria-modal="true"
        aria-labelledby="alert-detail-title"
        @keydown.escape.window="showAlertDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs text-ink-muted" x-text="selectedAlert?.ref"></span>
                    <span :class="alertLevelBadge(selectedAlert?.level ?? '')" x-text="selectedAlert?.level"></span>
                    <span :class="statusBadge(selectedAlert?.status ?? '')" x-text="selectedAlert?.status"></span>
                </div>
                <h3 id="alert-detail-title" class="font-semibold text-ink-body mt-1" x-text="selectedAlert?.title?.en"></h3>
            </div>
            <button aria-label="Close window" @click="showAlertDetail = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4">
            {{-- Filipino first, as residents see it, the same order as the send review. --}}
            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-2">Filipino</h4>
                <p class="text-sm font-medium text-ink-body" x-text="selectedAlert?.title?.fil"></p>
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-line" x-text="selectedAlert?.body?.fil"></p>
            </div>
            <div class="pt-3 border-t border-brand-600/10">
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-2">English</h4>
                <p class="text-sm font-medium text-ink-body" x-text="selectedAlert?.title?.en"></p>
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-line" x-text="selectedAlert?.body?.en"></p>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-brand-600/10">
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Target</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="alertReachLabel(selectedAlert)"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Channels</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedAlert?.channels || []).map(c => channelLabel(c)).join(', ')"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Residents reached</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAlert?.status === 'Draft' ? 'Not sent yet' : (selectedAlert?.recipients ?? 0) + (selectedAlert?.reach ? ' · ' + Object.entries(selectedAlert.reach).map(([c, n]) => ({ in_app: 'in-app', sms: 'SMS', email: 'email' }[c] || c) + ' ' + n).join(', ') : '')"></p></div>
                <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Released</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAlert?.published_at ? new Date(selectedAlert.published_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '—'"></p></div>
                <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Shown to residents until</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAlert?.expires_at ? alertEnds(selectedAlert) : 'Until you cancel it'"></p></div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex flex-wrap gap-2 items-center">
            <button x-show="can('sakuna_alerts','edit') && selectedAlert?.status === 'Draft'" @click="showAlertDetail = false; openEditAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
            <button x-show="can('sakuna_alerts','publish') && selectedAlert?.status === 'Draft'" @click="askPublishAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 transition-colors ml-auto">
                <i data-lucide="send" class="w-4 h-4"></i> Review and send
            </button>
            <button x-show="can('sakuna_alerts','cancel') && selectedAlert?.status === 'Released'" @click="showAlertDetail = false; openCancelAlert(selectedAlert)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors ml-auto">
                <i data-lucide="octagon-x" class="w-4 h-4"></i> Cancel alert
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
        aria-labelledby="cancel-alert-title"
        @keydown.escape.window="showCancelAlert = false">
    <div class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-rose-100 flex items-center justify-center flex-shrink-0"><i data-lucide="octagon-x" class="w-5 h-5 text-rose-600"></i></div>
            <div>
                <h3 id="cancel-alert-title" class="text-base font-semibold text-ink-body">Cancel Alert</h3>
                <p class="text-xs text-ink-muted" x-text="selectedAlert?.ref"></p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">
            The cancellation will reach exactly the <strong x-text="selectedAlert?.recipients"></strong> <span x-text="selectedAlert?.recipients === 1 ? 'resident' : 'residents'"></span> this alert reached, on the same channels.
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

{{-- ── Review and send (critique 2026-11-03) ─────────────────────────────────
     Sending reaches residents' phones and cannot be taken back, so it is
     reviewed: where, how, both texts and the end, then one explicit send. --}}
<div x-show="showPublishConfirm" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="if (!publishSaving) showPublishConfirm = false"
        x-trap="showPublishConfirm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="publish-alert-title"
        @keydown.escape.window="if (showPublishConfirm && !publishSaving) showPublishConfirm = false">
    <div class="bg-white rounded-[28px] shadow-float w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-brand-50 flex items-center justify-center flex-shrink-0"><i data-lucide="send" class="w-5 h-5 text-brand-600"></i></div>
            <div class="min-w-0">
                <h3 id="publish-alert-title" class="text-base font-semibold text-ink-body">Send this alert to residents?</h3>
                <p class="text-xs text-ink-muted"><span x-text="publishTarget?.ref"></span> · <span x-text="publishTarget?.type"></span></p>
            </div>
        </div>
        <dl class="grid grid-cols-[7rem_1fr] gap-x-3 gap-y-2 text-sm mb-4">
            <dt class="text-ink-muted self-center">Level</dt>
            <dd><span :class="alertLevelBadge(publishTarget?.level ?? '')" class="!text-sm !font-semibold !px-3 !py-1" x-text="publishTarget?.level"></span></dd>
            <dt class="text-ink-muted">Reaches</dt>
            <dd class="text-ink-body font-medium" x-text="alertReachLabel(publishTarget)"></dd>
            <dt class="text-ink-muted">Through</dt>
            <dd class="text-ink-body" x-text="(publishTarget?.channels || ['in_app']).map(c => channelLabel(c)).join(', ')"></dd>
            <dt class="text-ink-muted">Shown until</dt>
            <dd class="text-ink-body" x-text="publishTarget?.expires_at ? alertEnds(publishTarget).replace(/^Ends /, '') : 'Until you cancel it'"></dd>
        </dl>
        <div class="space-y-2 mb-4">
            <div class="rounded-2xl bg-slate-50 p-3">
                <p class="text-xs font-semibold text-ink-muted mb-0.5">Filipino</p>
                <p class="text-sm font-semibold text-ink-body" x-text="publishTarget?.title?.fil"></p>
                <p class="text-sm text-slate-600 mt-0.5 whitespace-pre-line" x-show="publishTarget?.body?.fil" x-text="publishTarget?.body?.fil"></p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <p class="text-xs font-semibold text-ink-muted mb-0.5">English</p>
                <p class="text-sm font-semibold text-ink-body" x-text="publishTarget?.title?.en"></p>
                <p class="text-sm text-slate-600 mt-0.5 whitespace-pre-line" x-show="publishTarget?.body?.en" x-text="publishTarget?.body?.en"></p>
            </div>
        </div>
        <p class="text-xs text-ink-muted mb-5">Once sent it cannot be unsent. If it is wrong, cancel it: the cancellation reaches the same residents.</p>
        <div class="flex gap-3">
            <button @click="showPublishConfirm = false" :disabled="publishSaving" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors disabled:opacity-40">Not yet</button>
            <button @click="confirmPublishAlert()" :disabled="publishSaving"
                class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-2xl hover:bg-brand-700 transition-colors disabled:opacity-60 inline-flex items-center justify-center gap-1.5">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span x-text="publishSaving ? 'Sending…' : 'Send to residents'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── Create / Edit Alert Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showCreateAlert" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="closeAlertForm()"
        x-trap="showCreateAlert"
        role="dialog"
        aria-modal="true"
        aria-labelledby="alert-form-title"
        @keydown.escape.window="if (!showCreateAlert) return; if (alertDiscardAsk) alertDiscardAsk = false; else closeAlertForm()">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 id="alert-form-title" class="font-semibold text-ink-body" x-text="alertFormMode === 'create' ? 'New alert' : 'Edit draft alert'"></h3>
                <p class="text-xs text-ink-muted mt-0.5">Both languages are required — an emergency is the worst moment to send someone a language they don't read.</p>
            </div>
            <button aria-label="Close window" @click="closeAlertForm()" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4" x-effect="JSON.stringify(alertForm); keepAlertDraft()">
            <p x-show="alertOffline" x-cloak role="status" class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                The live board is not reachable right now. You can write the alert: the draft stays on this computer, and it can be saved and sent once the connection returns.
            </p>
            <p x-show="alertDraftRestored" x-cloak role="status" class="text-sm text-brand-800 bg-brand-50 border border-brand-100 rounded-xl px-3 py-2">
                Your unsent draft from this computer is back. Discard it to start over.
            </p>
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
                        <template x-for="lvl in alertLevels()" :key="lvl"><option :value="lvl" x-text="lvl" :selected="lvl === alertForm.level"></option></template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-title-fil">Title (Filipino) <span class="text-rose-500">*</span></label>
                    <input id="alert-title-fil" type="text" x-model="alertForm.title_fil" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-title-en">Title (English) <span class="text-rose-500">*</span></label>
                    <input id="alert-title-en" type="text" x-model="alertForm.title_en" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-body-fil">Message (Filipino) <span class="text-rose-500">*</span></label>
                    <textarea id="alert-body-fil" x-model="alertForm.body_fil" rows="3" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-body-en">Message (English) <span class="text-rose-500">*</span></label>
                    <textarea id="alert-body-en" x-model="alertForm.body_en" rows="3" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
                </div>
            </div>

            {{-- Who it reaches is a choice (critique 2026-11-03): an empty list no
                 longer means everyone. --}}
            <fieldset>
                <legend class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5">Who receives it <span class="text-rose-500">*</span></legend>
                <div class="flex flex-wrap gap-2 mb-2" role="radiogroup" aria-label="Who receives it">
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl border text-sm cursor-pointer transition-colors"
                        :class="alertForm.audience === 'all' ? 'border-brand-600 bg-brand-50 text-brand-700 font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                        <input type="radio" name="alert-audience" value="all" x-model="alertForm.audience" class="accent-brand-600"> Whole municipality
                    </label>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl border text-sm cursor-pointer transition-colors"
                        :class="alertForm.audience === 'barangays' ? 'border-brand-600 bg-brand-50 text-brand-700 font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                        <input type="radio" name="alert-audience" value="barangays" x-model="alertForm.audience" class="accent-brand-600"> Chosen barangays
                    </label>
                </div>
                <div x-show="alertForm.audience === 'barangays'" x-cloak class="flex flex-wrap gap-1.5" role="group" aria-label="Barangays">
                    @foreach($barangays as $brgy)
                        <button type="button" @click="toggleAlertFormBarangay('{{ $brgy }}')" :aria-pressed="alertForm.target_barangays.includes('{{ $brgy }}') ? 'true' : 'false'"
                            :class="alertForm.target_barangays.includes('{{ $brgy }}') ? 'clay-blue border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
                            class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors">
                            {{ $brgy }}
                        </button>
                    @endforeach
                </div>
                <p x-show="alertForm.audience === 'barangays' && !alertForm.target_barangays.length" x-cloak class="text-xs text-amber-800 mt-1.5">Choose at least one barangay.</p>
            </fieldset>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="alert-expires">Ends by itself (optional)</label>
                <input id="alert-expires" type="datetime-local" x-model="alertForm.expires_at" :min="nowInput()" aria-describedby="alert-expires-help"
                    class="w-full sm:w-72 px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <p id="alert-expires-help" class="text-xs text-ink-muted mt-1">After this, residents no longer see it on their Sakuna page. Leave empty to keep it up until you cancel it.</p>
            </div>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block">Channels</label>
                <div class="flex flex-wrap gap-1.5">
                    {{-- In-App is the bell on the website and the app, and a push to the
                         residents' phones (AlertService, 2026-10-26). --}}
                    @foreach(['in_app' => 'In-App + phone push', 'sms' => 'SMS', 'email' => 'Email'] as $key => $label)
                        <button type="button" @click="toggleAlertFormChannel('{{ $key }}')" :aria-pressed="alertForm.channels.includes('{{ $key }}') ? 'true' : 'false'"
                            :class="alertForm.channels.includes('{{ $key }}') ? 'clay-blue border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-show="alertDiscardAsk" x-cloak class="px-6 py-4 border-t border-amber-200 bg-amber-50 rounded-b-2xl flex flex-wrap items-center gap-3" role="alertdialog" aria-labelledby="alert-discard-text">
            <p id="alert-discard-text" class="text-sm text-amber-900 flex-1 min-w-[14rem]">Discard what you have written? It has not been saved.</p>
            <button type="button" @click="alertDiscardAsk = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-2xl hover:border-slate-300 transition-colors">Keep writing</button>
            <button type="button" @click="closeAlertForm(true)" class="px-4 py-2 text-sm font-semibold text-rose-700 bg-white border border-rose-200 rounded-2xl hover:bg-rose-50 transition-colors">Discard</button>
        </div>
        <p x-show="!alertDiscardAsk && !alertFormReady && alertFormMissing.length" x-cloak class="px-6 pt-3 text-xs text-ink-muted border-t border-brand-600/10" aria-live="polite" x-text="'To save: ' + alertFormMissing.join(', ') + '.'"></p>
        <div x-show="!alertDiscardAsk" class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button @click="closeAlertForm()" class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-2xl hover:bg-white/60 transition-colors">Cancel</button>
            <button :disabled="alertSaving || !alertFormReady" @click="alertSendAfterSave = false; saveAlert()"
                :class="can('sakuna_alerts','publish') ? 'text-slate-700 bg-white border border-slate-200 hover:border-brand-200 hover:text-brand-700' : 'text-white bg-brand-600 hover:bg-brand-700'"
                class="flex-1 py-2.5 text-sm font-semibold rounded-2xl transition-colors disabled:opacity-50">
                <span x-text="alertSaving && !alertSendAfterSave ? 'Saving…' : (alertFormMode === 'create' ? 'Save as Draft' : 'Save Changes')"></span>
            </button>
            {{-- The writer who may publish goes straight on to review and send (critique 2026-11-03). --}}
            <button x-show="can('sakuna_alerts','publish')" :disabled="alertSaving || !alertFormReady" @click="alertSendAfterSave = true; saveAlert()"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl transition-colors disabled:opacity-50 inline-flex items-center justify-center gap-1.5">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span x-text="alertSaving && alertSendAfterSave ? 'Saving…' : 'Save and review sending'"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}
