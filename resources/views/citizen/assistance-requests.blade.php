@php
    // TAB 04: fixtures live in resources/data/citizen/assistance-requests.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/citizen/assistance-requests.php');
    // The resident's own requests come from the API after paint
    // (GET /citizen/requests). This was a fixture list, and the wizard only
    // added a row to the page: nothing reached the Municipality.
    $requests = [];

    // Relocated to config/esperanza_tulong_types.php, so this file has a
    // single source of truth for every Tulong fee (see admin Settings >
    // Service Catalog) — same mechanism as the Dokyu municipal/barangay
    // catalogs.
    // Staff-only services (psychological first aid, CICL, child protection,
    // VAWC: filed by an officer on a referral) are never in the resident's
    // list (welfare round, 2026-10-29); the server refuses them from here too.
    // The lookups below keep the whole catalog, so such a case filed FOR the
    // resident still reads right in their list.
    $allTulongTypes = config('esperanza_tulong_types.types');
    $assistanceTypes = collect($allTulongTypes)->reject(fn ($t) => ! empty($t['staffOnly']))->values()->all();

    // Attach expected-processing-time onto each seeded request by matching its
    // type against $assistanceTypes, same mechanism as the Dokyu tracker.
    $daysByAssistType = collect($allTulongTypes)->pluck('days', 'name');
    $officeByAssistType = collect($allTulongTypes)->pluck('office', 'name');
    // By service key too: the API's service_key is exact, and the backend's
    // display name differs from this catalog's for TUPAD and ERPAT.
    $daysByAssistKey = collect($allTulongTypes)->filter(fn ($t) => ! empty($t['key']))->pluck('days', 'key');
    // The pickup directory is keyed by an office's SHORT name, which the API
    // does not send; the catalog has it per service key.
    $officeShortByAssistKey = collect($allTulongTypes)->filter(fn ($t) => ! empty($t['key']))->pluck('office_short', 'key');
    // How each service is worked (welfare round, 2026-10-29): a 'session'
    // case shows its schedule, a 'counseling' one says what is shared stays
    // with the MSWDO, and one with a certificate offers it once issued.
    $kindByAssistKey = collect($allTulongTypes)->filter(fn ($t) => ! empty($t['key']))
        ->mapWithKeys(fn ($t) => [$t['key'] => ['kind' => $t['processingKind'] ?? null, 'couple' => ! empty($t['sessionCouple']), 'certificate' => $t['certificate']['title'] ?? null]])
        ->all();

    // Where to follow up / claim assistance, keyed by office name.
    $pickupDirectory = $__fixtures['pickupDirectory'];

@endphp

