@php
    $barangays = config('esperanza.barangays');
    // Shared with admin/papeles.blade.php (which surfaces a citizen's own
    // receipts as "Resibo" documents) — see config/esperanza_payments.php.
    $payments = config('esperanza_payments');
    // Citizen name -> avatar map, built here rather than in a config file —
    // see config/esperanza_citizen_avatars.php's own header for why a
    // config file can't safely call config() on another config file from
    // its own top level (Laravel loads them in filename order during
    // bootstrap) — so a resident's real picture shows up here too, not
    // just on their own Constituents profile.
    $citizenAvatarData = config('esperanza_citizen_avatars');
    $generatedAvatarFor = function ($acctId) use ($citizenAvatarData) {
        $bg = $citizenAvatarData['backgrounds'][crc32($acctId) % count($citizenAvatarData['backgrounds'])];
        return 'https://api.dicebear.com/9.x/personas/svg?seed=' . urlencode($acctId) . '&backgroundColor=' . $bg;
    };
    $citizenAvatars = [];
    foreach (config('esperanza_constituents')['individuals'] as $ind) {
        if (empty($ind['citizen_account_id'])) {
            continue;
        }
        $acctId = $ind['citizen_account_id'];
        $citizenAvatars[$ind['name']] = $citizenAvatarData['photoByAccountId'][$acctId] ?? $generatedAvatarFor($acctId);
    }

    // Classification lookup for the mock payment rows above, keyed by the
    // exact 'item' string -- reuses the same category/office/level values
    // already established for these services in admin/document-requests.blade.php
    // and admin/assistance-requests.blade.php, per the Dokyu & Tulong
    // routing plan, so Payments stays organized the same way as both.
    $paymentsClassification = [
        'Cedula (Community Tax Certificate)' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Business Permit (New)' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Barangay Clearance' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Certificate of Residency' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Business Permit Renewal' => ['category' => 'Business & Permits', 'office' => 'Business Permits & Licensing Office', 'level' => 'Multi-Office'],
        'Real Property Tax Clearance' => ['category' => 'Tax & Property', 'office' => "Municipal Treasurer's Office", 'level' => 'Municipal'],
        'Social Pension — Documentary Fee' => ['category' => 'Social Protection', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'Barangay Certification (General Purpose)' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Certificate of Live Birth (Certified Copy)' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Application for Marriage License' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Certified Copy of Marriage Certificate' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Birth' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Delayed Registration of Death' => ['category' => 'Civil Registry', 'office' => 'Office of the Municipal Civil Registrar', 'level' => 'Municipal'],
        'Barangay Certification for Late Registration of Birth' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Barangay Certification for Registration of Death' => ['category' => 'Barangay Certificates', 'office' => 'Barangay Office', 'level' => 'Barangay'],
        'Pet Registration' => ['category' => 'Other', 'office' => 'Municipal Agriculture Office', 'level' => 'Municipal'],
        'Locational Clearance' => ['category' => 'Business & Permits', 'office' => 'Municipal Planning & Development Office (MPDO)', 'level' => 'Municipal'],
    ];
    foreach ($payments as &$pmt) {
        $pmt = array_merge($pmt, $paymentsClassification[$pmt['item']] ?? ['category' => 'Other', 'office' => $pmt['type'], 'level' => 'Municipal']);
    }
    unset($pmt);

    $adminFiles = [
        ['name' => 'GCash Receipt Scan.jpg', 'category' => 'IMG'],
        ['name' => 'PayMaya Confirmation.png', 'category' => 'IMG'],
        ['name' => 'Official Receipt (OR) Scan.pdf', 'category' => 'PDF'],
    ];

    $tabs = ['all' => 'All', 'unpaid' => 'Awaiting Payment', 'review' => 'Pending Review', 'paid' => 'Paid'];

    $totalInvoiced = 2562.00;
    $totalCollected = 300.00;
    $paidCount = collect($payments)->where('status', 'Approved')->count();
    $reviewCount = collect($payments)->where('status', 'Pending Review')->count();
    $unpaidCount = collect($payments)->where('status', 'Waiting Requirements')->count();
@endphp

<x-layouts.admin title="Payments" subtitle="Track Dokyu and Tulong invoices, methods, and attached receipts." active="payments">
    <div
        x-data="{
            tab: 'all',
            search: '',
            barangayFilter: '',
            categoryFilter: '',
            levelFilter: '',
            showAdvancedFilters: false,
            open: false,
            selected: null,
            payments: @js($payments),
            adminFiles: @js($adminFiles),
            method: null,
            receipt: null,
            showReceiptPicker: false,
            previewDoc: null,
            processing: false,
            barangays: @js($barangays),
            get categories() { return [...new Set(this.payments.map(p => p.category))].sort(); },
            get isMunicipalScope() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal';
            },
            runAction(fn) {
                if (this.processing) return;
                this.processing = true;
                setTimeout(() => { fn(); this.processing = false; this.open = false; }, 700);
            },
            matches(p) {
                if (!this.$store.session.inScope(p.barangay)) return false;
                const tabOk = this.tab === 'all'
                    || (this.tab === 'unpaid' && p.status === 'Waiting Requirements')
                    || (this.tab === 'review' && p.status === 'Pending Review')
                    || (this.tab === 'paid' && p.status === 'Approved');
                const barangayOk = this.barangayFilter === '' || p.barangay === this.barangayFilter;
                const categoryOk = this.categoryFilter === '' || p.category === this.categoryFilter;
                const levelOk = this.levelFilter === '' || p.level === this.levelFilter;
                const q = this.search.trim().toLowerCase();
                const searchOk = q === '' || p.ref.toLowerCase().includes(q) || p.citizen.toLowerCase().includes(q);
                return tabOk && barangayOk && categoryOk && levelOk && searchOk;
            },
            clearAdvancedFilters() { this.levelFilter = ''; this.barangayFilter = ''; },
            // Arriving from a request's own process modal via "View in
            // Payments" — pre-filter to exactly that ref, mirroring the same
            // ?ref= pattern admin/document-requests.blade.php already uses.
            init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                if (ref) this.search = ref;
            },
            get visibleCount() { return this.payments.filter(p => this.matches(p)).length; },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('payments')" x-cloak>
            <x-admin.access-restricted module="Payments" icon="receipt" />
        </div>

        <div x-show="$store.session.can('payments')" x-cloak>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card label="Total Invoiced" value="₱{{ number_format($totalInvoiced, 2) }}" icon="receipt" color="brand" sublabel="This month" :delay="0" />
            <x-ui.stat-card label="Total Collected" value="₱{{ number_format($totalCollected, 2) }}" icon="banknote" color="green" :delay="40" />
            <x-ui.stat-card label="Paid" value="{{ $paidCount }}" valueBind="payments.filter(p => p.status === 'Approved').length" icon="circle-check" color="green" :delay="80" />
            <x-ui.stat-card label="Pending Review" value="{{ $reviewCount }}" valueBind="payments.filter(p => p.status === 'Pending Review').length" icon="clock" color="orange" sublabel="Receipt awaiting verification" :delay="120" />
            <x-ui.stat-card label="Awaiting Payment" value="{{ $unpaidCount }}" valueBind="payments.filter(p => p.status === 'Waiting Requirements').length" icon="circle-dollar-sign" color="red" :delay="160" />
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
                    :class="showAdvancedFilters || levelFilter || barangayFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
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
                <p class="text-[11px] text-slate-400 shrink-0" x-text="visibleCount + ' of ' + payments.length + ' payments'"></p>
            </div>

            <div
                x-show="showAdvancedFilters" x-cloak
                x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100"
            >
                <select x-model="levelFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Levels</option>
                    <option value="Barangay">Barangay</option>
                    <option value="Municipal">Municipal</option>
                    <option value="Multi-Office">Multi-Office</option>
                </select>
                <select x-show="isMunicipalScope" x-model="barangayFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <span x-show="!isMunicipalScope" x-cloak class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                    <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                </span>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors" x-show="levelFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Item</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Method</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden 2xl:table-cell">Receipt</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden xl:table-cell">Date</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($payments as $i => $p)
                    <tr
                        x-show="matches(payments[{{ $i }}])" x-cloak
                        class="hover:bg-slate-50/70 transition-colors group cursor-pointer"
                        @click="open = true; selected = {{ $i }}; method = payments[{{ $i }}].method; receipt = payments[{{ $i }}].receipt; showReceiptPicker = false; previewDoc = null; $nextTick(() => window.renderIcons?.())"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-slate-500 hidden xl:table-cell" @click.stop="open = true; selected = {{ $i }}; method = payments[{{ $i }}].method; receipt = payments[{{ $i }}].receipt; showReceiptPicker = false; previewDoc = null; $nextTick(() => window.renderIcons?.())">{{ $p['ref'] }}</td>
                        <td class="px-4 py-3" @click.stop="open = true; selected = {{ $i }}; method = payments[{{ $i }}].method; receipt = payments[{{ $i }}].receipt; showReceiptPicker = false; previewDoc = null; $nextTick(() => window.renderIcons?.())">
                            <div class="flex items-center gap-2.5 min-w-0">
                                @if(!empty($citizenAvatars[$p['citizen']]))
                                    <img src="{{ $citizenAvatars[$p['citizen']] }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" alt="{{ $p['citizen'] }}">
                                @else
                                    <div class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0">{{ strtoupper(collect(explode(' ', $p['citizen']))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('')) }}</div>
                                @endif
                                <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors">{{ $p['citizen'] }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden 2xl:table-cell">
                            <p class="text-slate-700">{{ $p['item'] }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                {{ $p['category'] }}
                                <span
                                    class="inline-block ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap {{ $p['level'] === 'Barangay' ? 'bg-teal-50 text-teal-700' : ($p['level'] === 'Multi-Office' ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700') }}"
                                >{{ $p['level'] }}</span>
                            </p>
                        </td>
                        <td class="px-4 py-3 text-slate-700 font-semibold whitespace-nowrap">{{ $p['amount'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden 2xl:table-cell" x-text="payments[{{ $i }}].method || '—'"></td>
                        <td class="px-4 py-3 hidden 2xl:table-cell">
                            <span class="inline-flex items-center gap-1 text-xs text-brand-600" x-show="payments[{{ $i }}].receipt">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i><span x-text="payments[{{ $i }}].receipt"></span>
                            </span>
                            <span class="text-xs text-slate-300" x-show="!payments[{{ $i }}].receipt">—</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden xl:table-cell whitespace-nowrap">{{ $p['date'] }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge status="Waiting Requirements" x-show="payments[{{ $i }}].status === 'Waiting Requirements'" />
                            <x-ui.badge status="Pending Review" x-show="payments[{{ $i }}].status === 'Pending Review'" x-cloak />
                            <x-ui.badge status="Approved" x-show="payments[{{ $i }}].status === 'Approved'" x-cloak />
                            <x-ui.badge status="Rejected" x-show="payments[{{ $i }}].status === 'Rejected'" x-cloak />
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop>
                            <button
                                @click="open = true; selected = {{ $i }}; method = payments[{{ $i }}].method; receipt = payments[{{ $i }}].receipt; showReceiptPicker = false; previewDoc = null; $nextTick(() => window.renderIcons?.())"
                                class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"
                            >Manage</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="visibleCount === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No payments match these filters.</p>
        </div>

        <x-ui.modal title="Payment Details" maxWidth="lg">
            <template x-if="selected !== null">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono text-slate-400" x-text="payments[selected].ref"></span>
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="payments[selected].type === 'Dokyu' ? 'bg-brand-50 text-brand-700' : 'bg-purple-50 text-purple-700'" x-text="payments[selected].type"></span>
                    </div>

                    {{-- Invoice — same letterhead + icon-field treatment as the
                         Submitted Application replica in Dokyu/Tulong, so a
                         payment record reads as the same kind of official
                         document instead of a differently-styled one-off. --}}
                    <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-sm mb-5">
                        <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 px-4 py-3.5 text-white overflow-hidden">
                            <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>
                            <div class="relative flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[8px] uppercase tracking-widest text-white/50">Republic of the Philippines · Municipality of Esperanza, Masbate</p>
                                    <p class="text-xs font-bold uppercase tracking-wide mt-0.5">Invoice Summary</p>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium text-white/70 bg-white/10 rounded-full px-2 py-1 shrink-0">
                                    <i data-lucide="receipt" class="w-3 h-3"></i> <span x-text="payments[selected].ref"></span>
                                </span>
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-white">
                            <div class="flex items-start gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Citizen</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="payments[selected].citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="calendar-days" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Date Invoiced</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="payments[selected].date"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 sm:col-span-2">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Item</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="payments[selected].item"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 sm:col-span-2">
                                <span class="w-8 h-8 rounded-lg bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1 flex items-center justify-between gap-3 rounded-lg border border-brand-100 bg-brand-50/50 px-3 py-2">
                                    <span class="text-sm font-semibold text-navy-900">Total Due</span>
                                    <span class="text-base font-bold text-brand-700" x-text="payments[selected].amount"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jumps to the same request in Dokyu/Tulong to see the full
                         submitted application and documents behind this invoice —
                         a payment record on its own is only half the story. -->
                    <button
                        type="button"
                        @click="window.location.href = (payments[selected].type === 'Dokyu' ? '/admin/document-requests' : '/admin/assistance-requests') + '?ref=' + encodeURIComponent(payments[selected].ref)"
                        class="w-full flex items-center justify-between gap-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/30 transition-colors p-3 mb-5 group"
                    >
                        <span class="flex items-center gap-2.5 text-left">
                            <span class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-brand-100 text-slate-500 group-hover:text-brand-600 flex items-center justify-center shrink-0 transition-colors"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-navy-900">View Full Application</span>
                                <span class="block text-[11px] text-slate-400">Submitted form and documents in <span x-text="payments[selected].type"></span></span>
                            </span>
                        </span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-slate-300 group-hover:text-brand-500 group-hover:translate-x-0.5 transition-all shrink-0"></i>
                    </button>

                    {{-- Official Payment Receipt — identical design to the receipt
                         a citizen sees and downloads on the Esperanza Mobile app
                         (same seal header, PAID pill, field order, and payment-mode
                         badge colors), so the same transaction reads the same way
                         on both sides all the way through to this final artifact.
                         Shared with the Dokyu/Tulong process modals — see
                         components/admin/payment-receipt.blade.php. --}}
                    <x-admin.payment-receipt recordExpr="payments[selected]" />

                    <!-- Admin: attach receipt on behalf of citizen (Waiting Requirements rows) -->
                    <div x-show="payments[selected].status === 'Waiting Requirements'" x-cloak>
                        <p class="text-sm font-semibold text-navy-900 mb-3 mt-4">Record Payment</p>
                        <p class="text-xs text-slate-400 mb-3">If the citizen paid over-the-counter or you've verified proof of payment, record it here.</p>
                        <div class="space-y-2 mb-4">
                            <template x-for="m in [{ name: 'GCash', icon: 'smartphone' }, { name: 'PayMaya', icon: 'wallet' }, { name: 'Payment in Center', icon: 'building-2' }]" :key="m.name">
                                <label
                                    class="flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all duration-[160ms] ease-standard"
                                    :class="method === m.name ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <input type="radio" name="admin_payment_method" class="text-brand-600 focus:ring-brand-200" :checked="method === m.name" @change="method = m.name">
                                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors" :class="method === m.name ? 'bg-brand-100 text-brand-600' : 'bg-slate-100 text-slate-400'"><i :data-lucide="m.icon" class="w-3.5 h-3.5"></i></span>
                                    <span class="text-sm font-medium text-slate-700" x-text="m.name"></span>
                                </label>
                            </template>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs text-slate-600 flex-1">Attach receipt / proof of payment</p>
                                <button type="button" x-show="!receipt" @click="showReceiptPicker = !showReceiptPicker" class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap">Attach File</button>
                                <div class="flex items-center gap-1.5 shrink-0" x-show="receipt">
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap"><i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span x-text="receipt"></span></span>
                                    <button type="button" @click="receipt = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                            <div x-show="showReceiptPicker" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-slate-100">
                                <x-ui.file-picker
                                    files="adminFiles"
                                    onSelect="receipt = file.name; showReceiptPicker = false"
                                    uploadAction="receipt = 'Scanned OR.jpg'; showReceiptPicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                    :columns="2"
                                    :search="false"
                                    :bilingual="false"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button next to
                 the uploaded proof of payment above. Fixed to the viewport so
                 it sits above the whole Payment Details modal. --}}
            <div
                x-show="previewDoc"
                x-cloak
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-[150ms] ease-exit"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="position: fixed; inset: 0; z-index: 95;"
                class="bg-navy-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="previewDoc = null"
                @keydown.escape.window="previewDoc = null"
            >
                <div
                    class="w-full max-w-sm bg-white rounded-2xl shadow-float overflow-hidden"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-slate-100">
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                            :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500'"
                        ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-3.5 h-3.5"></i></span>
                        <h3 class="text-sm font-semibold text-navy-900 truncate flex-1" x-text="previewDoc?.name"></h3>
                        <button type="button" @click="previewDoc = null" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors shrink-0" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/4] flex flex-col items-center justify-center gap-2.5 mb-3">
                            <span
                                class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-100 text-rose-500' : 'bg-blue-100 text-blue-500'"
                            ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-7 h-7"></i></span>
                            <p class="text-xs font-medium text-slate-500 px-4 text-center truncate max-w-full" x-text="previewDoc?.file"></p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-400 shrink-0">Status</dt>
                                <dd class="font-medium text-navy-900" x-text="previewDoc?.status"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <template x-if="selected !== null && payments[selected].status === 'Waiting Requirements'">
                    <x-ui.button
                        size="sm"
                        ::disabled="!method || !receipt || processing"
                        @click="runAction(() => { payments[selected].method = method; payments[selected].receipt = receipt; payments[selected].status = 'Pending Review'; $dispatch('toast', { message: 'Payment recorded — sent for verification.', variant: 'success' }); })"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="!processing"></i>
                            <span x-text="processing ? 'Saving…' : 'Save & Send for Verification'"></span>
                        </span>
                    </x-ui.button>
                </template>
                <template x-if="selected !== null && payments[selected].status === 'Pending Review'">
                    <div class="flex items-center gap-2.5">
                        <x-ui.button variant="danger" size="sm" x-bind:disabled="processing" @click="runAction(() => { payments[selected].status = 'Rejected'; $dispatch('toast', { message: 'Receipt rejected — citizen will be notified.', variant: 'error' }); })">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <span x-text="processing ? 'Rejecting…' : 'Reject Receipt'"></span>
                            </span>
                        </x-ui.button>
                        <x-ui.button size="sm" x-bind:disabled="processing" @click="runAction(() => { payments[selected].status = 'Approved'; $dispatch('toast', { message: 'Payment verified and marked as paid.', variant: 'success' }); })">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <i data-lucide="check" class="w-3.5 h-3.5" x-show="!processing"></i>
                                <span x-text="processing ? 'Verifying…' : 'Verify Payment'"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </template>
                <template x-if="selected !== null && !['Waiting Requirements', 'Pending Review'].includes(payments[selected].status)">
                    <x-ui.button size="sm" @click="open = false">Close</x-ui.button>
                </template>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
