@php
    $requests = [
        ['ref' => 'AR-2026-0151', 'citizen' => 'Elmer Bantillo', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Santiago', 'submitted' => 'Jul 9, 2026', 'status' => 'Pending Review'],
        ['ref' => 'AR-2026-0148', 'citizen' => 'Angelica Fajardo', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Rizal', 'submitted' => 'Jul 6, 2026', 'status' => 'Assigned'],
        ['ref' => 'AR-2026-0142', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'citizen' => 'Rodrigo Palma', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Domorog', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'citizen' => 'Teresita Salazar', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Agoho', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0044', 'citizen' => 'Michael Bacus', 'type' => 'Food / Relief Assistance', 'office' => 'MSWDO', 'barangay' => 'Iligan', 'submitted' => 'Mar 18, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0037', 'citizen' => 'Josefina Marbella', 'type' => 'Social Pension (Indigent Senior)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Feb 10, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0022', 'citizen' => 'Rosemarie Tan', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Jan 28, 2026', 'status' => 'Waiting Requirements', 'requirements' => [
            ['key' => 'req_solo_parent_id', 'name' => 'Solo Parent ID', 'file' => 'Rosemarie-SoloParentID.jpg', 'status' => 'Approved', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
            ['key' => 'req_psa_birth', 'name' => "PSA Birth Certificate(s) of children", 'file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.', 'versions' => [
                ['file' => 'PSA-Birth-Cert.jpg', 'status' => 'Rejected', 'date' => 'Jan 28, 2026', 'reject_reason' => 'Unreadable', 'remarks' => 'The scanned copy is too blurry to read. Please rescan or take a clearer photo.'],
            ]],
            ['key' => 'req_brgy_cert', 'name' => 'Barangay Certification', 'file' => 'Brgy-Cert-Baras.jpg', 'status' => 'Pending Review', 'reject_reason' => null, 'remarks' => null, 'versions' => []],
        ]],
        ['ref' => 'AR-2026-0009', 'citizen' => 'Ferdinand Cortez', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jan 8, 2026', 'status' => 'Rejected', 'remarks' => 'Missing PSA Death Certificate. Please resubmit with a certified copy attached.'],
    ];

    // Classification lookup for the mock request rows above, per the Dokyu &
    // Tulong routing plan -- centralized here rather than hand-edited per row.
    $tulongClassification = [
        'Medical Assistance (AICS)' => ['category' => 'Medical & Health', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Burial Assistance (AICS)' => ['category' => 'Burial & Bereavement', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Educational Assistance' => ['category' => 'Education', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'Financial Assistance (AICS)' => ['category' => 'Financial Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'Food / Relief Assistance' => ['category' => 'Food & Relief', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'Social Pension (Indigent Senior)' => ['category' => 'Social Protection', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'Solo Parent Cash Assistance' => ['category' => 'Social Protection', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
    ];
    $assignedByRef = [
        'AR-2026-0148' => 'Corazon Villareal', 'AR-2026-0142' => 'Corazon Villareal',
    ];
    foreach ($requests as &$r) {
        $r = array_merge($r, $tulongClassification[$r['type']] ?? ['category' => 'Social Protection', 'office' => $r['office'], 'level' => 'Municipal']);
        $r['assigned'] = $assignedByRef[$r['ref']] ?? null;
    }
    unset($r);

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Approved / Released', 'done' => 'Completed'];
    $actionStatuses = ['Pending Review', 'Assigned', 'Waiting Requirements', 'Processing'];
    $releaseStatuses = ['Approved', 'Released'];
@endphp

<x-layouts.admin title="Tulong" subtitle="Review and manage citizen assistance requests." active="assistance-requests">
    <div
        x-data="{
            tab: 'all',
            search: '',
            categoryFilter: '',
            officeFilter: '',
            levelFilter: '',
            assignedFilter: '',
            barangayFilter: '',
            showAdvancedFilters: false,
            open: false,
            selected: null,
            processing: false,
            rejectRemarks: '',
            requests: @js($requests),
            actionStatuses: @js($actionStatuses),
            releaseStatuses: @js($releaseStatuses),
            get categories() { return [...new Set(this.requests.map(r => r.category))].sort(); },
            get offices() { return [...new Set(this.requests.map(r => r.office))].sort(); },
            get barangays() { return [...new Set(this.requests.map(r => r.barangay))].sort(); },
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Pending Review': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Assigned': ['bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
                'Processing': ['bg-brand-50 text-brand-700 ring-brand-200', 'bg-brand-500'],
                'Waiting Requirements': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Released': ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
            },
            matchesTab(r) {
                if (this.tab === 'all') return true;
                if (this.tab === 'action') return this.actionStatuses.includes(r.status);
                if (this.tab === 'release') return this.releaseStatuses.includes(r.status);
                if (this.tab === 'done') return r.status === 'Completed';
                return true;
            },
            get filteredRequests() {
                const q = this.search.trim().toLowerCase();
                return this.requests.filter(r =>
                    this.$store.session.inScope(r.barangay) &&
                    this.$store.session.inDeptScope(r.office) &&
                    this.matchesTab(r) &&
                    (this.categoryFilter === '' || r.category === this.categoryFilter) &&
                    (this.officeFilter === '' || r.office === this.officeFilter) &&
                    (this.levelFilter === '' || r.level === this.levelFilter) &&
                    (this.assignedFilter === '' || (this.assignedFilter === 'unassigned' ? !r.assigned : r.assigned === this.assignedFilter)) &&
                    (this.barangayFilter === '' || r.barangay === this.barangayFilter) &&
                    (q === '' || r.ref.toLowerCase().includes(q) || r.citizen.toLowerCase().includes(q))
                );
            },
            clearAdvancedFilters() { this.categoryFilter = ''; this.officeFilter = ''; this.levelFilter = ''; this.assignedFilter = ''; this.barangayFilter = ''; },
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Pending Review': 0, 'Assigned': 1, 'Waiting Requirements': 1, 'Processing': 1, 'Approved': 2, 'Released': 3, 'Completed': 4, 'Rejected': 0 };
                return map[status] ?? 0;
            },
            reject() {
                if (this.processing || !this.rejectRemarks.trim()) return;
                this.processing = true;
                setTimeout(() => {
                    this.selected.status = 'Rejected';
                    this.selected.remarks = this.rejectRemarks.trim();
                    this.rejectRemarks = '';
                    this.processing = false;
                    this.open = false;
                    this.$dispatch('toast', { message: 'Case ' + this.selected.ref + ' rejected.', variant: 'error' });
                }, 700);
            },
            rejectReasons: ['Invalid document', 'Incorrect document', 'Unreadable', 'Expired', 'Wrong document', 'Missing information', 'Does not meet requirement', 'Other'],
            verifyRequirement(req) {
                req.status = 'Approved';
                req.reject_reason = null;
                req.remarks = null;
                this.$dispatch('toast', { message: req.name + ' verified.', variant: 'success' });
            },
            rejectRequirement(req) {
                if (!req._reason) return;
                req.versions = req.versions || [];
                req.versions.push({ file: req.file, status: 'Rejected', date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }), reject_reason: req._reason, remarks: req._remarks || '' });
                req.status = 'Rejected';
                req.reject_reason = req._reason;
                req.remarks = req._remarks || '';
                req._rejecting = false;
                req._reason = '';
                req._remarks = '';
                if (this.selected.status !== 'Rejected') {
                    this.selected.status = 'Waiting Requirements';
                }
                this.$dispatch('toast', { message: req.name + ' rejected.', variant: 'error' });
            },
            approve() {
                if (this.processing) return;
                this.processing = true;
                setTimeout(() => {
                    const order = ['Submitted', 'Assigned', 'Approved', 'Released', 'Completed'];
                    const next = Math.min(this.stepIndex(this.selected.status) + 1, order.length - 1);
                    this.selected.status = order[next];
                    this.processing = false;
                    this.open = false;
                    this.$dispatch('toast', { message: this.selected.ref + ' moved to next stage: ' + this.selected.status + '.', variant: 'success' });
                }, 700);
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('tulong')" x-cloak>
            <x-admin.access-restricted module="Tulong (Citizen Assistance)" icon="hand-heart" />
        </div>

        <div x-show="$store.session.can('tulong')" x-cloak>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card @click="tab = 'all'" class="cursor-pointer" label="Active Cases" value="417" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="34" icon="inbox" color="orange" sublabel="Review or assignment" :delay="40" />
            <x-ui.stat-card @click="tab = 'release'" class="cursor-pointer" label="Approved / Released" value="112" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card :href="route('admin.analytics')" label="Budget Utilized (Q3)" value="₱1.8M" icon="wallet" color="brand" sublabel="of ₱2.5M allocated" :delay="120" />
            <x-ui.stat-card :href="route('admin.reports')" label="Ayuda Received" value="₱1.8M" icon="hand-coins" color="gold" sublabel="Disbursed to citizens" :delay="160" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" x-model="search" placeholder="Search reference or citizen..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <select x-model="categoryFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0">
                    <option value="">All Categories</option>
                    <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <button
                    type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border transition-colors shrink-0"
                    :class="showAdvancedFilters || officeFilter || levelFilter || assignedFilter || barangayFilter ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
                >
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    @foreach($tabs as $key => $label)
                        <button
                            type="button" @click="tab = '{{ $key }}'"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' cases'"></p>
            </div>

            <div
                x-show="showAdvancedFilters" x-cloak
                x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100"
            >
                <select x-model="officeFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Offices</option>
                    <template x-for="o in offices" :key="o"><option :value="o" x-text="o"></option></template>
                </select>
                <select x-model="levelFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Levels</option>
                    <option value="Municipal">Municipal</option>
                    <option value="Multi-Office">Multi-Office</option>
                </select>
                <select x-model="barangayFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <select x-model="assignedFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">Anyone / Unassigned</option>
                    <option value="unassigned">Unassigned only</option>
                </select>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors" x-show="officeFilter || levelFilter || assignedFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Assigned</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="r in filteredRequests" :key="r.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors group cursor-pointer" @click="open = true; selected = r; rejectRemarks = ''">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="r.ref" @click.stop="open = true; selected = r; rejectRemarks = ''"></td>
                        <td class="px-4 py-3" @click.stop="open = true; selected = r; rejectRemarks = ''">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(r.citizen || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="r.citizen"></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <p class="text-slate-700" x-text="r.type"></p>
                            <p class="text-[11px] text-slate-400 mt-0.5" x-text="r.category"></p>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">
                            <span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap" x-text="r.office"></span>
                            <span
                                class="inline-block ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap"
                                :class="{ 'bg-brand-50 text-brand-700': r.level === 'Municipal', 'bg-purple-50 text-purple-700': r.level === 'Multi-Office' }"
                                x-text="r.level"
                            ></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell"><span x-text="'Brgy. ' + r.barangay"></span></td>
                        <td class="px-4 py-3 text-slate-500 hidden xl:table-cell">
                            <span x-show="r.assigned" x-text="r.assigned"></span>
                            <span x-show="!r.assigned" class="text-slate-300 italic">Unassigned</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell" x-text="r.submitted"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[r.status] || statusStyles['Draft'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[r.status] || statusStyles['Draft'])[1]"></span>
                                <span x-text="r.status"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop>
                            <button @click="open = true; selected = r; rejectRemarks = ''" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Review</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No cases match these filters.</p>
        </div>

        <x-ui.modal title="Review Tulong Case" maxWidth="lg">
            <template x-if="selected">
                <div>
                    <p class="text-xs text-slate-400 mb-4 font-mono" x-text="selected.ref"></p>

                    <div class="flex items-center mb-6 px-1">
                        @foreach(['Submitted', 'Assigned', 'Approved', 'Released', 'Completed'] as $i => $step)
                            <div class="flex-1 flex items-center">
                                <div class="flex flex-col items-center gap-2 text-center">
                                    <span
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-semibold transition-all duration-[600ms] ease-standard"
                                        :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'bg-purple-600 text-white shadow-card ring-4 ring-purple-100' : 'bg-slate-100 text-slate-400')"
                                    >
                                        <i data-lucide="check" class="w-4 h-4" x-show="stepIndex(selected.status) > {{ $i }}" x-cloak></i>
                                        <span x-show="stepIndex(selected.status) <= {{ $i }}">{{ $i + 1 }}</span>
                                    </span>
                                    <span class="text-[11px] max-w-[80px] leading-tight transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) === {{ $i }} ? 'text-navy-900 font-semibold' : 'text-slate-400'">{{ $step }}</span>
                                </div>
                                @if(!$loop->last)
                                    <div class="flex-1 h-1 rounded-full mx-1.5 -mt-6 transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-100'"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="hand-heart" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assistance Type</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Handling Office</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Assigned Personnel</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-purple-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Submitted</p>
                                    <p class="text-sm font-medium text-navy-900 truncate" x-text="selected.submitted"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-2 mt-3"
                        x-show="!selected.assigned && (!$store.session.account || ['department_head', 'municipal_admin', 'super_admin'].includes($store.session.account?.roleKey))"
                    >
                        <p class="text-xs text-slate-400">This case hasn't been routed to a processor yet.</p>
                        <x-ui.button
                            variant="secondary" size="sm" icon="user-plus"
                            @click="selected.assigned = $store.session.account?.name || 'You'; $dispatch('toast', { message: 'Assigned to yourself.', variant: 'success' })"
                        >Assign to me</x-ui.button>
                    </div>

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Requirements</p>
                            <p class="text-[11px] text-slate-400" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="req in (selected.requirements || [])" :key="req.key">
                                <div class="rounded-xl border p-3" :class="{ 'border-emerald-200 bg-emerald-50/30': req.status === 'Approved', 'border-rose-200 bg-rose-50/30': req.status === 'Rejected', 'border-slate-200': req.status === 'Pending Review' }">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <i data-lucide="file-text" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                            <div class="min-w-0">
                                                <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="req.file"></p>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset shrink-0"
                                            :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Pending Review' }"
                                        ><span x-text="req.status === 'Approved' ? 'Verified' : req.status"></span></span>
                                    </div>

                                    <div x-show="req.status === 'Rejected'" class="mt-2 pt-2 border-t border-rose-100/70 text-[11px]">
                                        <p class="font-semibold text-rose-700" x-text="req.reject_reason"></p>
                                        <p class="text-rose-600 mt-0.5" x-show="req.remarks" x-text="req.remarks"></p>
                                        <button type="button" x-show="(req.versions || []).length" @click="req._showVersions = !req._showVersions" class="mt-1.5 text-slate-400 hover:text-slate-600 transition-colors">
                                            <span x-text="(req._showVersions ? 'Hide' : 'Show') + ' version history (' + req.versions.length + ')'"></span>
                                        </button>
                                        <div x-show="req._showVersions" x-cloak class="mt-1.5 space-y-1 pl-2 border-l-2 border-rose-100">
                                            <template x-for="(v, vi) in (req.versions || [])" :key="vi">
                                                <p class="text-slate-400">Version <span x-text="vi + 1"></span> — <span x-text="v.status"></span> · <span x-text="v.date"></span><span x-show="v.reject_reason"> · <span x-text="v.reject_reason"></span></span></p>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 mt-2" x-show="req.status === 'Pending Review' && !req._rejecting">
                                        <button type="button" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Verify</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="req._rejecting = true; req._reason = ''; req._remarks = ''" class="text-[11px] font-medium text-rose-600 hover:underline">Reject</button>
                                    </div>

                                    <div x-show="req._rejecting" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-2">
                                        <select x-model="req._reason" class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">Select a reason...</option>
                                            <template x-for="reason in rejectReasons" :key="reason"><option :value="reason" x-text="reason"></option></template>
                                        </select>
                                        <textarea x-model="req._remarks" rows="2" placeholder="Remarks for the citizen (optional)..." class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-1.5 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-bind:disabled="!req._reason" @click="rejectRequirement(req)" class="text-[11px] font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:pointer-events-none rounded-lg px-2.5 py-1.5 transition-colors">Confirm Reject</button>
                                            <button type="button" @click="req._rejecting = false" class="text-[11px] font-medium text-slate-400 hover:text-slate-600">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-3 mt-3 flex items-start gap-2.5" x-show="selected.status === 'Rejected' && selected.remarks">
                        <i data-lucide="circle-x" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"></i>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-rose-700">Rejection Remarks</p>
                            <p class="text-xs text-rose-600 mt-0.5" x-text="selected.remarks"></p>
                        </div>
                    </div>

                    <div class="mt-3" x-show="!['Rejected', 'Released', 'Completed', 'Archived', 'Cancelled'].includes(selected.status)">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Rejection Remarks <span class="text-slate-400 font-normal">(required to reject — this is what the citizen will see to correct and resubmit)</span></label>
                        <textarea
                            x-model="rejectRemarks" rows="2" placeholder="e.g. Missing Medical Abstract. Please resubmit with this requirement attached."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-3.5 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                        ></textarea>
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button variant="danger" size="sm" x-bind:disabled="processing || !rejectRemarks.trim()" @click="reject()">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                        <span x-text="processing ? 'Rejecting…' : 'Reject'"></span>
                    </span>
                </x-ui.button>
                <x-ui.button size="sm" x-bind:disabled="processing" @click="approve()">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                        <i data-lucide="check" class="w-3.5 h-3.5" x-show="!processing"></i>
                        <span x-text="processing ? 'Processing…' : 'Approve & Continue'"></span>
                    </span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
