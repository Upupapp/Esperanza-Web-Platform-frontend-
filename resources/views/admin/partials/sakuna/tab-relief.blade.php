{{--
    tab-relief.blade.php
    Relief Distribution
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: reliefSearch/reliefOperationFilter/reliefStatusFilter,
    reliefList/reliefLoading/reliefFailed, showCreateDistribution/distributionForm/
    distributionSaving. Helpers: can(submodule, action), exportCSV(rows, filename),
    toast(msg, variant), loadRelief(), reliefOperations(), openCreateDistribution(),
    submitDistribution(), releaseDistribution(d).

    GET/POST /sakuna/relief, POST /sakuna/relief/{ref}/release. `SakunaReliefDistribution`
    is the ONLY real model behind this submodule — the mock's five subtabs
    (Inventory, Requests, Releases, Distribution, Donations) mapped to at most ONE of
    them for real. There is no `sakuna_relief_inventory`, no relief-request-approval
    table and no donations table anywhere in the schema (checked every Sakuna
    migration). Dropped rather than reskinned:
      - Inventory: 15 named stock items with reorder levels, expiry dates and
        condition ratings — no stock-tracking model exists at all.
      - Requests: a 6-card kanban ("For Validation" → "For Approval" → "Preparing"
        → "In Transit" → "Delivered"/"Distributing") with named relief officers —
        no request/approval workflow model exists; a distribution is created
        directly, with no separate approval step.
      - Releases: a flat table of four more invented rows, structurally identical
        to Distribution below it — not a distinct real concept.
      - Donations: donor/value/status tracking — no donations model exists.
    What's real: a flat list of distributions (operation + barangay + beneficiary +
    item + quantity), created directly and released once handed over, with a real
    server-side duplicate guard (DUPLICATE_DISTRIBUTION, 409) keyed on
    (operation, beneficiary) — not the mock's hardcoded "Rodrigo Panganiban" check.
    `approve` is a declared sakuna_relief permission with nothing behind it (only
    `release` is implemented) — left alone rather than invented, the same restraint
    applied to sakuna_resources' declared-but-unimplemented `assign`.
--}}

<div x-data="{
    get filteredRelief() {
        const q = reliefSearch.toLowerCase();
        return reliefList.filter(d =>
            $store.session.inScope(d.barangay) &&
            (!q || d.beneficiary.toLowerCase().includes(q) || d.ref.toLowerCase().includes(q) || d.item.toLowerCase().includes(q)) &&
            (!reliefOperationFilter || d.operation === reliefOperationFilter) &&
            (!reliefStatusFilter || d.status === reliefStatusFilter)
        );
    },
}" x-init="loadRelief()" class="space-y-3">

