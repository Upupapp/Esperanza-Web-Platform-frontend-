{{-- LANGUAGE (TAB 09): this renders an OFFICIAL DOCUMENT, and its wording is
     the artifact itself, not UI chrome. Philippine LGU certificates and
     clearances are issued in English and their phrasing is fixed by the
     issuing office, so the document body is deliberately NOT translated --
     a bilingual certificate would not match the paper it stands in for.
     Any surrounding CONTROLS (editor toolbar, zoom, panels) are bilingual;
     see document-editor-toolbar.blade.php. --}}
{{-- Standardized barangay certificate preview — letterhead (barangay seal +
     municipal seal) plus the legal template for whichever document is
     selected, with blanks live-bound to the citizen's typed form values.
     Every prop here is an Alpine expression string (not a PHP value) — the
     selected document AND its originating barangay both change at runtime
     as more barangays get onboarded, so nothing here can be server-rendered
     once.

     Editable content: every document-specific value below (`{{ $bind }}.*`)
     is rendered via <x-citizen.doc-field>, which shows a plain span while
     viewing and swaps to a real text input while `editing` (see
     document-requests.blade.php's editingDocument state) is true — both
     read from/write to the exact same snapshot path, so there is nothing
     separate to keep in sync. `barangay`, `punongBarangay` and
     `barangaySecretary` stay read-only here: they come from the shared
     request row (`selected.*`), not the per-document snapshot, and editing
     them in place would leak into other parts of the admin UI that read the
     same object (the request list, citizen info panel, etc). --}}
@props([
    'docKey', 'bind', 'title', 'subtitle', 'barangay', 'seal', 'punongBarangay', 'barangaySecretary', 'editing' => 'false',
    // The request's issued documents (control number, code, QR) and the
    // template's note, printed at the foot (scope round 2026-10-15).
    'verify' => 'null',
    // Alpine expression evaluating to a {src, opacity, size, position,
    // paperColor, decorative} object — see document-watermark.blade.php's
    // header comment for why this exists. Same object gets forwarded to
    // the watermark component below (it needs src/opacity/size/position;
    // this element only reads paperColor/decorative), so the default here
    // has to carry every field either one needs — it reproduces this
    // element's previous hardcoded white-paper, 5%-Esperanza-mark look
    // exactly, so a caller that doesn't pass this (the citizen portal)
    // renders unchanged.
    'background' => "{ src: '" . asset('images/esperanza/esperanza-seal.png') . "', opacity: 5, size: 'large', position: 'center', paperColor: '#FFFFFF', decorative: null }",
])

{{-- A4 page proportions (210mm × 297mm) for the on-screen preview, the
     printed page, and the downloaded PDF alike. Width caps at 210mm and
     shrinks on narrow viewports via min(); min-height uses a container
     query (141.4cqw = 297/210 of whatever width actually rendered) so the
     page keeps true A4 proportions at every size instead of shrinking to
     however tall a one-paragraph certificate's own text happens to be —
     a short certificate is a REAL, mostly-blank A4 sheet with its
     signature anchored near the bottom, not a text-height box, so flex
     pushes the signature block down to occupy that space the way an
     actual printed one does (see the Signatures section below). Content
     that genuinely needs more than one page's worth of space still grows
     past 297mm rather than being clipped.

     bg-white is gone from the static class list — background-color now
     always comes from paperStyle() below (defaulting to the exact same
     #FFFFFF), so a selected graphic template's paper color/decorative
     corner accents show here too, not only after the first Edit Document
     click (see document-watermark.blade.php's comment for the full
     "two divergent render branches" story this closes). --}}
<div
    class="doc-paper border border-slate-200 shadow-sm relative overflow-hidden mx-auto flex flex-col p-6 sm:p-10"
    :style="window.DokyuEditor.paperStyle({!! $background !!})"
    :class="({!! $background !!}).decorative ? 'doc-paper--tpl-' + ({!! $background !!}).decorative : ''"
>

    <x-citizen.document-template-artwork :background="$background" />
    <x-citizen.document-watermark :background="$background" />

    {{-- Everything except the watermark lives inside .doc-content — this is
         the exact subtree the admin's "Edit Document" rich-text editor
         captures (see resources/js/dokyu-editor.js captureContent()) the
         first time editing starts on a request, so the watermark can never
         be dragged/deleted by a contenteditable text edit (it's a sibling,
         not a descendant) and stays under its own separate "Change
         Background" controls. flex flex-col flex-1 (rather than leaving
         .doc-paper's own flex context to reach these children directly)
         keeps the exact same "signatures pushed to the bottom of a full
         A4 page via mt-auto" behavior now that there's one more level of
         nesting; `relative` keeps this whole block painting above the
         position:absolute watermark despite both being positioned. --}}
    <div class="doc-content relative flex flex-col flex-1">

    <!-- Letterhead -->
    <div class="relative flex items-center justify-between gap-3 mb-4">
        <div class="header-seal-wrap header-seal-wrap--left relative w-16 h-16 shrink-0">
            <div class="absolute inset-0 rounded-full bg-slate-50 border-2 border-gold-400 flex items-center justify-center text-slate-300">
                <i data-lucide="landmark" class="w-6 h-6"></i>
            </div>
            <img
                :src="{{ $seal }} ? '{{ rtrim(asset(''), '/') }}/' + {{ $seal }} : '{{ asset('images/esperanza/esperanza-seal.png') }}'"
                :data-default-src="{{ $seal }} ? '{{ rtrim(asset(''), '/') }}/' + {{ $seal }} : '{{ asset('images/esperanza/esperanza-seal.png') }}'"
                alt="Official Seal"
                class="header-seal header-seal--left absolute inset-0 w-full h-full rounded-full object-contain bg-white border border-slate-200 p-0.5"
            >
        </div>
        <div class="text-center flex-1 min-w-0">
            <p class="text-[11px] text-slate-500">Republic of the Philippines</p>
            <p class="text-xs font-semibold text-slate-700">PROVINCE OF MASBATE</p>
            <p class="text-xs text-slate-600">Municipality of Esperanza</p>
            <p class="text-sm font-bold text-navy-900 uppercase tracking-wide">Barangay <span x-text="{{ $barangay }}"></span></p>
        </div>
        <div class="header-seal-wrap header-seal-wrap--right relative w-16 h-16 shrink-0">
            <div class="absolute inset-0 rounded-full bg-slate-50 border-2 border-gold-400 flex items-center justify-center text-slate-300">
                <i data-lucide="shield" class="w-6 h-6"></i>
            </div>
            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" data-default-src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipality of Esperanza Seal" x-on:error="$el.style.display='none'" class="header-seal header-seal--right absolute inset-0 w-full h-full rounded-full object-contain bg-white border border-slate-200 p-0.5">
        </div>
    </div>

    <div class="relative border-t border-slate-300 pt-4 text-center mb-5">
        <h3 class="text-lg font-bold text-navy-900 uppercase tracking-wide underline decoration-2 underline-offset-4" x-text="$store.docTemplates.title({{ $docKey }}, {{ $title }})"></h3>
        <p class="text-xs font-medium text-slate-500 mt-1" x-show="$store.docTemplates.subtitle({{ $docKey }}, {{ $subtitle }})" x-text="$store.docTemplates.subtitle({{ $docKey }}, {{ $subtitle }})"></p>
    </div>

    {{-- The paper's ways (Barangay Labangtaytay's seven templates, LABANGTATAY
         BRGY TEMPLATES, 2026-11-03): the date it was issued (the issued copy's
         own date, else today) written "3rd day of November, 2026"; the
         residency and purposes printed as the paper's tick list, "( / )" on
         the resident's choices; and each document's own signature block. --}}
    <div class="relative text-[13px] leading-relaxed text-slate-800 space-y-3 flex flex-col flex-1" x-data="{
        get issuedOn() {
            const v = {{ $verify }};
            const list = Array.isArray(v) ? v : (v ? [v] : []);
            const doc = [...list].reverse().find((d) => d && !d.revoked && d.issued_on);
            return doc ? new Date(doc.issued_on + 'T00:00:00') : new Date();
        },
        get issuanceDate() {
            const d = this.issuedOn;
            const day = d.getDate();
            const suffix = (day >= 11 && day <= 13) ? 'th' : (['th', 'st', 'nd', 'rd'][day % 10] || 'th');
            return day + suffix + ' day of ' + d.toLocaleDateString('en-US', { month: 'long' }) + ', ' + d.getFullYear();
        },
        norm(v) { return String(v || '').toLowerCase().replace(/^others:\s*/, '').replace(/[^a-z0-9]/g, ''); },
        same(a, b) { const x = this.norm(a), y = this.norm(b); return x !== '' && (x === y || x + 's' === y || x === y + 's' || (x.length > 7 && y.includes(x))); },
        chosen(field) { const v = ({{ $bind }} || {})[field]; return Array.isArray(v) ? v : (v ? String(v).split(/,\s*/) : []); },
        tick(field, opt) { return this.chosen(field).some((c) => this.same(c, opt)) ? '( / )' : '(\u00a0\u00a0)'; },
        others(field, options) {
            return this.chosen(field).filter((c) => !options.some((o) => this.same(c, o))).map((c) => String(c).replace(/^others:\s*/i, '')).filter((c) => !/^others$/i.test(c)).join(', ');
        },
        pb(withHon) {
            const n = String(({{ $punongBarangay }}) || '').trim();
            if (!n) return 'Hon. Punong Barangay';
            return withHon ? (/^hon\.?\s/i.test(n) ? n : 'Hon. ' + n) : n.replace(/^hon\.?\s+/i, '');
        },
        residencyTypes: ['permanent', 'temporary', 'renter'],
        purposes: {
            brgy_residency: ['Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal I.D', 'Bank Requirement', 'NBI Clearance', 'Loan Purpose', 'Medical/Financial Assistance'],
            brgy_clearance: ['Proof of Residency', 'Local Employment', 'Travel Abroad', 'Postal I.D.', 'Bank Requirement', 'NBI Clearance', 'Loan Purposes', 'Medical/Financial Assistance'],
            brgy_indigency: ['Medical Assistance/Financial Assistance', 'Burial Assistance', 'Educational Assistance'],
        },
    }">
        <p class="font-semibold">TO WHOM IT MAY CONCERN:</p>

        <!-- Registration of Death -->
        <template x-if="{{ $docKey }} === 'brgy_cert_death_registration'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this barangay.</p>
                <p>This further certifies for the other information of this person to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden Name):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Date of Death:</span><x-citizen.doc-field :bind="$bind" field="date_of_death" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Place of Death:</span><x-citizen.doc-field :bind="$bind" field="place_of_death" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Religion:</span><x-citizen.doc-field :bind="$bind" field="religion" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>Issued this <span x-text="issuanceDate"></span>, upon request of the interested party for the <span class="font-semibold">REGISTRATION of Death</span> and for whatever legal purposes this will serve him/her best.</p>
            </div>
        </template>

        <!-- Late Registration -->
        <template x-if="{{ $docKey }} === 'brgy_cert_late_registration'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this barangay.</p>
                <p>This further certifies for the other information of this person to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden Name):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Date of Birth:</span><x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Place of Birth:</span><x-citizen.doc-field :bind="$bind" field="place_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Occupation:</span><x-citizen.doc-field :bind="$bind" field="occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="col-span-2 flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Husband/Wife (if married):</span><x-citizen.doc-field :bind="$bind" field="spouse_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>Issued this <span x-text="issuanceDate"></span> upon request of the herein interested party for the <span class="font-semibold">LATE REGISTRATION of his/her birth</span> at the Municipal Registry Office of Esperanza, Masbate.</p>
            </div>
        </template>

        <!-- Business Clearance -->
        <template x-if="{{ $docKey }} === 'brgy_business_clearance'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="applicant_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, having engaged in a business of
                    <x-citizen.doc-field :bind="$bind" field="business_type" fallback="_______________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, for almost
                    <x-citizen.doc-field :bind="$bind" field="years_operating" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years located at Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate
                    with the capital of ( <x-citizen.doc-field :bind="$bind" field="capital_figures" fallback="_______" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> )
                    ( <x-citizen.doc-field :bind="$bind" field="capital_words" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> ).
                </p>
                <p>This is non-transferable and shall be null and void upon failure of the aforementioned applicant to strictly comply with the conditions of this clearance.</p>
                <p>This clearance is being issued upon request of the interested party for identification and whatever legal purposes it may serve.</p>
            </div>
        </template>

        <!-- Residency -->
        <template x-if="{{ $docKey }} === 'brgy_residency'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, is a
                    <template x-if="!{{ $editing }}"><span><template x-for="t in residencyTypes" :key="t"><span><span x-text="tick('residency_type', t)"></span> <span x-text="t"></span><span x-text="t === residencyTypes.at(-1) ? '' : ', '"></span></span></template></span></template>
                    <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /></template> a bonafide resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p>He/She has no pending case/complaint against him/her as per record of this office. He/She has no derogatory record on file in this office.</p>
                <p>This certification is being issued by this office upon the request of the aforementioned name for:</p>
                <template x-if="!{{ $editing }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-0.5 pl-2 text-xs">
                        <template x-for="o in purposes[{{ $docKey }}]" :key="o"><p><span x-text="tick('purpose', o)"></span> <span x-text="o"></span></p></template>
                        <p class="sm:col-span-3"><span x-text="others('purpose', purposes[{{ $docKey }}]) ? '( / )' : '(\u00a0\u00a0)'"></span> Others: <span class="font-semibold underline decoration-dotted underline-offset-2" x-text="others('purpose', purposes[{{ $docKey }}]) || '_______________________'"></span></p>
                    </div>
                </template>
                <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" /></template>
            </div>
        </template>

        <!-- First Time Jobseeker -->
        <template x-if="{{ $docKey }} === 'brgy_first_time_jobseeker'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    a bonafide resident of Purok <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, is a qualified availee of
                    <span class="font-semibold">RA 11261</span> or the First Time Jobseekers Assistance Act of 2019.
                </p>
                <p>I further certify that the holder/bearer was informed of his/her rights, including the duties and responsibilities accorded by RA 11261, through the Oath of Undertaking he/she has signed and executed in the presence of Barangay Officials.</p>
                <p>Signed this <span x-text="issuanceDate"></span> at Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, Philippines.</p>
                <p>This certification is valid for one (1) year from the date of issuance.</p>
            </div>
        </template>

        <!-- Indigency -->
        <template x-if="{{ $docKey }} === 'brgy_indigency'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, is a
                    <template x-if="!{{ $editing }}"><span><template x-for="t in residencyTypes" :key="t"><span><span x-text="tick('residency_type', t)"></span> <span x-text="t"></span><span x-text="t === residencyTypes.at(-1) ? '' : ', '"></span></span></template></span></template>
                    <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /></template> a resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, for:
                </p>
                <template x-if="!{{ $editing }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-0.5 pl-2 text-xs">
                        <template x-for="o in purposes[{{ $docKey }}]" :key="o"><p><span x-text="tick('purpose', o)"></span> <span x-text="o"></span></p></template>
                        <p class="sm:col-span-3"><span x-text="others('purpose', purposes[{{ $docKey }}]) ? '( / )' : '(\u00a0\u00a0)'"></span> Others: <span class="font-semibold underline decoration-dotted underline-offset-2" x-text="others('purpose', purposes[{{ $docKey }}]) || '_______________________'"></span></p>
                    </div>
                </template>
                <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" /></template>
                <p>The way of life of his/her family is a hand-to-mouth existence, meaning they belong below the poverty line. Further certified that he/she has no pending case/complaint, no derogatory record on file against him/her as per record of this office.</p>
            </div>
        </template>

        <!-- Barangay Clearance — wording matches Reference_forms/Esperanza File/
             BRGY.CLEARANCE NEW .docx verbatim (the current form; an older
             Barangay Villa sample in the same reference set uses different
             "good moral character / moral turpitude" legacy phrasing that
             does not appear in the current form and was previously used
             here in error). -->
        <template x-if="{{ $docKey }} === 'brgy_clearance'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that
                    <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, is a
                    <template x-if="!{{ $editing }}"><span><template x-for="t in residencyTypes" :key="t"><span><span x-text="tick('residency_type', t)"></span> <span x-text="t"></span><span x-text="t === residencyTypes.at(-1) ? '' : ', '"></span></span></template></span></template>
                    <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /></template> resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p>He/She has no pending case/complaint against him/her as per record of this office. He/She has no derogatory record on file in this office.</p>
                <p>This certification is being issued by this office upon the request of the aforementioned name for:</p>
                <template x-if="!{{ $editing }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-0.5 pl-2 text-xs">
                        <template x-for="o in purposes[{{ $docKey }}]" :key="o"><p><span x-text="tick('purpose', o)"></span> <span x-text="o"></span></p></template>
                        <p class="sm:col-span-3"><span x-text="others('purpose', purposes[{{ $docKey }}]) ? '( / )' : '(\u00a0\u00a0)'"></span> Others: <span class="font-semibold underline decoration-dotted underline-offset-2" x-text="others('purpose', purposes[{{ $docKey }}]) || '_______________________'"></span></p>
                    </div>
                </template>
                <template x-if="{{ $editing }}"><x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" /></template>
            </div>
        </template>

        <!-- National ID / PSA Purpose -->
        <template x-if="{{ $docKey }} === 'brgy_cert_national_id'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this Barangay.</p>
                <p>This further certifies for the other information of the above-named person, to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden Name):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Date of Birth:</span><x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Place of Birth:</span><x-citizen.doc-field :bind="$bind" field="place_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Occupation:</span><x-citizen.doc-field :bind="$bind" field="occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="col-span-2 flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Husband/Wife:</span><x-citizen.doc-field :bind="$bind" field="spouse_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>Issued upon request of the herein interested party for the <span class="font-semibold">Registration of NATIONAL ID</span> and for whatever legal purposes this will serve him/her best.</p>
            </div>
        </template>

        <!-- Land / Property Certification -->
        <template x-if="{{ $docKey }} === 'brgy_cert_land_property'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="owner_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> is the owner of a lot with
                    <x-citizen.doc-field :bind="$bind" field="lot_area" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> square meters located at
                    <x-citizen.doc-field :bind="$bind" field="lot_location" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p x-show="{{ $bind }}.no_conflict_owner || {{ $editing }}">This certifies further that this lot has no conflict of ownership with <x-citizen.doc-field :bind="$bind" field="no_conflict_owner" fallback="(name)" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />.</p>
                <p>This certification is issued for whatever legal purpose it may serve.</p>
            </div>
        </template>

        <!-- General Purpose Certification -->
        <template x-if="{{ $docKey }} === 'brgy_cert_general'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age,
                    <x-citizen.doc-field :bind="$bind" field="civil_status" fallback="___" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    is a bonafide resident of Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p>This certification is issued upon request of the herein interested party for <x-citizen.doc-field :bind="$bind" field="purpose" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> purposes.</p>
                <p x-show="{{ $bind }}.details || {{ $editing }}"><x-citizen.doc-field :bind="$bind" field="details" fallback="Additional details" :editing="$editing" /></p>
            </div>
        </template>

        <!-- Business Permit -->
        <template x-if="{{ $docKey }} === 'brgy_business_permit'">
            <div class="space-y-2.5">
                <p class="font-semibold uppercase text-center tracking-wide">Barangay Business Permit</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Proprietor:</span><x-citizen.doc-field :bind="$bind" field="proprietor_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Nature of Business:</span><x-citizen.doc-field :bind="$bind" field="business_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Business Location:</span><x-citizen.doc-field :bind="$bind" field="business_location" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Amount Paid:</span><x-citizen.doc-field :bind="$bind" field="amount_paid" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>This Permit is being issued subject to existing rules and regulations, provided however, that the necessary fees are paid to the Treasurer of the Barangay as assessed. This is non-transferable and shall be deemed null and void upon failure by the owner to follow the said rules and regulations set forth by the Local Government Unit of Esperanza, Masbate.</p>
                <p class="text-[10px] text-slate-500 italic">This License, while in force, shall be posted in a conspicuous place in the business premises.</p>
            </div>
        </template>

        <!-- Event / Special Permit -->
        <template x-if="{{ $docKey }} === 'brgy_event_permit'">
            <div class="space-y-2.5">
                <p>
                    The <x-citizen.doc-field :bind="$bind" field="organizer_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, of Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate is requesting permission to hold a
                    <x-citizen.doc-field :bind="$bind" field="event_type" fallback="_______________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> on
                    <x-citizen.doc-field :bind="$bind" field="event_date" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, from
                    <x-citizen.doc-field :bind="$bind" field="event_time_from" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> to
                    <x-citizen.doc-field :bind="$bind" field="event_time_to" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, at
                    <x-citizen.doc-field :bind="$bind" field="venue" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />.
                </p>
                <p>This permit is granted subject to peace and order guidelines of the barangay and may be revoked at any time for cause.</p>
            </div>
        </template>

        {{-- Issuance-date sentence: Clearance and Residency say "Given today",
             Business Clearance and Indigency "Issued this", each verbatim from
             its paper (Death, Late Registration and the Jobseeker certificate
             carry theirs in the body above). The date is the issued copy's own,
             so a copy reopened later keeps it; before issue, today. --}}
        <p class="pt-1" x-show="['brgy_clearance', 'brgy_residency'].includes({{ $docKey }})">
            Given today <span x-text="issuanceDate"></span> at Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, Philippines.
        </p>
        <p class="pt-1" x-show="['brgy_business_clearance', 'brgy_indigency'].includes({{ $docKey }})">
            Issued this <span x-text="issuanceDate"></span> at Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, Philippines.
        </p>

    <!-- Signatures — mt-auto pushes this block, and the "Not Valid without
         Barangay Seal" footer below it, down to the bottom of the A4 page
         however short the text above is. Each of Labangtaytay's papers signs
         its own way (2026-11-03): who signs, the resident's line and boxes,
         "Certified by:", "Released by" and the validity line. -->
    <div class="relative mt-auto pt-6 text-[12px] text-slate-800">
        {{-- Late Registration: thumbmarks and picture, taken at the barangay hall --}}
        <div x-show="{{ $docKey }} === 'brgy_cert_late_registration'" class="flex items-end gap-4 mb-4 text-[10px] text-slate-500">
            <div class="border border-slate-500 w-[37mm] h-[26mm] flex items-end justify-center pb-1">Right Thumbmark</div>
            <div class="border border-slate-500 w-[39mm] h-[26mm] flex items-end justify-center pb-1">Left Thumbmark</div>
            <div class="border border-slate-500 w-[30mm] h-[26mm] flex items-end justify-center pb-1">Picture</div>
        </div>

        <div class="flex items-end justify-between gap-6">
            {{-- The requester's line, as each paper words it --}}
            <div class="text-center" x-show="!['brgy_residency', 'brgy_indigency', 'brgy_first_time_jobseeker'].includes({{ $docKey }})">
                <div class="flex items-end gap-3">
                    <div>
                        <div class="w-44 border-b border-slate-500 mb-1"></div>
                        <p class="text-[10px] text-slate-500" x-text="['brgy_clearance', 'brgy_business_clearance'].includes({{ $docKey }}) ? 'Resident Signature' : 'Signature over Printed Name of the requester'"></p>
                    </div>
                    <div x-show="['brgy_clearance', 'brgy_business_clearance'].includes({{ $docKey }})" class="border border-slate-500 w-[40mm] h-[22mm] flex items-end justify-center pb-1 text-[10px] text-slate-500">Right Thumbmark</div>
                </div>
            </div>
            <div class="ml-auto text-center">
                <p class="text-[11px] text-left mb-3" x-show="['brgy_cert_late_registration', 'brgy_cert_death_registration'].includes({{ $docKey }})">Certified by:</p>
                <p class="text-sm font-bold text-navy-900 uppercase underline underline-offset-2" x-text="pb({{ $docKey }} === 'brgy_cert_late_registration')"></p>
                <p class="text-[10px] text-slate-500">Punong Barangay</p>
                <template x-if="{{ $docKey }} === 'brgy_first_time_jobseeker'">
                    <div class="mt-6"><div class="w-36 mx-auto border-b border-slate-500 mb-1"></div><p class="text-[10px] text-slate-500">Date</p></div>
                </template>
            </div>
        </div>

        {{-- "Released by" the Barangay Secretary: not on Death, Late Registration or the Jobseeker certificate --}}
        <div class="mt-5" x-show="({{ $barangaySecretary }}) && !['brgy_cert_late_registration', 'brgy_cert_death_registration', 'brgy_first_time_jobseeker'].includes({{ $docKey }})">
            <p class="text-[11px]">Released by: <span class="font-bold uppercase underline underline-offset-2" x-text="{{ $barangaySecretary }}"></span></p>
            <p class="text-[10px] text-slate-500 pl-[4.6rem]">Barangay Secretary</p>
        </div>

        <p class="mt-3 text-[11px] text-slate-600" x-show="{{ $docKey }} === 'brgy_residency'">(Residency certificate valid three (3) months from the date of issuance)</p>
        <p class="mt-3 text-[11px] text-slate-600" x-show="{{ $docKey }} === 'brgy_indigency'">(Indigency certificate valid three (3) months from the date of issuance)</p>
    </div>
    </div>

    <x-documents.verify-strip :doc="$verify" :doc-key="$docKey" />

    <p class="relative text-center text-[10px] italic text-slate-500 mt-5">Not Valid without Barangay Seal</p>
    </div>
</div>
