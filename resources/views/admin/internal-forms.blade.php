@php
    $offices = config('esperanza_internal_forms.offices');
    $office = $office ?? 'hr';
    $submittedForms = config('esperanza_internal_forms.submissions');
@endphp

<x-layouts.admin title="Internal Forms" subtitle="Staff self-service forms for HR, GSO, Agriculture, and MSWD." active="internal-forms">
    <div
        x-data="{
            office: '{{ $office }}',
            offices: @js($offices),
            tab: 'all',
            search: '',
            submitted: @js($submittedForms),
            doneStatuses: ['Completed', 'Released', 'Rejected'],
            open: false,
            selectedForm: null,
            formData: {},
            attachments: {},
            showFilePicker: null,
            submitting: false,
            doneSubmit: false,
            formStep: 1,
            // Auto-splits a form's fields into logical steps: each repeatable
            // table gets its own step, plain fields are chunked in groups of
            // 6, and a Requirements/Attachments step is appended when present.
            // A short form (few fields, no requirements) naturally resolves
            // to a single step, so it renders exactly like a flat form.
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
                if ((this.selectedForm.requirements || []).length) steps.push({ type: 'requirements', label: 'Requirements & Attachments' });
                const fieldSteps = steps.filter(s => s.type === 'fields');
                fieldSteps.forEach((s, i) => { s.label = fieldSteps.length > 1 ? `Form Details — Part ${i + 1}` : 'Form Details'; });
                return steps;
            },
            get totalFormSteps() { return this.formSteps.length; },
            get currentFormStep() { return this.formSteps[this.formStep - 1] || { type: 'fields', fields: [] }; },
            get currentOffice() { return this.offices[this.office]; },
            // Only Super Administrator, Municipal Administrator, and Department
            // Heads (who review/approve their department's submissions) can see
            // everyone's forms. Every other role only sees their own — no
            // account means an unauthenticated preview, which fails open like
            // the rest of the admin portal's mock RBAC.
            get isAuthority() {
                const role = this.$store.session.account?.roleKey;
                return !this.$store.session.account || ['super_admin', 'municipal_admin', 'department_head'].includes(role);
            },
            get filteredForms() {
                const q = this.search.trim().toLowerCase();
                return (this.currentOffice?.forms || []).map((f, i) => ({ ...f, i })).filter(f => !q || f.name.toLowerCase().includes(q));
            },
            get filteredSubmitted() {
                return this.submitted.filter(s => {
                    if (!this.isAuthority && s.submittedBy !== this.$store.session.account?.name) return false;
                    if (this.tab === 'action') return !this.doneStatuses.includes(s.status);
                    if (this.tab === 'done') return this.doneStatuses.includes(s.status);
                    return true;
                });
            },
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
            reset() { this.open = false; this.selectedForm = null; this.formData = {}; this.attachments = {}; this.showFilePicker = null; this.doneSubmit = false; this.submitting = false; this.formStep = 1; },
            submit() {
                if (this.submitting) return;
                this.submitting = true;
                setTimeout(() => {
                    this.submitting = false;
                    this.doneSubmit = true;
                    this.submitted.unshift({
                        ref: 'IF-2026-' + Math.floor(1000 + Math.random() * 9000),
                        officeKey: this.office,
                        office: this.currentOffice?.shortLabel,
                        form: this.selectedForm?.name,
                        submittedBy: this.$store.session.account?.name || 'Staff Member',
                        submitted: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                        status: 'Submitted',
                    });
                    setTimeout(() => { this.open = false; this.reset(); $dispatch('toast', { message: 'Form submitted — this is a frontend preview, no data is saved.', variant: 'success' }); }, 1400);
                }, 700);
            },
        }"
        x-effect="!open && reset()"
        class="animate-fade-up"
    >
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
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Total Submissions" value="{{ count($submittedForms) }}" icon="clipboard-list" color="brand" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="2" icon="inbox" color="orange" :delay="40" />
            <x-ui.stat-card label="Offices Covered" value="{{ count($offices) }}" icon="building-2" color="purple" :delay="80" />
            <x-ui.stat-card label="Forms Available" value="{{ collect($offices)->sum(fn ($o) => count($o['forms'])) }}" icon="file-stack" color="green" :delay="120" />
        </div>

        <!-- Office tabs + search -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                @foreach($offices as $key => $o)
                    <button
                        type="button" @click="office = '{{ $key }}'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                        :class="office === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    ><i data-lucide="{{ $o['icon'] }}" class="w-3.5 h-3.5"></i>{{ $o['shortLabel'] }}</button>
                @endforeach
            </div>
            <div class="relative">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" x-model="search" placeholder="Search forms..." class="w-full max-w-sm pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
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
                        <span class="text-[10px] font-medium text-slate-400" x-text="f.days"></span>
                    </div>
                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="f.name"></p>
                    <p class="text-xs text-slate-400 mt-1 truncate" x-text="f.formNo"></p>
                </button>
            </template>
            <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl" x-show="filteredForms.length === 0">
                <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                <p class="text-sm font-medium text-slate-600">No forms match this search.</p>
            </div>
        </div>

        <!-- My Submitted Forms -->
        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-navy-900" x-text="isAuthority ? 'Recent Submissions' : 'My Submissions'"></h3>
            <span class="text-[11px] text-slate-400" x-text="filteredSubmitted.length + (isAuthority ? ' total' : ' submitted by you')"></span>
        </div>
        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Form</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Submitted By</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Date</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="s in filteredSubmitted" :key="s.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="s.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="s.form"></td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="s.office"></td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="s.submittedBy"></td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell" x-text="s.submitted"></td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                :class="{
                                    'bg-blue-50 text-blue-700 ring-blue-200': s.status === 'Submitted',
                                    'bg-amber-50 text-amber-700 ring-amber-200': s.status === 'Pending Review',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-200': s.status === 'Approved',
                                    'bg-cyan-50 text-cyan-700 ring-cyan-200': s.status === 'Released',
                                    'bg-green-50 text-green-700 ring-green-200': s.status === 'Completed',
                                    'bg-rose-50 text-rose-700 ring-rose-200': s.status === 'Rejected',
                                }"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="{
                                        'bg-blue-500': s.status === 'Submitted',
                                        'bg-amber-500': s.status === 'Pending Review',
                                        'bg-emerald-500': s.status === 'Approved',
                                        'bg-cyan-500': s.status === 'Released',
                                        'bg-green-500': s.status === 'Completed',
                                        'bg-rose-500': s.status === 'Rejected',
                                    }"
                                ></span>
                                <span x-text="s.status"></span>
                            </span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredSubmitted.length === 0">
            <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-5 h-5"></i></span>
            <p class="text-sm font-medium text-slate-600">No submissions in this view.</p>
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
                        <p class="text-xs text-slate-400 mt-1 max-w-[240px]">Your submission has been logged and routed for processing.</p>
                    </div>

                    <div x-show="!doneSubmit">
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-slate-100">
                            <span class="w-10 h-10 rounded-lg bg-brand-600 text-white flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4.5 h-4.5"></i></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy-900 truncate" x-text="selectedForm.name"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="selectedForm.formNo"></p>
                            </div>
                        </div>

                        {{-- Step progress indicator — only shown when the form actually needed splitting --}}
                        <nav x-show="totalFormSteps > 1" x-cloak class="flex items-center gap-0 mb-5" aria-label="Form steps">
                            <template x-for="(step, idx) in formSteps" :key="idx">
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="flex items-center w-full">
                                        <div class="flex-1 h-0.5" :class="formStep > idx + 1 ? 'bg-brand-500' : (idx === 0 ? 'bg-transparent' : 'bg-slate-200')"></div>
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all flex-shrink-0"
                                            :class="formStep >= idx + 1 ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white text-slate-400'"
                                        >
                                            <template x-if="formStep > idx + 1"><i data-lucide="check" class="w-3.5 h-3.5"></i></template>
                                            <template x-if="formStep <= idx + 1"><span x-text="idx + 1"></span></template>
                                        </div>
                                        <div class="flex-1 h-0.5" :class="[formStep > idx + 1 ? 'bg-brand-500' : 'bg-slate-200', idx === totalFormSteps - 1 ? '!bg-transparent' : '']"></div>
                                    </div>
                                    <span class="text-[10px] mt-1.5 font-medium text-center px-1 leading-tight" :class="formStep === idx + 1 ? 'text-brand-600' : formStep > idx + 1 ? 'text-brand-500' : 'text-slate-400'" x-text="step.label"></span>
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
                                                <input
                                                    :type="f.type" x-model="formData[f.key]"
                                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'select'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                <select x-model="formData[f.key]" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
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
                                            <span class="text-[11px] text-slate-400" x-text="(formData[currentFormStep.field.key] || []).length + ' added'"></span>
                                        </div>
                                        <div class="space-y-2.5">
                                            <template x-for="(row, ri) in (formData[currentFormStep.field.key] || [])" :key="ri">
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 bg-brand-50 rounded-full pl-1.5 pr-2.5 py-1">
                                                            <i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-3 h-3"></i>
                                                            <span x-text="row[currentFormStep.field.columns[0].key] || ((currentFormStep.field.rowLabel || 'Entry') + ' ' + (ri + 1))"></span>
                                                        </span>
                                                        <button type="button" @click="formData[currentFormStep.field.key].splice(ri, 1)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                                        <template x-for="col in currentFormStep.field.columns" :key="col.key">
                                                            <div>
                                                                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1" x-text="col.label"></label>
                                                                <input type="text" x-model="row[col.key]" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <div class="flex flex-col items-center text-center py-6 rounded-xl border-2 border-dashed border-slate-200" x-show="(formData[currentFormStep.field.key] || []).length === 0">
                                                <span class="w-9 h-9 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-2"><i :data-lucide="currentFormStep.field.rowIcon || 'list'" class="w-4 h-4"></i></span>
                                                <p class="text-xs text-slate-400">No entries yet.</p>
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

                            {{-- Requirements step --}}
                            <div x-show="currentFormStep.type === 'requirements'">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2" x-show="totalFormSteps === 1">Requirements</p>
                                <div class="space-y-2">
                                    <template x-for="(req, i) in selectedForm.requirements || []" :key="i">
                                        <div class="rounded-xl border p-3 transition-colors duration-[160ms] ease-standard" :class="attachments[i] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-xs text-slate-600 flex-1" x-text="req"></p>
                                                <button type="button" x-show="!attachments[i]" @click="attachments[i] = 'Attached.pdf'" class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap">Attach File</button>
                                                <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap"><i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span x-text="attachments[i]"></span></span>
                                                    <button type="button" @click="attachments[i] = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
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
                <span class="text-xs text-slate-400 mr-auto ml-3" x-show="!doneSubmit && totalFormSteps > 1" x-text="'Step ' + formStep + ' of ' + totalFormSteps"></span>
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
