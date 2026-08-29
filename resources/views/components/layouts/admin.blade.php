@props(['title' => 'Dashboard', 'subtitle' => null, 'active' => 'dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Esperanza LGU Administrative Portal</title>
    <link rel="icon" href="{{ asset('images/esperanza/esperanza-seal.png') }}">
    <script>
        window.__ESPERANZA_RBAC_ACCOUNTS__ = @json(collect(config('esperanza_rbac.accounts'))->map(fn ($a) => ['id' => $a['id'], 'moduleKeys' => $a['moduleKeys'], 'permissions' => $a['permissions']])->values());
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    <div class="flex h-screen overflow-hidden bg-slate-50" x-data="{ collapsed: false, mobileOpen: false }">
        <x-admin.sidebar :active="$active" />

        <div class="flex-1 flex flex-col min-w-0">
            <x-admin.topbar :title="$title" :subtitle="$subtitle" />

            <main class="flex-1 overflow-y-auto scrollbar-thin p-4 lg:p-6">
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
