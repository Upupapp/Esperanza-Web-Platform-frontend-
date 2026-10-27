{{-- LANGUAGE (TAB 09): this renders an OFFICIAL DOCUMENT, and its wording is
     the artifact itself, not UI chrome. Philippine LGU certificates and
     clearances are issued in English and their phrasing is fixed by the
     issuing office, so the document body is deliberately NOT translated --
     a bilingual certificate would not match the paper it stands in for.
     Any surrounding CONTROLS (editor toolbar, zoom, panels) are bilingual;
     see document-editor-toolbar.blade.php. --}}
{{-- Municipal document request-slip preview — this represents the CITIZEN'S
     REQUEST, not the final certificate itself (only the Civil Registrar /
     issuing office can produce that, after verifying it against the actual
     civil registry). Section layout recreates the real PSA/LCRO Municipal
     Form's boxed grid (Child/Mother/Father cells for Form 102, Husband/Wife
     cells for Form 97, bulleted blanks for the Pet Registration Form) with
     the citizen's typed answers sitting directly inside each box/blank —
     the same way the value would sit inside the box if the paper form were
     filled out by hand — instead of an external "label: value" summary.
     `docKey` / `bind` / `title` / `office` / etc. are Alpine expression
     strings since the selected document changes at runtime.

     Editable content: every document-specific value below (`{{ $bind }}.*`)
     is rendered via <x-citizen.doc-field>, matching barangay-certificate-preview.blade.php's
     own approach — a plain span/paragraph while viewing, a real text input
     while `editing` is true, both reading/writing the same snapshot path.
     `office`, `officiant`/`officiantTitle`, `technician`/`technicianTitle`
     and `verifier`/`verifierTitle` stay read-only here since they come from
     the shared request row (`selected.*`), not the per-document snapshot —
     editing them in place would leak into other parts of the admin UI that
     read the same object. --}}
@props([
    // The request's issued documents and the template's note, printed at the
    // foot (scope round 2026-10-15).
    'verify' => 'null',
    'docKey', 'bind', 'title', 'office', 'formNo' => null, 'officiant' => null, 'officiantTitle' => null, 'technician' => null, 'technicianTitle' => null, 'verifier' => null, 'verifierTitle' => null, 'editing' => 'false',
    // See barangay-certificate-preview.blade.php's own header comment on
    // this exact prop — same {src, opacity, size, position, paperColor,
    // decorative} shape, same default, same reasoning.
    'background' => "{ src: '" . asset('images/esperanza/esperanza-seal.png') . "', opacity: 5, size: 'large', position: 'center', paperColor: '#FFFFFF', decorative: null }",
])

{{-- A4 page proportions (210mm × 297mm) — see barangay-certificate-preview.blade.php's
     own header comment for the full reasoning: container-query min-height
     keeps a short request slip a real full-page A4 sheet (signature block
     pushed to the bottom via flex/mt-auto) rather than a text-height box,
     while longer content still grows past 297mm instead of clipping.

     bg-white removed — see barangay-certificate-preview.blade.php's own
     comment on this same change for why (paperStyle() below reproduces it
     by default, but also lets a selected graphic template's paper color/
     decorative accents show here, pre-first-edit, like everywhere else). --}}
<div
    class="doc-paper border-2 border-slate-200 p-6 sm:p-10 shadow-inner-sm relative overflow-hidden mx-auto flex flex-col"
    :style="window.DokyuEditor.paperStyle({!! $background !!})"
    :class="({!! $background !!}).decorative ? 'doc-paper--tpl-' + ({!! $background !!}).decorative : ''"