{{-- Sakuna Redesign (2026-11-02): stat strip and actions, then the list card. --}}
<div class="sk-pagebar">
    <div class="sk-stats" x-show="!reliefFailed" :class="reliefLoading ? 'animate-pulse opacity-60' : ''">
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-amber"></span>Total distributions</span>
            <span class="sk-sval tabular-nums" x-text="reliefList.length"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-blue"></span>Beneficiaries served</span>
            <span class="sk-sval tabular-nums" x-text="new Set(reliefList.map(d => d.beneficiary)).size"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-teal"></span>Released</span>
            <span class="sk-sval tabular-nums" x-text="reliefList.filter(d => d.status === 'Released').length"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-orange"></span>Awaiting release</span>
            <span class="sk-sval tabular-nums" x-text="reliefList.filter(d => d.status === 'Submitted').length"></span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" class="sk-iconbtn sk-iconbtn-bare" aria-label="About relief distribution: record and release relief goods to beneficiaries, with built-in duplicate protection" title="Relief distribution: record and release relief goods to beneficiaries, with built-in duplicate protection.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn" @click="exportCSV(filteredRelief, 'relief-distributions.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn" @click="exportPDF(filteredRelief, 'relief-distributions.csv')">
                Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_relief', 'create')" @click="openCreateDistribution()" class="sk-btn sk-btn-p">
            <i data-lucide="plus"></i> Record Distribution
        </button>
    </div>
</div>

<div x-show="reliefLoading" class="sk-card h-72 animate-pulse"></div>
<div x-show="reliefFailed" x-cloak><x-ui.error-state messageExpr="'Could not load relief distributions.'" retryExpr="loadRelief()" :retryable="true" /></div>

<div x-show="!reliefLoading && !reliefFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <div class="sk-toolbar">
        <div class="sk-search" style="max-width: 340px;">
            <label for="relief-search" class="sr-only">Search by beneficiary, ref or item</label>
            <i data-lucide="search"></i>
            <input id="relief-search" aria-label="Search by beneficiary, ref or item" type="search" x-model="reliefSearch" placeholder="Search by beneficiary, ref or item…">
        </div>
        <select aria-label="Operation filter" x-model="reliefOperationFilter" class="sk-sel">
            <option value="">All operations</option>
            <template x-for="op in reliefOperations()" :key="op"><option :value="op" x-text="op"></option></template>
        </select>
        <select aria-label="Relief status filter" x-model="reliefStatusFilter" class="sk-sel">
            <option value="">All statuses</option>
            <option value="Submitted">Submitted</option>
            <option value="Released">Released</option>
        </select>
        <button type="button" class="sk-clear" @click="reliefSearch = ''; reliefOperationFilter = ''; reliefStatusFilter = ''">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub">Showing <strong x-text="filteredRelief.length"></strong> of <strong x-text="reliefList.length"></strong> distributions</span>
    </div>

    {{-- ── Distributions Table ──────────────────────────────────────────────────────────── --}}
    <div class="sk-scroll" tabindex="0" role="region" aria-label="Relief distributions table (scrolls sideways)">
        <table class="sk-tbl min-w-[960px]">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Operation</th>
                    <th>Beneficiary</th>
                    <th>Barangay</th>
                    <th>Item</th>
                    <th class="sk-r">Qty</th>
                    <th>Status</th>
                    <th>Released</th>
                    <th class="sk-r">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="d in filteredRelief" :key="d.ref">
                    <tr>
                        <td class="whitespace-nowrap"><span class="sk-mono" x-text="d.ref"></span></td>
                        <td class="whitespace-nowrap"><span class="sk-tag" x-text="d.operation"></span></td>
                        <td class="whitespace-nowrap">
                            <div class="sk-b" x-text="d.beneficiary"></div>
                            <div x-show="d.evacuee_ref" class="sk-mono" style="color: #1D4ED8; font-size: 11px;" x-text="'FACED · ' + d.evacuee_ref"></div>
                        </td>
                        <td class="whitespace-nowrap" x-text="d.barangay"></td>
                        <td class="whitespace-nowrap" x-text="d.item"></td>
                        <td class="sk-r whitespace-nowrap tabular-nums">
                            <span x-text="d.quantity + (d.unit ? ' ' + d.unit : '')"></span>
                            <div x-show="d.cost != null || d.provider" class="sk-sub" x-text="[d.cost != null ? '₱' + Number(d.cost).toLocaleString('en-PH') : null, d.provider].filter(Boolean).join(' · ')"></div>
                        </td>
                        <td class="whitespace-nowrap"><span :class="statusBadge(d.status)" class="sk-pill" x-text="d.status"></span>
                            <div x-show="d.acknowledgement" class="text-[11px] font-medium mt-0.5" :class="d.acknowledgement === 'received' ? 'text-emerald-700' : 'text-rose-700'" data-relief-answer
                                x-text="d.acknowledgement === 'received' ? 'Resident confirmed in app' : 'Resident says not received'"></div></td>
                        <td class="whitespace-nowrap" :class="d.distributed_at ? '' : 'sk-muted'" x-text="d.distributed_at ? new Date(d.distributed_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></td>
                        <td class="sk-r whitespace-nowrap">
                            <button type="button" x-show="can('sakuna_relief','release') && d.status === 'Submitted'" @click="releaseDistribution(d)" class="sk-btn sk-btn-sm sk-btn-soft">
                                <i data-lucide="package-check"></i> Release
                            </button>
                            <span x-show="d.status === 'Released'" class="sk-muted">—</span>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredRelief.length === 0">
                    <td colspan="9">
                        <div class="sk-empty" style="padding: 36px 16px;">
                            <i data-lucide="package"></i>
                            <span>No distributions match your filters.</span>
                            <button type="button" @click="reliefSearch = ''; reliefOperationFilter = ''; reliefStatusFilter = ''" class="sk-clear">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

</div>{{-- /loaded content --}}

{{-- ── Record Distribution Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showCreateDistribution" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCreateDistribution = false"
        x-trap="showCreateDistribution"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateDistribution = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 class="font-semibold text-ink-body">Record Distribution</h3>
                <p class="text-xs text-ink-muted mt-0.5">A duplicate for the same operation and beneficiary is refused automatically.</p>
            </div>
            <button aria-label="Close" @click="showCreateDistribution = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-operation">Operation <span class="text-rose-500">*</span></label>
                <input id="dist-operation" type="text" x-model="distributionForm.operation" list="relief-operation-suggestions" placeholder="e.g. SWM-2026-07"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="relief-operation-suggestions">
                    <template x-for="op in reliefOperations()" :key="op"><option :value="op"></option></template>
                </datalist>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-evacuee">Evacuee Family (FACED card)</label>
                <input id="dist-evacuee" type="text" x-model="distributionForm.evacuee_ref" list="relief-evacuee-suggestions" @change="pickReliefFamily()" placeholder="EVF-… — leave blank for a family not in a center"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="relief-evacuee-suggestions">
                    <template x-for="ev in evacueesList.filter(e => e.status === 'Processing')" :key="ev.ref"><option :value="ev.ref" x-text="ev.head_name + ' — ' + ev.barangay"></option></template>
                </datalist>
                <p class="text-[11px] text-ink-muted mt-1">Recorded on the family's FACED card, in its Family Assistance Record.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-beneficiary">Beneficiary Name <span class="text-rose-500">*</span></label>
                <input id="dist-beneficiary" type="text" x-model="distributionForm.beneficiary_name" placeholder="Household head or individual name"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-barangay">Barangay <span class="text-rose-500">*</span></label>
                <select id="dist-barangay" x-model="distributionForm.barangay" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">Select barangay…</option>
                    @foreach($barangays as $brgy)
                        <option>{{ $brgy }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-center">Given at (centre)</label>
                <select id="dist-center" x-model="distributionForm.center" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">Not at a centre</option>
                    <template x-for="c in reliefCenters" :key="c.ref"><option :value="c.ref" x-text="c.name + ' — ' + c.barangay"></option></template>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-location">Or another place</label>
                <input id="dist-location" type="text" x-model="distributionForm.location" maxlength="160" placeholder="e.g. Barangay hall, Purok 3 chapel"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-item">Item <span class="text-rose-500">*</span></label>
                <input id="dist-item" type="text" x-model="distributionForm.item" placeholder="e.g. Family Food Pack"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-quantity">Quantity</label>
                <input id="dist-quantity" type="number" min="1" x-model="distributionForm.quantity" placeholder="1"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-unit">Unit</label>
                <input id="dist-unit" type="text" x-model="distributionForm.unit" list="relief-unit-suggestions" placeholder="e.g. pack"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="relief-unit-suggestions"><option>pack</option><option>sack</option><option>kg</option><option>piece</option><option>kit</option><option>bottle</option><option>pesos</option></datalist>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-cost">Cost (₱)</label>
                <input id="dist-cost" type="number" min="0" step="0.01" x-model="distributionForm.cost" placeholder="0.00"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-provider">Provider</label>
                <input id="dist-provider" type="text" x-model="distributionForm.provider" list="relief-provider-suggestions" placeholder="e.g. LGU, DSWD, NGO"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="relief-provider-suggestions"><option>LGU</option><option>DSWD</option><option>Provincial Government</option><option>NGO</option><option>Private donor</option></datalist>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button @click="showCreateDistribution = false" class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-2xl hover:bg-white/60 transition-colors">Cancel</button>
            <button :disabled="distributionSaving || !distributionForm.operation || !distributionForm.barangay || !distributionForm.beneficiary_name || !distributionForm.item" @click="submitDistribution(true)"
                class="flex-1 py-2.5 text-sm font-medium text-brand-700 bg-white border border-brand-200 rounded-2xl hover:bg-brand-50 transition-colors disabled:opacity-50" data-dist-another>
                Record &amp; add another item
            </button>
            <button :disabled="distributionSaving || !distributionForm.operation || !distributionForm.barangay || !distributionForm.beneficiary_name || !distributionForm.item" @click="submitDistribution()"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl transition-colors disabled:opacity-50">
                <span x-text="distributionSaving ? 'Recording…' : 'Record Distribution'"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}
