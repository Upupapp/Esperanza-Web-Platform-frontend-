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
}" x-init="loadEvacuees()" class="space-y-3">

{{-- Sakuna Redesign (2026-11-02): stat strip and actions, then the list card. --}}
<div class="sk-pagebar">
    <div class="sk-stats" x-show="!evacueesFailed" :class="evacueesLoading ? 'animate-pulse opacity-60' : ''">
        <div class="sk-stat">
            <span class="sk-slab">Total individuals</span>
            <span class="sk-sval tabular-nums" x-text="fig(evacueesList.reduce((s,e) => s + e.member_count, 0), evacueesLoading || evacueesFailed)"></span>
            <span class="sk-ssub" x-text="evacueesList.filter(e => e.status === 'Processing').length + ' families sheltered'"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Currently sheltered</span>
            <span class="sk-sval tabular-nums" x-text="fig(evacueesList.filter(e => e.status === 'Processing').reduce((s,e) => s + e.member_count, 0), evacueesLoading || evacueesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Checked out</span>
            <span class="sk-sval tabular-nums" x-text="fig(evacueesList.filter(e => e.status === 'Completed').length, evacueesLoading || evacueesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="evacueesList.some(e => !e.registered)"></span>Unregistered</span>
            <span class="sk-sval tabular-nums" x-text="fig(evacueesList.filter(e => !e.registered).length, evacueesLoading || evacueesFailed)"></span>
            <span class="sk-ssub">Not linked to a resident record</span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About evacuee registration and tracking" title="Evacuee registration and tracking: tracked by family unit, with unregistered households accepted.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="evacueesLoading || evacueesFailed" @click="exportCSV(filteredEvacuees, 'evacuees-export.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="evacueesLoading || evacueesFailed" @click="exportPDF(filteredEvacuees, 'evacuees-export.csv')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_evacuees', 'check_in')" @click="openEvacueeReg()" :disabled="evacueesLoading || evacueesFailed || onLastCopy" :title="evacueesFailed || onLastCopy ? 'Waits for the connection' : ''" class="sk-btn sk-btn-p disabled:opacity-50">
            <i data-lucide="user-plus"></i> Register evacuee
        </button>
    </div>
</div>

<div x-show="evacueesLoading" class="sk-card h-72 animate-pulse"></div>
<div x-show="evacueesFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load evacuees. ' + offlineWhy" retryExpr="loadEvacuees()" :retryable="true" /></div>

