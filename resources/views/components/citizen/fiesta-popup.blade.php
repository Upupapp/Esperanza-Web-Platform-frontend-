@php
    $confettiColors = ['bg-brand-400', 'bg-gold-400', 'bg-emerald-400'];
    $confetti = collect(range(1, 20))->map(fn () => [
        'left' => rand(2, 96),
        'delay' => rand(0, 900) / 1000,
        'rotate' => rand(0, 360),
        'color' => $confettiColors[array_rand($confettiColors)],
    ]);
@endphp

{{-- "Balita" and "Fiesta ng Esperanza 2026" are the module name and the event's
     own name -- proper nouns, so they are NOT translated in either direction. --}}
<div x-data="{ open: false }" x-on:show-fiesta-popup.window="open = true" class="contents">
    <x-ui.modal title="Balita: Fiesta ng Esperanza 2026" titleEn="Balita: Fiesta ng Esperanza 2026" maxWidth="md">
        <div class="relative h-24 -mx-5 -mt-5 mb-1 overflow-hidden bg-gradient-to-b from-brand-50/70 to-transparent" aria-hidden="true">
            @foreach($confetti as $piece)
                <span
                    class="absolute top-0 w-1.5 h-3 rounded-sm {{ $piece['color'] }} animate-confetti-fall"
                    style="left: {{ $piece['left'] }}%; animation-delay: {{ $piece['delay'] }}s; transform: rotate({{ $piece['rotate'] }}deg);"
                ></span>
            @endforeach
        </div>
        <div class="flex flex-col items-center text-center pb-2">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gold-100 to-brand-50 flex items-center justify-center mb-4 animate-check-pop" aria-hidden="true">
                <i data-lucide="party-popper" class="w-10 h-10 text-gold-600"></i>
            </div>

            <h3 class="text-lg font-semibold text-navy-900">
                <x-ui.t
                    fil="Paparating na ang Fiesta ng Esperanza 2026!"
                    en="Fiesta ng Esperanza 2026 is Coming!" />
            </h3>

            <p class="text-sm text-slate-500 mt-2 max-w-[320px]">
                <x-ui.t
                    fil="Samahan ang pagdiriwang sa buong bayan ngayong Agosto — street dance, trade fair, at mga programa sa gabi. Ang Opening Program ay sa Agosto 14, 2026, 6:00 NG, sa Municipal Plaza."
                    en="Join the municipality-wide celebration this August — street dance, trade fair, and evening programs. Opening Program is Aug 14, 2026, 6:00 PM at the Municipal Plaza." />
            </p>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="open = false">
                <x-ui.t fil="Isara" en="Close" />
            </x-ui.button>
            <x-ui.button :href="route('citizen.announcements')" size="sm" icon="megaphone">
                <x-ui.t fil="Tingnan ang Balita" en="View Balita" />
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
