{{-- Floating "Header Seals" inspector for the Dokyu Edit Document editor —
     same draggable/collapsible floating-window pattern as Position &
     Spacing (document-editor-spacing-panel.blade.php's own header comment
     explains the shared .doc-floating-panel*/floatingPanels/startPanelDrag()
     architecture in full); previously an inline panel, now converted so it
     floats above the paper instead of pushing page content around.
     Deliberately separate from the large background watermark (see
     document-editor-background-panel.blade.php): these are the small
     letterhead seals every template already renders, marked with a stable
     header-seal--left / header-seal--right class (see
     barangay-certificate-preview.blade.php / municipal-request-preview.blade.php)
     so this panel can find and adjust them generically across every Dokyu
     paper without any per-docKey logic. Municipal papers only ever render a
     left seal, so the Right Seal block hides itself automatically when
     there's nothing to control. dp-no-print (on the root element) keeps it
     out of Print/PDF like every other editor chrome piece.

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, activePanel, floatingPanels.seals,
     startPanelDrag($event, 'seals'), hasRightSeal(), sealOffsets,
     handleSealUpload(side, $event), removeSeal(side), restoreSeal(side),
     setSealSize(side, size), adjustSealOffset(side, axis, delta).

     :style is an object, not a string — see document-editor-spacing-panel.blade.php's
     own comment on this same pattern for why: a string :style binding
     overwrites the whole style attribute on every re-run, which can wipe
     out the display:none x-show writes directly onto the element and
     leave Close not sticking. --}}
<div
    class="doc-floating-panel dp-no-print"
    x-show="editingDocument && activePanel === 'seals'"
    x-cloak
    x-transition
    :style="{ left: floatingPanels.seals.pos.x + 'px', top: floatingPanels.seals.pos.y + 'px' }"
    :class="floatingPanels.seals.expanded ? 'doc-floating-panel--expanded' : ''"
>
    <x-citizen.doc-floating-panel-header title="Header Seals" title-fil="Mga Selyo sa Header" panel-key="seals" />

    <div x-show="!floatingPanels.seals.collapsed">
        <div class="doc-floating-panel-body space-y-4">
            <template x-for="side in ['left', 'right']" :key="side">
                <div class="space-y-2.5 pb-3 border-b border-slate-100 last:border-0 last:pb-0" x-show="side === 'left' || hasRightSeal()">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="$store.lang.current === 'en'
                        ? side + ' seal'
                        : 'selyo sa ' + (side === 'left' ? 'kaliwa' : 'kanan')"></p>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="doc-toolbar-btn border border-slate-200 cursor-pointer">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i> <x-ui.t fil="Palitan" en="Replace" />
                            <input type="file" accept="image/*" class="hidden" @change="handleSealUpload(side, $event)">
                        </label>
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="removeSeal(side)"><x-ui.t fil="Alisin" en="Remove" /></button>
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="restoreSeal(side)"><x-ui.t fil="Ibalik ang Default" en="Restore Default" /></button>
                    </div>

                    <div>
                        <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Laki" en="Size" /></p>
                        <div class="flex gap-1.5">
                            <template x-for="opt in ['small', 'medium', 'large']" :key="opt">
                                <button type="button" class="doc-toolbar-btn border capitalize" :class="'border-slate-200'" @click="setSealSize(side, opt)"
                                    x-text="$store.lang.current === 'en'
                                        ? opt
                                        : ({ small: 'maliit', medium: 'katamtaman', large: 'malaki' })[opt]"></button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[11px] font-medium text-slate-500 mb-1"><x-ui.t fil="Pahalang" en="Horizontal" /> — <span x-text="sealOffsets[side].x"></span>px</p>
                            <div class="flex items-center gap-1">
                                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'x', -5)" :aria-label="$store.lang.current === 'en' ? 'Decrease horizontal' : 'Bawasan ang pahalang'">−</button>
                                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'x', 5)" :aria-label="$store.lang.current === 'en' ? 'Increase horizontal' : 'Dagdagan ang pahalang'">+</button>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-500 mb-1"><x-ui.t fil="Patayo" en="Vertical" /> — <span x-text="sealOffsets[side].y"></span>px</p>
                            <div class="flex items-center gap-1">
                                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'y', -5)" :aria-label="$store.lang.current === 'en' ? 'Decrease vertical' : 'Bawasan ang patayo'">−</button>
                                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'y', 5)" :aria-label="$store.lang.current === 'en' ? 'Increase vertical' : 'Dagdagan ang patayo'">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
