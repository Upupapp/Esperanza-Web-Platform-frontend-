@php
    $requests = [
        ['ref' => 'AR-2026-0151', 'citizen' => 'Elmer Bantillo', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Santiago', 'submitted' => 'Jul 9, 2026', 'status' => 'Pending Review'],
        ['ref' => 'AR-2026-0148', 'citizen' => 'Angelica Fajardo', 'type' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Rizal', 'submitted' => 'Jul 6, 2026', 'status' => 'Assigned'],
        ['ref' => 'AR-2026-0142', 'citizen' => 'Maria Fe Bacaltos', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Poblacion', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'citizen' => 'Rodrigo Palma', 'type' => 'Educational Assistance', 'office' => "Mayor's Office", 'barangay' => 'Domorog', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'citizen' => 'Teresita Salazar', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'barangay' => 'Agoho', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0044', 'citizen' => 'Michael Bacus', 'type' => 'Food / Relief Assistance', 'office' => 'MSWDO', 'barangay' => 'Iligan', 'submitted' => 'Mar 18, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0037', 'citizen' => 'Josefina Marbella', 'type' => 'Social Pension (Indigent Senior)', 'office' => 'OSCA', 'barangay' => 'Baras', 'submitted' => 'Feb 10, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0022', 'citizen' => 'Rosemarie Tan', 'type' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'barangay' => 'Baras', 'submitted' => 'Jan 28, 2026', 'status' => 'Waiting Requirements'],
    ];

    $tabs = ['all' => 'All', 'action' => 'Needs Action', 'release' => 'Approved / Released', 'done' => 'Completed'];
@endphp

<x-layouts.admin title="Tulong" subtitle="Review and manage citizen assistance requests." active="assistance-requests">
    <div x-data="{ tab: 'all', open: false, selected: null }" class="animate-fade-up">

        <div x-show="!$store.session.can('tulong')" x-cloak>
            <x-admin.access-restricted module="Tulong (Citizen Assistance)" icon="hand-heart" />
        </div>

        <div x-show="$store.session.can('tulong')" x-cloak>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card label="Active Cases" value="417" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card label="Needs Action" value="34" icon="inbox" color="orange" sublabel="Review or assignment" :delay="40" />
            <x-ui.stat-card label="Approved / Released" value="112" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Budget Utilized (Q3)" value="₱1.8M" icon="wallet" color="brand" sublabel="of ₱2.5M allocated" :delay="120" />
            <x-ui.stat-card label="Ayuda Received" value="₱1.8M" icon="hand-coins" color="gold" sublabel="Disbursed to citizens" :delay="160" />
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
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($requests as $r)
                    <tr x-show="$store.session.inScope('{{ $r['barangay'] }}')" x-cloak class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $r['ref'] }}</td>
                        <td class="px-4 py-3 text-slate-700 font-medium whitespace-nowrap">{{ $r['citizen'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $r['type'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell"><span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap">{{ $r['office'] }}</span></td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">Brgy. {{ $r['barangay'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $r['submitted'] }}</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$r['status']" /></td>
                        <td class="px-4 py-3 text-right">
                            <button
                                @click="open = true; selected = '{{ $r['ref'] }}'"
                                class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"
                            >Review</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.modal title="Review Tulong Case" maxWidth="lg">
            <p class="text-xs text-slate-400 mb-4 font-mono" x-text="selected"></p>

            <div class="flex items-center justify-between mb-6">
                @foreach(['Submitted', 'Assigned', 'Approved', 'Released', 'Completed'] as $i => $step)
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
                <p><span class="text-slate-400">Citizen:</span> Elmer Bantillo</p>
                <p><span class="text-slate-400">Assistance Type:</span> Medical Assistance (AICS)</p>
                <p><span class="text-slate-400">Handling Office:</span> MSWDO</p>
                <p><span class="text-slate-400">Barangay:</span> Santiago</p>
                <p><span class="text-slate-400">Requested Amount:</span> ₱8,500.00</p>
                <p><span class="text-slate-400">Requirements Verified:</span> 3 of 4 — awaiting Barangay Certificate of Indigency</p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="danger" size="sm" @click="open = false; $dispatch('toast', { message: 'Case rejected.', variant: 'error' })">Reject</x-ui.button>
                <x-ui.button size="sm" icon="check" @click="open = false; $dispatch('toast', { message: 'Moved to next stage: Assigned.', variant: 'success' })">Approve & Continue</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
