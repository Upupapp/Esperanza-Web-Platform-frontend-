{{--
    Dokyu > Process > View document: the Prepared Document window.
    Kept out of the Dokyu page's HTML (page budget, 2026-11-25): it was a fifth
    of the page. <x-lazy-partial> in admin/document-requests puts it back in
    place, under the page's x-data, so every name below is the page's.
--}}
{{-- Document Preview: reuses the exact same letterhead components the
     citizen wizard uses for its own live pre-submission preview,
     bound here to the request's frozen formSnapshot so staff see
     precisely what the citizen will receive. Same #dp-print-area /
     dp-printing print isolation as the citizen portal (shared rules
     in app.css). --}}
<div
    x-data="{ get open() { return docPreviewOpen }, set open(v) { if (!v && editingDocument) cancelDocumentEdits(); docPreviewOpen = v; if (!v) { document.body.classList.remove('dp-printing'); editingDocument = false; documentEditBackup = null; backgroundEditBackup = null; templateKeyEditBackup = null; resetEditorUiState(); } else { zoomLevel = 100; $nextTick(() => recomputeZoomSizer()); } } }"
    @keydown.window="handleZoomShortcut($event); handleUndoRedoShortcut($event)"
    @keydown.escape.window="document.body.classList.remove('dp-printing')"
