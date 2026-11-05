@props(['title' => 'Welcome', 'image' => 'esperanza-aerial.jpg', 'fullscreen' => false, 'langSwitch' => true])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Esperanza Web Platform</title>
    <link rel="icon" href="{{ asset('images/esperanza/esperanza-seal-64.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased {{ $fullscreen ? 'overflow-hidden' : '' }}">
    {{-- First on the page: a keyboard or screen-reader user jumps past the navigation (WCAG 2.4.1, 2026-10-17). --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[200] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-brand-700 focus:shadow-lg focus:outline-none focus:ring-4 focus:ring-brand-200"><span x-text="$store.lang.current === 'en' ? 'Skip to content' : 'Lumaktaw sa nilalaman'">Lumaktaw sa nilalaman</span></a>
    <div class="{{ $fullscreen ? 'h-screen overflow-hidden' : 'min-h-screen' }} flex" x-data="{ time: '' }" x-init="const tick = () => time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }); tick(); setInterval(tick, 30000)">
        <div class="hidden lg:flex lg:w-[46%] relative overflow-hidden shrink-0">
            <img
                src="{{ asset('images/esperanza/' . $image) }}"
                alt="Municipality of Esperanza"
                class="absolute inset-0 w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-br from-navy-950/96 via-navy-900/88 to-brand-900/75"></div>
            <div class="absolute inset-0 bg-grid-dots opacity-40"></div>

            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-brand-500/25 blur-3xl animate-blob-float"></div>
            <div class="absolute bottom-0 -left-16 w-72 h-72 rounded-full bg-gold-400/25 blur-3xl animate-blob-float" style="animation-delay: -4s"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
                <div class="flex items-center justify-between animate-fade-up">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" class="w-12 h-12 rounded-full ring-2 ring-white/25 object-cover" alt="Municipal Seal">
                        <div>
                            <p class="font-semibold leading-tight">Municipality of Esperanza</p>
                            <p class="text-xs text-white/60">Province of Masbate</p>
                        </div>
                    </div>
                    <div class="hidden xl:flex items-center gap-1.5 text-[11px] font-mono text-white/50 tabular-nums" x-text="time"></div>
                </div>

                <div class="animate-fade-up" style="animation-delay: 100ms">
                    <p class="font-display text-3xl leading-snug max-w-md"><x-ui.t fil="Iisang plataporma para sa bawat residente, pamilya, at frontliner ng Esperanza." en="One platform for every resident, family, and frontliner of Esperanza." /></p>
                    <p class="text-white/60 text-sm mt-4 max-w-sm"><x-ui.t fil="Humiling ng dokumento, subaybayan ang mga programang tulong, at manatiling updated sa mga anunsyo ng munisipyo — lahat sa iisang lugar." en="Request documents, follow up assistance programs, and stay informed on municipal announcements — all in one place." /></p>
                </div>

                <div class="flex items-center gap-6 text-xs text-white/50 animate-fade-up" style="animation-delay: 200ms">
                    <span>20 Barangays</span>
                    <span class="w-1 h-1 rounded-full bg-white/30"></span>
                    <span>17,500+ Residents</span>
                    <span class="w-1 h-1 rounded-full bg-white/30"></span>
                    <span>LGU Digital Platform</span>
                </div>
            </div>
        </div>

        {{-- Clay design (STYLE_GUIDE §2): the washed canvas, the form on a glass card. --}}
        {{-- With the language switch, the top padding clears it (top-4/6 + its 44px height) so the card never slides under it. --}}
        <div class="relative flex-1 flex items-center justify-center p-5 sm:p-8 {{ $langSwitch ? 'pt-20 sm:pt-24' : '' }} canvas-washed {{ $fullscreen ? 'h-full overflow-hidden' : '' }}">

            @if ($langSwitch)
                {{-- Choose the language before signing in (live test 2026-10-07,
                     UX-6): the only switch used to be in Settings, after sign-in. --}}
                <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-10 inline-flex p-1 rounded-2xl surface-pressed-sm text-[13px] font-semibold" role="group" aria-label="Wika / Language">
                    <button type="button" @click="$store.lang.set('fil')" :aria-pressed="$store.lang.current !== 'en'"
                        :class="$store.lang.current !== 'en' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'" class="px-3.5 py-2 rounded-xl transition-all duration-200">Filipino</button>
                    <button type="button" @click="$store.lang.set('en')" :aria-pressed="$store.lang.current === 'en'"
                        :class="$store.lang.current === 'en' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'" class="px-3.5 py-2 rounded-xl transition-all duration-200">English</button>
                </div>
            @endif
            <main id="main-content" tabindex="-1" class="focus:outline-none w-full max-w-md animate-fade-up surface-glass rounded-[32px] p-7 sm:p-9 {{ $fullscreen ? 'max-h-full overflow-y-auto scrollbar-thin' : '' }}">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-ui.toast-container />
</body>
</html>
