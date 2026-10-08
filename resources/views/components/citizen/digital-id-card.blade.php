{{-- Esperanza Digital ID — a flippable Barangay Resident ID card, styled
     after the Esperanza Mobile app's own Digital ID wallet. Loaded from
     GET /citizen/digital-id (App\Domain\Identity\DigitalId::card(), a
     projection of the citizen account plus an HMAC-signed QR payload) when
     the card's modal opens — production-readiness programme, 2026-09-25.
     Used to be hardcoded to one fixture resident (RES-2026-104 / Cristy);
     every citizen sees their own card now. --}}
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
        card: null,
        loading: true,
        failed: false,
        get initials() {
            return (this.card?.name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('');
        },
        async init() {
            this.loading = true;
            this.failed = false;
            try {
                const result = await $api.get('/citizen/digital-id');
                this.card = result.data;
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
    }"
    class="w-full max-w-[360px] mx-auto select-none"
>
    <div x-show="loading" class="space-y-3">
        <x-ui.skeleton class="aspect-[1.58/1] rounded-2xl" />
    </div>

    <div x-show="failed" x-cloak>
        <x-ui.error-state messageExpr="($store.lang.current === 'en' ? 'Could not load your Digital ID.' : 'Hindi ma-load ang iyong Digital ID.')" retryExpr="init()" :retryable="true" />
    </div>

    <template x-if="!loading && !failed && card">
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

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 mt-1.5">
                                <div>
                                    <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Purok / Sitio</p>
                                    <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.purok || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Account Status</p>
                                    <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.status || '—'"></p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Full Address</p>
                                    <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.address || '—'"></p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[7.5px] text-slate-500 uppercase tracking-wide">Issued</p>
                                    <p class="text-[10px] font-semibold text-slate-700 truncate" x-text="card.issued_at || '—'"></p>
                                </div>
                            </div>

                            {{-- Barcode-style decoration — purely visual, common on real ID backs --}}
                            <div class="flex items-end gap-[1.5px] h-5 mt-2 opacity-70" aria-hidden="true">
                                @foreach([2,1,3,1,1,2,3,1,2,1,1,3,2,1,3,1,2,1,1,3,2,3,1,1,2,1,3,2,1,1,3,1,2,1,3,2,1,1,2,3] as $bar)
                                    <div class="bg-slate-800 w-[1.5px]" style="height: {{ $bar * 25 }}%"></div>
                                @endforeach
                            </div>

                            <div class="mt-auto pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[9.5px] font-semibold text-navy-900 truncate" x-text="card.valid ? ($store.lang.current === 'en' ? 'Valid credential' : 'Balidong kredensyal') : ($store.lang.current === 'en' ? 'Not yet a valid credential' : 'Hindi pa balidong kredensyal')"></p>
                                    <p class="text-[7.5px] text-slate-500" x-text="card.qr"></p>
                                </div>
                                <p class="text-[7px] text-slate-300 text-right max-w-[110px] leading-tight"><x-ui.t fil="Mula sa talaan ng Munisipyo. I-scan ang QR para i-verify." en="From the Municipality's records. Scan the QR to verify." /></p>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </template>
</div>
