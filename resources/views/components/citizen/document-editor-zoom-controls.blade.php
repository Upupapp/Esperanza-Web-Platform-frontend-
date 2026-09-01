{{-- Viewer zoom controls for the Dokyu document preview — a pure display
     feature (see resources/js/dokyu-editor.js for the full transform/
     hit-testing/print-neutralization reasoning), so it's shown regardless
     of editingDocument (useful in plain View Document too, not just while
     editing), and kept visually separate from the formatting toolbar so
     it's clear this affects the view, not selected text. Sits outside
     #dp-print-area (dp-no-print).

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: zoomLevel, zoomIn(), zoomOut(), setZoomLevel(level),
     resetZoom(), fitPage(). Full View reuses `compact`, the toggle already
     built into components/ui/modal.blade.php's own `fullscreen` branch
     (the Prepared Document modal opts in via :fullscreen="true" and
     :start-compact="true" so it still opens at its normal size) — `compact`
     lives on that wrapping x-data and is reachable here through Alpine's
     ordinary scope chain, so this is one shared toggle, not a second,
     competing one; the modal's own header icon can also flip it.

     Tooltips are bound rather than static because x-ui.t renders a <span>
     and cannot live inside an attribute. Icon-only controls carry the same
     string as aria-label, so the accessible name matches the tooltip. --}}
<div class="doc-zoom-controls dp-no-print flex items-center gap-1 mb-3">
    <button
        type="button"
        class="doc-toolbar-btn border border-slate-200"
        :title="$store.lang.current === 'en' ? 'Zoom out' : 'Paliitin'"
        :aria-label="$store.lang.current === 'en' ? 'Zoom out' : 'Paliitin'"
        @click="zoomOut()"
    >
        <i data-lucide="minus" class="w-3.5 h-3.5"></i>
    </button>

    <select
        class="doc-toolbar-select w-20"
        :title="$store.lang.current === 'en' ? 'Zoom level' : 'Antas ng laki'"
        :aria-label="$store.lang.current === 'en' ? 'Zoom level' : 'Antas ng laki'"
        :value="zoomLevel"
        @change="setZoomLevel(Number($event.target.value))"
    >
        <template x-for="level in [50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200]" :key="level">
            <option :value="level" x-text="level + '%'" :selected="level === zoomLevel"></option>
        </template>
        <option x-show="![50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200].includes(zoomLevel)" :value="zoomLevel" x-text="zoomLevel + '%'" selected></option>
    </select>

    <button
        type="button"
        class="doc-toolbar-btn border border-slate-200"
        :title="$store.lang.current === 'en' ? 'Zoom in' : 'Palakihin'"
        :aria-label="$store.lang.current === 'en' ? 'Zoom in' : 'Palakihin'"
        @click="zoomIn()"
    >
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
    </button>

    <span class="doc-toolbar-sep"></span>

    <button
        type="button"
        class="doc-toolbar-btn border border-slate-200"
        :title="$store.lang.current === 'en' ? 'Fit the whole page in view' : 'Ipakita ang buong pahina'"
        @click="fitPage()"
    >
        <i data-lucide="maximize" class="w-3.5 h-3.5"></i> <x-ui.t fil="Ibagay ang Pahina" en="Fit Page" />
    </button>

    <button
        type="button"
        class="doc-toolbar-btn text-slate-500"
        :title="$store.lang.current === 'en' ? 'Reset to 100%' : 'Ibalik sa 100%'"
        x-show="zoomLevel !== 100"
        @click="resetZoom()"
    >
        <x-ui.t fil="I-reset ang Laki" en="Reset Zoom" />
    </button>

    <span class="doc-toolbar-sep"></span>

    <button
        type="button"
        class="doc-toolbar-btn border border-slate-200 font-medium"
        :title="$store.lang.current === 'en'
            ? 'Enlarge the document workspace — more room for editing, not a zoom change'
            : 'Palakihin ang workspace ng dokumento — mas maluwag para sa pag-edit, hindi pagbabago ng laki'"
        x-show="compact"
        @click="compact = false"
    >
        <i data-lucide="expand" class="w-3.5 h-3.5"></i> <x-ui.t fil="Buong Tanawin" en="Full View" />
    </button>

    <button
        type="button"
        class="doc-toolbar-btn border border-brand-300 bg-brand-50 text-brand-700 font-medium"
        :title="$store.lang.current === 'en' ? 'Return to the normal workspace size' : 'Bumalik sa karaniwang laki ng workspace'"
        x-show="!compact"
        x-cloak
        @click="compact = true"
    >
        <i data-lucide="shrink" class="w-3.5 h-3.5"></i> <x-ui.t fil="Lumabas sa Buong Tanawin" en="Exit Full View" />
    </button>
</div>
