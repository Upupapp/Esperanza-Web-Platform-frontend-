{{-- ============================================================
     FILE: modal-lifecycle.blade.php
     Partial: Lifecycle action confirmation modal
     Alpine scope: parent x-data (showLifecycle, lifecycleAction, selectedIndividual, etc.)
     ============================================================ --}}

<div
    x-data="{
        get open() { return showLifecycle; },
        set open(v) { showLifecycle = v; },
        submitting: false,

        form: {
            target_barangay: '',
            transfer_reason: '',
            effective_date: '',
            certifying_officer: '',
            date_of_passing: '',
            cause_of_death: '',
            informant_name: '',
            death_cert_ref: '',
            archive_reason: '',
        },

        resetLifecycleForm() {
            this.form = {
                target_barangay: '', transfer_reason: '', effective_date: '',
                certifying_officer: '', date_of_passing: '', cause_of_death: '',
                informant_name: '', death_cert_ref: '', archive_reason: '',
            };
        },

        async confirmLifecycle() {
            if (this.submitting || !selectedIndividual) return;
            this.submitting = true;
            const action = { Transfer: 'transfer', Deceased: 'deceased', Archive: 'archive' }[lifecycleAction];
            try {
                await $api.post('/admin/residents/' + selectedIndividual.ref + '/lifecycle', { action, ...this.form });
                if (action === 'transfer') {
                    const idx = this.individuals.findIndex(x => x.ref === selectedIndividual.ref);
                    if (idx !== -1) this.individuals[idx].barangay = this.form.target_barangay;
                } else {
                    this.individuals = this.individuals.filter(x => x.ref !== selectedIndividual.ref);
                }
                this.open = false;
                this.resetLifecycleForm();
                $dispatch('toast', {
                    message: 'Lifecycle action recorded: ' + lifecycleAction,
                    variant: 'success'
                });
                window.renderIcons?.();
            } catch (err) {
                $dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not record this action.', variant: 'error' });
            } finally { this.submitting = false; }
        },

        get actionConfig() {
            const cfg = {
                Transfer: {
                    color: 'indigo',
                    icon: 'arrow-right-from-line',
                    title: 'Transfer Resident',
                    desc: 'Move this resident to another barangay within Esperanza. A transfer record will be created and the record will be updated accordingly.',
                    confirmLabel: 'Confirm Transfer',
                },
                Deceased: {
                    color: 'slate',
                    icon: 'flower',
                    title: 'Mark as Deceased',
                    desc: 'Record the passing of this resident. The profile is soft-deleted and retained for historical reference and program reconciliation.',
                    confirmLabel: 'Record Passing',
                },
                Archive: {
                    color: 'amber',
                    icon: 'archive',
                    title: 'Archive Record',
                    desc: 'Archiving soft-deletes this record — it is removed from active views but preserves all data for audit and compliance purposes.',
                    confirmLabel: 'Archive Record',
                },
            };
            return cfg[lifecycleAction] || cfg['Archive'];
        },
    }"
