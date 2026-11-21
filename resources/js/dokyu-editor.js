// Shared engine for the Dokyu "Edit Document" rich-text editor (see
// resources/views/admin/document-requests.blade.php, document-editor-toolbar.blade.php,
// document-editor-background-panel.blade.php). Plain DOM helpers rather than
// an Alpine component, since the actual editable region is a native
// contenteditable element — browser-native execCommand()/Selection/Range
// APIs are the simplest reliable way to build a lightweight Word-style
// toolbar without pulling in a full rich-text-editor dependency (TipTap,
// Quill, CKEditor, etc.), which this admin-only, print-focused use case
// doesn't need.
//
// execCommand is deprecated in the HTML spec but still fully supported by
// every evergreen browser (Chrome/Edge/Firefox/Safari) for exactly this
// use case, and there is no drop-in modern replacement with equivalent
// simplicity — the alternative (hand-rolling Selection/Range mutation for
// every formatting command) is a materially larger, more fragile
// undertaking for an internal tool that only needs to run in one browser.

const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

// One shared module-level "clipboard" for the current text selection.
// Toolbar controls live *outside* the contenteditable region, so clicking
// or focusing them collapses the browser's live selection before a
// formatting command ever runs. saveSelection() is called on mousedown
// (fires before focus moves) and restoreSelection() right before the
// formatting command applies, re-establishing the exact same Range.
let savedRange = null;

export function saveSelection(editorEl) {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && editorEl && editorEl.contains(sel.anchorNode)) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
}

export function restoreSelection() {
    if (!savedRange) return;
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
}

// execCommand has no arbitrary-pixel font-size command (only 7 legacy HTML
// <font size> steps) — the standard workaround is to apply size "7" (the
// largest legacy step, so it's unambiguous to find again) then replace the
// <font size="7"> wrapper(s) it created with a real inline font-size style.
export function applyFontSize(editorEl, px) {
    document.execCommand('fontSize', false, '7');
    editorEl.querySelectorAll('font[size="7"]').forEach((el) => {
        el.removeAttribute('size');
        el.style.fontSize = px + 'px';
    });
}

// Captures the CURRENT resolved state of a document's rendered .doc-content
// (the letterhead/body/signature markup produced by whichever docKey
// template is active — barangay-certificate-preview.blade.php or
// municipal-request-preview.blade.php) into a plain, self-contained HTML
// string the admin can then edit freely via contenteditable. This is what
// lets one editor engine work across every Dokyu paper without knowing
// anything about its particular structure: it edits whatever was actually
// rendered, not a generic assumed layout.
//
// Strips the Alpine-only plumbing that has no meaning once frozen:
//   - <template> branches for every OTHER docKey (Alpine's x-if leaves the
//     inactive branches as inert <template> tags in the DOM; only the
//     currently-matching branch was ever cloned out as real, visible markup)
//   - the now-redundant shadow <input class="doc-field-input"> that used to
//     sit next to each editable span for the old field-level editor — once
//     the whole paper is directly editable there is nothing left for that
//     input to do
//   - x-show/x-text/x-model/x-cloak/x-if/x-data attributes, whose current
//     effect (an inline display:none, or resolved text content) is already
//     baked into the DOM being cloned, so the attributes themselves are
//     dead weight afterward
export function captureContent(el) {
    const clone = el.cloneNode(true);
    clone.querySelectorAll('template').forEach((node) => node.remove());
    clone.querySelectorAll('input.doc-field-input').forEach((node) => node.remove());
    clone.querySelectorAll('.doc-drag-handle').forEach((node) => node.remove());
    clone.querySelectorAll('[x-show], [x-text], [x-model], [x-cloak], [x-if], [x-data]').forEach((node) => {
        ['x-show', 'x-text', 'x-model', 'x-cloak', 'x-if', 'x-data'].forEach((attr) => node.removeAttribute(attr));
    });
    return clone.innerHTML;
}

// Defensive cleanup applied when persisting the admin's edited HTML back
// into the request's demo state (see saveDocumentEdits() in
// document-requests.blade.php) — contenteditable + paste can't normally
// introduce a live <script>, but since this string later gets re-injected
// verbatim via x-html, stripping it is a cheap guarantee either way. Also
// strips any drag handle (see createDragHandleElement() below) — it's an
// editor-only affordance injected straight into the live DOM as a child of
// whichever section is currently hovered/selected, purely so it inherits
// that section's own position:relative positioning for free; it must never
// become part of the saved document.
export function cleanHtml(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    wrapper.querySelectorAll('script').forEach((node) => node.remove());
    wrapper.querySelectorAll('.doc-drag-handle').forEach((node) => node.remove());
    return wrapper.innerHTML;
}

