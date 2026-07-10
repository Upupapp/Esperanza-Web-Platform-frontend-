@php
    $requests = [
        ['ref' => 'AR-2026-0142', 'type' => 'Medical Assistance', 'purpose' => 'Hospital bill support — Masbate Provincial Hospital', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'type' => 'Educational Assistance', 'purpose' => 'School supplies for 2 dependents', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'type' => 'Financial Assistance (AICS)', 'purpose' => 'Livelihood capital support', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
    ];

    $assistanceTypes = [
        ['name' => 'Medical Assistance', 'icon' => 'stethoscope', 'desc' => 'Hospital bills, medicines, and treatment support.'],
        ['name' => 'Burial Assistance', 'icon' => 'flower-2', 'desc' => 'Financial aid for funeral and burial expenses.'],
        ['name' => 'Educational Assistance', 'icon' => 'graduation-cap', 'desc' => 'School supplies, tuition, and allowance support.'],
        ['name' => 'Financial Assistance (AICS)', 'icon' => 'hand-coins', 'desc' => 'Cash assistance for individuals in crisis situations.'],
        ['name' => 'Food Assistance', 'icon' => 'shopping-basket', 'desc' => 'Relief goods and food packs for affected families.'],
    ];
@endphp

<x-layouts.citizen title="Assistance Requests" subtitle="Submit and follow up assistance program requests." active="assistance-requests">
    <div x-data="{ open: false }" class="animate-fade-up">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Total Requests" value="3" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card label="Approved" value="1" icon="check-circle-2" color="green" :delay="40" />
            <x-ui.stat-card label="Released" value="1" icon="package-check" color="brand" :delay="80" />
            <x-ui.stat-card label="Completed" value="1" icon="badge-check" color="gold" :delay="120" />
        </div>

        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-sm font-semibold text-navy-900">My Requests</h3>
            <x-ui.button @click="open = true" icon="hand-heart" size="sm">New Assistance Request</x-ui.button>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assistance Type</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Purpose</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($requests as $req)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $req['ref'] }}</td>
                        <td class="px-4 py-3 text-slate-700 font-medium">{{ $req['type'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $req['purpose'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $req['submitted'] }}</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$req['status']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.modal title="New Assistance Request" maxWidth="lg">
            <p class="text-sm text-slate-500 mb-4">Choose the type of assistance you need. A social worker will review and contact you regarding requirements.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" x-data="{ selected: null }">
                @foreach($assistanceTypes as $type)
                    <label
                        class="flex items-start gap-3 rounded-xl border p-3.5 cursor-pointer transition-all duration-200"
                        :class="selected === {{ $loop->index }} ? 'border-purple-400 bg-purple-50/60 ring-4 ring-purple-100' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <input type="radio" name="assist_type" class="mt-1 text-purple-600 focus:ring-purple-200" @change="selected = {{ $loop->index }}">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ $type['name'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $type['desc'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Details of Request</label>
                <textarea rows="3" placeholder="Briefly describe your situation and needs" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                <x-ui.button
                    size="sm"
                    icon="send"
                    @click="open = false; $dispatch('toast', { message: 'Assistance request submitted — this is a frontend preview, no data is saved.', variant: 'success' })"
                >Submit Request</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.citizen>
