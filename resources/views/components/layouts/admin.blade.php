@props(['title' => 'Dashboard', 'subtitle' => null, 'active' => 'dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — Esperanza LGU Administrative Portal</title>
    <link rel="icon" href="{{ asset('images/esperanza/esperanza-seal-64.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    {{-- First on the page: a keyboard or screen-reader user jumps past the navigation (WCAG 2.4.1, 2026-10-17). --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[200] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-brand-700 focus:shadow-lg focus:outline-none focus:ring-4 focus:ring-brand-200">Skip to content</a>
    {{-- Clay design (STYLE_GUIDE §2): the full-height shell stays (pop-ups and
         the Dokyu print path depend on it) on a washed canvas. <main> reaches
         back over the shell's padding so the cards' shadows have a gutter
         inside the scroll area instead of being cut at its edges. --}}
    <div class="flex h-screen overflow-hidden canvas-washed gap-5 p-4 lg:p-5" x-data="{ collapsed: false, mobileOpen: false }">
        <x-admin.sidebar :active="$active" />
        <x-admin.my-profile />

        <div class="flex-1 flex flex-col gap-5 min-w-0">
            <x-admin.topbar :title="$title" :subtitle="$subtitle" />

            <main id="main-content" tabindex="-1" class="focus:outline-none flex-1 overflow-y-auto scrollbar-thin -mx-4 px-5 pt-4 pb-6 lg:-mx-5 lg:px-8 lg:pb-10">
                {{-- TAB 08: no session -> the portal shows nothing but a way in.
                     Gated here, once, rather than in each of the admin pages. --}}
                <div x-show="!$store.session.account" x-cloak>
                    <x-admin.sign-in-required />
                </div>

                <div x-show="$store.session.account" x-cloak>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <x-ui.toast-container />
</body>
</html>
