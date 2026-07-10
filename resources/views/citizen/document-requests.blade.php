@php
    $requests = [
        ['ref' => 'DR-2026-2210', 'type' => 'Cedula (Community Tax Certificate)', 'purpose' => 'Employment requirement', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2187', 'type' => 'Barangay Clearance', 'purpose' => 'Business permit renewal', 'submitted' => 'Jul 3, 2026', 'status' => 'Ready for Release'],
        ['ref' => 'DR-2026-1998', 'type' => 'Certificate of Residency', 'purpose' => 'School enrollment', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1872', 'type' => 'Certificate of Indigency', 'purpose' => 'Medical assistance application', 'submitted' => 'May 30, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1655', 'type' => 'Business Permit', 'purpose' => 'Sari-sari store registration', 'submitted' => 'May 12, 2026', 'status' => 'Rejected'],
    ];

    $documentTypes = [
        ['name' => 'Barangay Clearance', 'fee' => '₱50.00', 'days' => '1-2 working days'],
        ['name' => 'Cedula (Community Tax Certificate)', 'fee' => '₱30.00', 'days' => 'Same day'],
        ['name' => 'Certificate of Residency', 'fee' => '₱50.00', 'days' => '1-2 working days'],
        ['name' => 'Certificate of Indigency', 'fee' => 'Free', 'days' => '2-3 working days'],
        ['name' => 'Business Permit', 'fee' => '₱250.00', 'days' => '3-5 working days'],
    ];
@endphp

<x-layouts.citizen title="Document Requests" subtitle="Request and track municipal documents online." active="document-requests">
    <div x-data="{ open: false, tab: 'all' }" class="animate-fade-up">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Total Requests" value="5" icon="file-text" color="brand" :delay="0" />
            <x-ui.stat-card label="In Progress" value="1" icon="loader" color="orange" :delay="40" />
            <x-ui.stat-card label="Ready for Release" value="1" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Completed" value="2" icon="check-circle-2" color="purple" :delay="120" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit overflow-x-auto">
                @foreach(['all' => 'All', 'active' => 'In Progress', 'done' => 'Completed'] as $key => $label)
                    <button
                        @click="tab = '{{ $key }}'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                        :class="tab === '{{ $key }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"
                    >{{ $label }}</button>
                @endforeach
            </div>
            <x-ui.button @click="open = true" icon="file-plus-2" size="sm">New Document Request</x-ui.button>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Document Type</th>
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

        <x-ui.modal title="New Document Request" maxWidth="lg">
            <p class="text-sm text-slate-500 mb-4">Select the document you'd like to request. Processing fees are payable on release at the Municipal Hall or via GCash.</p>
            <div class="space-y-2" x-data="{ selected: null }">
                @foreach($documentTypes as $doc)
                    <label
                        class="flex items-center justify-between gap-3 rounded-xl border p-3.5 cursor-pointer transition-all duration-200"
                        :class="selected === {{ $loop->index }} ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="flex items-center gap-3">
                            <input type="radio" name="doc_type" class="text-brand-600 focus:ring-brand-200" @change="selected = {{ $loop->index }}">
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ $doc['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $doc['days'] }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-navy-900 shrink-0">{{ $doc['fee'] }}</span>
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Purpose</label>
                <textarea rows="2" placeholder="e.g. Employment requirement" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                <x-ui.button
                    size="sm"
                    icon="send"
                    @click="open = false; $dispatch('toast', { message: 'Document request submitted — this is a frontend preview, no data is saved.', variant: 'success' })"
                >Submit Request</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.citizen>
