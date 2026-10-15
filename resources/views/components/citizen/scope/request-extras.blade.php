@props(['kind' => 'dokyu'])
{{-- Track Request, Dokyu and Tulong alike (scope round 2026-10-15): print the
     request slip (Tulong: the approval notice once approved), book an
     appointment for the request, the documents issued for it with their
     verification QR codes, and the satisfaction survey once it is released or
     completed. Reads the page's selectedRequest (its applyDetail() copies the
     detail's issued_documents and history onto it); logic in
     resources/js/scope-citizen.js (requestExtras).

     LANGUAGE (TAB 09): the printed letterhead keeps the LGU's own names
     ("Republic of the Philippines", "Municipality of Esperanza") as they
     appear on its paper; every other line follows the language toggle. --}}
<div x-data="requestExtras(@js($kind), @js(['appointments' => route('citizen.appointments'), 'verify' => route('verify')]))" class="mt-5 space-y-4">

    <div class="flex flex-wrap gap-2">
        <x-ui.button size="sm" variant="secondary" icon="printer" @click="openSheet('slip')"><x-ui.t fil="I-print ang request slip" en="Print request slip" /></x-ui.button>
        <x-ui.button size="sm" variant="secondary" icon="file-check-2" x-show="noticeReady" x-cloak @click="openSheet('notice')"><x-ui.t fil="Abiso ng pag-apruba" en="Approval notice" /></x-ui.button>
        <a x-show="canBook" :href="bookHref" class="inline-flex items-center justify-center gap-2 min-h-[36px] px-3.5 py-2 rounded-2xl text-xs font-semibold surface-glass-sm text-ink-body hover:bg-white/90 hover:shadow-glass transition-all duration-[160ms] ease-standard">
            <i data-lucide="calendar-clock" class="w-4 h-4 shrink-0"></i><x-ui.t fil="Mag-book ng appointment" en="Book an appointment" />
        </a>
    </div>

    {{-- Ready to claim (owner, 2026-10-15): where, the office hours, who to
         call and what to bring, from the server's own `pickup` on the
         request detail (CitizenRequestController::pickup), present only while
         the request waits to be claimed - Mark to Release, or Tulong's Ready
         for Release. The page's own Where to Claim box is the fallback when
         it is absent. The app's PickupCard. --}}
    <div x-show="claim" x-cloak class="rounded-xl border border-teal-200 bg-teal-50/60 p-3.5" data-pickup-card>
        <p class="text-sm font-bold text-ink flex items-center gap-2"><i data-lucide="store" class="w-4 h-4 text-teal-700"></i><x-ui.t fil="Handa nang kunin" en="Ready to claim" /></p>
        <p x-show="claimSince" class="text-[11px] text-ink-muted mt-0.5" x-text="t('Ready since ', 'Handa mula ') + claimSince"></p>
        <dl class="mt-3 space-y-2 text-xs">
            <div class="flex gap-3"><dt class="w-16 shrink-0 text-ink-muted"><x-ui.t fil="Saan" en="Where" /></dt><dd class="font-semibold text-ink" x-text="claim?.office || t('The office handling your request', 'Ang opisinang humahawak ng iyong kahilingan')"></dd></div>
            <div class="flex gap-3" x-show="claimHours"><dt class="w-16 shrink-0 text-ink-muted"><x-ui.t fil="Oras" en="Hours" /></dt><dd class="font-semibold text-ink whitespace-pre-line" x-text="claimHours"></dd></div>
            <div class="flex gap-3" x-show="claim?.office_contact"><dt class="w-16 shrink-0 text-ink-muted"><x-ui.t fil="Contact" en="Contact" /></dt>
                <dd class="font-semibold">
                    <a x-show="claimTel" :href="claimTel" class="text-brand-700 underline underline-offset-2" :aria-label="t('Call ', 'Tawagan ang ') + (claim?.office_contact || '')" x-text="claim?.office_contact"></a>
                    <span x-show="!claimTel" class="text-ink" x-text="claim?.office_contact"></span>
                </dd>
            </div>
        </dl>
        <div x-show="claimBring.length" class="mt-3">
            <p class="text-[11px] font-semibold text-ink"><x-ui.t fil="Dalhin" en="Bring" /></p>
            <ul class="mt-1 space-y-1">
                <template x-for="(item, i) in claimBring" :key="i">
                    <li class="flex items-start gap-2 text-xs text-ink-body"><i data-lucide="circle-check" class="w-3.5 h-3.5 text-teal-700 shrink-0 mt-0.5"></i><span x-text="item"></span></li>
                </template>
            </ul>
        </div>
        <a :href="bookHref" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline"><i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-book ng oras para kunin ito" en="Book a time to claim it" /></a>
    </div>

    {{-- The documents issued for this request: each one's control number,
         verification code, dates and QR, and the public Verify page. --}}
    <div x-show="issued.length" x-cloak class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-xs font-semibold text-ink mb-2.5 flex items-center gap-1.5"><i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-600"></i><x-ui.t fil="Mga inilabas na dokumento" en="Issued documents" /></p>
        <ul class="space-y-2.5">
            <template x-for="d in issued" :key="d.control_number">
                <li class="flex items-start gap-3 rounded-xl border border-brand-600/10 bg-white/70 p-2.5" :class="d.revoked && 'opacity-80'">
                    <div class="w-[72px] h-[72px] shrink-0 rounded-lg bg-white p-0.5 [&>svg]:w-full [&>svg]:h-full" x-html="docQrs[d.verify_url] || ''" role="img" :aria-label="t('QR code to verify ', 'QR code para suriin ang ') + d.control_number"></div>
                    <div class="min-w-0 flex-1 text-[11px] text-ink-muted space-y-0.5">
                        <p class="flex flex-wrap items-center gap-1.5">
                            <span class="font-mono text-xs font-semibold text-ink break-all" x-text="d.control_number"></span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-px rounded-full text-[10px] font-semibold ring-1 ring-inset"
                                :class="docState(d) === 'valid' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : (docState(d) === 'expired' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-rose-50 text-rose-700 ring-rose-200')"
                                x-text="docStateLabel(d)"></span>
                            <span x-show="d.is_reprint" class="px-1.5 py-px rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600" x-text="'Reprint' + (d.reprint_number ? ' #' + d.reprint_number : '')"></span>
                        </p>
                        <p><x-ui.t fil="Verification code:" en="Verification code:" /> <span class="font-mono text-ink-body" x-text="d.serial"></span></p>
                        <p x-text="t('Issued ', 'Inilabas ') + fmtDate(d.issued_on) + (d.valid_until ? ' · ' + t('valid until ', 'may bisa hanggang ') + fmtDate(d.valid_until) : '')"></p>
                        <p x-show="d.revoked" class="text-rose-700" x-text="t('Revoked', 'Binawi') + (d.revoked_reason ? ': ' + d.revoked_reason : '')"></p>
                        <span class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-0.5">
                            <a :href="verifyHref(d)" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline"><i data-lucide="shield-check" class="w-3 h-3"></i><x-ui.t fil="Suriin online" en="Verify online" /></a>
                            {{-- The scan of the signed, sealed paper: the resident's digital copy (scan_url, a signed link). --}}
                            <a x-show="scanState(d) === 'ready'" :href="d.scan_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline"><i data-lucide="file-text" class="w-3 h-3"></i><x-ui.t fil="Tingnan ang pirmadong kopya" en="View signed copy" /></a>
                        </span>
                        <p x-show="scanState(d) === 'none'" class="text-ink-muted"><x-ui.t fil="Wala pang inilakip na pirmadong kopya ang opisina. Ang papel na kopyang kukunin mo ang opisyal na dokumento." en="The office has not attached a signed copy yet. The paper copy you claim is the official document." /></p>
                        <p x-show="scanState(d) === 'checking'" class="text-ink-muted"><x-ui.t fil="Sinusuri pa ang pirmadong kopya. Buksan muli ang kahilingan pagkalipas ng isang minuto." en="The signed copy is still being checked. Reopen the request in a minute." /></p>
                    </div>
                </li>
            </template>
        </ul>
    </div>

    {{-- How was the service: once per request (POST /citizen/surveys). --}}
    <div x-show="surveyDue" x-cloak class="rounded-xl border border-brand-200 bg-brand-50/60 p-3.5">
        <p class="text-xs font-semibold text-ink"><x-ui.t fil="Kumusta ang serbisyo?" en="How was the service?" /></p>
        <p class="text-[11px] text-ink-muted mt-0.5"><x-ui.t fil="I-rate ang iyong karanasan sa kahilingang ito. Isang beses lang namin itong itatanong." en="Rate your experience with this request. We ask only once." /></p>
        <div class="flex flex-wrap items-center gap-1 mt-2" role="radiogroup" :aria-label="t('Rating, 1 to 5', 'Rating, 1 hanggang 5')">
            <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                <button type="button" role="radio" :aria-checked="rating === n" :aria-label="n + ' – ' + ratingLabel(n)" @click="rating = n"
                    class="w-9 h-9 rounded-xl grid place-items-center transition-colors hover:bg-white/70 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                    :class="rating >= n ? 'text-amber-500 [&_svg]:fill-current' : 'text-slate-300'">
                    <i data-lucide="star" class="w-6 h-6"></i>
                </button>
            </template>
            <span class="text-xs font-semibold text-ink ml-1.5" x-text="rating ? rating + '/5 · ' + ratingLabel(rating) : ''"></span>
        </div>
        <label class="block mt-2.5">
            <span class="block text-[11px] font-medium text-slate-600 mb-1"><x-ui.t fil="Komento (opsyonal)" en="Comment (optional)" /></span>
            <textarea x-model="comment" rows="2" maxlength="1000" class="w-full text-xs rounded-xl px-2.5 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
        </label>
        <p x-show="surveyError" role="alert" class="text-[11px] text-rose-700 mt-1" x-text="surveyError"></p>
        <div class="flex flex-wrap justify-end gap-2 mt-2">
            <x-ui.button variant="ghost" size="sm" @click="skipSurvey()"><x-ui.t fil="Laktawan" en="Skip" /></x-ui.button>
            <button type="button" @click="sendSurvey()" :disabled="!rating || surveySending" :aria-busy="surveySending"
                class="inline-flex items-center justify-center gap-2 min-h-[36px] px-3.5 py-2 rounded-2xl text-xs font-semibold clay-blue btn-shine hover:brightness-95 disabled:pointer-events-none disabled:bg-none disabled:bg-[#e4e9f4] disabled:text-ink-faint disabled:shadow-none">
                <span x-show="surveySending" x-cloak><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i></span>
                <span x-show="!surveySending"><i data-lucide="send" class="w-4 h-4"></i></span>
                <span x-text="surveySending ? t('Sending…', 'Ipinapadala…') : t('Send rating', 'Ipadala ang rating')"></span>
            </button>
        </div>
    </div>
    <p x-show="surveyState === 'sent' || surveyState === 'already'" x-cloak class="text-[11px] text-emerald-700 flex items-center gap-1.5">
        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
        <span x-text="surveyState === 'sent' ? t('Thank you! Your rating was received.', 'Salamat! Natanggap ang iyong rating.') : t('You have already rated this request.', 'Na-rate mo na ang kahilingang ito.')"></span>
    </p>

    {{-- The slip or notice, printed or saved through the site's print isolation. --}}
    <div x-data="{ get open() { return sheetOpen }, set open(v) { sheetOpen = v } }">
        <x-ui.modal title="Handa nang i-print" titleEn="Ready to print" maxWidth="2xl">
            <template x-if="sheetOpen && r">
                <div>
                    <div id="scope-print-sheet">
                        <div class="doc-paper relative bg-white text-slate-800 border border-slate-200 rounded-sm p-6 sm:p-10">
                            <div class="flex items-center gap-4 border-b-2 border-slate-800 pb-4">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="Municipal Seal of Esperanza" class="w-16 h-16 rounded-full object-cover shrink-0">
                                <div class="min-w-0">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-600">Republic of the Philippines · Province of Masbate</p>
                                    <p class="text-lg font-bold uppercase tracking-wide text-slate-900">Municipality of Esperanza</p>
                                    <p class="text-xs text-slate-600" x-text="r.office"></p>
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-4 mt-6">
                                <div class="min-w-0">
                                    <p class="text-xl font-extrabold tracking-wide text-slate-900" x-text="sheetKind === 'notice' ? t('NOTICE OF APPROVAL', 'ABISO NG PAG-APRUBA') : t('REQUEST SLIP', 'SLIP NG KAHILINGAN')"></p>
                                    <p class="text-xs text-slate-600 mt-0.5" x-text="(kind === 'tulong' ? 'Tulong' : 'Dokyu') + ' · Esperanza Citizen Portal'"></p>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 mt-4"><x-ui.t fil="Reference number" en="Reference number" /></p>
                                    <p class="font-mono text-2xl font-bold text-slate-900 break-all" x-text="r.ref"></p>
                                </div>
                                <div class="w-[30mm] h-[30mm] shrink-0 [&>svg]:w-full [&>svg]:h-full" x-html="refQr" role="img" :aria-label="t('QR code of the reference number', 'QR code ng reference number')"></div>
                            </div>

                            <template x-if="sheetKind === 'slip'">
                                <div>
                                    <table class="w-full mt-6 text-sm border-collapse">
                                        <tbody>
                                            <tr class="border-t border-slate-200"><th scope="row" class="w-[38%] py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Serbisyo" en="Service" /></th><td class="py-2 font-semibold text-slate-900" x-text="r.type"></td></tr>
                                            <tr class="border-t border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Opisina" en="Office" /></th><td class="py-2" x-text="r.office || '—'"></td></tr>
                                            <tr class="border-t border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Aplikante" en="Applicant" /></th><td class="py-2" x-text="applicant || '—'"></td></tr>
                                            <tr class="border-t border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top">Barangay</th><td class="py-2" x-text="barangay || '—'"></td></tr>
                                            <tr class="border-t border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Petsa ng pagsumite" en="Date filed" /></th><td class="py-2" x-text="filed || '—'"></td></tr>
                                            <tr class="border-t border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Kasalukuyang status" en="Current status" /></th><td class="py-2 font-semibold" x-text="r.status"></td></tr>
                                            <tr class="border-t border-b border-slate-200" x-show="r.purpose"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Layunin" en="Purpose" /></th><td class="py-2" x-text="r.purpose"></td></tr>
                                        </tbody>
                                    </table>
                                    <p class="text-sm font-bold text-slate-900 mt-6"><x-ui.t fil="Ano ang dadalhin" en="What to bring" /></p>
                                    <ul class="mt-2 space-y-1.5 text-sm">
                                        <template x-for="(item, i) in bring" :key="i">
                                            <li class="flex items-start gap-2"><span class="mt-1 w-3 h-3 rounded-sm border border-slate-500 shrink-0"></span><span x-text="item"></span></li>
                                        </template>
                                    </ul>
                                    <div x-show="pickup" class="mt-6">
                                        <p class="text-sm font-bold text-slate-900"><x-ui.t fil="Saan pupunta" en="Where to go" /></p>
                                        <p class="text-sm mt-1" x-text="(r.office ? r.office + ' — ' : '') + pickup"></p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="sheetKind === 'notice'">
                                <div>
                                    <p class="text-sm mt-6"><span class="font-semibold"><x-ui.t fil="Para kay:" en="To:" /></span> <span x-text="applicant || '—'"></span><span x-show="barangay" x-text="', Brgy. ' + barangay"></span></p>
                                    <p class="text-sm leading-relaxed mt-4" x-text="noticeBody"></p>
                                    <table class="w-full mt-5 text-sm border-collapse">
                                        <tbody>
                                            <tr class="border-t border-slate-200"><th scope="row" class="w-[38%] py-2 pr-3 text-left font-medium text-slate-500 align-top">Tulong</th><td class="py-2 font-semibold text-slate-900" x-text="r.type"></td></tr>
                                            <tr class="border-t border-slate-200" x-show="approvedAmount"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Inaprubahang halaga" en="Approved amount" /></th><td class="py-2 font-semibold tabular-nums" x-text="approvedAmount"></td></tr>
                                            <tr class="border-t border-slate-200" x-show="releasedAmount"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Halagang natanggap" en="Amount received" /></th><td class="py-2 font-semibold tabular-nums" x-text="releasedAmount"></td></tr>
                                            <tr class="border-t border-slate-200" x-show="r.assistance && r.assistance.released_at"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Petsa ng pagtanggap" en="Date received" /></th><td class="py-2" x-text="fmtDate(r.assistance ? r.assistance.released_at : '')"></td></tr>
                                            <tr class="border-t border-slate-200" x-show="r.assistance && r.assistance.release_reference"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Reference ng paglabas" en="Release reference" /></th><td class="py-2 font-mono" x-text="r.assistance ? r.assistance.release_reference : ''"></td></tr>
                                            <tr class="border-t border-slate-200" x-show="entitlement && !approvedAmount && !releasedAmount"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top"><x-ui.t fil="Maaaring matanggap" en="Entitlement" /></th><td class="py-2" x-text="entitlement"></td></tr>
                                            <tr class="border-t border-b border-slate-200"><th scope="row" class="py-2 pr-3 text-left font-medium text-slate-500 align-top">Status</th><td class="py-2 font-semibold" x-text="r.status"></td></tr>
                                        </tbody>
                                    </table>
                                    <div x-show="r.status !== 'Completed'" class="mt-6">
                                        <p class="text-sm font-bold text-slate-900"><x-ui.t fil="Saan kukunin" en="Where to claim" /></p>
                                        <p class="text-sm mt-1" x-text="(r.office ? r.office + ' — ' : '') + pickup"></p>
                                        <p class="text-sm mt-1"><x-ui.t fil="Dalhin ang abisong ito at isang valid ID na may larawan." en="Bring this notice and a valid ID with your photo." /></p>
                                    </div>
                                    <x-documents.verify-strip doc="selectedRequest.issuedDocuments" doc-key="selectedRequest.serviceKey" />
                                </div>
                            </template>

                            <div class="mt-10 pt-3 border-t border-slate-200 text-[10px] text-slate-500 flex flex-wrap justify-between gap-2">
                                <span x-text="t('Printed ', 'Inilimbag noong ') + printedAt"></span>
                                <span x-text="sheetKind === 'notice' ? t('Generated from the Esperanza Citizen Portal. Not an official receipt.', 'Ginawa mula sa Esperanza Citizen Portal. Hindi ito opisyal na resibo.') : t('Show this slip at the office. The status may change after printing.', 'Ipakita ang slip na ito sa opisina. Maaaring magbago ang status pagkatapos i-print.')"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <x-slot:footer>
                <x-ui.button size="sm" variant="secondary" icon="printer" @click="printSheet()"><x-ui.t fil="I-print" en="Print" /></x-ui.button>
                <x-ui.button size="sm" icon="download" loading="downloading" @click="downloadSheet()"><x-ui.t fil="I-download" en="Download PDF" /></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
