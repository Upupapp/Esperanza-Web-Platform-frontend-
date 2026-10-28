{{-- The e-certificate of a group session (welfare round, 2026-10-29): A4
     landscape, the municipal seal, the LGU's letterhead, the title and
     subtitle, the body with the holders' names, the signatories (a
     counselor's DSWD accreditation number under the name), the control
     number, the issue date, and the QR of the Verify page with the
     verification code. Print and Download PDF as the other issued documents.
     Opens on window.openWelfareCertificate(doc, meta); logic and the data it
     reads in resources/js/welfare-certificate.js. Place it AFTER the windows
     that open it, so it stacks above them.

     LANGUAGE (TAB 09): the certificate itself is an official document, in
     English as the MSWDO issues it on paper; on the resident's portal
     (bilingual) the window's own title, notes and buttons follow the
     language toggle. The staff site is English only. --}}
@props(['bilingual' => false])
@php
    $certByKey = collect(config('esperanza_tulong_types.types'))
        ->filter(fn ($t) => ! empty($t['key']) && ! empty($t['certificate']))
        ->mapWithKeys(fn ($t) => [$t['key'] => $t['certificate']])
        ->all();
@endphp
<div x-data="welfareCertificate(@js($certByKey), @js((bool) $bilingual))" x-on:welfare-certificate.window="openCertificate($event.detail)">
    <div x-data="{ get open() { return certOpen }, set open(v) { certOpen = v } }">
        <x-ui.modal title="{{ $bilingual ? 'Sertipiko' : 'Certificate' }}" titleEn="Certificate" maxWidth="3xl">
            <template x-if="certOpen && cert">
                <div>
                    <p x-show="cert.revoked" class="dp-no-print mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert"
                       x-text="certT('This certificate was revoked', 'Binawi ang sertipikong ito') + (cert.revokedReason ? ': ' + cert.revokedReason : '.')"></p>
                    <div class="overflow-x-auto">
                        <div id="welfare-cert-sheet" class="min-w-[560px]">
                            <article class="cert-paper" :aria-label="cert.title">
                                <div class="cert-frame">
                                    <div class="cert-head">
                                        <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Seal of the Municipality of Esperanza" class="cert-seal">
                                        <div class="cert-lgu">
                                            <p>Republic of the Philippines</p>
                                            <p>Province of Masbate</p>
                                            <strong>MUNICIPALITY OF ESPERANZA</strong>
                                            <p x-text="cert.office"></p>
                                        </div>
                                    </div>
                                    <h2 class="cert-title" x-text="cert.title"></h2>
                                    <p class="cert-subtitle" x-show="cert.subtitle" x-text="cert.subtitle"></p>
                                    <p class="cert-body"><template x-for="(part, partIndex) in cert.parts" :key="partIndex"><span><strong x-show="part.strong" x-text="part.text"></strong><span x-show="!part.strong" x-text="part.text"></span></span></template></p>
                                    <p class="cert-given" x-show="cert.given" x-text="'Given this ' + cert.given + ' at the Municipality of Esperanza, Masbate, Philippines.'"></p>
                                    <div class="cert-signs">
                                        <template x-for="(s, signIndex) in cert.signatories" :key="signIndex">
                                            <div class="cert-sign">
                                                <p class="cert-sign-name" x-text="s.name"></p>
                                                <p class="cert-sign-pos" x-text="s.position"></p>
                                                <p class="cert-sign-acc" x-show="s.accreditation_no" x-text="'DSWD Accreditation No. ' + s.accreditation_no + (s.accreditation_valid_until ? ' (valid until ' + s.accreditation_valid_until + ')' : '')"></p>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="cert-foot">
                                        <div>
                                            <p>Control No. <strong x-text="cert.control"></strong></p>
                                            <p x-show="cert.issuedLong" x-text="'Issued ' + cert.issuedLong"></p>
                                            <p x-show="cert.code" x-text="'Verification code ' + cert.code"></p>
                                            <p x-show="cert.verifyHost" x-text="'Scan the QR code or visit ' + cert.verifyHost + ' to check this certificate.'"></p>
                                        </div>
                                        <div class="cert-qr" x-html="certQr" role="img" aria-label="QR code to verify this certificate"></div>
                                    </div>
                                </div>
                                <div class="cert-void" x-show="cert.revoked" aria-hidden="true">REVOKED</div>
                            </article>
                        </div>
                    </div>
                </div>
            </template>
            <x-slot:footer>
                <x-ui.button size="sm" variant="secondary" @click="certOpen = false"><span x-text="certT('Close', 'Isara')"></span></x-ui.button>
                <x-ui.button size="sm" variant="secondary" icon="printer" @click="printCertificate()"><span x-text="certT('Print', 'I-print')"></span></x-ui.button>
                <x-ui.button size="sm" icon="download" loading="certBusy" @click="downloadCertificate()"><span x-text="certT('Download PDF', 'I-download ang PDF')"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
