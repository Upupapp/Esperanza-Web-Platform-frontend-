<x-layouts.admin title="Internal Forms" subtitle="Staff self-service forms for HR, GSO, Agriculture, and MSWD." active="internal-forms">
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
            async init() {
                this.loading = true;
                this.failed = false;
                try {
                    const [defsRes, subsRes] = await Promise.all([
                        $api.get('/admin/internal-forms'),
                        $api.getAll('/admin/internal-forms/submissions'),
                    ]);
                    const grouped = {};
                    (defsRes.data || []).forEach(d => {
                        if (!grouped[d.office_key]) grouped[d.office_key] = { shortLabel: d.office, icon: this.iconMap[d.office_key] || 'building-2', forms: [] };
                        grouped[d.office_key].forms.push(d);
                    });
                    this.offices = grouped;
                    this.officeKey = Object.keys(grouped)[0] || null;
                    this.totalForms = (defsRes.data || []).length;
                    this.submissions = subsRes.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
            },
            // Auto-splits a form's fields into logical steps: each repeatable
            // table gets its own step, plain fields are chunked in groups of
            // 6. A short form (few fields) naturally resolves to a single
            // step, so it renders exactly like a flat form.
            get formSteps() {
                if (!this.selectedForm) return [];
                const steps = [];
                let bucket = [];
                const flush = () => { if (bucket.length) { steps.push({ type: 'fields', fields: bucket }); bucket = []; } };
                (this.selectedForm.fields || []).forEach(f => {
                    if (f.type === 'table') {
                        flush();
                        steps.push({ type: 'table', field: f, label: f.label });
                    } else {
                        bucket.push(f);
                        if (bucket.length >= 6) flush();
                    }
                });
                flush();
                if (!steps.length) steps.push({ type: 'fields', fields: [] });
                const fieldSteps = steps.filter(s => s.type === 'fields');
                fieldSteps.forEach((s, i) => { s.label = fieldSteps.length > 1 ? `Form Details — Part ${i + 1}` : 'Form Details'; });
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
            deciding: false,
            startDecision(s, kind) {
                this.decision = { s, kind };
                this.decisionRemarks = '';
            },
            async confirmDecision() {
                const d = this.decision;
                if (!d || this.deciding) return;
                if (d.kind === 'reject' && !this.decisionRemarks.trim()) return;
                this.deciding = true;
                try {
                    const res = await $api.post('/admin/internal-forms/submissions/' + encodeURIComponent(d.s.ref) + '/decide', {
                        decision: d.kind,
                        remarks: this.decisionRemarks.trim() || undefined,
                    });
                    const i = this.submissions.findIndex((x) => x.ref === d.s.ref);
                    if (i !== -1) this.submissions.splice(i, 1, { ...this.submissions[i], ...res.data });
                    this.decision = null;
                    this.$dispatch('toast', {
                        message: d.s.ref + (d.kind === 'approve' ? ' approved' : ' rejected') + ' — now ' + res.data.status + '.',
                        variant: d.kind === 'approve' ? 'success' : 'error',
                    });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not record the decision.', variant: 'error' });
                } finally { this.deciding = false; }
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
        <div x-show="isAuthority" x-cloak class="flex items-center gap-2.5 rounded-xl bg-brand-50 border border-brand-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="shield-check" class="w-4 h-4 text-brand-500 shrink-0"></i>
            <p class="text-xs text-brand-700">Full visibility — as <strong x-text="$store.session.account?.role || 'an administrator'"></strong>, you can see every staff member's submissions across all offices.</p>
        </div>
        <div x-show="!isAuthority" x-cloak class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="lock" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Personal view — you can only see forms <strong>you</strong> submitted. Other staff members' submissions are hidden.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Total Submissions" valueBind="submissions.length" icon="clipboard-list" color="brand" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" valueBind="needingAction" icon="inbox" color="orange" :delay="40" />
            <x-ui.stat-card label="Offices Covered" valueBind="Object.keys(offices).length" icon="building-2" color="purple" :delay="80" />
            <x-ui.stat-card label="Forms Available" valueBind="totalForms" icon="file-stack" color="green" :delay="120" />
        </div>

        <!-- Office tabs + search -->
        <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                <template x-for="(o, key) in offices" :key="key">
                    <button
                        type="button" @click="officeKey = key"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                        :class="officeKey === key ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    ><i :data-lucide="o.icon" class="w-3.5 h-3.5"></i><span x-text="o.shortLabel"></span></button>
                </template>
            </div>
            <div class="relative">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search forms" type="text" x-model="search" placeholder="Search forms..." class="w-full max-w-sm pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
            </div>
        </div>

        <!-- Form cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
            <template x-for="f in filteredForms" :key="f.key">
                <button
                    type="button" @click="selectForm(f)"
                    class="text-left bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard p-4"
                >
                    <div class="flex items-start justify-between mb-2.5">
                        <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                        <span x-show="f.sensitive" class="text-[10px] font-medium text-purple-600 bg-purple-50 rounded-full px-2 py-0.5">Sensitive</span>
                    </div>
                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="f.form"></p>
                    <p class="text-xs text-slate-500 mt-1 truncate" x-text="f.print_template || ''"></p>
                </button>
            </template>
            <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl" x-show="filteredForms.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600">No forms match this search.</p>
            </div>
        </div>

        <!-- My Submitted Forms -->
        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-navy-900" x-text="isAuthority ? 'Recent Submissions' : 'My Submissions'"></h3>
            <span class="text-[11px] text-slate-500" x-text="filteredSubmitted.length + (isAuthority ? ' total' : ' submitted by you')"></span>
        </div>
        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Form</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell">Submitted By</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Date</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="s in filteredSubmitted" :key="s.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="s.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="s.form"></td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="s.office"></td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="s.submitted_by"></td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell" x-text="s.submitted ? new Date(s.submitted).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></td>
                        <td class="px-4 py-3">
                            <x-ui.badge statusBind="s.status" />
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div x-show="s.can_decide" class="inline-flex items-center gap-1.5">
                                <button type="button" @click="startDecision(s, 'approve')" class="text-xs font-semibold text-emerald-700 hover:underline">Approve</button>
                                <span class="text-slate-200" aria-hidden="true">·</span>
                                <button type="button" @click="startDecision(s, 'reject')" class="text-xs font-semibold text-rose-600 hover:underline">Reject</button>
                            </div>
                            <span x-show="!s.can_decide && s.step" class="text-[11px] text-slate-500" x-text="'Waiting on ' + (s.step?.office || s.step?.label || 'the next step')"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredSubmitted.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No submissions in this view.</p>
        </div>
        </div>

        <!-- Decide on the waiting step -->
        <div x-show="decision" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
             @click.self="decision = null" @keydown.escape.window="decision = null"
             role="dialog" aria-modal="true" aria-labelledby="if-decide-title">
            <div class="bg-white rounded-2xl shadow-float w-full max-w-md p-6 space-y-4">
                <div>
                    <h3 id="if-decide-title" class="text-base font-semibold text-navy-900" x-text="(decision?.kind === 'approve' ? 'Approve ' : 'Reject ') + (decision?.s.ref || '')"></h3>
                    <p class="text-xs text-slate-500 mt-1" x-text="(decision?.s.form || '') + (decision?.s.step ? ' · Step ' + decision.s.step.number + (decision.s.step.label ? ': ' + decision.s.step.label : '') : '')"></p>
                </div>
                <label class="block">
                    <span class="text-xs font-medium text-slate-600" x-text="decision?.kind === 'reject' ? 'Reason (required)' : 'Remarks (optional)'"></span>
                    <textarea x-model="decisionRemarks" rows="3" class="mt-1 w-full text-sm rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="decision = null" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Cancel</button>
                    <button type="button" @click="confirmDecision()"
                        :disabled="deciding || (decision?.kind === 'reject' && !decisionRemarks.trim())"
                        :class="decision?.kind === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        class="px-4 py-2 text-xs font-semibold text-white rounded-xl disabled:opacity-40"
                        x-text="deciding ? 'Saving…' : (decision?.kind === 'approve' ? 'Confirm Approval' : 'Confirm Rejection')"></button>
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
                        <p class="text-sm font-semibold text-navy-900">Form Submitted!</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-[240px]">Your submission has been logged and routed for processing.</p>
                    </div>

                    <div x-show="!doneSubmit">
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-slate-100">
                            <span class="w-10 h-10 rounded-lg bg-brand-600 text-white flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4.5 h-4.5"></i></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy-900 truncate" x-text="selectedForm.form"></p>
                                <p class="text-xs text-slate-500 truncate" x-text="selectedForm.print_template || ''"></p>
                            </div>
                        </div>

                        {{-- Approval chain, from the form's own real definition --}}
                        <div x-show="(selectedForm.approval_chain || []).length" x-cloak class="flex flex-wrap items-center gap-1.5 mb-5 text-[11px] text-slate-500">
                            <span class="font-medium text-slate-600">Routes to:</span>
                            <template x-for="(step, idx) in (selectedForm.approval_chain || [])" :key="step.step">
                                <span class="inline-flex items-center gap-1.5">
                                    <span x-show="idx > 0" class="text-slate-300">→</span>
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
                                            :class="formStep >= idx + 1 ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white text-slate-500'"
                                        >
                                            <template x-if="formStep > idx + 1"><i data-lucide="check" class="w-3.5 h-3.5"></i></template>
                                            <template x-if="formStep <= idx + 1"><span x-text="idx + 1"></span></template>
                                        </div>
                                        <div class="flex-1 h-0.5" :class="[formStep > idx + 1 ? 'bg-brand-500' : 'bg-slate-200', idx === totalFormSteps - 1 ? '!bg-transparent' : '']"></div>
                                    </div>
                                    <span class="text-[10px] mt-1.5 font-medium text-center px-1 leading-tight" :class="formStep === idx + 1 ? 'text-brand-600' : formStep > idx + 1 ? 'text-brand-500' : 'text-slate-500'" x-text="step.label"></span>
                                </div>
                            </template>
                        </nav>

                        <div class="max-h-[52vh] overflow-y-auto scrollbar-thin pr-1 space-y-4">

                            {{-- Fields step --}}
                            <div x-show="currentFormStep.type === 'fields'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <template x-for="f in (currentFormStep.fields || [])" :key="f.key">
                                    <div :class="f.type === 'checkbox-group' ? 'sm:col-span-2' : ''">
                                        <template x-if="f.type === 'text' || f.type === 'number' || f.type === 'date'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <input :aria-label="f.label"
                                                    :type="f.type" x-model="formData[f.key]"
                                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'select'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <select :aria-label="f.label" x-model="formData[f.key]" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                    <option value="">—</option>
                                                    <template x-for="opt in f.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                                                </select>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'checkbox-group'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-2" x-text="f.label"></label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <template x-for="opt in f.options" :key="opt">
                                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="(formData[f.key] || []).includes(opt) && 'border-brand-300 bg-brand-50/60 text-brand-700'">
                                                            <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-200" :checked="(formData[f.key] || []).includes(opt)" @change="toggleChecklist(f.key, opt)">
                                                            <span x-text="opt"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            {{-- Table step (repeatable rows) --}}
                            <div x-show="currentFormStep.type === 'table'">
                                <template x-if="currentFormStep.type === 'table'">
                                    <div>
                                        <div class="flex items-center justify-between mb-2.5">
                                            <label class="block text-xs font-medium text-slate-700" x-text="currentFormStep.field.label"></label>
                                            <span class="text-[11px] text-slate-500" x-text="(formData[currentFormStep.field.key] || []).length + ' added'"></span>
                                        </div>
                                        <div class="space-y-2.5">
                                            <template x-for="(row, ri) in (formData[currentFormStep.field.key] || [])" :key="ri">
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 bg-brand-50 rounded-full pl-1.5 pr-2.5 py-1">
                                                            <i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-3 h-3"></i>
                                                            <span x-text="row[currentFormStep.field.columns[0].key] || ((currentFormStep.field.rowLabel || 'Entry') + ' ' + (ri + 1))"></span>
                                                        </span>
                                                        <button aria-label="Remove" type="button" @click="formData[currentFormStep.field.key].splice(ri, 1)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                                        <template x-for="col in currentFormStep.field.columns" :key="col.key">
                                                            <div>
                                                                <label class="block text-[10px] font-medium text-slate-500 uppercase tracking-wide mb-1" x-text="col.label"></label>
                                                                <input :aria-label="col.label" type="text" x-model="row[col.key]" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <div class="flex flex-col items-center text-center py-6 rounded-xl border-2 border-dashed border-slate-200" x-show="(formData[currentFormStep.field.key] || []).length === 0">
                                                <span class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mb-2"><i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-4 h-4"></i></span>
                                                <p class="text-xs text-slate-500">No entries yet.</p>
                                            </div>

                                            <button
                                                type="button" @click="addTableRow(currentFormStep.field)"
                                                class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2.5 transition-all duration-[160ms] ease-standard"
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
                <span class="text-xs text-slate-500 mr-auto ml-3" x-show="!doneSubmit && totalFormSteps > 1" x-text="'Step ' + formStep + ' of ' + totalFormSteps"></span>
                <x-ui.button size="sm" x-show="!doneSubmit && formStep < totalFormSteps" @click="formStep++; $nextTick(() => window.renderIcons?.())">
                    <span class="inline-flex items-center gap-1.5">Next<i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></span>
                </x-ui.button>
                <x-ui.button size="sm" x-show="!doneSubmit && formStep >= totalFormSteps" x-bind:disabled="submitting" @click="submit()">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                        <span x-text="submitting ? 'Submitting…' : 'Submit Form'"></span>
                    </span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.admin>
