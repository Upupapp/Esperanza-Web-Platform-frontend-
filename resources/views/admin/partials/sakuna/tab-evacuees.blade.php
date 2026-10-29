{{--
    tab-evacuees.blade.php
    Evacuee Registration & Occupancy Management
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: evacueeSearch/evCenterFilter/evStatusFilter, evacueesList/
    evacueesLoading/evacueesFailed, evacueeCentersOpen, selectedEvacuee/
    showEvacueeDetail, showEvacueeReg/checkInForm/checkInSaving. Helpers:
    statusBadge(st), toast(msg, variant), openEvacueeDetail(row), transferEvacuee(ev,ref),
    checkOutEvacuee(ev), openEvacueeReg(), addCheckInMember(), removeCheckInMember(i),
    submitCheckIn().

    GET /sakuna/evacuees, GET /sakuna/evacuees/{ref} (added in commit 1e5a8d4 -- there
    was no way to read a family's members or movement history back before this;
    familyDetail() was write-response-only), POST /sakuna/centers/{ref}/check-in,
    POST /sakuna/evacuees/{ref}/transfer, POST /sakuna/evacuees/{ref}/check-out.
    GET/PUT /sakuna/evacuees/{ref}/faced: the DSWD FACED card - the family's
    registration side and its Family Assistance Record (MSWDO forms, 2026-10-26).

    Evacuees are tracked BY FAMILY, not by individual (SakunaEvacueeFamily). The real
    status axis has exactly two values -- Processing (checked in) and Completed
    (checked out) -- not the mock's five (Checked In/Temporarily Out/Transferred/
    Returned Home/Referred to Hospital); a transfer moves current_center_id, it does
    not change status. Health/special-needs data is real but lives PER MEMBER
    (SakunaEvacueeMember.needs), not as a family-level flag list the mock assumed --
    the detail view below shows a member list instead. Summary demographic pills
    (Children/Seniors/PWD/Pregnant counts) are dropped: computing them accurately
    would need every family's full member list fetched up front, which the list
    endpoint doesn't return and no bulk-members endpoint exists for.
--}}

<div x-data="{
    showCheckoutConfirm: false,
    checkoutEvacuee: null,
    showTransferForm: false,
    transferToCenter: '',
    openCheckout(ev) { this.checkoutEvacuee = ev; this.showCheckoutConfirm = true; this.$nextTick(() => window.renderIcons?.()); },
    get filteredEvacuees() {
        const q = evacueeSearch.toLowerCase();
        return evacueesList.filter(ev =>
            $store.session.inScope(ev.barangay) &&
            (!q || ev.ref.toLowerCase().includes(q) || ev.head_name.toLowerCase().includes(q)) &&
            (!evCenterFilter || ev.center === evCenterFilter) &&
            (!evStatusFilter || ev.status === evStatusFilter)
        );
    },
}" x-init="loadEvacuees()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-ink-body">Evacuee Registration & Tracking</h2>
        <p class="text-sm text-ink-muted mt-0.5">Tracked by family unit, with unregistered households accepted.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredEvacuees, 'evacuees-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export CSV
        </button>
        <button @click="exportPDF(filteredEvacuees, 'evacuees-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="file-down" class="w-4 h-4 text-ink-muted"></i> Export PDF
        </button>
        <button x-show="can('sakuna_evacuees', 'check_in')" @click="openEvacueeReg()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Register Evacuee
        </button>
    </div>
</div>

<div x-show="evacueesLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="evacueesFailed" x-cloak><x-ui.error-state messageExpr="'Could not load evacuees.'" retryExpr="loadEvacuees()" :retryable="true" /></div>

<div x-show="!evacueesLoading && !evacueesFailed" x-cloak class="space-y-4">

