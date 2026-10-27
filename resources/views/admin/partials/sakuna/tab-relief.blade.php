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
}" x-init="loadRelief()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-ink-body">Relief Distribution</h2>
        <p class="text-sm text-ink-muted mt-0.5">Record and release relief goods to beneficiaries, with built-in duplicate protection.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredRelief, 'relief-distributions.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export CSV
        </button>
        <button @click="exportPDF(filteredRelief, 'relief-distributions.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="file-down" class="w-4 h-4 text-ink-muted"></i> Export PDF
        </button>
        <button x-show="can('sakuna_relief', 'create')" @click="openCreateDistribution()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Record Distribution
        </button>
    </div>
</div>

<div x-show="reliefLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="reliefFailed" x-cloak><x-ui.error-state messageExpr="'Could not load relief distributions.'" retryExpr="loadRelief()" :retryable="true" /></div>

<div x-show="!reliefLoading && !reliefFailed" x-cloak class="space-y-4">

{{-- ── Summary Cards ───────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-ui.stat-card layout="compact" tone="gold" object="box" label="Total Distributions" valueBind="reliefList.length" />
    <x-ui.stat-card layout="compact" tone="brand" object="people" label="Beneficiaries Served" valueBind="new Set(reliefList.map(d => d.beneficiary)).size" />
    <x-ui.stat-card layout="compact" tone="teal" object="stamp" label="Released" valueBind="reliefList.filter(d => d.status === 'Released').length" />
    <x-ui.stat-card layout="compact" tone="amber" object="tray" label="Awaiting Release" valueBind="reliefList.filter(d => d.status === 'Submitted').length" />
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] p-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
            <input aria-label="Search by beneficiary, ref or item" type="text" x-model="reliefSearch" placeholder="Search by beneficiary, ref or item…"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        </div>
        <select aria-label="Operation filter" x-model="reliefOperationFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Operations</option>
            <template x-for="op in reliefOperations()" :key="op"><option :value="op" x-text="op"></option></template>
        </select>
        <select aria-label="Relief status filter" x-model="reliefStatusFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Statuses</option>
            <option value="Submitted">Submitted</option>
            <option value="Released">Released</option>
        </select>
        <button @click="reliefSearch = ''; reliefOperationFilter = ''; reliefStatusFilter = ''"
            class="text-sm text-ink-muted hover:text-slate-700 px-3 py-2 rounded-2xl hover:bg-slate-100 transition-colors">
            Clear filters
        </button>
    </div>
</div>

{{-- ── Distributions Table ─────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-well/70">
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Ref</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Operation</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Beneficiary</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Barangay</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Item</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Qty</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Status</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Released</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="d in filteredRelief" :key="d.ref">
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-mono text-xs text-ink-muted" x-text="d.ref"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs font-medium" x-text="d.operation"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-ink-body" x-text="d.beneficiary"></div>
                            <div x-show="d.evacuee_ref" class="text-[11px] text-brand-600 font-mono" x-text="'FACED · ' + d.evacuee_ref"></div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600" x-text="d.barangay"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600" x-text="d.item"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-slate-700">
                            <span x-text="d.quantity + (d.unit ? ' ' + d.unit : '')"></span>
                            <div x-show="d.cost != null || d.provider" class="text-[11px] text-ink-muted" x-text="[d.cost != null ? '₱' + Number(d.cost).toLocaleString('en-PH') : null, d.provider].filter(Boolean).join(' · ')"></div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap"><span :class="statusBadge(d.status)" x-text="d.status"></span>
                            <div x-show="d.acknowledgement" class="text-[11px] font-medium mt-0.5" :class="d.acknowledgement === 'received' ? 'text-emerald-700' : 'text-rose-700'" data-relief-answer
                                x-text="d.acknowledgement === 'received' ? 'Resident confirmed in app' : 'Resident says not received'"></div></td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-ink-muted" x-text="d.distributed_at ? new Date(d.distributed_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <button x-show="can('sakuna_relief','release') && d.status === 'Submitted'" @click="releaseDistribution(d)"
                                class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700 px-2 py-1 rounded-xl hover:bg-brand-50 transition-colors">
                                <i data-lucide="package-check" class="w-3.5 h-3.5"></i> Release
                            </button>
                            <span x-show="d.status === 'Released'" class="text-xs text-ink-faint">—</span>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredRelief.length === 0">
                    <td colspan="9" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-ink-muted">
                            <i data-lucide="package" class="w-10 h-10 opacity-30"></i>
                            <p class="text-sm font-medium text-ink-muted">No distributions match your filters.</p>
                            <button @click="reliefSearch = ''; reliefOperationFilter = ''; reliefStatusFilter = ''" class="text-sm text-brand-600 hover:underline">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-brand-600/10 bg-slate-50/40">
        <p class="text-xs text-ink-muted">Showing <strong x-text="filteredRelief.length"></strong> of <strong x-text="reliefList.length"></strong> distributions</p>
    </div>
</div>

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
