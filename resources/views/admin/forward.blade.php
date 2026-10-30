{{-- An old address whose screen moved (round 2, 2026-10-30: Settings >
     Service Catalog became Dokyu's Document types). The site is static, with
     no server to answer a redirect, and the CSP allows no inline script: the
     browser is sent on by x-init as Alpine starts, replacing this address in
     the history so Back does not return here. The link is for anyone whose
     browser does not run the page's scripts. Route: routes/web.php
     ($to, $label, $active). --}}
<x-layouts.admin :title="$label" :active="$active ?? 'dashboard'">
    <div x-init="window.location.replace('{{ $to }}')" class="flex flex-col items-center justify-center text-center py-20 px-6 surface-glass rounded-[26px]" data-forward-to="{{ $to }}">
        <span class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-3"><i data-lucide="arrow-right" class="w-5 h-5"></i></span>
        <p class="text-sm text-slate-600">This screen moved. Opening <a href="{{ $to }}" class="font-semibold text-brand-700 hover:underline">{{ $label }}</a>…</p>
    </div>
</x-layouts.admin>
