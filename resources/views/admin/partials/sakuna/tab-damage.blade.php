{{--
    tab-damage.blade.php
    Damage Assessment
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: damageSearch/damageBarangayFilter/damageSectorFilter,
    damagesList/damagesLoading/damagesFailed, damageIncidentsOpen, selectedAssessment/
    showAssessmentDetail, showCreateAssessment/damageFormMode/damageForm/damageSaving.
    Helpers: can(submodule, action), exportCSV(rows, filename), toast(msg, variant),
    statusBadge(st), loadDamage(), damageSectors(), openCreateAssessment(),
    openEditAssessment(d), saveDamage(), openAssessmentDetail(row).

    GET/POST /sakuna/damage, GET/PUT /sakuna/damage/{ref} (detail + edit added in
    backend commit 297d039 -- notes, the incident link and photo URLs existed on
    the real model since recordDamage() first shipped, but nothing ever read any
    of them back; the list endpoint only ever exposed a bare photo COUNT).

    SakunaDamageAssessment has no `type` enum (the mock offered 8 fixed choices —
    "Initial Rapid Assessment", "Housing Assessment", etc. — none of them a real
    column; `sector` is the one real free-text field), no assessor name, no
    priority-needs list, and no Population/Housing/Infrastructure/Agriculture/
    Financial/Certification breakdown -- the mock's 7-subtab detail modal and its
    "Municipal Consolidated Summary" table (a fully invented per-barangay Housing/
    Infrastructure/Agriculture peso split) had nothing behind either. The real
    status axis is whatever `recordDamage()` actually sets, which today is only
    ever Submitted -- the mock's 8-value kanban (Draft/Submitted/For Validation/
    Validated/For Approval/Approved/Returned for Revision/Consolidated) does not
    exist. `validate`/`approve` are declared sakuna_damage actions with nothing
    behind them (only `create`/`edit` exist) -- left alone rather than invented,
    matching the restraint already applied to sakuna_relief's `approve` and
    sakuna_resources' `assign`.
--}}

<div x-data="{
    get filteredDamage() {
        const q = damageSearch.toLowerCase();
        return damagesList.filter(d =>
            $store.session.inScope(d.barangay) &&
            (!q || d.ref.toLowerCase().includes(q) || d.barangay.toLowerCase().includes(q) || d.sector.toLowerCase().includes(q)) &&
            (!damageBarangayFilter || d.barangay === damageBarangayFilter) &&
            (!damageSectorFilter || d.sector === damageSectorFilter)
        );
    },
}" x-init="loadDamage()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-ink-body">Damage Assessment</h2>
        <p class="text-sm text-ink-muted mt-0.5">Barangay-level damage reports, with optional photo evidence.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredDamage, 'damage-assessments.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export CSV
        </button>
        <button @click="exportPDF(filteredDamage, 'damage-assessments.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="file-down" class="w-4 h-4 text-ink-muted"></i> Export PDF
        </button>
        <button x-show="can('sakuna_damage', 'create')" @click="openCreateAssessment()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> New Assessment
        </button>
    </div>
</div>

<div x-show="damagesLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="damagesFailed" x-cloak><x-ui.error-state messageExpr="'Could not load damage assessments.'" retryExpr="loadDamage()" :retryable="true" /></div>

<div x-show="!damagesLoading && !damagesFailed" x-cloak class="space-y-4">

