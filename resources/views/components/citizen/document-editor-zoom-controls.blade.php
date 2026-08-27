{{-- Viewer zoom controls for the Dokyu document preview — a pure display
     feature (see resources/js/dokyu-editor.js for the full transform/
     hit-testing/print-neutralization reasoning), so it's shown regardless
     of editingDocument (useful in plain View Document too, not just while
     editing), and kept visually separate from the formatting toolbar so
     it's clear this affects the view, not selected text. Sits outside
     #dp-print-area (dp-no-print).

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: zoomLevel, zoomIn(), zoomOut(), setZoomLevel(level),
     resetZoom(), fitPage(). --}}
<div class="doc-zoom-controls dp-no-print flex items-center gap-1 mb-3">
    <button type="button" class="doc-toolbar-btn border border-slate-200" title="Zoom out" @click="zoomOut()">
        <i data-lucide="minus" class="w-3.5 h-3.5"></i>
    </button>
    <select class="doc-toolbar-select w-20" title="Zoom level" :value="zoomLevel" @change="setZoomLevel(Number($event.target.value))">
        <template x-for="level in [50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200]" :key="level">
            <option :value="level" x-text="level + '%'" :selected="level === zoomLevel"></option>
        </template>
        <option x-show="![50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200].includes(zoomLevel)" :value="zoomLevel" x-text="zoomLevel + '%'" selected></option>
    </select>
    <button type="button" class="doc-toolbar-btn border border-slate-200" title="Zoom in" @click="zoomIn()">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
    </button>
    <span class="doc-toolbar-sep"></span>
    <button type="button" class="doc-toolbar-btn border border-slate-200" title="Fit the whole page in view" @click="fitPage()">
        <i data-lucide="maximize" class="w-3.5 h-3.5"></i> Fit Page
    </button>
    <button type="button" class="doc-toolbar-btn text-slate-500" title="Reset to 100%" x-show="zoomLevel !== 100" @click="resetZoom()">
        Reset Zoom
    </button>
</div>
