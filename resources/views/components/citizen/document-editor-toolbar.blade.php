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
     saveDocumentEdits(). --}}
<div
    class="doc-editor-toolbar dp-no-print flex items-center gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5 mb-3"
    x-show="editingDocument"
    x-cloak
>
    <button type="button" class="doc-toolbar-btn" title="Undo" @mousedown.prevent="execCmd('undo')"><i data-lucide="undo-2" class="w-3.5 h-3.5"></i></button>
    <button type="button" class="doc-toolbar-btn" title="Redo" @mousedown.prevent="execCmd('redo')"><i data-lucide="redo-2" class="w-3.5 h-3.5"></i></button>

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

    <span class="doc-toolbar-sep"></span>

    <button type="button" class="doc-toolbar-btn" :class="activePanel === 'spacing' ? 'bg-slate-200' : ''" title="Adjust spacing between sections" @click="activePanel = activePanel === 'spacing' ? null : 'spacing'">
        <i data-lucide="move-vertical" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Spacing</span>
    </button>
    <button type="button" class="doc-toolbar-btn" :class="activePanel === 'seals' ? 'bg-slate-200' : ''" title="Replace or adjust the header seals" @click="activePanel = activePanel === 'seals' ? null : 'seals'; refreshSealOffsets()">
        <i data-lucide="shield" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Header Seals</span>
    </button>
    <button type="button" class="doc-toolbar-btn" :class="activePanel === 'background' ? 'bg-slate-200' : ''" title="Change the paper's watermark and background color" @click="activePanel = activePanel === 'background' ? null : 'background'">
        <i data-lucide="image" class="w-3.5 h-3.5"></i> <span class="text-[11px] font-medium">Background</span>
    </button>

    <span class="flex-1 min-w-2"></span>

    <button type="button" class="doc-toolbar-btn text-slate-600" @click="cancelDocumentEdits()">Cancel</button>
    <button type="button" class="doc-toolbar-btn bg-brand-600 text-white border-brand-600 hover:bg-brand-700" @click="saveDocumentEdits()">Save Changes</button>
</div>