{{-- ── Summary Cards ───────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-ui.stat-card layout="compact" tone="brand" object="people" label="Total Individuals" valueBind="evacueesList.reduce((s,e) => s + e.member_count, 0)" sublabelBind="evacueesList.filter(e => e.status === 'Processing').length + ' families sheltered'" />
    <x-ui.stat-card layout="compact" tone="teal" object="building" label="Currently Sheltered" valueBind="evacueesList.filter(e => e.status === 'Processing').reduce((s,e) => s + e.member_count, 0)" />
    <x-ui.stat-card layout="compact" tone="navy" object="stamp" label="Checked Out" valueBind="evacueesList.filter(e => e.status === 'Completed').length" />
    <x-ui.stat-card layout="compact" tone="amber" object="document" label="Unregistered" valueBind="evacueesList.filter(e => !e.registered).length" sublabel="Not linked to a resident record" />
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] p-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
            <input aria-label="Search by name or ID" type="text" x-model="evacueeSearch" placeholder="Search by name or ref…"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        </div>
        <select aria-label="Ev center filter" x-model="evCenterFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Centers</option>
            <template x-for="c in evacueeCentersOpen" :key="c.ref"><option :value="c.name" x-text="c.name"></option></template>
        </select>
        <select aria-label="Ev status filter" x-model="evStatusFilter"
            class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">All Statuses</option>
            <option value="Processing">Checked In</option>
            <option value="Completed">Checked Out</option>
        </select>
        <button @click="evacueeSearch = ''; evCenterFilter = ''; evStatusFilter = ''"
            class="text-sm text-ink-muted hover:text-slate-700 px-3 py-2 rounded-2xl hover:bg-slate-100 transition-colors">
            Clear filters
        </button>
    </div>
</div>

{{-- ── Evacuee Table ───────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-well/70">
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Ref</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Household Head</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Home Barangay</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Evacuation Center</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Checked In</th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Status</th>
                    <th class="text-right px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="ev in filteredEvacuees" :key="ev.ref">
                    <tr class="hover:bg-white/60 transition-colors cursor-pointer group" @click="openEvacueeDetail(ev)" tabindex="0" @keydown.enter.prevent="openEvacueeDetail(ev)">
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-mono text-xs text-ink-muted" x-text="ev.ref"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0"
                                     x-text="(ev.head_name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <div class="min-w-0">
                                    <div class="font-medium text-ink-body group-hover:text-brand-600 transition-colors truncate" x-text="ev.head_name"></div>
                                    <div class="text-xs text-ink-muted" x-text="ev.member_count + (ev.member_count === 1 ? ' member' : ' members')"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-slate-700" x-text="ev.barangay"></div>
                            <div class="text-xs text-ink-muted" x-text="ev.sitio || ''"></div>
                        </td>
                        <td class="px-4 py-3"><div class="text-slate-700 max-w-[180px] truncate" x-text="ev.center || '—'"></div></td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 text-xs" x-text="ev.checked_in ? new Date(ev.checked_in).toLocaleDateString('en-US',{month:'short',day:'numeric'}) : '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span :class="statusBadge(ev.status)" x-text="ev.status === 'Processing' ? 'Checked In' : 'Checked Out'"></span>
                            <span x-show="!ev.registered" class="ml-1 text-[10px] text-amber-600 font-medium" title="Not linked to a resident record">unreg.</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button @click="openEvacueeDetail(ev)" class="p-1.5 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors" title="View Details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <button x-show="can('sakuna_evacuees','transfer') && ev.status === 'Processing'" @click="selectedEvacuee = ev; transferToCenter = ''; showTransferForm = true; $nextTick(() => window.renderIcons?.())"
                                    class="p-1.5 rounded-lg text-ink-muted hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Transfer Center">
                                    <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                                </button>
                                <button x-show="can('sakuna_evacuees','check_out') && ev.status === 'Processing'" @click="openCheckout(ev)"
                                    class="p-1.5 rounded-xl text-ink-muted hover:text-amber-600 hover:bg-amber-50 transition-colors" title="Check Out">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredEvacuees.length === 0">
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-ink-muted">
                            <i data-lucide="users" class="w-10 h-10 opacity-30"></i>
                            <p class="text-sm font-medium text-ink-muted">No evacuees match your filters.</p>
                            <button @click="evacueeSearch = ''; evCenterFilter = ''; evStatusFilter = ''" class="text-sm text-brand-600 hover:underline">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-brand-600/10 bg-slate-50/40 flex items-center justify-between">
        <p class="text-xs text-ink-muted">
            Showing <strong x-text="filteredEvacuees.length"></strong> household(s) &bull;
            <strong x-text="filteredEvacuees.reduce((s,e) => s + e.member_count, 0)"></strong> <span x-text="filteredEvacuees.reduce((s,e) => s + e.member_count, 0) === 1 ? 'individual' : 'individuals'"></span>
        </p>
    </div>
</div>

</div>{{-- /loaded content --}}

{{-- ── Transfer Modal ──────────────────────────────────────────────────────────────────── --}}
<div x-show="showTransferForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm" @click.self="showTransferForm = false"
     x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-trap="showTransferForm" role="dialog" aria-modal="true" @keydown.escape.window="showTransferForm = false">
    <div class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0"><i data-lucide="arrow-right-left" class="w-5 h-5 text-blue-600"></i></div>
            <div>
                <h3 class="text-base font-semibold text-ink-body">Transfer Household</h3>
                <p class="text-xs text-ink-muted" x-text="selectedEvacuee?.head_name || ''"></p>
            </div>
        </div>
        <label class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5" for="transfer-to-center">Destination Center</label>
        <select id="transfer-to-center" x-model="transferToCenter" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors mb-5 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">— Choose center —</option>
            <template x-for="c in evacueeCentersOpen.filter(c => c.name !== selectedEvacuee?.center)" :key="c.ref">
                <option :value="c.ref" x-text="c.name + ' (' + c.individuals + '/' + c.capacity + ')'"></option>
            </template>
        </select>
        <div class="flex gap-3">
            <button @click="showTransferForm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Cancel</button>
            <button :disabled="!transferToCenter" @click="transferEvacuee(selectedEvacuee, transferToCenter); showTransferForm = false"
                class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors disabled:opacity-40">Confirm Transfer</button>
        </div>
    </div>
</div>

{{-- ── Check-out Confirmation Modal ───────────────────────────────────────────────────── --}}
<div x-show="showCheckoutConfirm" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCheckoutConfirm = false"
        x-trap="showCheckoutConfirm"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCheckoutConfirm = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="log-out" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-ink-body">Confirm Check-Out</h3>
                <p class="text-xs text-ink-muted" x-text="checkoutEvacuee ? checkoutEvacuee.ref : ''"></p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-5">
            Confirm that <strong x-text="checkoutEvacuee ? checkoutEvacuee.head_name : ''"></strong>'s household (<span x-text="checkoutEvacuee?.member_count"></span> members) has left the center.
        </p>
        <div class="flex gap-3">
            <button @click="showCheckoutConfirm = false; checkoutEvacuee = null"
                class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Cancel</button>
            <button @click="checkOutEvacuee(checkoutEvacuee); showCheckoutConfirm = false; checkoutEvacuee = null"
                class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-amber-600 rounded-2xl hover:bg-amber-700 transition-colors">Confirm Check-Out</button>
        </div>
    </div>
</div>

{{-- ── Evacuee Detail Modal ─────────────────────────────────────────────────────────────── --}}
<div x-show="showEvacueeDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showEvacueeDetail = false"
        x-trap="showEvacueeDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showEvacueeDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="user" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-ink-body" x-text="selectedEvacuee?.head_name ?? '—'"></h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs text-ink-muted" x-text="selectedEvacuee?.ref ?? ''"></span>
                        <span class="text-ink-faint">·</span>
                        <span :class="statusBadge(selectedEvacuee?.status ?? '')" x-text="selectedEvacuee?.status === 'Processing' ? 'Checked In' : 'Checked Out'"></span>
                    </div>
                </div>
            </div>
            <button aria-label="Close" @click="showEvacueeDetail = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">

            <div data-shelter-record>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5"></i> Household</h4>
                    <button type="button" x-show="mayEditShelter() && !recordEditing" @click="editShelterRecord()" class="text-xs font-medium text-brand-600 hover:text-brand-700">Edit</button>
                </div>
                <div x-show="recordEditing" x-cloak class="mb-3 grid grid-cols-2 gap-3 rounded-2xl border border-brand-600/10 p-3" data-shelter-edit>
                    <div><label for="record-sitio" class="text-xs text-ink-muted mb-1 block">Sitio</label><input id="record-sitio" type="text" x-model="recordForm.sitio" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="record-purok" class="text-xs text-ink-muted mb-1 block">Purok</label><input id="record-purok" type="text" x-model="recordForm.purok" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="record-hh" class="text-xs text-ink-muted mb-1 block">Household code (typed)</label><input id="record-hh" type="text" x-model="recordForm.hh_code" :disabled="!!selectedEvacuee?.household_ref" :placeholder="selectedEvacuee?.household_ref ? 'From the registry' : ''" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 disabled:opacity-50"></div>
                    <div><label for="record-count" class="text-xs text-ink-muted mb-1 block">Headcount</label><input id="record-count" type="number" :min="Math.max(1, (selectedEvacuee?.members || []).length)" x-model="recordForm.member_count" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <p class="col-span-2 text-[11px] text-ink-muted">A sitio is not a purok; leave the purok blank if it is not known. The headcount cannot be lower than the members listed below.</p>
                    <div class="col-span-2 flex justify-end gap-2">
                        <button type="button" @click="recordEditing = false" class="text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-1.5">Cancel</button>
                        <button type="button" :disabled="recordSaving" @click="saveShelterRecord()" class="text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-1.5 disabled:opacity-50" x-text="recordSaving ? 'Saving…' : 'Save'"></button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Household Head</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.head_name ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Total Members</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedEvacuee?.member_count ?? 0) + ((selectedEvacuee?.member_count ?? 0) === 1 ? ' person' : ' persons')"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Barangay</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.barangay ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Sitio</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.sitio || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Purok</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.purok || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Household Code</p><p class="text-sm font-mono font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.household_ref || selectedEvacuee?.hh_code || '—'"></p><p class="text-[11px] mt-0.5" x-show="selectedEvacuee?.household_ref || selectedEvacuee?.hh_code" :class="selectedEvacuee?.household_ref ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedEvacuee?.household_ref ? 'From the registry' : 'Typed at the centre, not verified'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Resident Record</p><p class="text-sm font-medium mt-0.5" :class="selectedEvacuee?.registered ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedEvacuee?.registered ? 'Linked' : 'Unregistered household'"></p></div>
                </div>
            </div>

            <div data-faced-panel>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="id-card" class="w-3.5 h-3.5"></i> FACED Card (DSWD)</h4>
                    <button type="button" x-show="can('sakuna_evacuees','check_in') && !facedEditing && !selectedEvacuee?.faced_restricted" @click="editFaced()" class="text-xs font-medium text-brand-600 hover:text-brand-700">Edit</button>
                </div>
                <p x-show="selectedEvacuee?.faced_restricted" x-cloak class="text-xs text-ink-muted rounded-xl bg-slate-50 p-3 flex items-center gap-1.5" data-faced-restricted><i data-lucide="lock" class="w-3.5 h-3.5 shrink-0"></i> FACED details are kept by the family's home barangay (<span x-text="selectedEvacuee?.barangay"></span>) and the municipality.</p>
                <div x-show="!facedEditing && !selectedEvacuee?.faced_restricted" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Serial No.</p><p class="text-sm font-mono font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced?.serial || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Contact No.</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced?.contact || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">House Ownership</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced?.house_ownership || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Shelter Damage</p><p class="text-sm font-medium mt-0.5" :class="selectedEvacuee?.faced?.shelter_damage === 'Totally Damaged' ? 'text-rose-600' : (selectedEvacuee?.faced?.shelter_damage === 'Partially Damaged' ? 'text-amber-600' : 'text-ink-body')" x-text="selectedEvacuee?.faced?.shelter_damage || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Monthly Income</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced?.monthly_income || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">4Ps Beneficiary</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced ? (selectedEvacuee.faced.is_4ps ? 'Yes' : 'No') : '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Indigenous People</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.faced ? (selectedEvacuee.faced.is_ip ? 'Yes' + (selectedEvacuee.faced.ip_ethnicity ? ' — ' + selectedEvacuee.faced.ip_ethnicity : '') : 'No') : '—'"></p></div>
                </div>
                <div x-show="facedEditing" x-cloak class="space-y-3 rounded-2xl border border-brand-600/10 p-3" data-faced-edit>
                    @include('admin.partials.sakuna.faced-fields', ['model' => 'facedForm', 'idPrefix' => 'faced-edit'])
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="facedEditing = false" class="text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-1.5">Cancel</button>
                        <button type="button" :disabled="facedSaving" @click="saveFaced()" class="text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-1.5 disabled:opacity-50" x-text="facedSaving ? 'Saving…' : 'Save FACED details'"></button>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Current Location</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Evacuation Center</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.center || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Checked In</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.checked_in ? new Date(selectedEvacuee.checked_in).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '—'"></p></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="users-round" class="w-3.5 h-3.5"></i> Members</h4>
                    <button type="button" x-show="mayEditShelter() && memberEditing === null" @click="newMember()" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Add member</button>
                </div>
                <div x-show="memberEditing !== null" x-cloak class="mb-3 grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl border border-brand-600/10 p-3" data-member-edit>
                    <div class="col-span-2 sm:col-span-3"><label for="member-name" class="text-xs text-ink-muted mb-1 block">Name</label><input id="member-name" type="text" x-model="memberForm.name" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="member-relation" class="text-xs text-ink-muted mb-1 block">Relation</label><input id="member-relation" type="text" x-model="memberForm.relation" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="member-sex" class="text-xs text-ink-muted mb-1 block">Sex</label><select id="member-sex" x-model="memberForm.sex" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"><option value="">Not recorded</option><option>Male</option><option>Female</option></select></div>
                    <div><label for="member-age" class="text-xs text-ink-muted mb-1 block">Age</label><input id="member-age" type="number" min="0" max="130" x-model="memberForm.age" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="member-dob" class="text-xs text-ink-muted mb-1 block">Birth date</label><input id="member-dob" type="date" x-model="memberForm.dob" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div class="col-span-2"><label for="member-vulnerability" class="text-xs text-ink-muted mb-1 block">Vulnerable sector</label><select id="member-vulnerability" x-model="memberForm.vulnerability" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"><option value="">None</option><option>Older person</option><option>Pregnant</option><option>Lactating mother</option><option>PWD</option><option>Solo parent</option><option>Child (under 18)</option></select></div>
                    <label class="col-span-2 sm:col-span-3 flex items-center gap-2 text-sm text-ink-body"><input type="checkbox" x-model="memberForm.is_head" class="rounded border-slate-300 text-brand-600"> Family head (unmarks anyone else)</label>
                    <div class="col-span-2 sm:col-span-3 flex justify-end gap-2">
                        <button type="button" @click="memberEditing = null" class="text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-1.5">Cancel</button>
                        <button type="button" :disabled="memberSaving || !memberForm.name.trim()" @click="saveMember()" class="text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-1.5 disabled:opacity-50" x-text="memberSaving ? 'Saving…' : (memberEditing === 'new' ? 'Add member' : 'Save member')"></button>
                    </div>
                </div>
                <div class="divide-y divide-brand-600/[.08] border border-brand-600/10 rounded-xl overflow-hidden">
                    <template x-for="(m, idx) in (selectedEvacuee?.members || [])" :key="m.id ?? idx">
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate"><span x-text="m.name"></span> <span x-show="m.is_head" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-brand-50 text-brand-700 border border-brand-200">Head</span> <span x-show="!m.sex || (m.age == null && !m.dob)" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10.5px] font-medium bg-amber-50 text-amber-700 border border-amber-200" x-text="!m.sex ? 'No sex recorded' : 'No age recorded'"></span></p>
                                <p class="text-xs text-ink-muted" x-text="[m.relation, m.age ? m.age + ' yrs' : null, m.sex, m.dob ? 'born ' + m.dob : null].filter(Boolean).join(' · ') || '—'"></p>
                                <p class="text-xs text-ink-muted" x-show="m.education || m.occupation" x-text="[m.education, m.occupation].filter(Boolean).join(' · ')"></p>
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end shrink-0 max-w-[50%]">
                                <span x-show="m.vulnerability" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-violet-50 text-violet-700 border border-violet-200" x-text="m.vulnerability"></span>
                                <template x-for="n in (m.needs || [])" :key="n">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200" x-text="n"></span>
                                </template>
                                <button type="button" x-show="mayEditShelter() && m.id && memberEditing === null" @click="editMember(m)" :aria-label="'Edit ' + m.name" class="text-xs font-medium text-brand-600 hover:text-brand-700 ml-1">Edit</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!(selectedEvacuee?.members || []).length" class="text-xs text-ink-muted italic px-4 py-4">No individual member records — only the household count was captured at check-in.</p>
                </div>
            </div>

            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="history" class="w-3.5 h-3.5"></i> Movement History</h4>
                <div class="space-y-0">
                    <template x-for="(mv, idx) in (selectedEvacuee?.movements || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedEvacuee.movements.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="w-2 h-2 rounded-full bg-brand-400 mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-semibold text-slate-700 capitalize" x-text="mv.verb.replace('_',' ')"></span>
                                <span class="text-[11px] text-ink-muted" x-text="(mv.from ? ' from ' + mv.from : '') + (mv.to ? ' to ' + mv.to : '')"></span>
                            </div>
                            <span class="text-[11px] text-ink-muted whitespace-nowrap flex-shrink-0" x-text="mv.at ? new Date(mv.at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedEvacuee?.movements || []).length" class="text-xs text-ink-muted py-2">No movements recorded yet.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex flex-wrap items-center gap-2">
            <button type="button" @click="openFacedCard(selectedEvacuee)" :disabled="facedLoading" data-faced-open
                class="inline-flex items-center gap-1.5 text-sm text-brand-700 bg-white border border-brand-200 rounded-xl px-3 py-2 hover:border-brand-300 transition-colors disabled:opacity-50">
                <i data-lucide="printer" class="w-4 h-4"></i> FACED Card
            </button>
            <button x-show="can('sakuna_evacuees','transfer') && selectedEvacuee?.status === 'Processing'" @click="transferToCenter = ''; showTransferForm = true; showEvacueeDetail = false; $nextTick(() => window.renderIcons?.())"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transfer Center
            </button>
            <button x-show="can('sakuna_evacuees','check_out') && selectedEvacuee?.status === 'Processing'" @click="openCheckout(selectedEvacuee); showEvacueeDetail = false"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 bg-amber-100 border border-amber-200 rounded-2xl px-3 py-2 hover:bg-amber-200 transition-colors ml-auto">
                <i data-lucide="log-out" class="w-4 h-4"></i> Check Out
            </button>
        </div>
    </div>
</div>

{{-- ── Evacuee Registration Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showEvacueeReg" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showEvacueeReg = false"
        x-trap="showEvacueeReg"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showEvacueeReg = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 class="font-semibold text-ink-body">Register Evacuee Household</h3>
                <p class="text-xs text-ink-muted mt-0.5">Unregistered households are accepted — a disaster is not the moment to require an account.</p>
            </div>
            <button aria-label="Close" @click="showEvacueeReg = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
            <div class="rounded-2xl border border-brand-600/10 p-3 space-y-2" data-registry-search>
                <label class="text-xs font-semibold text-ink-body flex items-center gap-1.5" for="checkin-registry"><i data-lucide="search" class="w-3.5 h-3.5 text-brand-600"></i> Find the family in the resident registry <span class="font-normal text-ink-muted">(optional)</span></label>
                <div x-show="!registryPicked">
                    <input id="checkin-registry" type="search" x-model="registryQuery" @input="searchRegistry()" placeholder="Surname, member name, or family/household code — at least 3 letters" autocomplete="off" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <p x-show="registrySearching" class="text-xs text-ink-muted mt-1.5">Searching…</p>
                    <p x-show="registrySearched && !registrySearching && !registryResults.length" class="text-xs text-ink-muted mt-1.5">No family in the registry matches. Fill the form by hand.</p>
                    <div x-show="registryResults.length" class="mt-2 divide-y divide-brand-600/[.08] border border-brand-600/10 rounded-xl overflow-hidden">
                        <template x-for="f in registryResults" :key="f.ref">
                            <button type="button" @click="pickRegistryFamily(f)" class="w-full text-left px-3 py-2 hover:bg-brand-50/60 transition-colors">
                                <p class="text-sm font-medium text-ink-body"><span x-text="f.head_name || f.label"></span> <span class="text-xs font-normal text-ink-muted" x-text="'· ' + f.barangay + (f.sitio ? ', ' + f.sitio : '') + ' · ' + f.members.length + (f.members.length === 1 ? ' member' : ' members')"></span></p>
                                <p class="text-[11px] text-ink-muted"><span x-show="f.source === 'kept_by_resident'" class="mr-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-violet-50 text-violet-700 border border-violet-200">Kept by the resident</span><span class="font-mono" x-text="f.household_ref || f.ref"></span> <span x-show="f.already_sheltered" class="text-amber-700" x-text="f.already_sheltered ? '· already at ' + f.already_sheltered.center + ' (' + f.already_sheltered.ref + ')' : ''"></span></p>
                            </button>
                        </template>
                    </div>
                </div>
                <div x-show="registryPicked" x-cloak class="flex items-start justify-between gap-3" data-registry-picked>
                    <div class="text-xs text-ink-body">
                        <p class="font-medium"><span x-text="registryPicked?.source === 'kept_by_resident' ? 'Filled from the list the resident keeps: ' : 'Filled from the registry: '"></span><span class="font-mono" x-text="registryPicked?.household_ref || registryPicked?.ref"></span></p>
                        <p class="text-ink-muted">Untick anyone who did not come to the centre. Corrections here change this shelter record only, not the registry.</p>
                        <p x-show="registryPicked?.already_sheltered" class="text-amber-700 mt-1" x-text="registryPicked?.already_sheltered ? 'Already checked in at ' + registryPicked.already_sheltered.center + ' (' + registryPicked.already_sheltered.ref + ').' : ''"></p>
                    </div>
                    <button type="button" @click="clearRegistryPick()" class="text-xs font-medium text-brand-600 hover:text-brand-700 shrink-0">Unlink</button>
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-center">Evacuation Center <span class="text-rose-500">*</span></label>
                <select id="checkin-center" x-model="checkInForm.center" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">— Choose center —</option>
                    <template x-for="c in evacueeCentersOpen" :key="c.ref">
                        <option :value="c.ref" x-text="c.name + ' — ' + c.individuals + '/' + c.capacity"></option>
                    </template>
                </select>
                <p x-show="!evacueeCentersOpen.length" class="text-xs text-amber-600 mt-1.5">No centers are currently open. Open one from the Evac. Centers tab first.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-head">Household Head <span class="text-rose-500">*</span></label>
                    <input id="checkin-head" type="text" x-model="checkInForm.head_name" placeholder="Full name" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-barangay">Home Barangay <span class="text-rose-500">*</span></label>
                    <select id="checkin-barangay" x-model="checkInForm.barangay" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                        <option value="">Select barangay…</option>
                        @foreach($barangays as $brgy)
                            <option>{{ $brgy }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-sitio">Home Sitio</label>
                    <input id="checkin-sitio" type="text" x-model="checkInForm.sitio" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-purok">Purok</label>
                    <input id="checkin-purok" type="text" x-model="checkInForm.purok" placeholder="If known" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
                <div class="sm:col-span-2" x-show="!registryPicked?.household_ref">
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-hh">Household code</label>
                    <input id="checkin-hh" type="text" x-model="checkInForm.hh_code" placeholder="If the family knows it — kept as typed, not verified" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
            </div>

            <div class="rounded-2xl border border-brand-600/10 p-3 space-y-3" data-checkin-faced>
                <p class="text-xs font-semibold text-ink-body flex items-center gap-1.5"><i data-lucide="id-card" class="w-3.5 h-3.5 text-brand-600"></i> FACED card details <span class="font-normal text-ink-muted">(optional — can be filled in later)</span></p>
                @include('admin.partials.sakuna.faced-fields', ['model' => 'checkInForm', 'idPrefix' => 'checkin-faced'])
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide">Members (optional — otherwise just the head is recorded). Mark the head with ◉.</label>
                    <button type="button" @click="addCheckInMember()" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Member
                    </button>
                </div>
                <datalist id="faced-education"><option>None</option><option>Elementary</option><option>High School</option><option>Senior High School</option><option>Vocational</option><option>College</option><option>Post-graduate</option></datalist>
                <div class="space-y-2">
                    <template x-for="(m, idx) in checkInForm.members" :key="idx">
                        <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl border border-slate-200 bg-white" :class="m.arrived === false ? 'opacity-50' : ''">
                            <label x-show="registryPicked" class="flex items-center gap-1 text-[11px] text-ink-muted"><input type="checkbox" x-model="m.arrived" :aria-label="'Member ' + (idx+1) + ' arrived at the centre'" class="rounded border-slate-300 text-brand-600"> Here</label>
                            <input type="radio" name="checkin-head-pick" :checked="m.is_head" @change="setCheckInHead(idx)" :aria-label="'Member ' + (idx+1) + ' is the family head'" class="text-brand-600 border-slate-300">
                            <input type="text" x-model="m.name" :aria-label="'Member ' + (idx+1) + ' name'" placeholder="Name" class="flex-1 min-w-[120px] text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="text" x-model="m.relation" :aria-label="'Member ' + (idx+1) + ' relation'" placeholder="Relation" class="w-24 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="number" min="0" x-model="m.age" :aria-label="'Member ' + (idx+1) + ' age'" placeholder="Age" class="w-16 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <select x-model="m.sex" :aria-label="'Member ' + (idx+1) + ' sex'" class="w-24 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                                <option value="">Sex</option><option>Male</option><option>Female</option>
                            </select>
                            <input type="text" x-model="m.needs" :aria-label="'Member ' + (idx+1) + ' needs'" placeholder="Needs (comma-separated)" class="flex-1 min-w-[140px] text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="date" x-model="m.dob" :aria-label="'Member ' + (idx+1) + ' birthdate'" class="w-32 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="text" x-model="m.education" list="faced-education" :aria-label="'Member ' + (idx+1) + ' highest educational attainment'" placeholder="Education" class="w-28 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <select x-model="m.vulnerability" :aria-label="'Member ' + (idx+1) + ' vulnerable sector'" class="w-36 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                                <option value="">Vulnerable sector</option><option>Older person</option><option>Pregnant</option><option>Lactating mother</option><option>PWD</option><option>Solo parent</option><option>Child (under 18)</option>
                            </select>
                            <button aria-label="Close" type="button" @click="removeCheckInMember(idx)" class="p-1.5 text-ink-muted hover:text-rose-600 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                            {{-- Occupation (owner, 2026-10-29): a registered resident's comes from the registry. --}}
                            <div class="basis-full flex flex-wrap items-center gap-2 text-xs" x-show="m.individual_id && m.registry_has_occupation && !m.update_registry">
                                <span class="text-ink-muted">Occupation: recorded from the resident's registry record.</span>
                                <button type="button" x-show="can('const_resident','edit')" @click="m.update_registry = true" class="text-brand-600 hover:text-brand-700 font-medium">Correct it in the registry</button>
                            </div>
                            <div class="basis-full" x-show="!(m.individual_id && m.registry_has_occupation) || m.update_registry">
                                <x-livelihood-select model="m" dynamic-id="'checkin-m-lv-' + idx"
                                    label-class="block text-[11px] font-medium text-ink-muted mb-1"
                                    input-class="w-full text-xs border border-slate-200 rounded-xl px-2 py-1.5" />
                                <p class="text-[11px] text-ink-muted mt-1" x-show="m.individual_id && !m.registry_has_occupation && can('const_resident','edit')">Saved to this resident's registry record too.</p>
                                <p class="text-[11px] text-amber-700 mt-1" x-show="m.update_registry">This changes the resident's registry record. <button type="button" class="underline" @click="m.update_registry = false; m.livelihood_code = ''; m.livelihood_other = ''">Keep the registry's</button></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="checkInDuplicate" x-cloak role="alert" class="mx-6 mb-3 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-2" data-checkin-duplicate>
            <p class="flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><span x-text="checkInDuplicate"></span></p>
            <button type="button" :disabled="checkInSaving" @click="submitCheckIn(true)" class="text-xs font-semibold text-amber-900 bg-white border border-amber-300 rounded-2xl px-3 py-1.5 disabled:opacity-50">It's a different family — check in</button>
        </div>
        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-between">
            <button @click="showEvacueeReg = false" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-4 py-2 hover:border-slate-300 transition-colors">Cancel</button>
            <button :disabled="checkInSaving || !checkInForm.center || !checkInForm.head_name || !checkInForm.barangay" @click="submitCheckIn()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-2xl px-4 py-2 transition-colors disabled:opacity-50">
                <i data-lucide="circle-check" class="w-4 h-4"></i>
                <span x-text="checkInSaving ? 'Checking in…' : 'Complete Check-In'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── FACED Card (print) ─────────────────────────────────────────────────────────────── --}}
<template x-if="facedCard">
    <div class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-8 bg-navy-950/60 backdrop-blur-sm overflow-y-auto" @click.self="facedCard = null"
         x-trap="facedCard" role="dialog" aria-modal="true" aria-label="FACED card" @keydown.escape.window="facedCard = null">
        <div class="bg-white rounded-[28px] shadow-float w-full max-w-4xl mb-8">
            <div class="flex items-center justify-between px-6 py-3 border-b border-brand-600/10">
                <h3 class="font-semibold text-ink-body">Family Assistance Card in Emergencies and Disasters</h3>
                <div class="flex items-center gap-2">
                    <button type="button" @click="printFacedCard()" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 text-white px-3 py-1.5 text-xs font-semibold"><i data-lucide="printer" class="w-3.5 h-3.5"></i> Print</button>
                    <button type="button" aria-label="Close" @click="facedCard = null" class="w-8 h-8 rounded-xl grid place-items-center text-ink-muted hover:bg-slate-100"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            </div>
            <div id="dp-print-area" class="doc-paper p-8 text-[11px] leading-snug text-black" data-faced-card>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-bold">DSWD-DRMB-FACED</p>
                        <p>Serial No. <span class="font-mono font-semibold" x-text="facedCard.faced?.serial || '__________'"></span></p>
                    </div>
                    <div class="text-center">
                        <p>Republic of the Philippines · Municipality of Esperanza</p>
                        <p class="font-bold">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE</p>
                        <p class="text-sm font-bold mt-1">FAMILY ASSISTANCE CARD IN EMERGENCIES AND DISASTERS (FACED)</p>
                    </div>
                    <p class="text-right">Ref. <span class="font-mono" x-text="facedCard.ref"></span></p>
                </div>

                <p class="font-bold mt-3 border-b border-black">Location of the Affected Family</p>
                <table class="w-full mt-1"><tbody>
                    <tr><td class="w-1/4 py-0.5">Barangay</td><td class="font-semibold" x-text="facedCard.barangay"></td><td class="w-1/4">Sitio / Purok</td><td class="font-semibold" x-text="facedCard.sitio || '—'"></td></tr>
                    <tr><td class="py-0.5">Evacuation center</td><td class="font-semibold" x-text="facedCard.center || '—'"></td><td>Checked in</td><td x-text="facedCard.checked_in ? new Date(facedCard.checked_in).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) : '—'"></td></tr>
                </tbody></table>

                <p class="font-bold mt-3 border-b border-black">Head of the Family</p>
                <table class="w-full mt-1"><tbody>
                    <tr><td class="w-1/4 py-0.5">Name</td><td class="font-semibold" x-text="facedCard.head_name"></td><td class="w-1/4">Contact no.</td><td x-text="facedCard.faced?.contact || '—'"></td></tr>
                    <tr><td class="py-0.5">Monthly family net income</td><td x-text="facedCard.faced?.monthly_income || '—'"></td><td>4Ps beneficiary</td><td x-text="facedCard.faced?.is_4ps ? 'Yes' : 'No'"></td></tr>
                    <tr><td class="py-0.5">House ownership</td><td x-text="facedCard.faced?.house_ownership || '—'"></td><td>Shelter damage</td><td x-text="facedCard.faced?.shelter_damage || '—'"></td></tr>
                    <tr><td class="py-0.5">Indigenous People</td><td colspan="3" x-text="facedCard.faced?.is_ip ? 'Yes' + (facedCard.faced.ip_ethnicity ? ' — ' + facedCard.faced.ip_ethnicity : '') : 'No'"></td></tr>
                </tbody></table>

                <p class="font-bold mt-3 border-b border-black">Family Information</p>
                <table class="w-full mt-1 border border-black border-collapse">
                    <thead><tr class="bg-slate-100">
                        <th class="border border-black px-1 text-left">Family members</th><th class="border border-black px-1">Relation to head</th><th class="border border-black px-1">Birthdate</th><th class="border border-black px-1">Age</th><th class="border border-black px-1">Sex</th><th class="border border-black px-1">Highest educ. attainment</th><th class="border border-black px-1">Occupation</th><th class="border border-black px-1">Remarks (vulnerable sector)</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="(m, i) in (facedCard.members || [])" :key="i">
                            <tr><td class="border border-black px-1" x-text="m.name"></td><td class="border border-black px-1" x-text="m.relation || ''"></td><td class="border border-black px-1" x-text="m.dob || ''"></td><td class="border border-black px-1 text-center" x-text="m.age ?? ''"></td><td class="border border-black px-1" x-text="m.sex || ''"></td><td class="border border-black px-1" x-text="m.education || ''"></td><td class="border border-black px-1" x-text="m.occupation || ''"></td><td class="border border-black px-1" x-text="[m.vulnerability, ...(m.needs || [])].filter(Boolean).join(', ')"></td></tr>
                        </template>
                        <tr x-show="!(facedCard.members || []).length"><td colspan="8" class="border border-black px-1 py-2 text-center italic">Only the household count was recorded at check-in.</td></tr>
                    </tbody>
                </table>

                <p class="font-bold mt-3 border-b border-black">Family Assistance Record</p>
                <table class="w-full mt-1 border border-black border-collapse" data-faced-assistance>
                    <thead><tr class="bg-slate-100">
                        <th class="border border-black px-1">Date</th><th class="border border-black px-1 text-left">Kind / type of assistance</th><th class="border border-black px-1">Unit</th><th class="border border-black px-1">Qty</th><th class="border border-black px-1">Cost</th><th class="border border-black px-1">Provider</th><th class="border border-black px-1">Ref.</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="a in (facedCard.assistance || [])" :key="a.ref">
                            <tr><td class="border border-black px-1" x-text="a.created_at ? new Date(a.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) : ''"></td><td class="border border-black px-1" x-text="a.item"></td><td class="border border-black px-1" x-text="a.unit || ''"></td><td class="border border-black px-1 text-center" x-text="a.quantity ?? ''"></td><td class="border border-black px-1 text-right" x-text="a.cost != null ? '₱' + Number(a.cost).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : ''"></td><td class="border border-black px-1" x-text="a.provider || ''"></td><td class="border border-black px-1 font-mono" x-text="a.ref"></td></tr>
                        </template>
                        <tr x-show="!(facedCard.assistance || []).length"><td colspan="7" class="border border-black px-1 py-2 text-center italic">No assistance released to this family yet.</td></tr>
                    </tbody>
                </table>

                <div class="grid grid-cols-2 gap-10 mt-8 text-center">
                    <div><p class="border-t border-black pt-1">Signature / thumbmark of the family head</p></div>
                    <div><p class="border-t border-black pt-1">Barangay Captain</p></div>
                    <div><p class="border-t border-black pt-1">MSWDO / LSWDO</p></div>
                    <div><p class="border-t border-black pt-1">Date registered</p></div>
                </div>
            </div>
        </div>
    </div>
</template>

</div>{{-- end x-data wrapper --}}
