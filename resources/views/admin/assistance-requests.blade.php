@php
    $barangays = config('esperanza.barangays');
    // Lookup tables only. The cases come from the API after paint
    // (GET /admin/tulong/requests). This page used to render a fixture queue
    // with synthesized requirements and form answers, so staff never saw a
    // request a resident had filed.
    $__fixtures = require base_path('resources/data/admin/assistance-requests.php');
    $requests = [];
    $validationGuide = config('esperanza_validation_guide');
    $typeToServiceGuideKey = $__fixtures['typeToServiceGuideKey'];
    $tulongClassification = $__fixtures['tulongClassification'];
    $formSpecs = config('esperanza_service_form_specs');
    $formSpecsByType = collect($formSpecs)
        ->map(fn ($spec) => ($spec['alias'] ?? null) ? ($formSpecs[$spec['alias']] ?? null) : $spec)
        ->filter()
        ->all();
    // The questions the PORTAL's wizard asks, per service key (the same keys
    // the API returns), so a request filed from the portal is labelled by the
    // form it was actually filed on. One filed from the app answers the app's
    // own form spec instead; formStepsFor() tells them apart by the answers.
    $wizardFieldsByKey = collect(config('esperanza_municipal_documents.documents'))
        ->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => ! empty($d['key']) && ! empty($d['fields']))
        ->mapWithKeys(fn ($d) => [$d['key'] => collect($d['fields'])
            ->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'] ?? $f['key'], 'type' => $f['type'] ?? 'text'])
            ->values()->all()])
        ->all();
    // Service key -> this catalog's name, for the lookups below that are keyed
    // by name: the backend's display name differs for some services (TUPAD,
    // ERPAT), its key does not.
    $catalogNameByKey = collect(config('esperanza_municipal_documents.documents'))
        ->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => ! empty($d['key']))
        ->pluck('name', 'key')
        ->all();
    $referencePdfByType = collect(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => !empty($d['reference_pdf']))
        ->pluck('reference_pdf', 'name')
        ->map(fn ($path) => asset($path));

    $tabs = $__fixtures['tabs'];
    $actionStatuses = $__fixtures['actionStatuses'];
    // Tulong's own case-status vocabulary — see PROCESS_TAB_FLOW.txt Part 2
    // for the full spec. Deliberately NOT Dokyu's Submitted/Under
    // Verification/Approved/Mark to Release/Released — Tulong is a social-
    // welfare case (screening -> eligibility assessment -> approval ->
    // disbursement -> release), not a document-preparation workflow, and
    // this file previously just copied Dokyu's lifecycle wholesale.
    //
    // Read from config/esperanza_tulong_lifecycle.php rather than hand-typed
    // here: config/ is what consuming codebases vendor, and they cannot read
    // this Blade file. The eleven names lived only here and in a .txt file,
    // which left the backend lane unable to vendor the vocabulary at all.
    $allTulongStatuses = config('esperanza_tulong_lifecycle.all');
    // Per-type metadata (requirements, process labels, and — new —
    // processingKind, which the Assistance Processing/Release sections use
    // to decide which fields actually apply to a given assistance type;
    // see config/esperanza_tulong_types.php's own header comment on that
    // field) keyed by name for a direct client-side lookup.
    $tulongTypesByName = collect(config('esperanza_tulong_types.types'))->keyBy('name');
@endphp