// Before the edited copy is sent to be stored: each icon Lucide drew (an <svg
// data-lucide>) goes back to the <i data-lucide> placeholder it came from, and
// is drawn again when the copy is shown. The server stores no SVG at all.
export function iconsToPlaceholders(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    wrapper.querySelectorAll('svg[data-lucide]').forEach((svg) => {
        const i = document.createElement('i');
        i.setAttribute('data-lucide', svg.getAttribute('data-lucide'));
        const keep = (svg.getAttribute('class') || '').split(/\s+/).filter((c) => c && c !== 'lucide' && !c.startsWith('lucide-'));
        if (keep.length) i.setAttribute('class', keep.join(' '));
        svg.replaceWith(i);
    });
    return wrapper.innerHTML;
}

const WATERMARK_SIZE_WIDTH = { small: '34%', medium: '50%', large: '68%' };

const WATERMARK_POSITION_STYLE = {
    center: 'top:50%;left:50%;right:auto;bottom:auto;transform:translate(-50%,-50%);',
    top: 'top:6%;left:50%;right:auto;bottom:auto;transform:translate(-50%,0);',
    bottom: 'bottom:6%;left:50%;right:auto;top:auto;transform:translate(-50%,0);',
    left: 'top:50%;left:6%;right:auto;bottom:auto;transform:translate(0,-50%);',
    right: 'top:50%;right:6%;left:auto;bottom:auto;transform:translate(0,-50%);',
};

// Background config shape: { src, opacity (0-20), size ('small'|'medium'|'large'), position, paperColor }
export function watermarkStyle(bg) {
    const width = WATERMARK_SIZE_WIDTH[bg?.size] || WATERMARK_SIZE_WIDTH.large;
    const posStyle = WATERMARK_POSITION_STYLE[bg?.position] || WATERMARK_POSITION_STYLE.center;
    const opacityPct = Math.max(0, Math.min(20, Number(bg?.opacity ?? 5)));
    return `position:absolute;${posStyle}width:${width};max-width:620px;height:auto;aspect-ratio:1/1;object-fit:contain;opacity:${opacityPct / 100};z-index:0;pointer-events:none;`;
}

export function paperStyle(bg) {
    const color = bg?.paperColor || '#FFFFFF';
    return `font-family: Georgia, 'Times New Roman', serif; background-color: ${color};`;
}

// --- Section spacing / movement -------------------------------------------
//
// Operates generically on whatever block-level element the admin clicks
// inside .doc-content — no per-docKey knowledge needed. A block is either
// one of .doc-content's own direct children (the major sections every
// template already authors as separate divs: letterhead, title, body,
// signatures, footer) or, for finer control, a paragraph-level element
// nested inside one of those (e.g. one certificate paragraph). Either way
// the mechanism is identical: read/adjust inline margin-top, margin-bottom
// and line-height, or swap the element with an adjacent sibling.

export const SPACING_MIN = 0;
export const SPACING_MAX = 100;
export const LINE_HEIGHT_MIN = 1.0;
export const LINE_HEIGHT_MAX = 2.5;
const LINE_HEIGHT_STEP = 0.1;
const SELECTABLE_TAGS = ['DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'UL', 'OL', 'TABLE'];

// Walks up from a click target to the nearest element that's reasonable to
// treat as a standalone block — never the watermark (a sibling of
// .doc-content, so this never matters in practice) and never .doc-content
// itself (there's always something more specific to select first). A click
// that lands on the drag handle itself (see createDragHandleElement()
// below) skips straight past it to the section it's attached to, rather
// than treating the handle as "the section" (which — since it's a plain
// DIV — would otherwise satisfy SELECTABLE_TAGS on the very first check).
export function findSelectableBlock(target, root) {
    let el = target;
    while (el && el !== root) {
        if (el.classList && el.classList.contains('doc-drag-handle')) {
            el = el.parentElement;
            continue;
        }
        if (SELECTABLE_TAGS.includes(el.tagName)) return el;
        el = el.parentElement;
    }
    return null;
}

function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

export function readSpacing(el) {
    const style = el.style;
    const before = parseInt(style.marginTop, 10);
    const after = parseInt(style.marginBottom, 10);
    const lineHeight = parseFloat(style.lineHeight);
    return {
        before: Number.isFinite(before) ? before : 0,
        after: Number.isFinite(after) ? after : 0,
        lineHeight: Number.isFinite(lineHeight) ? lineHeight : 1.0,
    };
}

export function setSpacingBefore(el, px) {
    el.style.marginTop = clamp(Math.round(px), SPACING_MIN, SPACING_MAX) + 'px';
}

export function setSpacingAfter(el, px) {
    el.style.marginBottom = clamp(Math.round(px), SPACING_MIN, SPACING_MAX) + 'px';
}

export function setLineHeight(el, value) {
    el.style.lineHeight = String(clamp(Math.round(value * 10) / 10, LINE_HEIGHT_MIN, LINE_HEIGHT_MAX));
}

export function lineHeightStep() {
    return LINE_HEIGHT_STEP;
}

export function canMove(el, root) {
    if (!el || !el.parentElement) return { up: false, down: false };
    // Never move a block out from under .doc-content itself, and never
    // reorder relative to a sibling that isn't a real element (stray text
    // nodes from contenteditable typing sit alongside real blocks).
    let prev = el.previousElementSibling;
    let next = el.nextElementSibling;
    return { up: !!prev, down: !!next };
}

// --- Paragraph indentation (Tab / Shift+Tab / toolbar Increase-Decrease
// Indent) -----------------------------------------------------------------
//
// One shared mechanism for both entry points (see increaseIndentAtCaret()/
// decreaseIndentAtCaret()/handleContentKeydown() in document-requests
// .blade.php), rather than leaving the toolbar on execCommand('indent')/
// ('outdent') while Tab does something else — Chromium's native indent
// command nests <blockquote> elements with its own fixed 40px step and no
// built-in maximum, which doesn't give a predictable, capped "how far can
// this go" the way plain margin-left does. margin-left (not text-indent) is
// used deliberately: text-indent only shifts a block's first line, but
// Word's own Tab-at-a-paragraph behavior (and the toolbar's existing
// indent icons, which this replaces) shifts the WHOLE paragraph block —
// matching that is more useful for a certificate paragraph that wraps
// across multiple lines.
export const INDENT_STEP = 24;
export const INDENT_MAX = 144;

// Finds the same kind of block findSelectableBlock() would (a paragraph or
// other block-level element inside .doc-content), but starting from
// wherever the text caret/selection currently is rather than a click
// target — this is what lets Tab apply to "the paragraph I'm typing in"
// without the admin needing to click anything first.
export function getCurrentBlock(contentEl) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0 || !contentEl || !contentEl.contains(sel.anchorNode)) return null;
    return findSelectableBlock(sel.getRangeAt(0).startContainer, contentEl);
}

