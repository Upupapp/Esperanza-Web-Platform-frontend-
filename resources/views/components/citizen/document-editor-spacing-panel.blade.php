{{-- Floating "Position & Spacing" inspector for the Dokyu Edit Document
     editor — a compact, draggable palette (Word/design-tool style) rather
     than an inline block, so it never pushes the A4 paper down or eats
     page space. position:fixed takes it out of normal flow entirely (so it
     can't affect document layout even in principle) and out from under the
     zoom scale (see document-editor-zoom-controls.blade.php's own comment
     on .doc-zoom-scale) — its own on-screen size never changes with paper
     zoom. dp-no-print keeps it out of Print/PDF like every other editor
     chrome piece.

     Works on whatever block the admin last clicked inside .doc-content —
     a major section (letterhead/title/body/signature row — a direct child
     of .doc-content) or a specific piece nested inside one (e.g. just the
     Punong Barangay name, or just the title beneath it) — generically
     across every Dokyu paper, since it's all driven by findSelectableBlock()
     in resources/js/dokyu-editor.js. Dragging the small handle that appears
     on the document itself (attachDragHandle() in document-requests.blade.php)
     is the primary way to reposition whatever is selected; everything here
     is the fine-tuning/exact-value fallback, plus Reset.

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, activePanel, selectedSectionEl,
     selectedSectionLabel, isMajorSection, sectionSpacing, sectionOffsetX,
     sectionOffsetY, documentOverflow, spacingPanelPos, spacingPanelCollapsed,
     startPanelDrag($event), selectParentSection(), adjustSpacingBefore(px),
     adjustSpacingAfter(px), adjustLineHeight(step), adjustSectionOffsetX(px),
     setSectionOffsetXDirect(px), adjustSectionOffsetY(px),
     setSectionOffsetYDirect(px), moveSelectedSection(direction),
     resetSectionSpacing(), deselectSection(). --}}
<div
    class="doc-spacing-panel dp-no-print"
    x-show="editingDocument && activePanel === 'spacing'"
    x-cloak
    x-transition
    :style="'left:' + spacingPanelPos.x + 'px; top:' + spacingPanelPos.y + 'px;'"
>
    <div class="doc-spacing-panel-header" @pointerdown="startPanelDrag($event)">
        <span class="text-[11px] font-semibold text-navy-900 select-none">Position &amp; Spacing</span>
        <div class="flex items-center gap-0.5">
            <button type="button" class="doc-spacing-panel-icon-btn" title="Collapse" @click="spacingPanelCollapsed = !spacingPanelCollapsed">
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" :class="spacingPanelCollapsed ? '-rotate-90' : ''"></i>
            </button>
            <button type="button" class="doc-spacing-panel-icon-btn" title="Close" @click="activePanel = null">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>

    <div x-show="!spacingPanelCollapsed">
        <div class="doc-spacing-panel-body">
            <p class="text-[11px] text-slate-400 leading-relaxed" x-show="!selectedSectionEl">Click any part of the document to select it.</p>

            <template x-if="selectedSectionEl">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[11px] text-slate-500 truncate">Selected: <span class="font-medium text-navy-900" x-text="selectedSectionLabel"></span></p>
                        <button type="button" class="doc-spacing-mini-btn shrink-0" x-show="!isMajorSection" @click="selectParentSection()">Whole section</button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="doc-spacing-label">X</p>
                            <div class="doc-spacing-row">
                                <input type="number" step="1" :value="sectionOffsetX" @change="setSectionOffsetXDirect($event.target.value)" class="doc-spacing-input">
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetX(-5)">−</button>
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetX(5)">+</button>
                            </div>
                        </div>
                        <div>
                            <p class="doc-spacing-label">Y</p>
                            <div class="doc-spacing-row">
                                <input type="number" step="1" :value="sectionOffsetY" @change="setSectionOffsetYDirect($event.target.value)" class="doc-spacing-input">
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetY(-10)">−</button>
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetY(10)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="doc-spacing-mini-btn justify-center" @click="adjustSectionOffsetY(-10)"><i data-lucide="arrow-up" class="w-3 h-3"></i> Up</button>
                        <button type="button" class="doc-spacing-mini-btn justify-center" @click="adjustSectionOffsetY(10)"><i data-lucide="arrow-down" class="w-3 h-3"></i> Down</button>
                    </div>

                    <div class="doc-spacing-divider"></div>

                    <div>
                        <p class="doc-spacing-label">Spacing Before</p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.before + ' px'"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingBefore(-4)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingBefore(4)">+</button>
                        </div>
                    </div>
                    <div>
                        <p class="doc-spacing-label">Spacing After</p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.after + ' px'"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingAfter(-4)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingAfter(4)">+</button>
                        </div>
                    </div>
                    <div>
                        <p class="doc-spacing-label">Line Height</p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.lineHeight.toFixed(1)"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustLineHeight(-0.1)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustLineHeight(0.1)">+</button>
                        </div>
                    </div>

                    <div class="doc-spacing-divider"></div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" class="doc-spacing-mini-btn flex-1 justify-center" :disabled="!sectionSpacing.canMoveUp" :class="!sectionSpacing.canMoveUp ? 'opacity-40 cursor-not-allowed' : ''" @click="moveSelectedSection('up')" title="Swap with the previous element in document order">Reorder ↑</button>
                        <button type="button" class="doc-spacing-mini-btn flex-1 justify-center" :disabled="!sectionSpacing.canMoveDown" :class="!sectionSpacing.canMoveDown ? 'opacity-40 cursor-not-allowed' : ''" @click="moveSelectedSection('down')" title="Swap with the next element in document order">Reorder ↓</button>
                    </div>

                    <div class="flex items-center justify-between pt-0.5">
                        <button type="button" class="doc-spacing-mini-btn" @click="resetSectionSpacing()">Reset Position</button>
                        <button type="button" class="doc-spacing-mini-btn text-slate-400" @click="deselectSection()">Deselect</button>
                    </div>
                </div>
            </template>

            <div class="doc-spacing-warning" x-show="documentOverflow" x-cloak>
                <i data-lucide="triangle-alert" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                <p class="text-[11px] text-amber-700">Document content exceeds one A4 page.</p>
            </div>
        </div>
    </div>
</div>
