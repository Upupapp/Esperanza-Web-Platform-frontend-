{{-- Esperanza Digital ID — a flippable Barangay Resident ID card, styled
     after the Esperanza Mobile app's own Digital ID wallet (screens/profile/
     digital_id_screen.dart there: tap-to-flip, front = photo + core
     identity, back = family background + emergency contact + issuing
     signature), but populated entirely from this Web Platform's own
     canonical resident record (config/esperanza_constituents.php) rather
     than a duplicated data source — so if her profile changes, this card
     never drifts out of sync with it. FRONTEND SIMULATION — no real
     government/LGU ID system issued this credential. Currently seeded for
     Cristy Bonghanoy only (RES-2026-104), the same "flagship demo resident"
     both platforms already use — see this component's only call site,
     citizen/profile.blade.php, for the gate. --}}
{{-- LANGUAGE (TAB 09): the CARD FACE stays English in both languages, on
     purpose. These are the printed labels of a Philippine Barangay ID
     ("Republic of the Philippines", "Civil Status", "Mother's Maiden Name")
     and they are part of the credential, not UI chrome -- translating them
     would render an ID that does not match the physical one it mirrors.
     The chrome AROUND the card (the flip hint, the simulation disclaimer,
     the control's accessible name) IS bilingual. --}}
@php
    $resident = collect(config('esperanza_constituents.individuals'))->firstWhere('id', 'RES-2026-104');
    $baras = config('esperanza_barangay_documents.baras');
    $initials = collect(explode(' ', $resident['name']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    // Her real portrait — the same asset esperanza_citizen_avatars.php
    // already maps her account id to elsewhere in the app (e.g. the admin
    // request-queue avatar), not the ID-scan image used for document
    // verification (cristy-id.png, a different asset for a different
    // purpose — see $idPreviewAssetByCitizen in admin/document-requests.blade.php).
    $photoPath = config('esperanza_citizen_avatars.photoByAccountId')[$resident['citizen_account_id']] ?? null;
    $photo = $photoPath ? asset($photoPath) : null;
@endphp

<div
    x-data="{ flipped: false }"
    class="w-full max-w-[360px] mx-auto select-none"
>
    <p class="text-center text-[11px] text-slate-400 mb-3 flex items-center justify-center gap-1.5">
        <i data-lucide="hand-pointer" class="w-3 h-3"></i>
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
                        <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="" class="w-7 h-7 rounded-full ring-1 ring-white/30 object-cover shrink-0">
                        <div class="min-w-0 leading-tight">
                            <p class="text-[8px] text-white/50 uppercase tracking-wide">Republic of the Philippines</p>
                            <p class="text-[9.5px] font-semibold text-white/85 truncate">Municipality of Esperanza, Masbate</p>
                        </div>
                        <span class="ml-auto text-[8px] font-bold text-gold-300 uppercase tracking-wider border border-gold-400/40 rounded-full px-1.5 py-0.5 shrink-0">Barangay ID</span>
                    </div>

                    <div class="flex items-center gap-3 mt-3.5">
                        <div class="w-14 h-14 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-lg font-bold text-white shrink-0 overflow-hidden">
                            @if($photo)
                                <img src="{{ $photo }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-[8px] text-white/45 uppercase tracking-wide">Full Name</p>
                            <p class="text-[13px] font-bold leading-tight truncate">{{ $resident['name'] }}</p>
                            <p class="text-[9.5px] text-white/60 mt-0.5">Brgy. {{ $resident['barangay'] }} &middot; Resident</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-3">
                        <div>
                            <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Date of Birth</p>
                            <p class="text-[10.5px] font-semibold text-white/90">{{ \Carbon\Carbon::parse($resident['dob'])->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Sex</p>
                            <p class="text-[10.5px] font-semibold text-white/90">{{ $resident['sex'] }}</p>
                        </div>
                        <div>
                            <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Civil Status</p>
                            <p class="text-[10.5px] font-semibold text-white/90">{{ $resident['civil_status'] }}</p>
                        </div>
                    </div>

                    <div class="mt-auto pt-2.5 flex items-end justify-between gap-2 border-t border-white/10">
                        <div>
                            <p class="text-[7.5px] text-white/40 uppercase tracking-wide">Resident ID No.</p>
                            <p class="text-[11px] font-mono font-semibold tracking-wide text-gold-200">{{ $resident['id'] }}</p>
                        </div>
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-white/35"></i>
                    </div>
                </div>
            </div>

            {{-- BACK --}}
            <div class="absolute inset-0 rounded-2xl overflow-hidden [backface-visibility:hidden] [transform:rotateY(180deg)] bg-white border border-slate-200">
                <div class="h-full flex flex-col px-4 pt-3 pb-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[9px] font-semibold text-slate-500 uppercase tracking-wide">Family Background</p>
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-300"></i>
                    </div>

                    <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 mt-1.5">
                        <div>
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">Father's Name</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">{{ $resident['father_name'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">Mother's Maiden Name</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">{{ $resident['mother_maiden_name'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">Occupation</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">{{ $resident['occupation'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">Resident Since</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">{{ \Carbon\Carbon::parse($resident['created'])->format('Y') }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">Full Address</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">{{ $resident['address'] ?? '—' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[7.5px] text-slate-400 uppercase tracking-wide">In Case of Emergency</p>
                            <p class="text-[10px] font-semibold text-slate-700 truncate">
                                {{ $resident['emergency']['name'] ?? '—' }}
                                @if(!empty($resident['emergency']['relation'])) ({{ $resident['emergency']['relation'] }}) @endif
                                &middot; {{ $resident['emergency']['contact'] ?? '—' }}
                            </p>
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
                            <p class="text-[9.5px] font-semibold text-navy-900 truncate">{{ $baras['officials']['punong_barangay'] ?? '—' }}</p>
                            <p class="text-[7.5px] text-slate-400">Punong Barangay, {{ ucfirst($baras['label'] ?? 'Baras') }}</p>
                        </div>
                        <p class="text-[7px] text-slate-300 text-right max-w-[110px] leading-tight"><x-ui.t fil="Simulation lamang — hindi tunay na ID mula sa pamahalaan." en="Frontend simulation — not a real government-issued ID." /></p>
                    </div>
                </div>
            </div>
        </button>
    </div>
</div>
