@php
    $confettiColors = ['bg-brand-400', 'bg-gold-400', 'bg-purple-400', 'bg-rose-400', 'bg-emerald-400'];
    $confetti = collect(range(1, 20))->map(fn () => [
        'left' => rand(2, 96),
        'delay' => rand(0, 900) / 1000,
        'rotate' => rand(0, 360),
        'color' => $confettiColors[array_rand($confettiColors)],
    ]);
@endphp

<div x-data="{ open: false }" x-on:show-birthday-popup.window="open = true" class="contents">
    <x-ui.modal title="Maligayang Kaarawan! 🎂" titleEn="It's Your Birthday! 🎂" maxWidth="md">
        <div class="relative h-24 -mx-5 -mt-5 mb-1 overflow-hidden bg-gradient-to-b from-gold-50/70 to-transparent" aria-hidden="true">
            @foreach($confetti as $piece)
                <span
                    class="absolute top-0 w-1.5 h-3 rounded-sm {{ $piece['color'] }} animate-confetti-fall"
                    style="left: {{ $piece['left'] }}%; animation-delay: {{ $piece['delay'] }}s; transform: rotate({{ $piece['rotate'] }}deg);"
                ></span>
            @endforeach
        </div>
        <div class="flex flex-col items-center text-center pb-2">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gold-100 to-brand-50 flex items-center justify-center mb-4 animate-check-pop" aria-hidden="true">
                <i data-lucide="cake" class="w-10 h-10 text-gold-600"></i>
            </div>

            {{-- The greeting interpolates the resident's first name, so it is built
                 in Alpine rather than with x-ui.t. "Kabalen" is the Bikol/Filipino
                 address used when we do not know the name; the English fallback
                 keeps the same warmth without pretending to translate it. --}}
            <h3
                class="text-lg font-semibold text-ink"
                x-text="$store.lang.current === 'en'
                    ? 'Happy Birthday, ' + ($store.citizenSession.account?.firstName ?? 'Kabalen') + '! 🎉'
                    : 'Maligayang Kaarawan, ' + ($store.citizenSession.account?.firstName ?? 'Kabalen') + '! 🎉'"
            ></h3>

            <p class="text-sm text-ink-muted mt-2 max-w-[320px]">
                <x-ui.t
                    fil="Nawa'y biyayaan ka ng Pamahalaang Bayan ng Esperanza ng mabuting kalusugan, kaligayahan, at biyaya sa iyong natatanging araw. Salamat sa patuloy na pagtitiwala sa ating LGU!"
                    en="The Municipality of Esperanza wishes you good health, happiness, and blessings on your special day. Thank you for your continued trust in our LGU!" />
            </p>
        </div>

        <x-slot:footer>
            <x-ui.button size="sm" icon="cake" @click="open = false">
                <x-ui.t fil="Salamat!" en="Thank you!" />
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
