{{-- Reusable rich-text formatting toolbar for the Dokyu "Edit Document"
     editor — shared by every Dokyu paper, not specific to any one docKey.
     Sits outside #dp-print-area (dp-no-print) so it never appears in Print
     or the downloaded PDF.

     Every control acts on the browser's current text selection inside the
     nearest contenteditable .doc-content via document.execCommand(). Since
     these controls live outside that contenteditable region, clicking or
     focusing them would normally collapse the live selection before the
     command ever runs — buttons use @mousedown.prevent (never take focus
     at all, so the selection never moves); the font/size <select> and the
     color <input> can't use .prevent without breaking their own native
     picker, so they instead save the selection on mousedown (fires before
     focus moves) and restore it right before applying the command on
     change/input (see resources/js/dokyu-editor.js saveSelection() /
     restoreSelection()).

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, execCmd(cmd), applyFontFamily(family),
     applyFontSize(px), applyColor(hex), resetSelectionFormatting(),
     increaseIndentAtCaret(), decreaseIndentAtCaret() (the same two methods
     the document's own Tab/Shift+Tab handling calls — see
     handleContentKeydown() in document-requests.blade.php), saveSelection(),
     restoreSelection(), activePanel, cancelDocumentEdits(),
     saveDocumentEdits(). Undo/Redo call undo()/redo()/canUndo()/canRedo()
     (the shared snapshot history — see its own comment above execCmd() in
     document-requests.blade.php), not execCmd('undo'/'redo')/
     document.execCommand — the native contenteditable undo stack has no
     visibility into drag/seal/background changes, only typing.

     Two groups, not one long row: formatting controls (icon-only, so they
     tolerate shrinking/scrolling fine) versus document actions (Spacing/
     Header Seals/Background/Cancel/Save Changes — every one has a text
     label that must stay fully readable). The old single flex row relied
     on overflow-x-auto alone, but overflow only kicks in once a flex item
     hits its own min-content size — it does nothing to stop shrinking
     BEFORE that. .doc-toolbar-btn sets white-space:nowrap (so its label
     can't wrap to escape) but never set flex-shrink:0, so at normal modal
     width the browser's default flex-shrink:1 kept squeezing the text
     buttons' boxes narrower than their own non-wrapping labels — the text
     itself doesn't shrink with the box, so it visually spilled into the
     next button ("SpacingHeader Seals..."). shrink-0 on the actions group
     and every button in it is the actual fix; splitting the groups (with
     flex-wrap on the outer row and ml-auto on the actions group) is what
     lets that group drop to its own line at normal width instead of
     fighting the formatting controls for space, while both groups still
     sit on one row automatically in Full View's own extra width — no
     Full-View-specific styling needed, flex-wrap already only wraps when
     the row actually runs out of room. --}}
<div
    class="doc-editor-toolbar dp-no-print flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5 mb-3"
    x-show="editingDocument"
    x-cloak
>
    <div class="flex items-center gap-1 overflow-x-auto min-w-0">
        <button type="button" class="doc-toolbar-btn" title="Undo (Ctrl+Z)" :class="!canUndo() ? 'opacity-40 cursor-not-allowed' : ''" :disabled="!canUndo()" @mousedown.prevent="undo()"><i data-lucide="undo-2" class="w-3.5 h-3.5"></i></button>
        <button type="button" class="doc-toolbar-btn" title="Redo (Ctrl+Y)" :class="!canRedo() ? 'opacity-40 cursor-not-allowed' : ''" :disabled="!canRedo()" @mousedown.prevent="redo()"><i data-lucide="redo-2" class="w-3.5 h-3.5"></i></button>

        <span class="doc-toolbar-sep"></span>

        <select class="doc-toolbar-select" title="Font family" @mousedown="saveSelection()" @change="applyFontFamily($event.target.value)">
            <option value="" selected disabled>Font</option>
            <option value="Arial, sans-serif">Arial</option>
            <option value="'Times New Roman', serif">Times New Roman</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="Verdana, sans-serif">Verdana</option>
            <option value="Tahoma, sans-serif">Tahoma</option>
            <option value="'Courier New', monospace">Courier New</option>
            <option value="serif">Serif</option>
            <option value="sans-serif">Sans-serif</option>
        </select>

        <select class="doc-toolbar-select w-16" title="Font size" @mousedown="saveSelection()" @change="applyFontSize($event.target.value)">
            <option value="" selected disabled>Size</option>
            <template x-for="s in [8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 32]" :key="s">
                <option :value="s" x-text="s"></option>
            </template>
        </select>

        <span class="doc-toolbar-sep"></span>

        <button type="button" class="doc-toolbar-btn font-bold" title="Bold" @mousedown.prevent="execCmd('bold')">B</button>
        <button type="button" class="doc-toolbar-btn italic" title="Italic" @mousedown.prevent="execCmd('italic')">I</button>
        <button type="button" class="doc-toolbar-btn underline" title="Underline" @mousedown.prevent="execCmd('underline')">U</button>
        <button type="button" class="doc-toolbar-btn line-through" title="Strikethrough" @mousedown.prevent="execCmd('strikeThrough')">S</button>

        <span class="doc-toolbar-sep"></span>

        <label class="doc-toolbar-btn p-0.5 cursor-pointer" title="Text color">
            <input type="color" value="#000000" class="w-5 h-5 border-0 p-0 bg-transparent cursor-pointer" @mousedown="saveSelection()" @input="applyColor($event.target.value)">
        </label>

        <span class="doc-toolbar-sep"></span>

        <button type="button" class="doc-toolbar-btn" title="Align left" @mousedown.prevent="execCmd('justifyLeft')"><i data-lucide="align-left" class="w-3.5 h-3.5"></i></button>
        <button type="button" class="doc-toolbar-btn" title="Align center" @mousedown.prevent="execCmd('justifyCenter')"><i data-lucide="align-center" class="w-3.5 h-3.5"></i></button>
        <button type="button" class="doc-toolbar-btn" title="Align right" @mousedown.prevent="execCmd('justifyRight')"><i data-lucide="align-right" class="w-3.5 h-3.5"></i></button>
        <button type="button" class="doc-toolbar-btn" title="Justify" @mousedown.prevent="execCmd('justifyFull')"><i data-lucide="align-justify" class="w-3.5 h-3.5"></i></button>

        <span class="doc-toolbar-sep"></span>

        <button type="button" class="doc-toolbar-btn" title="Decrease indent (Shift+Tab)" @mousedown.prevent="decreaseIndentAtCaret()"><i data-lucide="indent-decrease" class="w-3.5 h-3.5"></i></button>
        <button type="button" class="doc-toolbar-btn" title="Increase indent (Tab)" @mousedown.prevent="increaseIndentAtCaret()"><i data-lucide="indent-increase" class="w-3.5 h-3.5"></i></button>

        <span class="doc-toolbar-sep"></span>

        <button type="button" class="doc-toolbar-btn" title="Reset formatting on selected text" @mousedown.prevent="resetSelectionFormatting()"><i data-lucide="eraser" class="w-3.5 h-3.5"></i></button>
    </div>

    <div class="flex flex-wrap items-center gap-2 shrink-0 ml-auto">
        <button type="button" class="doc-toolbar-btn shrink-0" :class="activePanel === 'spacing' ? 'bg-slate-200' : ''" title="Adjust spacing between sections" @click="activePanel = activePanel === 'spacing' ? null : 'spacing'">
            <i data-lucide="move-vertical" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Spacing</span>
        </button>
        <button type="button" class="doc-toolbar-btn shrink-0" :class="activePanel === 'seals' ? 'bg-slate-200' : ''" title="Replace or adjust the header seals" @click="activePanel = activePanel === 'seals' ? null : 'seals'; refreshSealOffsets()">
            <i data-lucide="shield" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Header Seals</span>
        </button>
        <button type="button" class="doc-toolbar-btn shrink-0" :class="activePanel === 'background' ? 'bg-slate-200' : ''" title="Change the paper's watermark and background color" @click="activePanel = activePanel === 'background' ? null : 'background'">
            <i data-lucide="image" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Background</span>
        </button>

        <span class="doc-toolbar-sep"></span>

        <button type="button" class="doc-toolbar-btn shrink-0 text-slate-600" @click="cancelDocumentEdits()">Cancel</button>
        <button type="button" class="doc-toolbar-btn shrink-0 bg-brand-600 text-white border-brand-600 hover:bg-brand-700" @click="saveDocumentEdits()">Save Changes</button>
    </div>
</div>
