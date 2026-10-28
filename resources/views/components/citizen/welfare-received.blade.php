{{-- The resident's Tulong page (welfare round, 2026-10-29): "Mga natanggap
     na serbisyo" - what the MSWDO recorded as given to them (a counseling
     session, a referral, assistance released), from GET
     /citizen/services-rendered, which never carries a confidential or voided
     record - and "Aking mga sertipiko", the certificates of the sessions they
     attended (theirs, or a partner's copy naming them), from GET
     /citizen/certificates, each opening the printable certificate. Logic:
     citizenWelfare() in resources/js/welfare-citizen.js. --}}
<div x-data="citizenWelfare()" {{ $attributes->merge(['class' => 'grid grid-cols-1 lg:grid-cols-2 gap-4']) }}>
    <section class="surface-glass rounded-[26px] p-4" aria-labelledby="welfare-received-title">
        <div class="flex items-center justify-between gap-2 mb-3">
            <h3 id="welfare-received-title" class="text-sm font-semibold text-ink flex items-center gap-2"><i data-lucide="hand-heart" class="w-4 h-4 text-brand-600"></i><x-ui.t fil="Mga natanggap na serbisyo" en="Services received" /></h3>
            <span x-show="services.length" x-cloak class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700" x-text="services.length"></span>
        </div>
        <div x-show="servicesLoading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-11" /><x-ui.skeleton class="h-11 opacity-80" /></div>
        <p x-show="servicesFailed" x-cloak class="text-xs text-rose-700" role="alert">
            <x-ui.t fil="Hindi ma-load ang listahan." en="Could not load the list." />
            <button type="button" class="underline font-medium" @click="loadServices()"><x-ui.t fil="Subukang muli" en="Try again" /></button>
        </p>
        <p x-show="!servicesLoading && !servicesFailed && !services.length" x-cloak class="text-xs text-ink-muted"><x-ui.t fil="Wala pang naitalang serbisyo para sa iyo." en="No services recorded for you yet." /></p>
        <ul x-show="services.length" x-cloak class="divide-y divide-brand-600/[.08]">
            <template x-for="(s, rowIndex) in services" :key="s.ref || rowIndex">
                <li class="flex items-start justify-between gap-3 py-2.5">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink leading-snug" x-text="s.service_name"></p>
                        <p class="text-[11px] text-ink-muted mt-0.5" x-text="[s.office, fmtDate(s.rendered_on)].filter(Boolean).join(' · ')"></p>
                    </div>
                    <span x-show="s.amount" class="shrink-0 text-xs font-semibold tabular-nums text-emerald-700" x-text="peso(s.amount)"></span>
                </li>
            </template>
        </ul>
    </section>

    <section x-show="certificates.length" x-cloak class="surface-glass rounded-[26px] p-4" aria-labelledby="welfare-certs-title">
        <h3 id="welfare-certs-title" class="text-sm font-semibold text-ink flex items-center gap-2 mb-3"><i data-lucide="award" class="w-4 h-4 text-amber-600"></i><x-ui.t fil="Aking mga sertipiko" en="My certificates" /></h3>
        <ul class="space-y-2">
            <template x-for="c in certificates" :key="c.control_number">
                <li class="rounded-xl border border-brand-600/10 bg-white/70 p-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink" x-text="c.title"></p>
                        <p class="text-[11px] text-ink-muted" x-text="holders(c)"></p>
                        <p class="text-[11px] text-ink-muted" x-text="[c.session?.date ? fmtDate(c.session.date) : '', c.session?.venue, c.control_number].filter(Boolean).join(' · ')"></p>
                    </div>
                    <x-ui.button size="sm" variant="secondary" icon="award" @click="openCertificate(c)"><x-ui.t fil="Tingnan ang sertipiko" en="View certificate" /></x-ui.button>
                </li>
            </template>
        </ul>
    </section>
</div>
