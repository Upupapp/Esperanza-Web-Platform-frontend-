@php
    $households = [
        ['id' => 'HH-0001', 'name' => 'Bacaltos-Cruz Household', 'barangay' => 'Poblacion', 'families' => 2, 'members' => 7, 'head' => 'Maria Fe Bacaltos'],
        ['id' => 'HH-0002', 'name' => 'Espenida Household', 'barangay' => 'Masbaranon', 'families' => 1, 'members' => 6, 'head' => 'Ronaldo Espenida'],
        ['id' => 'HH-0003', 'name' => 'Marbella Household', 'barangay' => 'Baras', 'families' => 1, 'members' => 3, 'head' => 'Josefina Marbella'],
        ['id' => 'HH-0004', 'name' => 'Dagohoy-Palma Household', 'barangay' => 'Domorog', 'families' => 2, 'members' => 8, 'head' => 'Alvin Dagohoy'],
        ['id' => 'HH-0005', 'name' => 'Fajardo Household', 'barangay' => 'Rizal', 'families' => 1, 'members' => 7, 'head' => 'Angelica Fajardo'],
    ];

    $families = [
        ['id' => 'FAM-0001', 'household' => 'HH-0001', 'surname' => 'Bacaltos', 'barangay' => 'Poblacion', 'head' => 'Maria Fe Bacaltos', 'members' => 4],
        ['id' => 'FAM-0002', 'household' => 'HH-0001', 'surname' => 'Cruz', 'barangay' => 'Poblacion', 'head' => 'Ramon Cruz', 'members' => 3],
        ['id' => 'FAM-0003', 'household' => 'HH-0002', 'surname' => 'Espenida', 'barangay' => 'Masbaranon', 'head' => 'Ronaldo Espenida', 'members' => 6],
        ['id' => 'FAM-0004', 'household' => 'HH-0003', 'surname' => 'Marbella', 'barangay' => 'Baras', 'head' => 'Josefina Marbella', 'members' => 3],
        ['id' => 'FAM-0005', 'household' => 'HH-0004', 'surname' => 'Dagohoy', 'barangay' => 'Domorog', 'head' => 'Alvin Dagohoy', 'members' => 5],
        ['id' => 'FAM-0006', 'household' => 'HH-0004', 'surname' => 'Palma', 'barangay' => 'Domorog', 'head' => 'Rodrigo Palma', 'members' => 3],
        ['id' => 'FAM-0007', 'household' => 'HH-0005', 'surname' => 'Fajardo', 'barangay' => 'Rizal', 'head' => 'Angelica Fajardo', 'members' => 7],
    ];

    $individuals = [
        ['name' => 'Maria Fe Bacaltos', 'family' => 'FAM-0001', 'barangay' => 'Poblacion', 'contact' => '0917 342 8810', 'household' => 4, 'status' => 'Approved', 'verified' => true],
        ['name' => 'Ronaldo Espenida', 'family' => 'FAM-0003', 'barangay' => 'Masbaranon', 'contact' => '0918 220 4471', 'household' => 6, 'status' => 'Approved', 'verified' => true],
        ['name' => 'Josefina Marbella', 'family' => 'FAM-0004', 'barangay' => 'Baras', 'contact' => '0928 671 9902', 'household' => 3, 'status' => 'Pending Review', 'verified' => false],
        ['name' => 'Alvin Dagohoy', 'family' => 'FAM-0005', 'barangay' => 'Domorog', 'contact' => '0917 004 5523', 'household' => 5, 'status' => 'Approved', 'verified' => true],
        ['name' => 'Teresita Salazar', 'family' => 'FAM-0004', 'barangay' => 'Agoho', 'contact' => '0906 512 3384', 'household' => 2, 'status' => 'Under Verification', 'verified' => false],
        ['name' => 'Michael Bacus', 'family' => 'FAM-0003', 'barangay' => 'Iligan', 'contact' => '0915 887 2201', 'household' => 4, 'status' => 'Approved', 'verified' => true],
        ['name' => 'Angelica Fajardo', 'family' => 'FAM-0007', 'barangay' => 'Rizal', 'contact' => '0927 341 0098', 'household' => 7, 'status' => 'Pending Review', 'verified' => false],
        ['name' => 'Rodrigo Palma', 'family' => 'FAM-0006', 'barangay' => 'Domorog', 'contact' => '0918 776 5540', 'household' => 3, 'status' => 'Approved', 'verified' => true],
    ];

    $barangayOptions = config('esperanza.barangays');

    $profilingByBarangay = collect(config('esperanza.barangays'))->map(function ($name) {
        $pct = rand(38, 98);
        return [
            'name' => $name,
            'profiled' => rand(180, 1400),
            'total' => rand(1500, 2200),
            'pct' => $pct,
        ];
    })->sortByDesc('pct')->values();

    $tab = $tab ?? 'residents';
