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
    $assistanceTypes = config('esperanza_tulong_types.types');

    // Attach expected-processing-time onto each seeded request by matching its
    // type against $assistanceTypes, same mechanism as the Dokyu tracker.
    $daysByAssistType = collect($assistanceTypes)->pluck('days', 'name');
    $officeByAssistType = collect($assistanceTypes)->pluck('office', 'name');
    // By service key too: the API's service_key is exact, and the backend's
    // display name differs from this catalog's for TUPAD and ERPAT.
    $daysByAssistKey = collect($assistanceTypes)->filter(fn ($t) => ! empty($t['key']))->pluck('days', 'key');
    // The pickup directory is keyed by an office's SHORT name, which the API
    // does not send; the catalog has it per service key.
    $officeShortByAssistKey = collect($assistanceTypes)->filter(fn ($t) => ! empty($t['key']))->pluck('office_short', 'key');

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
            papeles: [],
            // Tulong runs its own case lifecycle on the server (the eleven
            // statuses of config/esperanza_tulong_lifecycle.php); these are
            // its terminal ones.
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
                'Cancelled': ['bg-slate-100 text-slate-500 ring-slate-200', 'bg-slate-400'],
            },
            get filteredRequests() {
                return this.requests.filter(r => {
                    if (this.tab === 'all') return true;
                    const isDone = this.doneStatuses.includes(r.status);
                    return this.tab === 'done' ? isDone : !isDone;
                });
            },
            pickupDirectory: @js($pickupDirectory),
            progressOpen: false,
            selectedRequest: null,
            resubmitNote: '',
            resubmitting: false,
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
                r.payment = d.payment || null;
                // What was approved and what was received (Tulong only).
                r.assistance = d.assistance || null;
                r.requirements = (d.requirements || []).map((q) => ({
                    key: q.key,
                    name: q.label,
                    status: q.status === 'Pending Review' ? (q.file ? 'Submitted' : 'Not submitted') : q.status,
                    reject_reason: q.reason,
                    remarks: q.remarks,
                    file: q.file?.name || null,
                    attachable: !!q.attachable,
                    _busy: false,
                }));
            },
            async openProgress(r) {
                this.selectedRequest = r;
                this.progressOpen = true;
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
            // Tulong's main line, as config/esperanza_tulong_lifecycle.php
            // declares it (tests/Feature/TulongLifecycleTest.php pins the two).
            // A side state sits on the step it branches from.
            stepOrder: ['Submitted', 'Initial Screening', 'Assessment', 'For Approval', 'Assistance Processing', 'Ready for Release', 'Completed'],
            stepIndex(status) {
                const side = { 'Needs Requirements': 'Initial Screening', 'Returned for Reassessment': 'For Approval' };
                return Math.max(0, this.stepOrder.indexOf(side[status] || status));
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
                const addDays = match ? parseInt(match[1]) : 5;
                const d = new Date(r.submitted);
                d.setDate(d.getDate() + addDays);
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

        <x-ui.page-intro id="tulong" icon="hand-heart" title="Paano gumagana ang Tulong" titleEn="How Tulong works" color="purple">
            <x-ui.t
                fil="Piliin ang programang tulong o ayuda na kailangan mo, sabihin sa amin ang iyong sitwasyon, ikabit ang mga requirement, pagkatapos i-submit. Halos lahat ng programa rito ay libreng i-apply — nagsisimula sa TUL- ang reference number."
                en="Choose the assistance or ayuda program you need, tell us about your situation, attach your requirements, then submit. Almost every program here is free to apply for — reference numbers start with TUL-."
            />
        </x-ui.page-intro>

        <x-citizen.approval-notice service="Tulong" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            {{-- Counted from the resident's own requests. A fifth tile showed
                 "Ayuda Received ₱42,500" to everyone; no amount received is
                 recorded anywhere, so it is gone. --}}
            <x-ui.stat-card label="Kabuuang Kahilingan" labelEn="Total Requests" value="0" valueBind="requests.length" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card label="Isinasagawa" labelEn="Processing" value="0" valueBind="requests.filter(r => !doneStatuses.includes(r.status) && r.status !== 'Ready for Release').length" icon="loader" color="purple" :delay="40" />
            <x-ui.stat-card label="Handa nang Ilabas" labelEn="Ready for Release" value="0" valueBind="requests.filter(r => r.status === 'Ready for Release').length" icon="package-check" color="brand" :delay="80" />
            <x-ui.stat-card label="Kumpleto" labelEn="Completed" value="0" valueBind="requests.filter(r => r.status === 'Completed').length" icon="badge-check" color="gold" :delay="120" />
        </div>

        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
            <x-citizen.status-guide type="tulong" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card px-3 py-2.5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto shrink-0">
                        @foreach(['all' => ['Lahat', 'All'], 'active' => ['Isinasagawa', 'In Progress'], 'done' => ['Kumpleto', 'Completed']] as $key => [$labelFil, $labelEn])
                            <button
                                @click="tab = '{{ $key }}'"
                                class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                                :class="tab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            ><x-ui.t :fil="$labelFil" :en="$labelEn" /></button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-slate-500 shrink-0" x-text="filteredRequests.length + ' ' + ($store.lang.current === 'en' ? 'of ' + requests.length + ' requests' : 'sa ' + requests.length + ' kahilingan')"></p>
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
                    },
                    formData: {},
                    get filteredTypes() { return this.types.map((t, i) => ({ ...t, i })).filter(t => t.name.toLowerCase().includes(this.search.toLowerCase())); },
                    get assistanceCategories() {
                        const counts = {};
                        this.types.forEach(t => { counts[t.category] = (counts[t.category] || 0) + 1; });
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
                        this.stepError = '';
                        this.stepWarning = '';
                        this.attachWarned = false;
                    },
                    // Same checks as the Dokyu wizard: required fields block,
                    // missing attachments are said once.
                    stepError: '',
                    stepWarning: '',
                    attachWarned: false,
                    missingRequired() {
                        return (this.types[this.selectedType]?.fields || []).filter((f) => {
                            if (!f.required) return false;
                            const v = this.formData[f.key];
                            return Array.isArray(v) ? v.length === 0 : String(v ?? '').trim() === '';
                        }).map((f) => f.label);
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
                            const total = (this.types[this.selectedType]?.requirements || []).length;
                            const attached = Object.keys(this.attachments).filter((k) => this.attachments[k]).length;
                            if (attached < total && !this.attachWarned) {
                                this.attachWarned = true;
                                this.stepWarning = en
                                    ? (total - attached) + ' of ' + total + ' requirements not attached. The office needs them to assess the request: attach them now, or press Continue to add them later from Track Request.'
                                    : (total - attached) + ' sa ' + total + ' requirement ang hindi pa naikakabit. Kailangan ito ng opisina: ikabit na ngayon, o pindutin ang Magpatuloy para idagdag mamaya sa Subaybayan.';
                                return;
                            }
                        }
                        this.step++;
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
                    reset() { this.step = 1; this.selectedType = null; this.attachments = {}; this.showFilePicker = null; this.submitted = false; this.submittedRef = null; this.search = ''; this.formData = {}; this.selectedCategory = null; this.stepError = ''; this.stepWarning = ''; this.attachWarned = false; },
                    // POST /citizen/requests, then each attachment onto its
                    // requirement. The server assigns the ref and notifies the
                    // office; a failed attachment can be added from Track Request.
                    async submitRequest() {
                        const type = this.types[this.selectedType];
                        const formData = JSON.parse(JSON.stringify(this.formData || {}));
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
                        const failed = [];
                        for (const [i, att] of Object.entries(this.attachments)) {
                            if (!att) continue;
                            const label = names[i];
                            const target = serverReqs.find((q) => q.label === label) || serverReqs[Number(i)];
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
                x-init="seedApplicant(); $watch('$store.citizenSession.account', () => seedApplicant())"
                x-effect="!open && reset()"
                class="contents"
            >
                <div class="flex items-center gap-2 shrink-0">
                <x-ui.button @click="open = true" ::disabled="$store.citizenSession.account && !$store.citizenSession.account.fullServiceAccess" icon="hand-heart" size="sm" class="shrink-0"><x-ui.t fil="Bagong Kahilingan sa Tulong" en="New Tulong Request" /></x-ui.button>
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
                        <p class="text-sm font-semibold text-navy-900">
                            <x-ui.t fil="Naisumite ang Kahilingan!" en="Request Submitted!" />
                            <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 max-w-[220px]"><x-ui.t fil="Aabisuhan ka namin kapag na-review na ang iyong kahilingan sa Tulong." en="We'll notify you once your Tulong request is reviewed." /></p>
                    </div>

                    <div x-show="!submitted">
                    <!-- Step indicator -->
                    <div class="flex items-center mb-8 px-1">
                        <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                            <div class="flex-1 flex items-center">
                                <div class="flex flex-col items-center gap-2 text-center">
                                    <span
                                        class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-[300ms] ease-standard"
                                        :class="step > i + 1 ? 'bg-emerald-500 text-white shadow-sm' : (step === i + 1 ? 'bg-purple-600 text-white shadow-card ring-4 ring-purple-100' : 'bg-slate-100 text-slate-500')"
                                    >
                                        <i data-lucide="check" class="w-4 h-4" x-show="step > i + 1" x-cloak></i>
                                        <i :data-lucide="stepIcons[i]" class="w-4 h-4" x-show="step <= i + 1"></i>
                                    </span>
                                    <span class="text-[11px] max-w-[100px] leading-tight transition-colors duration-[300ms] ease-standard" :class="step === i + 1 ? 'text-navy-900 font-semibold' : 'text-slate-500'" x-text="label"></span>
                                </div>
                                <div class="flex-1 h-1 rounded-full mx-1.5 -mt-6 transition-colors duration-[300ms] ease-standard" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < 3"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Step 1: choose assistance type -->
                    <div x-show="step === 1">
                        <p class="text-sm text-slate-500 mb-3"><x-ui.t fil="Piliin ang uri ng tulong na kailangan mo." en="Choose the type of assistance you need." /></p>
                        <div class="relative mb-4">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-300 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input aria-label="Search"
                                type="text" x-model="search"
                                x-bind:placeholder="$store.lang.current === 'en' ? 'Search assistance programs...' : 'Maghanap ng programang tulong...'"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:bg-white focus:border-purple-400 focus:ring-4 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard"
                            >
                        </div>
                        <!-- Category grid (shown first, or when searching) -->
                        <template x-if="!selectedCategory || search">
                            <div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" x-show="!search">
                                    <template x-for="cat in assistanceCategories" :key="cat.name">
                                        <button
                                            type="button" @click="chooseCategory(cat.name)"
                                            class="flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left hover:border-purple-300 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                                        >
                                            <span class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                                <i :data-lucide="cat.icon" class="w-5 h-5"></i>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-medium text-slate-700 truncate" x-text="cat.name"></span>
                                                <span class="block text-xs text-slate-500 mt-0.5" x-text="cat.count + (cat.count === 1 ? ' program' : ' programs')"></span>
                                            </span>
                                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
                                        </button>
                                    </template>
                                </div>

                                <!-- Search results (flat, across categories) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" x-show="search">
                                    <template x-for="type in filteredTypes" :key="type.i">
                                        <label
                                            class="relative flex flex-col rounded-xl border p-3.5 cursor-pointer transition-all duration-[220ms] ease-standard"
                                            :class="selectedType === type.i ? 'border-purple-400 bg-purple-50/60 ring-4 ring-purple-100 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5'"
                                        >
                                            <input type="radio" name="assist_type" class="sr-only" :checked="selectedType === type.i" @change="selectType(type.i)">
                                            <div class="flex items-center justify-between mb-2.5">
                                                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-[160ms] ease-standard" :class="selectedType === type.i ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                    <i :data-lucide="type.icon" class="w-4 h-4"></i>
                                                </span>
                                                <i data-lucide="circle-check" class="w-4 h-4 text-purple-500" x-show="selectedType === type.i" x-cloak></i>
                                            </div>
                                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="type.name"></p>
                                            <p class="text-xs text-slate-500 mt-1 truncate" x-text="type.category + ' · ' + type.office_short"></p>
                                            <span
                                                class="inline-block w-fit mt-2.5 text-[11px] font-semibold rounded-full px-2.5 py-1"
                                                :class="type.fee === 'Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-navy-900'"
                                                x-text="type.amount"
                                            ></span>
                                        </label>
                                    </template>
                                    <div class="sm:col-span-2 flex flex-col items-center text-center py-8" x-show="filteredTypes.length === 0">
                                        <span class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mb-2"><i data-lucide="search-x" class="w-4.5 h-4.5"></i></span>
                                        <p class="text-xs text-slate-500"><x-ui.t fil="Walang programang tumutugma." en="No assistance programs match your search." /></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Programs within the chosen category -->
                        <template x-if="selectedCategory && !search">
                            <div>
                                <button type="button" @click="backToCategories()" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-purple-600 mb-3 transition-colors">
                                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                    <x-ui.t fil="Bumalik sa mga kategorya" en="Back to categories" />
                                </button>
                                <p class="text-sm font-semibold text-navy-900 mb-3" x-text="selectedCategory"></p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="type in typesInCategory" :key="type.i">
                                        <label
                                            class="relative flex flex-col rounded-xl border p-3.5 cursor-pointer transition-all duration-[220ms] ease-standard"
                                            :class="selectedType === type.i ? 'border-purple-400 bg-purple-50/60 ring-4 ring-purple-100 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5'"
                                        >
                                            <input type="radio" name="assist_type" class="sr-only" :checked="selectedType === type.i" @change="selectType(type.i)">
                                            <div class="flex items-center justify-between mb-2.5">
                                                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-[160ms] ease-standard" :class="selectedType === type.i ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                    <i :data-lucide="type.icon" class="w-4 h-4"></i>
                                                </span>
                                                <i data-lucide="circle-check" class="w-4 h-4 text-purple-500" x-show="selectedType === type.i" x-cloak></i>
                                            </div>
                                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="type.name"></p>
                                            <p class="text-xs text-slate-500 mt-1 truncate" x-text="type.office_short"></p>
                                            <span
                                                class="inline-block w-fit mt-2.5 text-[11px] font-semibold rounded-full px-2.5 py-1"
                                                :class="type.fee === 'Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-navy-900'"
                                                x-text="type.amount"
                                            ></span>
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
                                <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                                <p class="text-xs text-slate-500 mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <x-ui.input name="assist_first_name" label="First Name" x-model="applicant.firstName" />
                                    <x-ui.input name="assist_last_name" label="Last Name" x-model="applicant.lastName" />
                                </div>
                                <div class="mb-3">
                                    <x-ui.input name="assist_address" label="Complete Address" icon="map-pin" x-model="applicant.address" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-ui.input name="assist_mobile" label="Mobile Number" icon="phone" x-model="applicant.mobile" />
                                    <x-ui.input name="assist_email" type="email" label="Email Address" icon="mail" x-model="applicant.email" />
                                </div>
                            </div>

                            <!-- Live request summary -->
                            <div class="lg:col-span-2">
                                <div class="lg:sticky lg:top-0 rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50/60 to-white p-4">
                                    <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="receipt-text" class="w-3.5 h-3.5 text-purple-500"></i><x-ui.t fil="Buod ng Kahilingan" en="Request Summary" /></p>
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <span class="w-10 h-10 rounded-lg bg-purple-600 text-white flex items-center justify-center shrink-0"><i :data-lucide="types[selectedType]?.icon || 'hand-heart'" class="w-4.5 h-4.5"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-navy-900 leading-snug" x-text="types[selectedType]?.name"></p>
                                            <p class="text-xs text-slate-500 truncate" x-text="types[selectedType]?.office"></p>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 text-xs border-t border-purple-100/70 pt-3">
                                        <div class="flex items-center justify-between"><span class="text-slate-500"><x-ui.t fil="Tinatayang Halaga" en="Estimated Amount" /></span><span class="font-semibold text-navy-900 text-right" x-text="types[selectedType]?.amount"></span></div>
                                        <div class="flex items-center justify-between"><span class="text-slate-500"><x-ui.t fil="Oras ng Pagproseso" en="Processing Time" /></span><span class="font-medium text-slate-600" x-text="types[selectedType]?.days"></span></div>
                                        <div class="flex items-center justify-between"><span class="text-slate-500"><x-ui.t fil="Requirements" en="Requirements" /></span><span class="font-medium text-slate-600" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (types[selectedType]?.requirements || []).length + ' attached'"></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full-width: generic purpose OR structured fields (e.g. Solo Parent Application, FACED) — sits below the two-column grid so it isn't squeezed beside the sticky Request Summary -->
                        <!-- Generic purpose (assistance types without structured fields) -->
                        <div class="mb-6" x-show="!(types[selectedType]?.fields?.length)">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Detalye ng Kahilingan" en="Details of Request" /></label>
                            <textarea x-model="formData.purpose" aria-label="Briefly describe your situation and needs" rows="3" placeholder="Briefly describe your situation and needs" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-purple-400 focus:ring-4 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>

                        <div class="mb-6" x-show="types[selectedType]?.fields?.length" x-cloak>
                            <p class="text-sm font-semibold text-navy-900 mb-1" x-text="types[selectedType]?.certTitle || ''"></p>
                            <p class="text-xs text-slate-500 mb-3" x-show="types[selectedType]?.certSubtitle" x-text="types[selectedType]?.certSubtitle"></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type !== 'table')" :key="f.key">
                                    <div :class="f.type === 'checkbox-group' ? 'sm:col-span-2 lg:col-span-3' : ''">
                                        <template x-if="f.type === 'text' || f.type === 'number' || f.type === 'date'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <input :aria-label="f.label"
                                                    :type="f.type" x-model="formData[f.key]"
                                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-purple-400 focus:ring-4 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'select'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <select :aria-label="f.label" x-model="formData[f.key]" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-purple-400 focus:ring-4 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard">
                                                    <option value="">—</option>
                                                    <template x-for="opt in f.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                                                </select>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'checkbox-group'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-2" x-text="f.label"></label>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                    <template x-for="opt in f.options" :key="opt">
                                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="(formData[f.key] || []).includes(opt) && 'border-purple-300 bg-purple-50/60 text-purple-700'">
                                                            <input type="checkbox" class="rounded text-purple-600 focus:ring-purple-200" :checked="(formData[f.key] || []).includes(opt)" @change="toggleChecklist(f.key, opt)">
                                                            <span x-text="opt"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Full-width table fields (e.g. Family Composition, FACED Family Members) -->
                        <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type === 'table')" :key="f.key">
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div>
                                        <p class="text-sm font-semibold text-navy-900" x-text="f.label"></p>
                                        <p class="text-xs text-slate-500 mt-0.5"><x-ui.t fil="Idagdag ang bawat miyembro ng sambahayan." en="Add each member of the household." /></p>
                                    </div>
                                    <span class="text-[11px] text-slate-500 shrink-0" x-text="(formData[f.key] || []).length + ' ' + ($store.lang.current === 'en' ? 'added' : 'nadagdag')"></span>
                                </div>
                                <div class="space-y-2.5">
                                    <template x-for="(row, ri) in (formData[f.key] || [])" :key="ri">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-purple-700 bg-purple-100/70 rounded-full pl-1.5 pr-2.5 py-1">
                                                    <i :data-lucide="f.rowIcon || 'list'" class="w-3 h-3"></i>
                                                    <span x-text="row[f.columns[0].key] || ((f.rowLabel || 'Entry') + ' ' + (ri + 1))"></span>
                                                </span>
                                                <button type="button" @click="formData[f.key].splice(ri, 1)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                                <template x-for="col in f.columns" :key="col.key">
                                                    <div>
                                                        <label class="block text-[10px] font-medium text-slate-500 uppercase tracking-wide mb-1" x-text="col.label"></label>
                                                        <template x-if="col.options">
                                                            <select :aria-label="col.label" x-model="row[col.key]" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800 focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard">
                                                                <option value="">—</option>
                                                                <template x-for="opt in col.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!col.options">
                                                            <input :aria-label="col.label" :type="col.type === 'date' ? 'date' : (col.type === 'number' ? 'number' : 'text')" x-model="row[col.key]" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800 focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition-all duration-[160ms] ease-standard">
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="flex flex-col items-center text-center py-6 rounded-xl border-2 border-dashed border-slate-200" x-show="(formData[f.key] || []).length === 0">
                                        <span class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mb-2"><i :data-lucide="f.rowIcon || 'list'" class="w-4 h-4"></i></span>
                                        <p class="text-xs text-slate-500"><x-ui.t fil="Wala pang idinagdag na miyembro." en="No members added yet." /></p>
                                    </div>

                                    <button
                                        type="button" @click="addTableRow(f)"
                                        class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-purple-300 hover:bg-purple-50/40 text-xs font-medium text-slate-500 hover:text-purple-600 py-2.5 transition-all duration-[160ms] ease-standard"
                                    >
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span x-text="($store.lang.current === 'en' ? 'Add ' : 'Magdagdag ng ') + (f.rowLabel || 'Entry')"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                            <p class="text-[11px] font-medium text-slate-500" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (types[selectedType]?.requirements || []).length"></p>
                        </div>
                        <div class="h-1 rounded-full bg-slate-100 overflow-hidden mb-3">
                            <div class="h-full bg-emerald-400 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + (((types[selectedType]?.requirements || []).length ? (Object.keys(attachments).filter(k => attachments[k]).length / (types[selectedType]?.requirements || []).length) : 0) * 100) + '%'"></div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3"><x-ui.t fil="Mag-upload ng file (larawan o PDF), o gamitin ang isang dokumentong naka-imbak na sa iyong Papeles. Ipapadala ang mga ito kasama ng iyong kahilingan." en="Upload a file (photo or PDF), or reuse a document already saved in your Papeles. They are sent with your request." /></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <template x-for="(req, i) in types[selectedType]?.requirements || []" :key="i">
                                <div class="rounded-xl border p-3 transition-colors duration-[220ms] ease-standard" :class="attachments[i] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs text-slate-600 flex-1" x-text="req"></p>
                                        <button
                                            type="button" x-show="!attachments[i]"
                                            @click="showFilePicker = (showFilePicker === i ? null : i)"
                                            class="text-[11px] font-medium text-purple-600 hover:underline shrink-0 whitespace-nowrap"
                                        ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                        <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span class="truncate max-w-[140px]" x-text="attachments[i]?.name"></span>
                                            </span>
                                            <button type="button" @click="attachments[i] = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </div>
                                    </div>
                                    <div x-show="showFilePicker === i" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-2">
                                        <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-white bg-purple-600 rounded-lg px-2.5 py-1.5 hover:bg-purple-700 transition-colors cursor-pointer">
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-upload ng file" en="Upload a file" />
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachFile(i, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <div x-show="papeles.length" class="space-y-1">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><x-ui.t fil="O gamitin mula sa Papeles" en="Or reuse from Papeles" /></p>
                                            <template x-for="doc in papeles" :key="doc.id">
                                                <button type="button" @click="attachPapeles(i, doc)" class="w-full text-left text-[11px] text-slate-600 hover:text-purple-700 hover:bg-purple-50 rounded-lg px-2 py-1 truncate" x-text="doc.name"></button>
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
                                <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Walang Kailangang Bayaran" en="No Payment Required" /></p>
                                <p class="text-xs text-slate-500 mt-1 max-w-[280px]" x-text="types[selectedType]?.name + ($store.lang.current === 'en' ? ' has no application fee under municipal ordinance.' : ' ay walang bayarin sa pag-apply ayon sa municipal ordinance.')"></p>
                            </div>
                        </template>

                        <template x-if="types[selectedType]?.fee !== 'Free'">
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
                                <div class="lg:col-span-3 order-2 lg:order-1">
                                    <p class="text-sm font-semibold text-navy-900 mb-2"><x-ui.t fil="Paano magbayad" en="How to pay" /></p>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-2 text-xs text-slate-600 leading-relaxed">
                                        <p class="flex items-start gap-2"><i data-lucide="landmark" class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"></i><x-ui.t fil="Babayaran mo ang bayarin nang personal sa tanggapang nakalista, at bibigyan ka roon ng opisyal na resibo." en="You pay the fee in person at the office listed, which gives you an official receipt." /></p>
                                        <p class="flex items-start gap-2 text-amber-700"><i data-lucide="shield-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="Huwag magpadala ng bayad sa anumang e-wallet o account na hindi kinumpirma ng Munisipyo." en="Never send payment to an e-wallet or account the Municipality has not confirmed." /></p>
                                    </div>
                                </div>

                                <!-- Invoice -->
                                <div class="lg:col-span-2 order-1 lg:order-2">
                                    <div class="lg:sticky lg:top-0 rounded-xl border border-slate-200 overflow-hidden">
                                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-100">
                                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide"><x-ui.t fil="Buod ng Invoice" en="Invoice Summary" /></p>
                                        </div>
                                        <div class="px-4 py-3.5 text-sm">
                                            <p class="text-[10px] font-mono text-slate-500 mb-2">Municipality of Esperanza</p>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-slate-500"><x-ui.t fil="Sisingilin" en="Bill To" /></span>
                                                <span class="text-slate-700 font-medium" x-text="applicant.firstName + ' ' + applicant.lastName"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-slate-500"><x-ui.t fil="Item" en="Item" /></span>
                                                <span class="text-slate-700 font-medium text-right" x-text="$store.lang.current === 'en' ? 'Documentary / Notarization Fee' : 'Bayarin sa Dokumento / Notaryo'"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-slate-500"><x-ui.t fil="Kaugnay na Aplikasyon" en="Related Application" /></span>
                                                <span class="text-slate-700 font-medium text-right" x-text="types[selectedType]?.name"></span>
                                            </div>
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-slate-500"><x-ui.t fil="Kokolektang Opisina" en="Collecting Office" /></span>
                                                <span class="text-slate-700 font-medium" x-text="types[selectedType]?.office"></span>
                                            </div>
                                            <div class="border-t border-dashed border-slate-200 my-2"></div>
                                            <div class="flex items-center justify-between py-1">
                                                <span class="text-sm font-semibold text-navy-900 inline-flex items-center gap-1">
                                                    <x-ui.t fil="Kabuuang Babayaran" en="Total Due" />
                                                    <x-ui.tooltip text="This documentary fee is separate from your assistance amount — it won't be deducted from the aid you receive.">
                                                        <i data-lucide="info" class="w-3 h-3 text-slate-300 hover:text-slate-500 cursor-help"></i>
                                                    </x-ui.tooltip>
                                                </span>
                                                <span class="text-base font-bold text-purple-700" x-text="types[selectedType]?.fee"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Step 4: review & submit -->
                    <div x-show="step === 4" x-cloak>
                        <p class="text-sm font-semibold text-navy-900 mb-3"><x-ui.t fil="I-review ang Iyong Kahilingan" en="Review Your Request" /></p>
                        <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i :data-lucide="types[selectedType]?.icon || 'hand-heart'" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wide"><x-ui.t fil="Uri ng Tulong" en="Assistance Type" /></p>
                                        <p class="text-sm font-medium text-navy-900 truncate" x-text="types[selectedType]?.name || ''"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wide"><x-ui.t fil="Nangangasiwang Opisina" en="Handling Office" /></p>
                                        <p class="text-sm font-medium text-navy-900 truncate" x-text="types[selectedType]?.office || ''"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wide"><x-ui.t fil="Aplikante" en="Applicant" /></p>
                                        <p class="text-sm font-medium text-navy-900 truncate" x-text="applicant.firstName + ' ' + applicant.lastName"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-4 py-3">
                                    <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wide"><x-ui.t fil="Bayarin sa Dokumento" en="Documentary Fee" /></p>
                                        <p class="text-sm font-medium text-navy-900 truncate">
                                            <template x-if="types[selectedType]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                            <template x-if="types[selectedType]?.fee !== 'Free'"><span x-text="$store.lang.current === 'en' ? 'Pay at the office (' + types[selectedType]?.fee + ')' : 'Bayaran sa opisina (' + types[selectedType]?.fee + ')'"></span></template>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Structured field details (Solo Parent, FACED, etc.) -->
                        <div class="mb-5" x-show="types[selectedType]?.fields?.length" x-cloak>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"><x-ui.t fil="Detalye ng Aplikasyon" en="Application Details" /></p>
                            <div class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                                <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type !== 'table')" :key="f.key">
                                    <div class="flex items-center justify-between gap-3 px-3.5 py-2 text-xs" x-show="formData[f.key] && (!Array.isArray(formData[f.key]) || formData[f.key].length)">
                                        <span class="text-slate-500 shrink-0" x-text="f.label"></span>
                                        <span class="font-medium text-slate-700 text-right truncate" x-text="Array.isArray(formData[f.key]) ? formData[f.key].join(', ') : formData[f.key]"></span>
                                    </div>
                                </template>
                            </div>

                            <template x-for="f in (types[selectedType]?.fields || []).filter(f => f.type === 'table')" :key="f.key">
                                <div class="mt-3" x-show="(formData[f.key] || []).length">
                                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                        <i :data-lucide="f.rowIcon || 'list'" class="w-3 h-3 text-purple-400"></i>
                                        <span x-text="f.label"></span>
                                    </p>
                                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-purple-50/60 border-b border-slate-200">
                                                    <template x-for="col in f.columns" :key="col.key">
                                                        <th class="px-2.5 py-2 text-left font-semibold text-purple-700 whitespace-nowrap" x-text="col.label"></th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
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

                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 mb-5">
                            <template x-for="(req, i) in types[selectedType]?.requirements || []" :key="i">
                                <li class="flex items-center gap-2 text-xs">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-slate-300'"></i>
                                    <span :class="attachments[i] ? 'text-slate-600' : 'text-slate-500'" class="truncate" x-text="req"></span>
                                </li>
                            </template>
                        </ul>

                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                        <div class="flex items-center rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-4">
                            <template x-for="(step_label, i) in types[selectedType]?.process || []" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span class="w-7 h-7 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs font-semibold shrink-0 shadow-sm" x-text="i + 1"></span>
                                        <span class="text-[10px] text-slate-500 max-w-[80px] leading-tight" x-text="step_label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 bg-purple-200 -mt-5" x-show="i < (types[selectedType]?.process || []).length - 1"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    </div>

                    <x-slot:footer>
                        <p x-show="!submitted && (stepError || stepWarning)" x-cloak role="alert" class="mr-auto min-w-0 text-xs leading-snug" :class="stepError ? 'text-rose-600' : 'text-amber-700'" x-text="stepError || stepWarning"></p>
                        <x-ui.button variant="secondary" size="sm" x-show="!submitted" @click="stepError = ''; stepWarning = ''; step > 1 ? step-- : (open = false, reset())">
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
                            <span x-text="submitting ? ($store.lang.current === 'en' ? 'Submitting…' : 'Isinusumite…') : ($store.lang.current === 'en' ? (step < 4 ? 'Continue' : 'Submit Request') : (step < 4 ? 'Magpatuloy' : 'Isumite ang Kahilingan'))"></span>
                        </x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
                </div>
            </div>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500"><x-ui.t fil="Reference" en="Reference" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500"><x-ui.t fil="Uri ng Tulong" en="Assistance Type" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell"><x-ui.t fil="Opisina" en="Office" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell"><x-ui.t fil="Layunin" en="Purpose" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell"><x-ui.t fil="Isinumite" en="Submitted" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500"><x-ui.t fil="Status" en="Status" /></th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="req in filteredRequests" :key="req.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer" @click="openProgress(req)"
        tabindex="0"
        @keydown.enter.prevent="openProgress(req)"
        @keydown.space.prevent="openProgress(req)">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="req.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="req.type"></td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="req.office"></td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="req.purpose"></td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell" x-text="req.submitted"></td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                :class="(statusStyles[req.status] || statusStyles['Draft'])[0]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="(statusStyles[req.status] || statusStyles['Draft'])[1]"
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
        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="!requestsLoading && !requestsFailed && filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang kahilingang tumutugma dito." en="No requests in this view." /></p>
        </div>

        <!-- Track Request -->
        <div x-data="{ get open() { return progressOpen }, set open(v) { progressOpen = v } }">
            <x-ui.modal title="Subaybayan ang Kahilingan" titleEn="Track Request" maxWidth="md">
                <template x-if="selectedRequest">
                    <div>
                        <p class="text-xs font-mono text-slate-500" x-text="selectedRequest.ref"></p>
                        <p class="text-sm font-semibold text-navy-900" x-text="selectedRequest.type"></p>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="selectedRequest.purpose"></p>

                        <!-- Progress stepper -->
                        <div class="flex items-center mt-6 mb-2" x-show="selectedRequest.status !== 'Rejected' && selectedRequest.status !== 'Cancelled'">
                            <template x-for="(label, i) in stepOrder" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                                            :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-500 text-white' : (stepIndex(selectedRequest.status) === i ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="stepIndex(selectedRequest.status) > i" x-cloak></i>
                                            <span x-show="stepIndex(selectedRequest.status) <= i" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-[9px] max-w-[60px] leading-tight" :class="stepIndex(selectedRequest.status) === i ? 'text-navy-900 font-medium' : 'text-slate-500'" x-text="label"></span>
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
                                <i data-lucide="ban" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-600"><x-ui.t fil="Kinansela ang kahilingang ito" en="This request was cancelled" /></p>
                                    <p class="text-xs text-slate-600 mt-0.5" x-show="selectedRequest.remarks" x-text="selectedRequest.remarks"></p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 mt-5 p-3.5" x-show="['Submitted', 'Initial Screening'].includes(selectedRequest.status) && (selectedRequest.requirements || []).length" x-cloak>
                            <p class="text-xs font-semibold text-navy-900 mb-2"><x-ui.t fil="Aking mga Dokumento para dito" en="My documents for this request" /></p>
                            <div class="space-y-2">
                                <template x-for="req in (selectedRequest.requirements || [])" :key="req.key">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-2.5">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                            <p class="text-[11px] truncate" :class="req.file ? 'text-slate-500' : 'text-amber-600'" x-text="req.file || ($store.lang.current === 'en' ? 'No file attached yet' : 'Wala pang nakakabit na file')"></p>
                                        </div>
                                        <label x-show="req.attachable" class="shrink-0 text-[11px] font-medium text-purple-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                            <span x-text="req._busy ? '…' : (req.file ? ($store.lang.current === 'en' ? 'Change' : 'Palitan') : ($store.lang.current === 'en' ? 'Attach' : 'Ikabit'))"></span>
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <span x-show="!req.attachable" class="shrink-0 text-[11px] text-slate-500" x-text="req.status === 'Approved' ? 'Verified' : req.status"></span>
                                    </div>
                                </template>
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
                                                <i data-lucide="file-text" class="w-4 h-4 text-slate-500 shrink-0"></i>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <p class="text-[11px] text-slate-500 truncate" x-text="req.file || 'Not yet submitted'"></p>
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset shrink-0"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted', 'bg-slate-100 text-slate-600 ring-slate-200': req.status === 'Not submitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : (req.status === 'Not submitted' ? ($store.lang.current === 'en' ? 'Not submitted' : 'Wala pa') : req.status)"></span></span>
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
                                    <span x-text="resubmitting ? ($store.lang.current === 'en' ? 'Resubmitting…' : 'Isinusumite muli…') : ($store.lang.current === 'en' ? 'Resubmit Application' : 'Isumite Muli ang Aplikasyon')"></span>
                                </x-ui.button>
                            </div>
                        </div>

                        {{-- Internal re-verification: nothing for the resident
                             to do, so a plain note, never a correction panel. --}}
                        <div class="rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Returned for Reassessment'" x-cloak>
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="hourglass" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
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
                                    <p class="text-xs font-semibold text-green-800"><x-ui.t fil="Natanggap na ang tulong" en="Assistance received" /></p>
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

                        <div class="grid grid-cols-2 gap-3 mt-5">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1"><x-ui.t fil="Inaasahang Petsa ng Release" en="Expected Release" /></p>
                                <p class="text-sm font-semibold text-navy-900" x-text="expectedRelease"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1"><x-ui.t fil="Isinumite" en="Submitted" /></p>
                                <p class="text-sm font-semibold text-navy-900" x-text="selectedRequest.submitted"></p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3.5 mt-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-2"><x-ui.t fil="Saan Kukunin / Sino ang Tatawagan" en="Where to Claim / Who to Contact" /></p>
                            <p class="text-sm font-medium text-navy-900" x-text="selectedRequest.office"></p>
                            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i><span x-text="pickupInfo?.address"></span></p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="phone" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i><span x-text="pickupInfo?.contact"></span></p>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button size="sm" @click="progressOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
</x-layouts.citizen>
