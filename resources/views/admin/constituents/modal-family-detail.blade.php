{{-- modal-family-detail.blade.php --}}
{{-- Family detail modal — 3 tabs: Overview | Members | Programs --}}

<div x-data="{
    get open() { return showFamilyDetail },
    set open(v) { showFamilyDetail = v },
    editing: false,
    saving: false,
    edit: {},
    startEdit() {
        this.edit = { family_type: selectedFamily.family_type, sitio: selectedFamily.sitio, head_name: selectedFamily.head_name, notes: selectedFamily.notes, programs: [...(selectedFamily.programs || [])] };
        this.editing = true;
    },
    async saveEdit() {
        if (this.saving) return;
        this.saving = true;
        try {
            const res = await $api.put('/admin/families/' + selectedFamily.ref, this.edit);
            Object.assign(selectedFamily, res.data);
            const idx = this.families.findIndex(x => x.ref === selectedFamily.ref);
            if (idx !== -1) this.families[idx] = { ...this.families[idx], ...res.data };
            this.editing = false;
            this.$dispatch('toast', { message: 'Family record updated.', variant: 'success' });
        } catch (err) {
            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save changes.', variant: 'error' });
        } finally { this.saving = false; }
    },
}">
<x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <template x-if="selectedFamily">
        <div class="flex flex-col min-h-0 h-full">

            {{-- ════════════════════════════════════════════════════════
                 MODAL HEADER (dark hero — matches individual/household)
            ════════════════════════════════════════════════════════ --}}
            <div class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden">
                <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

                {{-- Compact toggle + Close --}}
                <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                    <button type="button" @click="compact = !compact" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                        <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                        <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                    </button>
                    <button type="button" @click="showFamilyDetail = false; editing = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex items-start gap-5 relative z-10">
                    <div class="flex-shrink-0 w-20 h-20 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xl uppercase">
                        <span x-text="(selectedFamily?.surname || '').substring(0,2).toUpperCase()"></span>
                    </div>

                    {{-- Identity block --}}
                    <div class="flex-1 min-w-0 pt-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span x-show="selectedFamily?.family_type" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white" x-text="selectedFamily?.family_type"></span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                                <i data-lucide="shield-check" class="w-3 h-3"></i>
                                <span x-text="verificationBadge(selectedFamily?.verification).label"></span>
                            </span>
                            {{-- Verifying here (after actually reviewing the family's members
                                 and details below) rather than from the list row, where nothing
                                 has been reviewed yet. --}}
                            <button
                                type="button"
                                x-show="selectedFamily?.verification !== 'Verified' && can('const_data_quality','verify')"
                                @click="verifyFamily(selectedFamily)"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30 hover:bg-emerald-400/30 transition-colors"
                            >
                                <i data-lucide="check" class="w-3 h-3"></i> Mark as Verified
                            </button>
                            <span x-show="selectedFamily?.household_ref" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30">
                                <i data-lucide="house" class="w-3 h-3"></i>
                                <span x-text="selectedFamily?.household_ref"></span>
                            </span>
                        </div>

                        <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedFamily?.label"></h2>
                        <p class="text-sm text-white/60 mt-0.5">
                            ID: <span class="font-mono font-medium text-white/80" x-text="selectedFamily?.ref"></span>
                            &nbsp;·&nbsp;
                            <span x-text="(selectedFamily?.member_count || 0) + ' members'"></span>
                        </p>

                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span x-text="'Brgy. ' + (selectedFamily?.barangay || '—')"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="user" class="w-3 h-3"></i>
                                <span x-text="selectedFamily?.head_name || 'No head listed'"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════
                 BODY: side nav + scrollable content panel
            ════════════════════════════════════════════════════════ --}}
            <div class="flex-1 min-h-0 flex flex-col sm:flex-row overflow-hidden">

                {{-- ── Side nav ── --}}
                <div class="w-full sm:w-48 shrink-0 flex flex-row sm:flex-col gap-0.5 border-b sm:border-b-0 sm:border-r border-brand-600/10 bg-slate-50/60 p-2 sm:p-2.5 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto scrollbar-thin">
                    <template x-for="tab in [
                        {id:'overview',icon:'layout-dashboard',label:'Overview'},
                        {id:'members',icon:'users',label:'Members',count:selectedFamily?.member_count},
                        {id:'programs',icon:'heart-handshake',label:'Programs'}
                    ]" :key="tab.id">
                        <button
                            @click="familyTab = tab.id"
                            class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-xl text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                            :class="familyTab === tab.id ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-ink-muted hover:bg-white/70 hover:text-slate-700'"
                        >
                            <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="familyTab === tab.id ? 'text-brand-600' : 'text-ink-muted'"></i>
                            <span class="flex-1 min-w-0 sm:truncate" x-text="tab.label"></span>
                            <span
                                x-show="tab.count"
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                :class="familyTab === tab.id ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-ink-muted'"
                                x-text="tab.count"
                            ></span>
                        </button>
                    </template>
                </div>

                {{-- ── Content panel ── --}}
                <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50 space-y-4">

                {{-- ── OVERVIEW TAB ── --}}
                <div x-show="familyTab === 'overview'" x-cloak>
                    <template x-if="!editing">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Left column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-3">Family Details</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Family Type</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedFamily.family_type || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Barangay</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedFamily.barangay"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Sitio / Purok</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedFamily.sitio || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Household</dt>
                                        <dd class="text-xs font-semibold text-blue-600" x-text="selectedFamily.household_ref || 'Unlinked'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Head of Family</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedFamily.head_name || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-ink-muted">Member Count</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedFamily.member_count"></dd>
                                    </div>
                                </dl>
                            </x-ui.card>
                        </div>

                        {{-- Right column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-3">Status & Verification</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-ink-muted">Record Status</dt>
                                        <dd>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="statusBadge(selectedFamily.status)" x-text="selectedFamily.status"></span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-ink-muted">Record</dt>
                                        <dd>
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="verificationBadge(selectedFamily.verification).class"
                                                x-text="verificationBadge(selectedFamily.verification).label"
                                            ></span>
                                        </dd>
                                    </div>
                                </dl>
                            </x-ui.card>

                            {{-- Programs chips --}}
                            <x-ui.card class="p-4">
                                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-3">Enrolled Programs</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-if="!selectedFamily.programs || selectedFamily.programs.length === 0">
                                        <span class="text-xs text-ink-muted italic">No enrolled programs</span>
                                    </template>
                                    <template x-for="prog in (selectedFamily.programs || [])" :key="prog">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700" x-text="prog"></span>
                                    </template>
                                </div>
                            </x-ui.card>

                            {{-- Notes --}}
                            <x-ui.card class="p-4">
                                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-2">Notes / Remarks</h4>
                                <p class="text-xs text-slate-600 leading-relaxed" x-text="selectedFamily.notes || 'No notes recorded.'"></p>
                            </x-ui.card>
                        </div>
                    </div>
                    </template>

                    {{-- ── EDIT FORM ── --}}
                    <template x-if="editing">
                        <div class="max-w-xl space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5">Family Type</label>
                                <select aria-label="Family Type" x-model="edit.family_type" class="w-full rounded-2xl py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                                    <option value="Nuclear">Nuclear</option>
                                    <option value="Extended">Extended</option>
                                    <option value="Single Parent">Single Parent</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="fam-edit-sitio">Sitio / Purok</label>
                                <input id="fam-edit-sitio" type="text" x-model="edit.sitio" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="fam-edit-head">Head of Family</label>
                                <input id="fam-edit-head" type="text" x-model="edit.head_name" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="fam-edit-notes">Notes</label>
                                <textarea id="fam-edit-notes" x-model="edit.notes" rows="3" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all resize-none border-0 surface-pressed-sm"></textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.button variant="secondary" size="sm" @click="editing = false">Cancel</x-ui.button>
                                <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="saveEdit()" x-text="saving ? 'Saving…' : 'Save Changes'"></x-ui.button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- ── MEMBERS TAB ── --}}
                <div x-show="familyTab === 'members'" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-ink">
                            All Family Members
                            <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 font-semibold" x-text="selectedFamily.member_count"></span>
                        </h3>
                    </div>

                    <x-ui.card class="overflow-hidden">
                        <template x-if="members.rows.length === 0">
                            <div class="py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <span x-show="members.loading"><i data-lucide="loader-circle" class="w-8 h-8 text-ink-faint animate-spin"></i></span>
                                    <span x-show="!members.loading"><i data-lucide="user-x" class="w-8 h-8 text-ink-faint"></i></span>
                                    <p x-show="members.loading" class="text-sm text-ink-muted">Loading members…</p>
                                    <p x-show="!members.loading && members.failed" class="text-sm text-rose-700">Could not load the members. <button type="button" class="underline" @click="loadMembers('family', selectedFamily.ref)">Try again</button></p>
                                    <p x-show="!members.loading && !members.failed" class="text-sm text-ink-muted">No members linked to this family yet.</p>
                                </div>
                            </div>
                        </template>

                        <ul class="divide-y divide-brand-600/[.08]">
                            <template x-for="r in members.rows" :key="r.ref">
                                <li
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50/40 cursor-pointer transition-colors"
                                    @click="openIndividual(r)"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="openIndividual(r)"
        @keydown.space.prevent="openIndividual(r)">
                                    <div class="relative overflow-hidden w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0"><span x-text="(r.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></span><img x-show="photoOf(r.ref)" x-cloak :src="photoOf(r.ref)" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display='none'"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-ink truncate" x-text="r.name"></p>
                                        <p class="text-xs text-ink-muted">
                                            <span x-text="r.age ? r.age + ' yrs' : ''"></span>
                                            <template x-if="r.relation"><span> · <span x-text="r.relation"></span></span></template>
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                        :class="verificationBadge(r.verification).class"
                                        x-text="verificationBadge(r.verification).label"
                                    ></span>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint flex-shrink-0"></i>
                                </li>
                            </template>
                        </ul>
                    </x-ui.card>
                </div>

                {{-- ── PROGRAMS TAB ── --}}
                <div x-show="familyTab === 'programs'" x-cloak>
                    <h3 class="text-sm font-semibold text-ink mb-3">Program Enrollments</h3>

                    <template x-if="!selectedFamily.programs || selectedFamily.programs.length === 0">
                        <x-ui.card class="py-10 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="heart-handshake" class="w-8 h-8 text-ink-faint"></i>
                                <p class="text-sm text-ink-muted">No programs enrolled.</p>
                            </div>
                        </x-ui.card>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="prog in (selectedFamily.programs || [])" :key="prog">
                            <x-ui.card class="p-4 flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i data-lucide="heart-handshake" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink" x-text="prog"></p>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 mt-0.5">
                                        <i data-lucide="circle-check" class="w-3 h-3"></i>
                                        Active
                                    </span>
                                </div>
                            </x-ui.card>
                        </template>
                    </div>
                </div>

                </div>
            </div>

            {{-- ── Modal Footer ── --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-brand-600/10 bg-slate-50/50 flex items-center justify-end gap-3 rounded-b-2xl" x-show="!editing">
                <x-ui.button size="sm" icon="pencil" x-show="can('const_family','edit')" @click="startEdit()">Edit Family</x-ui.button>
            </div>

        </div>
    </template>

    {{-- Placeholder when no family selected --}}
    <template x-if="!selectedFamily">
        <div class="flex items-center justify-center h-48 text-ink-muted text-sm">
            <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mr-2"></i>
            Loading family details…
        </div>
    </template>

</x-ui.modal>
</div>
