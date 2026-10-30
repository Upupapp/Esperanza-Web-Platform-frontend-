{{-- The resident's Tulong page (welfare round, 2026-10-29): "Aking mga
     sertipiko", the certificates of the sessions they attended (theirs, or a
     partner's copy naming them), from GET /citizen/certificates, each opening
     the printable certificate; shown only when there is one. Its "Mga
     natanggap na serbisyo" card left in round 2 (owner, 2026-10-30): the
     resident's own Tulong list above already shows what they received.
     Logic: citizenWelfare() in resources/js/welfare-citizen.js. --}}
<div x-data="citizenWelfare()" x-show="certificates.length" x-cloak {{ $attributes }}>
    <section class="surface-glass rounded-[26px] p-4" aria-labelledby="welfare-certs-title">
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
