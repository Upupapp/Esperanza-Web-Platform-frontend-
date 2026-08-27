{{-- Header seal editing panel for the Dokyu Edit Document editor —
     deliberately separate from the large background watermark (see
     document-editor-background-panel.blade.php): these are the small
     letterhead seals every template already renders, marked with a stable
     header-seal--left / header-seal--right class (see
     barangay-certificate-preview.blade.php / municipal-request-preview.blade.php)
     so this panel can find and adjust them generically across every Dokyu
     paper without any per-docKey logic. Municipal papers only ever render a
     left seal, so the Right Seal block hides itself automatically when
     there's nothing to control. Sits outside #dp-print-area (dp-no-print).

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, activePanel, hasRightSeal(), sealOffsets,
     handleSealUpload(side, $event), removeSeal(side), restoreSeal(side),
     setSealSize(side, size), adjustSealOffset(side, axis, delta). --}}
<div
    class="doc-seal-panel dp-no-print rounded-xl border border-slate-200 bg-white shadow-float p-4 mb-3 space-y-4"
    x-show="editingDocument && activePanel === 'seals'"
    x-cloak
    x-transition
>
    <div class="flex items-center justify-between">
        <p class="text-xs font-semibold text-navy-900">Edit Header Seals</p>
        <button type="button" class="text-slate-400 hover:text-slate-600" @click="activePanel = null" title="Close">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>

    <template x-for="side in ['left', 'right']" :key="side">
        <div class="space-y-2.5 pb-3 border-b border-slate-100 last:border-0 last:pb-0" x-show="side === 'left' || hasRightSeal()">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="side + ' seal'"></p>

            <div class="flex flex-wrap items-center gap-2">
                <label class="doc-toolbar-btn border border-slate-200 cursor-pointer">
                    <i data-lucide="upload" class="w-3.5 h-3.5"></i> Replace
                    <input type="file" accept="image/*" class="hidden" @change="handleSealUpload(side, $event)">
                </label>
                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="removeSeal(side)">Remove</button>
                <button type="button" class="doc-toolbar-btn border border-slate-200" @click="restoreSeal(side)">Restore Default</button>
            </div>

            <div>
                <p class="text-[11px] font-medium text-slate-500 mb-1.5">Size</p>
                <div class="flex gap-1.5">
                    <template x-for="opt in ['small', 'medium', 'large']" :key="opt">
                        <button type="button" class="doc-toolbar-btn border capitalize" :class="'border-slate-200'" @click="setSealSize(side, opt)" x-text="opt"></button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-[11px] font-medium text-slate-500 mb-1">Horizontal — <span x-text="sealOffsets[side].x"></span>px</p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'x', -5)">−</button>
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'x', 5)">+</button>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-500 mb-1">Vertical — <span x-text="sealOffsets[side].y"></span>px</p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'y', -5)">−</button>
                        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="adjustSealOffset(side, 'y', 5)">+</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
