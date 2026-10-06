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
            // Only requests still open (not released, completed, rejected or cancelled).
            openOnly: false,
            showAdvancedFilters: false,
            open: false,
            selected: null,
            processing: false,
            // Reject, Cancel and Return for Reassessment each need a typed
            // reason (the contract's requires_reason). One form serves all
            // three: sideForm names which, sideReason holds the text.
            sideForm: null,
            sideReason: '',
            // The stage forms. Assessment records the findings and a
            // recommended amount; For Approval sets the approved amount;
            // Ready for Release records who received it, the actual amount, a
            // reference and the proof.
            assessNotes: '',
            assessAmount: '',
            approveAmount: '',
            releaseTo: '',
            releaseAmount: '',
            releaseReference: '',
            releaseNote: '',
            previewDoc: null,
            // Tulong runs its own case lifecycle on the server (backend finding
            // B-044, closed 2026-10-04): the eleven statuses of
            // config/esperanza_tulong_lifecycle.php, each move a real endpoint.
            // The detail's case.next lists the moves the server will accept
            // from where the case stands, so the buttons follow it.
            requests: [],
            requestsLoading: true,
            requestsFailed: false,
            detailLoading: false,
            citizenAvatars: {},
            actionStatuses: @js($actionStatuses),
            allTulongStatuses: @js($allTulongStatuses),
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
                if (this.selected?.case?.processing_kind) return this.selected.case.processing_kind;
                const name = this.catalogNameByKey[this.selected?.service_key] || this.selected?.type;
                return this.tulongTypesByName[name]?.processingKind || 'general';
            },
            // Goods and registration programs give a package or an enrollment,
            // not a sum, so they ask for no peso amount.
            get showsAmount() {
                return !['goods', 'registration'].includes(this.processingKind);
            },
            peso(v) {
                if (v === null || v === undefined || v === '') return '—';
                const n = Number(v);
                return isNaN(n) ? String(v) : '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                if (r) this.sideReason = r.message;
            },
            get flaggedRequirements() {
                return (this.selected?.requirements || []).filter(r => r.reject_reason || r.remarks);
            },
            useRequirementReasons() {
                if (!this.flaggedRequirements.length) return;
                this.sideReason = this.flaggedRequirements
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
                // From the dashboard's Open Requests by Barangay card.
                if (params.get('barangay')) { this.barangayFilter = params.get('barangay'); this.tab = 'all'; }
                if (params.get('open') === '1') { this.openOnly = true; this.tab = 'all'; }
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
            // Assign to me (and its undo): POST/DELETE .../assign, then the
            // server's answer. It used to change only this screen, so a reload
            // showed the case unassigned again.
            async setAssignment(assign) {
                if (!this.selected || this.processing) return;
                this.processing = true;
                try {
                    const url = this.apiBase(this.selected.ref) + '/assign';
                    const res = assign ? await $api.post(url) : await $api.delete(url);
                    const who = res.data?.assigned ?? null;
                    this.selected.assigned = who;
                    const row = this.requests.find((x) => x.ref === this.selected.ref);
                    if (row) row.assigned = who;
                    this.$dispatch('toast', { message: assign ? 'Assigned to you.' : 'No longer assigned to you.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not change who is assigned.'), variant: 'error' });
                } finally {
                    this.processing = false;
                }
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
                    assigned: row.assigned || null,
                    purpose: '',
                    requirements: null,
                    remarks: null,
                    history: [],
                    case: {},
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
                    // Not the file's URL: the API sends it as a download, which an <img> will not show. Photos open in the viewer (window.imageBlobUrl).
                    preview_asset: null,
                    file_notice: window.fileNotice(q.file),
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
                r.permitted = d.permitted || null;
                // Reject and Cancel both record their reason as the decision
                // remarks; the return reason is the case's own.
                r.remarks = d.decision_remarks || null;
                r.cancelReason = d.status === 'Cancelled' ? (d.decision_remarks || null) : null;
                r.case = d.case || {};
                r.returnReason = r.case.return_reason || null;
                r.form_data = d.form_data || {};
                r.purpose = (d.form_data && d.form_data.purpose) ? [].concat(d.form_data.purpose).join(', ') : '';
                r.form_steps = this.formStepsFor(r.type, d.form_data, r.service_key);
                r.requirements = (d.requirements || []).map((q) => this.fromApiRequirement(q));
                r.assigned = 'assigned' in d ? d.assigned : r.assigned;
                r.payment = d.payment || null;
                r.history = (d.history || []).map((h) => ({ stage: h.to, from: h.from, date: this.formatDay(h.at), by: h.actor_name || '—', note: window.historyNote(h.trigger) }));
                const proof = d.proof_of_release || {};
                r.proofNote = proof.note || null;
                if (proof.captured) {
                    r.release_evidence = proof.file?.url || r.release_evidence || null;
                    r.release_evidence_at = proof.note || 'on file';
                }
            },
            resetCaseForms() {
                this.sideForm = null;
                this.sideReason = '';
                this.assessNotes = '';
                this.assessAmount = '';
                this.approveAmount = '';
                this.releaseTo = '';
                this.releaseAmount = '';
                this.releaseReference = '';
                this.releaseNote = '';
            },
            // Start each form from what the case already holds: the approval
            // from the recommendation, the release from the approval and the
            // applicant's name. Only empty fields are filled, so a refresh
            // after a document decision never overwrites what was typed.
            prefillCaseForms(r) {
                const c = r.case || {};
                if (this.approveAmount === '' && c.recommended_amount !== null && c.recommended_amount !== undefined) this.approveAmount = String(Number(c.recommended_amount));
                if (this.releaseTo === '' && r.status === 'Ready for Release' && r.citizen && r.citizen !== '—') this.releaseTo = r.citizen;
                if (this.releaseAmount === '' && c.approved_amount !== null && c.approved_amount !== undefined) this.releaseAmount = String(Number(c.approved_amount));
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
                if (this.tab === 'release') return r.status === 'Ready for Release';
                if (this.tab === 'done') return this.terminalStatuses.includes(r.status);
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
                    (!this.openOnly || !this.terminalStatuses.includes(r.status)) &&
                    (q === '' || r.ref.toLowerCase().includes(q) || r.citizen.toLowerCase().includes(q))
                );
            },
            clearAdvancedFilters() { this.categoryFilter = ''; this.officeFilter = ''; this.levelFilter = ''; this.assignedFilter = ''; this.barangayFilter = ''; this.statusFilter = ''; },
            // The main line (statusOrder below). A side state sits on the step
            // it branches from; a closed case stays on the step it closed at.
            stepIndex(status) {
                const side = { 'Needs Requirements': 'Initial Screening', 'Returned for Reassessment': 'For Approval' };
                const at = this.statusOrder.indexOf(side[status] || status);
                if (at >= 0) return at;
                if (status === 'Rejected' || status === 'Cancelled') {
                    const last = (this.selected?.history || []).filter(h => h.stage === status).pop();
                    const from = last?.from ? (side[last.from] || last.from) : null;
                    return Math.max(0, this.statusOrder.indexOf(from));
                }
                return 0;
            },
            // Opening is the server's own trigger: GET on a Submitted case
            // moves it to Initial Screening (RequestLifecycle::open).
            async openRequest(r) {
                this.selected = r;
                this.resetCaseForms();
                this.previewDoc = null;
                this.open = true;
                this.detailLoading = true;
                try {
                    await this.refreshSelected();
                    this.prefillCaseForms(r);
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not open ' + r.ref + '.'), variant: 'error' });
                } finally {
                    this.detailLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            // One case move: POST the action, take the server's detail as the
            // new state, and start the next stage's form from it.
            async moveCase(action, body, okMessage, variant) {
                if (this.processing) return false;
                const r = this.selected;
                this.processing = true;
                try {
                    const res = await $api.post(this.apiBase(r.ref) + '/' + action, body);
                    this.applyDetail(r, res.data);
                    this.resetCaseForms();
                    this.prefillCaseForms(r);
                    this.$dispatch('toast', { message: okMessage || (r.ref + ' moved to ' + r.status + '.'), variant: variant || 'success' });
                    return true;
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not update ' + r.ref + '.'), variant: 'error' });
                    return false;
                } finally {
                    this.processing = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            canMove(to) {
                return !this.detailLoading && (this.selected?.case?.next || []).includes(to);
            },
            // The permission the primary button needs at this stage (the case
            // actions' own gates on the server).
            get primaryPermission() {
                const st = this.selected?.status;
                if (['Initial Screening', 'Returned for Reassessment', 'Assessment'].includes(st)) return 'review';
                if (st === 'For Approval') return 'approve';
                if (['Assistance Processing', 'Ready for Release'].includes(st)) return 'release';
                return null;
            },
            // What this officer may do here, as the server says (detail.permitted).
            // The staff site showed Reject to roles that could not reject, and
            // Department Staff every Tulong decision (QA run 2026-10-05). Unknown
            // means shown: the server still decides.
            may(action) {
                return !this.selected?.permitted || !!this.selected.permitted[action];
            },
            // Nothing uploaded means nothing to accept, unless the document is
            // optional ('(if applicable)'), or is a step the office does itself
            // (an interview, a case study, 'None'): the server's rule.
            mayAccept(req) {
                return !!req.file || /if applicable|if available|if any|required if|\bonly\s*$|interview|case study|case assessment|details of the record being requested|thumbmark|to be taken at|^\s*none\b/i.test(req.name || '');
            },

            sideForms: {
                reject: { title: 'Reject Case', confirm: 'Confirm Rejection', hint: 'The resident reads this reason. Reject only when the case cannot qualify.' },
                cancel: { title: 'Cancel Case', confirm: 'Confirm Cancellation', hint: 'For a case the resident or the office withdrew before an approval decision. The resident reads this reason.' },
                return: { title: 'Return for Reassessment', confirm: 'Return to Assessment', hint: 'Why the recommendation needs re-verification. Internal: the resident is not notified.' },
            },
            openSideForm(kind) {
                this.sideForm = kind;
                this.sideReason = '';
                this.$nextTick(() => { this.$refs.sideFormPanel?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); window.renderIcons?.(); });
            },
            async confirmSideForm() {
                const reason = this.sideReason.trim();
                if (!reason || !this.sideForm) return;
                const ref = this.selected.ref;
                if (this.sideForm === 'reject') {
                    await this.moveCase('reject', { decision_remarks: reason }, 'Case ' + ref + ' rejected.', 'error');
                } else if (this.sideForm === 'cancel') {
                    await this.moveCase('cancel', { reason }, 'Case ' + ref + ' cancelled.', 'warning');
                } else if (this.sideForm === 'return') {
                    await this.moveCase('return-for-reassessment', { reason }, ref + ' returned for reassessment.', 'warning');
                }
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
                if (st === 'Cancelled') return { icon: 'ban', tone: 'slate', title: 'Cancelled', message: this.selected.cancelReason || 'This case was cancelled.' };
                if (st === 'Completed') return { icon: 'circle-check', tone: 'emerald', title: 'Completed', message: 'The assistance was released and the release is on record. No further action is needed.' };
                if (st === 'Ready for Release') {
                    return this.hasReleaseProof
                        ? { icon: 'circle-check', tone: 'emerald', title: 'Ready to Confirm', message: 'Proof of Release is in hand. Record who received it, the amount and the reference, then confirm.' }
                        : { icon: 'truck', tone: 'amber', title: 'Release Assistance', message: 'Capture Proof of Release (a photo, or a note on the acknowledgement), then confirm once the beneficiary has received it.' };
                }
                if (st === 'Assistance Processing') {
                    const prep = { cash: 'Prepare the payout or voucher for the approved amount', goods: 'Prepare the approved package', medical: 'Prepare the guarantee letter or the payment to the provider', registration: 'Confirm the enrollment' }[this.processingKind] || 'Prepare the approved assistance';
                    return { icon: 'package', tone: 'indigo', title: 'Prepare Assistance', message: prep + ', then mark the case ready for release.' };
                }
                if (st === 'For Approval') return { icon: 'stamp', tone: 'amber', title: 'Awaiting Approval', message: 'Approve the assistance (the approved amount may differ from the recommendation), return it for reassessment, or reject the case.' };
                if (st === 'Returned for Reassessment') return { icon: 'rotate-ccw', tone: 'orange', title: 'Reassess', message: 'The approver sent this back' + (this.selected.returnReason ? ': ' + this.selected.returnReason : '.') + ' Resume the assessment to revise it.' };
                if (st === 'Assessment') return { icon: 'clipboard-pen', tone: 'indigo', title: 'Assess Eligibility', message: 'Record the eligibility findings' + (this.showsAmount ? ' and a recommended amount' : '') + ', then submit for approval.' };
                const reqs = this.selected.requirements || [];
                const flagged = reqs.filter(r => r.status === 'Flagged for Replacement').length;
                const pending = reqs.filter(r => r.status === 'Submitted' || r.status === 'Resubmitted').length;
                const rejected = reqs.filter(r => r.status === 'Rejected').length;
                if (st === 'Needs Requirements') return { icon: 'clock', tone: 'amber', title: 'Awaiting Beneficiary', message: (flagged ? flagged + ' document(s) flagged for replacement' : 'Requirements requested') + ' — waiting for the resident. The case returns to screening when they resubmit.' };
                if (pending) return { icon: 'search-check', tone: 'indigo', title: 'Screen Requirements', message: pending + ' of ' + reqs.length + ' requirement(s) still need review. Approve each, or flag what must be replaced (that asks the resident for it).' };
                if (st === 'Initial Screening' && rejected) return { icon: 'clock', tone: 'amber', title: 'Waiting on a Replacement', message: rejected + ' document(s) rejected. The resident was told why and can upload a replacement; review it when it arrives. Assessment opens once every document is accepted.' };
                if (st === 'Initial Screening' && this.screeningDone) return { icon: 'circle-check', tone: 'emerald', title: 'Screening Done', message: 'Every requirement is accepted. Proceed to the eligibility assessment.' };
                return { icon: 'clipboard-list', tone: 'brand', title: 'Review Case', message: 'Review the applicant and the submitted requirements.' };
            },
            // Proceed to Assessment only once every requirement is accepted:
            // a pending, replaced, flagged or rejected one holds the case. The
            // server refuses it the same way (SCREENING_NOT_FINISHED), so this
            // button is a convenience, not the rule (Esperanza QA, 2026-10-07).
            get screeningDone() {
                return (this.selected?.requirements || []).every(q => q.status === 'Approved');
            },
            get hasReleaseProof() {
                return !!(this.selected && (this.selected._proofFile || this.releaseNote.trim() || this.selected.release_evidence));
            },
            // Tulong's main line, as config/esperanza_tulong_lifecycle.php
            // declares it (tests/Feature/TulongLifecycleTest.php pins the two).
            statusOrder: ['Submitted', 'Initial Screening', 'Assessment', 'For Approval', 'Assistance Processing', 'Ready for Release', 'Completed'],
            terminalStatuses: ['Completed', 'Rejected', 'Cancelled'],
            stageActionLabels: {
                'Initial Screening': 'Proceed to Assessment',
                'Returned for Reassessment': 'Resume Assessment',
                'Assessment': 'Submit for Approval',
                'For Approval': 'Approve Assistance',
                'Assistance Processing': 'Mark Ready for Release',
                'Ready for Release': 'Confirm Release',
            },
            get nextStageLabel() {
                if (!this.selected || this.detailLoading) return '';
                return this.stageActionLabels[this.selected.status] ?? '';
            },
            // Why the primary button is not available yet, or '' when it is.
            get primaryBlocked() {
                const st = this.selected?.status;
                if (st === 'Initial Screening' && !this.screeningDone) return 'Accept every requirement first. A rejected one must be replaced and accepted.';
                if (st === 'Assessment' && !this.assessNotes.trim()) return 'Write the eligibility findings first.';
                if (st === 'Ready for Release' && !this.releaseTo.trim()) return 'Record who received the assistance.';
                if (st === 'Ready for Release' && !this.hasReleaseProof) return 'Capture Proof of Release first.';
                return '';
            },
            // A real photo, held here and sent with the release itself
            // (POST .../complete, proof_file): the server refuses to complete
            // a case without proof.
            // Take Photo opens the webcam on a computer (window.takePhoto); a
            // phone, or a computer with no camera, uses the file input, whose
            // capture hint a phone honours.
            async captureReleaseEvidence(source) {
                if (source === 'camera') {
                    const shot = await window.takePhoto();
                    if (shot === null) return;
                    if (shot) { this.useReleaseEvidence(shot); return; }
                }
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                if (source === 'camera') input.setAttribute('capture', 'environment');
                input.onchange = () => this.useReleaseEvidence(input.files && input.files[0]);
                input.click();
            },
            useReleaseEvidence(file) {
                if (!file || !this.selected) return;
                if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                this.selected._proofFile = file;
                this.selected._proofPreview = URL.createObjectURL(file);
                this.selected.release_evidence = this.selected._proofPreview;
                this.selected.release_evidence_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                this.$nextTick(() => window.renderIcons?.());
            },
            clearReleaseEvidence() {
                if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                this.selected._proofFile = null;
                this.selected._proofPreview = null;
                this.selected.release_evidence = null;
                this.selected.release_evidence_at = null;
            },
            // The footer's one primary button, a real endpoint per stage:
            //   Initial Screening / Returned for Reassessment -> begin-assessment
            //   Assessment            -> recommend (findings, recommended amount)
            //   For Approval          -> approve-assistance (approved amount)
            //   Assistance Processing -> ready-for-release
            //   Ready for Release     -> complete (recipient, amount, reference, proof)
            async primaryAction() {
                if (this.processing || this.primaryBlocked) return;
                const r = this.selected;
                const amount = (v) => (v === '' || v === null || v === undefined) ? null : Number(v);
                if (['Initial Screening', 'Returned for Reassessment'].includes(r.status)) {
                    await this.moveCase('begin-assessment', {});
                } else if (r.status === 'Assessment') {
                    await this.moveCase('recommend', { assessment_notes: this.assessNotes.trim(), recommended_amount: this.showsAmount ? amount(this.assessAmount) : null }, r.ref + ' submitted for approval.');
                } else if (r.status === 'For Approval') {
                    await this.moveCase('approve-assistance', { approved_amount: this.showsAmount ? amount(this.approveAmount) : null }, 'Assistance approved for ' + r.ref + '.');
                } else if (r.status === 'Assistance Processing') {
                    await this.moveCase('ready-for-release', {});
                } else if (r.status === 'Ready for Release') {
                    const form = new FormData();
                    form.append('released_to', this.releaseTo.trim());
                    if (this.showsAmount && this.releaseAmount !== '') form.append('released_amount', this.releaseAmount);
                    if (this.releaseReference.trim()) form.append('release_reference', this.releaseReference.trim());
                    if (r._proofFile) form.append('proof_file', r._proofFile);
                    if (this.releaseNote.trim()) form.append('proof_note', this.releaseNote.trim());
                    await this.moveCase('complete', form, r.ref + ' completed. The release is on record.');
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
            <x-ui.stat-card @click="tab = 'release'" class="cursor-pointer" label="Ready for Release" value="0" valueBind="requests.filter(r => r.status === 'Ready for Release').length" icon="package-check" color="green" :delay="80" />
            {{-- These two were hard-coded peso figures ("₱1.8M of ₱2.5M") shown as
                 if they were the municipality's budget. No budget or
                 disbursement amount is recorded anywhere, so they count what is
                 real instead. --}}
            <x-ui.stat-card @click="tab = 'done'" class="cursor-pointer" label="Completed" value="0" valueBind="requests.filter(r => r.status === 'Completed').length" icon="hand-coins" color="gold" sublabel="Assistance received" :delay="120" />
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="All Cases" value="0" valueBind="requests.length" icon="wallet" color="brand" sublabel="Every status" :delay="160" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
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
                <p class="text-[11px] text-slate-500 shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' cases'"></p>
                <p x-show="openOnly || barangayFilter" x-cloak class="w-full text-[11px] text-brand-700 bg-brand-50 rounded-lg px-3 py-1.5 flex flex-wrap items-center gap-x-2">
                    <span x-text="'Showing ' + (openOnly ? 'open cases' : 'cases') + (barangayFilter ? ' in Brgy. ' + barangayFilter : '') + '.'"></span>
                    <button type="button" class="font-medium underline" @click="openOnly = false; barangayFilter = ''">Show all</button>
                </p>
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
                    <option x-show="$store.session.account" :value="$store.session.account?.name || ''">Assigned to me</option>
                </select>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-slate-500 hover:text-slate-600 transition-colors" x-show="statusFilter || officeFilter || levelFilter || assignedFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden 2xl:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden 2xl:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden 2xl:table-cell">Assigned</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden xl:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
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
                            <p class="text-[11px] text-slate-500 mt-0.5" x-text="r.category"></p>
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
                        <p class="text-xs text-slate-500 mb-3 font-mono" x-text="selected.ref"></p>
                        <div class="flex flex-col">
                            @foreach(config('esperanza_tulong_lifecycle.main_sequence') as $i => $step)
                                <div class="flex items-start gap-3">
                                    <div class="flex flex-col items-center shrink-0">
                                        <span
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold transition-all duration-[600ms] ease-standard shrink-0"
                                            :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'bg-purple-600 text-white shadow-card ring-4 ring-purple-100' : 'bg-slate-100 text-slate-500')"
                                        >
                                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="stepIndex(selected.status) > {{ $i }}" x-cloak></i>
                                            <span x-show="stepIndex(selected.status) <= {{ $i }}">{{ $i + 1 }}</span>
                                        </span>
                                        @if(!$loop->last)
                                            <div class="w-0.5 flex-1 my-1 rounded-full transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-100'" style="min-height: 1.25rem"></div>
                                        @endif
                                    </div>
                                    <div class="pt-1.5 pb-2 min-w-0">
                                        <span class="block text-[11px] leading-tight transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) === {{ $i }} ? 'text-navy-900 font-semibold' : 'text-slate-500'">{{ $step }}</span>
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
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="hand-heart" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Assistance Type</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Handling Office</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Assigned Social Worker</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Submitted</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.submitted"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-2 mt-3"
                        x-show="!selected.assigned && (!$store.session.account || ['department_head', 'municipal_admin', 'super_admin'].includes($store.session.account?.roleKey))"
                    >
                        <p class="text-xs text-slate-500">This case hasn't been routed to a processor yet.</p>
                        <x-ui.button
                            variant="secondary" size="sm" icon="user-plus"
                            x-bind:disabled="processing"
                            @click="setAssignment(true)"
                        >Assign to me</x-ui.button>
                    </div>
                    <div
                        class="flex items-center justify-between gap-2 mt-3"
                        x-show="selected.assigned && selected.assigned === $store.session.account?.name"
                        x-cloak
                    >
                        <p class="text-xs text-slate-500">You are handling this case.</p>
                        <x-ui.button variant="secondary" size="sm" icon="user-minus" x-bind:disabled="processing" @click="setAssignment(false)">Unassign me</x-ui.button>
                    </div>

                    <x-admin.request-form-review />

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Submitted Documents</p>
                            <p class="text-[11px] text-slate-500" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
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
                                                <p class="text-[11px] text-slate-500 truncate" x-text="req.file || 'Not yet submitted'"></p>
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
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': (req.status === 'Submitted' && req.file) || req.status === 'Flagged for Replacement', 'bg-slate-100 text-slate-600 ring-slate-200': req.status === 'Submitted' && !req.file, 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : (req.status === 'Submitted' && !req.file ? 'Not submitted' : req.status)"></span></span>
                                        </div>
                                    </div>

                                    <div x-show="['Rejected', 'Flagged for Replacement'].includes(req.status) || (req.status === 'Resubmitted' && (req.versions || []).length)" class="mt-2 pt-2 border-t text-[11px]" :class="req.status === 'Rejected' ? 'border-rose-100/70' : req.status === 'Flagged for Replacement' ? 'border-amber-100/70' : 'border-sky-100/70'">
                                        <p class="font-semibold" x-show="req.reject_reason" :class="req.status === 'Rejected' ? 'text-rose-700' : 'text-amber-700'" x-text="req.reject_reason"></p>
                                        <p class="mt-0.5" :class="req.status === 'Rejected' ? 'text-rose-600' : 'text-amber-600'" x-show="req.remarks" x-text="req.remarks"></p>
                                        <p class="font-medium text-sky-700 flex items-center gap-1" x-show="req.status === 'Resubmitted'"><i data-lucide="refresh-ccw" class="w-3 h-3"></i> Citizen replaced this file — compare with the flagged version below.</p>
                                        <button type="button" x-show="(req.versions || []).length" @click="req._showVersions = !req._showVersions" class="mt-1.5 text-slate-500 hover:text-slate-600 transition-colors">
                                            <span x-text="(req._showVersions ? 'Hide' : 'Show') + ' version history (' + req.versions.length + ')'"></span>
                                        </button>
                                        <div x-show="req._showVersions" x-cloak class="mt-1.5 space-y-1 pl-2 border-l-2 border-slate-100">
                                            <template x-for="(v, vi) in (req.versions || [])" :key="vi">
                                                <p class="text-slate-500">Version <span x-text="vi + 1"></span> — <span x-text="v.status"></span> · <span x-text="v.date"></span><span x-show="v.reject_reason"> · <span x-text="v.reject_reason"></span></span></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Three decision outcomes at the requirement level: Approve /
                                         Flag for Replacement / Reject. No separate "needs verification"
                                         action — reviewing a requirement here already IS the
                                         verification step. --}}
                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="(req.status === 'Submitted' || req.status === 'Resubmitted') && !req._decidingOutcome && may('review')">
                                        <button type="button" x-show="mayAccept(req)" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Approve</button>
                                        <span x-show="!mayAccept(req)" class="text-[11px] text-slate-500">Nothing uploaded yet</span>
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
                                            <button type="button" @click="req._decidingOutcome = null" class="text-[11px] font-medium text-slate-500 hover:text-slate-600">Cancel</button>
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
                            <template x-for="item in (selected.requirements || []).filter(q => q.status === 'Flagged for Replacement')" :key="item.key">
                                <li class="text-xs text-amber-700 flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-amber-500 shrink-0"></span><span x-text="item.name + (item.reject_reason ? ' — ' + item.reject_reason : '')"></span></li>
                            </template>
                        </ul>
                        <p class="text-[11px] text-amber-700 mt-2">The resident was notified. Their resubmission returns the case to Initial Screening.</p>
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





                    {{-- Eligibility assessment (Assessment): the findings and the
                         recommendation the approver decides on. --}}
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-3.5 mt-3" x-show="selected.status === 'Assessment' && !detailLoading" x-cloak>
                        <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="clipboard-pen" class="w-3.5 h-3.5"></i> Eligibility Assessment</p>
                        <p class="text-[11px] text-slate-500 mt-1">What you found and what you recommend. The approver reads this; the resident does not.</p>
                        <label for="tulong-assess-notes" class="block text-[11px] font-medium text-slate-600 mt-2.5 mb-1">Findings <span class="text-rose-600">*</span></label>
                        <textarea id="tulong-assess-notes" x-model="assessNotes" rows="3" maxlength="4000" placeholder="e.g. Enrolled for SY 2026-2027; household income is below the program threshold."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                        <div x-show="showsAmount" class="mt-2">
                            <label for="tulong-assess-amount" class="block text-[11px] font-medium text-slate-600 mb-1">Recommended amount (₱)</label>
                            <input id="tulong-assess-amount" type="number" min="0" step="0.01" inputmode="decimal" x-model="assessAmount" placeholder="0.00"
                                class="w-full sm:w-48 rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 tabular-nums focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                    </div>

                    {{-- Approval (For Approval): the approved amount, kept apart
                         from the recommendation it may change. --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-3.5 mt-3" x-show="selected.status === 'For Approval' && !detailLoading && showsAmount" x-cloak>
                        <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="stamp" class="w-3.5 h-3.5"></i> Approval</p>
                        <p class="text-[11px] text-slate-500 mt-1">Approve at the recommended amount or set another. The recommendation stays on record either way.</p>
                        <label for="tulong-approve-amount" class="block text-[11px] font-medium text-slate-600 mt-2.5 mb-1">Approved amount (₱)</label>
                        <input id="tulong-approve-amount" type="number" min="0" step="0.01" inputmode="decimal" x-model="approveAmount" placeholder="0.00"
                            class="w-full sm:w-48 rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 tabular-nums focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>

                    {{-- Release (Ready for Release): Completed records who released
                         it (you), to whom, the actual amount and a reference, in one
                         step, and waits on proof of release. --}}
                    <div class="rounded-xl border border-teal-200 bg-teal-50/30 p-3.5 mt-3" x-show="selected.status === 'Ready for Release' && !detailLoading" x-cloak>
                        <p class="text-xs font-semibold text-teal-800 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="hand-coins" class="w-3.5 h-3.5"></i> Release</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2.5">
                            <div>
                                <label for="tulong-release-to" class="block text-[11px] font-medium text-slate-600 mb-1">Received by <span class="text-rose-600">*</span></label>
                                <input id="tulong-release-to" type="text" maxlength="160" x-model="releaseTo" placeholder="Full name of the person who received it"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            </div>
                            <div x-show="showsAmount">
                                <label for="tulong-release-amount" class="block text-[11px] font-medium text-slate-600 mb-1">Actual amount (₱)</label>
                                <input id="tulong-release-amount" type="number" min="0" step="0.01" inputmode="decimal" x-model="releaseAmount" placeholder="0.00"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 tabular-nums focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            </div>
                            <div>
                                <label for="tulong-release-ref" class="block text-[11px] font-medium text-slate-600 mb-1">Reference no.</label>
                                <input id="tulong-release-ref" type="text" maxlength="120" x-model="releaseReference" placeholder="e.g. voucher or acknowledgement no."
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            </div>
                        </div>
                        <label for="tulong-release-note" class="block text-[11px] font-medium text-slate-600 mt-2 mb-1">Proof note</label>
                        <textarea id="tulong-release-note" x-model="releaseNote" rows="2" maxlength="2000" placeholder="e.g. Received at the MSWDO counter; the beneficiary signed the acknowledgement slip."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                        <p class="text-[11px] text-slate-500 mt-1">Proof of Release is required: a photo (beside this) or this note.</p>
                    </div>

                    {{-- Reject / Cancel / Return for Reassessment: each needs a
                         typed reason before it can be confirmed. --}}
                    <div x-ref="sideFormPanel" class="rounded-xl border p-3.5 mt-3" x-show="sideForm" x-cloak
                        :class="sideForm === 'reject' ? 'border-rose-200 bg-rose-50/40' : (sideForm === 'return' ? 'border-orange-200 bg-orange-50/40' : 'border-slate-200 bg-slate-50')">
                        <p class="text-xs font-semibold uppercase tracking-wide" :class="sideForm === 'reject' ? 'text-rose-700' : (sideForm === 'return' ? 'text-orange-700' : 'text-slate-700')" x-text="sideForms[sideForm]?.title"></p>
                        <p class="text-[11px] text-slate-500 mt-1" x-text="sideForms[sideForm]?.hint"></p>
                        <div class="flex items-center gap-2 mt-2" x-show="sideForm === 'reject'">
                            <select aria-label="Insert a standard reason"
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
                        <textarea aria-label="Reason" x-model="sideReason" rows="3" maxlength="2000" placeholder="Type the reason…"
                            class="w-full mt-2 rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" x-bind:disabled="processing || !sideReason.trim()" @click="confirmSideForm()"
                                class="inline-flex items-center justify-center min-h-[36px] px-3.5 py-2 text-xs font-semibold rounded-xl text-white shadow-card transition-colors disabled:opacity-40 disabled:pointer-events-none"
                                :class="sideForm === 'reject' ? 'bg-rose-600 hover:bg-rose-700' : (sideForm === 'return' ? 'bg-orange-700 hover:bg-orange-800' : 'bg-slate-700 hover:bg-slate-800')"
                                x-text="processing ? 'Saving…' : sideForms[sideForm]?.confirm"></button>
                            <button type="button" @click="sideForm = null; sideReason = ''" class="text-xs font-medium text-slate-500 hover:text-slate-700">Back</button>
                        </div>
                    </div>

                    {{-- Case record: what the case has recorded so far. --}}
                    <div class="rounded-xl border border-slate-200 p-4 mt-3" x-show="selected.case?.assessment_notes || selected.case?.approved_amount || selected.case?.released_to" x-cloak>
                        <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="file-check-2" class="w-3.5 h-3.5"></i> Case Record</p>
                        <div class="mt-2.5" x-show="selected.case?.assessment_notes">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wide">Assessment <span class="normal-case tracking-normal" x-text="selected.case?.assessed_at ? '· ' + formatDay(selected.case.assessed_at) : ''"></span></p>
                            <p class="text-xs text-slate-700 mt-0.5 whitespace-pre-line" x-text="selected.case?.assessment_notes"></p>
                        </div>
                        <dl class="grid grid-cols-3 gap-2 mt-2.5" x-show="showsAmount">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <dt class="text-[10px] text-slate-500 uppercase tracking-wide">Recommended</dt>
                                <dd class="text-sm font-semibold text-navy-900 tabular-nums" x-text="peso(selected.case?.recommended_amount)"></dd>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <dt class="text-[10px] text-slate-500 uppercase tracking-wide">Approved</dt>
                                <dd class="text-sm font-semibold text-navy-900 tabular-nums" x-text="peso(selected.case?.approved_amount)"></dd>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <dt class="text-[10px] text-slate-500 uppercase tracking-wide">Released</dt>
                                <dd class="text-sm font-semibold text-navy-900 tabular-nums" x-text="peso(selected.case?.released_amount)"></dd>
                            </div>
                        </dl>
                        <div class="mt-2.5 text-xs text-slate-700 space-y-0.5" x-show="selected.case?.released_to">
                            <p><span class="text-slate-500">Received by</span> <span class="font-medium" x-text="selected.case?.released_to"></span><span x-show="selected.case?.released_at" x-text="' · ' + formatDay(selected.case?.released_at)"></span></p>
                            <p x-show="selected.case?.release_reference"><span class="text-slate-500">Reference</span> <span class="font-mono" x-text="selected.case?.release_reference"></span></p>
                            <p x-show="selected.proofNote"><span class="text-slate-500">Proof</span> <span x-text="selected.proofNote"></span></p>
                        </div>
                    </div>

                    {{-- Case history: every transition the server recorded, newest first. --}}
                    <div class="rounded-xl border border-slate-200 p-4 mt-3" x-show="(selected.history || []).length" x-cloak>
                        <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide flex items-center gap-1.5 mb-2"><i data-lucide="history" class="w-3.5 h-3.5"></i> Case History</p>
                        <ul class="space-y-2">
                            <template x-for="(h, hi) in (selected.history || []).slice().reverse()" :key="hi">
                                <li class="text-xs border-l-2 border-slate-200 pl-2.5">
                                    <p class="font-medium text-navy-900" x-text="h.stage + (h.note ? ' — ' + h.note : '')"></p>
                                    <p class="text-[11px] text-slate-500" x-text="h.date + (h.by ? ' · ' + h.by : '')"></p>
                                </li>
                            </template>
                        </ul>
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
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Ready for Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i> Proof of Release
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed" x-show="!selected.release_evidence">A photo of the beneficiary receiving the assistance, or a proof note in the Release form — one is required before confirming.</p>

                            <template x-if="selected.release_evidence">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 aspect-[4/3]">
                                        <img :src="selected.release_evidence" class="w-full h-full object-cover" alt="Proof of release">
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[11px] text-emerald-600 font-medium inline-flex items-center gap-1"><i data-lucide="circle-check" class="w-3 h-3"></i> Captured <span x-text="selected.release_evidence_at"></span></span>
                                        <button type="button" @click="clearReleaseEvidence()" class="text-[11px] text-slate-500 hover:text-rose-500" x-show="selected.status === 'Ready for Release'">Retake</button>
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
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Ready for Release' || selected.release_evidence" x-cloak>
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
                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed" x-show="!selected.reference_pdf">No reference form on file for this service.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button on any
                 submitted requirement above. Fixed to the viewport (not nested
                 inside the scrollable middle column) so it sits above the
                 whole Review modal regardless of scroll position. --}}
            <div
                data-modal-overlay x-show="previewDoc"
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
                        <button type="button" @click="previewDoc = null" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-600 transition-colors shrink-0" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        {{-- The file itself: an image shown inline, a download link, or
                             why it cannot be opened (the server links a file only once
                             the malware scan has cleared it). A PDF is not framed: the
                             server serves uploads as downloads, never inline, so a
                             frame only started a download and stayed blank. --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/2] overflow-hidden mb-3" x-show="previewDoc?.preview_asset">
                            <img :src="previewDoc?.preview_asset" :alt="previewDoc?.name" class="w-full h-full object-contain">
                        </div>
                        <a x-show="previewDoc?.file_url" :href="previewDoc?.file_url" target="_blank" rel="noopener" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium inline-flex items-center gap-1 mb-3">
                            <i data-lucide="download" class="w-3 h-3"></i> Download the file
                        </a>
                        {{-- A PDF is read here: PDF.js draws its pages from the file's data, so the
                             file is never opened on the portal's origin and runs nothing
                             (Esperanza bugs QA, 2026-10-06: staff downloaded every file to read it). --}}
                        <div x-data="pdfPreview" x-effect="show(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))" x-show="!previewDoc?.preview_asset && previewKind(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))" x-cloak class="rounded-xl border border-slate-200 bg-slate-100 max-h-[60vh] overflow-y-auto p-2 mb-3">
                            <p x-show="loading" class="text-xs text-slate-500 text-center py-8">Opening the document…</p>
                            <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8">This file could not be shown here. Use Download the file.</p>
                            <img x-show="image" x-cloak :src="image" :alt="previewDoc?.name" class="block w-full h-auto rounded-lg bg-white">
                            <div x-ref="pages"></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/4] flex flex-col items-center justify-center gap-2.5 mb-3" x-show="!previewDoc?.preview_asset && !previewKind(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))">
                            <span
                                class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-100 text-rose-500' : 'bg-blue-100 text-blue-500'"
                            ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-7 h-7"></i></span>
                            <p class="text-xs font-medium text-slate-500 px-4 text-center truncate max-w-full" x-text="previewDoc?.file"></p>
                            <p x-show="previewDoc?.file_url" class="text-[11px] leading-snug text-slate-500 px-5 text-center">This kind of file cannot be shown here. Download it to read it.</p>
                            <p x-show="previewDoc?.file_notice" class="text-[11px] leading-snug text-rose-600 px-5 text-center" x-text="previewDoc?.file_notice"></p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500 shrink-0">Requirement</dt>
                                <dd class="font-medium text-navy-900 text-right truncate" x-text="previewDoc?.name"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500 shrink-0">Status</dt>
                                <dd class="font-medium text-navy-900" x-text="previewDoc?.status === 'Approved' ? 'Verified' : previewDoc?.status"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-3" x-show="previewDoc?.remarks">
                                <dt class="text-slate-500 shrink-0">Remarks</dt>
                                <dd class="font-medium text-right text-slate-600" x-text="previewDoc?.remarks"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <p class="text-xs text-slate-500 flex-1 flex items-center gap-1.5" x-show="selected && terminalStatuses.includes(selected.status)" x-cloak>
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>This case is finalized — no further status changes.
                </p>
                <template x-if="selected && !terminalStatuses.includes(selected.status)">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        {{-- The side moves the server allows from here (case.next),
                             each opening its reason form. --}}
                        <x-ui.button variant="ghost" size="sm" x-show="canMove('Cancelled')" x-bind:disabled="processing" @click="openSideForm('cancel')">Cancel Case</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" x-show="canMove('Returned for Reassessment')" x-bind:disabled="processing" @click="openSideForm('return')">Return for Reassessment</x-ui.button>
                        <x-ui.button variant="danger" size="sm" x-show="canMove('Rejected')" x-bind:disabled="processing" @click="openSideForm('reject')">Reject Case</x-ui.button>
                        {{-- Primary action — one button, dynamic per stage
                             (stageActionLabels; primaryAction() calls the
                             matching endpoint). --}}
                        <x-ui.button
                            size="sm"
                            x-show="nextStageLabel && (!primaryPermission || may(primaryPermission))"
                            x-bind:disabled="processing || !!primaryBlocked"
                            @click="primaryAction()"
                            x-bind:title="primaryBlocked || null"
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
