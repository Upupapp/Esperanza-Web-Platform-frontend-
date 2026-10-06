<x-layouts.guest title="Page not found" image="esperanza-aerial.jpg" fullscreen>
    {{-- Any address that is not a page lands here (netlify.toml). It used to be
         the welcome page with no explanation (live test 2026-10-07, PR-3). --}}
    <div class="lg:hidden flex items-center gap-3 mb-8">
        <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" class="w-11 h-11 rounded-full ring-2 ring-slate-200 object-cover" alt="Seal">
        <div>
            <p class="font-semibold text-navy-900 leading-tight">Municipality of Esperanza</p>
            <p class="text-xs text-slate-500">Province of Masbate</p>
        </div>
    </div>

    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-5">
        <i data-lucide="map-pin-off" class="w-6 h-6"></i>
    </div>
    <h1 class="text-2xl font-semibold text-navy-900"><x-ui.t fil="Hindi nahanap ang pahinang ito" en="This page was not found" /></h1>
    <p class="text-slate-500 text-sm mt-2"><x-ui.t fil="Maaaring luma na ang link, o may mali sa address. Bumalik sa simula at piliin kung saan ka pupunta." en="The link may be old, or the address may have a typo. Go back to the start and choose where to go." /></p>

    <div class="mt-7 space-y-3">
        <a href="{{ route('citizen.login') }}" class="flex items-center justify-between gap-3 p-4 rounded-2xl border border-slate-200 bg-white hover:border-brand-300 transition">
            <span class="font-medium text-navy-900"><x-ui.t fil="Portal ng Mamamayan" en="Citizen Portal" /></span>
            <i data-lucide="chevron-right" class="w-5 h-5 text-slate-300"></i>
        </a>
        <a href="{{ route('home') }}" class="flex items-center justify-between gap-3 p-4 rounded-2xl border border-slate-200 bg-white hover:border-brand-300 transition">
            <span class="font-medium text-navy-900"><x-ui.t fil="Simula" en="Start page" /></span>
            <i data-lucide="chevron-right" class="w-5 h-5 text-slate-300"></i>
        </a>
        <a href="{{ route('admin.login') }}" class="flex items-center justify-between gap-3 p-4 rounded-2xl border border-slate-200 bg-white hover:border-navy-300 transition">
            <span class="font-medium text-navy-900">LGU Personnel</span>
            <i data-lucide="chevron-right" class="w-5 h-5 text-slate-300"></i>
        </a>
    </div>
</x-layouts.guest>