export function readIndent(el) {
    const ml = parseInt(el.style.marginLeft, 10);
    return Number.isFinite(ml) ? ml : 0;
}

export function increaseIndent(el) {
    const next = clamp(readIndent(el) + INDENT_STEP, 0, INDENT_MAX);
    el.style.marginLeft = next === 0 ? '' : next + 'px';
    return next;
}

export function decreaseIndent(el) {
    const next = clamp(readIndent(el) - INDENT_STEP, 0, INDENT_MAX);
    el.style.marginLeft = next === 0 ? '' : next + 'px';
    return next;
}

// Free vertical repositioning — deliberately separate from
// setSpacingBefore/After above. Margin only ever pushes/pulls flow
// neighbors, which fights with a block that has its own margin-top:auto
// (e.g. a signature row pinned to the bottom of a flex column) — the auto
// margin keeps recalculating to fill whatever space is left, so a small
// margin change barely moves it, or a naive negative margin can collapse
// unpredictably against a following auto-margin section. transform:translateY
// is a pure paint-time offset: it never changes the element's own layout
// box or reserved flow space, so it slides the block visually without
// disturbing the auto-margin calculation or its siblings' positions at all
// — exactly the "move this whole row into the empty space above/below it"
// behavior a signature block pinned by mt-auto needs, without needing to
// out-fight that mechanism directly.
export const VERTICAL_OFFSET_MIN = -300;
export const VERTICAL_OFFSET_MAX = 300;
// Widened from the original ±50px "small nudge" range now that dragging
// moves both axes as a primary interaction, not just a fine-tune-only X
// control — matches the vertical safety cap so a block can be dragged as
// meaningfully left/right as it can up/down. The dynamic per-element bounds
// below (computeHorizontalBounds) are what actually keeps it on the page in
// practice; this is just the outer safety ceiling.
export const HORIZONTAL_OFFSET_MIN = -300;
export const HORIZONTAL_OFFSET_MAX = 300;

export function readVerticalOffset(el) {
    const match = /translateY\(\s*(-?\d+(?:\.\d+)?)px\s*\)/.exec(el.style.transform || '');
    return match ? Number(match[1]) : 0;
}

export function readHorizontalOffset(el) {
    const match = /translateX\(\s*(-?\d+(?:\.\d+)?)px\s*\)/.exec(el.style.transform || '');
    return match ? Number(match[1]) : 0;
}

// Both axes live in the SAME transform property (translateX(...) translateY(...),
// composed — order doesn't matter for pure translation), so setting either one
// always re-reads and preserves whatever the other axis currently holds,
// rather than overwriting the whole transform string.
export function setVerticalOffset(el, px) {
    const clamped = clamp(Math.round(px), VERTICAL_OFFSET_MIN, VERTICAL_OFFSET_MAX);
    writeOffsetTransform(el, readHorizontalOffset(el), clamped);
}

export function setHorizontalOffset(el, px) {
    const clamped = clamp(Math.round(px), HORIZONTAL_OFFSET_MIN, HORIZONTAL_OFFSET_MAX);
    writeOffsetTransform(el, clamped, readVerticalOffset(el));
}

