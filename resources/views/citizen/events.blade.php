@php
    $events = [
        ['title' => 'Fiesta ng Esperanza — Opening Program', 'date' => 'Aug 14, 2026', 'day' => '14', 'month' => 'AUG', 'time' => '6:00 PM', 'venue' => 'Municipal Plaza', 'category' => 'Festival'],
        ['title' => 'Barangay Poblacion Medical Mission', 'date' => 'Jul 18, 2026', 'day' => '18', 'month' => 'JUL', 'time' => '8:00 AM', 'venue' => 'Poblacion Covered Court', 'category' => 'Health'],
        ['title' => 'Livelihood Skills Training — Batch 2', 'date' => 'Jul 25, 2026', 'day' => '25', 'month' => 'JUL', 'time' => '1:00 PM', 'venue' => 'Municipal Hall Conference Room', 'category' => 'Livelihood'],
        ['title' => 'Brigada Eskwela 2026', 'date' => 'Aug 3, 2026', 'day' => '3', 'month' => 'AUG', 'time' => '7:00 AM', 'venue' => 'Esperanza Central Elementary School', 'category' => 'Education'],
        ['title' => 'Quarterly Barangay Assembly — Poblacion', 'date' => 'Aug 9, 2026', 'day' => '9', 'month' => 'AUG', 'time' => '2:00 PM', 'venue' => 'Poblacion Barangay Hall', 'category' => 'Governance'],
    ];
@endphp

<x-layouts.citizen title="Events" subtitle="Upcoming municipal and barangay events." active="events">
    <div x-data="{}" class="animate-fade-up space-y-3.5">
        @foreach($events as $i => $event)
            <x-ui.card class="animate-fade-up" style="animation-delay: {{ $i * 40 }}ms">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-brand-50 text-brand-700 flex flex-col items-center justify-center shrink-0">
                        <span class="text-lg font-bold leading-none">{{ $event['day'] }}</span>
                        <span class="text-[10px] font-semibold uppercase tracking-wide">{{ $event['month'] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-navy-900">{{ $event['title'] }}</p>
                            <span class="text-[11px] font-medium text-brand-700 bg-brand-50 border border-brand-100 rounded-full px-2 py-0.5">{{ $event['category'] }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i>{{ $event['time'] }}</span>
                            <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i>{{ $event['venue'] }}</span>
                        </p>
                    </div>
                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        icon="bell"
                        class="shrink-0 hidden sm:inline-flex"
                        @click="$dispatch('toast', { message: 'We will remind you before this event starts.', variant: 'info' })"
                    >Remind Me</x-ui.button>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.citizen>
