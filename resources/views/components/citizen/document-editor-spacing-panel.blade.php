{{-- Floating "Position & Spacing" inspector for the Dokyu Edit Document
     editor — a compact, draggable palette (Word/design-tool style) rather
     than an inline block, so it never pushes the A4 paper down or eats
     page space. position:fixed takes it out of normal flow entirely (so it
     can't affect document layout even in principle) and out from under the
     zoom scale (see document-editor-zoom-controls.blade.php's own comment
     on .doc-zoom-scale) — its own on-screen size never changes with paper
     zoom. dp-no-print keeps it out of Print/PDF like every other editor
     chrome piece. Shares its .doc-floating-panel* chrome and
     floatingPanels/startPanelDrag() state with Header Seals
     (document-editor-header-seal-panel.blade.php) and Background
     (document-editor-background-panel.blade.php) — one floating-window
     pattern, not three separate ones.

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
     sectionOffsetY, documentOverflow, floatingPanels.spacing,
     startPanelDrag($event, 'spacing'), selectParentSection(),
     adjustSpacingBefore(px), adjustSpacingAfter(px), adjustLineHeight(step),
     adjustSectionOffsetX(px), setSectionOffsetXDirect(px),
     adjustSectionOffsetY(px), setSectionOffsetYDirect(px),
     moveSelectedSection(direction), resetSectionSpacing(),
     deselectSection(), canRemoveSelectedBlock, removeSelectedBlock().

     :style takes an object here, not a template-literal string, on
     purpose — this is the actual reason Close previously failed to stick
     on a panel that had never been individually toggled yet. x-show hides
     an element by writing display:none directly onto element.style; a
     STRING :style binding instead replaces the whole style attribute text
     every time it re-runs, so whichever of the two directives' effects
     happens to fire last on a given render wins outright, and there's no
     ordering guarantee between them. That's exactly why Close appeared to
     work sometimes and not others depending on what else had touched the
     panel already. The object form sets/removes individual CSS
     properties (left/top here) through the CSSStyleDeclaration API
     instead of overwriting the attribute wholesale, so it can never step
     on display, however many times it re-runs. --}}
<div
    class="doc-floating-panel dp-no-print"
    x-show="editingDocument && activePanel === 'spacing'"
    x-cloak
    x-transition
    :style="{ left: floatingPanels.spacing.pos.x + 'px', top: floatingPanels.spacing.pos.y + 'px' }"
    :class="floatingPanels.spacing.expanded ? 'doc-floating-panel--expanded' : ''"
>
    <x-citizen.doc-floating-panel-header title="Position & Spacing" title-fil="Posisyon at Espasyo" panel-key="spacing" />

    <div x-show="!floatingPanels.spacing.collapsed">
        <div class="doc-floating-panel-body">
            <p class="text-[11px] text-slate-500 leading-relaxed" x-show="!selectedSectionEl"><x-ui.t fil="I-click ang alinmang bahagi ng dokumento para piliin ito." en="Click any part of the document to select it." /></p>

            <template x-if="selectedSectionEl">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[11px] text-slate-500 truncate"><x-ui.t fil="Napili" en="Selected" />: <span class="font-medium text-navy-900" x-text="selectedSectionLabel"></span></p>
                        <button type="button" class="doc-spacing-mini-btn shrink-0" x-show="!isMajorSection" @click="selectParentSection()"><x-ui.t fil="Buong seksyon" en="Whole section" /></button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="doc-spacing-label">X</p>
                            <div class="doc-spacing-row">
                                <input type="number" step="1" :value="sectionOffsetX" @change="setSectionOffsetXDirect($event.target.value)"
                                    :aria-label="$store.lang.current === 'en' ? 'Horizontal position' : 'Pahalang na posisyon'" class="doc-spacing-input">
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetX(-5)">−</button>
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetX(5)">+</button>
                            </div>
                        </div>
                        <div>
                            <p class="doc-spacing-label">Y</p>
                            <div class="doc-spacing-row">
                                <input type="number" step="1" :value="sectionOffsetY" @change="setSectionOffsetYDirect($event.target.value)"
                                    :aria-label="$store.lang.current === 'en' ? 'Vertical position' : 'Patayong posisyon'" class="doc-spacing-input">
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetY(-10)">−</button>
                                <button type="button" class="doc-spacing-mini-btn" @click="adjustSectionOffsetY(10)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="doc-spacing-mini-btn justify-center" @click="adjustSectionOffsetY(-10)"><i data-lucide="arrow-up" class="w-3 h-3"></i> <x-ui.t fil="Itaas" en="Up" /></button>
                        <button type="button" class="doc-spacing-mini-btn justify-center" @click="adjustSectionOffsetY(10)"><i data-lucide="arrow-down" class="w-3 h-3"></i> <x-ui.t fil="Ibaba" en="Down" /></button>
                    </div>

                    <div class="doc-spacing-divider"></div>

                    <div>
                        <p class="doc-spacing-label"><x-ui.t fil="Espasyo sa Itaas" en="Spacing Before" /></p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.before + ' px'"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingBefore(-4)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingBefore(4)">+</button>
                        </div>
                    </div>
                    <div>
                        <p class="doc-spacing-label"><x-ui.t fil="Espasyo sa Ibaba" en="Spacing After" /></p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.after + ' px'"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingAfter(-4)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustSpacingAfter(4)">+</button>
                        </div>
                    </div>
                    <div>
                        <p class="doc-spacing-label"><x-ui.t fil="Taas ng Linya" en="Line Height" /></p>
                        <div class="doc-spacing-row">
                            <span class="doc-spacing-value" x-text="sectionSpacing.lineHeight.toFixed(1)"></span>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustLineHeight(-0.1)">−</button>
                            <button type="button" class="doc-spacing-mini-btn" @click="adjustLineHeight(0.1)">+</button>
                        </div>
                    </div>

                    <div class="doc-spacing-divider"></div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" class="doc-spacing-mini-btn flex-1 justify-center" :disabled="!sectionSpacing.canMoveUp" :class="!sectionSpacing.canMoveUp ? 'opacity-40 cursor-not-allowed' : ''" @click="moveSelectedSection('up')" :title="$store.lang.current === 'en' ? 'Swap with the previous element in document order' : 'Palitan ng puwesto ang naunang elemento'"><x-ui.t fil="Ayusin ↑" en="Reorder ↑" /></button>
                        <button type="button" class="doc-spacing-mini-btn flex-1 justify-center" :disabled="!sectionSpacing.canMoveDown" :class="!sectionSpacing.canMoveDown ? 'opacity-40 cursor-not-allowed' : ''" @click="moveSelectedSection('down')" :title="$store.lang.current === 'en' ? 'Swap with the next element in document order' : 'Palitan ng puwesto ang sumunod na elemento'"><x-ui.t fil="Ayusin ↓" en="Reorder ↓" /></button>
                    </div>

                    <div class="flex items-center justify-between pt-0.5">
                        <button type="button" class="doc-spacing-mini-btn" @click="resetSectionSpacing()"><x-ui.t fil="I-reset ang Posisyon" en="Reset Position" /></button>
                        <button type="button" class="doc-spacing-mini-btn text-slate-500" @click="deselectSection()"><x-ui.t fil="Alisin sa pagkakapili" en="Deselect" /></button>
                    </div>

                    <div x-show="canRemoveSelectedBlock" x-cloak class="pt-0.5">
                        <button type="button" class="doc-spacing-mini-btn justify-center w-full text-red-600 border-red-200" @click="removeSelectedBlock()">
                            <i data-lucide="trash-2" class="w-3 h-3"></i> <x-ui.t fil="Alisin ang Block" en="Remove Block" />
                        </button>
                    </div>
                </div>
            </template>

            <div class="doc-spacing-warning" x-show="documentOverflow" x-cloak>
                <i data-lucide="triangle-alert" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                <p class="text-[11px] text-amber-700"><x-ui.t fil="Ang nilalaman ng dokumento ay lumampas sa isang pahinang A4." en="Document content exceeds one A4 page." /></p>
            </div>
        </div>
    </div>
</div>