function writeOffsetTransform(el, x, y) {
    const parts = [];
    if (x) parts.push(`translateX(${x}px)`);
    if (y) parts.push(`translateY(${y}px)`);
    el.style.transform = parts.join(' ');
}

// How far a section's own translateY(0) top/bottom edge could move before
// it would push past containerEl's top/bottom — i.e. the actual usable
// drag/nudge range for THIS specific element inside THIS specific
// container, intersected with the fixed VERTICAL_OFFSET_MIN/MAX safety cap.
// Used for every positioning control (drag, the Move Up/Down buttons, the
// slider, and the direct numeric input), not just dragging, so a section
// can never be pushed out of the printable area through any of them.
//
// getBoundingClientRect() already reflects every ancestor transform
// (including the zoom scale — see the zoom exports further below), so
// naturalTop first "undoes" the element's OWN current offset (scaled by
// zoomFactor, since that's how much the element's local translateY actually
// moves it on screen once its scaled ancestor is accounted for) to find
// where it would render at translateY(0); the resulting min/max screen-space
// deltas are then divided back down by zoomFactor to land in the same
// unscaled document-space unit every stored offset uses — so the returned
// bounds are identical regardless of the zoom level they were computed at.
export function computeOffsetBounds(el, containerEl, zoomFactor) {
    if (!el || !containerEl) return { min: VERTICAL_OFFSET_MIN, max: VERTICAL_OFFSET_MAX };
    const factor = zoomFactor || 1;
    const currentOffset = readVerticalOffset(el);
    const elRect = el.getBoundingClientRect();
    const containerRect = containerEl.getBoundingClientRect();
    const naturalTop = elRect.top - currentOffset * factor;
    const naturalBottom = naturalTop + elRect.height;
    const min = (containerRect.top - naturalTop) / factor;
    const max = (containerRect.bottom - naturalBottom) / factor;
    return {
        min: clamp(Math.round(min), VERTICAL_OFFSET_MIN, VERTICAL_OFFSET_MAX),
        max: clamp(Math.round(max), VERTICAL_OFFSET_MIN, VERTICAL_OFFSET_MAX),
    };
}

// Same reasoning as computeOffsetBounds() above, mirrored onto the
// horizontal axis (left/right instead of top/bottom) — lets a block be
// dragged right up against either paper margin without ever pushing it
// past the printable area, while still allowing genuinely tight-to-the-edge
// placement when that's what the admin wants.
//
// Most certificate paragraphs are block-level and already stretch to fill
// the full width of .doc-content (their own width is ~equal to the
// container's), unlike sections vertically, which are almost always much
// shorter than the container's height. Requiring BOTH the left and right
// edges to stay inside the container — the same rule computeOffsetBounds
// uses for top/bottom — collapses to a zero-or-negative range for any
// block that's already as wide as its container, making horizontal
// dragging impossible for exactly the full-width text blocks admins most
// want to nudge left/right. When the element doesn't have meaningfully
// less width than its container, fall back to the fixed safety cap instead
// of the degenerate container-fit range, intentionally trading "both edges
// always stay inside" for "the block can still be moved a meaningful
// amount," accepting that it may extend past the margin on the side it's
// pushed toward.
export function computeHorizontalBounds(el, containerEl, zoomFactor) {
    if (!el || !containerEl) return { min: HORIZONTAL_OFFSET_MIN, max: HORIZONTAL_OFFSET_MAX };
    const factor = zoomFactor || 1;
    const currentOffset = readHorizontalOffset(el);
    const elRect = el.getBoundingClientRect();
    const containerRect = containerEl.getBoundingClientRect();
    if (elRect.width >= containerRect.width) {
        return { min: HORIZONTAL_OFFSET_MIN, max: HORIZONTAL_OFFSET_MAX };
    }
    const naturalLeft = elRect.left - currentOffset * factor;
    const naturalRight = naturalLeft + elRect.width;
    const min = (containerRect.left - naturalLeft) / factor;
    const max = (containerRect.right - naturalRight) / factor;
    return {
        min: clamp(Math.round(min), HORIZONTAL_OFFSET_MIN, HORIZONTAL_OFFSET_MAX),
        max: clamp(Math.round(max), HORIZONTAL_OFFSET_MIN, HORIZONTAL_OFFSET_MAX),
    };
}

// --- Drag-and-drop section positioning --------------------------------------
//
// A thin pointer-tracking layer on top of the same setVerticalOffset() the
// numeric Spacing panel controls already use — dragging never becomes a
// separate storage mechanism, it just updates the same inline transform in
// real time, so Save/Cancel/Print/PDF need no drag-specific handling at all.
//
// The handle is a plain, unattached DOM node — creating it here keeps the
// markup/CSS class name in one place, but wiring pointerdown/keydown and
// appending it to a specific section is left to the caller (document-requests
// .blade.php), since that's where the Alpine `this` needed to call back into
// selection/adjustment methods actually lives.
export function createDragHandleElement() {
    const handle = document.createElement('div');
    handle.className = 'doc-drag-handle dp-no-print';
    handle.setAttribute('contenteditable', 'false');
    handle.setAttribute('draggable', 'false');
    handle.setAttribute('tabindex', '0');
    handle.setAttribute('role', 'button');
    handle.setAttribute('aria-label', 'Drag to reposition this section');
    handle.title = 'Drag to reposition this section (or focus and press Arrow keys)';
    handle.textContent = '⠿';
    return handle;
}

