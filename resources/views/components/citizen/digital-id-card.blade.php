{{-- Esperanza Digital ID — a flippable Barangay Resident ID card, styled
     after the Esperanza Mobile app's own Digital ID. It shows `card` from the
     wallet around it (x-citizen.digital-id-wallet, GET /citizen/wallet ->
     resident_id.card, DigitalId::card() with its HMAC-signed QR payload), and
     the wallet renders it only while the Resident ID is active: a suspended or
     not-yet-issued card is never drawn as a credential (2026-10-13). --}}
{{-- LANGUAGE (TAB 09): the CARD FACE stays English in both languages, on
     purpose. These are the printed labels of a Philippine Barangay ID
     ("Republic of the Philippines", "Civil Status") and they are part of
     the credential, not UI chrome -- translating them would render an ID
     that does not match the physical one it mirrors. The chrome AROUND the
     card (the flip hint, the verification note, the control's
     accessible name) IS bilingual. --}}
<div
    x-data="{
        flipped: false,
        // The card's signed QR (resident_id.card.qr, DigitalId::card()) as a
        // real, scannable code - the one a barangay desk checks with
        // GET /verify-id/{payload}. Drawn by the same helper as an issued
        // document's QR (window.qrSvg, resources/js/shared-docs.js).
        qrMarkup: '',
        qrFor: null,
        drawQr(payload) {
            if (payload === this.qrFor) return;
            this.qrFor = payload;
            this.qrMarkup = '';
            if (!payload || !window.qrSvg) return;
            window.qrSvg(payload, 3).then((svg) => { if (this.qrFor === payload) this.qrMarkup = svg; }).catch(() => { this.qrMarkup = ''; });
        },
        get initials() {
            return (card?.name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('');
        },
    }"
    x-effect="drawQr(card?.qr || null)"
    class="w-full max-w-[360px] mx-auto select-none"
