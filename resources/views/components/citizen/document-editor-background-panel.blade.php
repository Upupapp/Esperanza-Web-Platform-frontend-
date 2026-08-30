{{-- Floating "Background" inspector for the Dokyu Edit Document editor —
     same draggable/collapsible floating-window pattern as Position &
     Spacing (document-editor-spacing-panel.blade.php's own header comment
     explains the shared .doc-floating-panel*/floatingPanels/startPanelDrag()
     architecture in full); previously an inline panel, now converted so it
     floats above the paper instead of pushing page content around.
     Controls the large low-opacity watermark (replace/remove/restore,
     opacity, size, position) and the paper's background color. Deliberately
     separate from the header seal(s), which stay part of each document
     template's own letterhead markup (barangay-certificate-preview.blade.php
     / municipal-request-preview.blade.php) and are never touched here —
     only the large background watermark and the paper color are admin-
     controllable. dp-no-print (on the root element) keeps it out of
     Print/PDF like every other editor chrome piece.

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, activePanel, floatingPanels.background,
     startPanelDrag($event, 'background'), documentBackground,
     handleWatermarkUpload($event), removeWatermark(),
     restoreDefaultWatermark(), setWatermarkOpacity(v), setWatermarkSize(v),
     setWatermarkPosition(v), setPaperColor(hex), resetPaperColor(),
     resetBackground().

     :style is an object, not a string — see document-editor-spacing-panel.blade.php's
     own comment on this same pattern for why: a string :style binding
     overwrites the whole style attribute on every re-run, which can wipe
     out the display:none x-show writes directly onto the element and
     leave Close not sticking. --}}
<div
    class="doc-floating-panel dp-no-print"
    x-show="editingDocument && activePanel === 'background'"
    x-cloak
    x-transition
    :style="{ left: floatingPanels.background.pos.x + 'px', top: floatingPanels.background.pos.y + 'px' }"
    :class="floatingPanels.background.expanded ? 'doc-floating-panel--expanded' : ''"
>
    <x-citizen.doc-floating-panel-header title="Background" title-fil="Background" panel-key="background" />

    <div x-show="!floatingPanels.background.collapsed">
        <div class="doc-floating-panel-body space-y-4">
            <div>
                <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Watermark" en="Watermark" /></p>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="doc-toolbar-btn border border-slate-200 cursor-pointer">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i> <x-ui.t fil="Palitan ang Watermark" en="Replace Watermark" />
                        <input type="file" accept="image/*" class="hidden" @change="handleWatermarkUpload($event)"
                            :aria-label="$store.lang.current === 'en' ? 'Replace watermark image' : 'Palitan ang larawan ng watermark'">
                    </label>
                    <button type="button" class="doc-toolbar-btn border border-slate-200" @click="removeWatermark()"><x-ui.t fil="Alisin ang Watermark" en="Remove Watermark" /></button>
                    <button type="button" class="doc-toolbar-btn border border-slate-200" @click="restoreDefaultWatermark()"><x-ui.t fil="Ibalik ang Watermark ng Esperanza" en="Restore Esperanza Watermark" /></button>
                </div>
            </div>

            <div x-show="documentBackground.src" x-cloak class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Opacity" en="Opacity" /> — <span x-text="documentBackground.opacity"></span>%</p>
                    <input type="range" min="0" max="20" step="1" :value="documentBackground.opacity" @input="setWatermarkOpacity($event.target.value)"
                        :aria-label="$store.lang.current === 'en' ? 'Watermark opacity' : 'Opacity ng watermark'" class="w-full">
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Laki" en="Size" /></p>
                    <div class="flex gap-1.5">
                        <template x-for="opt in ['small', 'medium', 'large']" :key="opt">
                            <button type="button" class="doc-toolbar-btn border capitalize" :class="documentBackground.size === opt ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-slate-200'" @click="setWatermarkSize(opt)" x-text="$store.lang.current === 'en' ? opt : ({ small: 'maliit', medium: 'katamtaman', large: 'malaki' })[opt]"></button>
                        </template>
                    </div>
                </div>
                <div class="col-span-2">
                    <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Posisyon" en="Position" /></p>
                    <div class="flex gap-1.5 flex-wrap">
                        <template x-for="opt in ['center', 'top', 'bottom', 'left', 'right']" :key="opt">
                            <button type="button" class="doc-toolbar-btn border capitalize" :class="documentBackground.position === opt ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-slate-200'" @click="setWatermarkPosition(opt)" x-text="$store.lang.current === 'en' ? opt : ({ center: 'gitna', top: 'itaas', bottom: 'ibaba', left: 'kaliwa', right: 'kanan' })[opt]"></button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-3">
                <p class="text-[11px] font-medium text-slate-500 mb-1.5"><x-ui.t fil="Kulay ng Papel" en="Paper Color" /></p>
                <div class="flex items-center gap-2">
                    <input type="color" :value="documentBackground.paperColor" @input="setPaperColor($event.target.value)"
                        :aria-label="$store.lang.current === 'en' ? 'Paper color' : 'Kulay ng papel'" class="w-8 h-8 border-0 p-0 bg-transparent cursor-pointer">
                    <span class="text-xs text-slate-500 font-mono" x-text="documentBackground.paperColor"></span>
                    <button type="button" class="doc-toolbar-btn border border-slate-200 ml-auto" @click="resetPaperColor()"><x-ui.t fil="Ibalik sa Puti" en="Reset to White" /></button>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-3 flex justify-end">
                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="resetBackground()"><x-ui.t fil="I-reset ang Background" en="Reset Background" /></button>
            </div>
        </div>
    </div>
</div>