// Module-level rather than per-call state since only one drag can possibly
// be in progress at a time — mirrors the same pattern saveSelection()/
// restoreSelection() use above for the same reason.
let activeDrag = null;

export function startSectionDrag(el, pointerEvent, containerEl, zoomLevel) {
    const factor = (zoomLevel || 100) / 100;
    activeDrag = {
        el,
        startClientX: pointerEvent.clientX,
        startClientY: pointerEvent.clientY,
        startOffsetX: readHorizontalOffset(el),
        startOffsetY: readVerticalOffset(el),
        zoomFactor: factor,
        boundsX: computeHorizontalBounds(el, containerEl, factor),
        boundsY: computeOffsetBounds(el, containerEl, factor),
    };
}

// Returns the new {x, y} offset (already applied to the element) so the
// caller can mirror both axes into its own reactive display state live —
// or null if no drag is active (defensive — a pointermove arriving after
// pointerup already ended the drag should be a harmless no-op). Each axis
// is computed and clamped independently from the same single pointer
// position, which is what makes diagonal dragging fall naturally out of
// this rather than needing special-case handling.
export function updateSectionDrag(pointerEvent) {
    if (!activeDrag) return null;
    const deltaXScreen = pointerEvent.clientX - activeDrag.startClientX;
    const deltaYScreen = pointerEvent.clientY - activeDrag.startClientY;
    const nextX = clamp(Math.round(activeDrag.startOffsetX + deltaXScreen / activeDrag.zoomFactor), activeDrag.boundsX.min, activeDrag.boundsX.max);
    const nextY = clamp(Math.round(activeDrag.startOffsetY + deltaYScreen / activeDrag.zoomFactor), activeDrag.boundsY.min, activeDrag.boundsY.max);
    writeOffsetTransform(activeDrag.el, nextX, nextY);
    return { x: nextX, y: nextY };
}

export function endSectionDrag() {
    activeDrag = null;
}

export function moveElement(el, direction) {
    if (direction === 'up') {
        const prev = el.previousElementSibling;
        if (prev) el.parentElement.insertBefore(el, prev);
    } else if (direction === 'down') {
        const next = el.nextElementSibling;
        if (next) el.parentElement.insertBefore(next, el);
    }
}

// Compares the paper's actual height against what its own width implies an
// A4 page's height should be (297 / 210 ratio) — resolution-independent, so
// it works regardless of how large the modal happens to render the on-screen
// preview. A small tolerance avoids flagging normal sub-pixel rounding.
const A4_RATIO = 297 / 210;
const OVERFLOW_TOLERANCE = 1.02;

export function checkOverflow(paperEl) {
    if (!paperEl || !paperEl.clientWidth) return false;
    return paperEl.scrollHeight > paperEl.clientWidth * A4_RATIO * OVERFLOW_TOLERANCE;
}

// --- Header seal editing ----------------------------------------------------
//
// Deliberately separate from the large background watermark (watermarkStyle()
// above) — these are the two small letterhead seals every barangay/municipal
// template already renders (see the header-seal / header-seal--left / --right
// classes added in barangay-certificate-preview.blade.php and
// municipal-request-preview.blade.php). Sized/positioned via inline style so
// the change survives being captured into customDocumentHtml exactly like
// any other admin edit.
//
// These operate on the seal's WRAPPER element (.header-seal-wrap--left/right
// in the templates above), not the <img> inside it. Each seal is really two
// stacked layers sharing one box: a decorative placeholder circle (visible
// only once the real seal is removed or fails to load) behind an <img> that's
// styled to fill 100% of the wrapper. Sizing/moving the wrapper scales and
// repositions both layers as one unit — sizing/moving the <img> alone left
// the placeholder both a stale fixed size (the browser's own img max-width
// reset then let one axis grow past the other, turning "Large" into an
// oval) and a stale fixed position (so moving the image uncovered the
// placeholder sitting behind it, looking like a leftover ghost circle).
export const SEAL_SIZE_PX = { small: 48, medium: 64, large: 80 };
export const SEAL_OFFSET_MIN = -50;
export const SEAL_OFFSET_MAX = 50;

export function applySealSize(wrapperEl, sizeKey) {
    const px = SEAL_SIZE_PX[sizeKey] || SEAL_SIZE_PX.medium;
    wrapperEl.style.width = px + 'px';
    wrapperEl.style.height = px + 'px';
}

