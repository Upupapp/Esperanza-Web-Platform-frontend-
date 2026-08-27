{{-- "Change Background" panel for the Dokyu "Edit Document" editor —
     controls the large low-opacity watermark (replace/remove/restore,
     opacity, size, position) and the paper's background color. Deliberately
     separate from the header seal(s), which stay part of each document
     template's own letterhead markup (barangay-certificate-preview.blade.php
     / municipal-request-preview.blade.php) and are never touched here —
     only the large background watermark and the paper color are admin-
     controllable. Sits outside #dp-print-area (dp-no-print).

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, activePanel, documentBackground,
     handleWatermarkUpload($event), removeWatermark(),
     restoreDefaultWatermark(), setWatermarkOpacity(v), setWatermarkSize(v),
     setWatermarkPosition(v), setPaperColor(hex), resetPaperColor(),
     resetBackground(). --}}
<div
    class="doc-background-panel dp-no-print rounded-xl border border-slate-200 bg-white shadow-float p-4 mb-3 space-y-4"
    x-show="editingDocument && activePanel === 'background'"
    x-cloak
    x-transition
>
    <div class="flex items-center justify-between">
        <p class="text-xs font-semibold text-navy-900">Change Background</p>
        <button type="button" class="text-slate-400 hover:text-slate-600" @click="activePanel = null" title="Close">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>

    <div>
        <p class="text-[11px] font-medium text-slate-500 mb-1.5">Watermark</p>
        <div class="flex flex-wrap items-center gap-2">
            <label class="doc-toolbar-btn border border-slate-200 cursor-pointer">
                <i data-lucide="upload" class="w-3.5 h-3.5"></i> Replace Seal
                <input type="file" accept="image/*" class="hidden" @change="handleWatermarkUpload($event)">
            </label>
            <button type="button" class="doc-toolbar-btn border border-slate-200" @click="removeWatermark()">Remove Seal</button>
            <button type="button" class="doc-toolbar-btn border border-slate-200" @click="restoreDefaultWatermark()">Restore Esperanza Seal</button>
        </div>
    </div>

    <div x-show="documentBackground.src" x-cloak class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-[11px] font-medium text-slate-500 mb-1.5">Opacity — <span x-text="documentBackground.opacity"></span>%</p>
            <input type="range" min="0" max="20" step="1" :value="documentBackground.opacity" @input="setWatermarkOpacity($event.target.value)" class="w-full">
        </div>
        <div>
            <p class="text-[11px] font-medium text-slate-500 mb-1.5">Size</p>
            <div class="flex gap-1.5">
                <template x-for="opt in ['small', 'medium', 'large']" :key="opt">
                    <button type="button" class="doc-toolbar-btn border capitalize" :class="documentBackground.size === opt ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-slate-200'" @click="setWatermarkSize(opt)" x-text="opt"></button>
                </template>
            </div>
        </div>
        <div class="col-span-2">
            <p class="text-[11px] font-medium text-slate-500 mb-1.5">Position</p>
            <div class="flex gap-1.5 flex-wrap">
                <template x-for="opt in ['center', 'top', 'bottom', 'left', 'right']" :key="opt">
                    <button type="button" class="doc-toolbar-btn border capitalize" :class="documentBackground.position === opt ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-slate-200'" @click="setWatermarkPosition(opt)" x-text="opt"></button>
                </template>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-3">
        <p class="text-[11px] font-medium text-slate-500 mb-1.5">Paper Color</p>
        <div class="flex items-center gap-2">
            <input type="color" :value="documentBackground.paperColor" @input="setPaperColor($event.target.value)" class="w-8 h-8 border-0 p-0 bg-transparent cursor-pointer">
            <span class="text-xs text-slate-500 font-mono" x-text="documentBackground.paperColor"></span>
            <button type="button" class="doc-toolbar-btn border border-slate-200 ml-auto" @click="resetPaperColor()">Reset to White</button>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-3 flex justify-end">
        <button type="button" class="doc-toolbar-btn border border-slate-200" @click="resetBackground()">Reset Background</button>
    </div>
</div>