<div x-show="!evacueesLoading && !evacueesFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <h2 class="sr-only">Evacuee households</h2>
    <div class="sk-toolbar">
        <div class="sk-search" style="max-width: 340px;">
            <label for="evacuee-search" class="sr-only">Search by name or ID</label>
            <i data-lucide="search"></i>
            <input id="evacuee-search" aria-label="Search by name or ID" type="search" x-model="evacueeSearch" placeholder="Search by name or ref…">
        </div>
        <select aria-label="Filter by evacuation center" x-model="evCenterFilter" class="sk-sel">
            <option value="">All centers</option>
            <template x-for="c in evacueeCentersOpen" :key="c.ref"><option :value="c.name" x-text="c.name"></option></template>
        </select>
        <select aria-label="Filter by check-in status" x-model="evStatusFilter" class="sk-sel">
            <option value="">All statuses</option>
            <option value="Processing">Checked In</option>
            <option value="Completed">Checked Out</option>
        </select>
        <button type="button" class="sk-clear" @click="evacueeSearch = ''; evCenterFilter = ''; evStatusFilter = ''">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub">
            Showing <strong x-text="filteredEvacuees.length"></strong> <span x-text="filteredEvacuees.length === 1 ? 'household' : 'households'"></span> &bull;
            <strong x-text="filteredEvacuees.reduce((s,e) => s + e.member_count, 0)"></strong> <span x-text="filteredEvacuees.reduce((s,e) => s + e.member_count, 0) === 1 ? 'individual' : 'individuals'"></span>
        </span>
    </div>

    {{-- ── Evacuee Table ────────────────────────────────────────────────────────────────── --}}
    <div class="sk-scroll" tabindex="0" role="region" aria-label="Evacuees table (scrolls sideways)">
        <table class="sk-tbl sk-tbl-act min-w-[860px]">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Household head</th>
                    <th>Home barangay</th>
                    <th>Evacuation center</th>
                    <th>Checked in</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="ev in filteredEvacuees" :key="ev.ref">
                    <tr class="cursor-pointer group" @click="openEvacueeDetail(ev)" tabindex="0" @keydown.enter.prevent="openEvacueeDetail(ev)">
                        <td class="whitespace-nowrap"><span class="sk-mono" x-text="ev.ref"></span></td>
                        <td class="whitespace-nowrap">
                            <div class="sk-b group-hover:text-[#1D4ED8] transition-colors truncate max-w-[240px]" x-text="ev.head_name"></div>
                            <div class="sk-sub" x-text="ev.member_count + (ev.member_count === 1 ? ' member' : ' members')"></div>
                        </td>
                        <td class="whitespace-nowrap">
                            <div x-text="ev.barangay"></div>
                            <div class="sk-sub" x-show="ev.sitio" x-text="ev.sitio || ''"></div>
                        </td>
                        <td><div class="max-w-[200px] truncate" :class="ev.center ? '' : 'sk-muted'" x-text="ev.center || '—'"></div></td>
                        <td class="whitespace-nowrap" x-text="ev.checked_in ? new Date(ev.checked_in).toLocaleDateString('en-US',{month:'short',day:'numeric'}) : '—'"></td>
                        <td class="whitespace-nowrap">
                            <span :class="stayPill(ev.status)" class="sk-pill" x-text="ev.status === 'Processing' ? 'Checked In' : 'Checked Out'"></span>
                            <span x-show="!ev.registered" class="sk-tag ml-1" style="color: #92400E; background: #FFF7E6;" title="Not linked to a resident record">Unregistered</span>
                        </td>
                        <td class="whitespace-nowrap" @click.stop>
                            {{-- View, then the family's other steps in a menu, so the action
                                 column stays narrow and Status stays in view at 1366 px
                                 (critique 2026-11-03, fourth run). The menu is placed against
                                 the window, so the table's own scrolling never clips it. --}}
                            <div class="inline-flex items-center gap-1" x-data="{ menu: null }" @keydown.escape.stop="menu = null">
                                <button type="button" @click="openEvacueeDetail(ev)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + ev.ref">
                                    <i data-lucide="eye"></i> View
                                </button>
                                <template x-if="ev.status === 'Processing' && (can('sakuna_evacuees','transfer') || can('sakuna_evacuees','check_out'))">
                                    <div>
                                        <button type="button" class="sk-iconbtn" data-row-menu-button :aria-label="'More steps for ' + ev.ref" aria-haspopup="menu" :aria-expanded="menu ? 'true' : 'false'"
                                            @click="const r = $el.getBoundingClientRect(); menu = menu ? null : { top: Math.min(r.bottom + 4, window.innerHeight - 100), right: window.innerWidth - r.right }; if (menu) $nextTick(() => document.querySelector('[data-row-menu] [role=menuitem]:not([style*=none])')?.focus())">
                                            <i data-lucide="ellipsis"></i>
                                        </button>
                                        {{-- Teleported to the page: inside a glass card (backdrop-filter) a
                                             fixed menu is placed against the card and clipped. --}}
                                        <template x-teleport="body">
                                        <div x-show="menu" x-cloak role="menu" data-row-menu :aria-label="'Steps for ' + ev.ref"
                                            @click.outside="if (!$event.target.closest('[data-row-menu-button]')) menu = null" @scroll.window="menu = null"
                                            @keydown.escape.window="menu = null"
                                            @keydown.arrow-down.prevent="const items = [...$el.querySelectorAll('[role=menuitem]')].filter(i => i.offsetParent); items[(items.indexOf(document.activeElement) + 1) % items.length]?.focus()"
                                            @keydown.arrow-up.prevent="const items = [...$el.querySelectorAll('[role=menuitem]')].filter(i => i.offsetParent); items[(items.indexOf(document.activeElement) - 1 + items.length) % items.length]?.focus()"
                                            class="fixed z-50 min-w-[11rem] rounded-2xl bg-white border border-slate-200 shadow-float p-1 text-left"
                                            :style="menu ? 'top:' + menu.top + 'px; right:' + menu.right + 'px' : ''">
                                            <button type="button" role="menuitem" x-show="can('sakuna_evacuees','transfer')"
                                                @click="menu = null; selectedEvacuee = ev; transferToCenter = ''; showTransferForm = true; $nextTick(() => window.renderIcons?.())"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-ink-body rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transfer to another center
                                            </button>
                                            <button type="button" role="menuitem" x-show="can('sakuna_evacuees','check_out')"
                                                @click="menu = null; openCheckout(ev)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-ink-body rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                                <i data-lucide="log-out" class="w-4 h-4"></i> Check out
                                            </button>
                                        </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredEvacuees.length === 0">
                    <td colspan="7">
                        <div class="sk-empty" style="padding: 36px 16px;">
                            <i data-lucide="users"></i>
                            <span>No evacuees match your filters.</span>
                            <button type="button" @click="evacueeSearch = ''; evCenterFilter = ''; evStatusFilter = ''" class="sk-clear">Clear filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

</div>{{-- /loaded content --}}

{{-- ── Transfer Modal ──────────────────────────────────────────────────────────────────── --}}
<div x-show="showTransferForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm" @click.self="showTransferForm = false"
     x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-trap="showTransferForm" role="dialog" aria-modal="true" @keydown.escape.window="showTransferForm = false">
    <div class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0"><i data-lucide="arrow-right-left" class="w-5 h-5 text-blue-600"></i></div>
            <div>
                <h3 class="text-base font-semibold text-ink-body">Transfer household</h3>
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
            <button @click="showTransferForm = false" class="sk-btn">Cancel</button>
            <button :disabled="!transferToCenter" @click="transferEvacuee(selectedEvacuee, transferToCenter); showTransferForm = false"
                class="sk-btn sk-btn-p">Confirm transfer</button>
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
                <h3 class="text-base font-semibold text-ink-body">Confirm check-out</h3>
                <p class="text-xs text-ink-muted" x-text="checkoutEvacuee ? checkoutEvacuee.ref : ''"></p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-5">
            Confirm that <strong x-text="checkoutEvacuee ? checkoutEvacuee.head_name : ''"></strong>'s household (<span x-text="(checkoutEvacuee?.member_count ?? 0) + ((checkoutEvacuee?.member_count ?? 0) === 1 ? ' member' : ' members')"></span>) has left the center.
        </p>
        <div class="flex gap-3">
            <button @click="showCheckoutConfirm = false; checkoutEvacuee = null"
                class="sk-btn">Cancel</button>
            <button @click="checkOutEvacuee(checkoutEvacuee); showCheckoutConfirm = false; checkoutEvacuee = null"
                class="sk-btn sk-btn-navy">Confirm check-out</button>
        </div>
    </div>
</div>

{{-- ── Evacuee Detail Modal ─────────────────────────────────────────────────────────────── --}}
<div x-show="showEvacueeDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showEvacueeDetail = false"
        x-trap="showEvacueeDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showEvacueeDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="user" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-ink-body" x-text="selectedEvacuee?.head_name ?? '—'"></h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs text-ink-muted" x-text="selectedEvacuee?.ref ?? ''"></span>
                        <span class="text-ink-faint">·</span>
                        <span :class="'sk-pill ' + stayPill(selectedEvacuee?.status ?? '')" x-text="selectedEvacuee?.status === 'Processing' ? 'Checked In' : 'Checked Out'"></span>
                    </div>
                </div>
            </div>
            <button aria-label="Close window" @click="showEvacueeDetail = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">

            <div data-shelter-record>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5"></i> Household</h4>
                    <button type="button" x-show="mayEditShelter() && !recordEditing" @click="editShelterRecord()" class="text-xs font-medium text-brand-600 hover:text-brand-700">Edit</button>
                </div>
                <div x-show="recordEditing" x-cloak class="mb-3 grid grid-cols-2 gap-3 rounded-2xl border border-brand-600/10 p-3" data-shelter-edit>
                    <div><label for="record-sitio" class="text-xs text-ink-muted mb-1 block">Sitio</label><input id="record-sitio" type="text" x-model="recordForm.sitio" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="record-purok" class="text-xs text-ink-muted mb-1 block">Purok</label><input id="record-purok" type="text" x-model="recordForm.purok" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <div><label for="record-hh" class="text-xs text-ink-muted mb-1 block">Household code (typed)</label><input id="record-hh" type="text" x-model="recordForm.hh_code" :disabled="!!selectedEvacuee?.household_ref" :placeholder="selectedEvacuee?.household_ref ? 'From the registry' : ''" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 disabled:opacity-50"></div>
                    <div><label for="record-count" class="text-xs text-ink-muted mb-1 block">Headcount</label><input id="record-count" type="number" :min="Math.max(1, (selectedEvacuee?.members || []).length)" x-model="recordForm.member_count" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></div>
                    <p class="col-span-2 text-xs text-ink-muted">A sitio is not a purok; leave the purok blank if it is not known. The headcount cannot be lower than the members listed below.</p>
                    <div class="col-span-2 flex justify-end gap-2">
                        <button type="button" @click="recordEditing = false" class="sk-btn sk-btn-sm">Cancel</button>
                        <button type="button" :disabled="recordSaving" @click="saveShelterRecord()" class="sk-btn sk-btn-p sk-btn-sm" x-text="recordSaving ? 'Saving…' : 'Save'"></button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Household Head</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.head_name ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Total Members</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="(selectedEvacuee?.member_count ?? 0) + ((selectedEvacuee?.member_count ?? 0) === 1 ? ' person' : ' persons')"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Barangay</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.barangay ?? '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Home Sitio</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.sitio || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Purok</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.purok || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Household Code</p><p class="text-sm font-mono font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.household_ref || selectedEvacuee?.hh_code || '—'"></p><p class="text-xs mt-0.5" x-show="selectedEvacuee?.household_ref || selectedEvacuee?.hh_code" :class="selectedEvacuee?.household_ref ? 'text-emerald-700' : 'text-amber-600'" x-text="selectedEvacuee?.household_ref ? 'From the registry' : 'Typed at the centre, not verified'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2"><p class="text-xs text-ink-muted">Resident Record</p><p class="text-sm font-medium mt-0.5" :class="selectedEvacuee?.registered ? 'text-emerald-700' : 'text-amber-600'" x-text="selectedEvacuee?.registered ? 'Linked' : 'Unregistered household'"></p></div>
                </div>
            </div>

            <div data-faced-panel>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="id-card" class="w-3.5 h-3.5"></i> FACED Card (DSWD)</h4>
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
                        <button type="button" @click="facedEditing = false" class="sk-btn sk-btn-sm">Cancel</button>
                        <button type="button" :disabled="facedSaving" @click="saveFaced()" class="sk-btn sk-btn-p sk-btn-sm" x-text="facedSaving ? 'Saving…' : 'Save FACED details'"></button>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Current Location</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Evacuation Center</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.center || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Checked In</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedEvacuee?.checked_in ? new Date(selectedEvacuee.checked_in).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '—'"></p></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5"><i data-lucide="users-round" class="w-3.5 h-3.5"></i> Members</h4>
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
                        <button type="button" @click="memberEditing = null" class="sk-btn sk-btn-sm">Cancel</button>
                        <button type="button" :disabled="memberSaving || !memberForm.name.trim()" @click="saveMember()" class="sk-btn sk-btn-p sk-btn-sm" x-text="memberSaving ? 'Saving…' : (memberEditing === 'new' ? 'Add member' : 'Save member')"></button>
                    </div>
                </div>
                <div class="divide-y divide-brand-600/[.08] border border-brand-600/10 rounded-xl overflow-hidden">
                    <template x-for="(m, idx) in (selectedEvacuee?.members || [])" :key="m.id ?? idx">
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate"><span x-text="m.name"></span> <span x-show="m.is_head" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">Head</span> <span x-show="!m.sex || (m.age == null && !m.dob)" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200" x-text="!m.sex ? 'No sex recorded' : 'No age recorded'"></span></p>
                                <p class="text-xs text-ink-muted" x-text="[m.relation, m.age ? m.age + ' yrs' : null, m.sex, m.dob ? 'born ' + m.dob : null].filter(Boolean).join(' · ') || '—'"></p>
                                <p class="text-xs text-ink-muted" x-show="m.education || m.occupation" x-text="[m.education, m.occupation].filter(Boolean).join(' · ')"></p>
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end shrink-0 max-w-[50%]">
                                <span x-show="m.vulnerability" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-50 text-violet-700 border border-violet-200" x-text="m.vulnerability"></span>
                                <template x-for="n in (m.needs || [])" :key="n">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200" x-text="n"></span>
                                </template>
                                <button type="button" x-show="mayEditShelter() && m.id && memberEditing === null" @click="editMember(m)" :aria-label="'Edit ' + m.name" class="text-xs font-medium text-brand-600 hover:text-brand-700 ml-1">Edit</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!(selectedEvacuee?.members || []).length" class="text-xs text-ink-muted italic px-4 py-4">No individual member records — only the household count was captured at check-in.</p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="history" class="w-3.5 h-3.5"></i> Movement History</h4>
                <div class="space-y-0">
                    <template x-for="(mv, idx) in (selectedEvacuee?.movements || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedEvacuee.movements.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="w-2 h-2 rounded-full bg-brand-400 mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-semibold text-slate-700 capitalize" x-text="mv.verb.replace('_',' ')"></span>
                                <span class="text-xs text-ink-muted" x-text="(mv.from ? ' from ' + mv.from : '') + (mv.to ? ' to ' + mv.to : '')"></span>
                            </div>
                            <span class="text-xs text-ink-muted whitespace-nowrap flex-shrink-0" x-text="mv.at ? new Date(mv.at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedEvacuee?.movements || []).length" class="text-xs text-ink-muted py-2">No movements recorded yet.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex flex-wrap items-center gap-2">
            <button type="button" @click="openFacedCard(selectedEvacuee)" :disabled="facedLoading" data-faced-open
                class="sk-btn sk-btn-soft">
                <i data-lucide="printer" class="w-4 h-4"></i> FACED Card
            </button>
            <button x-show="can('sakuna_evacuees','transfer') && selectedEvacuee?.status === 'Processing'" @click="transferToCenter = ''; showTransferForm = true; showEvacueeDetail = false; $nextTick(() => window.renderIcons?.())"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transfer center
            </button>
            <button x-show="can('sakuna_evacuees','check_out') && selectedEvacuee?.status === 'Processing'" @click="openCheckout(selectedEvacuee); showEvacueeDetail = false"
                class="sk-btn ml-auto">
                <i data-lucide="log-out" class="w-4 h-4"></i> Check out
            </button>
        </div>
    </div>
</div>

{{-- ── Evacuee Registration Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showEvacueeReg" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showEvacueeReg = false"
        x-trap="showEvacueeReg"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showEvacueeReg = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div>
                <h3 class="font-semibold text-ink-body">Register evacuee household</h3>
                <p class="text-xs text-ink-muted mt-0.5">Unregistered households are accepted — a disaster is not the moment to require an account.</p>
            </div>
            <button aria-label="Close window" @click="showEvacueeReg = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
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
                                <p class="text-xs text-ink-muted"><span x-show="f.source === 'kept_by_resident'" class="mr-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-violet-50 text-violet-700 border border-violet-200">Kept by the resident</span><span class="font-mono" x-text="f.household_ref || f.ref"></span> <span x-show="f.already_sheltered" class="text-amber-700" x-text="f.already_sheltered ? '· already at ' + f.already_sheltered.center + ' (' + f.already_sheltered.ref + ')' : ''"></span></p>
                            </button>
                        </template>
                    </div>
                </div>
                <div x-show="registryPicked" x-cloak class="flex items-start justify-between gap-3" data-registry-picked>
                    <div class="text-xs text-ink-body">
                        <p class="font-medium"><span x-text="registryPicked?.source === 'kept_by_resident' ? 'Filled from the list the resident keeps: ' : 'Filled from the registry: '"></span><span class="font-mono" x-text="registryPicked?.household_ref || registryPicked?.ref"></span></p>
                        <p class="text-ink-muted">Untick anyone who did not come to the center. Corrections here change this shelter record only, not the registry.</p>
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
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add member
                    </button>
                </div>
                <datalist id="faced-education"><option>None</option><option>Elementary</option><option>High School</option><option>Senior High School</option><option>Vocational</option><option>College</option><option>Post-graduate</option></datalist>
                <div class="space-y-2">
                    <template x-for="(m, idx) in checkInForm.members" :key="idx">
                        <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl border border-slate-200 bg-white" :class="m.arrived === false ? 'opacity-50' : ''">
                            <label x-show="registryPicked" class="flex items-center gap-1 text-xs text-ink-muted"><input type="checkbox" x-model="m.arrived" :aria-label="'Member ' + (idx+1) + ' arrived at the center'" class="rounded border-slate-300 text-brand-600"> Here</label>
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
                            <button :aria-label="'Remove family member ' + (idx + 1)" title="Remove this member" type="button" @click="removeCheckInMember(idx)" class="p-1.5 text-ink-muted hover:text-rose-600 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                            {{-- Occupation (owner, 2026-10-29): a registered resident's comes from the registry. --}}
                            <div class="basis-full flex flex-wrap items-center gap-2 text-xs" x-show="m.individual_id && m.registry_has_occupation && !m.update_registry">
                                <span class="text-ink-muted">Occupation: recorded from the resident's registry record.</span>
                                <button type="button" x-show="can('const_resident','edit')" @click="m.update_registry = true" class="text-brand-600 hover:text-brand-700 font-medium">Correct it in the registry</button>
                            </div>
                            <div class="basis-full" x-show="!(m.individual_id && m.registry_has_occupation) || m.update_registry">
                                <x-livelihood-select model="m" dynamic-id="'checkin-m-lv-' + idx"
                                    label-class="block text-xs font-medium text-ink-muted mb-1"
                                    input-class="w-full text-xs border border-slate-200 rounded-xl px-2 py-1.5" />
                                <p class="text-xs text-ink-muted mt-1" x-show="m.individual_id && !m.registry_has_occupation && can('const_resident','edit')">Saved to this resident's registry record too.</p>
                                <p class="text-xs text-amber-700 mt-1" x-show="m.update_registry">This changes the resident's registry record. <button type="button" class="underline" @click="m.update_registry = false; m.livelihood_code = ''; m.livelihood_other = ''">Keep the registry's</button></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="checkInDuplicate" x-cloak role="alert" class="mx-6 mb-3 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-2" data-checkin-duplicate>
            <p class="flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><span x-text="checkInDuplicate"></span></p>
            <button type="button" :disabled="checkInSaving" @click="submitCheckIn(true)" class="sk-btn sk-btn-sm">It's a different family — check in</button>
        </div>
        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-between">
            <button @click="showEvacueeReg = false" class="sk-btn">Cancel</button>
            <button :disabled="checkInSaving || !checkInForm.center || !checkInForm.head_name || !checkInForm.barangay" @click="submitCheckIn()"
                class="sk-btn sk-btn-p">
                <i data-lucide="circle-check" class="w-4 h-4"></i>
                <span x-text="checkInSaving ? 'Checking in…' : 'Complete check-in'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── FACED Card (print) ─────────────────────────────────────────────────────────────── --}}
<template x-if="facedCard">
    {{-- A document viewer sized to the screen (owner, 2026-11-03): the title bar
         stays put, the sheet scrolls on a grey desk when the family is large,
         and on screen the sheet is as tall as its content, never a fixed A4
         with blank space under it. Print still makes the exact A4 page. --}}
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 bg-navy-950/60 backdrop-blur-sm" @click.self="facedCard = null"
         x-trap="facedCard" role="dialog" aria-modal="true" aria-labelledby="faced-title" @keydown.escape.window="facedCard = null">
        <div class="bg-white rounded-[24px] shadow-float w-full max-w-[1000px] max-h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-brand-600/10 shrink-0">
                <div class="min-w-0">
                    <h3 id="faced-title" class="font-semibold text-ink-body leading-tight">Family Assistance Card in Emergencies and Disasters</h3>
                    <p class="text-xs text-ink-muted truncate" x-text="[facedCard.ref, facedCard.head_name, (facedCard.members || []).length ? (facedCard.members.length + ((facedCard.members.length === 1) ? ' member' : ' members')) : null].filter(Boolean).join(' · ')"></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="printFacedCard()" class="sk-btn sk-btn-p sk-btn-sm"><i data-lucide="printer" class="w-3.5 h-3.5"></i> Print</button>
                    <button type="button" aria-label="Close window" @click="facedCard = null" class="sk-mclose"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto bg-[#E9EEF6] p-3 sm:p-5">
            <div id="dp-print-area" class="doc-paper faced-sheet mx-auto bg-white px-6 py-6 sm:px-10 sm:py-8 text-[12px] leading-snug text-black" data-faced-card>
                <div class="grid grid-cols-[1fr_auto_1fr] items-start gap-4 pb-3 border-b-2 border-black">
                    <div>
                        <p class="font-bold">DSWD-DRMB-FACED</p>
                        <p>Serial No. <span class="font-mono font-semibold" x-text="facedCard.faced?.serial || '__________'"></span></p>
                    </div>
                    <div class="text-center">
                        <p>Republic of the Philippines · Municipality of Esperanza</p>
                        <p class="font-bold">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE</p>
                        <p class="text-[14px] font-bold mt-1 leading-tight">FAMILY ASSISTANCE CARD IN EMERGENCIES AND DISASTERS (FACED)</p>
                    </div>
                    <p class="text-right">Ref. <span class="font-mono" x-text="facedCard.ref"></span></p>
                </div>

                <p class="faced-h">Location of the Affected Family</p>
                <table class="faced-kv"><tbody>
                    <tr><td class="w-1/4 py-0.5">Barangay</td><td class="font-semibold" x-text="facedCard.barangay"></td><td class="w-1/4">Sitio / Purok</td><td class="font-semibold" x-text="facedCard.sitio || '—'"></td></tr>
                    <tr><td class="py-0.5">Evacuation center</td><td class="font-semibold" x-text="facedCard.center || '—'"></td><td>Checked in</td><td x-text="facedCard.checked_in ? new Date(facedCard.checked_in).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) : '—'"></td></tr>
                </tbody></table>

                <p class="faced-h">Head of the Family</p>
                <table class="faced-kv"><tbody>
                    <tr><td class="w-1/4 py-0.5">Name</td><td class="font-semibold" x-text="facedCard.head_name"></td><td class="w-1/4">Contact no.</td><td x-text="facedCard.faced?.contact || '—'"></td></tr>
                    <tr><td class="py-0.5">Monthly family net income</td><td x-text="facedCard.faced?.monthly_income || '—'"></td><td>4Ps beneficiary</td><td x-text="facedCard.faced?.is_4ps ? 'Yes' : 'No'"></td></tr>
                    <tr><td class="py-0.5">House ownership</td><td x-text="facedCard.faced?.house_ownership || '—'"></td><td>Shelter damage</td><td x-text="facedCard.faced?.shelter_damage || '—'"></td></tr>
                    <tr><td class="py-0.5">Indigenous People</td><td colspan="3" x-text="facedCard.faced?.is_ip ? 'Yes' + (facedCard.faced.ip_ethnicity ? ' — ' + facedCard.faced.ip_ethnicity : '') : 'No'"></td></tr>
                </tbody></table>

                <p class="faced-h">Family Information</p>
                <table class="faced-grid">
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

                <p class="faced-h">Family Assistance Record</p>
                <table class="faced-grid" data-faced-assistance>
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

                <div class="faced-signs">
                    <p>Signature / thumbmark of the family head</p>
                    <p>Barangay Captain</p>
                    <p>MSWDO / LSWDO</p>
                    <p>Date registered</p>
                </div>
            </div>
            </div>
        </div>
    </div>
</template>

</div>{{-- end x-data wrapper --}}