export function readSealOffset(wrapperEl) {
    const match = /translate\(\s*(-?\d+(?:\.\d+)?)px\s*,\s*(-?\d+(?:\.\d+)?)px\s*\)/.exec(wrapperEl.style.transform || '');
    return { x: match ? Number(match[1]) : 0, y: match ? Number(match[2]) : 0 };
}

export function applySealOffset(wrapperEl, x, y) {
    const cx = clamp(Math.round(x), SEAL_OFFSET_MIN, SEAL_OFFSET_MAX);
    const cy = clamp(Math.round(y), SEAL_OFFSET_MIN, SEAL_OFFSET_MAX);
    wrapperEl.style.transform = `translate(${cx}px, ${cy}px)`;
}

// Validates and reads an uploaded watermark image as a data URL — kept as
// a data URL (no server round-trip) since this project's document data is
// itself frontend-only demo/local state (see r.formSnapshot elsewhere in
// document-requests.blade.php); this is the same tier, not a new backend.
export function readImageFile(file) {
    return new Promise((resolve) => {
        if (!file || !file.type || !file.type.startsWith('image/')) {
            resolve({ ok: false, error: 'Please choose an image file (PNG, JPG, etc.).' });
            return;
        }
        if (file.size > MAX_IMAGE_BYTES) {
            resolve({ ok: false, error: 'That image is too large — please choose a file under 5MB.' });
            return;
        }
        const reader = new FileReader();
        reader.onload = () => resolve({ ok: true, dataUrl: reader.result });
        reader.onerror = () => resolve({ ok: false, error: 'Could not read that image file.' });
        reader.readAsDataURL(file);
    });
}

// --- Viewer zoom -------------------------------------------------------------
//
// A pure display feature — never touches the document's actual A4
// dimensions, font sizes, or any of the section/seal/watermark styles
// above. Implemented as transform:scale() on a dedicated .doc-zoom-scale
// wrapper (never on .doc-paper itself, so print/PDF can neutralize it with
// one CSS rule — see body.dp-printing .doc-zoom-scale in app.css — without
// touching any other transform, like a moved section or a repositioned
// seal, that happens to live inside it). Browsers hit-test a transformed
// element at its POST-transform visual position, so clicking/selecting
// text and sections keeps working correctly at any zoom level with no
// extra coordinate math.
//
// The scaled element's own offsetWidth/offsetHeight are its NATURAL
// (pre-transform) size — transform never changes an element's own layout
// box, only how it paints and hit-tests — so reading them straight off
// .doc-zoom-scale, at any current zoom level, is all computeZoomSizerSize()
// needs to figure out how much space the *scaled* result should reserve in
// its scrollable viewport (see recomputeZoomSizer() in document-requests
// .blade.php). This same transform-invariance is why checkOverflow() above
// keeps reading real, unscaled A4 proportions regardless of viewer zoom.
export const ZOOM_LEVELS = [50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200];
export const ZOOM_MIN = ZOOM_LEVELS[0];
export const ZOOM_MAX = ZOOM_LEVELS[ZOOM_LEVELS.length - 1];

export function nextZoomLevel(current, direction) {
    if (direction === 'in') {
        const next = ZOOM_LEVELS.find((level) => level > current);
        return next ?? ZOOM_MAX;
    }
    const levels = [...ZOOM_LEVELS].reverse();
    const prev = levels.find((level) => level < current);
    return prev ?? ZOOM_MIN;
}

export function clampZoom(level) {
    return clamp(Math.round(level), ZOOM_MIN, ZOOM_MAX);
}

export function computeZoomSizerSize(scaleEl, zoomLevel) {
    if (!scaleEl) return { width: 0, height: 0 };
    const factor = zoomLevel / 100;
    return { width: scaleEl.offsetWidth * factor, height: scaleEl.offsetHeight * factor };
}

// Largest zoom (capped at 200%, the top of ZOOM_LEVELS) that fits the
// paper's natural size inside the given viewport box, with a small margin
// so the page doesn't touch the scrollable container's edge.
export function computeFitZoom(scaleEl, viewportWidth, viewportHeight) {
    if (!scaleEl || !scaleEl.offsetWidth || !scaleEl.offsetHeight) return 100;
    const margin = 32;
    const availableWidth = Math.max(1, viewportWidth - margin);
    const availableHeight = Math.max(1, viewportHeight - margin);
    const fit = Math.min(availableWidth / scaleEl.offsetWidth, availableHeight / scaleEl.offsetHeight);
    return clampZoom(fit * 100);
}

