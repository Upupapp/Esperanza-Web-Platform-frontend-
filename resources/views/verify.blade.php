{{-- The public Verify page (scope round 2026-10-15): anyone - no account -
     types the verification code or control number printed on a document, or
     arrives from its QR code with ?code=..., and sees whether it is genuine
     (GET /verify/{token}: valid, revoked with the reason, or not found) and,
     from the date on it, whether it has expired. The endpoint never names the
     holder, by design. Logic in resources/js/scope-citizen.js (verifyPage). --}}
<x-layouts.guest title="Verify a Document" image="esperanza-aerial.jpg">
    <div x-data="verifyPage()">
        <div class="lg:hidden flex items-center gap-3 mb-6">
            <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" class="w-11 h-11 rounded-full ring-2 ring-slate-200 object-cover" alt="Municipal Seal">
            <div>
                <p class="font-semibold text-ink leading-tight">Municipality of Esperanza</p>
                <p class="text-xs text-ink-muted">Province of Masbate</p>
            </div>
        </div>

        <h1 class="text-2xl font-semibold text-ink flex items-center gap-2.5"><i data-lucide="shield-check" class="w-6 h-6 text-brand-600"></i><x-ui.t fil="Suriin ang Dokumento" en="Verify a Document" /></h1>
        <p class="text-ink-muted text-sm mt-1.5"><x-ui.t fil="Ilagay ang verification code o control number na nakalimbag sa dokumento, o i-scan ang QR code nito." en="Enter the verification code or control number printed on the document, or scan its QR code." /></p>

        <form class="mt-6" @submit.prevent="check()">
            <label for="verify-code" class="block text-sm font-semibold text-ink-body mb-1.5"><x-ui.t fil="Verification code o control number" en="Verification code or control number" /></label>
            <input id="verify-code" type="text" x-model="code" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="120"
                placeholder="ABCD-EFGH-IJKL"
                class="w-full min-h-[48px] rounded-2xl border-0 surface-pressed py-3 px-4 font-mono text-base tracking-wide text-ink placeholder:text-ink-faint placeholder:font-sans focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
            <p class="text-xs text-ink-muted mt-1.5"><x-ui.t fil="Hal. ang code sa tabi ng QR code (ABCD-EFGH-IJKL), o ang control number (BRGYCL-2026-000123)." en="E.g. the code beside the QR code (ABCD-EFGH-IJKL), or the control number (BRGYCL-2026-000123)." /></p>
            <x-ui.button type="submit" size="lg" class="w-full mt-4" x-bind:disabled="!code.trim() || state === 'loading'" x-bind:aria-busy="state === 'loading'">
                <span class="inline-flex items-center gap-2">
                    <span x-show="state === 'loading'" x-cloak><i data-lucide="loader-circle" class="w-[18px] h-[18px] animate-spin"></i></span>
                    <span x-show="state !== 'loading'"><i data-lucide="scan-line" class="w-[18px] h-[18px]"></i></span>
                    <span x-text="state === 'loading' ? t('Checking…', 'Sinusuri…') : t('Verify', 'Suriin')"></span>
                </span>
            </x-ui.button>
        </form>

        <div class="mt-6" aria-live="polite">
            <div x-show="state === 'loading'" class="space-y-2.5" aria-busy="true">
                <x-ui.skeleton class="h-20 rounded-2xl" />
                <x-ui.skeleton class="h-32 rounded-2xl opacity-70" />
            </div>

            {{-- Genuine: valid, expired, or revoked --}}
            <div x-show="found" x-cloak>
                <div class="rounded-2xl border p-4 flex items-start gap-3"
                    :class="state === 'valid' ? 'border-emerald-200 bg-emerald-50' : (state === 'expired' ? 'border-amber-200 bg-amber-50' : 'border-rose-200 bg-rose-50')">
                    <span class="w-11 h-11 rounded-2xl grid place-items-center shrink-0"
                        :class="state === 'valid' ? 'bg-emerald-500 text-white' : (state === 'expired' ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white')">
                        <span x-show="state === 'valid'"><i data-lucide="shield-check" class="w-6 h-6"></i></span>
                        <span x-show="state === 'expired'"><i data-lucide="hourglass" class="w-6 h-6"></i></span>
                        <span x-show="state === 'revoked'"><i data-lucide="shield-x" class="w-6 h-6"></i></span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-extrabold uppercase tracking-[.08em]"
                            :class="state === 'valid' ? 'text-emerald-700' : (state === 'expired' ? 'text-amber-700' : 'text-rose-700')"
                            x-text="state === 'valid' ? t('Valid', 'May bisa') : (state === 'expired' ? t('Expired', 'Lipas na') : t('Revoked', 'Binawi'))"></p>
                        <p class="text-base font-bold text-ink leading-snug" x-show="state === 'valid'"><x-ui.t fil="Tunay at may bisa ang dokumentong ito." en="This document is genuine and valid." /></p>
                        <p class="text-base font-bold text-ink leading-snug" x-show="state === 'expired'"><x-ui.t fil="Tunay ang dokumento, pero lipas na ang bisa nito." en="This document is genuine, but it has expired." /></p>
                        <p class="text-base font-bold text-ink leading-snug" x-show="state === 'revoked'"><x-ui.t fil="Binawi na ang dokumentong ito at wala na itong bisa." en="This document has been revoked and is no longer valid." /></p>
                        <p class="text-sm text-ink-body mt-1" x-show="state === 'valid'"><x-ui.t fil="Inilabas ito ng LGU Esperanza at hindi binawi." en="It was issued by LGU Esperanza and has not been revoked." /></p>
                        <p class="text-sm text-ink-body mt-1" x-show="state === 'expired'"><x-ui.t fil="Inilabas ito ng LGU Esperanza, pero lumipas na ang petsa ng bisa nito. Humiling ng bago sa opisina kung kailangan." en="It was issued by LGU Esperanza, but its valid-until date has passed. Ask the office for a new one if needed." /></p>
                        <p class="text-sm text-rose-800 mt-1" x-show="state === 'revoked'"><span class="font-semibold"><x-ui.t fil="Dahilan:" en="Reason:" /></span> <span x-text="result?.revoked_reason || t('Not stated.', 'Hindi nakasaad.')"></span></p>
                    </div>
                </div>

                <dl class="mt-3 rounded-2xl surface-pressed-sm divide-y divide-brand-600/[.08] text-sm">
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Dokumento" en="Document" /></dt>
                        <dd class="font-semibold text-ink text-right break-words min-w-0" x-text="documentName"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Control number" en="Control number" /></dt>
                        <dd class="font-mono font-semibold text-ink text-right break-all" x-text="result?.control_number"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5" x-show="result?.office">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Opisina" en="Office" /></dt>
                        <dd class="text-ink text-right break-words min-w-0" x-text="result?.office"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5" x-show="result?.barangay">
                        <dt class="text-ink-muted shrink-0">Barangay</dt>
                        <dd class="text-ink text-right" x-text="result?.barangay"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Inilabas noong" en="Issued on" /></dt>
                        <dd class="text-ink text-right" x-text="fmtDate(result?.issued_on) || '—'"></dd>
                    </div>
                    {{-- A revoked document is valid until nothing: it says when it was revoked instead (2026-10-15). --}}
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5" x-show="state !== 'revoked'">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="May bisa hanggang" en="Valid until" /></dt>
                        <dd class="text-right" :class="state === 'expired' ? 'font-semibold text-amber-700' : 'text-ink'" x-text="fmtDate(result?.valid_until) || '—'"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5" x-show="state === 'revoked'">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Binawi noong" en="Revoked on" /></dt>
                        <dd class="text-right font-semibold text-rose-700" x-text="fmtDate(result?.revoked_on) || '—'"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5" x-show="result?.is_reprint">
                        <dt class="text-ink-muted shrink-0"><x-ui.t fil="Kopya" en="Copy" /></dt>
                        <dd class="text-ink text-right"><x-ui.t fil="Muling inilimbag (reprint)" en="Reprint" /></dd>
                    </div>
                </dl>
                <p class="text-xs text-ink-muted mt-3 flex items-start gap-1.5"><i data-lucide="lock" class="w-3.5 h-3.5 shrink-0 mt-px"></i><x-ui.t fil="Para sa privacy, hindi ipinapakita ang pangalan ng may-ari. Ihambing ang mga detalyeng ito sa papel na hawak mo." en="For privacy, the holder's name is not shown. Compare these details with the paper in front of you." /></p>
                <details x-show="result?.content_sha256" class="mt-2 text-xs text-ink-muted">
                    <summary class="cursor-pointer select-none font-medium"><x-ui.t fil="Teknikal na detalye" en="Technical details" /></summary>
                    <p class="mt-1.5"><x-ui.t fil="SHA-256 ng nilalaman ng dokumento:" en="SHA-256 of the document's content:" /></p>
                    <p class="font-mono break-all text-[11px] text-ink-body mt-0.5" x-text="result?.content_sha256"></p>
                </details>
            </div>

            {{-- Not found --}}
            <div x-show="state === 'notfound'" x-cloak class="rounded-2xl border border-slate-200 bg-white/70 p-4 flex items-start gap-3" role="alert">
                <span class="w-11 h-11 rounded-2xl bg-slate-500 text-white grid place-items-center shrink-0"><i data-lucide="search-x" class="w-6 h-6"></i></span>
                <div class="min-w-0">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.08em] text-slate-600"><x-ui.t fil="Hindi natagpuan" en="Not found" /></p>
                    <p class="text-base font-bold text-ink leading-snug"><x-ui.t fil="Walang dokumentong tumutugma sa code na ito." en="No document matches this code." /></p>
                    <p class="text-sm text-ink-body mt-1"><x-ui.t fil="Suriin kung tama ang pagkaka-type. Kung galing ito sa QR code, maaaring hindi ito dokumento ng LGU Esperanza. Itanong sa opisinang naglabas nito kung may duda." en="Check that it is typed correctly. If it came from a QR code, it may not be an LGU Esperanza document. Ask the issuing office if in doubt." /></p>
                    <p x-show="checked && !fromQr" class="text-xs text-ink-muted mt-1.5"><x-ui.t fil="Sinuri:" en="Checked:" /> <span class="font-mono" x-text="checked"></span></p>
                </div>
            </div>

            {{-- Too many attempts (the endpoint allows 30 a minute) --}}
            <div x-show="state === 'toomany'" x-cloak class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3" role="alert">
                <span class="w-11 h-11 rounded-2xl bg-amber-500 text-white grid place-items-center shrink-0"><i data-lucide="timer" class="w-6 h-6"></i></span>
                <div class="min-w-0">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.08em] text-amber-700"><x-ui.t fil="Masyadong maraming pagsubok" en="Too many attempts" /></p>
                    <p class="text-sm text-ink-body mt-1"><x-ui.t fil="Maghintay ng isang minuto, saka subukang muli." en="Please wait a minute, then try again." /></p>
                    <x-ui.button variant="secondary" size="sm" icon="rotate-ccw" class="mt-3" @click="check()"><x-ui.t fil="Subukang muli" en="Try again" /></x-ui.button>
                </div>
            </div>

            {{-- Anything else: the connection, the server --}}
            <div x-show="state === 'error'" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 p-4 flex items-start gap-3" role="alert">
                <span class="w-11 h-11 rounded-2xl bg-rose-500 text-white grid place-items-center shrink-0"><i data-lucide="triangle-alert" class="w-6 h-6"></i></span>
                <div class="min-w-0">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.08em] text-rose-700"><x-ui.t fil="Hindi masuri ngayon" en="Could not check right now" /></p>
                    <p class="text-sm text-ink-body mt-1" x-text="failureText"></p>
                    <x-ui.button variant="secondary" size="sm" icon="rotate-ccw" class="mt-3" @click="check()"><x-ui.t fil="Subukang muli" en="Try again" /></x-ui.button>
                </div>
            </div>

            <button type="button" x-show="state !== 'idle' && state !== 'loading'" x-cloak @click="another()" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline">
                <i data-lucide="scan-line" class="w-4 h-4"></i><x-ui.t fil="Sumuri ng iba" en="Check another" />
            </button>
        </div>

        <p class="text-center text-sm text-ink-muted mt-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-brand-600 font-medium hover:underline"><i data-lucide="arrow-left" class="w-4 h-4"></i><x-ui.t fil="Bumalik sa Esperanza Web Platform" en="Back to the Esperanza Web Platform" /></a>
        </p>
    </div>
</x-layouts.guest>