>
    <template x-if="card">
        <div>
            <p class="text-center text-[11px] text-slate-500 mb-3 flex items-center justify-center gap-1.5">
                <i data-lucide="pointer" class="w-3 h-3"></i>
                <x-ui.t fil="I-tap ang card para baligtarin" en="Tap the card to flip" />
            </p>

            <div class="relative [perspective:1600px]">
                <button
                    type="button"
                    @click="flipped = !flipped"
                    :aria-label="$store.lang.current === 'en'
                        ? (flipped ? 'Show the front of the ID' : 'Show the back of the ID')
                        : (flipped ? 'Ipakita ang harap ng ID' : 'Ipakita ang likod ng ID')"
                    :aria-pressed="flipped"
                    class="relative w-full aspect-[1.58/1] rounded-2xl [transform-style:preserve-3d] transition-transform duration-[650ms] ease-[cubic-bezier(0.4,0.1,0.2,1)] shadow-xl"
                    :class="flipped && '[transform:rotateY(180deg)]'"
                >
                    {{-- FRONT --}}
                    <div class="absolute inset-0 rounded-2xl overflow-hidden [backface-visibility:hidden] bg-gradient-to-br from-navy-950 via-navy-900 to-brand-900 text-white">
                        <div class="absolute inset-0 bg-grid-dots opacity-[0.15] pointer-events-none"></div>
                        <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-gold-400/10 blur-2xl pointer-events-none"></div>

                        <div class="relative h-full flex flex-col px-4 pt-3.5 pb-3">
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-7 h-7 rounded-full ring-1 ring-white/30 object-cover shrink-0">
                                <div class="min-w-0 leading-tight">
                                    <p class="text-[8px] text-white/50 uppercase tracking-wide">Republic of the Philippines</p>
                                    <p class="text-[9.5px] font-semibold text-white/85 truncate">Municipality of Esperanza, Masbate</p>
                                </div>
                                <span class="ml-auto text-[8px] font-bold text-gold-300 uppercase tracking-wider border border-gold-400/40 rounded-full px-1.5 py-0.5 shrink-0">Barangay ID</span>
                            </div>

                            <div class="flex items-center gap-3 mt-3.5">
                                {{-- The LGU-approved photo; initials until there is one. --}}
                                <img x-show="card.photo_url" :src="card.photo_url" alt="Resident photo" class="w-14 h-14 rounded-xl border border-white/20 object-cover shrink-0">
                                <div x-show="!card.photo_url" class="w-14 h-14 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-lg font-bold text-white shrink-0 overflow-hidden" x-text="initials"></div>
                                <div class="min-w-0">
                                    <p class="text-[8px] text-white/45 uppercase tracking-wide">Full Name</p>
                                    <p class="text-[13px] font-bold leading-tight truncate" x-text="card.name"></p>
                                    <p class="text-[9.5px] text-white/60 mt-0.5"><span>Brgy. </span><span x-text="card.barangay"></span> &middot; Resident</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-3">
                                <div>
                                    <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Date of Birth</p>
                                    <p class="text-[10.5px] font-semibold text-white/90" x-text="card.birthdate ? new Date(card.birthdate).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Sex</p>
                                    <p class="text-[10.5px] font-semibold text-white/90" x-text="card.sex || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Civil Status</p>
                                    <p class="text-[10.5px] font-semibold text-white/90" x-text="card.civil_status || '—'"></p>
                                </div>
                            </div>

                            <div class="mt-auto pt-2.5 flex items-end justify-between gap-2 border-t border-white/10">
                                <div>
                                    <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Resident ID No.</p>
                                    <p class="text-[11px] font-mono font-semibold tracking-wide text-gold-200" x-text="card.account_no"></p>
                                </div>
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-white/35"></i>
                            </div>
                        </div>
                    </div>

                    {{-- BACK --}}
                    <div class="absolute inset-0 rounded-2xl overflow-hidden [backface-visibility:hidden] [transform:rotateY(180deg)] bg-white border border-slate-200">
                        <div class="h-full flex flex-col px-4 pt-3 pb-3">
                            <div class="flex items-center justify-between">
                                <p class="text-[9px] font-semibold text-slate-500 uppercase tracking-wide">Address &amp; Standing</p>
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-300"></i>
                            </div>

                            <div class="flex items-start gap-3 mt-1.5 min-h-0 flex-1">
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 min-w-0 flex-1">
                                    <div class="min-w-0">
                                        <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Purok / Sitio</p>
                                        <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.purok || '—'"></p>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Account Status</p>
                                        <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.status || '—'"></p>
                                    </div>
                                    <div class="col-span-2 min-w-0">
                                        <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Full Address</p>
                                        <p class="text-[10px] font-semibold text-slate-700 line-clamp-2 leading-snug" x-text="card.address || '—'"></p>
                                    </div>
                                    <div class="col-span-2 min-w-0">
                                        <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Issued</p>
                                        <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.issued_at || '—'"></p>
                                    </div>
                                </div>

                                {{-- The scannable QR of the card's signed payload. --}}
                                <div class="w-[104px] h-[104px] shrink-0 rounded-lg bg-white border border-slate-200 p-1 [&>svg]:w-full [&>svg]:h-full"
                                    x-show="card.qr"
                                    x-html="qrMarkup"
                                    role="img"
                                    :aria-label="($store.lang.current === 'en' ? 'QR code for Resident ID ' : 'QR code ng Resident ID ') + (card.account_no || '')"></div>
                            </div>

                            <div class="mt-auto pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[9.5px] font-semibold text-navy-900 truncate" x-text="card.valid ? ($store.lang.current === 'en' ? 'Valid credential' : 'Balidong kredensyal') : ($store.lang.current === 'en' ? 'Not yet a valid credential' : 'Hindi pa balidong kredensyal')"></p>
                                    <p class="text-[7.5px] text-slate-500 font-mono truncate" x-text="card.account_no"></p>
                                </div>
                                <p class="text-[7px] text-slate-500 text-right max-w-[120px] leading-tight"><x-ui.t fil="Mula sa talaan ng Munisipyo. I-scan ang QR para i-verify." en="From the Municipality's records. Scan the QR to verify." /></p>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </template>
</div>