// --- PDF export color sanitization -------------------------------------------
//
// html2canvas 1.4.1 (bundled inside html2pdf.js) only understands the color
// FUNCTIONS rgb/rgba/hsl/hsla (see its own SUPPORTED_COLOR_FUNCTIONS map) —
// it throws `Attempting to parse an unsupported color function "oklch"` the
// moment it meets one. Tailwind CSS v4 compiles its entire default palette
// as oklch(), so every element styled with a Tailwind color class fails
// Download PDF outright. Two normalization strategies were tried and
// verified against this browser directly before writing this fix:
//   1. Canvas 2D's fillStyle getter — commonly cited as "always normalizes
//      to rgb/hex" — actually echoes oklch() straight back unchanged in
//      current Chrome (confirmed by direct test), so it cannot be used to
//      convert the color.
//   2. A direct OKLCH -> OKLab -> linear sRGB -> sRGB math conversion (the
//      CSS Color 4 spec's own reference algorithm) does not depend on any
//      browser color-serialization quirk. Verified pixel-for-pixel
//      identical against this browser's own rendering of the same oklch
//      color (rendered a real pixel with each and diffed the RGBA output —
//      zero delta across multiple sample colors), so it reproduces exactly
//      what the browser would have painted.
// sanitizeExportColors() walks a cloned export document (meant to be called
// from html2canvas's own onclone callback, which hands back the *fully
// isolated* clone it is about to render — safe to mutate freely without
// ever touching the live, on-screen page) and rewrites every oklch-based
// computed color it finds as an equivalent inline rgb()/rgba() style.
const OKLCH_COLOR_PROPS = [
    'color',
    'backgroundColor',
    'borderTopColor',
    'borderRightColor',
    'borderBottomColor',
    'borderLeftColor',
    'outlineColor',
    'textDecorationColor',
    'caretColor',
];

// Captures each component's own optional "%" separately (group 2/4/7) so a
// percentage flag on one component (L is always a percentage in Tailwind's
// compiled output; C and H never are) is never confused with another's.
const OKLCH_PATTERN = /oklch\(\s*([\d.]+)(%)?\s+([\d.]+)(%)?\s+([\d.]+)(?:deg)?\s*(?:\/\s*([\d.]+)(%)?\s*)?\)/i;

function oklchToRgbString(value) {
    const match = OKLCH_PATTERN.exec(value);
    if (!match) return null;
    const L = match[2] ? Number(match[1]) / 100 : Number(match[1]);
    const C = match[4] ? (Number(match[3]) / 100) * 0.4 : Number(match[3]);
    const H = Number(match[5]);
    const A = match[6] === undefined ? 1 : match[7] ? Number(match[6]) / 100 : Number(match[6]);

    const h = (H * Math.PI) / 180;
    const a = C * Math.cos(h);
    const b = C * Math.sin(h);
    const l_ = L + 0.3963377774 * a + 0.2158037573 * b;
    const m_ = L - 0.1055613458 * a - 0.0638541728 * b;
    const s_ = L - 0.0894841775 * a - 1.2914855480 * b;
    const l = l_ ** 3;
    const m = m_ ** 3;
    const s = s_ ** 3;
    let r = 4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s;
    let g = -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s;
    let bl = -0.0041960863 * l - 0.7034186147 * m + 1.7076147010 * s;
    const encode = (c) => {
        c = clamp(c, 0, 1);
        return c <= 0.0031308 ? 12.92 * c : 1.055 * Math.pow(c, 1 / 2.4) - 0.055;
    };
    r = Math.round(encode(r) * 255);
    g = Math.round(encode(g) * 255);
    bl = Math.round(encode(bl) * 255);
    return A >= 1 ? `rgb(${r}, ${g}, ${bl})` : `rgba(${r}, ${g}, ${bl}, ${A})`;
}

const oklchConversionCache = new Map();

function toExportSafeColor(value) {
    if (!value || !value.includes('oklch')) return null;
    if (oklchConversionCache.has(value)) return oklchConversionCache.get(value);
    const converted = oklchToRgbString(value);
    oklchConversionCache.set(value, converted);
    return converted;
}

