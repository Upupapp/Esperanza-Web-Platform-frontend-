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
            <span class="sk-slab">Total distributions</span>
            <span class="sk-sval tabular-nums" x-text="fig(reliefList.length, reliefLoading || reliefFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Beneficiaries served</span>
            <span class="sk-sval tabular-nums" x-text="fig(new Set(reliefList.map(d => d.beneficiary)).size, reliefLoading || reliefFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Released</span>
            <span class="sk-sval tabular-nums" x-text="fig(reliefList.filter(d => d.status === 'Released').length, reliefLoading || reliefFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="reliefList.some(d => d.status === 'Submitted')"></span>Submitted, not yet released</span>
            <span class="sk-sval tabular-nums" x-text="fig(reliefList.filter(d => d.status === 'Submitted').length, reliefLoading || reliefFailed)"></span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About relief distribution" title="Relief distribution: record and release relief goods to beneficiaries, with built-in duplicate protection.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="reliefLoading || reliefFailed" @click="exportCSV(filteredRelief, 'relief-distributions.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="reliefLoading || reliefFailed" @click="exportPDF(filteredRelief, 'relief-distributions.csv')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_relief', 'create')" @click="openCreateDistribution()" :disabled="reliefLoading || reliefFailed || onLastCopy" :title="reliefFailed || onLastCopy ? 'Waits for the connection' : ''" class="sk-btn sk-btn-p disabled:opacity-50">
            <i data-lucide="plus"></i> Record distribution
        </button>
    </div>
</div>

<div x-show="reliefLoading" class="sk-card h-72 animate-pulse"></div>
<div x-show="reliefFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load relief distributions. ' + offlineWhy" retryExpr="loadRelief()" :retryable="true" /></div>

<div x-show="!reliefLoading && !reliefFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <h2 class="sr-only">Relief distributions</h2>
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
        <table class="sk-tbl sk-tbl-act min-w-[960px]">
            <thead>
                <tr>
                    <th>Beneficiary</th>
                    <th>Item · operation</th>
                    <th>Barangay</th>
                    <th class="sk-r">Qty</th>
                    <th>Status · released</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="d in filteredRelief" :key="d.ref">
                    <tr>
                        <td class="whitespace-nowrap">
                            <div class="sk-b" x-text="d.beneficiary"></div>
                            <div class="sk-mono" x-text="d.ref + (d.evacuee_ref ? ' · FACED ' + d.evacuee_ref : '')"></div>
                        </td>
                        <td>
                            <div class="text-ink-body" x-text="d.item"></div>
                            <div class="sk-sub" x-text="d.operation"></div>
                        </td>
                        <td class="whitespace-nowrap" x-text="d.barangay"></td>
                        <td class="sk-r whitespace-nowrap tabular-nums">
                            <span x-text="d.quantity + (d.unit ? ' ' + d.unit : '')"></span>
                            <div x-show="d.cost != null || d.provider" class="sk-sub" x-text="[d.cost != null ? '₱' + Number(d.cost).toLocaleString('en-PH') : null, d.provider].filter(Boolean).join(' · ')"></div>
                        </td>
                        <td class="whitespace-nowrap"><span :class="statusBadge(d.status)" class="sk-pill" x-text="d.status"></span>
                            <div x-show="d.acknowledgement" class="text-xs font-medium mt-0.5" :class="d.acknowledgement === 'received' ? 'text-emerald-700' : 'text-rose-700'" data-relief-answer
                                x-text="d.acknowledgement === 'received' ? 'Resident confirmed in app' : 'Resident says not received'"></div>
                            <div class="sk-sub" x-show="d.distributed_at" x-text="d.distributed_at ? new Date(d.distributed_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : ''"></div></td>
                        <td class="whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" x-show="can('sakuna_relief','release') && d.status === 'Submitted'" @click="releaseDistribution(d)" class="sk-btn sk-btn-sm sk-btn-soft">
                                    <i data-lucide="package-check"></i> Release
                                </button>
                                <button type="button" @click="reliefViewing = d" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + d.ref">
                                    <i data-lucide="eye"></i> View
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredRelief.length === 0">
                    <td colspan="6">
                        <div class="sk-empty" style="padding: 36px 16px;">
                            <i data-lucide="package"></i>
                            <span>No distributions match your filters.</span>
                            <button type="button" @click="reliefSearch = ''; reliefOperationFilter = ''; reliefStatusFilter = ''" class="sk-clear">Clear filters</button>
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
                <h3 class="font-semibold text-ink-body">Record distribution</h3>
                <p class="text-xs text-ink-muted mt-0.5">A duplicate for the same operation and beneficiary is refused automatically.</p>
            </div>
            <button aria-label="Close window" @click="showCreateDistribution = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
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
                <p class="text-xs text-ink-muted mt-1">Recorded on the family's FACED card, in its Family Assistance Record.</p>
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
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="dist-center">Given at (center)</label>
                <select id="dist-center" x-model="distributionForm.center" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">Not at a center</option>
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

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex justify-end gap-2">
            <button @click="showCreateDistribution = false" class="sk-btn">Cancel</button>
            <button :disabled="distributionSaving || !distributionForm.operation || !distributionForm.barangay || !distributionForm.beneficiary_name || !distributionForm.item" @click="submitDistribution(true)"
                class="sk-btn sk-btn-soft" data-dist-another>
                Record &amp; add another item
            </button>
            <button :disabled="distributionSaving || !distributionForm.operation || !distributionForm.barangay || !distributionForm.beneficiary_name || !distributionForm.item" @click="submitDistribution()"
                class="sk-btn sk-btn-p">
                <span x-text="distributionSaving ? 'Recording…' : 'Record distribution'"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}

