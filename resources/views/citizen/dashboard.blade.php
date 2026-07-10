@php
    $requests = [
        ['ref' => 'DR-2026-2210', 'type' => 'Cedula (Community Tax Certificate)', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2187', 'type' => 'Barangay Clearance', 'submitted' => 'Jul 3, 2026', 'status' => 'Ready for Release'],
        ['ref' => 'AR-2026-0142', 'type' => 'Medical Assistance', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'DR-2026-1998', 'type' => 'Certificate of Residency', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
    ];

    $announcements = [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'body' => 'Join the municipality-wide celebration this August. Street dance, trade fair, and evening programs.', 'time' => 'Posted yesterday', 'icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'body' => 'Free checkups, dental extraction, and medicine distribution at the covered court.', 'time' => 'Posted 3 days ago', 'icon' => 'stethoscope', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['title' => 'Online Document Requests now accept GCash payment', 'body' => 'Pay processing fees online and skip the queue at the Municipal Hall.', 'time' => 'Posted 5 days ago', 'icon' => 'credit-card', 'color' => 'text-brand-600 bg-brand-50'],
    ];

    $events = [
        ['title' => 'Fiesta ng Esperanza — Opening Program', 'date' => 'Aug 14, 2026', 'time' => '6:00 PM', 'venue' => 'Municipal Plaza'],
        ['title' => 'Barangay Poblacion Medical Mission', 'date' => 'Jul 18, 2026', 'time' => '8:00 AM', 'venue' => 'Poblacion Covered Court'],
        ['title' => 'Livelihood Skills Training', 'date' => 'Jul 25, 2026', 'time' => '1:00 PM', 'venue' => 'Municipal Hall Conference Room'],
    ];
@endphp

<x-layouts.citizen title="Dashboard" subtitle="Welcome back to your Esperanza Citizen Portal." active="dashboard">
    <div x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 400)">

        <!-- Skeleton state -->
        <div x-show="!ready" x-transition.opacity.duration.300ms>
            <div class="flex items-center justify-between mb-4">
                <div class="space-y-2">
                    <x-ui.skeleton class="h-5 w-48" />
                    <x-ui.skeleton class="h-3 w-64" />
                </div>
                <x-ui.skeleton class="h-9 w-40 rounded-xl" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @for ($i = 0; $i < 4; $i++)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 space-y-3">
                        <x-ui.skeleton class="w-10 h-10 rounded-xl" />
                        <x-ui.skeleton class="h-6 w-16" />
                        <x-ui.skeleton class="h-3 w-24" />
                    </div>
                @endfor
            </div>
        </div>

        <!-- Real content -->
        <div x-show="ready" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-navy-900">Magandang araw, Maria Fe</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Brgy. Poblacion · Here's your account overview.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-ui.button :href="route('citizen.assistance-requests')" variant="secondary" size="sm" icon="hand-heart">Request Assistance</x-ui.button>
                    <x-ui.button :href="route('citizen.document-requests')" size="sm" icon="file-plus-2">New Document Request</x-ui.button>
                </div>
            </div>

            <!-- KPI row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-4">
                <x-ui.stat-card label="Document Requests" value="4" icon="file-text" color="brand" sublabel="1 ready for release" :delay="0" />
                <x-ui.stat-card label="Assistance Requests" value="1" icon="hand-heart" color="purple" sublabel="Approved this month" :delay="40" />
                <x-ui.stat-card label="Unread Notifications" value="4" icon="bell" color="orange" sublabel="2 need your attention" :delay="80" />
                <x-ui.stat-card label="Profile Completeness" value="82%" icon="user-round-check" color="green" trend="+10%" sublabel="Add a valid ID to finish" :delay="120" />
            </div>

            <!-- Middle row: my requests + announcements -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <x-ui.card padded="false" class="lg:col-span-2">
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">My Recent Requests</h3>
                        <a href="{{ route('citizen.document-requests') }}" class="text-xs font-medium text-brand-600 hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($requests as $req)
                            <div class="flex items-center justify-between gap-4 px-4 py-2.5 hover:bg-slate-50/70 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate">{{ $req['type'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ $req['ref'] }} · Submitted {{ $req['submitted'] }}</p>
                                </div>
                                <x-ui.badge :status="$req['status']" class="shrink-0" />
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card padded="false">
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">Announcements</h3>
                        <a href="{{ route('citizen.announcements') }}" class="text-xs font-medium text-brand-600 hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($announcements as $a)
                            <div class="flex items-start gap-3 px-4 py-2.5 hover:bg-slate-50/70 transition-colors">
                                <span class="w-8 h-8 rounded-lg {{ $a['color'] }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $a['icon'] }}" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 leading-snug">{{ $a['title'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5 line-clamp-2">{{ $a['body'] }}</p>
                                    <p class="text-[11px] text-slate-300 mt-1">{{ $a['time'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <!-- Upcoming events -->
            <x-ui.card padded="false">
                <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                    <h3 class="text-sm font-semibold text-navy-900">Upcoming Events</h3>
                    <a href="{{ route('citizen.events') }}" class="text-xs font-medium text-brand-600 hover:underline">View All</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 px-4 pb-4">
                    @foreach($events as $event)
                        <div class="rounded-xl border border-slate-100 p-3.5 hover:border-brand-200 hover:shadow-card transition-all duration-300">
                            <div class="flex items-center gap-2 text-xs font-medium text-brand-600 mb-1.5">
                                <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
                                {{ $event['date'] }} · {{ $event['time'] }}
                            </div>
                            <p class="text-sm font-medium text-slate-700 leading-snug">{{ $event['title'] }}</p>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                {{ $event['venue'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.citizen>