>
    <x-ui.modal title="Prepared Document" maxWidth="3xl" :fullscreen="true" :start-compact="true">
        <template x-if="selected && documentReady">
            <div>
                <div class="flex items-center justify-between mb-3 dp-no-print" x-show="!editingDocument">
                    <p class="text-[11px] text-ink-muted leading-relaxed">Review the prepared document below, or edit its wording and formatting before printing or downloading.</p>
                    <x-ui.button size="sm" variant="secondary" icon="pencil" x-show="mayEditDocument" @click="startEditingDocument()">Edit document</x-ui.button>
                </div>

                <x-citizen.document-editor-zoom-controls />
                <x-citizen.document-editor-toolbar />
                <x-citizen.document-editor-background-panel />
                <x-citizen.document-editor-spacing-panel />
                <x-citizen.document-editor-header-seal-panel />

                {{-- Template sidebar sits beside the paper only —
                     the toolbar/floating panels above stay full-
                     width, matching the requested layout (sidebar
                     + paper in the middle, toolbar spanning the
                     top, inspectors floating independently). --}}
                <div class="flex gap-4 items-start">
                    <x-citizen.document-editor-template-sidebar />
                    <div class="flex-1 min-w-0">
                <div id="dp-print-area">
                    <div class="flex items-center justify-between mb-4 text-[11px] text-ink-muted dp-no-print">
                        <span x-text="selected.ref"></span>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-medium ring-1 ring-inset"
                              :class="selected.status === 'Released' ? 'bg-cyan-50 text-cyan-700 ring-cyan-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                              x-text="selected.status"></span>
                    </div>

                    {{-- Zoom is purely a view affordance — it wraps
                         both rendering branches below without
                         changing either. doc-zoom-viewport is the
                         scrollable window; doc-zoom-sizer's explicit
                         width/height (recomputeZoomSizer() in the
                         x-data above) reserves exactly the scaled
                         paper's footprint so the viewport actually
                         has something to scroll; doc-zoom-scale
                         carries the transform itself. All three are
                         neutralized during print/PDF (see app.css)
                         so the exported page is always true,
                         unscaled A4. --}}
                    <div class="doc-zoom-viewport max-h-[60vh]" x-ref="zoomViewport">
                        <div class="doc-zoom-sizer" x-ref="zoomSizer">
                            <div class="doc-zoom-scale" x-ref="zoomScale" :style="'transform: scale(' + (zoomLevel / 100) + ');'">
                                {{-- Not yet customized — render the live
                                     template exactly as before
                                     (unchanged appearance/behavior). --}}
                                <template x-if="!selected.customDocumentHtml">
                                    <div>
                                        <template x-if="resolvedDocument?.previewType === 'barangay'">
                                            <x-citizen.barangay-certificate-preview
                                                docKey="resolvedDocument.docKey"
                                                bind="resolvedDocument.snapshot"
                                                title="resolvedDocument.certTitle"
                                                subtitle="resolvedDocument.certSubtitle"
                                                barangay="selected.barangay"
                                                seal="barangayCatalog[selected.barangay]?.seal || null"
                                                punongBarangay="resolvedDocument.barangayOfficials?.punong_barangay"
                                                barangaySecretary="resolvedDocument.barangayOfficials?.barangay_secretary"
                                                background="documentBackground"
                                                verify="selected.issued_documents"
                                            />
                                        </template>
                                        <template x-if="resolvedDocument?.previewType === 'municipal_request'">
                                            <x-citizen.municipal-request-preview
                                                docKey="resolvedDocument.docKey"
                                                bind="resolvedDocument.snapshot"
                                                title="resolvedDocument.certTitle"
                                                office="selected.office"
                                                formNo="selected.formNo"
                                                officiant="selected.officiant"
                                                officiantTitle="selected.officiantTitle"
                                                technician="selected.technician"
                                                technicianTitle="selected.technicianTitle"
                                                verifier="selected.verifier"
                                                verifierTitle="selected.verifierTitle"
                                                background="documentBackground"
                                                verify="selected.issued_documents"
                                            />
                                        </template>
                                    </div>
                                </template>

                                {{-- Customized (or currently being
                                     edited) — the frozen/edited HTML
                                     from startEditingDocument()/
                                     saveDocumentEdits() above, with
                                     the watermark rendered as an
                                     independent sibling layer driven
                                     by documentBackground so Change
                                     Background never touches the
                                     editable text, and vice versa. --}}
                                <template x-if="selected.customDocumentHtml">
                                    <div class="doc-paper border border-slate-200 shadow-sm relative overflow-hidden mx-auto flex flex-col p-6 sm:p-10" :style="paperStyle()" :class="documentBackground.decorative ? 'doc-paper--tpl-' + documentBackground.decorative : ''">
                                        <img class="doc-template-artwork" alt="" aria-hidden="true" x-show="documentBackground.decorative === 'labangtaytay'" x-cloak src="{{ asset('images/esperanza/templates/barangay-labangtaytay-artwork.svg') }}">
                                        <img class="doc-watermark" alt="" aria-hidden="true" x-show="documentBackground.src" x-cloak :src="documentBackground.src" :style="watermarkStyle()">
                                        <div
                                            class="doc-content relative flex flex-col flex-1"
                                            x-ref="docEditor"
                                            :contenteditable="editingDocument ? 'true' : 'false'"
                                            x-html="selected.customDocumentHtml"
                                            @click="selectSection($event)"
                                            @mouseover="hoverSection($event)"
                                            @mouseleave="clearHoverSection()"
                                            @keydown="handleContentKeydown($event)"
                                            @input="reconcileSectionMarkers(); reconcileEmptyBlocks(); refreshOverflowWarning(); recomputeZoomSizer()"
                                        ></div>
                                        {{-- Control number, code and QR of the issued copy, outside the editable text (scope round 2026-10-15). --}}
                                        <x-documents.verify-strip doc="selected.issued_documents" doc-key="resolvedDocument?.docKey" />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </template>

        <x-slot:footer>
            {{-- While editing: keep or drop the changes. Closing the window drops them too. --}}
            <x-ui.button size="sm" variant="secondary" x-show="editingDocument" x-cloak @click="cancelDocumentEdits()">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" x-show="editingDocument" x-cloak @click="saveDocumentEdits()">Save changes</x-ui.button>
            <p class="mr-auto text-[11px] leading-snug text-amber-700" x-show="!editingDocument && editedInWindow && editedInWindow === selected?.ref" x-cloak>Not saved: this edit is in this window only. Save again, or print or download it before you leave.</p>
            <div class="mr-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] leading-snug text-ink-muted" x-show="!editingDocument && selected?.prepared && editedInWindow !== selected?.ref" x-cloak>
                <span x-text="'Edited by ' + (selected?.prepared?.by || 'an officer') + (selected?.prepared?.at ? ' on ' + new Date(selected.prepared.at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '') + '. Staff see this copy; the resident sees the template.'"></span>
                <button type="button" x-show="selected?.prepared?.editable && mayEditDocument" @click="useTemplateAgain()" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">Use the template again</button>
            </div>
            <x-ui.button size="sm" variant="secondary" icon="printer" x-show="!editingDocument" @click="printDocument()">Print</x-ui.button>
            <x-ui.button size="sm" icon="download" x-show="!editingDocument" @click="downloadDocument()">Download PDF</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
