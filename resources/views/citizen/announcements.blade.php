@php
    $announcements = [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'body' => 'Join the municipality-wide celebration this August featuring street dancing, a trade fair, and evening cultural programs at the Municipal Plaza.', 'category' => 'Community', 'time' => 'Posted yesterday', 'image' => 'esperanza_festival.jpg'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'body' => 'Free checkups, dental extraction, and medicine distribution at the covered court. Bring a valid ID and your PhilHealth card if available.', 'category' => 'Health', 'time' => 'Posted 3 days ago', 'image' => 'esperanza_people.jpg'],
        ['title' => 'Online Document Requests now accept GCash payment', 'body' => 'Citizens may now pay processing fees for document requests online through GCash, skipping the queue at the Municipal Hall.', 'category' => 'Public Service', 'time' => 'Posted 5 days ago', 'image' => 'esperanza-community.jpg'],
        ['title' => 'Typhoon Preparedness Advisory', 'body' => 'PAGASA has issued a low pressure area advisory. Residents in coastal barangays are advised to monitor updates from the MDRRMO.', 'category' => 'Advisory', 'time' => 'Posted 1 week ago', 'image' => 'esperanza-boats.jpg'],
        ['title' => 'Free Skills Training for Out-of-School Youth', 'body' => 'TESDA-accredited livelihood training on food processing and dressmaking. Registration open until July 20 at the Municipal Hall.', 'category' => 'Livelihood', 'time' => 'Posted 1 week ago', 'image' => 'esperanza_community2.jpg'],
        ['title' => '2026 Real Property Tax Deadline Reminder', 'body' => 'Taxpayers are reminded that the deadline for the 3rd quarter real property tax payment is on July 31, 2026.', 'category' => 'Public Service', 'time' => 'Posted 2 weeks ago', 'image' => 'rectangle_lgu.png'],
    ];

    $categories = ['All', 'Community', 'Health', 'Public Service', 'Advisory', 'Livelihood'];
@endphp

<x-layouts.citizen title="Balita" subtitle="Latest news and advisories from the municipality." active="announcements">
    <div x-data="{ cat: 'All' }" class="animate-fade-up">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit overflow-x-auto mb-4">
            @foreach($categories as $c)
                <button
                    @click="cat = '{{ $c }}'"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                    :class="cat === '{{ $c }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"
                >@if($c === 'All')<x-ui.t fil="Lahat" en="All" />@else{{ $c }}@endif</button>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($announcements as $i => $a)
                <div
                    x-show="cat === 'All' || cat === '{{ $a['category'] }}'"
                    class="bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden animate-fade-up"
                    style="animation-delay: {{ $i * 40 }}ms"
                >
                    <div class="relative h-36 overflow-hidden">
                        <img src="{{ asset('images/esperanza/' . $a['image']) }}" alt="" class="w-full h-full object-cover">
                        <span class="absolute top-3 left-3 text-[11px] font-medium text-white bg-navy-900/70 backdrop-blur px-2.5 py-1 rounded-full">{{ $a['category'] }}</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm font-semibold text-navy-900 leading-snug">{{ $a['title'] }}</p>
                        <p class="text-xs text-slate-500 mt-1.5 line-clamp-3">{{ $a['body'] }}</p>
                        <p class="text-[11px] text-slate-300 mt-2.5">{{ $a['time'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.citizen>