>
    <x-citizen.document-watermark :background="$background" />

    {{-- Everything except the watermark lives inside .doc-content — see
         barangay-certificate-preview.blade.php's own header comment on
         this same wrapper for the full reasoning (editor capture target,
         watermark stays a sibling so it can never be touched by a
         contenteditable text edit, flex flex-col flex-1 preserves the
         existing mt-auto "push signatures to the bottom of a full A4
         page" behavior one level deeper). --}}
    <div class="doc-content relative flex flex-col flex-1">

    <!-- Letterhead -->
    <div class="relative flex items-center gap-3 mb-4">
        <div class="header-seal-wrap header-seal-wrap--left relative w-14 h-14 shrink-0">
            <div class="absolute inset-0 rounded-full bg-slate-50 border-2 border-gold-400 flex items-center justify-center text-slate-300">
                <i data-lucide="shield" class="w-6 h-6"></i>
            </div>
            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" data-default-src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipality of Esperanza Seal" x-on:error="$el.style.display='none'" class="header-seal header-seal--left absolute inset-0 w-full h-full rounded-full object-cover border-2 border-gold-400">
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[11px] text-slate-500">Republic of the Philippines · Province of Masbate</p>
            <p class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Municipality of Esperanza</p>
            <p class="text-sm font-bold text-navy-900" x-text="{{ $office }}"></p>
        </div>
        <div class="text-right shrink-0">
            {{-- Not on an issued certificate: the Certificate of Indigency is the
                 office's own paper, not a request for one (2026-10-27). --}}
            <span x-show="{{ $docKey }} !== 'dokyu_indigency'" class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-brand-700 bg-brand-50 border border-brand-100 rounded-full px-2.5 py-1">
                <i data-lucide="file-text" class="w-3 h-3"></i> Request Slip
            </span>
            <p class="text-[10px] text-slate-500 mt-1" x-show="{{ $formNo }}" x-text="{{ $formNo }}"></p>
        </div>
    </div>

    <div class="relative text-center mb-5">
        <h3 class="text-base font-bold text-navy-900 uppercase tracking-wide underline decoration-2 underline-offset-4" x-text="$store.docTemplates.title({{ $docKey }}, {{ $title }})"></h3>
    </div>

    <div class="relative text-[13px] text-slate-800 space-y-4">
        <!-- Certificate of Marriage: Husband / Wife boxed grid, matching Municipal Form No. 97 -->
        <template x-if="{{ $docKey }} === 'mcro_marriage'">
            <div class="space-y-3">
                <p class="text-xs"><span class="text-slate-500">Requester:</span> <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="—" class="font-medium" :editing="$editing" /> <span class="text-slate-500">(<x-citizen.doc-field :bind="$bind" field="relationship" fallback="—" :editing="$editing" />)</span></p>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Husband</p>
                            <x-citizen.doc-field :bind="$bind" field="husband_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Wife</p>
                            <x-citizen.doc-field :bind="$bind" field="wife_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Marriage</p>
                            <x-citizen.doc-field :bind="$bind" field="date_of_marriage" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Place of Marriage</p>
                            <x-citizen.doc-field :bind="$bind" field="place_of_marriage" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Registration Status</p>
                        <x-citizen.doc-field :bind="$bind" field="is_delayed" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                    </div>
                </div>
            </div>
        </template>

        <!-- Application for Marriage License: Groom / Bride boxed grid, matching Municipal Form No. 90 -->
        <template x-if="{{ $docKey }} === 'mcro_marriage_license'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Groom</p>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1 border-l border-navy-700">Bride</p>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Full Name</p>
                            <x-citizen.doc-field :bind="$bind" field="groom_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Full Name</p>
                            <x-citizen.doc-field :bind="$bind" field="bride_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Birth</p>
                            <x-citizen.doc-field :bind="$bind" field="groom_dob" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Birth</p>
                            <x-citizen.doc-field :bind="$bind" field="bride_dob" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Citizenship</p>
                            <x-citizen.doc-field :bind="$bind" field="groom_citizenship" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Citizenship</p>
                            <x-citizen.doc-field :bind="$bind" field="bride_citizenship" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Residence</p>
                            <x-citizen.doc-field :bind="$bind" field="groom_residence" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Residence</p>
                            <x-citizen.doc-field :bind="$bind" field="bride_residence" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Civil Status</p>
                            <x-citizen.doc-field :bind="$bind" field="groom_civil_status" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Civil Status</p>
                            <x-citizen.doc-field :bind="$bind" field="bride_civil_status" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <p class="text-xs px-1"><span class="text-slate-500">Consent/Advice Given By:</span> <x-citizen.doc-field :bind="$bind" field="consent_person" fallback="—" class="font-medium" :editing="$editing" /></p>
            </div>
        </template>

        <!-- Certificate of Live Birth: Child / Mother / Father boxed grid, matching Municipal Form No. 102 -->
        <template x-if="{{ $docKey }} === 'mcro_live_birth'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Child</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Name</p>
                        <x-citizen.doc-field :bind="$bind" field="child_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Sex</p>
                            <x-citizen.doc-field :bind="$bind" field="sex" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Birth</p>
                            <x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Place of Birth</p>
                            <x-citizen.doc-field :bind="$bind" field="place_of_birth" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Mother</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Maiden Name</p>
                            <x-citizen.doc-field :bind="$bind" field="mother_maiden_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Citizenship</p>
                            <x-citizen.doc-field :bind="$bind" field="mother_citizenship" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Father</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Full Name</p>
                            <x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Citizenship</p>
                            <x-citizen.doc-field :bind="$bind" field="father_citizenship" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-x-4 text-xs px-1">
                    <p><span class="text-slate-500">Requester:</span> <x-citizen.doc-field :bind="$bind" field="requester_relationship" fallback="—" class="font-medium" :editing="$editing" /></p>
                    <p><span class="text-slate-500">Status:</span> <x-citizen.doc-field :bind="$bind" field="is_delayed" fallback="—" class="font-medium" :editing="$editing" /></p>
                </div>

                <!-- Muslim Attachment (Municipal Form No. 102, attachment) — only shown once any of its fields are filled -->
                <div
                    class="border border-slate-300 rounded-sm overflow-hidden"
                    x-show="{{ $bind }}.ethnicity_father || {{ $bind }}.ethnicity_mother || {{ $bind }}.dob_hijrah || {{ $editing }}"
                    x-cloak
                >
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Muslim Attachment</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Ethnicity of Father</p>
                            <x-citizen.doc-field :bind="$bind" field="ethnicity_father" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Ethnicity of Mother</p>
                            <x-citizen.doc-field :bind="$bind" field="ethnicity_mother" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Birth — Hijrah Calendar</p>
                        <x-citizen.doc-field :bind="$bind" field="dob_hijrah" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                </div>
            </div>
        </template>

        <!-- Certificate of Death: Deceased boxed grid, matching Municipal Form No. 103 -->
        <template x-if="{{ $docKey }} === 'mcro_death'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Deceased</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Name</p>
                        <x-citizen.doc-field :bind="$bind" field="deceased_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Sex</p>
                            <x-citizen.doc-field :bind="$bind" field="sex" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Death</p>
                            <x-citizen.doc-field :bind="$bind" field="date_of_death" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Place of Death</p>
                            <x-citizen.doc-field :bind="$bind" field="place_of_death" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Date of Birth</p>
                            <x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Civil Status</p>
                            <x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Citizenship</p>
                            <x-citizen.doc-field :bind="$bind" field="citizenship" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Residence</p>
                            <x-citizen.doc-field :bind="$bind" field="residence" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Name of Father</p>
                            <x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Mother's Maiden Name</p>
                            <x-citizen.doc-field :bind="$bind" field="mother_maiden_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-x-4 text-xs px-1">
                    <p><span class="text-slate-500">Requester:</span> <x-citizen.doc-field :bind="$bind" field="requester_relationship" fallback="—" class="font-medium" :editing="$editing" /></p>
                    <p><span class="text-slate-500">Status:</span> <x-citizen.doc-field :bind="$bind" field="is_delayed" fallback="—" class="font-medium" :editing="$editing" /></p>
                </div>
            </div>
        </template>

        <!-- Delayed Registration: sworn-affidavit format, matching the real "Affidavit for Delayed Registration" -->
        <template x-if="{{ $docKey }} === 'mcro_delayed_registration'">
            <div class="space-y-3">
                <p class="text-xs text-slate-500">Republic of the Philippines · Municipality of Esperanza</p>
                <p class="leading-relaxed">
                    That I am the applicant for the delayed registration of
                    <x-citizen.doc-field :bind="$bind" field="registration_type" fallback="_______" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> of
                    <x-citizen.doc-field :bind="$bind" field="subject_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    to whom I am the <x-citizen.doc-field :bind="$bind" field="applicant_relationship" fallback="_______" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />.
                </p>
                <p class="leading-relaxed">That the reason for the delay in registering this event was
                    <x-citizen.doc-field :bind="$bind" field="reason_for_delay" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />.
                </p>
                <p class="leading-relaxed">That I am executing this affidavit to attest to the truthfulness of the foregoing statements for all legal intents and purposes.</p>
            </div>
        </template>

        <!-- Pet Registration: bulleted label + blank, matching the real Pet Registration Form layout -->
        <template x-if="{{ $docKey }} === 'municipal_pet_registration'">
            <div class="space-y-4 text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Owner Information</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Full Name:</span><x-citizen.doc-field :bind="$bind" field="owner_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Address:</span><x-citizen.doc-field :bind="$bind" field="address" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Phone Number:</span><x-citizen.doc-field :bind="$bind" field="phone" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Email Address:</span><x-citizen.doc-field :bind="$bind" field="email" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Pet Information</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Pet's Name:</span><x-citizen.doc-field :bind="$bind" field="pet_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Species:</span><x-citizen.doc-field :bind="$bind" field="species" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Breed:</span><x-citizen.doc-field :bind="$bind" field="breed" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Color/Markings:</span><x-citizen.doc-field :bind="$bind" field="color_markings" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Gender:</span><x-citizen.doc-field :bind="$bind" field="gender" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Date of Birth / Age:</span><x-citizen.doc-field :bind="$bind" field="pet_dob" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Microchip Number:</span><x-citizen.doc-field :bind="$bind" field="microchip_number" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Vaccination &amp; Care</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Last Rabies Vaccination:</span><x-citizen.doc-field :bind="$bind" field="last_rabies_vaccination" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Other Vaccinations:</span><x-citizen.doc-field :bind="$bind" field="other_vaccinations" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Spayed / Neutered:</span><x-citizen.doc-field :bind="$bind" field="spay_neuter" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Emergency Contact</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Name:</span><x-citizen.doc-field :bind="$bind" field="emergency_contact_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Phone Number:</span><x-citizen.doc-field :bind="$bind" field="emergency_contact_phone" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
            </div>
        </template>

        <!-- PWD ID (PRPWD Application): sectioned label + blank, matching the DOH PRPWD v3.0 form -->
        <template x-if="{{ $docKey }} === 'msw_pwd_id'">
            <div class="space-y-4 text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Personal Information</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Full Name:</span><x-citizen.doc-field :bind="$bind" field="full_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Date of Birth:</span><x-citizen.doc-field :bind="$bind" field="date_of_birth" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Religion:</span><x-citizen.doc-field :bind="$bind" field="religion" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Blood Type:</span><x-citizen.doc-field :bind="$bind" field="blood_type" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Disability Information</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Type of Disability:</span><x-citizen.doc-field :bind="$bind" field="type_of_disability" transform="array" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Cause of Disability:</span><x-citizen.doc-field :bind="$bind" field="cause_of_disability" transform="array" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Residence &amp; Employment</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Address:</span><x-citizen.doc-field :bind="$bind" field="residence_address" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Educational Attainment:</span><x-citizen.doc-field :bind="$bind" field="educational_attainment" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Employment Status:</span><x-citizen.doc-field :bind="$bind" field="employment_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Occupation:</span><x-citizen.doc-field :bind="$bind" field="occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">ID Reference Numbers</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">SSS No.:</span><x-citizen.doc-field :bind="$bind" field="sss_no" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">GSIS No.:</span><x-citizen.doc-field :bind="$bind" field="gsis_no" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Pag-IBIG No.:</span><x-citizen.doc-field :bind="$bind" field="pagibig_no" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">PhilHealth No.:</span><x-citizen.doc-field :bind="$bind" field="philhealth_no" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Family Background</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Father's Name:</span><x-citizen.doc-field :bind="$bind" field="father_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Mother's Name:</span><x-citizen.doc-field :bind="$bind" field="mother_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Guardian's Name:</span><x-citizen.doc-field :bind="$bind" field="guardian_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
            </div>
        </template>

        <!-- Senior Citizen ID (OSCA Membership): bulleted label + blank, matching the real OSCA Application for Membership -->
        <template x-if="{{ $docKey }} === 'osca_senior_citizen'">
            <div class="space-y-4 text-xs">
                <div>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Name:</span><x-citizen.doc-field :bind="$bind" field="full_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Birthdate:</span><x-citizen.doc-field :bind="$bind" field="birthdate" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Birthplace:</span><x-citizen.doc-field :bind="$bind" field="birthplace" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Address (Brgy./Sitio):</span><x-citizen.doc-field :bind="$bind" field="address_purok" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Sex:</span><x-citizen.doc-field :bind="$bind" field="sex" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Age:</span><x-citizen.doc-field :bind="$bind" field="age" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Marital Status:</span><x-citizen.doc-field :bind="$bind" field="marital_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Educational Attainment:</span><x-citizen.doc-field :bind="$bind" field="educational_attainment" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Present Occupation:</span><x-citizen.doc-field :bind="$bind" field="present_occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Annual Income:</span><x-citizen.doc-field :bind="$bind" field="annual_income" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Pension:</span><x-citizen.doc-field :bind="$bind" field="pension" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">PhilSys ID No.:</span><x-citizen.doc-field :bind="$bind" field="philsys_id" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-navy-900 mb-1.5">Last Government Service</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Office:</span><x-citizen.doc-field :bind="$bind" field="last_govt_office" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Year:</span><x-citizen.doc-field :bind="$bind" field="last_govt_year" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Address:</span><x-citizen.doc-field :bind="$bind" field="last_govt_address" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Position/Designation:</span><x-citizen.doc-field :bind="$bind" field="last_govt_position" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
            </div>
        </template>

        <!-- Cedula (Community Tax Certificate): the layout every Philippine
             city/municipal Treasurer's Office is required to issue under
             RA 7160 Sec. 162 — this is the mandated national format, not
             any other LGU's own branded document. -->
        <template x-if="{{ $docKey }} === 'dokyu_cedula'">
            <div class="space-y-4 text-xs">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Taxpayer Information</p>
                    <ul class="p-3 space-y-1.5">
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Full Name:</span><x-citizen.doc-field :bind="$bind" field="full_name" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Address:</span><x-citizen.doc-field :bind="$bind" field="address" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Citizenship:</span><x-citizen.doc-field :bind="$bind" field="citizenship" fallback="Filipino" class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Civil Status:</span><x-citizen.doc-field :bind="$bind" field="civil_status" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Occupation:</span><x-citizen.doc-field :bind="$bind" field="occupation" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                        <li class="flex items-baseline gap-2"><span class="text-slate-500 shrink-0">Employer / Business:</span><x-citizen.doc-field :bind="$bind" field="employer_or_business" fallback=" " class="flex-1 font-medium border-b border-dotted border-slate-400" :editing="$editing" /></li>
                    </ul>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Basis of Tax</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Gross Annual Income / Receipts</p>
                            <x-citizen.doc-field :bind="$bind" field="annual_income" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Basic Community Tax</p>
                            <p class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]">₱5.00</p>
                        </div>
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden" x-show="{{ $bind }}.or_number || {{ $bind }}.amount_paid || {{ $editing }}" x-cloak>
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Payment Reference</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">O.R. Number</p>
                            <x-citizen.doc-field :bind="$bind" field="or_number" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Amount Paid</p>
                            <x-citizen.doc-field :bind="$bind" field="amount_paid" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Certificate of Residency (Municipal / Civil Registrar) — same
             content shape as the barangay-issued version, MCRO letterhead. -->
        <template x-if="{{ $docKey }} === 'dokyu_residency'">
            <div class="space-y-2.5 leading-relaxed">
                <p class="font-semibold">TO WHOM IT MAY CONCERN:</p>
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age,
                    <x-citizen.doc-field :bind="$bind" field="civil_status" fallback="___" transform="lowercase" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="citizenship" fallback="Filipino" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> citizen, is a bonafide resident of
                    <x-citizen.doc-field :bind="$bind" field="address" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, Municipality of Esperanza, Province of Masbate,
                    for <x-citizen.doc-field :bind="$bind" field="years_of_residency" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years.
                </p>
                <p>This certification is being issued upon request of the above-named person for <x-citizen.doc-field :bind="$bind" field="purpose" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> purposes.</p>
            </div>
        </template>

        <!-- Certificate of Indigency (Municipal / MSWDO) — same content shape
             as the barangay-issued version, MSWDO letterhead. -->
        <template x-if="{{ $docKey }} === 'dokyu_indigency'">
            <div class="space-y-2.5 leading-relaxed">
                <p class="font-semibold">TO WHOM IT MAY CONCERN:</p>
                <p>
                    This is to certify that <x-citizen.doc-field :bind="$bind" field="requester_name" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />,
                    <x-citizen.doc-field :bind="$bind" field="age" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> years of age, residing at
                    <x-citizen.doc-field :bind="$bind" field="address" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, lives in a household of
                    <x-citizen.doc-field :bind="$bind" field="family_size" fallback="___" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> member(s) with a monthly household income of
                    <x-citizen.doc-field :bind="$bind" field="monthly_income" fallback="___" transform="peso" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" />, and belongs to the indigent sector of this municipality.
                </p>
                <p>This certification is being issued upon request of the above-named person for <x-citizen.doc-field :bind="$bind" field="purpose" fallback="_______________________" class="font-semibold underline decoration-dotted underline-offset-2" :editing="$editing" /> purposes.</p>
            </div>
        </template>

        <!-- Business Permit (New / Renewal): follows the DTI/DILG Barangay
             Business One-Stop-Shop "BPLS Unified Application Form" — a
             national standard form (Reference_forms/Placeholders/Dokyu/
             BUSINESS-PERMIT-FORM.pdf), not a specific city's own document. -->
        <template x-if="{{ $docKey }} === 'dokyu_business_new'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Applicant</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Taxpayer Name</p>
                            <x-citizen.doc-field :bind="$bind" field="applicant_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Type of Business</p>
                            <x-citizen.doc-field :bind="$bind" field="business_type" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">DTI / SEC / CDA Registration No.</p>
                        <x-citizen.doc-field :bind="$bind" field="dti_sec_cda_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Business</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Business Name</p>
                        <x-citizen.doc-field :bind="$bind" field="business_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Business Address</p>
                        <x-citizen.doc-field :bind="$bind" field="business_address" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Line of Business</p>
                            <x-citizen.doc-field :bind="$bind" field="line_of_business" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Contact No.</p>
                            <x-citizen.doc-field :bind="$bind" field="contact_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Capital Investment</p>
                            <x-citizen.doc-field :bind="$bind" field="capital_investment" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Total No. of Employees</p>
                            <x-citizen.doc-field :bind="$bind" field="total_employees" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="{{ $docKey }} === 'dokyu_business_renewal'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Applicant / Business</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Taxpayer Name</p>
                        <x-citizen.doc-field :bind="$bind" field="applicant_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Business Name</p>
                        <x-citizen.doc-field :bind="$bind" field="business_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="px-3 py-2">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Business Address</p>
                        <x-citizen.doc-field :bind="$bind" field="business_address" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Renewal Reference</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Previous Permit No.</p>
                            <x-citizen.doc-field :bind="$bind" field="prior_permit_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Official Receipt No.</p>
                            <x-citizen.doc-field :bind="$bind" field="prior_or_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Line of Business</p>
                            <x-citizen.doc-field :bind="$bind" field="line_of_business" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Prior Year Gross Receipts</p>
                            <x-citizen.doc-field :bind="$bind" field="gross_receipts_prior_year" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Real Property Tax Clearance: standard Treasurer's Office format
             (owner / TD / PIN / assessed value, no delinquency statement). -->
        <template x-if="{{ $docKey }} === 'dokyu_rpt'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Property of Record</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Registered Owner</p>
                        <x-citizen.doc-field :bind="$bind" field="owner_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Tax Declaration No.</p>
                            <x-citizen.doc-field :bind="$bind" field="tax_declaration_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Property Index No. (PIN)</p>
                            <x-citizen.doc-field :bind="$bind" field="property_index_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-t border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Lot / Block No.</p>
                            <x-citizen.doc-field :bind="$bind" field="lot_block_no" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Assessed Value</p>
                            <x-citizen.doc-field :bind="$bind" field="assessed_value" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Property Location</p>
                        <x-citizen.doc-field :bind="$bind" field="property_location" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                    </div>
                </div>
                <p class="text-xs leading-relaxed">This is to certify that the real property described above has no delinquent real property tax as of the date of issuance, per records of this office.</p>
            </div>
        </template>

        <!-- Locational Clearance: field structure sourced from Reference_forms/
             Placeholders/Dokyu/CPDO-Application-for-Locational-Clearance.pdf —
             that file is itself another LGU's (Naga City CPDO) own branded
             form, so only its FIELD STRUCTURE is reused here; the letterhead
             above is always Esperanza's own MPDO, never Naga's. -->
        <template x-if="{{ $docKey }} === 'locational_clearance'">
            <div class="space-y-3">
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Applicant</p>
                    <div class="grid grid-cols-2 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Applicant Type</p>
                            <x-citizen.doc-field :bind="$bind" field="applicant_type" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Authorized Representative</p>
                            <x-citizen.doc-field :bind="$bind" field="authorized_rep_name" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Representative's Address</p>
                        <x-citizen.doc-field :bind="$bind" field="authorized_rep_address" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Project Description</p>
                    <div class="px-3 py-2 border-b border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Project Title</p>
                        <x-citizen.doc-field :bind="$bind" field="project_title" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-slate-300 border-b border-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Nature of Application</p>
                            <x-citizen.doc-field :bind="$bind" field="nature_of_application" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Project Location</p>
                            <x-citizen.doc-field :bind="$bind" field="project_location" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Floor Area (sqm)</p>
                            <x-citizen.doc-field :bind="$bind" field="floor_area" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Height (m)</p>
                            <x-citizen.doc-field :bind="$bind" field="building_height" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">No. of Storeys</p>
                            <x-citizen.doc-field :bind="$bind" field="number_of_storeys" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                </div>
                <div class="border border-slate-300 rounded-sm overflow-hidden">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-white bg-navy-800 px-3 py-1">Lot Information</p>
                    <div class="grid grid-cols-3 divide-x divide-slate-300">
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Lot Area (sqm)</p>
                            <x-citizen.doc-field :bind="$bind" field="lot_area" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">TCT / OCT No.</p>
                            <x-citizen.doc-field :bind="$bind" field="title_number" fallback=" " tag="p" class="font-medium border-b border-slate-300 pb-1 min-h-[1.4rem]" :editing="$editing" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Right Over Land</p>
                            <x-citizen.doc-field :bind="$bind" field="right_over_land" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                        </div>
                    </div>
                    <div class="px-3 py-2 border-t border-slate-300">
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">Zoning Classification</p>
                        <x-citizen.doc-field :bind="$bind" field="zoning_classification" transform="array" fallback=" " tag="p" class="font-medium" :editing="$editing" />
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- mt-auto (the outer container is now flex flex-col) pushes this whole
         closing group — Signatures where a named officiant exists, and the
         disclaimer notice always — down to the bottom of the A4-proportioned
         page, the same way barangay-certificate-preview.blade.php does. --}}
    <div class="relative mt-auto">
        <!-- Signatures — real named signatories from the actual municipal forms -->
        <div class="pt-4 border-t border-dashed border-slate-200" x-show="{{ $officiant }} || {{ $technician }}">
            <div class="grid grid-cols-2 gap-4">
                <template x-if="{{ $officiant }}">
                    <div class="text-center">
                        <div class="w-full border-b border-slate-400 mb-1 h-6"></div>
                        <p class="text-xs font-bold text-navy-900 uppercase" x-text="{{ $officiant }}"></p>
                        <p class="text-[10px] text-slate-500" x-text="{{ $officiantTitle }}"></p>
                    </div>
                </template>
                <template x-if="{{ $technician }}">
                    <div class="text-center">
                        <p class="text-xs font-bold text-navy-900 uppercase" x-text="{{ $technician }}"></p>
                        <p class="text-[10px] text-slate-500" x-text="{{ $technicianTitle }}"></p>
                    </div>
                </template>
                <template x-if="{{ $verifier }}">
                    <div class="text-center">
                        <p class="text-xs font-bold text-navy-900 uppercase" x-text="{{ $verifier }}"></p>
                        <p class="text-[10px] text-slate-500" x-text="{{ $verifierTitle }}"></p>
                    </div>
                </template>
            </div>
        </div>

        <x-documents.verify-strip :doc="$verify" :doc-key="$docKey" />

        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-100 px-3.5 py-2.5 mt-5">
            <i data-lucide="info" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
            <p class="text-[11px] text-slate-500">This is a summary of your request, not the final certificate. The <span x-text="{{ $office }}"></span> will prepare and release the official document upon verification against the civil registry.</p>
        </div>
    </div>
    </div>
</div>
