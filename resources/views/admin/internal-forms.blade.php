<x-layouts.admin title="Internal Forms" subtitle="Staff self-service forms: HR, General Services, Social Welfare and Agriculture." active="internal-forms">
    <div
        x-data="{
            officeKey: null,
            offices: {},
            totalForms: 0,
            submissions: [],
            loading: true,
            failed: false,
            tab: 'all',
            search: '',
            doneStatuses: ['Completed', 'Released', 'Rejected'],
            open: false,
            selectedForm: null,
            formData: {},
            attachments: {},
            submitting: false,
            doneSubmit: false,
            formStep: 1,
            iconMap: { hr: 'users-round', gso: 'warehouse', agriculture: 'wheat', mswd: 'hand-heart' },
            // Spelled out, acronym kept for those who know it (live test
            // 2026-10-07, UX-14). HR first: leave and service forms are for everyone.
            officeNames: { hr: 'HR (MHRMO)', gso: 'General Services (MGSO)', agriculture: 'Agriculture (MAO)', mswd: 'Social Welfare (MSWDO)' },
            officeOrder: ['hr', 'gso', 'mswd', 'agriculture'],
            ownOfficeKey() {
                const dept = String($store.session.account?.dept || '').toLowerCase();
                if (/social welfare|mswd/.test(dept)) return 'mswd';
                if (/agricultur|\bmao\b/.test(dept)) return 'agriculture';
                if (/general services|gso/.test(dept)) return 'gso';
                return 'hr';
            },
            async init() {
                this.loading = true;
                this.failed = false;
                try {
                    const [defsRes, subsRes] = await Promise.all([
                        $api.get('/admin/internal-forms'),
                        $api.getAll('/admin/internal-forms/submissions'),
                    ]);
                    const byKey = {};
                    (defsRes.data || []).forEach(d => {
                        if (!byKey[d.office_key]) byKey[d.office_key] = { shortLabel: this.officeNames[d.office_key] || d.office, icon: this.iconMap[d.office_key] || 'building-2', forms: [] };
                        byKey[d.office_key].forms.push(d);
                    });
                    const rank = (k) => { const i = this.officeOrder.indexOf(k); return i === -1 ? 99 : i; };
                    const grouped = {};
                    Object.keys(byKey).sort((a, b) => rank(a) - rank(b)).forEach((k) => { grouped[k] = byKey[k]; });
                    this.offices = grouped;
                    await $store.session.whenReady?.();
                    const own = this.ownOfficeKey();
                    this.officeKey = grouped[own] ? own : (Object.keys(grouped)[0] || null);
                    this.totalForms = (defsRes.data || []).length;
                    this.submissions = subsRes.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
            },
            // Splits a form's fields into steps: a form that follows its paper's
            // sections (2026-11-03) takes one step per section, as the paper
            // reads; otherwise plain fields are chunked in groups of 6. Each
            // repeatable table gets its own step. A short form resolves to a
            // single step, so it renders exactly like a flat form.
            get formSteps() {
                if (!this.selectedForm) return [];
                const steps = [];
                let bucket = [];
                let section = null;
                const flush = () => { if (bucket.length) { steps.push({ type: 'fields', fields: bucket, section }); bucket = []; } };
                (this.selectedForm.fields || []).forEach(f => {
                    if ((f.section || null) !== section) { flush(); section = f.section || null; }
                    if (f.type === 'table') {
                        flush();
                        steps.push({ type: 'table', field: f, label: f.label });
                    } else {
                        bucket.push(f);
                        if (!section && bucket.length >= 6) flush();
                    }
                });
                flush();
                if (!steps.length) steps.push({ type: 'fields', fields: [] });
                const fieldSteps = steps.filter(s => s.type === 'fields' && !s.section);
                steps.filter(s => s.type === 'fields').forEach(s => {
                    s.label = s.section || (fieldSteps.length > 1 ? `Form Details — Part ${fieldSteps.indexOf(s) + 1}` : 'Form Details');
                });
                return steps;
            },
            get totalFormSteps() { return this.formSteps.length; },
            get currentFormStep() { return this.formSteps[this.formStep - 1] || { type: 'fields', fields: [] }; },
            get currentOffice() { return this.offices[this.officeKey] || null; },
            // Only Super Administrator, Municipal Administrator, and Department
            // Heads (who review/approve their department's submissions) see
            // everyone's forms server-side; everyone else's list is already
            // scoped to their own by the API. This only drives the banner copy.
            get isAuthority() {
                const role = this.$store.session.account?.roleKey;
                return !this.$store.session.account || ['super_admin', 'municipal_admin', 'department_head'].includes(role);
            },
            // The server shows a Department Head their office's forms and those
            // of their own staff that wait on them, not every office's.
            get isOfficeHead() { return this.$store.session.account?.roleKey === 'department_head'; },
            get filteredForms() {
                const q = this.search.trim().toLowerCase();
                return (this.currentOffice?.forms || []).filter(f => !q || f.form.toLowerCase().includes(q));
            },
            get filteredSubmitted() {
                return this.submissions.filter(s => {
                    if (this.tab === 'action') return !this.doneStatuses.includes(s.status);
                    if (this.tab === 'done') return this.doneStatuses.includes(s.status);
                    return true;
                });
            },
            get needingAction() { return this.submissions.filter(s => !this.doneStatuses.includes(s.status)).length; },
            selectForm(f) {
                this.selectedForm = f;
                this.formData = {};
                this.attachments = {};
                this.formStep = 1;
                (f.fields || []).forEach(fld => {
                    if (fld.type === 'checkbox-group') { this.formData[fld.key] = []; }
                    else if (fld.type === 'table') { this.formData[fld.key] = []; }
                    else if (fld.type === 'checkbox') { this.formData[fld.key] = false; }
                    else { this.formData[fld.key] = fld.default || ''; }
                });
                this.open = true;
            },
            toggleChecklist(key, opt) {
                const list = this.formData[key] || [];
                const i = list.indexOf(opt);
                if (i === -1) { list.push(opt); } else { list.splice(i, 1); }
                this.formData[key] = list;
            },
            addTableRow(f) {
                if (!this.formData[f.key]) this.formData[f.key] = [];
                const row = {};
                f.columns.forEach(c => { row[c.key] = ''; });
                this.formData[f.key].push(row);
            },
            // Approve or reject the step waiting on this officer. The server had
            // POST .../decide and the screen had no way to call it, so a filed
            // form could never move (QA run 2026-10-05). Each row says whether
            // it is this officer's turn (can_decide) and which step it is on.
            decision: null,
            decisionRemarks: '',
            decisionData: {},
            decisionFields: [],
            decisionLoading: false,
            deciding: false,
            // The step's own part of the paper (CS Form 6's 7.A, the trip
            // ticket's fuel after the trip), filled by whoever decides it.
            async startDecision(s, kind) {
                this.decision = { s, kind };
                this.decisionRemarks = '';
                this.decisionData = {};
                this.decisionFields = [];
                this.decisionLoading = true;
                try {
                    const detail = this.viewing?.ref === s.ref ? this.viewing : (await $api.get('/admin/internal-forms/submissions/' + encodeURIComponent(s.ref))).data;
                    const step = (detail.chain || []).find(c => c.step === s.step?.number);
                    this.decisionFields = (step?.fields || []).filter(f => f.type !== 'table');
                    this.decisionFields.forEach(f => { this.decisionData[f.key] = f.type === 'checkbox-group' ? [] : (f.type === 'checkbox' ? false : (f.default || '')); });
                } catch { this.decisionFields = []; } finally { this.decisionLoading = false; }
            },
            get decisionMissing() {
                if (this.decision?.kind !== 'approve') return false;
                return this.decisionFields.some(f => f.required && (Array.isArray(this.decisionData[f.key]) ? !this.decisionData[f.key].length : !String(this.decisionData[f.key] ?? '').trim()));
            },
            async confirmDecision() {
                const d = this.decision;
                if (!d || this.deciding) return;
                if (d.kind === 'reject' && !this.decisionRemarks.trim()) return;
                if (this.decisionMissing) return;
                this.deciding = true;
                try {
                    const filled = Object.fromEntries(Object.entries(this.decisionData).filter(([, v]) => v !== '' && v !== false && !(Array.isArray(v) && !v.length)));
                    const res = await $api.post('/admin/internal-forms/submissions/' + encodeURIComponent(d.s.ref) + '/decide', {
                        decision: d.kind,
                        remarks: this.decisionRemarks.trim() || undefined,
                        data: Object.keys(filled).length ? filled : undefined,
                    });
                    const i = this.submissions.findIndex((x) => x.ref === d.s.ref);
                    if (i !== -1) this.submissions.splice(i, 1, { ...this.submissions[i], ...res.data });
                    this.decision = null;
                    if (this.viewing?.ref === d.s.ref) this.openView(this.submissions[i] || d.s);
                    this.$dispatch('toast', {
                        message: d.s.ref + (d.kind === 'approve' ? ' approved' : ' rejected') + ' — now ' + res.data.status + '.',
                        variant: d.kind === 'approve' ? 'success' : 'error',
                    });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not record the decision.', variant: 'error' });
                } finally { this.deciding = false; }
            },
            // A filed form, read and printed as its office's paper (2026-11-03).
            viewing: null,
            viewLoading: false,
            viewFailed: false,
            async openView(s) {
                this.viewLoading = true;
                this.viewFailed = false;
                if (this.viewing?.ref !== s.ref) this.viewing = { ...s, fields: [], chain: [], approvals: [], loading: true };
                try {
                    const res = await $api.get('/admin/internal-forms/submissions/' + encodeURIComponent(s.ref));
                    this.viewing = { ...s, ...res.data };
                } catch { this.viewFailed = true; } finally {
                    this.viewLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            approvalOf(step) { return (this.viewing?.approvals || []).find(a => a.step === step) || null; },
            officeLongName(key, fallback) {
                return ({ hr: 'Municipal Human Resource Management Office', gso: 'Municipal General Services Office', mswd: 'Municipal Social Welfare and Development Office', agriculture: 'Municipal Agriculture Office' })[key] || fallback || '';
            },
            longDate(v) {
                if (!v) return '';
                const d = new Date(/^\d{4}-\d{2}-\d{2}$/.test(v) ? v + 'T00:00:00' : v);
                return isNaN(d) ? String(v) : d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            },
            fieldText(f, v) {
                if (v === undefined || v === null || v === '' || (Array.isArray(v) && !v.length)) return '—';
                if (f.type === 'checkbox') return v ? 'Yes' : 'No';
                if (Array.isArray(v)) return v.join(', ');
                if (f.type === 'date') return this.longDate(v);
                if (f.type === 'time' && /^\d{2}:\d{2}/.test(v)) {
                    const [h, m] = v.split(':').map(Number);
                    return ((h % 12) || 12) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' AM' : ' PM');
                }
                return String(v);
            },
            // The answers under the paper's own section headings, in order.
            paperSections(fields, data) {
                const out = [];
                (fields || []).forEach(f => {
                    const heading = f.section || null;
                    let sec = out[out.length - 1];
                    if (!sec || sec.heading !== heading) { sec = { heading, items: [] }; out.push(sec); }
                    const value = (data || {})[f.key];
                    sec.items.push({ f, value, text: this.fieldText(f, value) });
                });
                // Answers to questions an earlier version of the form asked:
                // a form filed before 2026-11-03 still prints whole.
                const known = new Set((fields || []).map((f) => f.key));
                const left = Object.entries(data || {}).filter(([k, v]) => !known.has(k) && v !== '' && v !== null && !(Array.isArray(v) && !v.length));
                if (left.length) {
                    out.push({ heading: 'Other entries (earlier version of this form)', items: left.map(([k, v]) => {
                        const f = { key: k, label: k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase()), type: 'text' };
                        const text = Array.isArray(v) ? v.map((r) => (r && typeof r === 'object') ? Object.values(r).filter(Boolean).join(' / ') : String(r)).join('; ') : this.fieldText(f, v);
                        return { f, value: v, text };
                    }) });
                }
                return out;
            },
            // The letterhead lines: the paper's own, none for the CSC forms
            // that print none, else the LGU's with the office's long name.
            paperLetterhead(v) {
                if (Array.isArray(v?.paper?.letterhead)) return v.paper.letterhead;
                return ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza', v?.paper?.office || this.officeLongName(v?.office_key, v?.office)];
            },
            optionTick(value, o) {
                const chosen = Array.isArray(value) ? value : (value === undefined || value === null || value === '' ? [] : [value]);
                return chosen.some((c) => String(c) === String(o)) ? '( / )' : '(\u00a0\u00a0\u00a0)';
            },
            // The signature lines: as the paper lists them ('submitter', a
            // step's approver, or a blank line signed by hand), else the
            // filer's line and one for each step that names a signatory.
            paperSignatures(v) {
                const chain = v?.chain || [];
                const approver = (label) => {
                    const step = chain.find((c) => c.label === label);
                    const a = step ? this.approvalOf(step.step) : null;
                    return a && a.decision === 'approve' ? (a.by || '') : '';
                };
                const listed = v?.paper?.signatures;
                if (Array.isArray(listed) && listed.length) {
                    return listed.map((sg) => ({
                        role: sg.role || '',
                        name: sg.name === 'submitter' ? (v.submitted_by || '') : (String(sg.name || '').startsWith('step:') ? approver(sg.name.slice(5)) : ''),
                    }));
                }
                return [
                    { role: v?.paper?.applicantSigns || 'Signature over Printed Name', name: v?.submitted_by || '' },
                    ...chain.filter((c) => c.signatory).map((c) => ({ role: c.signatory, name: approver(c.label) })),
                ];
            },
            // Prints the paper alone: a copy goes to a plain section at the top
            // of the page and only that prints (the modal would clip it). A
            // paper kept in copies (the MGSO's and the borrower's) prints once
            // per copy, each with its label.
            printPaper() {
                const el = this.$refs.ifPaper;
                if (!el) return;
                document.getElementById('if-print-root')?.remove();
                const root = document.createElement('div');
                root.id = 'if-print-root';
                root.setAttribute('x-ignore', '');
                const copies = (this.viewing?.paper?.copies || []).length ? this.viewing.paper.copies : [null];
                copies.forEach((label) => {
                    const copy = el.cloneNode(true);
                    if (label) {
                        const tag = document.createElement('p');
                        tag.className = 'if-copy-label';
                        tag.textContent = label;
                        copy.prepend(tag);
                    }
                    root.appendChild(copy);
                });
                document.body.appendChild(root);
                document.documentElement.classList.add('if-printing');
                const done = () => { root.remove(); document.documentElement.classList.remove('if-printing'); window.removeEventListener('afterprint', done); };
                window.addEventListener('afterprint', done);
                window.print();
            },
            // ERPAT & KALIPI: the officers, members and activities of each
            // barangay's fathers' association, and the federation's meetings,
            // read together from their forms (MSWDO forms, 2026-10-26). A view
            // inside the MSWDO tab, not a sidebar entry of its own.
            mswdView: 'forms',
            assoc: null,
            assocLoading: false,
            assocFailed: false,
            assocOpen: null,
            get mayViewAssociations() { return this.$store.session.canPerm?.('tulong_erpat', 'view') ?? false; },
            async showAssociations() {
                this.mswdView = 'associations';
                if (this.assoc || this.assocLoading) return;
                this.assocLoading = true;
                this.assocFailed = false;
                try {
                    const res = await $api.get('/admin/associations/erpat');
                    this.assoc = res.data;
                } catch { this.assocFailed = true; } finally {
                    this.assocLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            reset() { this.open = false; this.selectedForm = null; this.formData = {}; this.attachments = {}; this.doneSubmit = false; this.submitting = false; this.formStep = 1; },
            async submit() {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const res = await $api.post('/admin/internal-forms/' + this.selectedForm.key + '/submit', { data: this.formData });
                    this.submissions.unshift(res.data);
                    this.doneSubmit = true;
                    setTimeout(() => { this.open = false; this.reset(); this.$dispatch('toast', { message: 'Form submitted — routed for processing.', variant: 'success' }); }, 1400);
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not submit the form.', variant: 'error' });
                } finally { this.submitting = false; }
            },
        }"
        x-effect="!open && reset()"
        class="animate-fade-up"
    >
        <div x-show="loading" class="space-y-3 mb-4"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-24 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load internal forms.'" retryExpr="init()" :retryable="true" /></div>

        <div x-show="!loading && !failed">
        <!-- Visibility banner: authorities see every submission, everyone else only their own -->
        <div x-show="isAuthority && !isOfficeHead" x-cloak class="flex items-center gap-2.5 rounded-xl bg-brand-50 border border-brand-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="shield-check" class="w-4 h-4 text-brand-500 shrink-0"></i>
            <p class="text-xs text-brand-700">Full visibility — as <strong x-text="$store.session.account?.role || 'an administrator'"></strong>, you can see every staff member's submissions across all offices.</p>
        </div>
        <div x-show="isOfficeHead" x-cloak class="flex items-center gap-2.5 rounded-xl bg-brand-50 border border-brand-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="shield-check" class="w-4 h-4 text-brand-500 shrink-0"></i>
            <p class="text-xs text-brand-700">Your office's view — the forms your office signs, and your own staff's forms that wait on you as their Department Head.</p>
        </div>
        <div x-show="!isAuthority" x-cloak class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="lock" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Personal view — you can only see forms <strong>you</strong> submitted. Other staff members' submissions are hidden.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card layout="compact" @click="tab = 'all'" class="cursor-pointer" label="Total Submissions" valueBind="submissions.length" icon="clipboard-list" color="brand" :delay="0" />
            <x-ui.stat-card layout="compact" @click="tab = 'action'" class="cursor-pointer" label="Needs Action" valueBind="needingAction" icon="inbox" color="orange" :delay="40" />
            <x-ui.stat-card layout="compact" label="Offices Covered" valueBind="Object.keys(offices).length" icon="building-2" color="purple" :delay="80" />
            <x-ui.stat-card layout="compact" label="Forms Available" valueBind="totalForms" icon="file-stack" color="green" :delay="120" />
        </div>

        <!-- Office tabs + search -->
        <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto">
                <template x-for="(o, key) in offices" :key="key">
                    <button
                        type="button" @click="officeKey = key"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                        :class="officeKey === key ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                    ><i :data-lucide="o.icon" class="w-3.5 h-3.5"></i><span x-text="o.shortLabel"></span></button>
                </template>
            </div>
            <div x-show="officeKey === 'mswd' && mayViewAssociations" x-cloak class="flex items-center gap-1.5" data-mswd-views>
                <button type="button" @click="mswdView = 'forms'" class="px-3 py-1.5 text-xs font-medium rounded-full border transition-colors"
                    :class="mswdView === 'forms' ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-ink-muted hover:text-slate-700'">Forms</button>
                <button type="button" @click="showAssociations()" class="px-3 py-1.5 text-xs font-medium rounded-full border transition-colors inline-flex items-center gap-1.5"
                    :class="mswdView === 'associations' ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-ink-muted hover:text-slate-700'"><i data-lucide="users-round" class="w-3.5 h-3.5"></i> ERPAT &amp; KALIPI associations</button>
            </div>
            <div class="relative" x-show="!(officeKey === 'mswd' && mswdView === 'associations')">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search forms" type="text" x-model="search" placeholder="Search forms..." class="w-full max-w-sm pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
            </div>
        </div>

        <!-- ERPAT & KALIPI associations (MSWDO tab) -->
        <div x-show="officeKey === 'mswd' && mswdView === 'associations'" x-cloak class="space-y-4 mb-5" data-associations>
            <div x-show="assocLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-40 opacity-80" /></div>
            <div x-show="assocFailed" x-cloak><x-ui.error-state messageExpr="'Could not load the associations.'" retryExpr="assoc = null; showAssociations()" :retryable="true" /></div>
            <template x-if="assoc">
                <div class="space-y-4">
                    <p class="text-xs text-ink-muted">Read from the ERPAT forms already filed in Esperanza: the officers' list, each member's registration (Tulong), activity attendance, and the federation and BVAW Desk / KALIPI meetings.</p>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <x-ui.stat-card layout="compact" label="Barangays with officers" valueBind="assoc.totals.barangays_with_officers" icon="landmark" color="brand" />
                        <x-ui.stat-card layout="compact" label="Registered members" valueBind="assoc.totals.members" icon="users-round" color="green" />
                        <x-ui.stat-card layout="compact" label="Activities held" valueBind="assoc.totals.activities" icon="calendar-check" color="purple" />
                        <x-ui.stat-card layout="compact" label="Federation meetings" valueBind="assoc.totals.meetings" icon="messages-square" color="orange" />
                    </div>
                    <x-ui.table>
                        <thead>
                            <tr class="bg-well/70">
                                <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Barangay</th>
                                <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">President</th>
                                <th class="px-4 py-2.5 text-right text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Members</th>
                                <th class="px-4 py-2.5 text-right text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Activities</th>
                                <th class="px-4 py-2.5 text-right text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><span class="sr-only">Details</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="b in assoc.barangays" :key="b.barangay">
                                <tr class="hover:bg-white/60 cursor-pointer" @click="assocOpen = assocOpen === b.barangay ? null : b.barangay">
                                    <td class="px-4 py-2.5 font-medium text-ink-body" x-text="b.barangay"></td>
                                    <td class="px-4 py-2.5" :class="b.officers ? 'text-ink-body' : 'text-ink-faint italic'" x-text="b.officers?.president || 'No officers listed yet'"></td>
                                    <td class="px-4 py-2.5 text-right tabular-nums" x-text="b.members.length"></td>
                                    <td class="px-4 py-2.5 text-right tabular-nums" x-text="b.activities.length"></td>
                                    <td class="px-4 py-2.5 text-right"><i data-lucide="chevron-down" class="w-4 h-4 inline text-ink-muted transition-transform" :class="assocOpen === b.barangay && 'rotate-180'"></i></td>
                                </tr>
                            </template>
                        </tbody>
                    </x-ui.table>
                    <template x-for="b in assoc.barangays.filter(x => x.barangay === assocOpen)" :key="'open-' + b.barangay">
                        <div class="surface-glass rounded-[26px] p-4 grid gap-4 md:grid-cols-3" data-association-detail>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2" x-text="'Officers · ' + b.barangay"></p>
                                <template x-if="b.officers">
                                    <dl class="text-sm space-y-1">
                                        <template x-for="[label, key] in [['President','president'],['Vice President','vice_president'],['Secretary','secretary'],['Treasurer','treasurer'],['Auditor','auditor']]" :key="key">
                                            <div class="flex justify-between gap-2"><dt class="text-ink-muted" x-text="label"></dt><dd class="font-medium text-ink-body text-right" x-text="b.officers[key] || '—'"></dd></div>
                                        </template>
                                        <div x-show="b.officers.public_relations_officers.length" class="flex justify-between gap-2"><dt class="text-ink-muted">P.R.O.</dt><dd class="text-right" x-text="b.officers.public_relations_officers.join(', ')"></dd></div>
                                        <div x-show="b.officers.peace_courting_officers.length" class="flex justify-between gap-2"><dt class="text-ink-muted">Peace courting</dt><dd class="text-right" x-text="b.officers.peace_courting_officers.join(', ')"></dd></div>
                                        <p class="text-[11px] text-ink-muted pt-1" x-text="'From ' + b.officers.ref + ' · ' + (b.officers.submitted || '')"></p>
                                    </dl>
                                </template>
                                <p x-show="!b.officers" class="text-xs text-ink-muted">File the "ERPAT Association Officers" form for this barangay.</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Members</p>
                                <ul class="text-sm space-y-1 max-h-56 overflow-y-auto">
                                    <template x-for="m in b.members" :key="m.ref">
                                        <li class="flex justify-between gap-2"><span class="text-ink-body" x-text="m.name"></span><span class="text-[11px] text-ink-muted" x-text="[m.occupation, m.status].filter(Boolean).join(' · ')"></span></li>
                                    </template>
                                </ul>
                                <p x-show="!b.members.length" class="text-xs text-ink-muted">No ERPAT membership registrations from this barangay yet.</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Activities</p>
                                <ul class="text-sm space-y-1.5">
                                    <template x-for="a in b.activities" :key="a.ref">
                                        <li><p class="text-ink-body" x-text="a.activity || a.ref"></p><p class="text-[11px] text-ink-muted" x-text="[a.date, a.venue, a.attendees + ' attended'].filter(Boolean).join(' · ')"></p></li>
                                    </template>
                                </ul>
                                <p x-show="!b.activities.length" class="text-xs text-ink-muted">No activity attendance filed yet.</p>
                            </div>
                        </div>
                    </template>
                    <div>
                        <h3 class="text-sm font-semibold text-ink mb-2">Federation and BVAW Desk / KALIPI meetings</h3>
                        <x-ui.table>
                            <thead>
                                <tr class="bg-well/70">
                                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Meeting</th>
                                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Date</th>
                                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell">Venue</th>
                                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden md:table-cell">Next</th>
                                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-600/[.08]">
                                <template x-for="m in assoc.meetings" :key="m.ref">
                                    <tr>
                                        <td class="px-4 py-2.5"><p class="text-ink-body" x-text="m.kind + (m.meeting_no ? ' #' + m.meeting_no : '')"></p><p class="text-[11px] font-mono text-ink-muted" x-text="m.ref"></p></td>
                                        <td class="px-4 py-2.5" x-text="m.date || '—'"></td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell" x-text="m.venue || '—'"></td>
                                        <td class="px-4 py-2.5 hidden md:table-cell" x-text="m.next || '—'"></td>
                                        <td class="px-4 py-2.5"><x-ui.badge statusBind="m.status" /></td>
                                    </tr>
                                </template>
                                <tr x-show="!assoc.meetings.length"><td colspan="5" class="px-4 py-6 text-center text-xs text-ink-muted">No meetings filed yet.</td></tr>
                            </tbody>
                        </x-ui.table>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!(officeKey === 'mswd' && mswdView === 'associations')">
        <!-- Form cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
            <template x-for="f in filteredForms" :key="f.key">
                <button
                    type="button" @click="selectForm(f)"
                    class="text-left surface-glass rounded-[26px] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard p-4"
                >
                    <div class="flex items-start justify-between mb-2.5">
                        <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                        <span x-show="f.sensitive" class="text-[10px] font-medium text-purple-600 bg-purple-50 rounded-full px-2 py-0.5">Sensitive</span>
                    </div>
                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="f.form"></p>
                    <p class="text-xs text-ink-muted mt-1 truncate" x-text="f.print_template || ''"></p>
                </button>
            </template>
            <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl" x-show="filteredForms.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600">No forms match this search.</p>
            </div>
        </div>

        <!-- My Submitted Forms -->
        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-ink" x-text="isAuthority ? 'Recent Submissions' : 'My Submissions'"></h3>
            <span class="text-[11px] text-ink-muted" x-text="filteredSubmitted.length + (isAuthority ? ' total' : ' submitted by you')"></span>
        </div>
        <x-ui.table>
            <thead>
                <tr class="bg-well/70">
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Form</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden md:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden lg:table-cell">Submitted By</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell">Date</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Status</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="s in filteredSubmitted" :key="s.ref">
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted" x-text="s.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="s.form"></td>
                        <td class="px-4 py-3 text-ink-muted hidden md:table-cell" x-text="s.office"></td>
                        <td class="px-4 py-3 text-ink-muted hidden lg:table-cell" x-text="s.submitted_by"></td>
                        <td class="px-4 py-3 text-ink-muted hidden sm:table-cell" x-text="s.submitted ? new Date(s.submitted).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></td>
                        <td class="px-4 py-3">
                            <x-ui.badge statusBind="s.status" />
                        </td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <button type="button" @click="openView(s)" class="text-xs font-semibold text-brand-600 hover:underline mr-1.5" :aria-label="'View ' + s.ref">View</button>
                            <span x-show="s.can_decide" class="text-slate-200" aria-hidden="true">·</span>
                            <div x-show="s.can_decide" class="inline-flex items-center gap-1.5">
                                <button type="button" @click="startDecision(s, 'approve')" class="text-xs font-semibold text-emerald-700 hover:underline">Approve</button>
                                <span class="text-slate-200" aria-hidden="true">·</span>
                                <button type="button" @click="startDecision(s, 'reject')" class="text-xs font-semibold text-rose-600 hover:underline">Reject</button>
                            </div>
                            <span x-show="!s.can_decide && s.step" class="text-[11px] text-ink-muted" x-text="'Waiting on ' + (s.step?.office || s.step?.label || 'the next step')"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3" x-show="filteredSubmitted.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No submissions in this view.</p>
        </div>
        </div>{{-- /forms view --}}
        </div>

        <!-- Decide on the waiting step -->
        <div x-show="decision" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
             @click.self="decision = null" @keydown.escape.window="decision = null"
             role="dialog" aria-modal="true" aria-labelledby="if-decide-title">
            <div class="bg-white rounded-2xl shadow-float w-full p-6 space-y-4" :class="decisionFields.length ? 'max-w-lg' : 'max-w-md'">
                <div>
                    <h3 id="if-decide-title" class="text-base font-semibold text-ink" x-text="(decision?.kind === 'approve' ? 'Approve ' : 'Reject ') + (decision?.s.ref || '')"></h3>
                    <p class="text-xs text-ink-muted mt-1" x-text="(decision?.s.form || '') + (decision?.s.step ? ' · Step ' + decision.s.step.number + (decision.s.step.label ? ': ' + decision.s.step.label : '') : '')"></p>
                </div>
                <div x-show="decisionLoading" class="space-y-2"><x-ui.skeleton class="h-10" /></div>
                <div x-show="!decisionLoading && decisionFields.length" x-cloak class="space-y-3 max-h-[46vh] overflow-y-auto scrollbar-thin pr-1" data-step-part>
                    <p class="text-[11px] font-bold uppercase tracking-[.07em] text-ink-muted">This step’s part of the form</p>
                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="f in decisionFields" :key="f.key">
                            <x-admin.internal-forms.field-input model="decisionData" />
                        </template>
                    </div>
                </div>
                <label class="block">
                    <span class="text-xs font-medium text-slate-600" x-text="decision?.kind === 'reject' ? 'Reason (required)' : 'Remarks (optional)'"></span>
                    <textarea x-model="decisionRemarks" rows="3" class="mt-1 w-full text-sm rounded-2xl px-3 py-2 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm"></textarea>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="decision = null" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-2xl">Cancel</button>
                    <button type="button" @click="confirmDecision()"
                        :disabled="deciding || decisionLoading || decisionMissing || (decision?.kind === 'reject' && !decisionRemarks.trim())"
                        :class="decision?.kind === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        class="px-4 py-2 text-xs font-semibold text-white rounded-2xl disabled:opacity-40"
                        x-text="deciding ? 'Saving…' : (decision?.kind === 'approve' ? 'Confirm approval' : 'Confirm rejection')"></button>
                </div>
            </div>
        </div>

        <!-- A filed form, as its paper -->
        <div x-show="viewing" x-cloak class="fixed inset-0 z-40 flex items-start sm:items-center justify-center p-3 sm:p-6 bg-navy-950/60 backdrop-blur-sm"
             @click.self="viewing = null" @keydown.escape.window="!decision && (viewing = null)"
             role="dialog" aria-modal="true" aria-labelledby="if-view-title" data-if-view>
            <div class="bg-white rounded-2xl shadow-float w-full max-w-3xl max-h-[92vh] flex flex-col">
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-brand-600/10">
                    <div class="min-w-0">
                        <h3 id="if-view-title" class="text-sm font-semibold text-ink truncate" x-text="viewing ? viewing.form + ' · ' + viewing.ref : ''"></h3>
                        <p class="text-[11px] text-ink-muted" x-text="viewing?.status || ''"></p>
                    </div>
                    <button type="button" @click="viewing = null" class="ml-auto text-ink-muted hover:text-ink" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <div class="overflow-y-auto scrollbar-thin px-3 sm:px-6 py-5 bg-slate-50/70">
                    <div x-show="viewLoading && !viewing?.chain?.length" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-48 opacity-80" /></div>
                    <div x-show="viewFailed" x-cloak><x-ui.error-state messageExpr="'Could not open this form.'" retryExpr="openView(viewing)" :retryable="true" /></div>
                    <template x-if="viewing && !viewing.loading">
                        <x-admin.internal-forms.paper />
                    </template>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 px-5 py-3 border-t border-brand-600/10">
                    <button type="button" x-show="viewing?.can_decide" @click="startDecision(viewing, 'reject')" class="px-4 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-2xl">Reject</button>
                    <button type="button" x-show="viewing?.can_decide" @click="startDecision(viewing, 'approve')" class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-2xl">Approve</button>
                    <button type="button" @click="printPaper()" :disabled="!viewing || viewing.loading" class="px-4 py-2 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-2xl inline-flex items-center gap-1.5 disabled:opacity-40"><i data-lucide="printer" class="w-3.5 h-3.5"></i>Print</button>
                </div>
            </div>
        </div>

        <!-- Fill-out Modal -->
        <x-ui.modal title="Fill Out Form" maxWidth="2xl" :fullscreen="true">
            <template x-if="selectedForm">
                <div>
                    <!-- Success state -->
                    <div x-show="doneSubmit" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                        <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                            <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                            <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                                <i data-lucide="check" class="w-7 h-7"></i>
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-ink">Form Submitted!</p>
                        <p class="text-xs text-ink-muted mt-1 max-w-[240px]">Your submission has been logged and routed for processing.</p>
                    </div>

                    <div x-show="!doneSubmit">
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-brand-600/10">
                            <span class="w-10 h-10 rounded-lg clay-blue flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4.5 h-4.5"></i></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink truncate" x-text="selectedForm.form"></p>
                                <p class="text-xs text-ink-muted truncate" x-text="selectedForm.print_template || ''"></p>
                            </div>
                        </div>

                        {{-- Approval chain, from the form's own real definition --}}
                        <div x-show="(selectedForm.approval_chain || []).length" x-cloak class="flex flex-wrap items-center gap-1.5 mb-5 text-[11px] text-ink-muted">
                            <span class="font-medium text-slate-600">Routes to:</span>
                            <template x-for="(step, idx) in (selectedForm.approval_chain || [])" :key="step.step">
                                <span class="inline-flex items-center gap-1.5">
                                    <span x-show="idx > 0" class="text-ink-faint">→</span>
                                    <span class="bg-slate-100 rounded-full px-2 py-0.5" x-text="step.label"></span>
                                </span>
                            </template>
                        </div>

                        {{-- Step progress indicator — only shown when the form actually needed splitting --}}
                        <nav x-show="totalFormSteps > 1" x-cloak class="flex items-center gap-0 mb-5" aria-label="Form steps">
                            <template x-for="(step, idx) in formSteps" :key="idx">
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="flex items-center w-full">
                                        <div class="flex-1 h-0.5" :class="formStep > idx + 1 ? 'bg-brand-500' : (idx === 0 ? 'bg-transparent' : 'bg-slate-200')"></div>
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all flex-shrink-0"
                                            :class="formStep >= idx + 1 ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white text-ink-muted'"
                                        >
                                            <template x-if="formStep > idx + 1"><i data-lucide="check" class="w-3.5 h-3.5"></i></template>
                                            <template x-if="formStep <= idx + 1"><span x-text="idx + 1"></span></template>
                                        </div>
                                        <div class="flex-1 h-0.5" :class="[formStep > idx + 1 ? 'bg-brand-500' : 'bg-slate-200', idx === totalFormSteps - 1 ? '!bg-transparent' : '']"></div>
                                    </div>
                                    <span class="text-[10px] mt-1.5 font-medium text-center px-1 leading-tight" :class="formStep === idx + 1 ? 'text-brand-600' : formStep > idx + 1 ? 'text-brand-500' : 'text-ink-muted'" x-text="step.label"></span>
                                </div>
                            </template>
                        </nav>

                        <div class="max-h-[52vh] overflow-y-auto scrollbar-thin pr-1 space-y-4">

                            {{-- Fields step --}}
                            <div x-show="currentFormStep.type === 'fields'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <template x-for="f in (currentFormStep.fields || [])" :key="f.key">
                                    <x-admin.internal-forms.field-input model="formData" />
                                </template>
                            </div>

                            {{-- Table step (repeatable rows) --}}
                            <div x-show="currentFormStep.type === 'table'">
                                <template x-if="currentFormStep.type === 'table'">
                                    <div>
                                        <div class="flex items-center justify-between mb-2.5">
                                            <label class="block text-xs font-medium text-slate-700" x-text="currentFormStep.field.label"></label>
                                            <span class="text-[11px] text-ink-muted" x-text="(formData[currentFormStep.field.key] || []).length + ' added'"></span>
                                        </div>
                                        <div class="space-y-2.5">
                                            <template x-for="(row, ri) in (formData[currentFormStep.field.key] || [])" :key="ri">
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 bg-brand-50 rounded-full pl-1.5 pr-2.5 py-1">
                                                            <i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-3 h-3"></i>
                                                            <span x-text="row[currentFormStep.field.columns[0].key] || ((currentFormStep.field.rowLabel || 'Entry') + ' ' + (ri + 1))"></span>
                                                        </span>
                                                        <button aria-label="Remove" type="button" @click="formData[currentFormStep.field.key].splice(ri, 1)" class="text-ink-faint hover:text-rose-500 transition-colors">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                                        <template x-for="col in currentFormStep.field.columns" :key="col.key">
                                                            <div>
                                                                <label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1" x-text="col.label"></label>
                                                                <input :aria-label="col.label" :type="col.type || 'text'" x-model="row[col.key]" class="w-full rounded-xl py-1.5 px-2.5 text-xs text-ink-body focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <div class="flex flex-col items-center text-center py-6 rounded-xl border-2 border-dashed border-slate-200" x-show="(formData[currentFormStep.field.key] || []).length === 0">
                                                <span class="w-9 h-9 rounded-full bg-slate-100 text-ink-muted flex items-center justify-center mb-2"><i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-4 h-4"></i></span>
                                                <p class="text-xs text-ink-muted">No entries yet.</p>
                                            </div>

                                            <button
                                                type="button" @click="addTableRow(currentFormStep.field)"
                                                class="w-full flex items-center justify-center gap-1.5 rounded-2xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2.5 transition-all duration-[160ms] ease-standard"
                                            >
                                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                                <span x-text="'Add ' + (currentFormStep.field.rowLabel || 'Entry')"></span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" x-show="!doneSubmit && formStep === 1" @click="open = false">Cancel</x-ui.button>
                <x-ui.button variant="secondary" size="sm" x-show="!doneSubmit && formStep > 1" @click="formStep--">
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>Back</span>
                </x-ui.button>
                <span class="text-xs text-ink-muted mr-auto ml-3" x-show="!doneSubmit && totalFormSteps > 1" x-text="'Step ' + formStep + ' of ' + totalFormSteps"></span>
                <x-ui.button size="sm" x-show="!doneSubmit && formStep < totalFormSteps" @click="formStep++; $nextTick(() => window.renderIcons?.())">
                    <span class="inline-flex items-center gap-1.5">Next<i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></span>
                </x-ui.button>
                <x-ui.button size="sm" x-show="!doneSubmit && formStep >= totalFormSteps" x-bind:disabled="submitting" @click="submit()">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                        <span x-text="submitting ? 'Submitting…' : 'Submit form'"></span>
                    </span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>

    <style>
        .if-paper { background: #fff; color: #111827; max-width: 794px; margin: 0 auto; padding: 36px 40px 32px; box-shadow: 0 1px 3px rgb(15 23 42 / .12); font-family: Arial, Helvetica, sans-serif; font-size: 12.5px; line-height: 1.45; }
        .if-top { display: flex; justify-content: space-between; gap: 12px; min-height: 14px; margin-bottom: 6px; }
        .if-formno { font-size: 10.5px; font-style: italic; color: #374151; margin: 0; white-space: pre-line; }
        .if-corner { font-size: 11px; font-weight: 700; margin: 0; }
        .if-copy-label { text-align: right; font-size: 11px; font-weight: 700; font-style: italic; margin: 0 0 4px; }
        .if-titlebar { position: relative; }
        .if-stamp { position: absolute; right: 0; top: -6px; border: 1px dashed #6b7280; padding: 4px 8px; font-size: 9.5px; text-align: center; min-width: 120px; }
        .if-stamp p { margin: 0; color: #4b5563; }
        .if-subtitle { text-align: center; font-size: 11px; margin: 0 0 2px; }
        .if-statement { margin: 0 0 6px; font-size: 11.5px; white-space: pre-line; }
        .if-statement-end { margin-top: 10px; }
        .if-ticks { display: flex; flex-wrap: wrap; gap: 2px 14px; font-weight: 500 !important; border-bottom: 0 !important; }
        .if-letterhead { text-align: center; line-height: 1.35; }
        .if-letterhead p { margin: 0; }
        .if-lgu { font-weight: 700; }
        .if-office { font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
        .if-title { text-align: center; font-size: 16px; font-weight: 800; letter-spacing: .06em; margin: 14px 0 2px; text-transform: uppercase; }
        .if-ref { text-align: center; font-size: 10.5px; color: #4b5563; margin-bottom: 12px; }
        .if-restricted { border: 1px solid #e5e7eb; padding: 10px; font-size: 11.5px; color: #6b21a8; background: #faf5ff; }
        .if-section { border: 1px solid #111827; margin-top: -1px; padding: 8px 10px 10px; page-break-inside: avoid; break-inside: avoid; }
        .if-section h3 { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; margin: 0 0 6px; }
        .if-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 18px; margin: 0; }
        .if-grid > div { min-width: 0; }
        .if-grid .if-wide { grid-column: 1 / -1; }
        .if-grid dt { font-size: 10.5px; color: #4b5563; }
        .if-grid dd { margin: 0; border-bottom: 1px solid #9ca3af; min-height: 18px; white-space: pre-wrap; overflow-wrap: anywhere; font-weight: 600; }
        .if-table-wrap { margin-top: 8px; overflow-x: auto; }
        .if-table-label { font-size: 10.5px; color: #4b5563; margin: 0 0 3px; }
        .if-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .if-table th, .if-table td { border: 1px solid #111827; padding: 3px 5px; text-align: left; vertical-align: top; }
        .if-table th { font-weight: 700; background: #f3f4f6; font-size: 10px; }
        .if-none { color: #6b7280; font-style: italic; text-align: center !important; }
        .if-office-part h3 { color: #111827; }
        .if-decision { margin: 8px 0 0; font-size: 11.5px; }
        .if-remarks { margin: 2px 0 0; font-size: 11.5px; white-space: pre-wrap; }
        .if-pending { margin: 0; font-size: 11.5px; color: #6b7280; font-style: italic; }
        .if-signs { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 26px 28px; margin-top: 34px; page-break-inside: avoid; break-inside: avoid; }
        .if-sign { text-align: center; }
        .if-sign-name { border-bottom: 1px solid #111827; min-height: 20px; margin: 0; font-weight: 700; text-transform: uppercase; font-size: 11.5px; }
        .if-sign-role { margin: 3px 0 0; font-size: 10.5px; color: #374151; }
        .if-notes { margin: 22px 0 0; padding-left: 16px; font-size: 10px; color: #374151; }
        .if-notes li { margin: 2px 0; }
        @media (max-width: 640px) { .if-paper { padding: 20px 16px; } .if-grid { grid-template-columns: minmax(0, 1fr); } }
        #if-print-root { display: none; }
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            html.if-printing body > *:not(#if-print-root) { display: none !important; }
            html.if-printing #if-print-root { display: block !important; }
            #if-print-root .if-paper { box-shadow: none; padding: 0; max-width: none; }
            #if-print-root .if-paper + .if-paper { break-before: page; page-break-before: always; }
            #if-print-root .if-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</x-layouts.admin>
