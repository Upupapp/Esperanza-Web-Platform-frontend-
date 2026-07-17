@php
    $announcements = [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'category' => 'Community', 'author' => 'Corazon Villareal', 'date' => 'Jul 9, 2026', 'status' => 'Draft'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'category' => 'Health', 'author' => 'Dr. Leilani Domingo', 'date' => 'Jul 7, 2026', 'status' => 'Pending Review'],
        ['title' => 'Online Document Requests now accept GCash payment', 'category' => 'Public Service', 'author' => 'Paolo Reyes', 'date' => 'Jul 5, 2026', 'status' => 'Approved'],
        ['title' => 'Typhoon Preparedness Advisory', 'category' => 'Advisory', 'author' => 'MDRRMO', 'date' => 'Jul 3, 2026', 'status' => 'Completed'],
        ['title' => 'Free Skills Training for Out-of-School Youth', 'category' => 'Livelihood', 'author' => 'Corazon Villareal', 'date' => 'Jun 28, 2026', 'status' => 'Completed'],
        ['title' => '2025 Barangay Election Results Advisory', 'category' => 'Governance', 'author' => 'Atty. Marivic Ong', 'date' => 'Jan 15, 2026', 'status' => 'Archived'],
    ];

    $offices = [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Atty. Marivic S. Ong', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph'],
    ];

    $barangays = collect(config('esperanza.barangays'))->map(fn ($b) => [
        'name' => $b,
        'captain' => 'Hon. ' . collect(['Roberto','Elena','Manuel','Fe','Domingo','Rosario','Alfredo','Teresita'])->random() . ' ' . collect(['Cruz','Santos','Reyes','Bautista','Delos Reyes','Villanueva'])->random(),
    ]);

    $tab = $tab ?? 'balita';
@endphp

<x-layouts.admin title="Communications" subtitle="Balita and the municipal directory." active="communications">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4 overflow-x-auto">
            <button @click="tab = 'balita'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'balita' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="megaphone" class="w-3.5 h-3.5"></i>Balita</button>
            <button @click="tab = 'offices'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="building-2" class="w-3.5 h-3.5"></i>Offices</button>
            <button @click="tab = 'barangays'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i>Barangays</button>
        </div>

        <!-- Balita -->
        <div x-show="tab === 'balita'" x-data="{ open: false }">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Published" value="28" icon="megaphone" color="brand" sublabel="This year" :delay="0" />
                <x-ui.stat-card label="Pending Review" value="3" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card label="Drafts" value="2" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card label="Total Reach" value="14,208" icon="eye" color="green" sublabel="Citizen views this month" :delay="120" />
            </div>

            <div class="flex items-center justify-end mb-3">
                <x-ui.button @click="open = true" icon="plus" size="sm">New Balita</x-ui.button>
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Title</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Category</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Author</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Date</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($announcements as $a)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium max-w-xs">{{ $a['title'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden md:table-cell">{{ $a['category'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $a['author'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $a['date'] }}</td>
                            <td class="px-4 py-2.5"><x-ui.badge :status="$a['status']" /></td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.modal title="New Balita" maxWidth="lg">
                <div class="space-y-4">
                    <x-ui.input name="title" label="Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" />
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>Community</option>
                                <option>Health</option>
                                <option>Public Service</option>
                                <option>Advisory</option>
                                <option>Livelihood</option>
                                <option>Governance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Visibility</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>All Barangays</option>
                                <option>Selected Barangays</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
                        <textarea rows="4" placeholder="Write the announcement content..." class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
                    </div>
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="open = false; $dispatch('toast', { message: 'Saved as draft.', variant: 'info' })">Save as Draft</x-ui.button>
                    <x-ui.button size="sm" icon="send" @click="open = false; $dispatch('toast', { message: 'Announcement submitted for review.', variant: 'success' })">Submit for Review</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        <!-- Municipal offices -->
        <div x-show="tab === 'offices'" x-cloak>
            <div class="flex items-center justify-end mb-3">
                <x-ui.button icon="plus" size="sm">Add Office</x-ui.button>
            </div>
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Office</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Head</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Contact</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Email</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($offices as $office)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $office['name'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $office['head'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden md:table-cell font-mono text-xs">{{ $office['contact'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden lg:table-cell">{{ $office['email'] }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>

        <!-- Barangays -->
        <div x-show="tab === 'barangays'" x-cloak>
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay Captain</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Contact</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($barangays as $b)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium">Brgy. {{ $b['name'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500">{{ $b['captain'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell font-mono text-xs">0917 {{ rand(100,999) }} {{ rand(1000,9999) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>
    </div>
</x-layouts.admin>
