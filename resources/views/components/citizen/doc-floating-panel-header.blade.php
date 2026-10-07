{{-- Shared header (drag handle + title + Expand/Collapse/Close) for every
     Dokyu Edit Document floating inspector — Position & Spacing, Header
     Seals, Background, and any future one — so drag/expand/collapse/close
     behave identically everywhere instead of each panel rolling its own
     header. Two static icons per toggle (rather than one icon with a
     dynamically bound data-lucide) because Lucide replaces each
     <i data-lucide> with a plain <svg> once at startup (see app.js's own
     renderIcons() comment) — changing the bound icon NAME afterwards has no
     visual effect on an SVG that already exists, so both icons are always
     rendered and x-show simply picks which one is visible, exactly like
     this codebase's existing processing-spinner icons.

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: activePanel, floatingPanels[panelKey] ({pos, collapsed,
     expanded}), startPanelDrag($event, panelKey).

     titleFil defaults to title, so a caller that has no Filipino wording yet
     degrades to the English one rather than rendering an empty header. --}}
@props(['title', 'titleFil' => null, 'panelKey'])

@php
    $titleFil = $titleFil ?? $title;
@endphp

<div class="doc-floating-panel-header" @pointerdown="startPanelDrag($event, '{{ $panelKey }}')">
    <span class="text-[12.5px] font-semibold text-ink select-none">
        <x-ui.t :fil="$titleFil" :en="$title" />
    </span>
    <div class="flex items-center gap-0.5">
        {{-- These are icon-only controls, so the accessible name and the tooltip
             are the same bilingual string, bound rather than static: x-ui.t
             renders a <span> and cannot be used inside an attribute. --}}
        <button
            type="button"
            class="doc-floating-panel-icon-btn"
            :title="$store.lang.current === 'en' ? 'Expand for a larger, easier-to-read view' : 'Palakihin para mas madaling basahin'"
            :aria-label="$store.lang.current === 'en' ? 'Expand for a larger, easier-to-read view' : 'Palakihin para mas madaling basahin'"
            x-show="!floatingPanels.{{ $panelKey }}.expanded"
            @click="floatingPanels.{{ $panelKey }}.expanded = true"
        >
            <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
        </button>
        <button
            type="button"
            class="doc-floating-panel-icon-btn"
            :title="$store.lang.current === 'en' ? 'Compact view' : 'Compact na tanawin'"
            :aria-label="$store.lang.current === 'en' ? 'Compact view' : 'Compact na tanawin'"
            x-show="floatingPanels.{{ $panelKey }}.expanded"
            x-cloak
            @click="floatingPanels.{{ $panelKey }}.expanded = false"
        >
            <i data-lucide="minimize-2" class="w-3.5 h-3.5"></i>
        </button>
        <button
            type="button"
            class="doc-floating-panel-icon-btn"
            :title="$store.lang.current === 'en' ? 'Collapse' : 'Itiklop'"
            :aria-label="$store.lang.current === 'en' ? 'Collapse' : 'Itiklop'"
            @click="floatingPanels.{{ $panelKey }}.collapsed = !floatingPanels.{{ $panelKey }}.collapsed"
        >
            <i data-lucide="chevron-down" class="w-3.5 h-3.5" :class="floatingPanels.{{ $panelKey }}.collapsed ? '-rotate-90' : ''"></i>
        </button>
        <button
            type="button"
            class="doc-floating-panel-icon-btn"
            :title="$store.lang.current === 'en' ? 'Close' : 'Isara'"
            :aria-label="$store.lang.current === 'en' ? 'Close' : 'Isara'"
            @click="activePanel = null"
        >
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>
</div>
