@php
    $constituents = [
        ['name' => 'Maria Fe Bacaltos', 'barangay' => 'Poblacion', 'contact' => '0917 342 8810', 'household' => 4, 'status' => 'Approved'],
        ['name' => 'Ronaldo Espenida', 'barangay' => 'Masbaranon', 'contact' => '0918 220 4471', 'household' => 6, 'status' => 'Approved'],
        ['name' => 'Josefina Marbella', 'barangay' => 'Baras', 'contact' => '0928 671 9902', 'household' => 3, 'status' => 'Pending Review'],
        ['name' => 'Alvin Dagohoy', 'barangay' => 'Domorog', 'contact' => '0917 004 5523', 'household' => 5, 'status' => 'Approved'],
        ['name' => 'Teresita Salazar', 'barangay' => 'Agoho', 'contact' => '0906 512 3384', 'household' => 2, 'status' => 'Under Verification'],
        ['name' => 'Michael Bacus', 'barangay' => 'Iligan', 'contact' => '0915 887 2201', 'household' => 4, 'status' => 'Approved'],
        ['name' => 'Angelica Fajardo', 'barangay' => 'Rizal', 'contact' => '0927 341 0098', 'household' => 7, 'status' => 'Pending Review'],
        ['name' => 'Rodrigo Palma', 'barangay' => 'Domorog', 'contact' => '0918 776 5540', 'household' => 3, 'status' => 'Approved'],
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
        <div x-show="tab === 'residents'">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Registered Residents" value="17,842" icon="users" color="brand" trend="+312" sublabel="Across 20 barangays" :delay="0" />
                <x-ui.stat-card label="Verified Profiles" value="16,210" icon="user-round-check" color="green" sublabel="90.9% of residents" :delay="40" />
                <x-ui.stat-card label="Pending Verification" value="1,632" icon="user-round-search" color="orange" sublabel="Needs field visit" :delay="80" />
                <x-ui.stat-card label="New This Month" value="284" icon="user-plus" color="purple" trend="+6.1%" :delay="120" />
            </div>

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
                </div>
                <x-ui.button icon="user-plus" size="sm">Add Resident</x-ui.button>
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Resident</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Contact</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Household</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($constituents as $c)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 flex items-center gap-3">
                                <x-ui.avatar :name="$c['name']" size="sm" />
                                <span class="text-slate-700 font-medium whitespace-nowrap">{{ $c['name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">Brgy. {{ $c['barangay'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell font-mono text-xs">{{ $c['contact'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $c['household'] }} members</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$c['status']" /></td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
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
