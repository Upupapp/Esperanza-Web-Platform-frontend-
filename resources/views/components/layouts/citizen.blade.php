@props(['title' => 'Dashboard', 'subtitle' => null, 'active' => 'dashboard', 'public' => false])
{{-- public: a page anyone may read signed out (the Privacy Policy and the
     Terms, opened from sign-up in a new tab). Without a session it skips the
     calls only a signed-in resident can make - the bell, My IDs, the request
     counts - which used to answer 401 and send the visitor to sign-in. Every
     other citizen page stays sign-in only. --}}
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
    {{-- Clay design (STYLE_GUIDE §2): the full-height shell stays (pop-ups and
         the Dokyu print path depend on it) on a washed canvas. <main> reaches
         back over the shell's padding so the cards' shadows have a gutter
         inside the scroll area instead of being cut at its edges. --}}
    <div class="flex h-screen overflow-hidden canvas-washed gap-5 p-4 lg:p-5" x-data="{ collapsed: false, mobileOpen: false }">
        <x-citizen.sidebar :active="$active" :public="$public" />

        <div class="flex-1 flex flex-col gap-5 min-w-0">
            <x-citizen.topbar :title="$title" :subtitle="$subtitle" :active="$active" :public="$public" />

            <main class="flex-1 overflow-y-auto scrollbar-thin -mx-4 px-5 pt-4 pb-6 lg:-mx-5 lg:px-8 lg:pb-10">
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