{{-- ── Distribution window (critique 2026-11-03, seventh run) ────────────────────────
     Read only: what the row leaves out (center or place, when it was recorded,
     the resident's answer and when). --}}
<div x-show="reliefViewing" x-cloak
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="reliefViewing = null" x-trap="reliefViewing" role="dialog" aria-modal="true" aria-labelledby="relief-view-title"
    @keydown.escape.window="reliefViewing = null">
    <div class="bg-white rounded-[28px] shadow-float w-full max-w-lg my-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 id="relief-view-title" class="font-semibold text-ink-body" x-text="reliefViewing ? reliefViewing.item + ' for ' + reliefViewing.beneficiary : ''"></h3>
                <p class="font-mono text-xs text-ink-muted mt-0.5" x-text="reliefViewing?.ref || ''"></p>
            </div>
            <button aria-label="Close window" @click="reliefViewing = null" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <dl class="p-6 grid grid-cols-2 gap-3 text-sm">
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Operation</dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing?.operation || '—'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Status</dt><dd class="mt-0.5"><span :class="statusBadge(reliefViewing?.status)" class="sk-pill" x-text="reliefViewing?.status"></span></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Quantity</dt><dd class="font-medium text-ink-body mt-0.5 tabular-nums" x-text="reliefViewing ? reliefViewing.quantity + (reliefViewing.unit ? ' ' + reliefViewing.unit : '') : ''"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted" x-text="reliefViewing?.cost != null && reliefViewing?.provider ? 'Cost · provider' : (reliefViewing?.cost != null ? 'Cost' : 'Provider')"></dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing ? ([reliefViewing.cost != null ? '₱' + Number(reliefViewing.cost).toLocaleString('en-PH') : null, reliefViewing.provider].filter(Boolean).join(' · ') || '—') : ''"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Barangay</dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing?.barangay || '—'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Center or place</dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing?.center || reliefViewing?.location || '—'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">FACED card</dt><dd class="font-mono text-ink-body mt-0.5" x-text="reliefViewing?.evacuee_ref || 'Not linked'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Recorded</dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing?.created_at ? new Date(reliefViewing.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Released</dt><dd class="font-medium text-ink-body mt-0.5" x-text="reliefViewing?.distributed_at ? new Date(reliefViewing.distributed_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Not yet'"></dd></div>
            <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-ink-muted">Resident's answer</dt><dd class="font-medium mt-0.5" :class="reliefViewing?.acknowledgement === 'received' ? 'text-emerald-700' : (reliefViewing?.acknowledgement ? 'text-amber-800' : 'text-ink-body')"
                x-text="reliefViewing?.acknowledgement === 'received' ? 'Confirmed in the app' + (reliefViewing.acknowledged_at ? ', ' + new Date(reliefViewing.acknowledged_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '') : (reliefViewing?.acknowledgement ? 'Says not received' : 'No answer yet')"></dd></div>
        </dl>
    </div>
</div>
