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
}" x-init="loadDamage()" class="space-y-3">

{{-- Sakuna Redesign (2026-11-02): stat strip and actions, then the list card. --}}
<div class="sk-pagebar">
    <div class="sk-stats" x-show="!damagesFailed" :class="damagesLoading ? 'animate-pulse opacity-60' : ''">
        <div class="sk-stat">
            <span class="sk-slab">Total assessments</span>
            <span class="sk-sval tabular-nums" x-text="fig(damagesList.length, damagesLoading || damagesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Units affected</span>
            <span class="sk-sval tabular-nums" x-text="fig(damagesList.reduce((s,d) => s + (d.units_affected || 0), 0), damagesLoading || damagesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Est. total damage</span>
            <span class="sk-sval tabular-nums" x-text="fig('₱' + damagesList.reduce((s,d) => s + (d.estimated_cost || 0), 0).toLocaleString('en-US'), damagesLoading || damagesFailed)"></span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About damage assessment" title="Damage assessment: barangay-level damage reports, with optional photo evidence.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn" @click="exportCSV(filteredDamage, 'damage-assessments.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn" @click="exportPDF(filteredDamage, 'damage-assessments.csv')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_damage', 'create')" @click="openCreateAssessment()" class="sk-btn sk-btn-p">
            <i data-lucide="plus"></i> New assessment
        </button>
    </div>
</div>

<div x-show="damagesLoading" class="sk-card h-72 animate-pulse"></div>
<div x-show="damagesFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load damage assessments. ' + offlineWhy" retryExpr="loadDamage()" :retryable="true" /></div>

<div x-show="!damagesLoading && !damagesFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <h2 class="sr-only">Damage assessments</h2>
    <div class="sk-toolbar">
        <div class="sk-search" style="max-width: 340px;">
            <label for="damage-search" class="sr-only">Search by ref, barangay or sector</label>
            <i data-lucide="search"></i>
            <input id="damage-search" aria-label="Search by ref, barangay or sector" type="search" x-model="damageSearch" placeholder="Search by ref, barangay or sector…">
        </div>
        <select aria-label="Damage barangay filter" x-model="damageBarangayFilter" class="sk-sel">
            <option value="">All barangays</option>
            @foreach($barangays as $brgy)
                <option value="{{ $brgy }}">{{ $brgy }}</option>
            @endforeach
        </select>
        <select aria-label="Damage sector filter" x-model="damageSectorFilter" class="sk-sel">
            <option value="">All sectors</option>
            <template x-for="s in damageSectors()" :key="s"><option :value="s" x-text="s"></option></template>
        </select>
        <button type="button" class="sk-clear" @click="damageSearch = ''; damageBarangayFilter = ''; damageSectorFilter = ''">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub">Showing <strong x-text="filteredDamage.length"></strong> of <strong x-text="damagesList.length"></strong> assessments</span>
    </div>

    {{-- ── Assessment Table ─────────────────────────────────────────────────────────────── --}}
    <div class="sk-scroll" tabindex="0" role="region" aria-label="Damage assessments table (scrolls sideways)">
        <table class="sk-tbl sk-tbl-act min-w-[820px]">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Barangay</th>
                    <th>Sector</th>
                    <th class="sk-r">Units affected</th>
                    <th class="sk-r">Est. damage</th>
                    <th class="text-center">Photos</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="d in filteredDamage" :key="d.ref">
                    <tr class="cursor-pointer group" @click="openAssessmentDetail(d)" tabindex="0" @keydown.enter.prevent="openAssessmentDetail(d)">
                        <td class="whitespace-nowrap"><span class="sk-mono" x-text="d.ref"></span></td>
                        <td class="whitespace-nowrap sk-b group-hover:text-[#1D4ED8] transition-colors" x-text="d.barangay"></td>
                        <td class="whitespace-nowrap"><span class="sk-tag" x-text="d.sector"></span></td>
                        <td class="sk-r whitespace-nowrap tabular-nums" x-text="d.units_affected || '—'"></td>
                        <td class="sk-r whitespace-nowrap tabular-nums sk-b" x-text="d.estimated_cost ? '₱' + d.estimated_cost.toLocaleString('en-PH') : '—'"></td>
                        <td class="whitespace-nowrap text-center sk-muted tabular-nums" x-text="d.photos || 0"></td>
                        <td class="whitespace-nowrap"><span :class="statusBadge(d.status)" class="sk-pill" x-text="d.status"></span></td>
                        <td class="whitespace-nowrap" @click.stop>
                            <button type="button" @click="openAssessmentDetail(d)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + d.ref">
                                <i data-lucide="eye"></i> View
                            </button>
                            <button type="button" x-show="can('sakuna_damage','edit')" @click="openEditAssessment(d)" class="sk-btn sk-btn-sm" :aria-label="'Edit ' + d.ref">
                                <i data-lucide="pencil"></i> Edit
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredDamage.length === 0">
                    <td colspan="8">
                        <div class="sk-empty" style="padding: 36px 16px;">
                            <i data-lucide="clipboard-list"></i>
                            <span>No assessments match your filters.</span>
                            <button type="button" @click="damageSearch = ''; damageBarangayFilter = ''; damageSectorFilter = ''" class="sk-clear">Clear filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

</div>{{-- /loaded content --}}

{{-- ── Assessment Detail Modal ─────────────────────────────────────────────────────────── --}}
<div x-show="showAssessmentDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showAssessmentDetail = false"
        x-trap="showAssessmentDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showAssessmentDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-ink-body" x-text="selectedAssessment?.ref ?? ''"></h3>
                    <span :class="statusBadge(selectedAssessment?.status ?? '')" x-text="selectedAssessment?.status"></span>
                </div>
                <p class="text-xs text-ink-muted mt-0.5" x-text="(selectedAssessment?.sector ?? '') + ' · ' + (selectedAssessment?.barangay ?? '')"></p>
            </div>
            <button aria-label="Close window" @click="showAssessmentDetail = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">
            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> Details</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Units Affected</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.units_affected || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Estimated Cost</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.estimated_cost ? '₱' + selectedAssessment.estimated_cost.toLocaleString('en-PH') : '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Related Incident</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedAssessment?.incident_ref || '—'"></p></div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Notes</h4>
                <p class="text-sm text-slate-600 bg-slate-50 rounded-xl p-3" x-text="selectedAssessment?.notes || 'No notes recorded.'"></p>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="image" class="w-3.5 h-3.5"></i> Photo Evidence</h4>
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
                class="sk-btn">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
        </div>
    </div>
</div>

{{-- ── New / Edit Assessment Modal ─────────────────────────────────────────────────────── --}}
<div x-show="showCreateAssessment" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showCreateAssessment = false"
        x-trap="showCreateAssessment"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateAssessment = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl my-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <h3 class="font-semibold text-ink-body" x-text="damageFormMode === 'create' ? 'New damage assessment' : 'Edit assessment'"></h3>
            <button aria-label="Close window" @click="showCreateAssessment = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
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

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex justify-end gap-2">
            <button @click="showCreateAssessment = false" class="sk-btn">Cancel</button>
            <button :disabled="damageSaving || !damageForm.barangay || !damageForm.sector" @click="saveDamage()"
                class="sk-btn sk-btn-p">
                <span x-text="damageSaving ? 'Saving…' : (damageFormMode === 'create' ? 'Record assessment' : 'Save changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}
