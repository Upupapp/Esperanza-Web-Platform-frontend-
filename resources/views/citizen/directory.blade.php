@php
    $offices = [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph', 'icon' => 'landmark'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph', 'icon' => 'hand-heart'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph', 'icon' => 'landmark'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph', 'icon' => 'stethoscope'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Atty. Marivic S. Ong', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph', 'icon' => 'file-badge'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph', 'icon' => 'monitor-cog'],
    ];

    $barangays = collect(config('esperanza.barangays'))->map(fn ($b) => [
        'name' => $b,
        'captain' => 'Hon. ' . collect(['Roberto','Elena','Manuel','Fe','Domingo','Rosario','Alfredo','Teresita'])->random() . ' ' . collect(['Cruz','Santos','Reyes','Bautista','Delos Reyes','Villanueva'])->random(),
    ])->take(8);
@endphp

<x-layouts.citizen title="Government Directory" subtitle="Contact details for municipal and barangay offices." active="directory">
    <div class="animate-fade-up">

        <div class="relative mb-4 max-w-md">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input type="text" placeholder="Search offices or barangays..." class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
        </div>

        <h3 class="text-sm font-semibold text-navy-900 mb-3">Municipal Offices</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
            @foreach($offices as $i => $office)
                <x-ui.card class="animate-fade-up" style="animation-delay: {{ $i * 40 }}ms">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $office['icon'] }}" class="w-[18px] h-[18px]"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-navy-900 leading-snug">{{ $office['name'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $office['head'] }}</p>
                        </div>
                    </div>
                    <div class="mt-3.5 pt-3.5 border-t border-slate-100 space-y-1.5 text-xs text-slate-500">
                        <p class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5 text-slate-300"></i>{{ $office['contact'] }}</p>
                        <p class="flex items-center gap-1.5"><i data-lucide="mail" class="w-3.5 h-3.5 text-slate-300"></i>{{ $office['email'] }}</p>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <h3 class="text-sm font-semibold text-navy-900 mb-3">Barangay Contacts</h3>
        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay Captain</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($barangays as $b)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 text-slate-700 font-medium">Brgy. {{ $b['name'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $b['captain'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell font-mono text-xs">0917 {{ rand(100,999) }} {{ rand(1000,9999) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </div>
</x-layouts.citizen>
