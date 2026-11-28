{{-- modal-dq-detail.blade.php — Data Quality Issue Detail Modal --}}
{{-- Rendered inside constituents.blade.php Alpine x-data scope --}}
{{-- Open state bound to: showDQDetail --}}

<div x-data="{
    get open() { return showDQDetail }, set open(v) { showDQDetail = v },
    assignEncoderId: '',
    resolutionNote: '',
}" x-init="$watch('showDQDetail', v => { if (v) { assignEncoderId = ''; resolutionNote = ''; } })">
    {{-- Bare, as the account review is: the window draws its own header,
         close button and footer (QA sweep 2026-11-18: the modal added an
         empty title bar, a second X, and scrolled the footer away). --}}
    <x-ui.modal maxWidth="xl" :bare="true">

        {{-- ── MODAL HEADER ────────────────────────────────────────────────── --}}
        <div class="flex items-start justify-between gap-4 p-6 pb-4 border-b border-brand-600/10">
            <div class="flex-1 min-w-0">

                {{-- Title row --}}
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <h2 class="text-base font-bold text-ink tracking-tight font-mono" x-text="selectedDQIssue?.ref"></h2>

                    {{-- Type chip --}}
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1"
                        :class="{
                            'text-rose-700 bg-rose-50 ring-rose-200':       selectedDQIssue?.type === 'Duplicate Suspected',
                            'text-orange-700 bg-orange-50 ring-orange-200': selectedDQIssue?.type === 'Orphaned Record',
                            'text-amber-700 bg-amber-50 ring-amber-200':    selectedDQIssue?.type === 'Incomplete Profile',
                        }"
                        x-text="selectedDQIssue?.type"
                    ></span>

                    {{-- Severity badge --}}
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1"
                        :class="selectedDQIssue ? severityBadge(selectedDQIssue.severity) : ''"
                        x-text="selectedDQIssue?.severity"
                    ></span>
                </div>

                {{-- Meta row --}}
                <div class="flex flex-wrap items-center gap-3 text-xs text-ink-muted">
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                        <span x-text="selectedDQIssue?.barangay"></span>
                    </span>
                    <span class="text-ink-faint">·</span>
                    <span class="flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3"></i>
                        Assigned to <span class="font-medium text-slate-700 ml-1" x-text="selectedDQIssue?.assigned_to || 'Unassigned'"></span>
                    </span>
                    <span class="text-ink-faint">·</span>
                    <span class="flex items-center gap-1">
                        <i data-lucide="calendar" class="w-3 h-3"></i>
                        Created <span class="font-medium text-slate-700 ml-1" x-text="selectedDQIssue?.created ? new Date(selectedDQIssue.created).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></span>
                    </span>
                </div>
            </div>

            {{-- Close X --}}
            <button
                @click="showDQDetail = false"
                class="p-1.5 rounded-xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition shrink-0"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- ── MODAL BODY ──────────────────────────────────────────────────── --}}
        <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh]">

            {{-- ── SECTION 1: Record Context ──────────────────────────────── --}}
            <section>
                <h3 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-2">
                    <i data-lucide="file-user" class="w-3.5 h-3.5"></i>
                    Record Context
                </h3>

                {{-- Highlighted record box --}}
                <div class="rounded-xl bg-navy-900/[0.03] border border-navy-900/10 p-4 space-y-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-ink-muted font-medium">Record ID</span>
                            <code
                                class="px-2 py-0.5 rounded bg-slate-100 text-[11px] font-mono font-semibold text-ink"
                                x-text="selectedDQIssue?.record"
                            ></code>
                        </div>
                        <span class="text-ink-faint">·</span>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-ink-muted font-medium">Record Name</span>
                            <span
                                class="text-sm font-semibold text-ink"
                                x-text="dqRecordName(selectedDQIssue?.record) || '—'"
                            ></span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="pt-3 border-t border-slate-200/60">
                        <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-1.5">Description</p>
                        <p
                            class="text-sm text-slate-700 leading-relaxed"
                            x-text="selectedDQIssue?.description || 'No description provided.'"
                        ></p>
                    </div>
                </div>
            </section>

            {{-- ── SECTION 2: Issue Details ────────────────────────────────── --}}
            <section>
                <h3 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                    Issue Details
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-px bg-slate-100 rounded-xl overflow-hidden border border-brand-600/10">

                    {{-- Type --}}
                    <div class="bg-white px-4 py-3">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Type</dt>
                        <dd
                            class="text-xs font-semibold text-ink"
                            x-text="selectedDQIssue?.type"
                        ></dd>
                    </div>

                    {{-- Severity --}}
                    <div class="bg-white px-4 py-3">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Severity</dt>
                        <dd>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1"
                                :class="selectedDQIssue ? severityBadge(selectedDQIssue.severity) : ''"
                                x-text="selectedDQIssue?.severity"
                            ></span>
                        </dd>
                    </div>

                    {{-- Barangay --}}
                    <div class="bg-white px-4 py-3">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Barangay</dt>
                        <dd
                            class="text-xs font-medium text-slate-700"
                            x-text="selectedDQIssue?.barangay"
                        ></dd>
                    </div>

                    {{-- Assigned To + Assign action --}}
                    <div class="bg-white px-4 py-3 col-span-2 md:col-span-1">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Assigned To</dt>
                        <dd class="flex items-center gap-1.5 mb-2">
                            <span
                                class="w-5 h-5 rounded-full bg-brand-100 text-brand-700 text-[9px] font-bold flex items-center justify-center uppercase shrink-0"
                                x-text="selectedDQIssue?.assigned_to ? selectedDQIssue.assigned_to.charAt(0) : '?'"
                            ></span>
                            <span
                                class="text-xs font-medium text-slate-700"
                                x-text="selectedDQIssue?.assigned_to || 'Unassigned'"
                            ></span>
                        </dd>
                        <div x-show="can('const_data_quality','assign') && selectedDQIssue?.status !== 'Resolved'" class="flex items-center gap-1.5">
                            <select aria-label="Assign to encoder" x-model="assignEncoderId"
                                class="min-w-0 flex-1 text-[11px] rounded-xl py-1 px-1.5 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="">Assign to…</option>
                                <template x-for="e in encoders" :key="e.id">
                                    <option :value="e.id" x-text="e.name + ' (' + e.employee_id + ')'"></option>
                                </template>
                            </select>
                            <button
                                @click="assignDQIssue(selectedDQIssue, assignEncoderId); assignEncoderId = ''"
                                :disabled="!assignEncoderId"
                                class="shrink-0 px-2 py-1 rounded-xl text-[11px] font-medium text-brand-700 bg-brand-50 hover:bg-brand-100 ring-1 ring-brand-200 transition disabled:opacity-40 disabled:pointer-events-none"
                            >Go</button>
                        </div>
                    </div>

                    {{-- Created --}}
                    <div class="bg-white px-4 py-3">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Created</dt>
                        <dd
                            class="text-xs text-slate-700"
                            x-text="selectedDQIssue?.created ? new Date(selectedDQIssue.created).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"
                        ></dd>
                    </div>

                    {{-- Current Status --}}
                    <div class="bg-white px-4 py-3">
                        <dt class="text-[10px] font-semibold text-ink-muted uppercase tracking-wider mb-1">Status</dt>
                        <dd>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1"
                                :class="{
                                    'text-rose-700 bg-rose-50 ring-rose-200':           selectedDQIssue?.status === 'Open',
                                    'text-amber-700 bg-amber-50 ring-amber-200':        selectedDQIssue?.status === 'In Review',
                                    'text-emerald-700 bg-emerald-50 ring-emerald-200':  selectedDQIssue?.status === 'Resolved',
                                }"
                                x-text="selectedDQIssue?.status"
                            ></span>
                        </dd>
                    </div>

                </div>
            </section>

            {{-- ── SECTION 3: Merge Candidate (only if present) ────────────── --}}
            <section x-show="selectedDQIssue?.merge_candidate" x-cloak>
                <h3 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-2">
                    <i data-lucide="git-merge" class="w-3.5 h-3.5 text-rose-500"></i>
                    Possible Duplicate Found
                </h3>

                <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 space-y-4">

                    {{-- Candidate card --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] text-rose-600 font-semibold uppercase tracking-wider mb-1">
                                {{-- The older record this one repeats (#15). --}}
                                Original Record (older)
                            </p>
                            <p class="text-sm font-bold text-ink" x-text="dqRecordName(selectedDQIssue?.merge_candidate) || '—'"></p>
                            <p class="text-xs text-ink-muted font-mono mt-0.5" x-text="selectedDQIssue?.merge_candidate"></p>
                        </div>
                    </div>

                    {{-- Open merge review CTA --}}
                    <div class="pt-3 border-t border-rose-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <p class="text-xs text-rose-700">
                            <i data-lucide="triangle-alert" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i>
                            Compare both records before merging. The merged-away record can be restored by a System Administrator.
                        </p>
                        <button
                            x-show="can('const_data_quality','merge')"
                            @click="showMerge = true; showDQDetail = false; $nextTick(() => window.renderIcons?.())"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-rose-600 text-white hover:bg-rose-700 transition shadow-sm shrink-0"
                        >
                            <i data-lucide="git-merge" class="w-3.5 h-3.5"></i>
                            Open merge review
                        </button>
                    </div>

                </div>
            </section>

            {{-- ── SECTION 4: Resolve ───────────────────────────────────────── --}}
            <section x-show="can('const_data_quality','resolve') && selectedDQIssue?.status !== 'Resolved'" x-cloak>
                <h3 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-2">
                    <i data-lucide="circle-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                    Resolve This Issue
                </h3>
                <textarea aria-label="Resolution note" x-model="resolutionNote" rows="2"
                    placeholder="What was done to resolve this issue? (required, min. 3 characters)"
                    class="w-full text-sm rounded-xl p-3 placeholder-slate-400 text-ink focus:outline-none transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                ></textarea>
            </section>

        </div>

        {{-- ── MODAL FOOTER ────────────────────────────────────────────────── --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl">

            {{-- Left: Close --}}
            <button
                @click="showDQDetail = false"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-medium rounded-xl border border-slate-200 text-slate-600 bg-white hover:bg-white/60 transition"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i> Close
            </button>

            {{-- Right: Mark Resolved --}}
            <button
                x-show="can('const_data_quality','resolve') && selectedDQIssue?.status !== 'Resolved'"
                @click="resolveDQIssue(selectedDQIssue, resolutionNote); resolutionNote = ''; showDQDetail = false"
                :disabled="resolutionNote.trim().length < 3"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm disabled:opacity-50 disabled:pointer-events-none"
            >
                <i data-lucide="circle-check" class="w-3.5 h-3.5"></i> Mark resolved
            </button>

        </div>

    </x-ui.modal>
</div>