<x-layouts.citizen title="Tulong" subtitle="Submit and follow up assistance program requests." active="assistance-requests">
    <div
        x-data="{
            open: false,
            tab: 'all',
            requests: [],
            requestsLoading: true,
            requestsFailed: false,
            daysByType: @js($daysByAssistType),
            daysByKey: @js($daysByAssistKey),
            officeShortByKey: @js($officeShortByAssistKey),
            officeByType: @js($officeByAssistType),
            kindByKey: @js($kindByAssistKey),
            papeles: [],
            // Tulong runs its own case lifecycle on the server (the eleven
            // statuses of config/esperanza_tulong_lifecycle.php); these are
            // its terminal ones.
            // A status the page does not know (a new one on the server, before
            // this page learns it) is flagged, never passed off as Draft
            // (2026-10-17): a dashed amber chip that says so.
            chip(status) {
                const known = this.statusStyles[status];
                if (known) return known;
                if (status && !this._unknownSeen?.[status]) {
                    this._unknownSeen = { ...(this._unknownSeen || {}), [status]: true };
                    console.warn('Unknown request status from the server:', status);
                }
                return ['bg-amber-50 text-amber-800 ring-amber-400 border border-dashed border-amber-400', 'bg-amber-500'];
            },
            isUnknownStatus(status) { return !!status && !this.statusStyles[status]; },
            doneStatuses: ['Completed', 'Rejected', 'Cancelled'],
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Initial Screening': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Needs Requirements': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Assessment': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'For Approval': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Returned for Reassessment': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Assistance Processing': ['bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
                'Ready for Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Cancelled': ['bg-slate-100 text-ink-muted ring-slate-200', 'bg-slate-400'],
            },
            // Rejected and cancelled requests have their own tab and tile, so the
            // tiles add up to the total (live test 2026-10-07, UX-7).
            stoppedStatuses: ['Rejected', 'Cancelled'],
            get filteredRequests() {
                return this.requests.filter(r => {
                    if (this.tab === 'all') return true;
                    const stopped = this.stoppedStatuses.includes(r.status);
                    if (this.tab === 'stopped') return stopped;
                    const isDone = this.doneStatuses.includes(r.status);
                    return this.tab === 'done' ? (isDone && !stopped) : !isDone;
                });
            },
            pickupDirectory: @js($pickupDirectory),
            progressOpen: false,
            // The proof-of-release preview inside the request's progress view.
            proofOpen: false,
            selectedRequest: null,
            resubmitNote: '',
            resubmitting: false,
            // The resident withdraws their own request until the office has
            // recommended it (end-to-end test 2026-10-07): confirmed in the
            // panel itself, with an optional reason.
            withdrawOpen: false,
            withdrawReason: '',
            withdrawing: false,
            lang(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
            apiMessage(err, fallback) { return err && err.localised ? err.localised(this.$store.lang.current) : fallback; },
            formatDay(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return isNaN(d) ? iso : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            async loadRequests() {
                this.requestsLoading = true;
                this.requestsFailed = false;
                try {
                    const res = await $api.getAll('/citizen/requests', { params: { sort: '-submitted_at' } });
                    this.requests = (res.data || []).filter((r) => r.type === 'tulong').map((r) => ({
                        ref: r.ref,
                        type: r.service || '—',
                        serviceKey: r.service_key || null,
                        office: r.office || this.officeByType[r.service] || '',
                        purpose: '',
                        days: this.daysByKey[r.service_key] || this.daysByType[r.service] || '3-5 working days',
                        submitted: this.formatDay(r.submitted),
                        submitted_at: r.submitted,
                        status: r.status,
                        requirements: null,
                    }));
                } catch { this.requestsFailed = true; } finally {
                    this.requestsLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            async loadPapeles() {
                try {
                    const res = await $api.get('/citizen/papeles');
                    this.papeles = res.data || [];
                } catch { this.papeles = []; }
            },
            applyDetail(r, d) {
                r.status = d.status;
                r.office = d.office || r.office;
                r.remarks = d.decision_remarks || null;
                r.purpose = (d.form_data && d.form_data.purpose) ? [].concat(d.form_data.purpose).join(', ') : r.purpose;
                r.canResubmit = !!d.can_resubmit;
                r.canWithdraw = !!d.can_withdraw;
                // Required documents not uploaded yet hold the resubmit back (QA run 2026-10-05).
                r.missingRequired = d.missing_required || [];
                r.payment = d.payment || null;
                // The photo or scan the office took at release, once released
                // (Esperanza QA, 2026-10-07). A signed, short-lived link from the
                // resident's own request detail.
                r.releaseProof = d.proof_of_release?.file || null;
                // What was approved and what was received (Tulong only).
                r.assistance = d.assistance || null;
                // Track Request's history, the documents issued for it and what
                // was filed, for the printed notice (scope round 2026-10-15).
                r.history = d.history || [];
                r.issuedDocuments = d.issued_documents || [];
                // The service's steps, who is responsible, and which are done (2026-10-17).
                r.workflow = d.workflow || null;
                // Where and how to claim it, from the server, while it waits
                // to be claimed (Ready for Release); null at any other status.
                r.pickup = d.pickup || null;
                r.formSnapshot = d.form_data || {};
                r.barangay = d.barangay || r.barangay || null;
                // A group session's case (welfare round, 2026-10-29): the session
                // it is scheduled to, { ref, date, start_time, venue, status }, or
                // null while it waits for one.
                r.session = d.session || null;
                r.requirements = (d.requirements || []).map((q) => ({
                    key: q.key,
                    name: q.label,
                    status: q.status === 'Pending Review' ? (q.file ? 'Submitted' : 'Not submitted') : q.status,
                    reject_reason: q.reason,
                    remarks: q.remarks,
                    file: q.file?.name || null,
                    attachable: !!q.attachable,
                    // Flagged, or rejected while the office can still review a
                    // replacement (Esperanza QA, 2026-10-07).
                    replaceable: !!q.replaceable,
                    _busy: false,
                }));
            },
            async openProgress(r) {
                this.selectedRequest = r;
                this.progressOpen = true;
                this.proofOpen = false;
                this.withdrawOpen = false;
                this.withdrawReason = '';
                try {
                    const res = await $api.get('/citizen/requests/' + encodeURIComponent(r.ref));
                    this.applyDetail(r, res.data);
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not load ' + r.ref + '.'), variant: 'error' });
                } finally { this.$nextTick(() => window.renderIcons?.()); }
            },
            async sendRequirementFile(req, file, action) {
                if (!file || req._busy) return;
                const r = this.selectedRequest;
                const form = new FormData();
                form.append('file', file);
                req._busy = true;
                try {
                    const res = await $api.post('/citizen/requests/' + encodeURIComponent(r.ref) + '/requirements/' + encodeURIComponent(req.key) + '/' + action, form);
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: action === 'replace' ? this.lang('Replacement uploaded — awaiting review.', 'Na-upload ang kapalit — hinihintay ang pagsusuri.') : this.lang('Document attached.', 'Naikabit ang dokumento.'), variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, this.lang('Could not upload the file.', 'Hindi ma-upload ang file.')), variant: 'error' });
                } finally { req._busy = false; this.$nextTick(() => window.renderIcons?.()); }
            },
            replaceRequirementFile(req, file) { return this.sendRequirementFile(req, file, 'replace'); },
            attachRequirementFile(req, file) { return this.sendRequirementFile(req, file, 'attach'); },
            get canResubmitRequirements() {
                return !!(this.selectedRequest && this.selectedRequest.canResubmit);
            },
            async resubmitRequirements() {
                if (this.resubmitting || !this.canResubmitRequirements) return;
                const r = this.selectedRequest;
                this.resubmitting = true;
                try {
                    const res = await $api.post('/citizen/requests/' + encodeURIComponent(r.ref) + '/resubmit');
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: this.lang(r.ref + ' resubmitted for review.', 'Naisumite muli ang ' + r.ref + ' para sa pagsusuri.'), variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not resubmit.'), variant: 'error' });
                } finally { this.resubmitting = false; }
            },
            async withdrawRequest() {
                const r = this.selectedRequest;
                if (this.withdrawing || !r || !r.canWithdraw) return;
                this.withdrawing = true;
                try {
                    const res = await $api.post('/citizen/requests/' + encodeURIComponent(r.ref) + '/withdraw', { reason: this.withdrawReason.trim() || null });
                    this.applyDetail(r, res.data);
                    this.withdrawOpen = false;
                    this.$dispatch('toast', { message: this.lang(r.ref + ' withdrawn.', 'Binawi ang ' + r.ref + '.'), variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, this.lang('Could not withdraw the request.', 'Hindi mabawi ang kahilingan.')), variant: 'error' });
                } finally { this.withdrawing = false; this.$nextTick(() => window.renderIcons?.()); }
            },
            // Tulong's main line, as config/esperanza_tulong_lifecycle.php
            // declares it (tests/Feature/TulongLifecycleTest.php pins the two).
            // A side state sits on the step it branches from.
            stepOrder: ['Submitted', 'Initial Screening', 'Assessment', 'For Approval', 'Assistance Processing', 'Ready for Release', 'Completed'],
            stepIndex(status) {
                const side = { 'Needs Requirements': 'Initial Screening', 'Returned for Reassessment': 'For Approval' };
                // An unknown status sits on no step (it is flagged below the steps).
                return this.stepOrder.indexOf(side[status] || status);
            },
            // How the service is worked: 'session', 'counseling', 'referral', ...
            kindOf(r) { return (r && this.kindByKey[r.serviceKey]?.kind) || null; },
            isCouple(r) { return !!(r && this.kindByKey[r.serviceKey]?.couple); },
            clock(hm) {
                const m = /^(\d{1,2}):(\d{2})/.exec(String(hm || ''));
                if (!m) return hm || '';
                const h = Number(m[1]);
                return (h % 12 || 12) + ':' + m[2] + (h < 12 ? ' AM' : ' PM');
            },
            sessionStatus(s) {
                return { Scheduled: this.lang('Scheduled', 'Naka-iskedyul'), Done: this.lang('Done', 'Tapos na'), Cancelled: this.lang('Cancelled', 'Kinansela') }[s?.status] || s?.status || '';
            },
            // The certificates issued on this case, still valid or not; the
            // service's catalog says it gives one.
            certDocs(r) { return r && this.kindByKey[r.serviceKey]?.certificate ? (r.issuedDocuments || []) : []; },
            openCert(r, d) {
                window.openWelfareCertificate?.(d, { service_key: r.serviceKey, title: this.kindByKey[r.serviceKey]?.certificate, session: r.session });
            },
            peso(v) {
                if (v === null || v === undefined || v === '') return '';
                const n = Number(v);
                return isNaN(n) ? String(v) : '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            get pickupInfo() {
                const r = this.selectedRequest;
                if (!r) return null;
                // The directory is keyed by the office's short name ('MSWDO'),
                // not the full name the API sends; where an office is listed as
                // 'A / B', A is where to claim it.
                const primaryOffice = (this.officeShortByKey[r.serviceKey] || r.office || '').split('/')[0].trim();
                return this.pickupDirectory[primaryOffice] || { contact: 'Municipal Hall Trunkline (056) 333-1000', address: 'Municipal Hall, Poblacion, Esperanza, Masbate' };
            },
            get expectedRelease() {
                const r = this.selectedRequest;
                if (!r) return '';
                const match = r.days.match(/(\d+)(?!.*\d)/);
                let left = match ? parseInt(match[1]) : 5;
                const d = new Date(r.submitted);
                // Working days skip Saturdays and Sundays, the same rule staff
                // are alerted by when a request runs past this date.
                if (/working/i.test(r.days)) {
                    while (left > 0) { d.setDate(d.getDate() + 1); if (d.getDay() !== 0 && d.getDay() !== 6) left--; }
                } else {
                    d.setDate(d.getDate() + left);
                }
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            // Deep-link landing: a notification click appends ?ref=TUL-2026-xxxx
            // (see citizen/notifications.blade.php) — open that exact
            // request's tracking modal instead of just landing on the list.
            init() {
                // Notifications are server-driven now (see resources/js/app.js) —
                // the backend lifecycle itself creates one on every status
                // change, so there is nothing left to backfill here.
                const ref = new URLSearchParams(window.location.search).get('ref');
                this.loadPapeles();
                this.loadRequests().then(() => {
                    if (!ref) return;
                    const match = this.requests.find(r => r.ref === ref);
                    if (match) this.openProgress(match);
                });
            },
        }"
        class="animate-fade-up"
    >

        <x-ui.page-intro id="tulong" icon="hand-heart" title="Paano gumagana ang Tulong" titleEn="How Tulong works" color="brand">
            <x-ui.t
                fil="Piliin ang programang tulong o ayuda na kailangan mo, sabihin sa amin ang iyong sitwasyon, ikabit ang mga requirement, pagkatapos i-submit. Halos lahat ng programa rito ay libreng i-apply — nagsisimula sa TUL- ang reference number."
                en="Choose the assistance or ayuda program you need, tell us about your situation, attach your requirements, then submit. Almost every program here is free to apply for — reference numbers start with TUL-."
            />
        </x-ui.page-intro>

        <x-citizen.approval-notice service="Tulong" />

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
            {{-- Counted from the resident's own requests. A fifth tile showed
                 "Ayuda Received ₱42,500" to everyone; no amount received is
                 recorded anywhere, so it is gone. --}}
            <x-ui.stat-card layout="compact" class="col-span-2 sm:col-span-1" label="Kabuuang Kahilingan" labelEn="Total Requests" value="0" valueBind="requests.length" icon="hand-heart" color="brand" :delay="0" />
            <x-ui.stat-card layout="compact" label="Isinasagawa" labelEn="Processing" value="0" valueBind="requests.filter(r => !doneStatuses.includes(r.status) && r.status !== 'Ready for Release').length" icon="loader" color="orange" :delay="40" />
            <x-ui.stat-card layout="compact" label="Handa nang Ilabas" labelEn="Ready for Release" value="0" valueBind="requests.filter(r => r.status === 'Ready for Release').length" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card layout="compact" label="Kumpleto" labelEn="Completed" value="0" valueBind="requests.filter(r => r.status === 'Completed').length" icon="badge-check" color="deep" :delay="120" />
            <x-ui.stat-card layout="compact" label="Hindi Natuloy" labelEn="Not Completed" value="0" valueBind="requests.filter(r => stoppedStatuses.includes(r.status)).length" icon="circle-x" color="red" :delay="160" sublabel="Tinanggihan o kinansela" sublabelEn="Rejected or cancelled" />
        </div>

        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-ink"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
            <x-citizen.status-guide type="tulong" />
        </div>

        <div class="surface-glass rounded-[26px] px-3 py-2.5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2.5 min-w-0 max-w-full">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full overflow-x-auto">
                        @foreach(['all' => ['Lahat', 'All'], 'active' => ['Isinasagawa', 'In Progress'], 'done' => ['Kumpleto', 'Completed'], 'stopped' => ['Hindi Natuloy', 'Not Completed']] as $key => [$labelFil, $labelEn])
                            <button
                                @click="tab = '{{ $key }}'"
                                class="tap px-2.5 py-1.5 text-xs font-medium rounded-xl transition-colors whitespace-nowrap"
                                :class="tab === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                            ><x-ui.t :fil="$labelFil" :en="$labelEn" /></button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filteredRequests.length + ' ' + ($store.lang.current === 'en' ? 'of ' + requests.length + ' requests' : 'sa ' + requests.length + ' kahilingan')"></p>
                </div>

            <div
                x-data="{
                    open: false,
                    step: 1,
                    selectedType: null,
                    attachments: {},
                    showFilePicker: null,
                    submitted: false,
                    submitting: false,
                    types: @js($assistanceTypes),
                    submittedRef: null,
                    attachFile(i, file) { if (file) this.attachments[i] = { kind: 'file', file, name: file.name }; this.showFilePicker = null; },
                    attachPapeles(i, doc) { this.attachments[i] = { kind: 'papeles', id: doc.id, name: doc.name }; this.showFilePicker = null; },
                    search: '',
                    selectedCategory: null,
                    categoryIcons: {
                        'Medical & Health': 'stethoscope', 'Burial & Bereavement': 'flower', 'Education': 'graduation-cap',
                        'Financial Assistance': 'wallet', 'Food & Relief': 'package', 'Social Protection': 'shield-check',
                        'Employment & Livelihood': 'briefcase',
                        // The MSWDO's services beyond assistance (welfare round, 2026-10-29).
                        'Counseling & Case Management': 'message-circle-heart', 'Marriage & Family': 'heart-handshake',
                        'Children & Youth': 'baby', 'Women & Children Protection': 'shield-alert',
                        'Certifications & Referrals': 'file-user', 'Disaster Assistance': 'house-plus',
                    },
                    formData: {},
                    // The programs the backend offers now (GET /services?type=tulong), as
                    // Dokyu does: one switched off in the Service Catalog is not listed,
                    // since this tab is the resident's list of programs (owner, 2026-10-15).
                    // Each program's fee, processing time, requirements, steps and
                    // amount come from that same answer (window.applyServiceCatalog);
                    // the built-in list keeps only the form fields.
                    offered: null,
                    async loadOffered() {
                        try {
                            const res = await $api.get('/services', { params: { type: 'tulong' } });
                            const before = JSON.stringify(this.types[this.selectedType]?.requirements || null);
                            // A program added in the Service Catalog after this site was
                            // built (2026-10-17) is listed too.
                            // A staff-only service never reaches this list, from the server or not.
                            const meta = window.applyServiceCatalog(this.types, (res.data || []).filter((sv) => !sv.staff_only), (sv) => ({
                                key: sv.key, name: sv.name, category: sv.category || 'Social Protection', level: sv.level,
                                office: sv.office, office_short: sv.office_short || sv.office, amount: sv.form_spec?.entitlement || '',
                                fee: sv.fee?.display || 'Free', days: sv.days || '', icon: 'hand-heart', desc: '',
                            }));
                            // Attachments follow the requirement list by position.
                            if (this.selectedType !== null && before !== JSON.stringify(this.types[this.selectedType]?.requirements || null)) this.attachments = {};
                            Object.values(meta).forEach((sv) => { if (sv.days) this.daysByKey[sv.key] = sv.days; });
                            this.requests.forEach((r) => { if (r.serviceKey && meta[r.serviceKey]?.days) r.days = meta[r.serviceKey].days; });
                            const keys = (res.data || []).map((sv) => sv.key);
                            this.offered = keys.length ? keys : null;
                        } catch { this.offered = null; }
                    },
                    isOffered(t) { return !t.staffOnly && (!this.offered || !t.key || this.offered.includes(t.key)); },
                    get filteredTypes() { return this.types.map((t, i) => ({ ...t, i })).filter(t => this.isOffered(t) && t.name.toLowerCase().includes(this.search.toLowerCase())); },
                    // A category counts only the programs on offer: a switched-off
                    // one was counted while not listed (parity check, 2026-10-26).
                    get assistanceCategories() {
                        const counts = {};
                        this.types.filter(t => this.isOffered(t)).forEach(t => { counts[t.category] = (counts[t.category] || 0) + 1; });
                        return Object.keys(counts).map(name => ({ name, count: counts[name], icon: this.categoryIcons[name] || 'folder' }));
                    },
                    get typesInCategory() { return this.filteredTypes.filter(t => t.category === this.selectedCategory); },
                    chooseCategory(cat) { this.selectedCategory = cat; },
                    backToCategories() { this.selectedCategory = null; this.search = ''; },
                    selectType(i) {
                        this.selectedType = i;
                        this.attachments = {};
                        this.formData = {};
                        (this.types[i]?.fields || []).forEach(f => {
                            if (f.type === 'checkbox-group') { this.formData[f.key] = []; }
                            else if (f.type === 'table') { this.formData[f.key] = []; }
                            else { this.formData[f.key] = f.default || ''; }
                        });
                        Object.entries(window.profileDefaults(this.types[i]?.fields, this.$store.citizenSession.account)).forEach(([k, v]) => {
                            if (this.formData[k] === '') this.formData[k] = v;
                        });
                        window.prefillFamilyTables(this.types[i]?.fields, this.formData);
                        this.stepError = '';
                        this.stepWarning = '';
                        this.attachWarned = false;
                    },
                    // Same checks as the Dokyu wizard: required fields block,
                    // missing attachments are said once.
                    stepError: '',
                    stepWarning: '',
                    attachWarned: false,
                    // The applicant's mobile number and any number field: '' when every one is fine.
                    phoneProblem() {
                        const lang = this.$store.lang.current;
                        const own = window.phMobile.error(this.applicant.mobile, false, lang);
                        if (own) return own;
                        for (const f of (this.types[this.selectedType]?.fields || []).filter((f) => f.type === 'tel')) {
                            const err = window.phMobile.error(this.formData[f.key], !!f.required, lang);
                            if (err) return f.label + ': ' + err;
                        }
                        return '';
                    },
                    // A field asked only after another answer (the course, for a
                    // college year: QA2 of 2026-10-07). Hidden, it is neither
                    // required nor sent.
                    // Age worked out from a birth date (the form builder's Age), as on Dokyu.
                    computeAge(dateStr) {
                        if (!dateStr) return null;
                        const dob = new Date(dateStr);
                        if (isNaN(dob)) return null;
                        const today = new Date();
                        let age = today.getFullYear() - dob.getFullYear();
                        const monthDiff = today.getMonth() - dob.getMonth();
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;
                        return age;
                    },
                    fieldShown(f) {
                        return window.showIfMet(f?.showIf, this.formData);
                    },
                    // The requirements this application owes: one whose showIf
                    // does not match the answers is not asked (2026-10-26). Each
                    // keeps its place in the full list, which keys the attachments.
                    get shownReqs() {
                        const t = this.types[this.selectedType];
                        const rules = t?.requirementRules || window.requirementRules(t?.requirements);
                        return rules.map((r, i) => ({ label: r.label, req: r.label, i })).filter((r) => window.showIfMet(rules[r.i].showIf, this.formData));
                    },
                    missingRequired() {
                        return (this.types[this.selectedType]?.fields || []).filter((f) => {
                            if (!f.required || f.type === 'derived_age' || !this.fieldShown(f)) return false;
                            const v = this.formData[f.key];
                            return Array.isArray(v) ? v.length === 0 : String(v ?? '').trim() === '';
                        }).map((f) => f.label);
                    },
                    // A number answer below zero (owner's manual test, 2026-11-06:
                    // the form took -3 household members). The inputs say min 0;
                    // this is the check, for a typed minus too. Table cells included.
                    negativeNumbers() {
                        const below = (v) => String(v ?? '').trim() !== '' && Number(v) < 0;
                        // A projected net income may be a loss, as the server allows (QA 2026-11-06).
                        const mayBeNegative = (key) => String(key || '').endsWith('net_income');
                        const bad = [];
                        for (const f of (this.types[this.selectedType]?.fields || [])) {
                            if (!this.fieldShown(f)) continue;
                            if (f.type === 'number' && !mayBeNegative(f.key) && below(this.formData[f.key])) bad.push(f.label);
                            if (f.type === 'table') {
                                (f.columns || []).filter((c) => c.type === 'number' && !c.options && !mayBeNegative(c.key)).forEach((c) => {
                                    if ((this.formData[f.key] || []).some((row) => below(row?.[c.key]))) bad.push(f.label + ': ' + c.label);
                                });
                            }
                        }
                        return bad;
                    },
                    advance() {
                        const en = this.$store.lang.current === 'en';
                        this.stepError = '';
                        this.stepWarning = '';
                        if (this.step === 2) {
                            const missing = this.missingRequired();
                            if (missing.length) {
                                this.stepError = (en ? 'Please fill in: ' : 'Pakipunan ang: ') + missing.join(', ');
                                return;
                            }
                            const negative = this.negativeNumbers();
                            if (negative.length) {
                                this.stepError = (en ? 'Must be 0 or more: ' : 'Dapat 0 o higit pa: ') + negative.join(', ');
                                return;
                            }
                            // Every number field takes a Philippine mobile number (Esperanza QA, 2026-10-07).
                            const badPhone = this.phoneProblem();
                            if (badPhone) {
                                this.stepError = badPhone;
                                return;
                            }
                            // Names only what the resident owes: the MSWDO interview is
                            // done at the office, and nothing can be attached for it.
                            const owed = this.shownReqs.filter((r) => reqKind(r.label) === 'required' && !this.attachments[r.i]).map((r) => r.label);
                            if (owed.length && !this.attachWarned) {
                                this.attachWarned = true;
                                this.stepWarning = en
                                    ? 'Still to attach, and the office needs them to assess the request: ' + owed.join(', ') + '. Attach them now, or press Continue to add them later from Track Request.'
                                    : 'Hindi pa naikakabit, at kailangan ito ng opisina: ' + owed.join(', ') + '. Ikabit na ngayon, o pindutin ang Magpatuloy para idagdag mamaya sa Subaybayan.';
                                return;
                            }
                        }
                        this.step++;
                        if (this.step === 3 && this.tulongFree) this.step = 4;
                    },
                    toggleChecklist(fieldKey, option) {
                        const list = this.formData[fieldKey] || [];
                        const i = list.indexOf(option);
                        if (i === -1) { list.push(option); } else { list.splice(i, 1); }
                        this.formData[fieldKey] = list;
                    },
                    addTableRow(f) {
                        if (!this.formData[f.key]) this.formData[f.key] = [];
                        const row = {};
                        f.columns.forEach(c => { row[c.key] = ''; });
                        this.formData[f.key].push(row);
                    },
                    applicant: { firstName: '', lastName: '', address: '', mobile: '', email: '' },
                    seedApplicant() {
                        const a = this.$store.citizenSession.account;
                        this.applicant = a ? {
                            firstName: a.firstName, lastName: a.lastName, address: a.address, mobile: a.mobile, email: a.email,
                        } : { firstName: '', lastName: '', address: '', mobile: '', email: '' };
                    },
                    // No online payment exists (LGU question Q3). The GCash and Maya
                    // 'numbers' this listed were placeholders a resident could have
                    // sent real money to.
                    stepLabels: {
                        fil: ['Pumili ng Tulong', 'Detalye ng Aplikasyon', 'Bayad', 'I-review at I-submit'],
                        en: ['Choose Assistance', 'Application Details', 'Payment', 'Review & Submit'],
                    },
                    stepIcons: ['hand-heart', 'user-round', 'credit-card', 'clipboard-check'],
                    // Free assistance has no payment step (live test 2026-10-07, UX-12):
                    // it only said 'Hindi aplikable'. Step 3 is skipped both ways.
                    get tulongFree() { return this.selectedType !== null && this.types[this.selectedType]?.fee === 'Free'; },
                    reset() { this.step = 1; this.selectedType = null; this.attachments = {}; this.showFilePicker = null; this.submitted = false; this.submittedRef = null; this.search = ''; this.formData = {}; this.selectedCategory = null; this.stepError = ''; this.stepWarning = ''; this.attachWarned = false; },
                    // POST /citizen/requests, then each attachment onto its
                    // requirement. The server assigns the ref and notifies the
                    // office; a failed attachment can be added from Track Request.
                    async submitRequest() {
                        const type = this.types[this.selectedType];
                        const formData = JSON.parse(JSON.stringify(this.formData || {}));
                        for (const f of (type?.fields || [])) {
                            if (!this.fieldShown(f)) delete formData[f.key];
                            if (!this.fieldShown(f) || !/^others?$/i.test(formData[f.key] || '') || !String(formData[f.key + '_other'] || '').trim()) delete formData[f.key + '_other'];
                        }
                        // The applicant block says 'edit if needed', so what the
                        // resident confirmed or corrected goes with the request.
                        Object.assign(formData, {
                            applicant_first_name: this.applicant.firstName || '',
                            applicant_last_name: this.applicant.lastName || '',
                            applicant_address: this.applicant.address || '',
                            applicant_mobile: this.applicant.mobile || '',
                            applicant_email: this.applicant.email || '',
                        });
                        const res = await $api.post('/citizen/requests', { service_key: type.key, form_data: formData });
                        const created = res.data || {};
                        const serverReqs = created.requirements || [];
                        const names = type.requirements || [];
                        const shown = new Set(this.shownReqs.map((r) => r.i));
                        const conditional = (type.requirementRules || []).some((r) => r.showIf);
                        const failed = [];
                        for (const [i, att] of Object.entries(this.attachments)) {
                            if (!att || !shown.has(Number(i))) continue;
                            const label = names[i];
                            const target = serverReqs.find((q) => q.label === label) || (conditional ? null : serverReqs[Number(i)]);
                            if (!target) { failed.push(label); continue; }
                            try {
                                const url = '/citizen/requests/' + encodeURIComponent(created.ref) + '/requirements/' + encodeURIComponent(target.key) + '/attach';
                                if (att.kind === 'papeles') {
                                    await $api.post(url, { papeles_id: att.id });
                                } else {
                                    const form = new FormData();
                                    form.append('file', att.file);
                                    await $api.post(url, form);
                                }
                            } catch { failed.push(label); }
                        }
                        this.submittedRef = created.ref;
                        await this.loadRequests();
                        return { ref: created.ref, failed };
                    },
                }"
                x-init="seedApplicant(); loadOffered(); $watch('$store.citizenSession.account', () => seedApplicant()); $nextTick(() => { const want = new URLSearchParams(window.location.search).get('new'); if (!want) return; const i = types.findIndex(t => t.key === want); if (i >= 0) { search = types[i].name; selectType(i); } open = true; })"
                x-effect="!open && reset()"
                class="contents"
            >
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <x-ui.button @click="open = true" ::disabled="$store.citizenSession.account && !$store.citizenSession.account.fullServiceAccess" icon="hand-heart" size="sm" class="w-full sm:w-auto justify-center"><x-ui.t fil="Bagong Kahilingan sa Tulong" en="New Tulong Request" /></x-ui.button>
                {{-- The certificates list sits at the bottom of the page; this finds it (owner's manual test, 2026-11-06). --}}
                <x-ui.button variant="secondary" size="sm" icon="award" href="#my-certificates" class="w-full sm:w-auto justify-center"><x-ui.t fil="Aking mga sertipiko" en="My certificates" /></x-ui.button>
                <x-ui.modal title="Bagong Kahilingan sa Tulong" titleEn="New Tulong Request" maxWidth="3xl" :fullscreen="true">
                    <!-- Success state -->
                    <div x-show="submitted" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                        <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                            <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                            <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse" style="animation-delay: 0.35s;"></span>
                            <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                                <i data-lucide="check" class="w-7 h-7"></i>
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-ink">
                            <x-ui.t fil="Naisumite ang Kahilingan!" en="Request Submitted!" />
                            <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                        </p>
                        <p class="text-xs text-ink-muted mt-1 max-w-[220px]"><x-ui.t fil="Aabisuhan ka namin kapag na-review na ang iyong kahilingan sa Tulong." en="We'll notify you once your Tulong request is reviewed." /></p>
                    </div>

                    <div x-show="!submitted">
                    {{-- Step indicator: equal columns, each circle centred in its own, the label
                         below, and each line drawn from one circle's centre to the next. It was a
                         row sized by its labels: the lines came out of different lengths, the last
                         step left a gap, and a one-line label ("Bayad") dropped its circle below
                         the others (2026-10-07). --}}
                    <div class="grid mb-8" :class="tulongFree ? 'grid-cols-3' : 'grid-cols-4'">
                        <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                            <div class="relative flex flex-col items-center text-center px-1" x-show="!(i === 2 && tulongFree)">
                                <div x-show="i < 3" class="absolute top-4 left-[calc(50%_+_26px)] right-[calc(-50%_+_26px)] h-1 rounded-full transition-colors duration-[300ms] ease-standard" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-brand-600/10'"></div>
                                <span
                                    class="relative w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-[300ms] ease-standard"
                                    :class="step > i + 1 ? 'bg-emerald-500 text-white shadow-sm' : (step === i + 1 ? 'clay-blue ring-4 ring-brand-100' : 'bg-white text-ink-faint ring-1 ring-brand-600/15')"
                                >
                                    <i data-lucide="check" class="w-4 h-4" x-show="step > i + 1" x-cloak></i>
                                    <i :data-lucide="stepIcons[i]" class="w-4 h-4" x-show="step <= i + 1"></i>
                                </span>
                                <span class="mt-2 text-[11px] max-w-[110px] leading-tight transition-colors duration-[300ms] ease-standard" :class="step === i + 1 ? 'text-ink font-bold' : 'text-ink-muted'" x-text="label"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Step 1: choose assistance type -->
                    <div x-show="step === 1" class="space-y-4">
                        <div class="flex flex-col gap-3">
                            <h4 class="text-base font-bold text-ink"><x-ui.t fil="Piliin ang uri ng tulong na kailangan mo." en="Choose the type of assistance you need." /></h4>
                            <div class="relative">
                                    <i data-lucide="search" class="w-4 h-4 text-ink-faint absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input aria-label="Search"
                                        type="text" x-model="search"
                                        x-bind:placeholder="$store.lang.current === 'en' ? 'Search assistance programs...' : 'Maghanap ng programang tulong...'"
                                        class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 pl-11 pr-4 text-sm text-ink placeholder:text-ink-faint focus:bg-white focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                    >
                                </div>
                        </div>
                        <!-- Category grid (shown first, or when searching) -->
                        <template x-if="!selectedCategory || search">
                            <div>
                                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3" x-show="!search">
                                    <template x-for="cat in assistanceCategories" :key="cat.name">
                                        <button type="button" @click="chooseCategory(cat.name)" class="choice-card group">
                                                    <span class="choice-icon"><i :data-lucide="cat.icon" class="w-5 h-5"></i></span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[15px] font-semibold text-ink truncate" x-text="cat.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5" x-text="cat.count + (cat.count === 1 ? ' program' : ' programs')"></span>
                                                    </span>
                                                    <span class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 grid place-items-center shrink-0 transition-transform duration-200 group-hover:translate-x-0.5"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                                                </button>
                                    </template>
                                </div>

                                <!-- Search results (flat, across categories) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3" x-show="search">
                                    <template x-for="type in filteredTypes" :key="type.i">
                                        <label class="choice-card" :class="selectedType === type.i && 'is-selected'">
                                                    <input type="radio" name="assist_type" class="sr-only" :checked="selectedType === type.i" @change="selectType(type.i)">
                                                    <span class="choice-icon">
                                                        <i :data-lucide="type.icon" class="w-5 h-5" x-show="selectedType !== type.i"></i>
                                                        <i data-lucide="check" class="w-5 h-5" x-show="selectedType === type.i" x-cloak></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[14.5px] font-semibold text-ink leading-snug" x-text="type.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5 truncate" x-text="type.category + ' · ' + type.office_short"></span>
                                                    </span>
                                                    <span class="shrink-0 self-start text-[11.5px] font-bold rounded-full px-2.5 py-1" :class="type.fee === 'Free' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70'" x-text="type.amount"></span>
                                                </label>
                                    </template>
                                    <div class="md:col-span-2 2xl:col-span-3 flex flex-col items-center text-center py-10 rounded-2xl bg-white border border-dashed border-brand-600/15" x-show="filteredTypes.length === 0">
                                                <span class="w-11 h-11 rounded-full bg-brand-50 text-brand-500 flex items-center justify-center mb-2.5"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Walang programang tumutugma." en="No assistance programs match your search." /></p>
                                            </div>
                                </div>
                            </div>
                        </template>

                        <!-- Programs within the chosen category -->
                        <template x-if="selectedCategory && !search">
                            <div>
                                <button type="button" @click="backToCategories()" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand-600 hover:text-brand-700 mb-3 transition-colors">
                                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                            <x-ui.t fil="Bumalik sa mga kategorya" en="Back to categories" />
                                        </button>
                                <p class="text-[15px] font-bold text-ink mb-3" x-text="selectedCategory"></p>
                                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3">
                                    <template x-for="type in typesInCategory" :key="type.i">
                                        <label class="choice-card" :class="selectedType === type.i && 'is-selected'">
                                                    <input type="radio" name="assist_type" class="sr-only" :checked="selectedType === type.i" @change="selectType(type.i)">
                                                    <span class="choice-icon">
                                                        <i :data-lucide="type.icon" class="w-5 h-5" x-show="selectedType !== type.i"></i>
                                                        <i data-lucide="check" class="w-5 h-5" x-show="selectedType === type.i" x-cloak></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[14.5px] font-semibold text-ink leading-snug" x-text="type.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5 truncate" x-text="type.office_short"></span>
                                                    </span>
                                                    <span class="shrink-0 self-start text-[11.5px] font-bold rounded-full px-2.5 py-1" :class="type.fee === 'Free' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70'" x-text="type.amount"></span>
                                                </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Step 2: application details + requirements -->
                    <div x-show="step === 2" x-cloak>
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start mb-6">
                            <div class="lg:col-span-3">
                                <p class="text-sm font-semibold text-ink mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                                <p class="text-xs text-ink-muted mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <x-ui.input name="assist_first_name" label="First Name" x-model="applicant.firstName" />
                                    <x-ui.input name="assist_last_name" label="Last Name" x-model="applicant.lastName" />
                                </div>
                                <div class="mb-3">
                                    <x-ui.input name="assist_address" label="Complete Address" icon="map-pin" x-model="applicant.address" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-ui.input name="assist_mobile" type="tel" inputmode="numeric" maxlength="11" label="Mobile Number" icon="phone" placeholder="09XXXXXXXXX" x-model="applicant.mobile" @input="applicant.mobile = window.phMobile.clean($event.target.value)" />
                                    <x-ui.input name="assist_email" type="email" label="Email Address" icon="mail" x-model="applicant.email" />
                                </div>
                            </div>

                            <!-- Live request summary -->
                            <div class="lg:col-span-2">
                                <div class="lg:sticky lg:top-0 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/60 to-white p-4">
                                    <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="receipt-text" class="w-3.5 h-3.5 text-brand-500"></i><x-ui.t fil="Buod ng Kahilingan" en="Request Summary" /></p>
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <span class="w-10 h-10 rounded-lg bg-brand-600 text-white flex items-center justify-center shrink-0"><i :data-lucide="types[selectedType]?.icon || 'hand-heart'" class="w-4.5 h-4.5"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-ink leading-snug" x-text="types[selectedType]?.name"></p>
                                            <p class="text-xs text-ink-muted truncate" x-text="types[selectedType]?.office"></p>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 text-xs border-t border-brand-100/70 pt-3">
                                        <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Tinatayang Halaga" en="Estimated Amount" /></span><span class="font-semibold text-ink text-right" x-text="types[selectedType]?.amount"></span></div>
                                        <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Oras ng Pagproseso" en="Processing Time" /></span><span class="font-medium text-slate-600" x-text="types[selectedType]?.days"></span></div>
                                        <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Requirements" en="Requirements" /></span><span class="font-medium text-slate-600" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + shownReqs.filter(r => reqKind(r.label) !== 'office').length + ' attached'"></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full-width: generic purpose OR structured fields (e.g. Solo Parent Application, FACED) — sits below the two-column grid so it isn't squeezed beside the sticky Request Summary -->
                        <!-- Generic purpose (assistance types without structured fields) -->
                        <div class="mb-6" x-show="!(types[selectedType]?.fields?.length)">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Detalye ng Kahilingan" en="Details of Request" /></label>
                            <textarea x-model="formData.purpose" aria-label="Briefly describe your situation and needs" rows="3" placeholder="Briefly describe your situation and needs" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>

                        <div class="mb-6" x-show="types[selectedType]?.fields?.length" x-cloak>
                            <p class="text-sm font-semibold text-ink mb-1" x-text="types[selectedType]?.certTitle || ''"></p>
                            <p class="text-xs text-ink-muted mb-3" x-show="types[selectedType]?.certSubtitle" x-text="types[selectedType]?.certSubtitle"></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {{-- Every question in the paper's order, a table across the full width
                                     where it stands: tables used to follow all other questions, so a
                                     business plan's expenses came after its last question (MPESO papers,
                                     2026-11-03). --}}
                                <template x-for="f in (types[selectedType]?.fields || [])" :key="f.key">
                                    <div :class="f.type === 'table' ? 'col-span-full' : ((f.type === 'checkbox-group' || f.type === 'multiselect' || f.type === 'textarea') ? 'sm:col-span-2 lg:col-span-3' : '')" x-show="fieldShown(f)">
                                        <template x-if="f.type === 'text' || f.type === 'number' || f.type === 'date' || f.type === 'tel'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <input :aria-label="f.label"
                                                    :type="f.type" x-model="formData[f.key]"
                                                    :inputmode="f.type === 'tel' ? 'numeric' : null" :maxlength="f.type === 'tel' ? 11 : null" :placeholder="f.type === 'tel' ? '09XXXXXXXXX' : null"
                                                    :min="f.type === 'number' && !String(f.key).endsWith('net_income') ? 0 : null" :step="f.type === 'number' ? 'any' : null"
                                                    @input="if (f.type === 'tel') formData[f.key] = window.phMobile.clean($event.target.value)"
                                                    class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'derived_age'">
                                            <div x-init="$watch(() => formData[f.derived_from], v => formData[f.key] = computeAge(v)); formData[f.key] = computeAge(formData[f.derived_from])">
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label"></label>
                                                <div class="w-full rounded-xl border border-slate-200 surface-pressed py-2.5 px-4 text-sm text-ink-muted" x-text="(formData[f.key] ?? null) !== null ? formData[f.key] + ' years old' : 'Set birthdate first'"></div>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'textarea'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <textarea :aria-label="f.label" x-model="formData[f.key]" rows="3" :placeholder="f.placeholder || null" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'checkbox'">
                                            <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors">
                                                <input type="checkbox" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200" :checked="formData[f.key] === true || formData[f.key] === 'Yes'" @change="formData[f.key] = $event.target.checked ? 'Yes' : ''">
                                                <span x-text="f.label + (f.required ? ' *' : '')"></span>
                                            </label>
                                        </template>
                                        <template x-if="f.type === 'select'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <select :aria-label="f.label" x-model="formData[f.key]" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                    <option value="">—</option>
                                                    <template x-for="opt in f.options" :key="opt"><option :value="opt" x-text="opt" :selected="formData[f.key] === opt"></option></template>
                                                </select>
                                                {{-- "Other" with room to say what (welfare round, 2026-10-29): sent as <key>_other. --}}
                                                <input x-show="f.allowOther && /^others?$/i.test(formData[f.key] || '')" x-cloak type="text" x-model="formData[f.key + '_other']" maxlength="120"
                                                    :aria-label="f.label + ($store.lang.current === 'en' ? ': please specify' : ': pakisabi kung ano')" :placeholder="$store.lang.current === 'en' ? 'Please specify' : 'Pakisabi kung ano'"
                                                    class="mt-2 w-full rounded-2xl border-0 surface-pressed py-2 px-4 text-sm text-ink-body focus:bg-white focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'checkbox-group' || f.type === 'multiselect'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-2" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                    <template x-for="opt in f.options" :key="opt">
                                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="(formData[f.key] || []).includes(opt) && 'border-brand-300 bg-brand-50/60 text-brand-700'">
                                                            <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-200" :checked="(formData[f.key] || []).includes(opt)" @change="toggleChecklist(f.key, opt)">
                                                            <span x-text="opt"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        {{-- A table (e.g. Family Composition, FACED Family Members), across the full
                                             width. It follows its show-if rule like every other question: it was
                                             drawn while the submit dropped its rows (parity check, 2026-10-26). --}}
                                        <template x-if="f.type === 'table'">
                                            <div>
                                                <div class="flex items-center justify-between mb-2.5">
                                                    <div>
                                                        <p class="text-sm font-semibold text-ink" x-text="f.label"></p>
                                                        {{-- Said in the table's own row name: not every table is a household
                                                             (business interests, expenses, start-up items: MPESO papers, 2026-11-03). --}}
                                                        <p class="text-xs text-ink-muted mt-0.5" x-text="($store.lang.current === 'en' ? 'Add each ' + (f.rowLabel || 'entry').toLowerCase() : 'Idagdag ang bawat ' + (f.rowLabel || 'entry').toLowerCase()) + '.'"></p>
                                                    </div>
                                                    <span class="text-[11px] text-ink-muted shrink-0" x-text="(formData[f.key] || []).length + ' ' + ($store.lang.current === 'en' ? 'added' : 'nadagdag')"></span>
                                                </div>
                                                <div class="space-y-2.5">
                                                    <template x-for="(row, ri) in (formData[f.key] || [])" :key="ri">
                                                        <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 bg-brand-100/70 rounded-full pl-1.5 pr-2.5 py-1">
                                                                    <i :data-lucide="f.rowIcon || 'list'" class="w-3 h-3"></i>
                                                                    <span x-text="row[f.columns[0].key] || ((f.rowLabel || 'Entry') + ' ' + (ri + 1))"></span>
                                                                </span>
                                                                <button aria-label="Remove" type="button" @click="formData[f.key].splice(ri, 1)" class="text-ink-faint hover:text-rose-500 transition-colors">
                                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                                </button>
                                                            </div>
                                                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                                                <template x-for="col in f.columns" :key="col.key">
                                                                    <div>
                                                                        <label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1" x-text="col.label"></label>
                                                                        <template x-if="col.options">
                                                                            <select :aria-label="col.label" x-model="row[col.key]" class="w-full rounded-xl py-1.5 px-2.5 text-xs text-ink-body focus:border-brand-400 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                                                                                <option value="">—</option>
                                                                                <template x-for="opt in col.options" :key="opt"><option :value="opt" x-text="opt" :selected="row[col.key] === opt"></option></template>
                                                                            </select>
                                                                        </template>
                                                                        <template x-if="!col.options">
                                                                            <input :aria-label="col.label" :type="col.type === 'date' ? 'date' : (col.type === 'number' ? 'number' : 'text')" :min="col.type === 'number' && !String(col.key).endsWith('net_income') ? 0 : null" x-model="row[col.key]" class="w-full rounded-xl py-1.5 px-2.5 text-xs text-ink-body focus:border-brand-400 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <div class="flex flex-col items-center text-center py-6 rounded-xl border-2 border-dashed border-slate-200" x-show="(formData[f.key] || []).length === 0">
                                                        <span class="w-9 h-9 rounded-full bg-slate-100 text-ink-muted flex items-center justify-center mb-2"><i :data-lucide="f.rowIcon || 'list'" class="w-4 h-4"></i></span>
                                                        <p class="text-xs text-ink-muted" x-text="$store.lang.current === 'en' ? 'No ' + (f.rowLabel || 'entry').toLowerCase() + ' added yet.' : 'Wala pang naidagdag na ' + (f.rowLabel || 'entry').toLowerCase() + '.'"></p>
                                                    </div>

                                                    <button
                                                        type="button" @click="addTableRow(f)"
                                                        class="w-full flex items-center justify-center gap-1.5 rounded-2xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2.5 transition-all duration-[160ms] ease-standard"
                                                    >
                                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                                        <span x-text="($store.lang.current === 'en' ? 'Add ' : 'Magdagdag ng ') + (f.rowLabel || 'Entry')"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-ink"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                            <p class="text-[11px] font-medium text-ink-muted" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + shownReqs.filter(r => reqKind(r.label) !== 'office').length"></p>
                        </div>
                        <div class="h-1 rounded-full bg-slate-100 overflow-hidden mb-3">
                            <div class="h-full bg-emerald-400 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + ((shownReqs.filter(r => reqKind(r.label) !== 'office').length ? Math.min(1, Object.keys(attachments).filter(k => attachments[k]).length / shownReqs.filter(r => reqKind(r.label) !== 'office').length) : 0) * 100) + '%'"></div>
                        </div>
                        <p class="text-xs text-ink-muted mb-3" x-show="shownReqs.length"><x-ui.t fil="Mag-upload ng file (larawan o PDF), o gamitin ang isang dokumentong naka-imbak na sa iyong Papeles. Ipapadala ang mga ito kasama ng iyong kahilingan." en="Upload a file (photo or PDF), or reuse a document already saved in your Papeles. They are sent with your request." /></p>
                        {{-- Some services ask for nothing (counseling, the Parent Effectiveness Service: welfare round, 2026-10-29). --}}
                        <p class="text-xs text-ink-muted mb-3 flex items-center gap-1.5" x-show="!shownReqs.length" x-cloak><i data-lucide="circle-check" class="w-3.5 h-3.5 text-emerald-500"></i><x-ui.t fil="Walang kailangang i-upload para sa serbisyong ito." en="Nothing to upload for this service." /></p>
                        {{-- What is shared in counseling stays with the MSWDO (RA 10173). --}}
                        <p class="text-xs text-slate-600 mb-3 flex items-start gap-1.5 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2" x-show="types[selectedType]?.processingKind === 'counseling'" x-cloak><i data-lucide="lock" class="w-3.5 h-3.5 text-brand-600 shrink-0 mt-0.5"></i><x-ui.t fil="Ang ibabahagi mo ay mananatili sa MSWDO at hindi ipapakita sa ibang opisina." en="What you share stays with the MSWDO and is not shown to other offices." /></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <template x-for="({ req, i }) in shownReqs" :key="i">
                                <div class="rounded-xl border p-3 transition-colors duration-[220ms] ease-standard" :class="attachments[i] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs text-slate-600 flex-1"><span x-text="req"></span> <span class="ml-1 inline-block rounded px-1.5 py-px text-[10px] font-semibold align-middle" :class="reqKindClass(req)" x-text="reqKindLabel(req)"></span></p>
                                        <button
                                            type="button" x-show="!attachments[i] && reqKind(req) !== 'office'"
                                            @click="showFilePicker = (showFilePicker === i ? null : i)"
                                            class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                        ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                        <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span class="truncate max-w-[140px]" x-text="attachments[i]?.name"></span>
                                            </span>
                                            <button aria-label="Close" type="button" @click="attachments[i] = null" class="text-ink-faint hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </div>
                                    </div>
                                    <div x-show="showFilePicker === i" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-brand-600/10 space-y-2">
                                        <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-white bg-brand-600 rounded-lg px-2.5 py-1.5 hover:bg-brand-700 transition-colors cursor-pointer">
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-upload ng file" en="Upload a file" />
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachFile(i, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <div x-show="papeles.length" class="space-y-1">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="O gamitin mula sa Papeles" en="Or reuse from Papeles" /></p>
                                            <template x-for="doc in papeles" :key="doc.id">
                                                <button type="button" @click="attachPapeles(i, doc)" class="w-full text-left text-[11px] text-slate-600 hover:text-brand-700 hover:bg-brand-50 rounded-xl px-2 py-1 truncate" x-text="doc.name"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: payment -->
                    <div x-show="step === 3" x-cloak>
                        <template x-if="types[selectedType]?.fee === 'Free'">
                            <div class="flex flex-col items-center text-center py-8">
                                <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-3">
                                    <i data-lucide="badge-check" class="w-6 h-6"></i>
                                </span>
                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Walang Kailangang Bayaran" en="No Payment Required" /></p>
                                <p class="text-xs text-ink-muted mt-1 max-w-[280px]" x-text="types[selectedType]?.name + ($store.lang.current === 'en' ? ' has no application fee under municipal ordinance.' : ' ay walang bayarin sa pag-apply ayon sa municipal ordinance.')"></p>
                            </div>
                        </template>

                        <template x-if="types[selectedType]?.fee !== 'Free'">
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
                                <div class="lg:col-span-3 order-2 lg:order-1">
                                    <p class="text-sm font-semibold text-ink mb-2"><x-ui.t fil="Paano magbayad" en="How to pay" /></p>
                                    <div class="rounded-xl border-0 surface-pressed p-4 space-y-2 text-xs text-slate-600 leading-relaxed">
                                        <p class="flex items-start gap-2"><i data-lucide="landmark" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i><x-ui.t fil="Babayaran mo ang bayarin nang personal sa tanggapang nakalista, at bibigyan ka roon ng opisyal na resibo." en="You pay the fee in person at the office listed, which gives you an official receipt." /></p>
                                        <p class="flex items-start gap-2 text-amber-700"><i data-lucide="shield-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="Huwag magpadala ng bayad sa anumang e-wallet o account na hindi kinumpirma ng Munisipyo." en="Never send payment to an e-wallet or account the Municipality has not confirmed." /></p>
                                    </div>
                                </div>

                                <!-- Invoice -->
                                <div class="lg:col-span-2 order-1 lg:order-2">
                                    <div class="lg:sticky lg:top-0 rounded-xl border border-slate-200 overflow-hidden">
                                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-brand-600/10">
                                            <p class="text-xs font-semibold text-ink uppercase tracking-wide"><x-ui.t fil="Buod ng Invoice" en="Invoice Summary" /></p>
                                        </div>
                                        <div class="px-4 py-3.5 text-sm">
                                            <p class="text-[10px] font-mono text-ink-muted mb-2">Municipality of Esperanza</p>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-ink-muted"><x-ui.t fil="Sisingilin" en="Bill To" /></span>
                                                <span class="text-slate-700 font-medium" x-text="applicant.firstName + ' ' + applicant.lastName"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-ink-muted"><x-ui.t fil="Item" en="Item" /></span>
                                                <span class="text-slate-700 font-medium text-right" x-text="$store.lang.current === 'en' ? 'Documentary / Notarization Fee' : 'Bayarin sa Dokumento / Notaryo'"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-ink-muted"><x-ui.t fil="Kaugnay na Aplikasyon" en="Related Application" /></span>
                                                <span class="text-slate-700 font-medium text-right" x-text="types[selectedType]?.name"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-ink-muted"><x-ui.t fil="Kokolektang Opisina" en="Collecting Office" /></span>
                                                <span class="text-slate-700 font-medium" x-text="types[selectedType]?.office"></span>
                                            </div>
                                            <div class="border-t border-dashed border-slate-200 my-2"></div>
                                            <div class="flex items-center justify-between py-1">
                                                <span class="text-sm font-semibold text-ink inline-flex items-center gap-1">
                                                    <x-ui.t fil="Kabuuang Babayaran" en="Total Due" />
                                                    <x-ui.tooltip text="This documentary fee is separate from your assistance amount — it won't be deducted from the aid you receive.">
                                                        <i data-lucide="info" class="w-3 h-3 text-ink-faint hover:text-ink-muted cursor-help"></i>
                                                    </x-ui.tooltip>
                                                </span>
                                                <span class="text-base font-bold text-brand-700" x-text="types[selectedType]?.fee"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Step 4: review & submit -->
                    <div x-show="step === 4" x-cloak>
                        <p class="text-sm font-semibold text-ink mb-3"><x-ui.t fil="I-review ang Iyong Kahilingan" en="Review Your Request" /></p>
                        <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-brand-600/[.08] bg-slate-50/60">
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i :data-lucide="types[selectedType]?.icon || 'hand-heart'" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Uri ng Tulong" en="Assistance Type" /></p>
                                        <p class="text-sm font-medium text-ink truncate" x-text="types[selectedType]?.name || ''"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Nangangasiwang Opisina" en="Handling Office" /></p>
                                        <p class="text-sm font-medium text-ink truncate" x-text="types[selectedType]?.office || ''"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Aplikante" en="Applicant" /></p>
                                        <p class="text-sm font-medium text-ink truncate" x-text="applicant.firstName + ' ' + applicant.lastName"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Bayarin sa Dokumento" en="Documentary Fee" /></p>
                                        <p class="text-sm font-medium text-ink truncate">
                                            <template x-if="types[selectedType]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                            <template x-if="types[selectedType]?.fee !== 'Free'"><span x-text="$store.lang.current === 'en' ? 'Pay at the office (' + types[selectedType]?.fee + ')' : 'Bayaran sa opisina (' + types[selectedType]?.fee + ')'"></span></template>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Structured field details (Solo Parent, FACED, etc.) -->
                        <div class="mb-5" x-show="types[selectedType]?.fields?.length" x-cloak>
                            <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2"><x-ui.t fil="Detalye ng Aplikasyon" en="Application Details" /></p>
                            <div class="rounded-xl border border-slate-200 divide-y divide-brand-600/[.08]">
                                <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type !== 'table')" :key="f.key">
                                    <div class="flex items-center justify-between gap-3 px-3.5 py-2 text-xs" x-show="fieldShown(f) && formData[f.key] && (!Array.isArray(formData[f.key]) || formData[f.key].length)">
                                        <span class="text-ink-muted shrink-0" x-text="f.label"></span>
                                        <span class="font-medium text-slate-700 text-right truncate" x-text="Array.isArray(formData[f.key]) ? formData[f.key].join(', ') : formData[f.key]"></span>
                                    </div>
                                </template>
                            </div>

                            <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type === 'table')" :key="f.key">
                                <div class="mt-3" x-show="fieldShown(f) && (formData[f.key] || []).length">
                                    <p class="text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                        <i :data-lucide="f.rowIcon || 'list'" class="w-3 h-3 text-brand-400"></i>
                                        <span x-text="f.label"></span>
                                    </p>
                                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-brand-50/60 border-b border-slate-200">
                                                    <template x-for="col in f.columns" :key="col.key">
                                                        <th class="px-2.5 py-2 text-left font-semibold text-brand-700 whitespace-nowrap" x-text="col.label"></th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-brand-600/[.08]">
                                                <template x-for="(row, ri) in (formData[f.key] || [])" :key="ri">
                                                    <tr :class="ri % 2 === 1 && 'bg-slate-50/60'">
                                                        <template x-for="col in f.columns" :key="col.key">
                                                            <td class="px-2.5 py-2 text-slate-700 whitespace-nowrap" x-text="row[col.key] || '—'"></td>
                                                        </template>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2" x-show="shownReqs.length"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 mb-5">
                            <template x-for="({ req, i }) in shownReqs" :key="i">
                                <li class="flex items-center gap-2 text-xs">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-ink-faint'"></i>
                                    <span :class="attachments[i] ? 'text-slate-600' : 'text-ink-muted'" class="truncate" x-text="req"></span>
                                </li>
                            </template>
                        </ul>

                        <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                        <div class="flex items-center rounded-xl border border-brand-600/10 bg-slate-50/60 px-4 py-4">
                            <template x-for="(step_label, i) in types[selectedType]?.process || []" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-semibold shrink-0 shadow-sm" x-text="i + 1"></span>
                                        <span class="text-[10px] text-ink-muted max-w-[80px] leading-tight" x-text="step_label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 bg-brand-200 -mt-5" x-show="i < (types[selectedType]?.process || []).length - 1"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    </div>

                    <x-slot:footer>
                        <p x-show="!submitted && (stepError || stepWarning)" x-cloak role="alert" class="mr-auto min-w-0 text-xs leading-snug" :class="stepError ? 'text-rose-600' : 'text-amber-700'" x-text="stepError || stepWarning"></p>
                        <x-ui.button variant="secondary" size="sm" x-show="!submitted" @click="stepError = ''; stepWarning = ''; step > 1 ? (step = (step === 4 && tulongFree) ? 2 : step - 1) : (open = false, reset())">
                            <span x-text="$store.lang.current === 'en' ? (step > 1 ? 'Back' : 'Cancel') : (step > 1 ? 'Bumalik' : 'Kanselahin')"></span>
                        </x-ui.button>
                        <x-ui.button
                            size="sm"
                            x-show="!submitted"
                            ::disabled="submitting || (step === 1 && selectedType === null)"
                            @click="
                                if (step < 4) { advance() }
                                else {
                                    submitting = true;
                                    submitRequest().then(({ ref, failed }) => {
                                        submitted = true;
                                        const msg = $store.lang.current === 'en'
                                            ? 'Request ' + ref + ' submitted.' + (failed.length ? ' Some files did not upload (' + failed.join(', ') + ') — add them from Track Request.' : '')
                                            : 'Naisumite ang kahilingan ' + ref + '.' + (failed.length ? ' May mga file na hindi na-upload (' + failed.join(', ') + ') — idagdag sa Subaybayan.' : '');
                                        setTimeout(() => { open = false; reset(); $dispatch('toast', { message: msg, variant: failed.length ? 'warning' : 'success' }) }, 1200);
                                    }).catch((err) => {
                                        $dispatch('toast', { message: err && err.explain ? err.explain($store.lang.current) : 'Could not submit the request.', variant: 'error' });
                                    }).finally(() => { submitting = false; });
                                }
                            "
                        >
                            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                            <span x-text="submitting ? ($store.lang.current === 'en' ? 'Submitting…' : 'Isinusumite…') : ($store.lang.current === 'en' ? (step < 4 ? 'Continue' : 'Submit request') : (step < 4 ? 'Magpatuloy' : 'Isumite ang kahilingan'))"></span>
                        </x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
                </div>
            </div>
            </div>
        </div>

        {{-- On a phone each request is a card with a full-width Subaybayan
             button; the table cut that column to one letter (live test
             2026-10-07, BUG-4). --}}
        <div class="sm:hidden space-y-2.5">
            <template x-for="req in filteredRequests" :key="'card-' + req.ref">
                <div class="surface-glass rounded-[26px] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-[11px] text-ink-muted" x-text="req.ref"></p>
                            <p class="text-sm font-medium text-ink-body mt-0.5 break-words" x-text="req.type"></p>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-show="req.submitted" x-text="($store.lang.current === 'en' ? 'Submitted ' : 'Isinumite ') + req.submitted"></p>
                        </div>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0"
                            :class="chip(req.status)[0]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="chip(req.status)[1]"></span>
                            <span x-text="req.status"></span>
                        </span>
                    </div>
                    <button type="button" @click="openProgress(req)"
                        class="mt-3 w-full inline-flex items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white py-2 text-xs font-medium text-brand-700 hover:bg-white/60">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i><x-ui.t fil="Subaybayan" en="Track" />
                    </button>
                </div>
            </template>
        </div>

        <x-ui.table class="hidden sm:block">
            <thead>
                <tr class="bg-well/70">
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Reference" en="Reference" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Uri ng Tulong" en="Assistance Type" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden lg:table-cell"><x-ui.t fil="Opisina" en="Office" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden md:table-cell"><x-ui.t fil="Layunin" en="Purpose" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell"><x-ui.t fil="Isinumite" en="Submitted" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Status" en="Status" /></th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="req in filteredRequests" :key="req.ref">
                    <tr class="hover:bg-white/60 transition-colors cursor-pointer" @click="openProgress(req)"
        tabindex="0"
        @keydown.enter.prevent="openProgress(req)"
        @keydown.space.prevent="openProgress(req)">
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted" x-text="req.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="req.type"></td>
                        <td class="px-4 py-3 text-ink-muted hidden lg:table-cell" x-text="req.office"></td>
                        <td class="px-4 py-3 text-ink-muted hidden md:table-cell" x-text="req.purpose"></td>
                        <td class="px-4 py-3 text-ink-muted hidden sm:table-cell" x-text="req.submitted"></td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                :class="chip(req.status)[0]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="chip(req.status)[1]"
                                ></span>
                                <span x-text="req.status"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop="openProgress(req)">
                            <span class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"><x-ui.t fil="Subaybayan" en="Track" /></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div x-show="requestsLoading" class="space-y-2 mt-3"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
        <div x-show="requestsFailed" x-cloak class="mt-3"><x-ui.error-state messageExpr="$store.lang.current === 'en' ? 'Could not load your requests.' : 'Hindi ma-load ang iyong mga kahilingan.'" retryExpr="loadRequests()" :retryable="true" /></div>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3" x-show="!requestsLoading && !requestsFailed && filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang kahilingang tumutugma dito." en="No requests in this view." /></p>
        </div>

        {{-- What the office recorded for them before they had an account (2026-10-13). --}}
        <x-citizen.records type="tulong" class="mt-6" />

        {{-- Session certificates (welfare round, 2026-10-29). The services-received
             card left in round 2 (2026-10-30): this list is what they received. --}}
        <x-citizen.welfare-received class="mt-6" />

        <!-- Track Request -->
        <div x-data="{ get open() { return progressOpen }, set open(v) { progressOpen = v } }">
            <x-ui.modal title="Subaybayan ang Kahilingan" titleEn="Track Request" maxWidth="md">
                <template x-if="selectedRequest">
                    <div>
                        <p class="text-xs font-mono text-ink-muted" x-text="selectedRequest.ref"></p>
                        <p class="text-sm font-semibold text-ink" x-text="selectedRequest.type"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="selectedRequest.purpose"></p>

                        <!-- A status this page does not know: said so, with what to do (2026-10-17). -->
                        <div x-show="isUnknownStatus(selectedRequest.status)" x-cloak role="status" class="mt-4 rounded-xl border border-dashed border-amber-400 bg-amber-50 px-3 py-2 text-xs text-amber-900" data-testid="unknown-status">
                            <p><span class="font-semibold" x-text="lang('Status: ', 'Katayuan: ') + selectedRequest.status"></span> <span x-text="lang('This page does not recognise this status yet, so it cannot show the step. Your request is not lost: reload the page, or ask the office handling it.', 'Hindi pa kilala ng page na ito ang katayuang ito, kaya hindi maipakita ang hakbang. Hindi nawala ang iyong kahilingan: i-reload ang page, o magtanong sa opisinang humahawak nito.')"></span></p>
                        </div>
                        <!-- Progress stepper -->
                        <div class="flex items-center mt-6 mb-2" x-show="selectedRequest.status !== 'Rejected' && selectedRequest.status !== 'Cancelled' && !isUnknownStatus(selectedRequest.status)">
                            <template x-for="(label, i) in stepOrder" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                                            :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-500 text-white' : (stepIndex(selectedRequest.status) === i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-ink-muted')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="stepIndex(selectedRequest.status) > i" x-cloak></i>
                                            <span x-show="stepIndex(selectedRequest.status) <= i" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-xs max-w-[76px] leading-tight" :class="stepIndex(selectedRequest.status) === i ? 'text-ink font-medium' : 'text-ink-muted'" x-text="label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 -mt-4 transition-colors duration-[300ms] ease-standard" :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < stepOrder.length - 1"></div>
                                </div>
                            </template>
                        </div>

                        {{-- Rejected is terminal — informational only, no
                             resubmit path out of it. --}}
                        <div class="rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Rejected'">
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="circle-x" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-rose-700"><x-ui.t fil="Tinanggihan ang kahilingang ito" en="This request was rejected" /></p>
                                    <p class="text-xs text-rose-600 mt-0.5" x-show="selectedRequest.remarks" x-text="selectedRequest.remarks"></p>
                                    <p class="text-xs text-rose-600 mt-0.5" x-show="!selectedRequest.remarks"><x-ui.t fil="Bisitahin ang opisina para sa detalye." en="Visit the office below for details." /></p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-100 border border-slate-200 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Cancelled'">
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="ban" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-600"><x-ui.t fil="Kinansela ang kahilingang ito" en="This request was cancelled" /></p>
                                    <p class="text-xs text-slate-600 mt-0.5" x-show="selectedRequest.remarks" x-text="selectedRequest.remarks"></p>
                                </div>
                            </div>
                        </div>

                        {{-- At every stage, so the resident sees which documents the office accepted
                             (Esperanza bugs QA, 2026-10-06); Attach shows only where a file can still go. --}}
                        <div class="rounded-xl border border-slate-200 mt-5 p-3.5" x-show="(selectedRequest.requirements || []).length" x-cloak>
                            <p class="text-xs font-semibold text-ink mb-2"><x-ui.t fil="Aking mga Dokumento para dito" en="My documents for this request" /></p>
                            <div class="space-y-2">
                                <template x-for="req in (selectedRequest.requirements || [])" :key="req.key">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-600/10 p-2.5">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-slate-700 truncate"><span x-text="req.name"></span> <span x-show="req.status !== 'Approved'" class="ml-1 inline-block rounded px-1.5 py-px text-[10px] font-semibold align-middle" :class="reqKindClass(req.name)" x-text="reqKindLabel(req.name)"></span></p>
                                            <p class="text-[11px] truncate" :class="req.file ? 'text-ink-muted' : 'text-amber-600'" x-text="req.file || (reqKind(req.name) === 'office' ? ($store.lang.current === 'en' ? 'Nothing to upload: the office does this with you' : 'Walang ia-upload: gagawin ito ng opisina kasama ka') : ($store.lang.current === 'en' ? 'No file attached yet' : 'Wala pang nakakabit na file'))"></p>
                                            {{-- A rejected document says why, and takes a replacement below. --}}
                                            <p x-show="req.status === 'Rejected' && (req.reject_reason || req.remarks)" class="text-[11px] text-rose-600 whitespace-normal" x-text="($store.lang.current === 'en' ? 'Reason: ' : 'Dahilan: ') + [req.reject_reason, req.remarks].filter((v, i, a) => v && a.indexOf(v) === i).join(' — ')"></p>
                                        </div>
                                        <label x-show="req.attachable && reqKind(req.name) !== 'office'" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                            <span x-text="req._busy ? '…' : (req.file ? ($store.lang.current === 'en' ? 'Change' : 'Palitan') : ($store.lang.current === 'en' ? 'Attach' : 'Ikabit'))"></span>
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <label x-show="req.replaceable && req.status === 'Rejected'" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                            <span x-text="req._busy ? '…' : ($store.lang.current === 'en' ? 'Upload replacement' : 'Mag-upload ng kapalit')"></span>
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="replaceRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <span x-show="!req.attachable && !(req.replaceable && req.status === 'Rejected')" class="shrink-0 text-[11px]" :class="req.status === 'Approved' ? 'text-emerald-600 font-semibold' : (req.status === 'Rejected' ? 'text-rose-600 font-semibold' : 'text-ink-muted')" x-text="req.status === 'Approved' ? ($store.lang.current === 'en' ? 'Accepted' : 'Tinanggap') : (req.status === 'Rejected' ? ($store.lang.current === 'en' ? 'Not accepted' : 'Hindi tinanggap') : req.status)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Once released: the photo or scan the office took at release, shown
                             here in the page's own viewer (Esperanza QA, 2026-10-07). --}}
                        <div class="rounded-xl border border-slate-200 mt-5 p-3.5" x-show="selectedRequest.releaseProof" x-cloak>
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink"><x-ui.t fil="Patunay ng Pag-release" en="Proof of release" /></p>
                                    <p class="text-[11px] text-ink-muted truncate" x-text="selectedRequest.releaseProof?.name"></p>
                                </div>
                                <button type="button" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline" @click="proofOpen = !proofOpen" x-text="proofOpen ? lang('Hide', 'Itago') : lang('View', 'Tingnan')"></button>
                            </div>
                            <div x-show="proofOpen" x-cloak x-data="pdfPreview" x-effect="show(proofOpen && selectedRequest.releaseProof ? selectedRequest.releaseProof.url : null, (selectedRequest.releaseProof?.mime || '') + ' ' + (selectedRequest.releaseProof?.name || ''))" class="mt-3 max-h-[60vh] overflow-y-auto rounded-xl border border-slate-200 bg-slate-100 p-2">
                                <p x-show="loading" class="text-xs text-ink-muted text-center py-8"><x-ui.t fil="Binubuksan…" en="Opening…" /></p>
                                <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8"><x-ui.t fil="Hindi maipakita ang file. Isara at buksan muli ang kahilingan." en="The file could not be shown. Close and reopen the request." /></p>
                                <img x-show="image" x-cloak :src="image" alt="Proof of release" class="block w-full h-auto rounded-lg bg-white">
                                <div x-ref="pages"></div>
                            </div>
                        </div>

                        {{-- Tulong's correction state. Sending the corrections
                             returns the case to Initial Screening (there is no
                             separate Resubmitted status in Tulong). --}}
                        <div class="rounded-xl bg-amber-50 border border-amber-100 mt-5" x-show="selectedRequest.status === 'Needs Requirements' && (selectedRequest.requirements || []).length">
                            <div class="flex items-start gap-2.5 px-3.5 py-2.5">
                                <i data-lucide="triangle-alert" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-amber-700"><x-ui.t fil="Kailangan ng Ayos ang Kahilingan" en="Application Needs Correction" /></p>
                                    <p class="text-xs text-amber-600 mt-0.5"><x-ui.t fil="May mga requirement sa ibaba na kailangan ng atensyon." en="One or more requirements below need your attention." /></p>
                                </div>
                            </div>
                            <div class="px-3.5 pb-3.5 space-y-2">
                                <template x-for="req in (selectedRequest.requirements || [])" :key="req.key">
                                    <div class="rounded-xl border bg-white p-3" :class="{ 'border-emerald-200': req.status === 'Approved', 'border-rose-200': req.status === 'Rejected', 'border-amber-200': req.status === 'Flagged for Replacement', 'border-sky-200': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' || req.status === 'Not submitted' }">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <i data-lucide="file-text" class="w-4 h-4 text-ink-muted shrink-0"></i>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <p class="text-[11px] text-ink-muted truncate" x-text="req.file || (reqKind(req.name) === 'office' ? ($store.lang.current === 'en' ? 'Done at the office' : 'Gagawin sa opisina') : ($store.lang.current === 'en' ? 'Not yet submitted' : 'Wala pang naisumite'))"></p>
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset shrink-0"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted', 'bg-slate-100 text-slate-600 ring-slate-200': req.status === 'Not submitted' }"
                                            ><span x-text="req.status === 'Approved' ? ($store.lang.current === 'en' ? 'Accepted' : 'Tinanggap') : (req.status === 'Not submitted' ? ($store.lang.current === 'en' ? 'Not submitted' : 'Wala pa') : req.status)"></span></span>
                                        </div>

                                        <div x-show="req.status === 'Flagged for Replacement'" class="mt-2 pt-2 border-t border-amber-100 text-[11px]">
                                            <p class="font-semibold text-amber-700" x-text="req.reject_reason"></p>
                                            <p class="text-amber-600 mt-0.5" x-show="req.remarks" x-text="req.remarks"></p>
                                            <label class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                                <i data-lucide="upload" class="w-3 h-3"></i><span x-text="req._busy ? ($store.lang.current === 'en' ? 'Uploading…' : 'Ina-upload…') : ($store.lang.current === 'en' ? 'Replace Document' : 'Palitan ang Dokumento')"></span>
                                                <input type="file" class="sr-only" accept="image/*,application/pdf" @change="replaceRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                            </label>
                                        </div>
                                    </div>
                                </template>
                                <x-ui.button
                                    class="w-full justify-center" icon="rotate-ccw"
                                    x-bind:disabled="resubmitting || !canResubmitRequirements"
                                    @click="resubmitRequirements()"
                                >
                                    <span x-text="resubmitting ? ($store.lang.current === 'en' ? 'Resubmitting…' : 'Isinusumite muli…') : ($store.lang.current === 'en' ? 'Resubmit application' : 'Isumite muli ang aplikasyon')"></span>
                                </x-ui.button>
                                <p x-show="(selectedRequest?.missingRequired || []).length" x-cloak class="text-[11px] text-amber-700" x-text="($store.lang.current === 'en' ? 'Attach these first, then resubmit: ' : 'I-upload muna ang mga ito bago muling isumite: ') + (selectedRequest?.missingRequired || []).map(m => m.label).join(', ')"></p>
                            </div>
                        </div>

                        {{-- Internal re-verification: nothing for the resident
                             to do, so a plain note, never a correction panel. --}}
                        <div class="rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Returned for Reassessment'" x-cloak>
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="hourglass" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
                                <p class="text-xs text-slate-600"><x-ui.t fil="Muling sinusuri ng opisina ang iyong kaso. Wala kang kailangang gawin." en="The office is re-checking your case. Nothing is needed from you." /></p>
                            </div>
                        </div>

                        {{-- Approved: what was approved, and where to claim it
                             once it is ready. --}}
                        <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-3.5 py-2.5 mt-5" x-show="['Assistance Processing', 'Ready for Release'].includes(selectedRequest.status)" x-cloak>
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="badge-check" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-emerald-800" x-show="selectedRequest.status === 'Assistance Processing'"><x-ui.t fil="Naaprubahan — inihahanda na ang iyong tulong" en="Approved — your assistance is being prepared" /></p>
                                    <p class="text-xs font-semibold text-emerald-800" x-show="selectedRequest.status === 'Ready for Release'"><x-ui.t fil="Handa nang kunin ang iyong tulong" en="Your assistance is ready to claim" /></p>
                                    <p class="text-xs text-emerald-700 mt-0.5" x-show="selectedRequest.assistance?.approved_amount">
                                        <x-ui.t fil="Inaprubahang halaga:" en="Approved amount:" /> <span class="font-semibold tabular-nums" x-text="peso(selectedRequest.assistance?.approved_amount)"></span>
                                    </p>
                                    <p class="text-xs text-emerald-700 mt-0.5" x-show="selectedRequest.status === 'Ready for Release'"><x-ui.t fil="Magdala ng valid ID sa opisinang nakalista sa ibaba." en="Bring a valid ID to the office listed below." /></p>
                                </div>
                            </div>
                        </div>

                        {{-- Completed: what was received. --}}
                        <div class="rounded-xl bg-green-50 border border-green-100 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Completed'" x-cloak>
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="circle-check" class="w-4 h-4 text-green-600 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-green-800" x-show="!['session', 'counseling', 'referral'].includes(kindOf(selectedRequest))"><x-ui.t fil="Natanggap na ang tulong" en="Assistance received" /></p>
                                    <p class="text-xs font-semibold text-green-800" x-show="['session', 'counseling', 'referral'].includes(kindOf(selectedRequest))"><x-ui.t fil="Natapos na ang serbisyo" en="The service is complete" /></p>
                                    <p class="text-xs text-green-700 mt-0.5" x-show="selectedRequest.assistance?.released_amount">
                                        <x-ui.t fil="Halaga:" en="Amount:" /> <span class="font-semibold tabular-nums" x-text="peso(selectedRequest.assistance?.released_amount)"></span>
                                    </p>
                                    <p class="text-xs text-green-700 mt-0.5" x-show="selectedRequest.assistance?.release_reference">
                                        <x-ui.t fil="Reference:" en="Reference:" /> <span class="font-mono" x-text="selectedRequest.assistance?.release_reference"></span>
                                    </p>
                                    <p class="text-xs text-green-700 mt-0.5" x-show="selectedRequest.assistance?.released_at" x-text="formatDay(selectedRequest.assistance?.released_at)"></p>
                                </div>
                            </div>
                        </div>

                        {{-- A group session's case (welfare round, 2026-10-29): when and where,
                             from the case's own session, or that it waits for one. --}}
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 px-3.5 py-3 mt-5" x-show="kindOf(selectedRequest) === 'session' && !['Rejected', 'Cancelled'].includes(selectedRequest.status)" x-cloak data-testid="tulong-session">
                            <p class="text-xs font-semibold text-indigo-900 flex items-center gap-1.5"><i data-lucide="calendar-days" class="w-3.5 h-3.5"></i><x-ui.t fil="Iskedyul ng sesyon" en="Session schedule" /></p>
                            <template x-if="selectedRequest.session">
                                <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs">
                                    <dt class="text-ink-muted"><x-ui.t fil="Petsa" en="Date" /></dt><dd class="font-semibold text-ink" x-text="formatDay(selectedRequest.session.date)"></dd>
                                    <dt class="text-ink-muted"><x-ui.t fil="Oras" en="Time" /></dt><dd class="font-semibold text-ink" x-text="clock(selectedRequest.session.start_time) || '—'"></dd>
                                    <dt class="text-ink-muted"><x-ui.t fil="Lugar" en="Venue" /></dt><dd class="font-semibold text-ink" x-text="selectedRequest.session.venue || '—'"></dd>
                                    <dt class="text-ink-muted"><x-ui.t fil="Katayuan" en="Status" /></dt><dd class="font-semibold text-ink" x-text="sessionStatus(selectedRequest.session)"></dd>
                                </dl>
                            </template>
                            <p x-show="!selectedRequest.session && selectedRequest.status !== 'Completed'" class="text-xs text-indigo-800 mt-1"><x-ui.t fil="Naghihintay ng iskedyul. Aabisuhan ka kapag naka-iskedyul ka na sa isang sesyon." en="Waiting for a schedule. You will be notified once you are scheduled to a session." /></p>
                            <p x-show="isCouple(selectedRequest) && selectedRequest.status !== 'Completed'" class="text-[11px] text-indigo-800 mt-1.5"><x-ui.t fil="Kailangang dumalo kayong dalawa. Ang sertipiko ay ibibigay lamang kapag pareho kayong dumalo." en="Both of you must attend. The certificate is issued only when both of you attended." /></p>
                        </div>

                        {{-- Counseling: what is shared stays with the MSWDO. --}}
                        <p class="mt-5 flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600" x-show="kindOf(selectedRequest) === 'counseling'" x-cloak>
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-brand-600 shrink-0 mt-0.5"></i>
                            <x-ui.t fil="Ang ibinabahagi mo sa social worker ay mananatili sa MSWDO. Hindi ito ipinapakita sa ibang opisina." en="What you share with the social worker stays with the MSWDO. It is not shown to other offices." />
                        </p>

                        {{-- The certificate issued once the session was finished: opens printable. --}}
                        <div class="rounded-xl border border-amber-200 bg-amber-50/40 px-3.5 py-3 mt-5" x-show="certDocs(selectedRequest).length" x-cloak>
                            <p class="text-xs font-semibold text-amber-900 flex items-center gap-1.5"><i data-lucide="award" class="w-3.5 h-3.5"></i><x-ui.t fil="Ang iyong sertipiko" en="Your certificate" /></p>
                            <ul class="mt-2 space-y-1.5">
                                <template x-for="d in certDocs(selectedRequest)" :key="d.control_number">
                                    <li class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-[11px] text-ink-muted"><span class="font-mono font-semibold text-ink" x-text="d.control_number"></span><span x-show="d.revoked" class="text-rose-700" x-text="' · ' + lang('Revoked', 'Binawi')"></span></span>
                                        <x-ui.button size="sm" variant="secondary" icon="award" @click="openCert(selectedRequest, d)"><x-ui.t fil="Tingnan ang sertipiko" en="View certificate" /></x-ui.button>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <x-citizen.request-fee />

                        {{-- Slip, approval notice, appointment, issued documents and the survey (scope round 2026-10-15). --}}
                        <x-citizen.scope.request-extras kind="tulong" />

                        {{-- Withdraw: until the office recommends it (end-to-end test 2026-10-07). --}}
                        <div class="mt-5" x-show="selectedRequest.canWithdraw" x-cloak>
                            <button type="button" x-show="!withdrawOpen" @click="withdrawOpen = true; $nextTick(() => window.renderIcons?.())" class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-600 hover:underline">
                                <i data-lucide="undo-2" class="w-3.5 h-3.5"></i><x-ui.t fil="Bawiin ang kahilingang ito" en="Withdraw this request" />
                            </button>
                            <div x-show="withdrawOpen" class="rounded-xl border border-rose-200 bg-rose-50 p-3.5">
                                <p class="text-xs font-semibold text-rose-700" x-text="lang('Withdraw ' + selectedRequest.ref + '?', 'Bawiin ang ' + selectedRequest.ref + '?')"></p>
                                <p class="text-xs text-rose-600 mt-0.5"><x-ui.t fil="Ititigil ng opisina ang pagproseso nito at hindi na ito mabubuksan muli. Para humiling ulit, magsumite ng bago." en="The office stops working on it and it cannot be reopened. To ask again, file a new request." /></p>
                                <label for="withdraw-reason" class="block text-[11px] font-medium text-slate-600 mt-2.5 mb-1"><x-ui.t fil="Dahilan (hindi kailangan)" en="Reason (optional)" /></label>
                                <textarea id="withdraw-reason" x-model="withdrawReason" rows="2" maxlength="500" class="w-full text-xs rounded-xl px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-brand-100"></textarea>
                                <div class="flex flex-wrap justify-end gap-2 mt-2">
                                    <x-ui.button variant="secondary" size="sm" @click="withdrawOpen = false"><x-ui.t fil="Huwag na" en="Keep it" /></x-ui.button>
                                    <x-ui.button variant="danger" size="sm" icon="undo-2" loading="withdrawing" @click="withdrawRequest()"><x-ui.t fil="Oo, bawiin" en="Yes, withdraw" /></x-ui.button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-5">
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1"><x-ui.t fil="Inaasahang Petsa ng Release" en="Expected Release" /></p>
                                <p class="text-sm font-semibold text-ink" x-text="expectedRelease"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1"><x-ui.t fil="Isinumite" en="Submitted" /></p>
                                <p class="text-sm font-semibold text-ink" x-text="selectedRequest.submitted"></p>
                            </div>
                        </div>

                        {{-- The page's own directory, only when the server sent no pickup
                             details (the Ready to claim card above shows those). --}}
                        <div class="rounded-xl border border-slate-200 p-3.5 mt-3" x-show="!selectedRequest.pickup">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-2"><x-ui.t fil="Saan Kukunin / Sino ang Tatawagan" en="Where to Claim / Who to Contact" /></p>
                            <p class="text-sm font-medium text-ink" x-text="selectedRequest.office"></p>
                            <p class="text-xs text-ink-muted mt-1.5 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="pickupInfo?.address"></span></p>
                            <p class="text-xs text-ink-muted mt-1 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="phone" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="pickupInfo?.contact"></span></p>
                        </div>

                        <x-citizen.scope.request-timeline />
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button size="sm" @click="progressOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- The printable certificate, above Track Request (welfare round, 2026-10-29). --}}
        <x-welfare.certificate :bilingual="true" />
    </div>
</x-layouts.citizen>
