@props(['title' => 'Dashboard', 'subtitle' => null, 'active' => 'dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Esperanza Citizen Portal</title>
    <link rel="icon" href="{{ asset('images/esperanza/esperanza-seal-64.png') }}">
    {{-- The per-barangay directory (seal, officials, contact) used to be
         injected here from config/esperanza_barangay_documents.php as
         window.__barangayCatalog. $store.barangay now loads it from
         GET /barangays itself (see resources/js/app.js) so every barangay in
         the real table shows up here, not just the ones a build-time fixture
         happened to list. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    <div class="flex h-screen overflow-hidden bg-slate-50" x-data="{ collapsed: false, mobileOpen: false }">
        <x-citizen.sidebar :active="$active" />

        <div class="flex-1 flex flex-col min-w-0">
            <x-citizen.topbar :title="$title" :subtitle="$subtitle" :active="$active" />

            <main class="flex-1 overflow-y-auto scrollbar-thin p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-ui.toast-container />
    <x-citizen.birthday-popup />
    <x-citizen.fiesta-popup />
    <x-citizen.alert-popup />
    <x-citizen.verified-popup />
</body>
</html>
