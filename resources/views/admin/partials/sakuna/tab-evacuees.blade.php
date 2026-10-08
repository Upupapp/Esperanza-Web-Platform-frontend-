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
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export
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
            <strong x-text="filteredEvacuees.reduce((s,e) => s + e.member_count, 0)"></strong> individuals
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

            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5"></i> Household</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Household Head</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.head_name ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Total Members</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedEvacuee?.member_count ?? 0) + ' persons'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Barangay</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.barangay ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Sitio/Purok</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.sitio || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Resident Record</p><p class="text-sm font-medium mt-0.5" :class="selectedEvacuee?.registered ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedEvacuee?.registered ? 'Linked' : 'Unregistered household'"></p></div>
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
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="users-round" class="w-3.5 h-3.5"></i> Members</h4>
                <div class="divide-y divide-brand-600/[.08] border border-brand-600/10 rounded-xl overflow-hidden">
                    <template x-for="(m, idx) in (selectedEvacuee?.members || [])" :key="idx">
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate" x-text="m.name"></p>
                                <p class="text-xs text-ink-muted" x-text="[m.relation, m.age ? m.age + ' yrs' : null, m.sex].filter(Boolean).join(' · ') || '—'"></p>
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end shrink-0 max-w-[50%]">
                                <template x-for="n in (m.needs || [])" :key="n">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200" x-text="n"></span>
                                </template>
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
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="checkin-sitio">Home Sitio/Purok</label>
                    <input id="checkin-sitio" type="text" x-model="checkInForm.sitio" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-ink-muted uppercase tracking-wide">Members (optional — otherwise just the head is recorded)</label>
                    <button type="button" @click="addCheckInMember()" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Member
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(m, idx) in checkInForm.members" :key="idx">
                        <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl border border-slate-200 bg-white">
                            <input type="text" x-model="m.name" :aria-label="'Member ' + (idx+1) + ' name'" placeholder="Name" class="flex-1 min-w-[120px] text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="text" x-model="m.relation" :aria-label="'Member ' + (idx+1) + ' relation'" placeholder="Relation" class="w-24 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <input type="number" min="0" x-model="m.age" :aria-label="'Member ' + (idx+1) + ' age'" placeholder="Age" class="w-16 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <select x-model="m.sex" :aria-label="'Member ' + (idx+1) + ' sex'" class="w-24 text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                                <option value="">Sex</option><option>Male</option><option>Female</option>
                            </select>
                            <input type="text" x-model="m.needs" :aria-label="'Member ' + (idx+1) + ' needs'" placeholder="Needs (comma-separated)" class="flex-1 min-w-[140px] text-xs border border-slate-200 rounded-xl px-2 py-1.5">
                            <button aria-label="Close" type="button" @click="removeCheckInMember(idx)" class="p-1.5 text-ink-muted hover:text-rose-600 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </template>
                </div>
            </div>
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

</div>{{-- end x-data wrapper --}}
