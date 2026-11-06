{{-- The resident's Tulong page (welfare round, 2026-10-29): "Aking mga
     sertipiko", the certificates of the sessions they attended (theirs, or a
     partner's copy naming them), from GET /citizen/certificates, each opening
     the printable certificate. Always shown (owner's manual test, 2026-11-06:
     residents could not find it before their first certificate), with an
     empty message until one is issued; the page's "My certificates" button
     jumps here (#my-certificates). Its "Mga
     natanggap na serbisyo" card left in round 2 (owner, 2026-10-30): the
     resident's own Tulong list above already shows what they received.
     Logic: citizenWelfare() in resources/js/welfare-citizen.js. --}}
<div x-data="citizenWelfare()" {{ $attributes }}>
    <section id="my-certificates" class="surface-glass rounded-[26px] p-4 scroll-mt-24" aria-labelledby="welfare-certs-title">
        <h3 id="welfare-certs-title" class="text-sm font-semibold text-ink flex items-center gap-2 mb-3"><i data-lucide="award" class="w-4 h-4 text-amber-600"></i><x-ui.t fil="Aking mga sertipiko" en="My certificates" /></h3>
        <div x-show="certificatesLoading" class="space-y-2"><x-ui.skeleton class="h-12" /></div>
        <p x-show="!certificatesLoading && !certificates.length" x-cloak class="text-sm text-ink-muted"><x-ui.t fil="Wala pang sertipiko - lalabas ang mga ito rito kapag naibigay na." en="No certificates yet - they appear here once issued." /></p>
        <ul class="space-y-2" x-show="certificates.length" x-cloak>
            <template x-for="c in certificates" :key="c.control_number">
                <li class="rounded-xl border border-brand-600/10 bg-white/70 p-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        {{-- An event's certificate is listed here too (round 2, 2026-10-30), marked as one. --}}
                        <p class="text-sm font-semibold text-ink"><span x-text="c.title"></span> <span class="ml-1 inline-block align-middle rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="isEventCert(c) ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-800'" x-text="isEventCert(c) ? tr('Event', 'Kaganapan') : tr('Session', 'Sesyon')"></span></p>
                        <p class="text-[11px] text-ink-muted" x-text="holders(c)"></p>
                        <p class="text-[11px] text-ink-muted" x-text="certLine(c)"></p>
                    </div>
                    <x-ui.button size="sm" variant="secondary" icon="award" @click="openCertificate(c)"><x-ui.t fil="Tingnan ang sertipiko" en="View certificate" /></x-ui.button>
                </li>
            </template>
        </ul>
    </section>
</div>