<x-layouts.admin title="Tulong" subtitle="Review and manage citizen assistance requests." active="assistance-requests">
    <div
        x-data="{
            tab: 'all',
            search: '',
            categoryFilter: '',
            officeFilter: '',
            levelFilter: '',
            assignedFilter: '',
            barangayFilter: '',
            showAdvancedFilters: false,
            open: false,
            selected: null,
            processing: false,
            rejectRemarks: '',
            // Side-state action forms — Milestones 3/6/10 each require a
            // reason/specification before confirming, same pattern as the
            // existing Reject flow's rejectRemarks + Confirm button.
            showReturnForm: false,
            returnRemarks: '',
            showCancelForm: false,
            cancelRemarks: '',
            showRequirementsForm: false,
            missingRequirementPicks: [],
            missingRequirementOther: '',
            previewDoc: null,
            // Tulong runs the same lifecycle engine as Dokyu on the server
            // (RequestLifecycle; the case-management stages of
            // config/esperanza_tulong_lifecycle.php are backend finding B-044,
            // still open), so the queue shows and acts on the engine's
            // statuses. The stage-by-stage case forms that had nowhere to be
            // saved are gone until the backend stores them.
            requests: [],
            requestsLoading: true,
            requestsFailed: false,
            detailLoading: false,
            citizenAvatars: {},
            actionStatuses: ['Submitted', 'Under Verification', 'Under Review', 'Resubmitted'],
            allTulongStatuses: ['Submitted', 'Under Verification', 'Under Review', 'Resubmitted', 'Approved', 'Mark to Release', 'Released', 'Rejected'],
            formSpecsByType: @js($formSpecsByType),
            wizardFieldsByKey: @js($wizardFieldsByKey),
            catalogNameByKey: @js($catalogNameByKey),
            tulongClassification: @js($tulongClassification),
            referencePdfByType: @js($referencePdfByType),
            typeToServiceGuideKey: @js($typeToServiceGuideKey),
            requestType: 'tulong',
            tulongTypesByName: @js($tulongTypesByName),
            statusFilter: '',
            standardReasons: @js($validationGuide['standard_reasons']),
            decisionOutcomes: @js($validationGuide['decision_outcomes']),
            // What Assistance Processing/Release actually need to ask for
            // depends on the type — see config/esperanza_tulong_types.php's
            // processingKind field. Falls back to 'general' for any type
            // missing one rather than guessing from the name.
            get processingKind() {
                const name = this.catalogNameByKey[this.selected?.service_key] || this.selected?.type;
                return this.tulongTypesByName[name]?.processingKind || 'general';
            },
            // Evidence Strength Hierarchy (guide section 1.2) — a light
            // keyword heuristic since this is mock/reference data, not a
            // real document classifier.
            evidenceTier(name) {
                const n = (name || '').toLowerCase();
                if (n.includes('interview') || n.includes('case assessment') || n.includes('registry') || n.includes('source verification')) return 4;
                if (n.includes('government') || n.includes('psa') || n.includes('valid') && n.includes('id') || n.includes('medical certificate') || n.includes('hospital') || n.includes('tax declaration') || n.includes('death certificate') || n.includes('birth certificate') || n.includes('receipt')) return 3;
                if (n.includes('barangay') || n.includes('school') || n.includes('employer') || n.includes('lease') || n.includes('utility') || n.includes('clearance')) return 2;
                return 1;
            },
            insertStandardReason(label) {
                const r = this.standardReasons.find(x => x.label === label);
                if (r) this.rejectRemarks = r.message;
            },
            get flaggedRequirements() {
                return (this.selected?.requirements || []).filter(r => r.reject_reason || r.remarks);
            },
            useRequirementReasons() {
                if (!this.flaggedRequirements.length) return;
                this.rejectRemarks = this.flaggedRequirements
                    .map(r => r.name + ': ' + (r.remarks || r.reject_reason))
                    .join('\n\n');
            },
            // Full mobile form preview helpers — see
            // components/admin/request-form-review.blade.php.
            formatFieldDate(iso) {
                if (!iso) return '—';
                const [y, m, d] = iso.split('-').map(Number);
                return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            },
            derivedAgeDisplay(step, field) {
                const source = step.fields.find(f => f.key === field.derivedFromKey);
                if (!source?.answer) return '—';
                const [y] = source.answer.split('-').map(Number);
                return (2026 - y) + ' years old';
            },
            fieldIcon(field) {
                const byKey = {
                    dateOfBirth: 'calendar-days', dateOfDeath: 'calendar-days',
                    placeOfBirth: 'map-pin', placeOfDeath: 'map-pin',
                    sex: 'venus-and-mars', civilStatus: 'heart-handshake',
                    occupation: 'briefcase', presentOccupation: 'briefcase',
                    fatherOccupation: 'briefcase', motherOccupation: 'briefcase',
                    businessName: 'store', businessNature: 'tag', yearsOperating: 'history',
                    capitalAmount: 'wallet', monthlyIncome: 'wallet', annualIncome: 'wallet', parentsMonthlyIncome: 'wallet',
                    schoolName: 'school', yearOrGradeLevel: 'graduation-cap', degreeProgramOrCourse: 'book-open',
                    lastSchoolAverageGrade: 'percent', educationalAttainment: 'graduation-cap',
                    fatherName: 'user', motherName: 'user', spouseName: 'heart', personFullName: 'user',
                    deceasedFullName: 'user', motherMaidenName: 'user', emergencyContactName: 'user',
                    communityInvolvement: 'heart-handshake', postGraduationPlans: 'flag',
                    familyComposition: 'users', needsOrProblems: 'hand-heart',
                    emergencyContactNumber: 'phone', residencyType: 'home', citizenship: 'flag',
                    religion: 'landmark', philsysIdNumber: 'id-card', employmentStatus: 'briefcase',
                    lastGovtOffice: 'landmark', lastGovtPosition: 'briefcase', lastGovtYear: 'calendar',
                    isPantawidBeneficiary: 'check-square', isIndigenousPerson: 'check-square',
                    receivingPension: 'check-square', confirmFirstTime: 'check-square',
                    soloParentClassification: 'list-checks', purpose: 'clipboard-list',
                };
                if (byKey[field.key]) return byKey[field.key];
                const byType = { date: 'calendar-days', derivedAge: 'cake', select: 'list-checks', multiselect: 'list-checks', checkbox: 'check-square', number: 'hash', textarea: 'align-left', text: 'pencil-line' };
                return byType[field.type] || 'file-text';
            },
            returnToProfile: null,
            // Arrived from a resident's profile via Open in Tulong — pre-filter to
            // their name and surface a way back to exactly where they came from.
            init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                const citizen = params.get('citizen');
                if (ref || citizen) {
                    this.search = ref || citizen;
                    this.tab = 'all';
                }
                const stored = sessionStorage.getItem('returnToProfile');
                if (stored) {
                    try { this.returnToProfile = JSON.parse(stored); } catch (e) { this.returnToProfile = null; }
                    sessionStorage.removeItem('returnToProfile');
                }
                this.loadRequests().then(() => {
                    // Arriving from a notification (see components/admin/topbar.blade.php)
                    // — jump straight into that exact request, not just the filtered list.
                    if (!ref) return;
                    const match = this.requests.find(r => r.ref === ref);
                    if (match) this.$nextTick(() => this.openRequest(match));
                });
            },
            // ---- The queue, from the API -----------------------------------
            // Every action calls the lifecycle endpoint it names and takes the
            // server's answer as the new state; a reload shows the same.
            apiBase(ref) {
                return '/admin/' + this.requestType + '/requests/' + encodeURIComponent(ref);
            },
            apiMessage(err, fallback) {
                return err && err.localised ? err.localised('en') : fallback;
            },
            formatDay(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return isNaN(d) ? iso : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            async loadRequests() {
                this.requestsLoading = true;
                this.requestsFailed = false;
                try {
                    const res = await $api.getAll('/admin/' + this.requestType + '/requests', { params: { sort: '-submitted_at' } });
                    this.requests = (res.data || []).map((row) => this.fromApi(row));
                } catch (err) {
                    this.requestsFailed = true;
                } finally {
                    this.requestsLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            fromApi(row) {
                const type = row.service || row.service_key || 'Assistance';
                // The name-keyed tables below use this catalog's name for the
                // service, which is not always the backend's display name.
                const name = this.catalogNameByKey[row.service_key] || type;
                const cls = this.tulongClassification[name] || this.tulongClassification[type] || {};
                return {
                    ref: row.ref,
                    citizen: row.applicant || '—',
                    type,
                    service_key: row.service_key,
                    office: row.office || cls.office || '—',
                    barangay: row.barangay || '',
                    submitted: this.formatDay(row.submitted),
                    submitted_at: row.submitted,
                    status: row.status,
                    category: cls.category || 'Social Protection',
                    level: cls.level || 'Municipal',
                    reference_pdf: this.referencePdfByType[name] || this.referencePdfByType[type] || null,
                    service_guide_key: this.typeToServiceGuideKey[name] || this.typeToServiceGuideKey[type] || null,
                    assigned: null,
                    purpose: '',
                    requirements: null,
                    remarks: null,
                    history: [],
                    release_evidence: null,
                    release_evidence_at: null,
                };
            },
            // The request's form answers, labelled by the form they were filed
            // on. A request from the app answers the app's form spec (looked up
            // by name); one from the portal answers the portal wizard's fields
            // (by service key) -- their field keys differ, so the answers say
            // which applies. Anything neither knows is still shown, under its
            // own name, rather than dropped.
            formStepsFor(type, formData, serviceKey) {
                const data = formData || {};
                const answered = (v) => v !== null && v !== undefined && v !== '' && !(Array.isArray(v) && !v.length);
                const seen = new Set(['purpose']);
                let steps = [];
                const spec = this.formSpecsByType[this.catalogNameByKey[serviceKey] || type] || this.formSpecsByType[type];
                const specFields = (spec?.steps || []).flatMap((s) => s.fields || []);
                const wizardFields = this.wizardFieldsByKey[serviceKey] || [];
                // The two forms share a few keys (age, purpose), so pick the one
                // that covers more of the answers, not the first that covers any.
                const covered = (fields) => fields.filter((f) => f.key !== 'purpose' && answered(data[f.key])).length;
                const specCovers = covered(specFields);
                const wizardCovers = covered(wizardFields);
                if (specCovers > 0 && specCovers >= wizardCovers) {
                    steps = spec.steps.map((step) => ({
                        label: step.label,
                        description: step.description || null,
                        fields: (step.fields || []).map((f) => { seen.add(f.key); return this.answerField(f, data[f.key]); }),
                    }));
                } else if (wizardCovers > 0) {
                    steps = [{
                        label: 'Application details',
                        description: null,
                        fields: wizardFields.map((f) => { seen.add(f.key); return this.answerField(f, data[f.key]); }),
                    }];
                }
                const extra = Object.keys(data).filter((k) => !seen.has(k) && answered(data[k]));
                if (extra.length) {
                    steps.push({
                        label: steps.length ? 'Other details' : 'Application details',
                        description: null,
                        fields: extra.map((k) => this.answerField({ key: k, label: k.replace(/([A-Z])/g, ' $1').replace(/[_-]+/g, ' ').replace(/^./, (c) => c.toUpperCase()), type: 'text' }, data[k])),
                    });
                }
                return steps;
            },
            // One answer in the shape x-admin.request-form-review prints. The
            // portal's field types are mapped onto the review's (a checkbox
            // group is a multiselect; a table prints one line per row), and an
            // array or object answer is joined instead of printing as
            // '[object Object]'.
            answerField(f, value) {
                const type = { 'checkbox-group': 'multiselect', derived_age: 'text', table: 'textarea' }[f.type] || f.type || 'text';
                let answer = value ?? null;
                if (type === 'multiselect') {
                    answer = Array.isArray(answer) ? answer : (answer ? [String(answer)] : []);
                } else if (Array.isArray(answer)) {
                    answer = answer.map((row) => (row && typeof row === 'object')
                        ? Object.values(row).filter((v) => v !== '' && v !== null && v !== undefined).join(' · ')
                        : String(row)).filter(Boolean).join('; ');
                } else if (answer && typeof answer === 'object') {
                    answer = Object.entries(answer).map(([k, v]) => k + ': ' + v).join(', ');
                }
                return { ...f, type, answer };
            },
            fromApiRequirement(q) {
                return {
                    key: q.key,
                    name: q.label,
                    status: q.status === 'Pending Review' ? 'Submitted' : q.status,
                    reject_reason: q.reason,
                    remarks: q.remarks,
                    file: q.file?.name || null,
                    file_url: q.file?.url || null,
                    file_mime: q.file?.mime || null,
                    preview_asset: q.file?.url && (q.file?.mime || '').startsWith('image/') ? q.file.url : null,
                    versions: (q.versions || []).map((v) => ({
                        file: v.file?.name || null,
                        file_url: v.file?.url || null,
                        status: v.status === 'Pending Review' ? 'Submitted' : v.status,
                        date: this.formatDay(v.archived_at),
                        reject_reason: v.reason,
                        remarks: v.remarks,
                    })),
                    _decidingOutcome: null,
                    _reason: '',
                    _remarks: '',
                    _showVersions: false,
                };
            },
            applyDetail(r, d) {
                r.status = d.status;
                r.office = d.office || r.office;
                r.remarks = d.decision_remarks || null;
                r.form_data = d.form_data || {};
                r.purpose = (d.form_data && d.form_data.purpose) ? [].concat(d.form_data.purpose).join(', ') : '';
                r.form_steps = this.formStepsFor(r.type, d.form_data, r.service_key);
                r.requirements = (d.requirements || []).map((q) => this.fromApiRequirement(q));
                r.payment = d.payment || null;
                r.history = (d.history || []).map((h) => ({ stage: h.to, date: this.formatDay(h.at), by: h.actor_name || '—', note: h.trigger || '' }));
                const proof = d.proof_of_release || {};
                if (proof.captured) {
                    r.release_evidence = proof.file?.url || r.release_evidence || null;
                    r.release_evidence_at = proof.note || 'on file';
                }
            },
            async refreshSelected() {
                if (!this.selected) return;
                const r = this.selected;
                const res = await $api.get(this.apiBase(r.ref));
                this.applyDetail(r, res.data);
                this.$nextTick(() => window.renderIcons?.());
            },
            backToProfile() {
                if (!this.returnToProfile) return;
                sessionStorage.setItem('reopenResident', JSON.stringify(this.returnToProfile));
                window.location.href = '/admin/constituents';
            },
            get categories() { return [...new Set(this.requests.map(r => r.category))].sort(); },
            get offices() { return [...new Set(this.requests.map(r => r.office))].sort(); },
            get isMunicipalScope() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal';
            },
            barangays: @js($barangays),
            // Colors per Tulong's own case-status vocabulary (see
            // PROCESS_TAB_FLOW.txt Part 2) — blue/indigo through the review
            // stages, amber at the decision point (For Approval), purple
            // while assistance is actively being prepared, teal/green once
            // it's ready-for/actually released. Side states get their own
            // distinct tones so they never look like ordinary forward
            // progress: amber (Needs Requirements — waiting on the citizen,
            // same tone as For Approval's needs-a-decision feel but a
            // different context), orange (Returned for Reassessment — a
            // stronger signal that work must be redone), rose (Rejected),
            // slate (Cancelled).
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Resubmitted': ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Mark to Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Released': ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
                'Initial Screening': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Needs Requirements': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Assessment': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'For Approval': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Returned for Reassessment': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Assistance Processing': ['bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
                'Ready for Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Cancelled': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
            },
            matchesTab(r) {
                if (this.statusFilter) return r.status === this.statusFilter;
                if (this.tab === 'all') return true;
                if (this.tab === 'action') return this.actionStatuses.includes(r.status);
                if (this.tab === 'release') return r.status === 'Mark to Release';
                if (this.tab === 'done') return ['Released', 'Rejected'].includes(r.status);
                return true;
            },
            get filteredRequests() {
                const q = this.search.trim().toLowerCase();
                return this.requests.filter(r =>
                    this.$store.session.inScope(r.barangay) &&
                    this.$store.session.inDeptScope(r.office) &&
                    this.matchesTab(r) &&
                    (this.categoryFilter === '' || r.category === this.categoryFilter) &&
                    (this.officeFilter === '' || r.office === this.officeFilter) &&
                    (this.levelFilter === '' || r.level === this.levelFilter) &&
                    (this.assignedFilter === '' || (this.assignedFilter === 'unassigned' ? !r.assigned : r.assigned === this.assignedFilter)) &&
                    (this.barangayFilter === '' || r.barangay === this.barangayFilter) &&
                    (q === '' || r.ref.toLowerCase().includes(q) || r.citizen.toLowerCase().includes(q))
                );
            },
            clearAdvancedFilters() { this.categoryFilter = ''; this.officeFilter = ''; this.levelFilter = ''; this.assignedFilter = ''; this.barangayFilter = ''; this.statusFilter = ''; },
            // The engine's main line (see statusOrder below); Under Review and
            // Resubmitted are the correction loop at the verification step.
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Rejected': 0 };
                return map[status] ?? 0;
            },
            // Opening is the server's own trigger: GET on a Submitted request
            // moves it to Under Verification (RequestLifecycle::open).
            async openRequest(r) {
                this.selected = r;
                this.rejectRemarks = '';
                this.previewDoc = null;
                this.open = true;
                this.detailLoading = true;
                try {
                    await this.refreshSelected();
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not open ' + r.ref + '.'), variant: 'error' });
                } finally {
                    this.detailLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            async rejectCase() {
                if (this.processing || !this.rejectRemarks.trim()) return;
                this.processing = true;
                const r = this.selected;
                try {
                    const res = await $api.post(this.apiBase(r.ref) + '/reject', { decision_remarks: this.rejectRemarks.trim() });
                    this.applyDetail(r, res.data);
                    this.rejectRemarks = '';
                    this.$dispatch('toast', { message: 'Case ' + r.ref + ' rejected.', variant: 'error' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not reject ' + r.ref + '.'), variant: 'error' });
                } finally { this.processing = false; }
            },
            rejectReasons: ['Invalid document', 'Incorrect document', 'Unreadable', 'Expired', 'Wrong document', 'Missing information', 'Does not meet requirement', 'Other'],
            async verifyRequirement(req) {
                if (req._busy) return;
                req._busy = true;
                try {
                    await $api.post(this.apiBase(this.selected.ref) + '/requirements/' + encodeURIComponent(req.key) + '/approve');
                    await this.refreshSelected();
                    this.$dispatch('toast', { message: req.name + ' verified.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not verify ' + req.name + '.'), variant: 'error' });
                } finally { req._busy = false; }
            },
            startRequirementDecision(req, outcome) {
                req._decidingOutcome = outcome;
                req._reason = '';
                req._remarks = '';
            },
            // Flag for Replacement asks the resident for a new file (the whole
            // case moves to Under Review on the server); Reject refuses that
            // document. The resident is notified by the server either way.
            async confirmRequirementDecision(req) {
                if (!req._reason || req._busy) return;
                const outcome = req._decidingOutcome;
                const action = outcome === 'Rejected' ? 'reject' : 'flag';
                const remarks = (req._remarks || '').trim() || req._reason;
                req._busy = true;
                try {
                    await $api.post(this.apiBase(this.selected.ref) + '/requirements/' + encodeURIComponent(req.key) + '/' + action, { reason: req._reason, remarks });
                    await this.refreshSelected();
                    this.$dispatch('toast', { message: req.name + ' marked ' + outcome + '.', variant: outcome === 'Rejected' ? 'error' : 'warning' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not save the decision on ' + req.name + '.'), variant: 'error' });
                } finally { req._busy = false; }
            },
            // Plain-language read on what to do next, shown in the System
            // Advice panel beside the stepper — mirrors the Esperanza Mobile
            // app's own VerificationStatusPanel pattern (icon + title +
            // explanation) so the admin isn't left to infer it from the raw
            // requirement statuses alone. Copy here is Tulong's own
            // (Milestone 17) — deliberately NOT the same text as the
            // identical-purpose getter in admin/document-requests.blade.php,
            // since this is a case-management flow, not a document one.
            get processAdvice() {
                if (!this.selected) return { icon: 'info', tone: 'brand', title: 'Reviewing', message: '' };
                const st = this.selected.status;
                if (this.detailLoading) return { icon: 'loader-circle', tone: 'brand', title: 'Loading', message: 'Opening the case…' };
                if (st === 'Rejected') return { icon: 'circle-x', tone: 'rose', title: 'Rejected', message: this.selected.remarks || 'This case was rejected.' };
                if (st === 'Released') return { icon: 'circle-check', tone: 'emerald', title: 'Released', message: 'The assistance was released to the beneficiary. No further action is needed.' };
                if (st === 'Mark to Release') {
                    return this.selected.release_evidence
                        ? { icon: 'circle-check', tone: 'emerald', title: 'Ready to Confirm', message: 'Proof of Release is ready. Confirm the release once the beneficiary actually receives it.' }
                        : { icon: 'truck', tone: 'amber', title: 'Release Assistance', message: 'Capture Proof of Release, then confirm once received by the beneficiary.' };
                }
                if (st === 'Approved') return { icon: 'package', tone: 'indigo', title: 'Prepare Assistance', message: 'The case is approved. Prepare the assistance, then mark it ready for release.' };
                const reqs = this.selected.requirements || [];
                const flagged = reqs.filter(r => r.status === 'Flagged for Replacement').length;
                const pending = reqs.filter(r => r.status === 'Submitted' || r.status === 'Resubmitted').length;
                if (st === 'Under Review' && flagged) return { icon: 'clock', tone: 'amber', title: 'Awaiting Beneficiary', message: flagged + ' document(s) flagged for replacement — waiting for the resident to send new ones.' };
                if (pending) return { icon: 'search-check', tone: 'indigo', title: 'Check Eligibility', message: pending + ' of ' + reqs.length + ' requirement(s) still need review. Approve each, or flag what must be replaced.' };
                if (reqs.length && reqs.every(r => r.status === 'Approved')) return { icon: 'circle-check', tone: 'emerald', title: 'Ready to Approve', message: 'Every requirement is verified. You can approve the assistance.' };
                return { icon: 'clipboard-list', tone: 'brand', title: 'Review Case', message: 'Review the applicant and the submitted requirements, then decide.' };
            },
            get canReject() {
                return this.selected && ['Under Verification', 'Under Review', 'Resubmitted'].includes(this.selected.status);
            },
            statusOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            terminalStatuses: ['Released', 'Rejected', 'Completed', 'Cancelled', 'Archived'],
            stageActionLabels: {
                'Under Verification': 'Approve Assistance',
                'Under Review': 'Approve Assistance',
                'Resubmitted': 'Approve Assistance',
                'Approved': 'Mark Ready for Release',
                'Mark to Release': 'Confirm Release',
            },
            get nextStageLabel() {
                if (!this.selected || this.detailLoading) return '';
                return this.stageActionLabels[this.selected.status] ?? '';
            },
            // A real photo, held here and sent with the release itself
            // (POST .../release, proof_file): the server refuses a release
            // without proof.
            captureReleaseEvidence(source) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                if (source === 'camera') input.setAttribute('capture', 'environment');
                input.onchange = () => {
                    const file = input.files && input.files[0];
                    if (!file || !this.selected) return;
                    if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                    this.selected._proofFile = file;
                    this.selected._proofPreview = URL.createObjectURL(file);
                    this.selected.release_evidence = this.selected._proofPreview;
                    this.selected.release_evidence_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                    this.$nextTick(() => window.renderIcons?.());
                };
                input.click();
            },
            clearReleaseEvidence() {
                if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                this.selected._proofFile = null;
                this.selected._proofPreview = null;
                this.selected.release_evidence = null;
                this.selected.release_evidence_at = null;
            },
            // The footer's one primary button, a real endpoint per stage:
            //   Under Verification / Under Review / Resubmitted -> approve
            //   Approved        -> mark-to-release
            //   Mark to Release -> release, carrying the proof-of-release photo
            async primaryAction() {
                if (this.processing) return;
                const r = this.selected;
                this.processing = true;
                try {
                    let res;
                    if (['Under Verification', 'Under Review', 'Resubmitted'].includes(r.status)) {
                        res = await $api.post(this.apiBase(r.ref) + '/approve');
                    } else if (r.status === 'Approved') {
                        res = await $api.post(this.apiBase(r.ref) + '/mark-to-release');
                    } else if (r.status === 'Mark to Release') {
                        const form = new FormData();
                        if (r._proofFile) form.append('proof_file', r._proofFile);
                        form.append('proof_note', 'Released to ' + r.citizen + '. Recorded by ' + (this.$store.session.account?.name || 'staff') + '.');
                        res = await $api.post(this.apiBase(r.ref) + '/release', form);
                    } else {
                        return;
                    }
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: r.ref + ' moved to: ' + r.status + '.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not update ' + r.ref + '.'), variant: 'error' });
                } finally {
                    this.processing = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('tulong')" x-cloak>
            <x-admin.access-restricted module="Tulong (Citizen Assistance)" icon="hand-heart" />
        </div>

        <div x-show="$store.session.can('tulong')" x-cloak>

        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mb-4 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing Tulong requests for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Profile
            </button>
        </div>

        {{-- Live-computed from $requests (see valueBind), matching Dokyu's
             own pattern — these used to be hardcoded literals ("417",
             "34", "112") that never actually reflected the mock data. --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Active Cases" value="{{ collect($requests)->whereNotIn('status', ['Completed', 'Rejected', 'Cancelled', 'Archived'])->count() }}" valueBind="requests.filter(r => !terminalStatuses.includes(r.status)).length" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="{{ collect($requests)->whereIn('status', $actionStatuses)->count() }}" valueBind="requests.filter(r => actionStatuses.includes(r.status)).length" icon="inbox" color="orange" sublabel="Review or assessment" :delay="40" />
            <x-ui.stat-card @click="tab = 'release'" class="cursor-pointer" label="Ready for Release" value="0" valueBind="requests.filter(r => r.status === 'Mark to Release').length" icon="package-check" color="green" :delay="80" />
            {{-- These two were hard-coded peso figures ("₱1.8M of ₱2.5M") shown as
                 if they were the municipality's budget. No budget or
                 disbursement amount is recorded anywhere, so they count what is
                 real instead. --}}
            <x-ui.stat-card @click="tab = 'done'" class="cursor-pointer" label="Released" value="0" valueBind="requests.filter(r => r.status === 'Released').length" icon="hand-coins" color="gold" sublabel="Assistance received" :delay="120" />
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="All Cases" value="0" valueBind="requests.length" icon="wallet" color="brand" sublabel="Every status" :delay="160" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search reference or citizen" type="text" x-model="search" placeholder="Search reference or citizen..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <select aria-label="Category filter" x-model="categoryFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0">
                    <option value="">All Categories</option>
                    <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <button
                    type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border transition-colors shrink-0"
                    :class="showAdvancedFilters || officeFilter || levelFilter || assignedFilter || barangayFilter ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
                >
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced
                </button>
                <button
                    type="button" @click="loadRequests()"
                    title="Reload the cases from the server."
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border border-slate-200 bg-slate-50 text-slate-500 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 transition-colors shrink-0"
                >
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    @foreach($tabs as $key => $label)
                        <button
                            type="button" @click="tab = '{{ $key }}'"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' cases'"></p>
            </div>

            <div
                x-show="showAdvancedFilters" x-cloak
                x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100"
            >
                {{-- Milestone 20 — every Tulong status individually
                     selectable, without crowding the quick-tab bar above
                     with 11 buttons. --}}
                <select aria-label="Status filter" x-model="statusFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Statuses</option>
                    <template x-for="s in allTulongStatuses" :key="s"><option :value="s" x-text="s"></option></template>
                </select>
                <select aria-label="Office filter" x-model="officeFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Offices</option>
                    <template x-for="o in offices" :key="o"><option :value="o" x-text="o"></option></template>
                </select>
                <select aria-label="Level filter" x-model="levelFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Levels</option>
                    <option value="Municipal">Municipal</option>
                    <option value="Multi-Office">Multi-Office</option>
                </select>
                <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <span x-show="!isMunicipalScope" x-cloak class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                    <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                </span>
                <select aria-label="Assigned filter" x-model="assignedFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">Anyone / Unassigned</option>
                    <option value="unassigned">Unassigned only</option>
                </select>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors" x-show="statusFilter || officeFilter || levelFilter || assignedFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Assigned</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="r in filteredRequests" :key="r.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors group cursor-pointer" @click="openRequest(r)"
        tabindex="0"
        @keydown.enter.prevent="openRequest(r)"
        @keydown.space.prevent="openRequest(r)">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500 hidden xl:table-cell" x-text="r.ref" @click.stop="openRequest(r)"></td>
                        <td class="px-4 py-3" @click.stop="openRequest(r)">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img x-show="citizenAvatars[r.citizen]" :src="citizenAvatars[r.citizen]" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" :alt="r.citizen">
                                <div x-show="!citizenAvatars[r.citizen]" class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(r.citizen || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="r.citizen"></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700" x-text="r.type"></p>
                            <p class="text-[11px] text-slate-400 mt-0.5" x-text="r.category"></p>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell">
                            <span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap" x-text="r.office"></span>
                            <span
                                class="inline-block ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap"
                                :class="{ 'bg-brand-50 text-brand-700': r.level === 'Municipal', 'bg-purple-50 text-purple-700': r.level === 'Multi-Office' }"
                                x-text="r.level"
                            ></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell"><span x-text="'Brgy. ' + r.barangay"></span></td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell">
                            <span x-show="r.assigned" x-text="r.assigned"></span>
                            <span x-show="!r.assigned" class="text-slate-300 italic">Unassigned</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden xl:table-cell" x-text="r.submitted"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[r.status] || statusStyles['Draft'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[r.status] || statusStyles['Draft'])[1]"></span>
                                <span x-text="r.status"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop>
                            <button @click="openRequest(r)" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Review</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div x-show="requestsLoading" class="space-y-2 mt-3"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
        <div x-show="requestsFailed" x-cloak class="mt-3"><x-ui.error-state messageExpr="'Could not load assistance requests.'" retryExpr="loadRequests()" :retryable="true" /></div>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="!requestsLoading && !requestsFailed && filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600" x-text="requests.length ? 'No cases match these filters.' : 'No assistance requests yet.'"></p>
        </div>

        <x-ui.modal title="Tulong Case" maxWidth="4xl">
            <template x-if="selected">
                {{-- Three isolated regions so a long case never buries the
                     stepper or the advice off-screen: the stepper (left) and
                     System Advice (right) stay pinned in place while only the
                     middle column — fields, the submitted form, and the
                     documents list — scrolls. --}}
                <div class="flex flex-col sm:flex-row gap-5 sm:h-[66vh]">

                    {{-- LEFT — Stepper, isolated (never scrolls). Tulong's
                         own 7-stage case lifecycle (Milestone 2), not
                         Dokyu's 5-step document flow. Needs Requirements /
                         Returned for Reassessment are side states, not
                         steps of their own — they render as a small pill
                         under whichever step they branch from (Initial
                         Screening / For Approval), per Milestone 3. --}}
                    <div class="sm:w-44 shrink-0">
                        <p class="text-xs text-slate-400 mb-3 font-mono" x-text="selected.ref"></p>
                        <div class="flex flex-col">
                            @foreach(['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'] as $i => $step)
                                <div class="flex items-start gap-3">
                                    <div class="flex flex-col items-center shrink-0">
                                        <span
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold transition-all duration-[600ms] ease-standard shrink-0"
                                            :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'bg-purple-600 text-white shadow-card ring-4 ring-purple-100' : 'bg-slate-100 text-slate-400')"
                                        >
                                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="stepIndex(selected.status) > {{ $i }}" x-cloak></i>
                                            <span x-show="stepIndex(selected.status) <= {{ $i }}">{{ $i + 1 }}</span>
                                        </span>
                                        @if(!$loop->last)
                                            <div class="w-0.5 flex-1 my-1 rounded-full transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-100'" style="min-height: 1.25rem"></div>
                                        @endif
                                    </div>
                                    <div class="pt-1.5 pb-2 min-w-0">
                                        <span class="block text-[11px] leading-tight transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) === {{ $i }} ? 'text-navy-900 font-semibold' : 'text-slate-400'">{{ $step }}</span>
                                        @if($step === 'Initial Screening')
                                            <span x-show="selected.status === 'Needs Requirements'" x-cloak class="inline-block mt-1 text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">Needs Requirements</span>
                                        @endif
                                        @if($step === 'For Approval')
                                            <span x-show="selected.status === 'Returned for Reassessment'" x-cloak class="inline-block mt-1 text-[10px] font-semibold text-orange-700 bg-orange-50 border border-orange-200 rounded-full px-2 py-0.5">Returned for Reassessment</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-100 space-y-1.5" x-show="['Rejected', 'Cancelled'].includes(selected.status)" x-cloak>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2 py-1 rounded-full" :class="selected.status === 'Rejected' ? 'text-rose-700 bg-rose-50' : 'text-slate-600 bg-slate-100'">
                                <i :data-lucide="selected.status === 'Rejected' ? 'circle-x' : 'ban'" class="w-3 h-3"></i><span x-text="selected.status"></span>
                            </span>
                        </div>
                    </div>

                    {{-- MIDDLE — everything about this specific case; the
                         only column that scrolls. --}}
                    <div class="flex-1 min-w-0 sm:overflow-y-auto sm:min-h-0 scrollbar-thin sm:pr-1">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="hand-heart" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assistance Type</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Handling Office</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assigned Social Worker</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Submitted</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.submitted"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-2 mt-3"
                        x-show="!selected.assigned && (!$store.session.account || ['department_head', 'municipal_admin', 'super_admin'].includes($store.session.account?.roleKey))"
                    >
                        <p class="text-xs text-slate-400">This case hasn't been routed to a processor yet.</p>
                        <x-ui.button
                            variant="secondary" size="sm" icon="user-plus"
                            @click="selected.assigned = $store.session.account?.name || 'You'; $dispatch('toast', { message: 'Assigned to yourself.', variant: 'success' })"
                        >Assign to me</x-ui.button>
                    </div>

                    <x-admin.request-form-review />

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Submitted Documents</p>
                            <p class="text-[11px] text-slate-400" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="req in (selected.requirements || [])" :key="req.key">
                                <div class="rounded-xl border p-3" :class="{ 'border-emerald-200 bg-emerald-50/30': req.status === 'Approved', 'border-rose-200 bg-rose-50/30': req.status === 'Rejected', 'border-amber-200 bg-amber-50/30': req.status === 'Flagged for Replacement', 'border-sky-200 bg-sky-50/30': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' }">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                :class="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500') : 'bg-slate-100 text-slate-300'"
                                            >
                                                <i :data-lucide="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image') : 'file-x'" class="w-4 h-4"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <span
                                                        class="text-[9px] font-bold px-1 py-0.5 rounded shrink-0"
                                                        :class="{ 1: 'bg-slate-100 text-slate-500', 2: 'bg-blue-50 text-blue-600', 3: 'bg-emerald-50 text-emerald-600', 4: 'bg-purple-50 text-purple-600' }[evidenceTier(req.name)]"
                                                        :title="'Evidence Strength: Level ' + evidenceTier(req.name)"
                                                    >L<span x-text="evidenceTier(req.name)"></span></span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="req.file || 'Not yet submitted'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            {{-- The whole point of a review queue is looking at what was
                                                 actually submitted before deciding — these buttons used to
                                                 go straight from filename text to Approve/Reject with no way
                                                 to actually open the file in between. --}}
                                            <button
                                                type="button"
                                                x-show="req.file"
                                                @click="previewDoc = req; $nextTick(() => window.renderIcons?.())"
                                                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-purple-600 hover:border-purple-300 flex items-center justify-center transition-colors"
                                                title="View submitted document"
                                            ><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : req.status"></span></span>
                                        </div>
                                    </div>

                                    <div x-show="['Rejected', 'Flagged for Replacement'].includes(req.status) || (req.status === 'Resubmitted' && (req.versions || []).length)" class="mt-2 pt-2 border-t text-[11px]" :class="req.status === 'Rejected' ? 'border-rose-100/70' : req.status === 'Flagged for Replacement' ? 'border-amber-100/70' : 'border-sky-100/70'">
                                        <p class="font-semibold" x-show="req.reject_reason" :class="req.status === 'Rejected' ? 'text-rose-700' : 'text-amber-700'" x-text="req.reject_reason"></p>
                                        <p class="mt-0.5" :class="req.status === 'Rejected' ? 'text-rose-600' : 'text-amber-600'" x-show="req.remarks" x-text="req.remarks"></p>
                                        <p class="font-medium text-sky-700 flex items-center gap-1" x-show="req.status === 'Resubmitted'"><i data-lucide="refresh-ccw" class="w-3 h-3"></i> Citizen replaced this file — compare with the flagged version below.</p>
                                        <button type="button" x-show="(req.versions || []).length" @click="req._showVersions = !req._showVersions" class="mt-1.5 text-slate-400 hover:text-slate-600 transition-colors">
                                            <span x-text="(req._showVersions ? 'Hide' : 'Show') + ' version history (' + req.versions.length + ')'"></span>
                                        </button>
                                        <div x-show="req._showVersions" x-cloak class="mt-1.5 space-y-1 pl-2 border-l-2 border-slate-100">
                                            <template x-for="(v, vi) in (req.versions || [])" :key="vi">
                                                <p class="text-slate-400">Version <span x-text="vi + 1"></span> — <span x-text="v.status"></span> · <span x-text="v.date"></span><span x-show="v.reject_reason"> · <span x-text="v.reject_reason"></span></span></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Three decision outcomes at the requirement level: Approve /
                                         Flag for Replacement / Reject. No separate "needs verification"
                                         action — reviewing a requirement here already IS the
                                         verification step. --}}
                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="(req.status === 'Submitted' || req.status === 'Resubmitted') && !req._decidingOutcome">
                                        <button type="button" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Approve</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Flagged for Replacement')" class="text-[11px] font-medium text-amber-600 hover:underline">Flag for Replacement</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Rejected')" class="text-[11px] font-medium text-rose-600 hover:underline">Reject</button>
                                    </div>

                                    <div x-show="req._decidingOutcome" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-2">
                                        <p class="text-[11px] font-semibold text-slate-500" x-text="(decisionOutcomes[req._decidingOutcome]?.label || req._decidingOutcome) + ' — ' + req.name"></p>
                                        <select aria-label="Reason" x-model="req._reason" class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">Select a reason...</option>
                                            <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                                            <template x-for="reason in rejectReasons" :key="reason"><option :value="reason" x-text="reason"></option></template>
                                        </select>
                                        <textarea aria-label="Remarks for the citizen (optional)" x-model="req._remarks" rows="2" placeholder="Remarks for the citizen (optional)..." class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button" x-bind:disabled="!req._reason" @click="confirmRequirementDecision(req)"
                                                class="text-[11px] font-medium text-white disabled:opacity-40 disabled:pointer-events-none rounded-lg px-2.5 py-1.5 transition-colors"
                                                :class="req._decidingOutcome === 'Rejected' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-500 hover:bg-amber-600'"
                                            >Confirm</button>
                                            <button type="button" @click="req._decidingOutcome = null" class="text-[11px] font-medium text-slate-400 hover:text-slate-600">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Needs Requirements — Milestone 6: exactly which
                         item(s) the citizen still needs to submit. --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50/40 px-3.5 py-3 mt-3" x-show="selected.status === 'Needs Requirements'" x-cloak>
                        <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="file-plus-2" class="w-3.5 h-3.5"></i> Requirements Needed</p>
                        <ul class="mt-2 space-y-1">
                            <template x-for="item in (selected.screening?.missingRequirements || [])" :key="item">
                                <li class="text-xs text-amber-700 flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-amber-500 shrink-0"></span><span x-text="item"></span></li>
                            </template>
                        </ul>
                    </div>

                    {{-- Returned for Reassessment banner. --}}
                    <div class="rounded-xl border border-orange-200 bg-orange-50/50 px-3.5 py-3 mt-3 flex items-start gap-2.5" x-show="selected.status === 'Returned for Reassessment' && selected.returnReason" x-cloak>
                        <i data-lucide="rotate-ccw" class="w-4 h-4 shrink-0 mt-0.5 text-orange-500"></i>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-orange-700">Returned for Reassessment</p>
                            <p class="text-xs mt-0.5 text-orange-600" x-text="selected.returnReason"></p>
                        </div>
                    </div>

                    {{-- Rejected / Cancelled banner. --}}
                    <div
                        class="rounded-xl border px-3.5 py-3 mt-3 flex items-start gap-2.5"
                        :class="selected.status === 'Rejected' ? 'bg-rose-50 border-rose-100' : 'bg-slate-100 border-slate-200'"
                        x-show="['Rejected', 'Cancelled'].includes(selected.status) && (selected.remarks || selected.cancelReason)"
                    >
                        <i :data-lucide="selected.status === 'Rejected' ? 'circle-x' : 'ban'" class="w-4 h-4 shrink-0 mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-500' : 'text-slate-500'"></i>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold" :class="selected.status === 'Rejected' ? 'text-rose-700' : 'text-slate-700'" x-text="selected.status"></p>
                            <p class="text-xs mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-600' : 'text-slate-600'" x-text="selected.status === 'Rejected' ? selected.remarks : selected.cancelReason"></p>
                        </div>
                    </div>





                    {{-- Case history: every transition the server recorded, newest first. --}}
                    <div class="rounded-xl border border-slate-200 p-4 mt-3" x-show="(selected.history || []).length" x-cloak>
                        <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide flex items-center gap-1.5 mb-2"><i data-lucide="history" class="w-3.5 h-3.5"></i> Case History</p>
                        <ul class="space-y-2">
                            <template x-for="(h, hi) in (selected.history || []).slice().reverse()" :key="hi">
                                <li class="text-xs border-l-2 border-slate-200 pl-2.5">
                                    <p class="font-medium text-navy-900" x-text="h.stage + (h.note ? ' — ' + h.note : '')"></p>
                                    <p class="text-[11px] text-slate-400" x-text="h.date + (h.by ? ' · ' + h.by : '')"></p>
                                </li>
                            </template>
                        </ul>
                    </div>




                    <div class="mt-3" x-show="canReject">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Rejection Reason <span class="text-slate-400 font-normal">(required for Reject — this is what the citizen will see)</span></label>
                        <p class="text-[11px] text-slate-400 mb-1.5">This is the final message the citizen will see. If you've already flagged specific requirements below, pull those reasons in instead of retyping them.</p>
                        <div class="flex items-center gap-2 mb-1.5">
                            <select
                                @change="insertStandardReason($event.target.value); $event.target.value = ''"
                                class="flex-1 min-w-0 text-xs rounded-xl border border-slate-200 bg-white py-2 px-3 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                            >
                                <option value="">Insert a standard reason…</option>
                                <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                            </select>
                            <button
                                type="button" x-show="flaggedRequirements.length" x-cloak
                                @click="useRequirementReasons()"
                                class="shrink-0 text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 hover:border-brand-300 rounded-xl px-3 py-2 transition-colors whitespace-nowrap"
                            >Use requirement reasons</button>
                        </div>
                        <textarea aria-label="e.g. Does not meet the program's eligibility criteria"
                            x-model="rejectRemarks" rows="2" placeholder="e.g. Does not meet the program's eligibility criteria."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-3.5 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                        ></textarea>
                    </div>
                    </div>

                    {{-- RIGHT — System Advice, isolated (never scrolls). Mirrors
                         the Esperanza Mobile app's own VerificationStatusPanel
                         pattern: icon + title + plain-language explanation,
                         so the admin always has a read on what to do next
                         regardless of how far they've scrolled the middle
                         column. --}}
                    <div class="sm:w-72 shrink-0">
                        <div
                            class="rounded-2xl border p-4"
                            :class="{
                                'bg-emerald-50/60 border-emerald-200': processAdvice.tone === 'emerald',
                                'bg-rose-50/60 border-rose-200': processAdvice.tone === 'rose',
                                'bg-amber-50/60 border-amber-200': processAdvice.tone === 'amber',
                                'bg-orange-50/60 border-orange-200': processAdvice.tone === 'orange',
                                'bg-indigo-50/60 border-indigo-200': processAdvice.tone === 'indigo',
                                'bg-purple-50/60 border-purple-200': processAdvice.tone === 'brand',
                                'bg-slate-50/60 border-slate-200': processAdvice.tone === 'slate',
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': processAdvice.tone === 'emerald',
                                        'bg-rose-100 text-rose-700': processAdvice.tone === 'rose',
                                        'bg-amber-100 text-amber-700': processAdvice.tone === 'amber',
                                        'bg-orange-100 text-orange-700': processAdvice.tone === 'orange',
                                        'bg-indigo-100 text-indigo-700': processAdvice.tone === 'indigo',
                                        'bg-purple-100 text-purple-700': processAdvice.tone === 'brand',
                                        'bg-slate-100 text-slate-600': processAdvice.tone === 'slate',
                                    }"
                                ><i :data-lucide="processAdvice.icon" class="w-4.5 h-4.5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide"
                                        :class="{
                                            'text-emerald-700': processAdvice.tone === 'emerald',
                                            'text-rose-700': processAdvice.tone === 'rose',
                                            'text-amber-700': processAdvice.tone === 'amber',
                                            'text-orange-700': processAdvice.tone === 'orange',
                                            'text-indigo-700': processAdvice.tone === 'indigo',
                                            'text-purple-700': processAdvice.tone === 'brand',
                                            'text-slate-600': processAdvice.tone === 'slate',
                                        }"
                                    >System Advice — <span x-text="processAdvice.title"></span></p>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed" x-text="processAdvice.message"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Proof of Release — same feature as Dokyu's own
                             process modal (see admin/document-requests.blade.php's
                             identical block for the full rationale), regated
                             onto Tulong's own Ready for Release stage and
                             relabeled for assistance rather than a document. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i> Proof of Release
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.release_evidence">Photo evidence that the citizen actually received this assistance — required before confirming release.</p>

                            <template x-if="selected.release_evidence">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 aspect-[4/3]">
                                        <img :src="selected.release_evidence" class="w-full h-full object-cover" alt="Proof of release">
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[11px] text-emerald-600 font-medium inline-flex items-center gap-1"><i data-lucide="circle-check" class="w-3 h-3"></i> Captured <span x-text="selected.release_evidence_at"></span></span>
                                        <button type="button" @click="clearReleaseEvidence()" class="text-[11px] text-slate-400 hover:text-rose-500" x-show="selected.status === 'Mark to Release'">Retake</button>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-2 mt-2.5" x-show="!selected.release_evidence">
                                <button type="button" @click="captureReleaseEvidence('camera')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-purple-400 hover:bg-purple-50/40 py-3 text-slate-500 hover:text-purple-600 transition-colors">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Take Photo</span>
                                </button>
                                <button type="button" @click="captureReleaseEvidence('upload')" class="flex flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 hover:border-purple-400 hover:bg-purple-50/40 py-3 text-slate-500 hover:text-purple-600 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Upload Photo</span>
                                </button>
                            </div>
                        </div>

                        {{-- Reference Form — the citizen's own submitted
                             application form (not a Dokyu-style prepared
                             document), gated the same as Proof of Release
                             above. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Reference Form
                            </p>
                            <template x-if="selected.reference_pdf">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50" style="height: 220px;">
                                        <iframe :src="selected.reference_pdf" class="w-full h-full" title="Reference form preview"></iframe>
                                    </div>
                                    <a :href="selected.reference_pdf" target="_blank" rel="noopener" class="text-[11px] text-purple-600 hover:text-purple-700 font-medium inline-flex items-center gap-1 mt-1.5">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> Open in new tab
                                    </a>
                                </div>
                            </template>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed" x-show="!selected.reference_pdf">No reference form on file for this service.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button on any
                 submitted requirement above. Fixed to the viewport (not nested
                 inside the scrollable middle column) so it sits above the
                 whole Review modal regardless of scroll position. --}}
            <div
                x-show="previewDoc"
                x-cloak
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-[150ms] ease-exit"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="position: fixed; inset: 0; z-index: 95;"
                class="bg-navy-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="previewDoc = null"
                @keydown.escape.window="previewDoc = null"
            >
                <div
                    class="w-full max-w-sm bg-white rounded-2xl shadow-float overflow-hidden"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-slate-100">
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                            :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500'"
                        ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-3.5 h-3.5"></i></span>
                        <h3 class="text-sm font-semibold text-navy-900 truncate flex-1" x-text="previewDoc?.name"></h3>
                        <button type="button" @click="previewDoc = null" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors shrink-0" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        {{-- Real placeholder scan (only wired up for the handful of
                             residents with an actual supplied ID image on file, e.g.
                             Cristy — see 'preview_asset' on her requirement rows)
                             renders as an actual image; every other mock requirement
                             still falls back to the plain icon + filename, since no
                             real file exists behind those. --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/2] overflow-hidden mb-3" x-show="previewDoc?.preview_asset">
                            <img :src="previewDoc?.preview_asset" :alt="previewDoc?.name" class="w-full h-full object-cover">
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/4] flex flex-col items-center justify-center gap-2.5 mb-3" x-show="!previewDoc?.preview_asset">
                            <span
                                class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-100 text-rose-500' : 'bg-blue-100 text-blue-500'"
                            ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-7 h-7"></i></span>
                            <p class="text-xs font-medium text-slate-500 px-4 text-center truncate max-w-full" x-text="previewDoc?.file"></p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-400 shrink-0">Requirement</dt>
                                <dd class="font-medium text-navy-900 text-right truncate" x-text="previewDoc?.name"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-400 shrink-0">Status</dt>
                                <dd class="font-medium text-navy-900" x-text="previewDoc?.status === 'Approved' ? 'Verified' : previewDoc?.status"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-3" x-show="previewDoc?.remarks">
                                <dt class="text-slate-400 shrink-0">Remarks</dt>
                                <dd class="font-medium text-right text-slate-600" x-text="previewDoc?.remarks"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <p class="text-xs text-slate-400 flex-1 flex items-center gap-1.5" x-show="selected && terminalStatuses.includes(selected.status)" x-cloak>
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>This case is finalized — no further status changes.
                </p>
                <template x-if="selected && !terminalStatuses.includes(selected.status)">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <template x-if="canReject">
                            <x-ui.button variant="danger" size="sm" x-bind:disabled="processing || !rejectRemarks.trim()" @click="rejectCase()">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                    <span x-text="processing ? 'Rejecting…' : 'Reject Case'"></span>
                                </span>
                            </x-ui.button>
                        </template>
                        {{-- Primary action — one button, dynamic per stage
                             (Milestone 18's exact label table lives in
                             stageActionLabels; primaryAction() dispatches
                             to the matching method). --}}
                        <x-ui.button
                            size="sm"
                            x-show="nextStageLabel"
                            x-bind:disabled="processing || (selected.status === 'Mark to Release' && !selected.release_evidence)"
                            @click="primaryAction()"
                            x-bind:title="selected.status === 'Mark to Release' && !selected.release_evidence ? 'Capture or upload Proof of Release first' : null"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="!processing"></i>
                                <span x-text="processing ? 'Saving…' : nextStageLabel"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </template>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
