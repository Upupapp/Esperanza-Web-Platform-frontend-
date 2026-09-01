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
            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" data-default-src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipality of Esperanza Seal" onerror="this.style.display='none'" class="header-seal header-seal--right absolute inset-0 w-full h-full rounded-full object-contain bg-white border border-slate-200 p-0.5">
        </div>
    </div>

    <div class="relative border-t border-slate-300 pt-4 text-center mb-5">
        <h3 class="text-lg font-bold text-navy-900 uppercase tracking-wide underline decoration-2 underline-offset-4" x-text="{{ $title }}"></h3>
        <p class="text-xs font-medium text-slate-500 mt-1" x-show="{{ $subtitle }}" x-text="{{ $subtitle }}"></p>
    </div>

    <div class="relative text-[13px] leading-relaxed text-slate-800 space-y-3">
        <p class="font-semibold">TO WHOM IT MAY CONCERN:</p>

        <!-- Registration of Death -->
        <template x-if="{{ $docKey }} === 'brgy_cert_death_registration'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this barangay.</p>
                <p>This further certifies for the other information of this person to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Date of Death:</span><x-citizen.doc-field :bind="$bind" field="date_of_death" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Place of Death:</span><x-citizen.doc-field :bind="$bind" field="place_of_death" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Religion:</span><x-citizen.doc-field :bind="$bind" field="religion" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>Issued upon request of the interested party for the <span class="font-semibold">REGISTRATION of Death</span> and for whatever legal purposes this will serve him/her best.</p>
            </div>
        </template>

        <!-- Late Registration -->
        <template x-if="{{ $docKey }} === 'brgy_cert_late_registration'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this barangay.</p>
                <p>This further certifies for the other information of this person to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Date of Birth:</span><x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Place of Birth:</span><x-citizen.doc-field :bind="$bind" field="place_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Occupation:</span><x-citizen.doc-field :bind="$bind" field="occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="col-span-2 flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Husband/Wife (if married):</span><x-citizen.doc-field :bind="$bind" field="spouse_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                </div>
                <p>Issued upon request of the herein interested party for the <span class="font-semibold">LATE REGISTRATION of Birth</span> at the Municipal Registry Office of Esperanza, Masbate.</p>
                <div class="flex items-center gap-6 pt-2 text-[10px] text-slate-400">
                    <span class="border border-slate-300 rounded px-4 py-3">Right Thumbmark</span>
                    <span class="border border-slate-300 rounded px-4 py-3">Left Thumbmark</span>
                    <span class="border border-slate-300 rounded px-6 py-3">Picture</span>
                </div>
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
                <p>This is Non-Transferrable, and shall be null and void, upon failure of the aforementioned applicant to strictly comply with the conditions of this clearance.</p>
                <p>This clearance is being issued upon request of the interested party for identification and whatever legal purposes it may serve.</p>
            </div>
        </template>

        <!-- Residency -->
        <template x-if="{{ $docKey }} === 'brgy_residency'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> of legal age, is a
                    <x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> bonafide resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p>He/She has no pending case/complaint against him/her as per record of this office. He/She has no derogatory record file in this office.</p>
                <p>This certification is being issued by this office upon the request of aforementioned name for:</p>
                <x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" />
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
                <p>This certifies that the holder/bearer was informed of their rights, including the duties and responsibilities accorded by RA 11261, through the Oath of Understanding signed and executed in the presence of Barangay Officials.</p>
            </div>
        </template>

        <!-- Indigency -->
        <template x-if="{{ $docKey }} === 'brgy_indigency'">
            <div class="space-y-2.5">
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, is a
                    <x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, for:
                </p>
                <x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" />
                <p>The way of life of the family is a hand-to-mouth existence, meaning they belong to below poverty line. Further certified that he/she has no pending case/complaint, no derogatory record on file against him/her as per record of this office.</p>
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
                    <x-citizen.doc-field :bind="$bind" field="residency_type" fallback="permanent" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> resident of Purok
                    <x-citizen.doc-field :bind="$bind" field="purok" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate.
                </p>
                <p>He/She has no pending case/complaint against his/her as per record of this office. He/She has no derogatory record file in this office.</p>
                <p>This certification is being issued by this office upon the request of the aforementioned name for:</p>
                <x-citizen.doc-field :bind="$bind" field="purpose" transform="array" fallback="_______________________" tag="p" class="pl-2 text-xs font-semibold underline decoration-dotted underline-offset-2 inline-block" :editing="$editing" />
            </div>
        </template>

        <!-- National ID / PSA Purpose -->
        <template x-if="{{ $docKey }} === 'brgy_cert_national_id'">
            <div class="space-y-2.5">
                <p>This is to certify that <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, is a bonafide resident of this Barangay.</p>
                <p>This further certifies for the other information of the above-named person, to wit:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pl-2">
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Father:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
                    <p class="flex items-baseline gap-1.5"><span class="text-slate-500 shrink-0">Name of Mother (Maiden):</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></p>
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
                <p class="text-[10px] text-slate-400 italic">This License, while in force, shall be posted in a conspicuous place in the business premises.</p>
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

        <p class="text-[11px] text-slate-400 pt-1" x-show="{{ $bind }}.__validity" x-text="{{ $bind }}.__validity"></p>

        {{-- Issuance-date sentence — every one of the "TO WHOM IT MAY
             CONCERN" reference forms (Clearance, Residency, Indigency,
             Business Clearance, First Time Jobseeker) closes the
             certification paragraph with this line immediately before the
             signature block; it was missing from this component entirely.
             Wording differs per doc: Clearance/Residency say "Given today",
             Business Clearance/Indigency/Jobseeker say "Issued this" — both
             verbatim from their own reference docx. Dated to today, since
             this is the moment the document is actually being generated. --}}
        <div x-data="{
            get issuanceDate() {
                const d = new Date();
                const day = d.getDate();
                const suffix = (day >= 11 && day <= 13) ? 'th' : (['th', 'st', 'nd', 'rd'][day % 10] || 'th');
                return day + suffix + ' day of ' + d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            },
        }">
            <p class="pt-1" x-show="['brgy_clearance', 'brgy_residency'].includes({{ $docKey }})">
                Given today <span x-text="issuanceDate"></span> at Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, Philippines.
            </p>
            <p class="pt-1" x-show="['brgy_business_clearance', 'brgy_indigency', 'brgy_first_time_jobseeker'].includes({{ $docKey }})">
                Issued this <span x-text="issuanceDate"></span> at Barangay <span x-text="{{ $barangay }}"></span>, Esperanza, Masbate, Philippines.
            </p>
        </div>
    </div>

    <!-- Signatures — mt-auto (the outer container is now flex flex-col)
         pushes this whole block, and the "Not Valid without Barangay Seal"
         footer below it, down to the bottom of the A4-proportioned page
         regardless of how short the certification text above it is. -->
    <div class="relative flex items-end justify-between mt-auto pt-4">
        <div class="text-center">
            <div class="w-40 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] text-slate-500">Signature over Printed Name of the Requester</p>
        </div>
        <div class="text-center">
            <p class="text-sm font-bold text-navy-900 uppercase" x-text="({{ $punongBarangay }}) || 'Hon. Punong Barangay'"></p>
            <p class="text-[10px] text-slate-500">Punong Barangay</p>
        </div>
    </div>
    <div class="relative mt-4 text-[11px] text-slate-500" x-show="{{ $barangaySecretary }}">
        Released by: <span class="font-semibold text-slate-700" x-text="{{ $barangaySecretary }}"></span> — Barangay Secretary
    </div>

    <p class="relative text-center text-[10px] italic text-slate-400 mt-5">Not Valid without Barangay Seal</p>
    </div>
</div>
