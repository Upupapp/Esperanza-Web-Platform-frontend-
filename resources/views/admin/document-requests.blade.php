@php
    $requests = [
        ['ref' => 'DR-2026-2214', 'citizen' => 'Rosemarie Tan', 'type' => 'Business Permit', 'barangay' => 'Baras', 'submitted' => 'Jul 10, 2026', 'status' => 'Pending Review'],
        ['ref' => 'DR-2026-2210', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Cedula', 'barangay' => 'Poblacion', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2201', 'citizen' => 'Elmer Bantillo', 'type' => 'Certificate of Indigency', 'barangay' => 'Santiago', 'submitted' => 'Jul 7, 2026', 'status' => 'Waiting Requirements'],
        ['ref' => 'DR-2026-2187', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Barangay Clearance', 'barangay' => 'Poblacion', 'submitted' => 'Jul 3, 2026', 'status' => 'Ready for Release'],
        ['ref' => 'DR-2026-2154', 'citizen' => 'Corazon Villareal', 'type' => 'Certificate of Residency', 'barangay' => 'Iligan', 'submitted' => 'Jun 29, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-1998', 'citizen' => 'Josefina Marbella', 'type' => 'Certificate of Residency', 'barangay' => 'Baras', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1872', 'citizen' => 'Alvin Dagohoy', 'type' => 'Certificate of Indigency', 'barangay' => 'Domorog', 'submitted' => 'May 30, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1655', 'citizen' => 'Rosemarie Tan', 'type' => 'Business Permit', 'barangay' => 'Baras', 'submitted' => 'May 12, 2026', 'status' => 'Rejected'],
    ];

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Ready for Release', 'done' => 'Completed'];
@endphp

<x-layouts.admin title="Document Requests" subtitle="Track and process citizen document requests." active="document-requests">
    <div x-data="{ tab: 'all', open: false, selected: null }" class="animate-fade-up">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Total Requests" value="1,284" icon="file-text" color="brand" trend="+8.4%" sublabel="This month" :delay="0" />
            <x-ui.stat-card label="Needs Action" value="63" icon="inbox" color="orange" sublabel="Review or verification" :delay="40" />
            <x-ui.stat-card label="Ready for Release" value="41" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Avg. Processing Time" value="2.4 days" icon="timer" color="purple" trendDirection="down" trend="-0.6 days" :delay="120" />
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
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Document Type</th>
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
                            <button @click="open = true; selected = '{{ $r['ref'] }}'" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Process</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.modal title="Process Document Request" maxWidth="lg">
            <p class="text-xs text-slate-400 mb-4 font-mono" x-text="selected"></p>

            <div class="flex items-center justify-between mb-6">
                @foreach(['Submitted', 'Under Verification', 'Approved', 'Ready for Release', 'Released'] as $i => $step)
                    <div class="flex-1 flex items-center">
                        <div class="flex flex-col items-center gap-1.5 text-center">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold {{ $i <= 1 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                {{ $i + 1 }}
                            </span>
                            <span class="text-[10px] text-slate-400 max-w-[64px] leading-tight">{{ $step }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 {{ $i < 1 ? 'bg-brand-600' : 'bg-slate-100' }} -mt-4"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600 space-y-1.5">
                <p><span class="text-slate-400">Citizen:</span> Maria Fe Bacaltos</p>
                <p><span class="text-slate-400">Document:</span> Cedula (Community Tax Certificate)</p>
                <p><span class="text-slate-400">Barangay:</span> Poblacion</p>
                <p><span class="text-slate-400">Purpose:</span> Employment requirement</p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="danger" size="sm" @click="open = false; $dispatch('toast', { message: 'Request rejected.', variant: 'error' })">Reject</x-ui.button>
                <x-ui.button size="sm" icon="check" @click="open = false; $dispatch('toast', { message: 'Moved to next stage: Approved.', variant: 'success' })">Approve & Continue</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.admin>