@endphp

<x-layouts.admin title="Constituents" subtitle="Browse registered residents and profiling coverage." active="constituents">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
            <button @click="tab = 'residents'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'residents' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="users" class="w-3.5 h-3.5"></i>Residents</button>
            <button @click="tab = 'profiling'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'profiling' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="user-round-search" class="w-3.5 h-3.5"></i>Profiling Coverage</button>
        </div>

        <!-- Residents -->
        <div x-show="tab === 'residents'" x-data="{ view: 'individuals', idFilter: 'all' }">
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                <x-ui.stat-card label="Registered Residents" value="17,842" icon="users" color="brand" trend="+312" sublabel="Across 20 barangays" :delay="0" />
                <x-ui.stat-card label="Households" value="4,918" icon="home" color="green" sublabel="Physical dwellings" :delay="40" />
                <x-ui.stat-card label="Families" value="6,204" icon="users-round" color="purple" sublabel="May share a household" :delay="80" />
                <x-ui.stat-card label="ID Verified" value="79%" icon="badge-check" color="gold" sublabel="14,095 of 17,842" :delay="120" />
                <x-ui.stat-card label="New This Month" value="284" icon="user-plus" color="red" trend="+6.1%" :delay="160" />
            </div>

            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
                <button @click="view = 'individuals'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="view === 'individuals' ? 'bg-navy-900 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="user" class="w-3.5 h-3.5"></i>Individuals</button>
                <button @click="view = 'families'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="view === 'families' ? 'bg-navy-900 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="users-round" class="w-3.5 h-3.5"></i>Families</button>
                <button @click="view = 'household'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="view === 'household' ? 'bg-navy-900 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="home" class="w-3.5 h-3.5"></i>Household</button>
            </div>

            <!-- Individuals -->
            <div x-show="view === 'individuals'">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5 flex-nowrap">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" placeholder="Search residents..." class="w-56 pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
                        </div>
                        <select class="text-sm rounded-xl border border-slate-200 bg-white py-2.5 px-3.5 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                            <option value="">All Barangays</option>
                            @foreach($barangayOptions as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit">
                            @foreach(['all' => 'All', 'verified' => 'Verified', 'unverified' => 'Unverified'] as $key => $label)
                                <button
                                    @click="idFilter = '{{ $key }}'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                                    :class="idFilter === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <x-ui.button icon="user-plus" size="sm">Add Individual</x-ui.button>
                </div>

                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Resident</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Family</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Contact</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">ID Verification</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($individuals as $c)
                            <tr
                                x-show="idFilter === 'all' || idFilter === '{{ $c['verified'] ? 'verified' : 'unverified' }}'"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <td class="px-4 py-2.5 flex items-center gap-3">
                                    <x-ui.avatar :name="$c['name']" size="sm" />
                                    <span class="text-slate-700 font-medium whitespace-nowrap">{{ $c['name'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 hidden md:table-cell font-mono text-xs">{{ $c['family'] }}</td>
                                <td class="px-4 py-3 text-slate-500">Brgy. {{ $c['barangay'] }}</td>
                                <td class="px-4 py-3 text-slate-500 hidden lg:table-cell font-mono text-xs">{{ $c['contact'] }}</td>
                                <td class="px-4 py-3"><x-ui.badge :status="$c['status']" /></td>
                                <td class="px-4 py-3">
                                    @if($c['verified'])
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2 py-0.5 whitespace-nowrap">
                                            <i data-lucide="badge-check" class="w-3 h-3"></i> Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5 whitespace-nowrap">
                                            <i data-lucide="shield-alert" class="w-3 h-3"></i> Unverified
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>

            <!-- Families -->
            <div x-show="view === 'families'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5 flex-nowrap">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" placeholder="Search families or family head..." class="w-64 pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
                        </div>
                        <select class="text-sm rounded-xl border border-slate-200 bg-white py-2.5 px-3.5 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                            <option value="">All Barangays</option>
                            @foreach($barangayOptions as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.button icon="users-round" size="sm">Add Family</x-ui.button>
                </div>
                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Family</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Household</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Family Head</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Members</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($families as $f)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-2.5 text-slate-700 font-medium whitespace-nowrap">{{ $f['surname'] }} Family</td>
                                <td class="px-4 py-2.5 text-slate-500 hidden md:table-cell font-mono text-xs">{{ $f['household'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500">Brgy. {{ $f['barangay'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $f['head'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $f['members'] }} members</td>
                                <td class="px-4 py-2.5 text-right">
                                    <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>

            <!-- Household -->
            <div x-show="view === 'household'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5 flex-nowrap">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" placeholder="Search households or household head..." class="w-64 pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
                        </div>
                        <select class="text-sm rounded-xl border border-slate-200 bg-white py-2.5 px-3.5 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                            <option value="">All Barangays</option>
                            @foreach($barangayOptions as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.button icon="home" size="sm">Add Household</x-ui.button>
                </div>
                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Household</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Household Head</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Families</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Members</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($households as $h)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-2.5 text-slate-700 font-medium whitespace-nowrap">{{ $h['name'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500">Brgy. {{ $h['barangay'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $h['head'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $h['families'] }} {{ $h['families'] > 1 ? 'families' : 'family' }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $h['members'] }} members</td>
                                <td class="px-4 py-2.5 text-right">
                                    <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>
        </div>

        <!-- Profiling coverage -->
        <div x-show="tab === 'profiling'" x-cloak>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Barangays Profiled" value="20/20" icon="map-pin" color="brand" :delay="0" />
                <x-ui.stat-card label="Households Recorded" value="4,918" icon="home" color="green" trend="+126" :delay="40" />
                <x-ui.stat-card label="Avg. Completion Rate" value="71%" icon="clipboard-check" color="purple" :delay="80" />
                <x-ui.stat-card label="Barangays Below 60%" value="4" icon="alert-triangle" color="red" sublabel="Needs field verification" :delay="120" />
            </div>

            <x-ui.card padded="false">
                <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                    <h3 class="text-sm font-semibold text-navy-900">Profiling Completion by Barangay</h3>
                    <x-ui.button variant="secondary" size="sm" icon="download">Export Report</x-ui.button>
                </div>
                <div class="px-4 pb-4 space-y-4">
                    @foreach($profilingByBarangay as $b)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <span class="text-slate-600 font-medium">Brgy. {{ $b['name'] }}</span>
                                <span class="text-slate-400">{{ number_format($b['profiled']) }} / {{ number_format($b['total']) }} residents ({{ $b['pct'] }}%)</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-700 ease-out {{ $b['pct'] < 60 ? 'bg-gradient-to-r from-rose-500 to-rose-400' : ($b['pct'] < 80 ? 'bg-gradient-to-r from-amber-500 to-amber-400' : 'bg-gradient-to-r from-emerald-500 to-emerald-400') }}"
                                    style="width: 0%"
                                    x-data
                                    x-init="setTimeout(() => $el.style.width = '{{ $b['pct'] }}%', 150)"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.admin>