>
    <x-ui.modal maxWidth="sm">

        {{-- ════════════════════════════════════════════════════════
             DYNAMIC HEADER — color and icon change per action
        ════════════════════════════════════════════════════════ --}}
        <div
            class="px-6 pt-6 pb-5 rounded-t-2xl border-b border-brand-600/10 relative overflow-hidden"
            :class="{
                'bg-indigo-50': lifecycleAction === 'Transfer',
                'bg-slate-50': lifecycleAction === 'Deceased',
                'bg-amber-50': lifecycleAction === 'Archive',
                'bg-slate-50': !lifecycleAction,
            }"
        >
            {{-- Decorative blob --}}
            <div
                class="absolute -top-6 -right-6 w-24 h-24 rounded-full opacity-20 pointer-events-none"
                :class="{
                    'bg-indigo-400': lifecycleAction === 'Transfer',
                    'bg-slate-400': lifecycleAction === 'Deceased',
                    'bg-amber-400': lifecycleAction === 'Archive',
                }"
            ></div>

            {{-- Close --}}
            <button
                @click="open = false; resetLifecycleForm()"
                class="absolute top-4 right-4 w-7 h-7 rounded-full flex items-center justify-center text-ink-muted hover:text-slate-600 hover:bg-white/60 transition-colors z-10"
                title="Close"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <div class="flex items-start gap-4 pr-6">
                {{-- Icon --}}
                <div
                    class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm"
                    :class="{
                        'bg-indigo-100 text-indigo-600': lifecycleAction === 'Transfer',
                        'bg-slate-200 text-slate-600': lifecycleAction === 'Deceased',
                        'bg-amber-100 text-amber-600': lifecycleAction === 'Archive',
                    }"
                >
                    <template x-if="lifecycleAction === 'Transfer'">
                        <i data-lucide="arrow-right-from-line" class="w-6 h-6"></i>
                    </template>
                    <template x-if="lifecycleAction === 'Deceased'">
                        <i data-lucide="flower" class="w-6 h-6"></i>
                    </template>
                    <template x-if="lifecycleAction === 'Archive'">
                        <i data-lucide="archive" class="w-6 h-6"></i>
                    </template>
                    <template x-if="!['Transfer','Deceased','Archive'].includes(lifecycleAction)">
                        <i data-lucide="settings-2" class="w-6 h-6"></i>
                    </template>
                </div>

                <div class="min-w-0 pt-0.5">
                    <h2
                        class="text-base font-bold"
                        :class="{
                            'text-indigo-900': lifecycleAction === 'Transfer',
                            'text-slate-700': lifecycleAction === 'Deceased',
                            'text-amber-900': lifecycleAction === 'Archive',
                        }"
                        x-text="actionConfig.title"
                    ></h2>
                    <p class="text-xs mt-1 leading-relaxed text-ink-muted" x-text="actionConfig.desc"></p>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             MODAL BODY
        ════════════════════════════════════════════════════════ --}}
        <div class="px-6 py-5 space-y-5 overflow-y-auto max-h-[60vh]">

            {{-- ── Resident context box ── --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-navy-100 flex items-center justify-center text-navy-700 font-bold text-sm uppercase flex-shrink-0">
                    <span x-text="(selectedIndividual?.name || 'R').split(' ').map(w=>w[0]).slice(0,2).join('')"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-ink truncate" x-text="selectedIndividual?.name || '—'"></p>
                    <p class="text-xs text-ink-muted mt-0.5">
                        ID: <span class="font-mono font-medium text-slate-700" x-text="selectedIndividual?.ref || '—'"></span>
                        <span class="mx-1.5 text-ink-faint">·</span>
                        Brgy. <span x-text="selectedIndividual?.barangay || '—'"></span>
                    </p>
                </div>
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                    :class="statusBadge(selectedIndividual?.status)"
                    x-text="selectedIndividual?.status"
                ></span>
            </div>


            {{-- ── TRANSFER FORM ── --}}
            <div x-show="lifecycleAction === 'Transfer'" class="space-y-4">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="target-barangay">
                        Target Barangay <span class="text-red-500">*</span>
                    </label>
                    <select id="target-barangay"
                        x-model="form.target_barangay"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                        required
                    >
                        <option value="" disabled selected>Select destination barangay</option>
                        @foreach(config('esperanza.barangays') as $brgy)
                            <option value="{{ $brgy }}">{{ $brgy }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-ink-muted mt-1">
                        Select the barangay where this resident will be formally transferred.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="reason-for-transfer">
                        Reason for Transfer
                    </label>
                    <textarea id="reason-for-transfer"
                        x-model="form.transfer_reason"
                        placeholder="Briefly state the reason for this transfer (e.g., marriage, employment relocation, family consolidation…)"
                        rows="3"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition resize-none"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="effective-date">
                            Effective Date
                        </label>
                        <input id="effective-date"
                            type="date"
                            x-model="form.effective_date"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="certifying-officer">
                            Certifying Officer
                        </label>
                        <input id="certifying-officer"
                            type="text"
                            x-model="form.certifying_officer"
                            placeholder="Full name of the barangay officer"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                        />
                    </div>
                </div>

                {{-- Warning note --}}
                <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3 flex items-start gap-2.5 text-xs text-indigo-700">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5 text-indigo-400"></i>
                    <span>
                        This transfer moves the resident's barangay on their record and unlinks their current
                        household and family — they'll need to be re-linked at the receiving barangay.
                    </span>
                </div>

            </div>{{-- /Transfer --}}


            {{-- ── DECEASED FORM ── --}}
            <div x-show="lifecycleAction === 'Deceased'" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="date-of-passing">
                            Date of Passing <span class="text-red-500">*</span>
                        </label>
                        <input id="date-of-passing"
                            type="date"
                            x-model="form.date_of_passing"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="cause-of-death">Cause of Death</label>
                        <input id="cause-of-death"
                            type="text"
                            x-model="form.cause_of_death"
                            placeholder="e.g. Natural causes, illness"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="informant-name">
                        Informant Name
                    </label>
                    <input id="informant-name"
                        type="text"
                        x-model="form.informant_name"
                        placeholder="Name of the person reporting the death"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="psa-death-certificate-reference-no">PSA Death Certificate Reference No.</label>
                    <input id="psa-death-certificate-reference-no"
                        type="text"
                        x-model="form.death_cert_ref"
                        placeholder="e.g. 2026-DC-000123"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition"
                    />
                    <p class="text-xs text-ink-muted mt-1">Leave blank if PSA certificate is not yet available.</p>
                </div>

                {{-- Note --}}
                <div class="bg-slate-100 border border-slate-200 rounded-lg px-4 py-3 flex items-start gap-2.5 text-xs text-slate-600">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5 text-ink-muted"></i>
                    <span>
                        The record will be soft-deleted and removed from active views. It is not permanently erased.
                    </span>
                </div>

            </div>{{-- /Deceased --}}


            {{-- ── ARCHIVE FORM ── --}}
            <div x-show="lifecycleAction === 'Archive'" class="space-y-4">

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                    <i data-lucide="archive" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm text-amber-800">
                        <p class="font-semibold mb-1">Confirm Archive</p>
                        <p class="text-xs leading-relaxed">
                            This resident record will be hidden from all active dashboards, reports, and program eligibility lists.
                            The data is <strong>not deleted</strong> — it is soft-deleted, and a record of who archived it and why is kept in the audit log.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="reason-for-archiving">
                        Reason for Archiving <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason-for-archiving"
                        x-model="form.archive_reason"
                        placeholder="Provide a reason (e.g., resident migrated permanently, duplicate record, data entry error…)"
                        rows="4"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-ink placeholder-slate-400 bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none"
                        required
                    ></textarea>
                    <p class="text-xs text-ink-muted mt-1">This reason will be recorded in the audit log and is required by LGU data governance policy.</p>
                </div>

            </div>{{-- /Archive --}}

        </div>{{-- /body --}}


        {{-- ════════════════════════════════════════════════════════
             FOOTER ACTIONS
        ════════════════════════════════════════════════════════ --}}
        <div class="px-6 py-4 border-t border-slate-200 bg-white rounded-b-2xl flex items-center justify-between gap-3">

            <button
                @click="open = false; resetLifecycleForm()"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-white/60 transition-colors"
            >
                Cancel
            </button>

            {{-- Confirm button — color shifts per action --}}
            <button
                @click="confirmLifecycle()"
                :disabled="submitting || (lifecycleAction === 'Transfer' && !form.target_barangay) || (lifecycleAction === 'Deceased' && !form.date_of_passing) || (lifecycleAction === 'Archive' && form.archive_reason.trim().length < 5)"
                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-white text-sm font-semibold transition-colors shadow-sm disabled:opacity-60"
                :class="{
                    'bg-indigo-600 hover:bg-indigo-700': lifecycleAction === 'Transfer',
                    'bg-slate-700 hover:bg-slate-800': lifecycleAction === 'Deceased',
                    'bg-amber-500 hover:bg-amber-600': lifecycleAction === 'Archive',
                }"
            >
                <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="submitting"></i>
                <template x-if="!submitting">
                    <template x-if="lifecycleAction === 'Transfer'">
                        <i data-lucide="arrow-right-from-line" class="w-4 h-4"></i>
                    </template>
                </template>
                <template x-if="!submitting">
                    <template x-if="lifecycleAction === 'Deceased'">
                        <i data-lucide="flower" class="w-4 h-4"></i>
                    </template>
                </template>
                <template x-if="!submitting">
                    <template x-if="lifecycleAction === 'Archive'">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                    </template>
                </template>

                <span x-text="submitting ? 'Saving…' : actionConfig.confirmLabel"></span>
            </button>

        </div>

    </x-ui.modal>
</div>