// Originally written to run inside html2canvas's onclone callback (safe to
// mutate freely there, since that clone is fully detached from the live
// page) — direct reproduction showed html2canvas still throws on oklch()
// even with onclone wired up: it walks the LIVE document first (resolving
// background/image resources it needs to embed before the clone — and
// hence onclone — ever exists), so the unsupported color function is hit
// before onclone gets a chance to run at all.
//
// This therefore has to sanitize the real, on-screen DOM instead — briefly
// swapping each oklch-based computed color for its rgb() equivalent as an
// inline style, then handing back a restore() that puts every touched
// property back to its exact prior inline value (blank or not). Two things
// make this safe to do directly on the live page rather than "damaging the
// live browser preview styling": every rgb() conversion is mathematically
// exact (verified pixel-for-pixel identical against this browser's own
// rendering of the source oklch color), so nothing visibly changes even for
// the brief moment the override is in place, and the caller always reverts
// it in a finally block immediately after capture.
//
// Every html2canvas download calls this for the length of its capture, so it
// also puts the text where the browser does (2026-11-21): html2canvas measures
// each font's baseline with a 1x1 probe image in the LIVE page. Tailwind's
// preflight made the probe display:block (text drawn a line low, on the cell's
// bottom border), and under body.dp-printing its box was hidden (text drawn
// high). The style keeps it inline and shown (still visibility:hidden); the
// ids only outrank the print-mode rule.
export const PROBE = 'img[src^="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP"]';
export const TRUE_BASELINE_CSS = PROBE + ' { display: inline !important; } body > div:has(> ' + PROBE + '):not(#_):not(#_):not(#_) { display: block !important; }';
// A centred title with letter-spacing that wraps came out split in the PDF
// ("PHILIPPINE REGIS ... TRY FOR PERSONS WITH DISABILITY —", 2026-11-21):
// html2canvas places letter-spaced text letter by letter and misplaces part
// of such a line. Titles are drawn without the spacing (0.4px a letter) in
// the copy html2pdf captures; the page on screen keeps it.
export const CAPTURE_CSS = TRUE_BASELINE_CSS + ' .html2pdf__container :is(h1, h2, h3, h4, .cert-title, .faced-h) { letter-spacing: 0 !important; }';
export function sanitizeExportColors(rootEl) {
    if (!rootEl) return () => {};
    const doc = rootEl.ownerDocument || document;
    const win = doc.defaultView || window;
    const elements = rootEl.nodeType === 1 ? [rootEl, ...rootEl.querySelectorAll('*')] : Array.from(rootEl.querySelectorAll('*'));
    const baseline = doc.createElement('style');
    baseline.textContent = CAPTURE_CSS;
    doc.head.appendChild(baseline);
    const restores = [() => baseline.remove()];
    elements.forEach((el) => {
        const computed = win.getComputedStyle(el);
        OKLCH_COLOR_PROPS.forEach((prop) => {
            const safe = toExportSafeColor(computed[prop]);
            if (!safe) return;
            const prior = el.style[prop];
            el.style[prop] = safe;
            restores.push(() => { el.style[prop] = prior; });
        });
    });
    return () => restores.forEach((restore) => restore());
}

// The element to hand html2pdf instead of #dp-print-area itself. html2pdf
// copies what it is given into the live page (its toContainer overlay)
// before html2canvas reads it, and Alpine's MutationObserver then starts
// every x-show/x-text/:class in that copy outside its component: each one
// throws a ReferenceError, and a failed x-show hides its element. The copy
// measured 0x0, so the staff download hung and the resident's saved a blank
// page (2026-10-08). This copy has the Alpine attributes already gone, the
// current field values written in, and each canvas as an image. Call it
// after sanitizeExportColors() so the copy carries the rgb() colours.
export function exportClone(el) {
    const clone = el.cloneNode(true);
    const fields = el.querySelectorAll('input, textarea, select');
    clone.querySelectorAll('input, textarea, select').forEach((field, i) => {
        const live = fields[i];
        if (!live) return;
        if (field.tagName === 'TEXTAREA') field.textContent = live.value;
        else if (field.tagName === 'SELECT') Array.from(field.options).forEach((o, k) => o.toggleAttribute('selected', !!live.options[k]?.selected));
        else if (field.type === 'checkbox' || field.type === 'radio') field.toggleAttribute('checked', live.checked);
        else field.setAttribute('value', live.value);
    });
    const canvases = el.querySelectorAll('canvas');
    clone.querySelectorAll('canvas').forEach((canvas, i) => {
        try {
            const img = document.createElement('img');
            img.src = canvases[i].toDataURL();
            img.style.cssText = canvas.style.cssText;
            img.className = canvas.className;
            canvas.replaceWith(img);
        } catch (e) { /* a tainted canvas stays as it is */ }
    });
    clone.querySelectorAll('template').forEach((t) => t.remove());
    [clone, ...clone.querySelectorAll('*')].forEach((node) => {
        Array.from(node.attributes).forEach((attr) => {
            if (attr.name.startsWith('x-') || attr.name.startsWith(':') || attr.name.startsWith('@')) node.removeAttribute(attr.name);
        });
    });
    return clone;
}

// Resolves once every <img> under rootEl has either finished loading or
// failed — used before handing the document to html2canvas so a
// still-loading default seal/watermark (or a freshly-uploaded custom one)
// never gets captured as a blank box. A per-image timeout keeps a single
// broken/slow asset from blocking Download PDF indefinitely.
export function waitForImages(rootEl, timeoutMs = 8000) {
    if (!rootEl) return Promise.resolve();
    const images = Array.from(rootEl.querySelectorAll('img'));
    return Promise.all(
        images.map((img) => {
            if (img.complete) return Promise.resolve();
            return new Promise((resolve) => {
                const done = () => {
                    clearTimeout(timer);
                    img.removeEventListener('load', done);
                    img.removeEventListener('error', done);
                    resolve();
                };
                const timer = setTimeout(done, timeoutMs);
                img.addEventListener('load', done);
                img.addEventListener('error', done);
            });
        })
    );
}
