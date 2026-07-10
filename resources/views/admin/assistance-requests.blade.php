@php
    $requests = [
        ['ref' => 'AR-2026-0151', 'citizen' => 'Elmer Bantillo', 'type' => 'Medical Assistance', 'barangay' => 'Santiago', 'submitted' => 'Jul 9, 2026', 'status' => 'Pending Review'],
        ['ref' => 'AR-2026-0148', 'citizen' => 'Angelica Fajardo', 'type' => 'Burial Assistance', 'barangay' => 'Rizal', 'submitted' => 'Jul 6, 2026', 'status' => 'Assigned'],
        ['ref' => 'AR-2026-0142', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Medical Assistance', 'barangay' => 'Poblacion', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'citizen' => 'Rodrigo Palma', 'type' => 'Educational Assistance', 'barangay' => 'Domorog', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'citizen' => 'Teresita Salazar', 'type' => 'Financial Assistance (AICS)', 'barangay' => 'Agoho', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0044', 'citizen' => 'Michael Bacus', 'type' => 'Food Assistance', 'barangay' => 'Iligan', 'submitted' => 'Mar 18, 2026', 'status' => 'Completed'],
    ];

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Approved / Released', 'done' => 'Completed'];
@endphp

<x-layouts.admin title="Assistance Requests" subtitle="Review and manage citizen assistance requests." active="assistance-requests">
    <div x-data="{ tab: 'all' }" class="animate-fade-up">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Active Cases" value="417" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card label="Needs Action" value="34" icon="inbox" color="orange" sublabel="Review or assignment" :delay="40" />
            <x-ui.stat-card label="Approved / Released" value="112" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Budget Utilized (Q3)" value="₱1.8M" icon="wallet" color="brand" sublabel="of ₱2.5M allocated" :delay="120" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit overflow-x-auto">
                @foreach($tabs as $key => $label)
                    <button
                        @click="tab = '{{ $key }}'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                        :class="tab === '{{ $key }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"
                    >{{ $label }}</button>
                @endforeach
            </div>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" placeholder="Search reference or citizen..." class="w-64 pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($requests as $r)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $r['ref'] }}</td>
                        <td class="px-4 py-3 text-slate-700 font-medium whitespace-nowrap">{{ $r['citizen'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $r['type'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">Brgy. {{ $r['barangay'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $r['submitted'] }}</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$r['status']" /></td>
                        <td class="px-4 py-3 text-right">
                            <button
                                @click="$dispatch('toast', { message: 'Opening case {{ $r['ref'] }}…', variant: 'info' })"
                                class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"
                            >Review</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </div>
</x-layouts.admin>
