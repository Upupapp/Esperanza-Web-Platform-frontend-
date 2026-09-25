{{-- modal-merge.blade.php — Duplicate Merge Review Modal --}}
{{-- Rendered inside constituents.blade.php Alpine x-data scope --}}
{{-- Open state bound to: showMerge --}}

{{-- POST /admin/merges takes only {keep, merge, issue_ref?, note?} -- the whole
     record is kept or archived, there is no field-by-field merge on the backend.
     The mock's per-field radio-button comparison table implied one and is dropped;
     what's here is a real side-by-side of the two actual records so the admin can
     judge which one to keep, plus a whole-record Keep choice. --}}
<div x-data="{
    get open() { return showMerge },
    set open(v) { showMerge = v },

    keep: 'A',
    note: '',
    merging: false,
    loadingRecords: false,

    reset() { this.keep = 'A'; this.note = ''; },

    // The Registry list fetch is intentionally lean (no household/family) --
    // the merge decision needs the fuller record, same as openIndividual().
    async loadRecords() {
        const refs = [selectedDQIssue?.record, selectedDQIssue?.merge_candidate].filter(Boolean);
        if (!refs.length) return;
        this.loadingRecords = true;
        try {
            const results = await Promise.all(refs.map(ref => $api.get('/admin/residents/' + ref).catch(() => null)));
            results.forEach((res, idx) => {
                if (!res) return;
                const i = individuals.findIndex(x => x.ref === refs[idx]);
                if (i !== -1) individuals[i] = { ...individuals[i], ...res.data };
            });
        } finally { this.loadingRecords = false; }
    },

    async confirmMerge() {
        if (this.merging) return;
        const recA = selectedDQIssue?.record;
        const recB = selectedDQIssue?.merge_candidate;
        const keepRef  = this.keep === 'A' ? recA : recB;
        const mergeRef = this.keep === 'A' ? recB : recA;
        if (!keepRef || !mergeRef) return;
        this.merging = true;
        try {
            const result = await confirmDQMerge(keepRef, mergeRef, selectedDQIssue?.ref, this.note.trim() || null);
            this.merging = false;
            this.open = false;
            $dispatch('toast', { message: 'Merged ' + result.merged + ' into ' + result.kept + '. The secondary record has been archived.', variant: 'success' });
        } catch (err) {
            this.merging = false;
            $dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not complete the merge.', variant: 'error' });
        }
    },
}">
    <x-ui.modal maxWidth="2xl">

        {{-- ── MODAL HEADER ────────────────────────────────────────────────── --}}
        <div class="flex items-start justify-between gap-4 p-6 pb-4 border-b border-slate-100">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-rose-100">
                        <i data-lucide="git-merge" class="w-4 h-4 text-rose-600"></i>
                    </span>
                    <h2 class="text-base font-bold text-navy-900 tracking-tight">
                        Duplicate Review &mdash; Merge Preview
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">
                    Review both records side-by-side, choose which one to keep, and confirm.
                    The other record is archived (soft-deleted) and its linked rows repointed to the kept record.
                    A System Administrator can revert this merge if needed — it is not permanent.
                </p>
            </div>

            {{-- Close X --}}
            <button
                @click="showMerge = false"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition shrink-0"
                aria-label="Cancel merge and close"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- ── MODAL BODY ──────────────────────────────────────────────────── --}}
        <div class="p-6 space-y-6 overflow-y-auto max-h-[65vh]"
             x-init="$watch('showMerge', v => { if (v) { reset(); loadRecords(); } })"
        >

            {{-- ── RECORD HEADERS (side-by-side, click to choose Keep) ─────── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Record A --}}
                <button
                    type="button"
                    @click="keep = 'A'"
                    class="text-left rounded-xl border-2 p-4 transition"
                    :class="keep === 'A' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-xs font-bold shrink-0">A</span>
                        <span class="text-xs font-bold text-brand-700 uppercase tracking-wider">Record A</span>
                        <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold ring-1"
                              :class="keep === 'A' ? 'bg-brand-100 text-brand-700 ring-brand-200' : 'bg-slate-100 text-slate-500 ring-slate-200'"
                              x-text="keep === 'A' ? 'Will keep' : 'Will archive'"></span>
                    </div>
                    <p class="text-sm font-bold text-navy-900" x-text="dqRecordName(selectedDQIssue?.record) || '—'"></p>
                    <p class="text-xs font-mono text-slate-500 mt-0.5" x-text="selectedDQIssue?.record || '—'"></p>
                </button>

                {{-- Record B --}}
                <button
                    type="button"
                    @click="keep = 'B'"
                    class="text-left rounded-xl border-2 p-4 transition"
                    :class="keep === 'B' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-rose-500 text-white text-xs font-bold shrink-0">B</span>
                        <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Record B — Suspected Duplicate</span>
                        <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold ring-1"
                              :class="keep === 'B' ? 'bg-brand-100 text-brand-700 ring-brand-200' : 'bg-slate-100 text-slate-500 ring-slate-200'"
                              x-text="keep === 'B' ? 'Will keep' : 'Will archive'"></span>
                    </div>
                    <p class="text-sm font-bold text-navy-900" x-text="dqRecordName(selectedDQIssue?.merge_candidate) || '—'"></p>
                    <p class="text-xs font-mono text-slate-500 mt-0.5" x-text="selectedDQIssue?.merge_candidate || '—'"></p>
                </button>
            </div>

            {{-- ── FIELD COMPARISON (read-only) ─────────────────────────────── --}}
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="columns-2" class="w-3.5 h-3.5"></i>
                    Field-by-Field Comparison
                </h3>

                <div class="rounded-xl overflow-hidden border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider w-32">Field</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-brand-600 uppercase tracking-wider">Record A</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Record B</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">

                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Full Name</td>
                                <td class="px-4 py-2.5 text-sm font-medium text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.name || '—'"></td>
                                <td class="px-4 py-2.5 text-sm font-medium text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.name || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Barangay</td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.barangay || '—'"></td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.barangay || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Sex</td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.sex || '—'"></td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.sex || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Date of Birth</td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.dob || '—'"></td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.dob || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Civil Status</td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.civil_status || '—'"></td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.civil_status || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Contact No.</td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.contact || '—'"></td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.contact || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Household</td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.household?.ref || '—'"></td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.household?.ref || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Family</td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.family?.ref || '—'"></td>
                                <td class="px-4 py-2.5 text-sm font-mono text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.family?.ref || '—'"></td>
                            </tr>
                            <tr class="even:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Verification</td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.record)?.verification || '—'"></td>
                                <td class="px-4 py-2.5 text-sm text-navy-900" x-text="dqRecord(selectedDQIssue?.merge_candidate)?.verification || '—'"></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── NOTE ──────────────────────────────────────────────────────── --}}
            <div>
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 block" for="merge-note">Note (optional)</label>
                <textarea id="merge-note" x-model="note" rows="2"
                    placeholder="Why these are the same person, or anything the next reviewer should know."
                    class="w-full text-sm rounded-lg border border-slate-200 bg-white p-3 placeholder-slate-400 text-navy-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition"
                ></textarea>
            </div>

            {{-- ── WARNING BANNER ───────────────────────────────────────────── --}}
            <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                <i data-lucide="triangle-alert" class="w-4 h-4 mt-0.5 shrink-0 text-amber-600"></i>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-amber-800">This is a significant change.</p>
                    <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                        The archived record's household/family links move to the kept record. This is logged and can be
                        reverted by a System Administrator, but should not be done casually.
                    </p>
                </div>
            </div>

        </div>

        {{-- ── MODAL FOOTER ────────────────────────────────────────────────── --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl">

            {{-- Left: Cancel --}}
            <button
                @click="showMerge = false" x-bind:disabled="merging"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 transition disabled:opacity-50 disabled:pointer-events-none"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel
            </button>

            {{-- Right: Merge confirmation --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                <p class="text-[11px] text-slate-500 text-center sm:text-right hidden sm:block">
                    Keeping <span class="font-semibold text-navy-900" x-text="(keep === 'A' ? dqRecordName(selectedDQIssue?.record) : dqRecordName(selectedDQIssue?.merge_candidate)) || 'record'"></span>
                </p>
                <button
                    @click="confirmMerge()" x-bind:disabled="merging"
                    class="inline-flex items-center justify-center gap-1.5 px-5 py-2 text-xs font-bold rounded-lg bg-rose-600 text-white hover:bg-rose-700 active:bg-rose-800 transition shadow-md shadow-rose-200 shrink-0 disabled:opacity-70 disabled:pointer-events-none"
                >
                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="merging" x-cloak></i>
                    <i data-lucide="git-merge" class="w-3.5 h-3.5" x-show="!merging"></i>
                    <span x-text="merging ? 'Merging…' : 'Proceed with Merge'"></span>
                </button>
            </div>

        </div>

    </x-ui.modal>
</div>