{{-- ── Summary Cards ───────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <x-ui.stat-card layout="compact" tone="violet" object="document" label="Total Assessments" valueBind="damagesList.length" />
    <x-ui.stat-card layout="compact" tone="amber" object="building" label="Units Affected" valueBind="damagesList.reduce((s,d) => s + (d.units_affected || 0), 0)" />
    <x-ui.stat-card layout="compact" tone="rose" object="flame" label="Est. Total Damage" valueBind="'₱' + damagesList.reduce((s,d) => s + (d.estimated_cost || 0), 0).toLocaleString('en-US')" />
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] p-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
            <input aria-label="Search by ref, barangay or sector" type="text" x-model="damageSearch" placeholder="Search by ref, barangay or sector…"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        </div>
        <select aria-label="Damage barangay filter" x-model="damageBarangayFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Barangays</option>
            @foreach($barangays as $brgy)
                <option value="{{ $brgy }}">{{ $brgy }}</option>
            @endforeach
        </select>
        <select aria-label="Damage sector filter" x-model="damageSectorFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Sectors</option>
            <template x-for="s in damageSectors()" :key="s"><option :value="s" x-text="s"></option></template>
        </select>
        <button @click="damageSearch = ''; damageBarangayFilter = ''; damageSectorFilter = ''"
            class="text-sm text-ink-muted hover:text-slate-700 px-3 py-2 rounded-2xl hover:bg-slate-100 transition-colors">
            Clear filters
        </button>
    </div>
</div>

{{-- ── Assessment Table ────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-well/70">
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Ref</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Barangay</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Sector</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Units Affected</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Est. Damage</th>
                    <th class="text-center px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Photos</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Status</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="d in filteredDamage" :key="d.ref">
                    <tr class="hover:bg-white/60 transition-colors cursor-pointer group" @click="openAssessmentDetail(d)" tabindex="0" @keydown.enter.prevent="openAssessmentDetail(d)">
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-mono text-xs text-ink-muted" x-text="d.ref"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap font-medium text-ink-body" x-text="d.barangay"></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs font-medium" x-text="d.sector"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-slate-700" x-text="d.units_affected || '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-ink-body" x-text="d.estimated_cost ? '₱' + d.estimated_cost.toLocaleString('en-US') : '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-ink-muted" x-text="d.photos || 0"></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span :class="statusBadge(d.status)" x-text="d.status"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button @click="openAssessmentDetail(d)" class="p-1.5 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors" title="View Details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <button x-show="can('sakuna_damage','edit')" @click="openEditAssessment(d)" class="p-1.5 rounded-xl text-ink-muted hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredDamage.length === 0">
                    <td colspan="8" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-ink-muted">
                            <i data-lucide="clipboard-list" class="w-10 h-10 opacity-30"></i>
                            <p class="text-sm font-medium text-ink-muted">No assessments match your filters.</p>
                            <button @click="damageSearch = ''; damageBarangayFilter = ''; damageSectorFilter = ''" class="text-sm text-brand-600 hover:underline">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-brand-600/10 bg-slate-50/40">
        <p class="text-xs text-ink-muted">Showing <strong x-text="filteredDamage.length"></strong> of <strong x-text="damagesList.length"></strong> assessments</p>
    </div>
</div>

</div>{{-- /loaded content --}}

{{-- ── Assessment Detail Modal ─────────────────────────────────────────────────────────── --}}
<div x-show="showAssessmentDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showAssessmentDetail = false"
        x-trap="showAssessmentDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showAssessmentDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-ink-body" x-text="selectedAssessment?.ref ?? ''"></h3>
                    <span :class="statusBadge(selectedAssessment?.status ?? '')" x-text="selectedAssessment?.status"></span>
                </div>
                <p class="text-xs text-ink-muted mt-0.5" x-text="(selectedAssessment?.sector ?? '') + ' · ' + (selectedAssessment?.barangay ?? '')"></p>
            </div>
            <button aria-label="Close" @click="showAssessmentDetail = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">
            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> Details</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Units Affected</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.units_affected || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Estimated Cost</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.estimated_cost ? '₱' + selectedAssessment.estimated_cost.toLocaleString('en-US') : '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Related Incident</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.incident_ref || '—'"></p></div>
                </div>
            </div>

            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Notes</h4>
                <p class="text-sm text-slate-600 bg-slate-50 rounded-xl p-3" x-text="selectedAssessment?.notes || 'No notes recorded.'"></p>
            </div>

            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="image" class="w-3.5 h-3.5"></i> Photo Evidence</h4>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="(p, idx) in (selectedAssessment?.photos || [])" :key="idx">
                        <a :href="p.url" target="_blank" rel="noopener" class="block aspect-square rounded-2xl overflow-hidden border border-brand-600/10 bg-slate-50 hover:opacity-90 transition-opacity" :title="p.caption">
                            <img :src="p.url" :alt="p.caption || 'Damage photo'" class="w-full h-full object-cover" loading="lazy">
                        </a>
                    </template>
                </div>
                <p x-show="!Array.isArray(selectedAssessment?.photos) || !selectedAssessment.photos.length" class="text-xs text-ink-muted italic">No photos attached.</p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center gap-2">
            <button x-show="can('sakuna_damage','edit')" @click="showAssessmentDetail = false; openEditAssessment(selectedAssessment)"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
        </div>
    </div>
</div>

{{-- ── New / Edit Assessment Modal ─────────────────────────────────────────────────────── --}}
<div x-show="showCreateAssessment" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showCreateAssessment = false"
        x-trap="showCreateAssessment"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateAssessment = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <h3 class="font-semibold text-ink-body" x-text="damageFormMode === 'create' ? 'New Damage Assessment' : 'Edit Assessment'"></h3>
            <button aria-label="Close" @click="showCreateAssessment = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-barangay">Barangay <span class="text-rose-500">*</span></label>
                <select id="dmg-barangay" x-model="damageForm.barangay" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">Select barangay…</option>
                    @foreach($barangays as $brgy)
                        <option>{{ $brgy }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-sector">Sector <span class="text-rose-500">*</span></label>
                <input id="dmg-sector" type="text" x-model="damageForm.sector" list="damage-sector-suggestions" placeholder="e.g. Housing"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="damage-sector-suggestions">
                    <template x-for="s in damageSectors()" :key="s"><option :value="s"></option></template>
                </datalist>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-units">Units Affected</label>
                <input id="dmg-units" type="number" min="0" x-model="damageForm.units_affected" placeholder="e.g. 48"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-cost">Estimated Cost (₱)</label>
                <input id="dmg-cost" type="number" min="0" step="0.01" x-model="damageForm.estimated_cost" placeholder="0.00"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-incident">Related Incident (optional)</label>
                <select id="dmg-incident" x-model="damageForm.incident_id" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">— None —</option>
                    <template x-for="inc in damageIncidentsOpen" :key="inc.id">
                        <option :value="inc.id" x-text="inc.ref + ' — ' + inc.type + ', ' + inc.barangay"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-notes">Notes</label>
                <textarea id="dmg-notes" x-model="damageForm.notes" rows="3" placeholder="Field observations…"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dmg-photo">
                    <span x-text="damageFormMode === 'create' ? 'Photo Evidence' : 'Add Another Photo'"></span>
                </label>
                <input id="dmg-photo" type="file" accept="image/*" @change="damageForm.photoFile = $event.target.files[0] || null"
                    class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-2xl file:border-0 file:bg-brand-50 file:text-brand-700 file:text-sm file:font-medium hover:file:bg-brand-100">
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button @click="showCreateAssessment = false" class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-2xl hover:bg-white/60 transition-colors">Cancel</button>
            <button :disabled="damageSaving || !damageForm.barangay || !damageForm.sector" @click="saveDamage()"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl transition-colors disabled:opacity-50">
                <span x-text="damageSaving ? 'Saving…' : (damageFormMode === 'create' ? 'Record Assessment' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}
