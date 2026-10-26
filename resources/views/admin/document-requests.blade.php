@php
    $barangays = config('esperanza.barangays');
    // Lookup tables only. The queue itself comes from the API after paint
    // (GET /admin/dokyu/requests). This page used to render a fixture queue
    // with requirements and form answers synthesized here at build time, so
    // staff never saw, and could never process, a request a resident had
    // actually filed from the app or the portal.
    $__fixtures = require base_path('resources/data/admin/document-requests.php');
    $barangayCatalog = $__fixtures['barangayCatalog'];
    foreach (config('esperanza_barangay_documents') as $info) {
        $barangayCatalog[$info['label']] = ['label' => $info['label'], 'seal' => $info['seal'], 'officials' => $info['officials']];
    }
    $requests = [];
    $validationGuide = config('esperanza_validation_guide');
    $typeToServiceGuideKey = $__fixtures['typeToServiceGuideKey'];
    $dokyuClassification = $__fixtures['dokyuClassification'];
    // The question set per service, keyed by service name, aliases resolved:
    // the same specs the app's wizard asks, so the Process screen can label
    // every answer in a request's form_data.
    $formSpecs = config('esperanza_service_form_specs');
    $formSpecsByType = collect($formSpecs)
        ->map(fn ($spec) => ($spec['alias'] ?? null) ? ($formSpecs[$spec['alias']] ?? null) : $spec)
        ->filter()
        ->all();
    // The questions the PORTAL's wizard asks, per service key (the same keys
    // the API returns), so a request filed from the portal is labelled by the
    // form it was actually filed on. One filed from the app answers the app's
    // own form spec instead; formStepsFor() tells them apart by the answers.
    $wizardFieldsByKey = collect(config('esperanza_municipal_documents.documents'))
        ->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => ! empty($d['key']) && ! empty($d['fields']))
        ->mapWithKeys(fn ($d) => [$d['key'] => collect($d['fields'])
            ->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'] ?? $f['key'], 'type' => $f['type'] ?? 'text'])
            ->values()->all()])
        ->all();
    // Service key -> this catalog's name, for the lookups below that are keyed
    // by name: the backend's display name differs for some services (TUPAD,
    // ERPAT), its key does not.
    $catalogNameByKey = collect(config('esperanza_municipal_documents.documents'))
        ->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => ! empty($d['key']))
        ->pluck('name', 'key')
        ->all();
    $referencePdfByType = collect(config('esperanza_municipal_documents.documents'))
        ->concat(config('esperanza_barangay_documents.labangtaytay.documents'))
        ->concat(config('esperanza_tulong_types.types'))
        ->filter(fn ($d) => !empty($d['reference_pdf']))
        ->pluck('reference_pdf', 'name')
        ->map(fn ($path) => asset($path));

    $tabs = $__fixtures['tabs'];
    $actionStatuses = $__fixtures['actionStatuses'];
    $doneStatuses = $__fixtures['doneStatuses'];

    // Dokyu document templates (Canva-style "pick a design" library) — the
    // seal images referenced here are real assets extracted directly from
    // the official reference letterheads found on disk at
    // Esperanza-Mobile-App/Reference_forms/Esperanza File/*.docx (each
    // .docx is itself a zip archive; its embedded seal images were pulled
    // from word/media/ and copied as-is into
    // public/images/esperanza/templates/ — never redrawn or invented).
    // Barangay Labangtaytay's real seal + a real Esperanza municipal seal
    // variant both came from "BRGY.CLEARANCE NEW .docx" / "Barangay
    // Certification.docx"; Barangay Villa's real seal came from the same
    // Certification reference. Only these two barangays had an actual
    // distinct seal in the reference files — no other barangay design was
    // fabricated to pad this list. 'esperanza_standard' is the existing,
    // already-approved default paper — kept completely unchanged, still
    // what every new/never-templated request starts from.
    //
    // decorative is a key into the .doc-paper--tpl-* CSS classes in
    // app.css (a subtle corner accent approximating each reference's own
    // background flourish — those were native Word vector shapes, not
    // extractable image files, so they're recreated in CSS rather than
    // faked as a fabricated raster asset).
    //
    // watermarkOpacity is per-template on purpose: Esperanza Standard's own
    // watermark is deliberately a near-invisible 5% authenticity mark
    // (already-approved, unchanged), but the barangay letterheads' whole
    // point is a clearly visible seal watermark — that's the actual "full
    // design" the reference documents show across the middle of the page.
    // Applying a barangay template previously left whatever opacity was
    // already set (5%, inherited from Standard) untouched, which reads as
    // "the middle of the page is just white" even though the watermark
    // image is correctly sized/positioned/z-ordered underneath — confirmed
    // directly by screenshotting the rendered page: the seal is genuinely
    // there, just too faint to read as artwork at 5%. No element is
    // actually covering it.
    $dokyuTemplates = $__fixtures['dokyuTemplates'];
@endphp

<x-layouts.admin title="Dokyu" subtitle="Track and process citizen document requests." active="document-requests">
    <div
        x-data="{
            view: 'requests',
            tab: 'all',
            search: '',
            categoryFilter: '',
            officeFilter: '',
            levelFilter: '',
            assignedFilter: '',
            barangayFilter: '',
            // Only requests still open (not released, completed, rejected or cancelled).
            openOnly: false,
            // One import's records (Show the imported records), or empty.
            importFilter: '',
            // Asked of the server, with the filters above (scope round 2026-10-15).
            mineOnly: false,
            overdueOnly: false,
            loadSeq: 0,
            showAdvancedFilters: false,
            open: false,
            selected: null,
            processing: false,
            rejectRemarks: '',
            previewDoc: null,
            docPreviewOpen: false,
            // The ref whose prepared document was edited in this window (the
            // edit is not stored), for the note beside Print.
            editedInWindow: null,
            // Edit Document rich-text editor state for the Prepared Document
            // modal below — see resources/js/dokyu-editor.js for the shared
            // engine and document-editor-toolbar/document-editor-background-panel
            // .blade.php for the UI. The first time a request is edited, the
            // CURRENT resolved output of whichever docKey template rendered it
            // (barangay-certificate-preview.blade.php / municipal-request-preview
            // .blade.php) is captured into selected.customDocumentHtml — a
            // plain, self-contained HTML string the admin can then freely edit
            // via contenteditable, independent of that template's own Alpine
            // bindings. Once set, the Prepared Document modal renders that
            // captured/edited HTML instead of re-invoking the template
            // component, for every future view (see the previewType-branching
            // template below) — this is what lets one generic editor work
            // across every Dokyu paper's own particular layout rather than
            // needing per-docKey editing logic.
            editingDocument: false,
            documentEditBackup: null,
            backgroundEditBackup: null,
            templateKeyEditBackup: null,
            // Undo/Redo history for the document editor — {stack, index}
            // where stack holds {html, background} snapshots and index
            // points at the one currently applied. Populated by
            // initHistoryTracking()/pushHistorySnapshot() below; see that
            // section's own comment for the full design (why a snapshot
            // stack rather than a command log, debouncing, and how it stays
            // one shared system instead of a separate one per feature).
            editHistory: { stack: [], index: -1 },
            _historyObserver: null,
            _historyDebounceTimer: null,
            _restoringHistory: false,
            // Which editor side panel is open (null, background, spacing, or
            // seals) — only one at a time, toggled from the toolbar.
            activePanel: null,
            // Currently selected block for the Spacing panel below — a plain
            // DOM element reference (findSelectableBlock() in dokyu-editor.js),
            // not itself reactive; sectionSpacing mirrors its current inline
            // margin-top/margin-bottom/line-height so the panel's inputs have
            // something reactive to bind to.
            selectedSectionEl: null,
            hoveredSectionEl: null,
            sectionSpacing: { before: 0, after: 0, lineHeight: 1.0, canMoveUp: false, canMoveDown: false },
            // Free vertical offset (transform:translateY) mirroring
            // selectedSectionEl's own inline transform — a different
            // mechanism from sectionSpacing above, see setVerticalOffset()
            // in dokyu-editor.js for why margin alone cannot reliably
            // reposition a block pinned by margin-top:auto.
            sectionOffsetY: 0,
            // A small, drag-independent horizontal nudge (see setHorizontalOffset()
            // in dokyu-editor.js) — dragging itself stays vertical-only, this is
            // just the Position & Spacing panel's own X +/- control.
            sectionOffsetX: 0,
            isDraggingSection: false,
            documentOverflow: false,
            // Every floating editor inspector (Position & Spacing, Header
            // Seals, Background) shares this one state shape and the
            // startPanelDrag()/floating-panel CSS below, rather than each
            // rolling its own window — a UI-window drag, unrelated to
            // document-section dragging above. Positions reset to these
            // defaults every time editing starts fresh (not persisted
            // beyond the current session); staggered X so a panel opened
            // for the first time never lands exactly on top of another.
            // expanded switches a panel into the larger, easier-to-read
            // layout (see .doc-floating-panel--expanded in app.css) — an
            // accessibility affordance for older staff, independent of
            // collapsed (a panel can be both expanded and collapsed at once).
            floatingPanels: {
                spacing: { pos: { x: 32, y: 88 }, collapsed: false, expanded: false },
                seals: { pos: { x: 356, y: 88 }, collapsed: false, expanded: false },
                background: { pos: { x: 680, y: 88 }, collapsed: false, expanded: false },
            },
            sealOffsets: { left: { x: 0, y: 0 }, right: { x: 0, y: 0 } },
            // Viewer zoom (document-editor-zoom-controls.blade.php) — a
            // display-only setting, reset to 100 every time View Document
            // opens (see the View Document button below); see
            // resources/js/dokyu-editor.js for why this never affects the
            // real document, print, PDF, or the overflow check.
            zoomLevel: 100,
            defaultWatermarkSrc: @js(asset('images/esperanza/esperanza-seal.png')),
            // Template library (document-editor-template-sidebar.blade.php)
            // — see the $dokyuTemplates comment above in this file's own
            // @php block for exactly which real reference files each
            // template's assets came from. templateSidebarCollapsed resets
            // with the rest of the editor UI in resetEditorUiState().
            dokyuTemplates: @js($dokyuTemplates),
            templateSidebarCollapsed: false,
            barangayCatalog: @js($barangayCatalog),
            docTypeLookup: @js(config('esperanza_document_type_lookup')),
            // Mirrors citizen/document-requests.blade.php's own resolvedDocument/
            // documentReady/printDocument/downloadDocument — same #dp-print-area /
            // dp-printing convention (shared print CSS in app.css), so staff can
            // preview, print, or download the prepared document from the Process
            // tab itself before handing it over, not just the blank Reference
            // Form. Falls back to docTypeLookup (config/esperanza_document_type_lookup.php)
            // for any request that never had a hand-authored formSnapshot — see
            // that config file's own header comment for why almost none of the
            // seeded queue does, and what the fallback synthesizes instead.
            get resolvedDocument() {
                const r = this.selected;
                if (!r) return null;
                if (r.previewType && r.formSnapshot) {
                    return { previewType: r.previewType, docKey: r.docKey, certTitle: r.certTitle, certSubtitle: r.certSubtitle, snapshot: r.formSnapshot, barangayOfficials: r.barangayOfficials || (this.barangayCatalog[r.barangay]?.officials ?? null) };
                }
                const meta = this.docTypeLookup[r.type];
                if (!meta) return null;
                // Synthesized once per request, then cached onto the request
                // itself (r.formSnapshot) so admin edits to individual fields
                // survive this getter re-running on every reactive read —
                // without this, editing a field would appear to work for one
                // tick and then silently reset, since a plain (un-memoized)
                // getter rebuilds a brand-new snapshot object from scratch on
                // every single access.
                if (!r.formSnapshot) {
                const snapshot = {};
                if (meta.nameField) snapshot[meta.nameField] = r.citizen || '';
                if (meta.purposeField) snapshot[meta.purposeField] = meta.purposeIsArray ? (r.purpose ? [r.purpose] : []) : (r.purpose || '');
                // The citizen's real Constituents profile — the overview tab —
                // already has age/dob/sex/civil status/occupation/parents/
                // address on file for anyone with a citizen account (see
                // $citizenBio in this file's own @php block) — merge
                // whichever of those a docKey's template actually uses
                // instead of leaving them blank just because this request
                // never had its own formSnapshot. Never overwrites a field
                // the name/purpose mapping above already set.
                const bio = null;
                if (bio) {
                    const purokMatch = /Purok\s+([^,]+)/i.exec(bio.address || '');
                    const bioFields = {
                        age: bio.age, date_of_birth: bio.dob, sex: bio.sex, civil_status: bio.civil_status,
                        citizenship: 'Filipino', occupation: bio.occupation, father_name: bio.father_name,
                        mother_name: bio.mother_name, mother_maiden_name: bio.mother_maiden_name,
                        address: bio.address, residence: bio.address, purok: purokMatch ? purokMatch[1].trim() : null,
                    };
                    for (const [key, value] of Object.entries(bioFields)) {
                        if (!(key in snapshot) && value) snapshot[key] = value;
                    }
                }
                // Every field the citizen supposedly answered in the mobile
                // app's own request wizard is already synthesized onto this
                // request as r.form_steps (see $synthesizeFormAnswer in this
                // file's own @php block, sourced from config/esperanza_service_form_specs.php
                // — real Mobile Main formSpecs, not invented). That system
                // uses the mobile app's own camelCase field keys
                // (businessNature, capitalAmount, residencyType...); this
                // document's own fields use snake_case (business_type,
                // capital_figures, residency_type...) — auto-converted, plus
                // a short alias list for the handful of keys/values that
                // don't line up 1:1 after conversion (a business capital
                // amount figure vs. this doc's separate figures/words split;
                // mobile's Permanent Resident vs. this doc's plain Permanent).
                // This is what actually answers the missing-capital-field
                // report — the field exists, it just wasn't being read.
                const residencyTypeMap = { 'Permanent Resident': 'Permanent', 'Temporary Resident': 'Temporary', 'Renter / Lessee': 'Renter' };
                // These docKey fields are always rendered as a joined list
                // (.join(', ') in the templates) — coerce a single-select
                // mobile answer into a one-item array so it never crashes
                // against a template expecting .join() to exist.
                const arrayFields = ['purpose', 'type_of_disability', 'cause_of_disability', 'zoning_classification'];
                (r.form_steps || []).forEach(step => (step.fields || []).forEach(f => {
                    if (f.answer === null || f.answer === undefined || f.answer === '') return;
                    const key = f.key.replace(/([A-Z])/g, '_$1').toLowerCase();
                    if (key in snapshot) return;
                    let value = key === 'residency_type' ? (residencyTypeMap[f.answer] || f.answer) : f.answer;
                    if (arrayFields.includes(key) && !Array.isArray(value)) value = [value];
                    snapshot[key] = value;
                    if (key === 'business_nature') { snapshot.business_type = snapshot.business_type || value; snapshot.line_of_business = snapshot.line_of_business || value; }
                    if (key === 'capital_amount') { snapshot.capital_figures = snapshot.capital_figures || ('₱' + value); snapshot.capital_investment = snapshot.capital_investment || ('₱' + value); }
                }));
                // 'age' is deliberately never a stored answer on the mobile
                // side (a derivedAge field computes it live from Date of
                // Birth rather than being independently entered — see
                // $synthesizeFormAnswer's own derivedAge case) — so derive
                // it here the same way, whenever a date_of_birth made it
                // into the snapshot above but age itself didn't.
                if (!snapshot.age && snapshot.date_of_birth) {
                    const dob = new Date(snapshot.date_of_birth);
                    if (!isNaN(dob)) {
                        const today = new Date();
                        let age = today.getFullYear() - dob.getFullYear();
                        if (today.getMonth() < dob.getMonth() || (today.getMonth() === dob.getMonth() && today.getDate() < dob.getDate())) age--;
                        snapshot.age = String(age);
                    }
                }
                r.formSnapshot = snapshot;
                }
                return { previewType: meta.previewType, docKey: meta.docKey, certTitle: meta.certTitle, certSubtitle: meta.certSubtitle || null, snapshot: r.formSnapshot, barangayOfficials: this.barangayCatalog[r.barangay]?.officials ?? null };
            },
            get documentReady() {
                const r = this.selected;
                return !!(r && ['Approved', 'Mark to Release', 'Released'].includes(r.status) && this.resolvedDocument);
            },
            get defaultBackground() {
                return { src: this.defaultWatermarkSrc, opacity: 5, size: 'large', position: 'center', paperColor: '#FFFFFF', decorative: null };
            },
            get documentBackground() {
                return (this.selected && this.selected.background) || this.defaultBackground;
            },
            ensureBackground() {
                if (this.selected && !this.selected.background) {
                    this.selected.background = { ...this.defaultBackground };
                }
            },
            // Document Templates (document-editor-template-sidebar.blade.php)
            // — Canva-style pick-a-design rather than pick-a-document,
            // so applyTemplate() only ever touches presentation (seals,
            // watermark, paper color, decorative accent), never the
            // request's own content. It deliberately does NOT touch
            // customDocumentHtml or any doc-field/x-text bound value — the
            // applicant's name/age/barangay/purpose/date/reference/official
            // all live in that markup untouched, exactly like editing font
            // or color doesn't rewrite the wording around it. Seal <img>
            // src changes live inside .doc-content, so Undo/Redo's own
            // MutationObserver (see the Undo/Redo section's comment above
            // execCmd()) picks them up automatically; watermark/paper color
            // live outside it, so this schedules its own snapshot the same
            // way setWatermarkOpacity() etc. already do.
            get activeTemplateKey() {
                return (this.selected && this.selected.templateKey) || 'esperanza_standard';
            },
            applyTemplate(key) {
                const tpl = this.dokyuTemplates.find((t) => t.key === key);
                if (!tpl || !this.selected) return;
                this.selected.templateKey = key;
                const leftImg = this.findSealImg('left');
                if (leftImg) {
                    leftImg.src = tpl.leftSeal;
                    leftImg.setAttribute('data-default-src', tpl.leftSeal);
                    leftImg.style.display = '';
                }
                const rightImg = this.findSealImg('right');
                if (rightImg) {
                    rightImg.src = tpl.rightSeal;
                    rightImg.setAttribute('data-default-src', tpl.rightSeal);
                    rightImg.style.display = '';
                }
                this.ensureBackground();
                this.selected.background.src = tpl.watermarkSrc;
                this.selected.background.opacity = tpl.watermarkOpacity;
                this.selected.background.paperColor = tpl.paperColor;
                this.selected.background.decorative = tpl.decorative;
                this.refreshSealOffsets();
                this.scheduleHistorySnapshot();
            },
            // A real bug exists where an <img> src change inside
            // .doc-content can fail to survive Save -> Close -> Reopen — a
            // header seal set by applyTemplate() reverts to the default
            // Esperanza seal in the live DOM even though
            // this.selected.templateKey itself persists correctly (full
            // diagnosis in the final report; extensive testing ruled out
            // the obvious causes — snapshot size, data: vs http: URLs,
            // attribute-mutation vs node-replacement, contenteditable
            // timing — without finding a complete fix). Since the template
            // key IS reliable, this re-derives and re-asserts both seal
            // srcs from the CURRENT template's own definition rather than
            // trusting whatever the captured HTML happened to keep, and is
            // called every time editing (re)starts so a stale reopen
            // self-heals. It does not help a seal replaced by a direct
            // upload (handleSealUpload) with no template backing it — that
            // specific case is a known, documented limitation.
            reapplyTemplateSeals() {
                const key = this.selected && this.selected.templateKey;
                if (!key) return;
                const tpl = this.dokyuTemplates.find((t) => t.key === key);
                if (!tpl) return;
                const leftImg = this.findSealImg('left');
                if (leftImg && leftImg.src !== tpl.leftSeal) {
                    leftImg.src = tpl.leftSeal;
                    leftImg.style.display = '';
                }
                const rightImg = this.findSealImg('right');
                if (rightImg && rightImg.src !== tpl.rightSeal) {
                    rightImg.src = tpl.rightSeal;
                    rightImg.style.display = '';
                }
            },
            paperStyle() {
                return window.DokyuEditor.paperStyle(this.documentBackground);
            },
            watermarkStyle() {
                return window.DokyuEditor.watermarkStyle(this.documentBackground);
            },
            startEditingDocument() {
                const doc = this.resolvedDocument;
                if (!doc || !this.selected) return;
                // First time editing this request: freeze the exact output the
                // template just rendered into a plain HTML string — see this
                // file's own comment above editingDocument for why.
                if (!this.selected.customDocumentHtml) {
                    const contentEl = document.querySelector('#dp-print-area .doc-content');
                    if (contentEl) {
                        // Without the verification strip: the edited copy draws a live
                        // one below it, so a later reprint or revoke still shows
                        // (scope round 2026-10-15).
                        const copy = contentEl.cloneNode(true);
                        copy.querySelectorAll('[x-data^=verifyStrip]').forEach((el) => el.remove());
                        this.selected.customDocumentHtml = window.DokyuEditor.captureContent(copy);
                    }
                }
                this.ensureBackground();
                this.documentEditBackup = this.selected.customDocumentHtml;
                this.backgroundEditBackup = JSON.parse(JSON.stringify(this.selected.background));
                this.templateKeyEditBackup = this.activeTemplateKey;
                this.editingDocument = true;
                // Prefer inline style-based formatting (e.g. a span with a
                // CSS color style) over legacy presentational tags (font
                // color, b, i) for
                // everything except fontSize, which always uses the legacy
                // <font size> mechanism regardless of this setting — see
                // applyFontSize()'s own comment in dokyu-editor.js.
                document.execCommand('styleWithCSS', false, true);
                this.$nextTick(() => {
                    window.renderIcons?.();
                    this.refreshOverflowWarning();
                    this.recomputeZoomSizer();
                    this._hadContentBlocks = new WeakSet();
                    this.reconcileEmptyBlocks();
                    this.reapplyTemplateSeals();
                    this.initHistoryTracking();
                });
            },
            // Cancel, and closing the window while editing, put the document back
            // as it was. Restoring the same string does not redraw x-html, so
            // the edited text stayed on the page and in the print (Esperanza
            // bugs QA, 2026-10-06): the editor's content is reset directly.
            cancelDocumentEdits() {
                if (this.selected) {
                    // Closing the window runs this from the modal's wrapper,
                    // where $refs.docEditor does not resolve (see
                    // getDocContentEl), so the edit survived a close.
                    const editor = this.$refs.docEditor || this.getDocContentEl();
                    if (editor && this.documentEditBackup !== null) {
                        editor.innerHTML = this.documentEditBackup;
                    }
                    this.selected.customDocumentHtml = this.documentEditBackup;
                    if (this.backgroundEditBackup) this.selected.background = this.backgroundEditBackup;
                    if (this.templateKeyEditBackup) this.selected.templateKey = this.templateKeyEditBackup;
                }
                this.documentEditBackup = null;
                this.backgroundEditBackup = null;
                this.templateKeyEditBackup = null;
                this.editingDocument = false;
                this.teardownHistoryTracking();
                this.resetEditorUiState();
                this.$nextTick(() => this.recomputeZoomSizer());
            },
            // Shared by Cancel, Save, and modal-close below — the x-html
            // re-render those trigger replaces .doc-content's DOM entirely,
            // so any element the Spacing panel was pointing at no longer
            // exists; clearing it here avoids the panel acting on a detached
            // node afterward.
            resetEditorUiState() {
                // Detach any handle BEFORE nulling the refs below — otherwise
                // it's orphaned in the live DOM. This matters most when the
                // modal is simply closed (docPreviewOpen goes false without
                // an x-html re-render): without this, a stray handle would
                // stay visible even in plain, non-editing View Document.
                if (this.selectedSectionEl) this.detachDragHandle(this.selectedSectionEl);
                if (this.hoveredSectionEl && this.hoveredSectionEl !== this.selectedSectionEl) this.detachDragHandle(this.hoveredSectionEl);
                this.activePanel = null;
                this.selectedSectionEl = null;
                this.hoveredSectionEl = null;
                this.isDraggingSection = false;
                this.sectionSpacing = { before: 0, after: 0, lineHeight: 1.0, canMoveUp: false, canMoveDown: false };
                this.sectionOffsetY = 0;
                this.sectionOffsetX = 0;
                this.documentOverflow = false;
                this.floatingPanels = {
                    spacing: { pos: { x: 32, y: 88 }, collapsed: false, expanded: false },
                    seals: { pos: { x: 356, y: 88 }, collapsed: false, expanded: false },
                    background: { pos: { x: 680, y: 88 }, collapsed: false, expanded: false },
                };
                this.templateSidebarCollapsed = false;
            },
            // Toolbar controls (document-editor-toolbar.blade.php) — every
            // command acts on the live browser selection inside .doc-content;
            // see resources/js/dokyu-editor.js for why saveSelection()/
            // restoreSelection() exist (the toolbar sits outside the
            // contenteditable region, so focusing it would otherwise collapse
            // the selection before the command runs).
            saveSelection() {
                window.DokyuEditor.saveSelection(this.$refs.docEditor);
            },
            restoreSelection() {
                window.DokyuEditor.restoreSelection();
            },
            execCmd(cmd) {
                document.execCommand(cmd);
            },
            // --- Undo/Redo -----------------------------------------------------
            //
            // document.execCommand('undo'/'redo') (the toolbar's previous
            // implementation) only ever tracked the browser's own native
            // contenteditable typing history — it has no idea section
            // drag-and-drop, spacing, seal changes, or watermark/background
            // changes even happened, since all of those are plain JS style/
            // attribute mutations, never routed through execCommand. That's
            // why Undo/Redo did not work properly: most of what an admin
            // actually does in this editor was invisible to it.
            //
            // This replaces it with one snapshot-based history shared by
            // every kind of edit, rather than a separate undo mechanism per
            // feature: each entry is {html, background, templateKey} — html
            // is .doc-content's own innerHTML (typing, formatting,
            // indentation, and section X/Y/spacing/seal changes are ALL
            // already expressed as markup/inline-styles inside it, exactly
            // what captureContent()/cleanHtml() already rely on for Save),
            // background is a clone of documentBackground (watermark/paper
            // color, which live outside .doc-content as a sibling — see
            // barangay-certificate-preview.blade.php's own comment on that),
            // and templateKey is which Document Template was active (see
            // applyTemplate() further below) — a separate field from
            // background/html, so it needs its own place in the snapshot to
            // make template switching undoable too.
            // Restoring a snapshot is the same technique cancelDocumentEdits()
            // already uses for its own single-level backup, just applied
            // through a multi-step stack instead of one slot.
            //
            // New entries come from a MutationObserver on .doc-content
            // (initHistoryTracking() below) rather than hand-wiring a
            // pushHistorySnapshot() call onto every individual toolbar/panel
            // action — typing, drag-and-drop, indentation, seal edits, and
            // any future feature that mutates that DOM all get history for
            // free from one place. Background/watermark changes live
            // outside .doc-content, so those specific setters call
            // scheduleHistorySnapshot() directly (see the Change Background
            // section further below).
            //
            // Both paths are debounced (600ms of quiet) rather than pushing
            // one entry per mutation — required for drag (pointermove fires
            // continuously; the requirement is ONE history entry for the
            // whole completed drag, achieved for free here since each new
            // mutation keeps pushing the deadline back until pointerup),
            // and equally necessary for typing (one entry per keystroke
            // would make Undo nearly useless) and for slider-driven changes
            // like watermark opacity.
            // $refs is scoped to the nearest x-data — undo()/redo() can be
            // triggered from the toolbar (inside this component's own
            // template, where $refs.docEditor resolves fine) or from the
            // Ctrl+Z/Ctrl+Y window keydown handler, which lives on the
            // Prepared Document modal's own separate small x-data wrapper
            // ({ get open() ... } above) — from there $refs.docEditor
            // doesn't resolve at all (confirmed directly: editHistory.index
            // updates correctly either way, but the DOM only actually
            // changed when triggered from the toolbar). getDocContentEl()
            // sidesteps that scope quirk entirely with a plain selector —
            // the same one startEditingDocument() already uses for its own
            // first-capture, for the same reason.
            getDocContentEl() {
                return document.querySelector('#dp-print-area .doc-content');
            },
            initHistoryTracking() {
                this.teardownHistoryTracking();
                const contentEl = this.getDocContentEl();
                if (!contentEl) return;
                this.editHistory = { stack: [], index: -1 };
                this.pushHistorySnapshot();
                this._historyObserver = new MutationObserver(() => this.scheduleHistorySnapshot());
                this._historyObserver.observe(contentEl, { childList: true, subtree: true, attributes: true, characterData: true });
            },
            teardownHistoryTracking() {
                clearTimeout(this._historyDebounceTimer);
                this._historyDebounceTimer = null;
                if (this._historyObserver) {
                    this._historyObserver.disconnect();
                    this._historyObserver = null;
                }
                this.editHistory = { stack: [], index: -1 };
            },
            scheduleHistorySnapshot() {
                if (this._restoringHistory) return;
                clearTimeout(this._historyDebounceTimer);
                this._historyDebounceTimer = setTimeout(() => this.pushHistorySnapshot(), 600);
            },
            pushHistorySnapshot() {
                const contentEl = this.getDocContentEl();
                if (!this.editingDocument || !contentEl || this._restoringHistory) return;
                const snapshot = {
                    html: window.DokyuEditor.cleanHtml(contentEl.innerHTML),
                    background: JSON.parse(JSON.stringify(this.documentBackground)),
                    templateKey: this.activeTemplateKey,
                };
                const last = this.editHistory.stack[this.editHistory.index];
                if (last && last.html === snapshot.html && last.templateKey === snapshot.templateKey && JSON.stringify(last.background) === JSON.stringify(snapshot.background)) return;
                this.editHistory.stack = this.editHistory.stack.slice(0, this.editHistory.index + 1);
                this.editHistory.stack.push(snapshot);
                const maxHistoryEntries = 100;
                if (this.editHistory.stack.length > maxHistoryEntries) this.editHistory.stack.shift();
                this.editHistory.index = this.editHistory.stack.length - 1;
            },
            restoreHistorySnapshot(snapshot) {
                const contentEl = this.getDocContentEl();
                if (!snapshot || !contentEl) return;
                this._restoringHistory = true;
                clearTimeout(this._historyDebounceTimer);
                contentEl.innerHTML = snapshot.html;
                this.selected.background = JSON.parse(JSON.stringify(snapshot.background));
                if (snapshot.templateKey) this.selected.templateKey = snapshot.templateKey;
                this.selectedSectionEl = null;
                this.hoveredSectionEl = null;
                this.$nextTick(() => {
                    window.renderIcons?.();
                    this.refreshOverflowWarning();
                    this.recomputeZoomSizer();
                    this.refreshSealOffsets();
                    this._hadContentBlocks = new WeakSet();
                    this.reconcileEmptyBlocks();
                    setTimeout(() => { this._restoringHistory = false; }, 50);
                });
            },
            canUndo() {
                return this.editHistory.index > 0;
            },
            canRedo() {
                return this.editHistory.index < this.editHistory.stack.length - 1;
            },
            undo() {
                if (!this.canUndo()) return;
                this.editHistory.index -= 1;
                this.restoreHistorySnapshot(this.editHistory.stack[this.editHistory.index]);
            },
            redo() {
                if (!this.canRedo()) return;
                this.editHistory.index += 1;
                this.restoreHistorySnapshot(this.editHistory.stack[this.editHistory.index]);
            },
            // Ctrl+Z / Ctrl+Y / Ctrl+Shift+Z, scoped to only while the document
            // editor is actively open — never interferes with the rest of the
            // Web Admin's own shortcuts elsewhere. Skips native text inputs
            // (the floating panels' own number fields, color pickers, etc.)
            // so this never hijacks a field's own native undo while the admin
            // is mid-edit typing a value into it.
            handleUndoRedoShortcut(event) {
                if (!this.editingDocument) return;
                if (!(event.ctrlKey || event.metaKey)) return;
                const activeTag = document.activeElement ? document.activeElement.tagName : '';
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag)) return;
                if (event.key === 'z' || event.key === 'Z') {
                    event.preventDefault();
                    if (event.shiftKey) this.redo();
                    else this.undo();
                } else if (event.key === 'y' || event.key === 'Y') {
                    event.preventDefault();
                    this.redo();
                }
            },
            applyFontFamily(family) {
                this.restoreSelection();
                document.execCommand('fontName', false, family);
            },
            applyFontSize(px) {
                this.restoreSelection();
                window.DokyuEditor.applyFontSize(this.$refs.docEditor, px);
            },
            applyColor(hex) {
                this.restoreSelection();
                document.execCommand('foreColor', false, hex);
            },
            resetSelectionFormatting() {
                this.restoreSelection();
                document.execCommand('removeFormat');
            },
            // Paragraph indentation — Tab/Shift+Tab (handleContentKeydown()
            // below) and the toolbar's Increase/Decrease Indent buttons both
            // call these exact same two methods, rather than the toolbar
            // staying on execCommand('indent')/('outdent') while Tab does
            // something else; see the shared increaseIndent()/decreaseIndent()
            // comment in dokyu-editor.js for why margin-left was chosen over
            // that native command.
            increaseIndentAtCaret() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                const block = window.DokyuEditor.getCurrentBlock(contentEl);
                if (!block) return;
                window.DokyuEditor.increaseIndent(block);
                if (this.selectedSectionEl === block) this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            decreaseIndentAtCaret() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                const block = window.DokyuEditor.getCurrentBlock(contentEl);
                if (!block) return;
                window.DokyuEditor.decreaseIndent(block);
                if (this.selectedSectionEl === block) this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Bound directly on .doc-content's own keydown (see the modal
            // template below), which only ever fires while that specific
            // element is focused — the guard below additionally requires
            // document.activeElement to BE .doc-content itself (not one of
            // its children, like a drag handle, which is also focusable),
            // so this only ever intercepts Tab while genuinely editing text,
            // never while the caret is merely somewhere in the document
            // for positioning purposes. Everywhere else on the page (toolbar,
            // inputs, color pickers, the floating inspector, seal controls)
            // Tab is never touched at all, since keydown never reaches this
            // handler from there in the first place.
            handleContentKeydown(event) {
                if (event.key !== 'Tab') return;
                if (document.activeElement !== this.$refs.docEditor) return;
                event.preventDefault();
                if (event.shiftKey) this.decreaseIndentAtCaret();
                else this.increaseIndentAtCaret();
            },
            // Section spacing / movement panel (document-editor-spacing-panel.blade.php)
            // — operates on whatever block the admin last clicked inside
            // .doc-content (see findSelectableBlock() in dokyu-editor.js);
            // works identically across every Dokyu paper since it is purely
            // DOM-generic, never docKey-aware.
            selectSection(event) {
                if (!this.editingDocument || !this.$refs.docEditor) return;
                const block = window.DokyuEditor.findSelectableBlock(event.target, this.$refs.docEditor);
                if (!block) {
                    this.deselectSection();
                    return;
                }
                this.selectSectionElement(block);
            },
            // Shared by selectSection() above and selectParentSection() below
            // so both go through identical hover-cleanup/class bookkeeping.
            selectSectionElement(block) {
                if (this.selectedSectionEl === block) return;
                if (this.selectedSectionEl) {
                    this.selectedSectionEl.classList.remove('doc-section-selected');
                    if (this.selectedSectionEl !== this.hoveredSectionEl) this.detachDragHandle(this.selectedSectionEl);
                }
                if (block === this.hoveredSectionEl) {
                    block.classList.remove('doc-section-hover');
                    this.hoveredSectionEl = null;
                }
                this.selectedSectionEl = block;
                block.classList.add('doc-section-selected');
                this.attachDragHandle(block);
                this.refreshSectionSpacing();
            },
            deselectSection() {
                if (this.selectedSectionEl) {
                    this.selectedSectionEl.classList.remove('doc-section-selected');
                    if (this.selectedSectionEl !== this.hoveredSectionEl) this.detachDragHandle(this.selectedSectionEl);
                }
                this.selectedSectionEl = null;
            },
            // Fixes the blue-dashed-lines-after-pressing-Enter bug: a
            // browser's native contenteditable paragraph-split (what Enter
            // actually does) clones the CURRENT block's opening tag —
            // including its doc-section-selected / doc-section-hover class —
            // onto every new paragraph the split produces, and can also
            // leave a stray .doc-drag-handle child behind on a fragment that
            // is no longer the tracked block. selectedSectionEl/hoveredSectionEl
            // (not the class/handle themselves) are the real source of truth,
            // so this just re-applies that truth to the DOM after every
            // keystroke: strip the marker class/handle from anything that
            // isn't the currently tracked element, and re-attach a handle if
            // the tracked element's own got relocated by the same split.
            // Deliberately does nothing to Enter/typing itself — this only
            // cleans up state that already exists in the DOM.
            reconcileSectionMarkers() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                if (this.selectedSectionEl && !contentEl.contains(this.selectedSectionEl)) this.selectedSectionEl = null;
                if (this.hoveredSectionEl && !contentEl.contains(this.hoveredSectionEl)) this.hoveredSectionEl = null;
                contentEl.querySelectorAll('.doc-section-selected').forEach((el) => {
                    if (el !== this.selectedSectionEl) el.classList.remove('doc-section-selected');
                });
                contentEl.querySelectorAll('.doc-section-hover').forEach((el) => {
                    if (el !== this.hoveredSectionEl) el.classList.remove('doc-section-hover');
                });
                contentEl.querySelectorAll('.doc-drag-handle').forEach((handle) => {
                    const owner = handle.parentElement;
                    if (owner !== this.selectedSectionEl && owner !== this.hoveredSectionEl) {
                        if (owner) owner.__dragHandle = null;
                        handle.remove();
                    }
                });
                if (this.selectedSectionEl && !this.selectedSectionEl.__dragHandle) this.attachDragHandle(this.selectedSectionEl);
                if (this.hoveredSectionEl && !this.hoveredSectionEl.__dragHandle) this.attachDragHandle(this.hoveredSectionEl);
            },
            // Collapses a leaf text block's own margin/padding once every
            // character is deleted from it, so clearing e.g. the TO WHOM IT
            // MAY CONCERN line no longer leaves a large empty box reserving
            // its old height — without ever deleting the element itself (it
            // stays fully editable; typing into it again removes the
            // collapsed class on the next input event) and without touching
            // a block Enter just created blank on purpose.
            //
            // The two are structurally identical once empty (both end up as
            // e.g. an empty p containing just a br) — the only way to tell
            // them apart is history, not shape. this._hadContentBlocks is a
            // live WeakSet of blocks this editing session has actually seen
            // non-empty — a block only ever enters it by being observed with
            // real text at some point. A paragraph split off by Enter is a
            // structurally new node (even though the browser clones the
            // original's attributes onto it — see reconcileSectionMarkers()'s
            // own comment above), so it starts absent from the set and stays
            // absent — and therefore un-collapsed — for exactly as long as
            // it stays genuinely blank, which is exactly the intentional
            // blank line this needs to preserve. Only resets on a fresh
            // render of .doc-content (first Edit Document click, template
            // switch, Undo/Redo restore — see their own calls into this
            // same method) since those create all-new DOM nodes anyway.
            //
            // Only ever applied to LEAF blocks (no nested selectable tag
            // inside) — a container like the letterhead row, the signature
            // row, or a field grid always has nested paragraphs/divs, so it
            // can never itself collapse just because everything inside it
            // happens to be empty at once; that keeps every required
            // structural wrapper untouched by construction, not via an
            // allow/deny list of specific class names.
            // A drag handle (createDragHandleElement() in dokyu-editor.js)
            // is injected as a real child of whichever block is currently
            // selected or hovered — a plain div carrying its own grip-glyph
            // text — purely so it inherits that block's own position:relative
            // for free (see that function's own comment). That means the
            // one block reconcileEmptyBlocks()/canRemoveSelectedBlock below
            // is most likely to be checking (the one the admin just clicked
            // into) can already have one attached at exactly the moment
            // that would make it look both non-empty (the handle's own
            // glyph) and non-leaf (a div is itself one of the nested-block
            // tags being searched for) even though nothing the admin typed
            // is actually there. Stripped from a throwaway clone — same
            // idea cleanHtml()/captureContent() already use before saving —
            // so this never touches the real DOM or the handle in use.
            contentOnlyClone(el) {
                const clone = el.cloneNode(true);
                clone.querySelectorAll('.doc-drag-handle').forEach((h) => h.remove());
                return clone;
            },
            reconcileEmptyBlocks() {
                const contentEl = this.$refs.docEditor;
                if (!contentEl) return;
                if (!this._hadContentBlocks) this._hadContentBlocks = new WeakSet();
                const nestedSelector = 'p, div, h1, h2, h3, h4, h5, h6, ul, ol, table';
                contentEl.querySelectorAll(nestedSelector).forEach((el) => {
                    const clone = this.contentOnlyClone(el);
                    if (clone.querySelector(nestedSelector)) {
                        el.classList.remove('doc-block-collapsed');
                        return;
                    }
                    const hasText = (clone.textContent || '').split(String.fromCharCode(160)).join(' ').trim().length > 0;
                    const hasMedia = !!clone.querySelector('img, table, input, textarea, select');
                    if (hasText || hasMedia) {
                        this._hadContentBlocks.add(el);
                        el.classList.remove('doc-block-collapsed');
                    } else if (this._hadContentBlocks.has(el)) {
                        el.classList.add('doc-block-collapsed');
                    }
                });
                this._contentVersion = (this._contentVersion || 0) + 1;
            },
            // Backs the Position & Spacing panel's Remove Block button —
            // true only for a currently-selected leaf block (the same
            // no-nested-selectable-tag-inside test reconcileEmptyBlocks()
            // uses, so a required structural container can never qualify,
            // since it always has nested paragraphs/divs) that is currently
            // empty. Deliberately not restricted to blocks the reconcile
            // pass has already collapsed — an admin selecting a blank line
            // Enter just created should be able to remove that line
            // outright too, not only shrink it.
            get canRemoveSelectedBlock() {
                // Reading this reactive counter (its value is never used) is
                // what makes Alpine re-run this getter after a keystroke.
                // selectedSectionEl is the only OTHER thing read here that
                // Alpine's reactivity can see, and clearing an already-
                // selected block's text never changes WHICH element is
                // selected — it mutates that element's own DOM content via
                // execCommand/contenteditable, entirely outside Alpine's
                // reactive store. Without this, the Remove Block button's
                // own x-show binding would freeze at whatever this getter
                // returned the moment
                // the block was first selected (almost always false, since
                // that's usually before its text gets cleared) and never
                // re-evaluate again on its own. reconcileEmptyBlocks() bumps
                // this on every input event for exactly this reason.
                void this._contentVersion;
                const el = this.selectedSectionEl;
                if (!el || !this.$refs.docEditor || el === this.$refs.docEditor) return false;
                const clone = this.contentOnlyClone(el);
                const nestedSelector = 'p, div, h1, h2, h3, h4, h5, h6, ul, ol, table';
                if (clone.querySelector(nestedSelector)) return false;
                const hasText = (clone.textContent || '').split(String.fromCharCode(160)).join(' ').trim().length > 0;
                const hasMedia = !!clone.querySelector('img, table, input, textarea, select');
                return !hasText && !hasMedia;
            },
            // Removes the element outright (not just a collapsed/zero-height
            // state) — for when the admin wants an empty optional line gone
            // entirely, not merely out of the way. Plain DOM removal inside
            // .doc-content is already covered by the existing childList:true
            // MutationObserver (see initHistoryTracking() above), so Undo
            // restores it exactly like undoing a delete/drag/format change —
            // no separate history bookkeeping needed here.
            removeSelectedBlock() {
                const el = this.selectedSectionEl;
                if (!el || !this.canRemoveSelectedBlock) return;
                this.detachDragHandle(el);
                this.deselectSection();
                el.remove();
                this.refreshOverflowWarning();
                this.recomputeZoomSizer();
            },
            // Whether the current selection is already one of .doc-content's
            // own direct children (a major section: letterhead, title, body,
            // signatures, footer, ...) rather than a paragraph nested inside
            // one of those.
            get isMajorSection() {
                return !!(this.selectedSectionEl && this.$refs.docEditor && this.selectedSectionEl.parentElement === this.$refs.docEditor);
            },
            // Select Whole Section in the panel — walks up from whatever
            // nested text block is currently selected to the enclosing major
            // section, since a click on the text itself always selects the
            // innermost element (needed so a click still places the text
            // cursor normally) and would otherwise make the parent
            // impossible to reach.
            selectParentSection() {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                let el = this.selectedSectionEl;
                while (el.parentElement && el.parentElement !== this.$refs.docEditor) {
                    el = el.parentElement;
                }
                this.selectSectionElement(el);
            },
            // Hover affordance — mirrors selectSection()'s own targeting but
            // only ever touches one element via JS-computed delegation
            // (mouseover bubbles, so this re-fires as the pointer crosses
            // nested descendants; each call recomputes the single nearest
            // selectable block and swaps the hover class onto only that one)
            // rather than a plain CSS :hover rule, which would highlight
            // every ancestor of the pointer at once.
            hoverSection(event) {
                if (!this.editingDocument || !this.$refs.docEditor) return;
                const block = window.DokyuEditor.findSelectableBlock(event.target, this.$refs.docEditor);
                if (block === this.hoveredSectionEl) return;
                this.clearHoverSection();
                if (block && block !== this.selectedSectionEl) {
                    block.classList.add('doc-section-hover');
                    this.hoveredSectionEl = block;
                    this.attachDragHandle(block);
                }
            },
            clearHoverSection() {
                if (this.hoveredSectionEl) {
                    this.hoveredSectionEl.classList.remove('doc-section-hover');
                    if (this.hoveredSectionEl !== this.selectedSectionEl) this.detachDragHandle(this.hoveredSectionEl);
                }
                this.hoveredSectionEl = null;
            },
            // Drag handle (document-editor-spacing-panel.blade.php's own
            // comment explains why this is a separate mechanism from
            // Spacing Before/After) — attached to whatever block-level
            // element findSelectableBlock() resolved (the same one hover/
            // click selection already targets), whether that's a major
            // section (a direct child of .doc-content) or a nested piece
            // inside one (a specific name/title/label paragraph) — so an
            // inner block like Punong Barangay can be repositioned
            // independently of the section it lives in, without making
            // individual words draggable (findSelectableBlock still only
            // ever matches whole block-level elements). Stored as a plain
            // property on the element itself (el.__dragHandle) rather than
            // a separate registry — the handle's lifetime is exactly tied
            // to its block's, and this avoids leaking references if a block
            // is ever removed from the DOM some other way.
            //
            // Positioning the handle (position:absolute, left of the block)
            // requires the block itself to be a positioning context; every
            // major section already carries the `relative` class, but a
            // plain nested <p> usually doesn't, so this sets it inline the
            // first time a handle is attached — inert with no offsets of
            // its own, so it changes nothing about how the block renders.
            attachDragHandle(el) {
                if (!el || el.__dragHandle || !this.$refs.docEditor) return;
                if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
                const handle = window.DokyuEditor.createDragHandleElement();
                handle.addEventListener('pointerdown', (event) => this.onDragHandlePointerDown(event, el, handle));
                handle.addEventListener('keydown', (event) => this.onDragHandleKeyDown(event));
                el.appendChild(handle);
                el.__dragHandle = handle;
            },
            detachDragHandle(el) {
                if (el && el.__dragHandle) {
                    el.__dragHandle.remove();
                    el.__dragHandle = null;
                }
            },
            // Pointer Events (not mouse-specific handlers) so the same code
            // path covers mouse, trackpad, and pen/touch where available —
            // see requirement for touch/pointer support. setPointerCapture
            // redirects every subsequent pointermove/pointerup to the
            // handle itself regardless of where the pointer physically ends
            // up, which is the standard, reliable way to implement a drag
            // gesture without needing window-level listeners. The
            // dp-dragging-section body class (see app.css) is the fix for
            // the cursor/handle-disappears-while-dragging bug: without
            // it, the cursor is only ever grabbing while physically over
            // the dragged element itself, and reverts to whatever's
            // actually underneath (the editable text cursor, or the plain
            // arrow over the modal's own backdrop) the instant a real drag
            // — moving fast, hitting a clamped boundary so the section
            // stops following but the pointer keeps going, or overshooting
            // onto the backdrop — takes the pointer past it, even though
            // the drag itself never actually stops tracking.
            onDragHandlePointerDown(event, el, handle) {
                if (!this.editingDocument) return;
                event.preventDefault();
                try { handle.setPointerCapture(event.pointerId); } catch (error) { /* not fatal: pointermove/up below still work via normal bubbling if capture is unavailable */ }
                this.selectSectionElement(el);
                window.DokyuEditor.startSectionDrag(el, event, this.$refs.docEditor, this.zoomLevel);
                this.isDraggingSection = true;
                el.classList.add('doc-section-dragging');
                document.body.classList.add('dp-dragging-section');
                const onMove = (moveEvent) => {
                    const next = window.DokyuEditor.updateSectionDrag(moveEvent);
                    if (next) {
                        this.sectionOffsetX = next.x;
                        this.sectionOffsetY = next.y;
                    }
                    this.autoScrollDuringDrag(moveEvent);
                };
                const onUp = () => {
                    handle.removeEventListener('pointermove', onMove);
                    handle.removeEventListener('pointerup', onUp);
                    handle.removeEventListener('pointercancel', onUp);
                    window.DokyuEditor.endSectionDrag();
                    this.isDraggingSection = false;
                    el.classList.remove('doc-section-dragging');
                    document.body.classList.remove('dp-dragging-section');
                    this.refreshOverflowWarning();
                };
                handle.addEventListener('pointermove', onMove);
                handle.addEventListener('pointerup', onUp);
                handle.addEventListener('pointercancel', onUp);
            },
            // Fine keyboard nudging after a drag — deliberately scoped to
            // when the handle itself has DOM focus (a deliberate click/tab
            // onto it), never globally while a section is merely selected,
            // so arrow keys keep their normal caret-movement meaning
            // everywhere else, including while typing in the very same
            // section's own text.
            onDragHandleKeyDown(event) {
                if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const step = event.shiftKey ? 10 : 1;
                if (event.key === 'ArrowUp') this.adjustSectionOffsetY(-step);
                else if (event.key === 'ArrowDown') this.adjustSectionOffsetY(step);
                else if (event.key === 'ArrowLeft') this.adjustSectionOffsetX(-step);
                else if (event.key === 'ArrowRight') this.adjustSectionOffsetX(step);
            },
            // Auto-scrolls the zoom viewport when dragging near its top/bottom
            // edge, so a section can be moved farther than what's currently
            // visible — capped low (12px per pointermove) so it never feels
            // like it's racing away from the pointer.
            autoScrollDuringDrag(pointerEvent) {
                const viewport = this.$refs.zoomViewport;
                if (!viewport) return;
                const rect = viewport.getBoundingClientRect();
                const edgeZone = 40;
                const maxSpeed = 12;
                if (pointerEvent.clientY < rect.top + edgeZone) {
                    viewport.scrollTop -= maxSpeed * ((rect.top + edgeZone - pointerEvent.clientY) / edgeZone);
                } else if (pointerEvent.clientY > rect.bottom - edgeZone) {
                    viewport.scrollTop += maxSpeed * ((pointerEvent.clientY - (rect.bottom - edgeZone)) / edgeZone);
                }
            },
            refreshSectionSpacing() {
                if (!this.selectedSectionEl) return;
                const spacing = window.DokyuEditor.readSpacing(this.selectedSectionEl);
                const move = window.DokyuEditor.canMove(this.selectedSectionEl);
                this.sectionSpacing = { before: spacing.before, after: spacing.after, lineHeight: spacing.lineHeight, canMoveUp: move.up, canMoveDown: move.down };
                this.sectionOffsetY = window.DokyuEditor.readVerticalOffset(this.selectedSectionEl);
                this.sectionOffsetX = window.DokyuEditor.readHorizontalOffset(this.selectedSectionEl);
            },
            // A short, human-readable hint for the floating panel's own
            // Selected line — every Dokyu paper has different named
            // pieces (Punong Barangay, an officiant, a payment table row...)
            // with no shared data attribute to hang a label off, so this
            // just previews the block's own text rather than requiring every
            // template to be annotated by hand for this one purpose. Reads
            // from a clone with the drag handle stripped out first — the
            // handle is a child of the very element being labeled, so its
            // own glyph would otherwise get pulled into textContent too.
            get selectedSectionLabel() {
                if (!this.selectedSectionEl) return '';
                const clone = this.selectedSectionEl.cloneNode(true);
                clone.querySelectorAll('.doc-drag-handle').forEach((node) => node.remove());
                const text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
                if (!text) return this.isMajorSection ? 'Section' : 'Block';
                return text.length > 42 ? text.slice(0, 42) + '…' : text;
            },
            adjustSectionOffsetX(delta) {
                if (!this.selectedSectionEl) return;
                this.setSectionOffsetXDirect(this.sectionOffsetX + delta);
            },
            setSectionOffsetXDirect(value) {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                const bounds = window.DokyuEditor.computeHorizontalBounds(this.selectedSectionEl, this.$refs.docEditor, this.zoomLevel / 100);
                const clamped = Math.max(bounds.min, Math.min(bounds.max, Math.round(Number(value) || 0)));
                window.DokyuEditor.setHorizontalOffset(this.selectedSectionEl, clamped);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustSpacingBefore(delta) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setSpacingBefore(this.selectedSectionEl, this.sectionSpacing.before + delta);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustSpacingAfter(delta) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setSpacingAfter(this.selectedSectionEl, this.sectionSpacing.after + delta);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            adjustLineHeight(step) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.setLineHeight(this.selectedSectionEl, this.sectionSpacing.lineHeight + step);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Free vertical repositioning — see setVerticalOffset()'s own
            // comment in dokyu-editor.js for why this exists as a separate
            // mechanism from Spacing Before/After above. Dragging (see
            // onDragHandlePointerDown()) is the primary way to use this now;
            // these numeric/button/keyboard paths are the fine-tuning
            // fallback, so all of them (including the drag itself) share
            // the exact same computeOffsetBounds() clamp — a section can
            // never be pushed out of the printable area through any control.
            adjustSectionOffsetY(delta) {
                if (!this.selectedSectionEl) return;
                this.setSectionOffsetYDirect(this.sectionOffsetY + delta);
            },
            setSectionOffsetYDirect(value) {
                if (!this.selectedSectionEl || !this.$refs.docEditor) return;
                const bounds = window.DokyuEditor.computeOffsetBounds(this.selectedSectionEl, this.$refs.docEditor, this.zoomLevel / 100);
                const clamped = Math.max(bounds.min, Math.min(bounds.max, Math.round(Number(value) || 0)));
                window.DokyuEditor.setVerticalOffset(this.selectedSectionEl, clamped);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            moveSelectedSection(direction) {
                if (!this.selectedSectionEl) return;
                window.DokyuEditor.moveElement(this.selectedSectionEl, direction);
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            resetSectionSpacing() {
                if (!this.selectedSectionEl) return;
                this.selectedSectionEl.style.marginTop = '';
                this.selectedSectionEl.style.marginBottom = '';
                this.selectedSectionEl.style.lineHeight = '';
                this.selectedSectionEl.style.transform = '';
                this.refreshSectionSpacing();
                this.refreshOverflowWarning();
            },
            // Dragging any floating editor window by its title bar —
            // Position & Spacing, Header Seals, and Background all call this
            // same method with their own key into floatingPanels. A plain
            // screen-space UI drag, unrelated to dragging a document
            // section: no zoom conversion, no A4 boundary clamping, since
            // these windows float above the paper (position:fixed) and
            // never affect the document at all. Plain window-level
            // listeners are enough here (no pointer capture needed) since
            // there's no hit-testing precision to protect.
            startPanelDrag(event, panelKey) {
                if (event.target.closest('button')) return;
                event.preventDefault();
                const panel = this.floatingPanels[panelKey];
                const startX = event.clientX;
                const startY = event.clientY;
                const originX = panel.pos.x;
                const originY = panel.pos.y;
                const onMove = (moveEvent) => {
                    panel.pos = { x: originX + (moveEvent.clientX - startX), y: originY + (moveEvent.clientY - startY) };
                };
                const onUp = () => {
                    window.removeEventListener('pointermove', onMove);
                    window.removeEventListener('pointerup', onUp);
                };
                window.addEventListener('pointermove', onMove);
                window.addEventListener('pointerup', onUp);
            },
            refreshOverflowWarning() {
                const paperEl = document.querySelector('#dp-print-area .doc-paper');
                this.documentOverflow = window.DokyuEditor.checkOverflow(paperEl);
            },
            // Viewer zoom (document-editor-zoom-controls.blade.php) — a pure
            // display feature; see the header comment on the zoom exports in
            // resources/js/dokyu-editor.js for why transform:scale() on
            // .doc-zoom-scale never affects the real document, the overflow
            // check above, print, or PDF. .doc-zoom-sizer's explicit
            // width/height (recomputeZoomSizer()) is what makes
            // .doc-zoom-viewport scrollable to the full scaled size, since a
            // transformed element never grows its own ancestors' scrollable
            // area by itself.
            setZoomLevel(level) {
                this.zoomLevel = window.DokyuEditor.clampZoom(level);
                this.$nextTick(() => this.recomputeZoomSizer());
            },
            zoomIn() {
                this.setZoomLevel(window.DokyuEditor.nextZoomLevel(this.zoomLevel, 'in'));
            },
            zoomOut() {
                this.setZoomLevel(window.DokyuEditor.nextZoomLevel(this.zoomLevel, 'out'));
            },
            resetZoom() {
                this.setZoomLevel(100);
            },
            fitPage() {
                const viewportEl = this.$refs.zoomViewport;
                const scaleEl = this.$refs.zoomScale;
                if (!viewportEl || !scaleEl) return;
                this.setZoomLevel(window.DokyuEditor.computeFitZoom(scaleEl, viewportEl.clientWidth, viewportEl.clientHeight));
            },
            recomputeZoomSizer() {
                const scaleEl = this.$refs.zoomScale;
                const sizerEl = this.$refs.zoomSizer;
                if (!scaleEl || !sizerEl) return;
                const size = window.DokyuEditor.computeZoomSizerSize(scaleEl, this.zoomLevel);
                sizerEl.style.width = size.width + 'px';
                sizerEl.style.height = size.height + 'px';
            },
            handleZoomShortcut(event) {
                if (!this.docPreviewOpen) return;
                if (!(event.ctrlKey || event.metaKey)) return;
                if (event.key === '+' || event.key === '=') {
                    event.preventDefault();
                    this.zoomIn();
                } else if (event.key === '-') {
                    event.preventDefault();
                    this.zoomOut();
                } else if (event.key === '0') {
                    event.preventDefault();
                    this.resetZoom();
                }
            },
            // Header seal panel (document-editor-header-seal-panel.blade.php)
            // — targets the header-seal--left / header-seal--right markers
            // every template renders (see barangay-certificate-preview.blade.php
            // / municipal-request-preview.blade.php); municipal papers only
            // render a left seal, so hasRightSeal() lets the panel hide the
            // Right Seal block when there is nothing to control.
            //
            // Size and position are applied to the WRAPPER (header-seal-wrap--
            // left/right), not the <img> itself. Each seal is really two
            // stacked layers in one positioning box: a decorative gold-ringed
            // placeholder circle (shown only if the real seal image is
            // removed or fails to load) sitting behind the actual seal image
            // in front — the image is styled width:100%/height:100% of that
            // box so it always exactly covers the placeholder. Previously
            // size/offset were set on the <img> alone, which left the
            // placeholder circle both a fixed size (causing Large to be
            // capped on one axis by the browser's own img max-width reset,
            // turning it into an oval) and a fixed position (so moving the
            // img uncovered the placeholder's gold ring behind it, looking
            // like a leftover ghost circle). Resizing/moving the wrapper
            // moves and scales both layers together as one object, so
            // nothing is ever left behind and both axes always scale
            // equally. Upload/remove/restore still target the <img> itself
            // since those change its src/visibility, not its box.
            findSealImg(side) {
                const root = document.querySelector('#dp-print-area .doc-content');
                return root ? root.querySelector('.header-seal--' + side) : null;
            },
            findSealWrapper(side) {
                const root = document.querySelector('#dp-print-area .doc-content');
                return root ? root.querySelector('.header-seal-wrap--' + side) : null;
            },
            hasRightSeal() {
                return !!this.findSealImg('right');
            },
            refreshSealOffsets() {
                ['left', 'right'].forEach((side) => {
                    const wrapper = this.findSealWrapper(side);
                    this.sealOffsets[side] = wrapper ? window.DokyuEditor.readSealOffset(wrapper) : { x: 0, y: 0 };
                });
            },
            async handleSealUpload(side, event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const img = this.findSealImg(side);
                if (!img) return;
                const result = await window.DokyuEditor.readImageFile(file);
                if (!result.ok) {
                    this.$dispatch('toast', { message: result.error, variant: 'danger' });
                    return;
                }
                img.src = result.dataUrl;
                img.style.display = '';
            },
            removeSeal(side) {
                const img = this.findSealImg(side);
                if (img) img.style.display = 'none';
            },
            restoreSeal(side) {
                const img = this.findSealImg(side);
                if (!img) return;
                img.style.display = '';
                img.src = img.getAttribute('data-default-src') || img.src;
                const wrapper = this.findSealWrapper(side);
                if (wrapper) {
                    wrapper.style.width = '';
                    wrapper.style.height = '';
                    wrapper.style.transform = '';
                }
                this.refreshSealOffsets();
            },
            setSealSize(side, sizeKey) {
                const wrapper = this.findSealWrapper(side);
                if (wrapper) window.DokyuEditor.applySealSize(wrapper, sizeKey);
            },
            adjustSealOffset(side, axis, delta) {
                const wrapper = this.findSealWrapper(side);
                if (!wrapper) return;
                const current = window.DokyuEditor.readSealOffset(wrapper);
                const next = { x: current.x, y: current.y };
                next[axis] = current[axis] + delta;
                window.DokyuEditor.applySealOffset(wrapper, next.x, next.y);
                this.refreshSealOffsets();
            },
            // Change Background panel (document-editor-background-panel.blade.php)
            // — watermark/paper-color state lives outside .doc-content (see
            // barangay-certificate-preview.blade.php), so unlike typing/drag/
            // seal edits it isn't caught by the Undo/Redo MutationObserver on
            // .doc-content — each setter below schedules its own history
            // snapshot directly (see the Undo/Redo section's own comment).
            setWatermarkOpacity(v) {
                this.ensureBackground();
                this.selected.background.opacity = Number(v);
                this.scheduleHistorySnapshot();
            },
            setWatermarkSize(v) {
                this.ensureBackground();
                this.selected.background.size = v;
                this.scheduleHistorySnapshot();
            },
            setWatermarkPosition(v) {
                this.ensureBackground();
                this.selected.background.position = v;
                this.scheduleHistorySnapshot();
            },
            setPaperColor(hex) {
                this.ensureBackground();
                this.selected.background.paperColor = hex;
                this.scheduleHistorySnapshot();
            },
            resetPaperColor() {
                this.ensureBackground();
                this.selected.background.paperColor = '#FFFFFF';
                this.scheduleHistorySnapshot();
            },
            removeWatermark() {
                this.ensureBackground();
                this.selected.background.src = null;
                this.scheduleHistorySnapshot();
            },
            restoreDefaultWatermark() {
                this.ensureBackground();
                this.selected.background.src = this.defaultWatermarkSrc;
                this.scheduleHistorySnapshot();
            },
            resetBackground() {
                this.selected.background = { ...this.defaultBackground };
                this.scheduleHistorySnapshot();
            },
            async handleWatermarkUpload(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const result = await window.DokyuEditor.readImageFile(file);
                if (!result.ok) {
                    this.$dispatch('toast', { message: result.error, variant: 'danger' });
                    return;
                }
                this.ensureBackground();
                this.selected.background.src = result.dataUrl;
                this.scheduleHistorySnapshot();
            },
            // Blurs the contenteditable region and flips :contenteditable
            // to false BEFORE reading its innerHTML for the capture, rather
            // than after — a defensive ordering, not a complete fix. A real
            // bug exists here: an <img> src changed via direct DOM/JS
            // (a header seal upload, applyTemplate()'s own seal swap, or a
            // watermark replace) can silently revert to its pre-edit value
            // in .doc-content once Save runs, surviving neither an
            // immediate re-check nor a Close -> Reopen. Confirmed to be a
            // render issue, not a data-loss one — this.selected.
            // customDocumentHtml itself stays correct throughout (checked
            // directly against a live x-text bound to that same
            // expression) — only what actually gets painted from it
            // reverts. Ruled out by direct testing as the cause: content
            // size (a tiny data: URL reproduces it identically to a 500KB
            // one), the data: vs http: URL distinction, attribute-mutation
            // vs whole-node replacement, and contenteditable state at the
            // moment of the src change. Typed text via execCommand, and
            // non-src mutations via direct JS (e.g. removeSeal's
            // display:none, or removing a node outright), all survive
            // fine — this reproduces only for the src attribute of an
            // <img> specifically. See the final report for the full
            // diagnosis. reapplyTemplateSeals() below (called from
            // startEditingDocument()) is the actual fix for the common
            // case — a template-selected seal re-derives its src from the
            // template definition on every reopen rather than trusting
            // whatever the captured HTML kept. A seal replaced by a direct
            // upload (handleSealUpload) has no template to re-derive from,
            // so that specific case remains a known, documented
            // limitation; this reordering is kept regardless as a safe,
            // non-harmful improvement.
            // Stores the edited copy on the request (owner decision
            // 2026-10-06, option B): the next officer who opens it, or this one
            // after a reload, sees the same paper. The server cleans the HTML
            // and answers with what it kept, which replaces the copy here.
            async saveDocumentEdits() {
                const r = this.selected;
                const el = this.getDocContentEl();
                if (el) el.blur();
                this.editingDocument = false;
                await this.$nextTick();
                const editor = this.$refs.docEditor || this.getDocContentEl();
                if (editor) r.customDocumentHtml = window.DokyuEditor.cleanHtml(editor.innerHTML);
                this.reapplyTemplateSeals();
                this.documentEditBackup = null;
                this.backgroundEditBackup = null;
                this.templateKeyEditBackup = null;
                this.teardownHistoryTracking();
                this.resetEditorUiState();
                try {
                    const res = await $api.put(this.apiBase(r.ref) + '/prepared-document', {
                        html: window.DokyuEditor.iconsToPlaceholders(r.customDocumentHtml || ''),
                        background: r.background || null,
                        template_key: this.activeTemplateKey,
                    });
                    this.applyDetail(r, res.data);
                    this.editedInWindow = null;
                    this.$dispatch('toast', { message: 'Saved. Staff who open this request see this copy; the resident sees the template.', variant: 'success' });
                } catch (err) {
                    // Kept here, so it can still be printed or saved again.
                    this.editedInWindow = r.ref;
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not save the document.') + ' The edit is still in this window.', variant: 'error' });
                }
                this.$nextTick(() => { this.recomputeZoomSizer(); window.renderIcons?.(); });
            },
            // Removes the stored copy for everyone; the audit log keeps that it existed.
            async useTemplateAgain() {
                const r = this.selected;
                if (!r?.prepared) return;
                if (!window.confirm('Go back to the template? The edited copy is removed for everyone who opens this request.')) return;
                try {
                    const res = await $api.delete(this.apiBase(r.ref) + '/prepared-document');
                    this.applyDetail(r, res.data);
                    this.editedInWindow = null;
                    this.$dispatch('toast', { message: 'Back to the template.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not go back to the template.'), variant: 'error' });
                }
            },
            // Saving needs the service's release permission and a request
            // still being prepared; released paper stays as handed over.
            get mayEditDocument() {
                return ['Approved', 'Mark to Release'].includes(this.selected?.status) && this.may('release');
            },
            printDocument() {
                if (this.editingDocument) this.saveDocumentEdits();
                document.body.classList.add('dp-printing');
                window.print();
            },
            async downloadDocument() {
                if (this.editingDocument) this.saveDocumentEdits();
                const el = document.getElementById('dp-print-area');
                if (!el) return;
                let html2pdf;
                try {
                    html2pdf = await window.loadHtml2Pdf();
                } catch (e) {
                    this.$dispatch('toast', { message: 'Could not load the PDF generator. Check your connection and try again.', variant: 'error' });
                    return;
                }
                const citizenName = (this.selected?.citizen || 'Citizen').replace(/[^a-z0-9]+/gi, '-');
                const docName = (this.resolvedDocument?.certTitle || this.selected?.type || 'Document').replace(/[^a-z0-9]+/gi, '-');
                document.body.classList.add('dp-printing');
                await this.$nextTick();
                // The default seals/watermark are real image files (not
                // inlined), and a just-uploaded custom seal/watermark is a
                // data: URL that still needs a browser decode tick — either
                // way, capturing before they've finished loading bakes a
                // blank box into the PDF instead of the actual image.
                await window.DokyuEditor.waitForImages(el);
                // html2canvas 1.4.1 only understands the rgb/rgba/hsl/hsla
                // color functions and throws on anything else — Tailwind v4
                // compiles its whole palette as oklch(), which is exactly
                // what was breaking every Download PDF attempt (confirmed
                // via direct reproduction: it throws Attempting to parse an
                // unsupported color function oklch). html2canvas resolves
                // background/image resources against the LIVE document
                // before its own clone (and onclone) ever exists, so the
                // fix has to touch the real page rather than a clone —
                // sanitizeExportColors() only ever swaps in a mathematically
                // identical rgb() for each oklch() it finds, so nothing
                // visibly changes, and restoreColors() (always run in
                // finally) puts every touched property back immediately
                // after capture.
                const restoreColors = window.DokyuEditor.sanitizeExportColors(document.body);
                try {
                    await html2pdf().set({
                        margin: 0,
                        filename: 'Esperanza-' + docName + '-' + citizenName + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff',
                            // html2canvas briefly inserts its clone into the
                            // real document to read accurate computed styles
                            // before rendering it — Alpine's own global
                            // MutationObserver picks that insertion up and
                            // tries to initialize x-data/x-show/etc. on it as
                            // if it were live markup, throwing (no reactive
                            // scope exists for an orphan clone: confirmed via
                            // direct reproduction, a ReferenceError for
                            // selected/documentBackground/etc. on every
                            // directive) and, worse, x-show's
                            // failed expression can leave the element it's
                            // on hidden — which is why #dp-print-area itself
                            // (inside the modal's own x-show open wrapper)
                            // was measuring 0x0 in html2canvas's own clone
                            // despite being correctly sized on the live page
                            // the whole time. Every Alpine directive has
                            // already done its job by the time the clone is
                            // made — the visual result is fully baked into
                            // the DOM/computed styles — so the clone needs
                            // none of them; stripping x-*/:*/@* attributes
                            // here is the standard fix for this exact class
                            // of html2canvas + Alpine(/Vue/React) interop bug.
                            onclone: (clonedDoc) => {
                                clonedDoc.querySelectorAll('*').forEach((node) => {
                                    Array.from(node.attributes).forEach((attr) => {
                                        if (attr.name.startsWith('x-') || attr.name.startsWith(':') || attr.name.startsWith('@')) {
                                            node.removeAttribute(attr.name);
                                        }
                                    });
                                });
                            },
                        },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'], avoid: ['.doc-paper'] },
                    }).from(window.DokyuEditor.exportClone(el)).save();
                } catch (error) {
                    console.error('PDF generation failed:', error?.message || error, error);
                    this.$dispatch('toast', { message: 'Unable to download the PDF. Please try Print and choose Save as PDF.', variant: 'danger' });
                } finally {
                    restoreColors();
                    document.body.classList.remove('dp-printing');
                }
            },
            requests: [],
            requestsLoading: true,
            requestsFailed: false,
            detailLoading: false,
            actionStatuses: @js($actionStatuses),
            doneStatuses: @js($doneStatuses),
            formSpecsByType: @js($formSpecsByType),
            wizardFieldsByKey: @js($wizardFieldsByKey),
            catalogNameByKey: @js($catalogNameByKey),
            dokyuClassification: @js($dokyuClassification),
            referencePdfByType: @js($referencePdfByType),
            typeToServiceGuideKey: @js($typeToServiceGuideKey),
            requestType: 'dokyu',
            // The request's own assessment (detail.payment), in the shape
            // x-admin.payment-receipt reads; it renders once a receipt exists.
            get selectedPayment() {
                const p = this.selected?.payment;
                if (!p) return null;
                return {
                    ref: p.ref, receipt: p.receipt_no, method: p.method, status: p.status,
                    citizen: this.selected.citizen, item: this.selected.type,
                    date: p.date ? new Date(p.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—',
                    amount: '₱' + Number(p.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }),
                };
            },
            standardReasons: @js($validationGuide['standard_reasons']),
            decisionOutcomes: @js($validationGuide['decision_outcomes']),
            // Evidence Strength Hierarchy (guide section 1.2) — a light
            // keyword heuristic since this is mock/reference data, not a
            // real document classifier.
            evidenceTier(name) {
                const n = (name || '').toLowerCase();
                if (n.includes('interview') || n.includes('case assessment') || n.includes('registry') || n.includes('source verification')) return 4;
                if (n.includes('government') || n.includes('psa') || n.includes('valid') && n.includes('id') || n.includes('medical certificate') || n.includes('hospital') || n.includes('tax declaration') || n.includes('death certificate') || n.includes('birth certificate') || n.includes('receipt')) return 3;
                if (n.includes('barangay') || n.includes('school') || n.includes('employer') || n.includes('lease') || n.includes('utility') || n.includes('clearance')) return 2;
                return 1;
            },
            insertStandardReason(label) {
                const r = this.standardReasons.find(x => x.label === label);
                if (r) this.rejectRemarks = r.message;
            },
            get flaggedRequirements() {
                return (this.selected?.requirements || []).filter(r => r.reject_reason || r.remarks);
            },
            useRequirementReasons() {
                if (!this.flaggedRequirements.length) return;
                this.rejectRemarks = this.flaggedRequirements
                    .map(r => r.name + ': ' + (r.remarks || r.reject_reason))
                    .join('\n\n');
            },
            // Full mobile form preview helpers — see
            // components/admin/request-form-review.blade.php.
            formatFieldDate(iso) {
                if (!iso) return '—';
                // The app sends dates with a time (2026-01-31T00:00:00.000): only
                // the date part is read, or the day became NaN and the field
                // said 'Invalid Date' (live test 2026-10-07, BUG-1).
                const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number);
                if (!y || !m || !d) return String(iso);
                return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            },
            derivedAgeDisplay(step, field) {
                const source = step.fields.find(f => f.key === field.derivedFromKey);
                if (!source?.answer) return '—';
                const [y] = source.answer.split('-').map(Number);
                return (2026 - y) + ' years old';
            },
            fieldIcon(field) {
                const byKey = {
                    dateOfBirth: 'calendar-days', dateOfDeath: 'calendar-days',
                    placeOfBirth: 'map-pin', placeOfDeath: 'map-pin',
                    sex: 'venus-and-mars', civilStatus: 'heart-handshake',
                    occupation: 'briefcase', presentOccupation: 'briefcase',
                    fatherOccupation: 'briefcase', motherOccupation: 'briefcase',
                    businessName: 'store', businessNature: 'tag', yearsOperating: 'history',
                    capitalAmount: 'wallet', monthlyIncome: 'wallet', annualIncome: 'wallet', parentsMonthlyIncome: 'wallet',
                    schoolName: 'school', yearOrGradeLevel: 'graduation-cap', degreeProgramOrCourse: 'book-open',
                    lastSchoolAverageGrade: 'percent', educationalAttainment: 'graduation-cap',
                    fatherName: 'user', motherName: 'user', spouseName: 'heart', personFullName: 'user',
                    deceasedFullName: 'user', motherMaidenName: 'user', emergencyContactName: 'user',
                    communityInvolvement: 'heart-handshake', postGraduationPlans: 'flag',
                    familyComposition: 'users', needsOrProblems: 'hand-heart',
                    emergencyContactNumber: 'phone', residencyType: 'home', citizenship: 'flag',
                    religion: 'landmark', philsysIdNumber: 'id-card', employmentStatus: 'briefcase',
                    lastGovtOffice: 'landmark', lastGovtPosition: 'briefcase', lastGovtYear: 'calendar',
                    isPantawidBeneficiary: 'check-square', isIndigenousPerson: 'check-square',
                    receivingPension: 'check-square', confirmFirstTime: 'check-square',
                    soloParentClassification: 'list-checks', purpose: 'clipboard-list',
                };
                if (byKey[field.key]) return byKey[field.key];
                const byType = { date: 'calendar-days', derivedAge: 'cake', select: 'list-checks', multiselect: 'list-checks', checkbox: 'check-square', number: 'hash', textarea: 'align-left', text: 'pencil-line' };
                return byType[field.type] || 'file-text';
            },
            returnToProfile: null,
            // Arriving from a resident's profile via Open in Dokyu — pre-filter to
            // their name and surface a way back to exactly where they came from.
            init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                const citizen = params.get('citizen');
                // From the dashboard's Open Requests by Barangay card.
                if (params.get('barangay')) { this.barangayFilter = params.get('barangay'); this.tab = 'all'; }
                if (params.get('open') === '1') { this.openOnly = true; this.tab = 'all'; }
                this.mineOnly = params.get('assigned') === 'me';
                this.overdueOnly = params.get('overdue') === '1';
                if (ref || citizen) {
                    this.search = ref || citizen;
                    this.view = 'requests';
                    this.tab = 'all';
                }
                const stored = sessionStorage.getItem('returnToProfile');
                if (stored) {
                    try { this.returnToProfile = JSON.parse(stored); } catch (e) { this.returnToProfile = null; }
                    sessionStorage.removeItem('returnToProfile');
                }
                this.loadOfficials();
                this.loadRequests().then(() => {
                    // Arriving from a notification (see components/admin/topbar.blade.php)
                    // — jump straight into that exact request, not just the filtered list.
                    if (!ref) return;
                    const match = this.requests.find(r => r.ref === ref);
                    if (match) this.$nextTick(() => this.openRequest(match));
                });
            },
            // ---- The queue, from the API -----------------------------------
            // Every action below calls the lifecycle endpoint it names and then
            // takes the server's answer as the new state. The old localStorage
            // 'progress' layer, the demo reset and the simulated resubmission
            // are gone: the server is the record, and a reload shows it.
            apiBase(ref) {
                return '/admin/' + this.requestType + '/requests/' + encodeURIComponent(ref);
            },
            // Assign to me (and its undo): POST/DELETE .../assign, then the
            // server's answer. It used to change only this screen, so a reload
            // showed the case unassigned again.
            // Filing to release, over the requests released so far. The card
            // showed a fixed 2.4 days (trend -0.6) before any request had
            // been released.
            get releasedDurations() {
                return this.requests
                    .filter((r) => r.released_at && r.submitted_at)
                    .map((r) => (new Date(r.released_at) - new Date(r.submitted_at)) / 86400000)
                    .filter((days) => days >= 0);
            },
            get avgProcessingLabel() {
                const all = this.releasedDurations;
                if (!all.length) return '—';
                const avg = all.reduce((a, b) => a + b, 0) / all.length;
                if (avg >= 1) return avg.toFixed(1) + ' days';
                const hours = Math.max(1, Math.round(avg * 24));
                return hours + (hours === 1 ? ' hr' : ' hrs');
            },
            get avgProcessingNote() {
                const n = this.releasedDurations.length;
                return n ? 'Filing to release, ' + n + ' released' : 'No request released yet';
            },
            async setAssignment(assign) {
                if (!this.selected || this.processing) return;
                this.processing = true;
                try {
                    const url = this.apiBase(this.selected.ref) + '/assign';
                    const res = assign ? await $api.post(url) : await $api.delete(url);
                    const who = res.data?.assigned ?? null;
                    const id = res.data?.assigned_employee_id ?? null;
                    this.selected.assigned = who;
                    this.selected.assigned_employee_id = id;
                    const row = this.requests.find((x) => x.ref === this.selected.ref);
                    if (row) { row.assigned = who; row.assigned_employee_id = id; }
                    this.$dispatch('toast', { message: assign ? 'Assigned to you.' : 'No longer assigned to you.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not change who is assigned.'), variant: 'error' });
                } finally {
                    this.processing = false;
                }
            },
            apiMessage(err, fallback) {
                return err && err.localised ? err.localised('en') : fallback;
            },
            formatDay(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return isNaN(d) ? iso : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            // Assigned to me and Overdue go to the server (scope round
            // 2026-10-15); only the latest of loads overlapping is kept.
            async loadRequests() {
                const seq = ++this.loadSeq;
                this.requestsLoading = true;
                this.requestsFailed = false;
                try {
                    const params = { sort: '-submitted_at' };
                    if (this.mineOnly) params.assigned = 'me';
                    if (this.overdueOnly) params.overdue = 1;
                    const res = await $api.getAll('/admin/' + this.requestType + '/requests', { params });
                    if (seq === this.loadSeq) this.requests = (res.data || []).map((row) => this.fromApi(row));
                } catch (err) {
                    if (seq === this.loadSeq) this.requestsFailed = true;
                } finally {
                    if (seq === this.loadSeq) {
                        this.requestsLoading = false;
                        this.$nextTick(() => window.renderIcons?.());
                    }
                }
            },
            // Real officials per barangay (GET /barangays), over the config
            // defaults the prepared-document letterhead falls back to.
            async loadOfficials() {
                try {
                    const res = await $api.get('/barangays');
                    (res.data || []).forEach((b) => {
                        if (!b.name || !b.officials) return;
                        this.barangayCatalog[b.name] = { ...(this.barangayCatalog[b.name] || { label: b.name }), officials: b.officials };
                    });
                } catch { /* the config officials stay */ }
            },
            fromApi(row) {
                const type = row.service || row.service_key || 'Request';
                // The name-keyed tables below use this catalog's name for the
                // service, which is not always the backend's display name.
                const name = this.catalogNameByKey[row.service_key] || type;
                const cls = this.dokyuClassification[name] || this.dokyuClassification[type] || {};
                return {
                    ref: row.ref,
                    citizen: row.applicant || '—',
                    // The requester's approved photo (signed, short-lived), or null.
                    photo: row.applicant_photo || null,
                    // Brought in from the office's own records, and the registry
                    // resident it was matched to (2026-10-12).
                    imported_from: row.imported_from || null,
                    resident_ref: row.resident_ref || null,
                    type,
                    service_key: row.service_key,
                    office: row.office || cls.office || '—',
                    barangay: row.barangay || '',
                    submitted: this.formatDay(row.submitted),
                    submitted_at: row.submitted,
                    released_at: row.released || null,
                    status: row.status,
                    category: cls.category || 'Other Municipal Services',
                    level: cls.level || 'Municipal',
                    reference_pdf: this.referencePdfByType[name] || this.referencePdfByType[type] || null,
                    service_guide_key: this.typeToServiceGuideKey[name] || this.typeToServiceGuideKey[type] || null,
                    assigned: row.assigned || null,
                    assigned_employee_id: row.assigned_employee_id || null,
                    due: row.due || null,
                    overdue: !!row.overdue,
                    issued_documents: [],
                    workflow: null,
                    purpose: '',
                    requirements: null,
                    remarks: null,
                    payment: null,
                    release_evidence: null,
                    release_evidence_at: null,
                };
            },
            // The request's form answers, labelled by the form they were filed
            // on. A request from the app answers the app's form spec (looked up
            // by name); one from the portal answers the portal wizard's fields
            // (by service key) -- their field keys differ, so the answers say
            // which applies. Anything neither knows is still shown, under its
            // own name, rather than dropped.
            formStepsFor(type, formData, serviceKey) {
                const data = formData || {};
                const answered = (v) => v !== null && v !== undefined && v !== '' && !(Array.isArray(v) && !v.length);
                const seen = new Set(['purpose']);
                let steps = [];
                const spec = this.formSpecsByType[this.catalogNameByKey[serviceKey] || type] || this.formSpecsByType[type];
                const specFields = (spec?.steps || []).flatMap((s) => s.fields || []);
                const wizardFields = this.wizardFieldsByKey[serviceKey] || [];
                // The two forms share a few keys (age, purpose), so pick the one
                // that covers more of the answers, not the first that covers any.
                const covered = (fields) => fields.filter((f) => f.key !== 'purpose' && answered(data[f.key])).length;
                const specCovers = covered(specFields);
                const wizardCovers = covered(wizardFields);
                if (specCovers > 0 && specCovers >= wizardCovers) {
                    steps = spec.steps.map((step) => ({
                        label: step.label,
                        description: step.description || null,
                        fields: (step.fields || []).map((f) => { seen.add(f.key); return this.answerField(f, data[f.key]); }),
                    }));
                } else if (wizardCovers > 0) {
                    steps = [{
                        label: 'Application details',
                        description: null,
                        fields: wizardFields.map((f) => { seen.add(f.key); return this.answerField(f, data[f.key]); }),
                    }];
                }
                const extra = Object.keys(data).filter((k) => !seen.has(k) && answered(data[k]));
                if (extra.length) {
                    steps.push({
                        label: steps.length ? 'Other details' : 'Application details',
                        description: null,
                        fields: extra.map((k) => this.answerField({ key: k, label: k.replace(/([A-Z])/g, ' $1').replace(/[_-]+/g, ' ').replace(/^./, (c) => c.toUpperCase()), type: 'text' }, data[k])),
                    });
                }
                return steps;
            },
            // One answer in the shape x-admin.request-form-review prints. The
            // portal's field types are mapped onto the review's (a checkbox
            // group is a multiselect; a table prints one line per row), and an
            // array or object answer is joined instead of printing as
            // '[object Object]'.
            answerField(f, value) {
                const type = { 'checkbox-group': 'multiselect', derived_age: 'text', table: 'textarea' }[f.type] || f.type || 'text';
                let answer = value ?? null;
                if (type === 'multiselect') {
                    answer = Array.isArray(answer) ? answer : (answer ? [String(answer)] : []);
                } else if (Array.isArray(answer)) {
                    answer = answer.map((row) => (row && typeof row === 'object')
                        ? Object.values(row).filter((v) => v !== '' && v !== null && v !== undefined).join(' · ')
                        : String(row)).filter(Boolean).join('; ');
                } else if (answer && typeof answer === 'object') {
                    answer = Object.entries(answer).map(([k, v]) => k + ': ' + v).join(', ');
                }
                return { ...f, type, answer };
            },
            fromApiRequirement(q) {
                return {
                    key: q.key,
                    name: q.label,
                    // The API's Pending Review is what this screen calls Submitted.
                    status: q.status === 'Pending Review' ? 'Submitted' : q.status,
                    reject_reason: q.reason,
                    remarks: q.remarks,
                    file: q.file?.name || null,
                    file_url: q.file?.url || null,
                    file_mime: q.file?.mime || null,
                    // Not the file's URL: the API sends it as a download, which an <img> will not show. Photos open in the viewer (window.imageBlobUrl).
                    preview_asset: null,
                    file_notice: window.fileNotice(q.file),
                    versions: (q.versions || []).map((v) => ({
                        file: v.file?.name || null,
                        file_url: v.file?.url || null,
                        status: v.status === 'Pending Review' ? 'Submitted' : v.status,
                        date: this.formatDay(v.archived_at),
                        reject_reason: v.reason,
                        remarks: v.remarks,
                    })),
                    _decidingOutcome: null,
                    _reason: '',
                    _remarks: '',
                    _showVersions: false,
                };
            },
            applyDetail(r, d) {
                r.status = d.status;
                r.photo = d.applicant_photo || r.photo || null;
                r.photo = d.applicant_photo || r.photo || null;
                r.photo = d.applicant_photo || r.photo || null;
                r.office = d.office || r.office;
                r.permitted = d.permitted || null;
                r.remarks = d.decision_remarks || null;
                r.form_data = d.form_data || {};
                r.purpose = (d.form_data && d.form_data.purpose) ? [].concat(d.form_data.purpose).join(', ') : '';
                r.form_steps = this.formStepsFor(r.type, d.form_data, r.service_key);
                // The document's fields are filled from these answers: a snapshot
                // taken before they arrived (the list row, the dialog's first draw)
                // has only the name, so it is rebuilt from them. A Certificate of
                // Indigency printed blank but for the name (2026-10-27). Not while
                // this officer is editing it; hand-authored ones are kept.
                if (!r.previewType && !(this.editingDocument && this.selected === r)) r.formSnapshot = null;
                r.requirements = (d.requirements || []).map((q) => this.fromApiRequirement(q));
                r.assigned = 'assigned' in d ? d.assigned : r.assigned;
                r.assigned_employee_id = 'assigned_employee_id' in d ? d.assigned_employee_id : r.assigned_employee_id;
                r.due = d.due || r.due || null;
                r.overdue = 'overdue' in d ? !!d.overdue : r.overdue;
                // Control numbers, codes and QR for the printed copy (scope round 2026-10-15).
                r.issued_documents = d.issued_documents || [];
                // A Senior Citizen, PWD or Solo Parent ID: the number its office
                // writes, asked before it is issued; and the IDs the resident
                // already holds (MSWDO forms, 2026-10-26).
                r.idNumberSpec = d.id_number || null;
                r.sectorIds = d.applicant_sector_ids || [];
                if (r._idNumber === undefined) r._idNumber = '';
                // The service's steps and which are done (2026-10-17).
                r.workflow = d.workflow || null;
                r.payment = d.payment || null;
                // The officer's edited copy, stored on the request (owner
                // decision 2026-10-06, option B), or the template when there
                // is none. Not while this officer is in the middle of editing it.
                if (!(this.editingDocument && this.selected === r)) {
                    const pd = d.prepared_document || null;
                    r.prepared = pd ? { by: pd.edited_by || 'an officer', at: pd.edited_at, editable: !!pd.editable } : null;
                    r.customDocumentHtml = pd ? pd.html : null;
                    r.background = pd && pd.background ? pd.background : null;
                    r.templateKey = pd ? (pd.template_key || null) : null;
                    if (pd) this.$nextTick(() => window.renderIcons?.());
                }
                r.remarks_log = d.remarks || [];
                r.history = (d.history || []).map((h) => ({ stage: h.to, date: this.formatDay(h.at), by: h.actor_name || '—', note: window.historyNote(h.trigger) }));
                const proof = d.proof_of_release || {};
                if (proof.captured) {
                    r.release_evidence = proof.file?.url || r.release_evidence || null;
                    r.release_evidence_mime = (proof.file?.mime || '') + ' ' + (proof.file?.name || '');
                    r.release_evidence_at = proof.note || 'on file';
                    r._proofOnFile = true;
                }
            },
            async refreshSelected() {
                if (!this.selected) return;
                const r = this.selected;
                const res = await $api.get(this.apiBase(r.ref));
                this.applyDetail(r, res.data);
                this.$nextTick(() => window.renderIcons?.());
            },
            backToProfile() {
                if (!this.returnToProfile) return;
                sessionStorage.setItem('reopenResident', JSON.stringify(this.returnToProfile));
                window.location.href = '/admin/constituents';
            },
            get categories() { return [...new Set(this.requests.map(r => r.category))].sort(); },
            get offices() { return [...new Set(this.requests.map(r => r.office))].sort(); },
            get isMunicipalScope() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal';
            },
            barangays: @js($barangays),
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Processing': ['bg-brand-50 text-brand-700 ring-brand-200', 'bg-brand-500'],
                'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Mark to Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Released': ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
            },
            matchesTab(r) {
                if (this.tab === 'all') return true;
                if (this.tab === 'action') return this.actionStatuses.includes(r.status);
                if (this.tab === 'release') return r.status === 'Mark to Release';
                if (this.tab === 'done') return this.doneStatuses.includes(r.status);
                return true;
            },
            get filteredRequests() {
                const q = this.search.trim().toLowerCase();
                return this.requests.filter(r =>
                    this.$store.session.inScope(r.barangay) &&
                    this.$store.session.inDeptScope(r.office) &&
                    this.matchesTab(r) &&
                    (this.categoryFilter === '' || r.category === this.categoryFilter) &&
                    (this.officeFilter === '' || r.office === this.officeFilter) &&
                    (this.levelFilter === '' || r.level === this.levelFilter) &&
                    (this.assignedFilter === '' || (this.assignedFilter === 'unassigned' ? !r.assigned : r.assigned === this.assignedFilter)) &&
                    (this.barangayFilter === '' || r.barangay === this.barangayFilter) &&
                    (this.importFilter === '' || r.imported_from === this.importFilter) &&
                    (!this.openOnly || !this.terminalStatuses.includes(r.status)) &&
                    (q === '' || r.ref.toLowerCase().includes(q) || r.citizen.toLowerCase().includes(q))
                );
            },
            clearAdvancedFilters() { this.categoryFilter = ''; this.officeFilter = ''; this.levelFilter = ''; this.assignedFilter = ''; this.barangayFilter = ''; },
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Completed': 4, 'Rejected': 0 };
                return map[status] ?? 0;
            },
            // Opening a freshly-submitted request IS starting its review —
            // no separate 'Begin Verification' click first. A brand-new
            // Submitted request auto-advances to Under Verification the
            // moment an admin actually opens it; anything already past
            // that stays exactly where it is.
            // Opening is itself the server's trigger: GET on a Submitted
            // request moves it to Under Verification (RequestLifecycle::open).
            async openRequest(r) {
                this.selected = r;
                this.rejectRemarks = '';
                this.previewDoc = null;
                this.open = true;
                this.detailLoading = true;
                try {
                    await this.refreshSelected();
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not open ' + r.ref + '.'), variant: 'error' });
                } finally {
                    this.detailLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            // Every status transition below still writes to local Alpine
            // state instead of calling the real lifecycle endpoints (see
            // PRODUCTION_READINESS.md), so there is no request on the
            // backend for a server-side notification to be about yet.
            // $store.citizenNotifications now reads real rows from
            // GET /citizen/notifications — the moment this modal's actions
            // call the real POST .../reject|approve|... endpoints, the
            // notification for that step arrives on the citizen's own next
            // load() for free, from NotificationService::notifyTransition(),
            // and this no-op (kept only so every existing call site below
            // still resolves) should be deleted along with its calls.
            // The resident is notified by the server on every transition
            // (NotificationService::notifyTransition); nothing to send here.
            async reject() {
                if (this.processing || !this.rejectRemarks.trim()) return;
                this.processing = true;
                const r = this.selected;
                try {
                    const res = await $api.post(this.apiBase(r.ref) + '/reject', { decision_remarks: this.rejectRemarks.trim() });
                    this.applyDetail(r, res.data);
                    this.rejectRemarks = '';
                    this.$dispatch('toast', { message: 'Request ' + r.ref + ' rejected.', variant: 'error' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not reject ' + r.ref + '.'), variant: 'error' });
                } finally { this.processing = false; }
            },
            rejectReasons: ['Invalid document', 'Incorrect document', 'Unreadable', 'Expired', 'Wrong document', 'Missing information', 'Does not meet requirement', 'Other'],
            // Plain-language read on what to do next, shown in the System
            // Advice panel beside the stepper — mirrors the Esperanza Mobile
            // app's own VerificationStatusPanel pattern (icon + title +
            // explanation) so the admin isn't left to infer it from the raw
            // requirement statuses alone.
            // Distinct advice per actual stage — not just per document-review
            // outcome. Once all requirements clear Approved, the request still
            // has to move through Approved -> Mark to Release -> Released,
            // and each of those calls for a genuinely different next action
            // (prepare the document, capture Proof of Release, hand it over),
            // so the panel keeps saying something new instead of repeating
            // 'Ready to Approve' all the way to the end.
            get processAdvice() {
                if (!this.selected) return { icon: 'info', tone: 'brand', title: 'Reviewing', message: '' };
                if (this.selected.status === 'Rejected') {
                    return { icon: 'circle-x', tone: 'rose', title: 'Rejected', message: this.selected.remarks || 'This request was rejected.' };
                }
                if (this.terminalStatuses.includes(this.selected.status)) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Finalized', message: 'This request is complete — no further action is needed.' };
                }
                if (this.selected.status === 'Approved') {
                    return { icon: 'package-check', tone: 'indigo', title: 'Prepare Document', message: 'Verification is complete. Prepare the requested document, then mark it Mark to Release.' };
                }
                if (this.selected.status === 'Mark to Release') {
                    if (this.feeOwed) return { icon: 'receipt', tone: 'amber', title: 'Fee Not Paid', message: this.feeOwed + ' The cashier issues it on Payments.' };
                    return this.selected.release_evidence
                        ? { icon: 'circle-check', tone: 'emerald', title: 'Ready to Release', message: 'Proof of Release is on file. You can now release this document to the citizen.' }
                        : { icon: 'camera', tone: 'amber', title: 'Awaiting Proof of Release', message: 'Capture or upload Proof of Release (see the panel below) before this can be released to the citizen.' };
                }
                if (this.selected.status === 'Under Review') {
                    const flagged = (this.selected.requirements || []).filter(r => r.status === 'Flagged for Replacement').length;
                    const resubmitted = (this.selected.requirements || []).filter(r => r.status === 'Resubmitted').length;
                    if (flagged === 0 && resubmitted > 0) {
                        return { icon: 'circle-check', tone: 'sky', title: 'Ready to Approve', message: 'The citizen resubmitted corrected documents and everything now checks out. You can approve this request directly.' };
                    }
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Citizen', message: (flagged ? flagged + ' document(s) flagged for replacement — ' : '') + 'this request is waiting for the citizen to resubmit corrected documents.' };
                }
                // Under Verification — the actual document-review phase,
                // where the advice reflects whatever is concretely blocking
                // approval right now.
                const reqs = this.selected.requirements || [];
                const rejected = reqs.filter(r => r.status === 'Rejected').length;
                const pending = reqs.filter(r => r.status === 'Submitted' || r.status === 'Under Review').length;
                if (rejected > 0) {
                    return { icon: 'file-x', tone: 'rose', title: 'Action Needed', message: rejected + ' of ' + reqs.length + ' document(s) were rejected — the citizen must resubmit before this can proceed.' };
                }
                if (pending > 0) {
                    return { icon: 'clock', tone: 'amber', title: 'Awaiting Review', message: pending + ' of ' + reqs.length + ' document(s) still need review before this can be approved.' };
                }
                if (reqs.length && reqs.every(r => r.status === 'Approved')) {
                    return { icon: 'circle-check', tone: 'emerald', title: 'Ready to Approve', message: 'All submitted documents are verified and complete. You can proceed to the next stage.' };
                }
                return { icon: 'info', tone: 'brand', title: 'In Progress', message: 'Review the submitted application and documents below, then choose a decision.' };
            },
            // Reject is available on the first pass (Under Verification) and
            // also while a request is sitting Under Review — a citizen who
            // never corrects, or whose original submission turns out to be
            // unsalvageable, shouldn't be stuck waiting forever for a
            // resubmission that may never come.
            get canReject() {
                return this.selected && ['Under Verification', 'Under Review'].includes(this.selected.status) && this.may('reject');
            },
            // Approve waits until every document is accepted, as the server
            // does (Esperanza bugs QA, 2026-10-06: a permit reached Released
            // with 2 of 7 documents checked). '' when nothing holds it back.
            // A flagged document does not unblock it: the resident replaces it
            // and the officer approves the new file, so the footer says who is
            // being waited for.
            get approveBlocked() {
                if (this.primaryPermission === 'release') return this.selected?.status === 'Mark to Release' ? this.feeOwed : '';
                if (this.primaryPermission !== 'approve') return '';
                const reqs = this.selected?.requirements || [];
                const open = reqs.filter((r) => r.status !== 'Approved').length;
                if (!open) return '';
                const flagged = reqs.filter((r) => r.status === 'Flagged for Replacement').length;
                if (flagged === open) return 'Waiting for the resident to replace ' + (flagged === 1 ? 'the flagged document.' : 'the ' + flagged + ' flagged documents.');
                return 'Approve every document first: ' + open + ' not approved yet' + (flagged ? ', ' + flagged + ' of them waiting for the resident to replace.' : '.');
            },
            // The server refuses Release while a fee that can be receipted has
            // no receipt (FEE_NOT_PAID). A provisional fee (the LGU has not set
            // its schedule) cannot be receipted, so it does not hold it.
            get feeOwed() {
                const p = this.selected?.payment;
                if (!p || !(Number(p.amount) > 0) || p.receipt_no || p.provisional) return '';
                return 'Collect the fee first: ' + p.ref + ' (₱' + Number(p.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ') has no official receipt yet.';
            },
            // The permission the primary button needs at this stage.
            get primaryPermission() {
                const st = this.selected?.status;
                if (['Under Verification', 'Under Review', 'Resubmitted'].includes(st)) return 'approve';
                if (['Approved', 'Mark to Release'].includes(st)) return 'release';
                return null;
            },
            // What this officer may do here, as the server says (detail.permitted).
            // The staff site showed Reject to roles that could not reject, and
            // Department Staff every Tulong decision (QA run 2026-10-05). Unknown
            // means shown: the server still decides.
            may(action) {
                return !this.selected?.permitted || !!this.selected.permitted[action];
            },
            // Nothing uploaded means nothing to accept, unless the document is
            // optional ('(if applicable)'), or is a step the office does itself
            // (an interview, a case study, 'None'): the server's rule.
            mayAccept(req) {
                return !!req.file || /if applicable|if available|if any|required if|\bonly\s*$|interview|case study|case assessment|details of the record being requested|thumbmark|to be taken at|^\s*none\b/i.test(req.name || '');
            },

            async verifyRequirement(req) {
                if (req._busy) return;
                req._busy = true;
                try {
                    await $api.post(this.apiBase(this.selected.ref) + '/requirements/' + encodeURIComponent(req.key) + '/approve');
                    await this.refreshSelected();
                    this.$dispatch('toast', { message: req.name + ' verified.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not verify ' + req.name + '.'), variant: 'error' });
                } finally { req._busy = false; }
            },
            // Requirement-level decision flow covering the guide's other
            // three outcomes — Needs Additional Documents, Needs Manual
            // Verification, and Reject — each requiring a reason before it
            // can be confirmed, same as the existing whole-request reject.
            startRequirementDecision(req, outcome) {
                req._decidingOutcome = outcome;
                req._reason = '';
                req._remarks = '';
            },
            // Flag for Replacement or Reject, with the reason (and remarks)
            // the resident will read. Flagging any one requirement moves the
            // whole request to Under Review on the server; the resident then
            // replaces the file from their own device, and it shows here as
            // Resubmitted the next time this request is opened.
            async confirmRequirementDecision(req) {
                if (!req._reason || req._busy) return;
                const outcome = req._decidingOutcome;
                const action = outcome === 'Rejected' ? 'reject' : 'flag';
                const remarks = (req._remarks || '').trim() || req._reason;
                req._busy = true;
                try {
                    await $api.post(this.apiBase(this.selected.ref) + '/requirements/' + encodeURIComponent(req.key) + '/' + action, { reason: req._reason, remarks });
                    await this.refreshSelected();
                    this.$dispatch('toast', { message: req.name + ' marked ' + outcome + '.', variant: outcome === 'Rejected' ? 'error' : 'warning' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not save the decision on ' + req.name + '.'), variant: 'error' });
                } finally { req._busy = false; }
            },
            statusOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            terminalStatuses: ['Rejected', 'Released', 'Completed', 'Archived', 'Cancelled'],
            // What clicking the advance button actually does at each stage:
            // Submitted -> Under Verification -> Approved -> Mark to Release
            // -> Released, with a correction branch off Under Verification
            // (Under Review, where the per-requirement panel is what the
            // admin actually works from — once every flagged item is
            // resubmitted and approved there, this same button approves the
            // whole request directly, no separate resubmitted hold state).
            stageActionLabels: {
                'Submitted': 'Begin Verification',
                'Under Verification': 'Approve Request',
                'Under Review': 'Approve Request',
                'Approved': 'Mark to Release',
                'Mark to Release': 'Release to Citizen',
            },
            get nextStageLabel() {
                if (!this.selected) return '';
                return this.stageActionLabels[this.selected.status] ?? 'Approve & Continue';
            },
            // A real photo from the device camera or files. It is held here
            // and sent with the release itself (POST .../release, proof_file):
            // the server refuses a release without proof.
            // Take Photo opens the webcam on a computer (window.takePhoto); a
            // phone, or a computer with no camera, uses the file input, whose
            // capture hint a phone honours.
            async captureReleaseEvidence(source) {
                if (source === 'camera') {
                    const shot = await window.takePhoto();
                    if (shot === null) return;
                    if (shot) { this.useReleaseEvidence(shot); return; }
                }
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                if (source === 'camera') input.setAttribute('capture', 'environment');
                input.onchange = () => this.useReleaseEvidence(input.files && input.files[0]);
                input.click();
            },
            useReleaseEvidence(file) {
                if (!file || !this.selected) return;
                if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                this.selected._proofFile = file;
                this.selected._proofPreview = URL.createObjectURL(file);
                this.selected.release_evidence = this.selected._proofPreview;
                this.selected.release_evidence_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                this.$nextTick(() => window.renderIcons?.());
            },
            clearReleaseEvidence() {
                if (this.selected._proofPreview) URL.revokeObjectURL(this.selected._proofPreview);
                this.selected._proofFile = null;
                this.selected._proofPreview = null;
                this.selected.release_evidence = null;
                this.selected.release_evidence_at = null;
            },
            // Generic advance-to-next-stage action. From Under Review this
            // approves the request directly — once the admin has approved
            // every resubmitted requirement at the per-requirement level
            // (see confirmRequirementDecision), there is no further
            // whole-request gate to pass.
            // The one advance button, per stage, each a real endpoint:
            //   Under Verification / Under Review / Resubmitted -> approve
            //   Approved        -> mark-to-release, then issue the document
            //                      (control number + verification token)
            //   Mark to Release -> release, carrying the proof-of-release photo
            async approve() {
                if (this.processing) return;
                const r = this.selected;
                this.processing = true;
                try {
                    let res;
                    if (['Under Verification', 'Under Review', 'Resubmitted'].includes(r.status)) {
                        res = await $api.post(this.apiBase(r.ref) + '/approve');
                    } else if (r.status === 'Approved' && r.idNumberSpec) {
                        // An ID: issued first, so its number is checked before the
                        // request moves on (MSWDO forms, 2026-10-26).
                        const number = String(r._idNumber || '').trim();
                        if (!number) {
                            this.$dispatch('toast', { message: 'Write the ' + r.idNumberSpec.label + ' first.', variant: 'error' });
                            return;
                        }
                        const issued = await $api.post('/admin/requests/' + encodeURIComponent(r.ref) + '/issue', { id_number: number });
                        r.issued = issued.data;
                        res = await $api.post(this.apiBase(r.ref) + '/mark-to-release');
                        res = await $api.get(this.apiBase(r.ref)).catch(() => res);
                    } else if (r.status === 'Approved') {
                        res = await $api.post(this.apiBase(r.ref) + '/mark-to-release');
                        try {
                            const issued = await $api.post('/admin/requests/' + encodeURIComponent(r.ref) + '/issue');
                            r.issued = issued.data;
                            // The detail again, now with the issued document (its QR goes on the paper).
                            res = await $api.get(this.apiBase(r.ref)).catch(() => res);
                        } catch (err) {
                            this.$dispatch('toast', { message: 'Marked to release, but the document could not be issued: ' + this.apiMessage(err, 'unknown error') + '.', variant: 'warning' });
                        }
                    } else if (r.status === 'Mark to Release') {
                        const form = new FormData();
                        if (r._proofFile) form.append('proof_file', r._proofFile);
                        form.append('proof_note', 'Released to ' + r.citizen + ' at the office. Recorded by ' + (this.$store.session.account?.name || 'staff') + '.');
                        res = await $api.post(this.apiBase(r.ref) + '/release', form);
                    } else {
                        return;
                    }
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: r.ref + ' moved to: ' + r.status + '.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not update ' + r.ref + '.'), variant: 'error' });
                } finally {
                    this.processing = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('dokyu')" x-cloak>
            <x-admin.access-restricted module="Dokyu (Document Requests)" icon="file-text" />
        </div>

        <div x-show="$store.session.can('dokyu')" x-cloak
             x-on:requests-imported.window="if ($event.detail?.type === 'dokyu') loadRequests()"
             x-effect="!requestsLoading && !requestsFailed && !mineOnly && !overdueOnly && ($store.queueCounts.dokyu = requests.filter((r) => actionStatuses.includes(r.status)).length)"
             x-on:requests-show-import.window="if ($event.detail?.type === 'dokyu') { importFilter = $event.detail.ref; tab = 'all'; openOnly = false; search = ''; loadRequests(); }">

        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mb-4 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing Dokyu requests for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Profile
            </button>
        </div>

        <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto mb-4">
            <button
                type="button" @click="view = 'requests'"
                class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                :class="view === 'requests' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
            ><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Document Requests</button>
            <a
                href="{{ route('admin.papeles') }}"
                class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap text-ink-muted hover:text-slate-700"
            ><i data-lucide="folder-open" class="w-3.5 h-3.5"></i> Citizen Document Storage <i data-lucide="arrow-up-right" class="w-3 h-3"></i></a>
        </div>

        <div x-show="view === 'requests'">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card layout="compact" @click="tab = 'all'" class="cursor-pointer" label="Total Requests" value="{{ count($requests) }}" valueBind="requests.length" icon="file-text" color="brand" sublabel="All statuses" :delay="0" />
            <x-ui.stat-card layout="compact" @click="tab = 'action'" class="cursor-pointer" label="Needs Action" value="{{ collect($requests)->whereIn('status', $actionStatuses)->count() }}" valueBind="requests.filter(r => actionStatuses.includes(r.status)).length" icon="inbox" color="orange" sublabel="Review or verification" :delay="40" />
            <x-ui.stat-card layout="compact" @click="tab = 'release'" class="cursor-pointer" label="Mark to Release" value="{{ collect($requests)->where('status', 'Mark to Release')->count() }}" valueBind="requests.filter(r => r.status === 'Mark to Release').length" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card layout="compact" :href="route('admin.analytics')" label="Avg. Processing Time" value="—" valueBind="avgProcessingLabel" icon="timer" color="purple" sublabel="No request released yet" sublabelBind="avgProcessingNote" :delay="120" />
        </div>

        <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search reference or citizen" type="text" x-model="search" placeholder="Search reference or citizen..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                </div>
                <select aria-label="Category filter" x-model="categoryFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0 border-0 surface-pressed-sm">
                    <option value="">All Categories</option>
                    <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <button
                    type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-2xl px-3 py-2 border transition-colors shrink-0"
                    :class="showAdvancedFilters || officeFilter || levelFilter || assignedFilter || barangayFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-slate-50 border-slate-200 text-ink-muted hover:text-slate-700'"
                >
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced
                </button>
                <button
                    type="button" @click="loadRequests()"
                    title="Reload the queue from the server."
                    class="inline-flex items-center gap-1.5 text-xs font-medium rounded-2xl px-3 py-2 border border-slate-200 bg-slate-50 text-ink-muted hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 transition-colors shrink-0"
                >
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                </button>
                @include('admin.partials.modal-request-import', ['importType' => 'dokyu'])
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto">
                    @foreach($tabs as $key => $label)
                        <button
                            type="button" @click="tab = '{{ $key }}'"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <x-admin.scope.queue-chips />
                <p class="text-[11px] text-ink-muted shrink-0" x-text="filteredRequests.length + ' of ' + requests.length + ' requests'"></p>
                <p x-show="importFilter" x-cloak class="w-full text-[11px] text-brand-700 bg-brand-50 rounded-lg px-3 py-1.5 flex flex-wrap items-center gap-x-2">
                    <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                    <span x-text="'Showing the ' + filteredRequests.length.toLocaleString() + ' records imported from ' + importFilter + '.'"></span>
                    <button type="button" class="font-medium underline" @click="importFilter = ''">Show all</button>
                </p>
                <p x-show="openOnly || barangayFilter" x-cloak class="w-full text-[11px] text-brand-700 bg-brand-50 rounded-lg px-3 py-1.5 flex flex-wrap items-center gap-x-2">
                    <span x-text="'Showing ' + (openOnly ? 'open requests' : 'requests') + (barangayFilter ? ' in Brgy. ' + barangayFilter : '') + '.'"></span>
                    <button type="button" class="font-medium underline" @click="openOnly = false; barangayFilter = ''">Show all</button>
                </p>
            </div>

            <div
                x-show="showAdvancedFilters" x-cloak
                x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-wrap items-center gap-2 pt-3 border-t border-brand-600/10"
            >
                <select aria-label="Office filter" x-model="officeFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    <option value="">All Offices</option>
                    <template x-for="o in offices" :key="o"><option :value="o" x-text="o"></option></template>
                </select>
                <select aria-label="Level filter" x-model="levelFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    <option value="">All Levels</option>
                    <option value="Barangay">Barangay</option>
                    <option value="Municipal">Municipal</option>
                    <option value="Multi-Office">Multi-Office</option>
                </select>
                <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <span x-show="!isMunicipalScope" x-cloak class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                    <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                </span>
                <select aria-label="Assigned filter" x-model="assignedFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    <option value="">Anyone / Unassigned</option>
                    <option value="unassigned">Unassigned only</option>
                    <option x-show="$store.session.account" :value="$store.session.account?.name || ''">Assigned to me</option>
                </select>
                <button type="button" @click="clearAdvancedFilters()" class="text-xs text-ink-muted hover:text-slate-600 transition-colors" x-show="officeFilter || levelFilter || assignedFilter || barangayFilter">Clear</button>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="bg-well/70">
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Document Type</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden 2xl:table-cell">Office</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden 2xl:table-cell">Barangay</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden 2xl:table-cell">Assigned</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden xl:table-cell">Submitted</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="r in filteredRequests" :key="r.ref">
                    <tr class="hover:bg-white/60 transition-colors group cursor-pointer" @click="openRequest(r)"
        tabindex="0"
        @keydown.enter.prevent="openRequest(r)"
        @keydown.space.prevent="openRequest(r)">
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted hidden xl:table-cell" x-text="r.ref" @click.stop="openRequest(r)"></td>
                        <td class="px-4 py-3" @click.stop="openRequest(r)">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img x-show="r.photo" x-cloak :src="r.photo" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" :alt="r.citizen" x-on:error="r.photo = null">
                                <div x-show="!r.photo" class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(r.citizen || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <div class="min-w-0">
                                    <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="r.citizen"></p>
                                    <p x-show="r.imported_from" x-cloak class="text-[10px] font-semibold text-brand-700" :title="'Imported from ' + r.imported_from + (r.resident_ref ? '; matched to resident ' + r.resident_ref : '; not matched to a resident')"><span x-text="'Imported · ' + (r.resident_ref || 'no resident record')"></span></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700" x-text="r.type"></p>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-text="r.category"></p>
                        </td>
                        <td class="px-4 py-3 text-ink-muted hidden 2xl:table-cell">
                            <span class="text-[11px] font-medium text-ink-muted bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap" x-text="r.office"></span>
                            <span
                                class="inline-block ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap"
                                :class="{ 'bg-teal-50 text-teal-700': r.level === 'Barangay', 'bg-brand-50 text-brand-700': r.level === 'Municipal', 'bg-purple-50 text-purple-700': r.level === 'Multi-Office' }"
                                x-text="r.level"
                            ></span>
                        </td>
                        <td class="px-4 py-3 text-ink-muted hidden 2xl:table-cell"><span x-text="'Brgy. ' + r.barangay"></span></td>
                        <td class="px-4 py-3 text-ink-muted hidden 2xl:table-cell">
                            <span x-show="r.assigned" x-text="r.assigned"></span>
                            <span x-show="!r.assigned" class="text-ink-faint italic">Unassigned</span>
                        </td>
                        <td class="px-4 py-3 text-ink-muted hidden xl:table-cell" x-text="r.submitted"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[r.status] || statusStyles['Draft'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[r.status] || statusStyles['Draft'])[1]"></span>
                                <span x-text="r.status"></span>
                            </span>
                            <x-admin.scope.queue-due :assignee="true" />
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop>
                            <button @click="openRequest(r)" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Process</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div x-show="requestsLoading" class="space-y-2 mt-3"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
        <div x-show="requestsFailed" x-cloak class="mt-3"><x-ui.error-state messageExpr="'Could not load document requests.'" retryExpr="loadRequests()" :retryable="true" /></div>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3" x-show="!requestsLoading && !requestsFailed && filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600" x-text="requests.length || mineOnly || overdueOnly ? 'No requests match these filters.' : 'No document requests yet.'"></p>
        </div>

        <x-ui.modal title="Process Document Request" maxWidth="4xl">
            <template x-if="selected">
                {{-- Three isolated regions so a long submission never buries the
                     stepper or the advice off-screen: the stepper (left) and
                     System Advice (right) stay pinned in place while only the
                     middle column — fields, the submitted form, the validation
                     guide, and the documents list — scrolls. --}}
                <div class="flex flex-col sm:flex-row gap-5 sm:h-[66vh]">

                    {{-- LEFT — Stepper, isolated (never scrolls). --}}
                    <div class="sm:w-40 shrink-0">
                        <p class="text-xs text-ink-muted mb-3 font-mono" x-text="selected.ref"></p>
                        <div class="flex flex-col">
                            @foreach(['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'] as $i => $step)
                                <div class="flex items-start gap-3">
                                    <div class="flex flex-col items-center shrink-0">
                                        <span
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold transition-all duration-[600ms] ease-standard shrink-0"
                                            :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-500 text-white shadow-sm' : (stepIndex(selected.status) === {{ $i }} ? 'clay-blue shadow-card ring-4 ring-brand-100' : 'bg-slate-100 text-ink-muted')"
                                        >
                                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="stepIndex(selected.status) > {{ $i }}" x-cloak></i>
                                            <span x-show="stepIndex(selected.status) <= {{ $i }}">{{ $i + 1 }}</span>
                                        </span>
                                        @if(!$loop->last)
                                            <div class="w-0.5 flex-1 my-1 rounded-full transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-100'" style="min-height: 1.25rem"></div>
                                        @endif
                                    </div>
                                    <span class="text-[11px] leading-tight pt-1.5 pb-2 transition-colors duration-[600ms] ease-standard" :class="stepIndex(selected.status) === {{ $i }} ? 'text-ink font-semibold' : 'text-ink-muted'">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- MIDDLE — everything about this specific request; the
                         only column that scrolls. --}}
                    <div class="flex-1 min-w-0 sm:overflow-y-auto sm:min-h-0 scrollbar-thin sm:pr-1">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-brand-600/[.08] bg-slate-50/60">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <img x-show="selected.photo" x-cloak :src="selected.photo" :alt="'Photo of ' + selected.citizen" class="w-11 h-11 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" x-on:error="selected.photo = null">
                                <span x-show="!selected.photo" class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Citizen</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Document</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.type"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Processing Office</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.office"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Barangay</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="'Brgy. ' + selected.barangay"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Category</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.category"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span
                                    class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0"
                                    :class="{ 'bg-teal-50 text-teal-600 border-teal-100': selected.level === 'Barangay', 'bg-brand-50 text-brand-600 border-brand-100': selected.level === 'Municipal', 'bg-purple-50 text-purple-600 border-purple-100': selected.level === 'Multi-Office' }"
                                ><i data-lucide="route" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Processing Level</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.level"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Assigned Personnel</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.assigned || 'Unassigned'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-ink-muted uppercase tracking-wide">Submitted</p>
                                    <p class="text-sm font-medium text-ink truncate" x-text="selected.submitted"></p>
                                    <x-admin.scope.queue-due row="selected" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-admin.scope.request-assign />
                    <x-admin.scope.request-workflow />

                    {{-- Payment method + Official Payment Receipt — same source of
                         truth and same visual design as the dedicated Payments
                         page and the Esperanza Mobile app, so a processor never
                         has to leave this modal to see how (and whether) this
                         request's fee was settled. Shown for every Dokyu request
                         once a payment method has actually been chosen (not for
                         a bare "Under Review" record where nothing's been
                         picked yet); the compact summary below always shows the
                         chosen method/amount/status, and the full receipt further
                         down (see x-admin.payment-receipt) only once a receipt
                         has actually been uploaded/generated. --}}
                    <div class="mt-4" x-show="selectedPayment?.method">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-ink uppercase tracking-wide">Payment</p>
                            <button
                                type="button"
                                @click="window.location.href = '/admin/payments?ref=' + encodeURIComponent(selected.ref)"
                                class="text-[11px] font-medium text-brand-600 hover:underline whitespace-nowrap"
                            >View in Payments</button>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 mb-2 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-[#0072CE]/10': selectedPayment?.method === 'GCash',
                                        'bg-[#00A85A]/10': selectedPayment?.method === 'PayMaya',
                                        'bg-brand-50 text-brand-600': !['GCash','PayMaya'].includes(selectedPayment?.method),
                                    }"
                                >
                                    <img x-show="selectedPayment?.method === 'GCash'" src="{{ asset('images/payment-methods/gcash-logo.svg') }}" alt="GCash" class="h-[18px] w-5 object-contain">
                                    <img x-show="selectedPayment?.method === 'PayMaya'" src="{{ asset('images/payment-methods/maya-logo.webp') }}" alt="Maya" class="h-3.5 w-[34px] object-contain">
                                    <i x-show="!['GCash','PayMaya'].includes(selectedPayment?.method)" data-lucide="store" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink" x-text="(selectedPayment?.method === 'Payment in Center' ? 'Onsite — Municipal Office' : selectedPayment?.method) + ' · ' + selectedPayment?.amount"></p>
                                    <p class="text-[11px] text-ink-muted" x-text="selectedPayment?.receipt ? 'Receipt on file' : 'Awaiting receipt'"></p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap shrink-0"
                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': selectedPayment?.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': selectedPayment?.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': selectedPayment?.status === 'Submitted' || selectedPayment?.status === 'Under Review' }"
                            ><span x-text="selectedPayment?.status === 'Approved' ? 'Verified' : selectedPayment?.status"></span></span>
                        </div>
                        <x-admin.payment-receipt recordExpr="selectedPayment" />
                    </div>

                    <x-admin.scope.issued-documents can="may('release')" issuable="selected.status === 'Mark to Release'" />

                    <x-admin.request-form-review />

                    <div class="mt-4" x-show="(selected.requirements || []).length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-ink uppercase tracking-wide">Submitted Documents</p>
                            <p class="text-[11px] text-ink-muted" x-text="(selected.requirements || []).filter(r => r.status === 'Approved').length + ' / ' + (selected.requirements || []).length + ' verified'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="req in (selected.requirements || [])" :key="req.key">
                                <div class="rounded-xl border p-3" :class="{ 'border-emerald-200 bg-emerald-50/30': req.status === 'Approved', 'border-rose-200 bg-rose-50/30': req.status === 'Rejected', 'border-amber-200 bg-amber-50/30': req.status === 'Flagged for Replacement', 'border-sky-200 bg-sky-50/30': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' }">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                :class="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500') : 'bg-slate-100 text-ink-faint'"
                                            >
                                                <i :data-lucide="req.file ? (req.file.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image') : 'file-x'" class="w-4 h-4"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <span
                                                        class="text-[9px] font-bold px-1 py-0.5 rounded shrink-0"
                                                        :class="{ 1: 'bg-slate-100 text-ink-muted', 2: 'bg-blue-50 text-blue-600', 3: 'bg-emerald-50 text-emerald-600', 4: 'bg-purple-50 text-purple-600' }[evidenceTier(req.name)]"
                                                        :title="'Evidence Strength: Level ' + evidenceTier(req.name)"
                                                    >L<span x-text="evidenceTier(req.name)"></span></span>
                                                </div>
                                                <p class="text-[11px] text-ink-muted truncate" x-text="req.file || 'Not yet submitted'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            {{-- The whole point of a review queue is looking at what was
                                                 actually submitted before deciding — these buttons used to
                                                 go straight from filename text to Approve/Reject with no way
                                                 to actually open the file in between. --}}
                                            <button
                                                type="button"
                                                x-show="req.file"
                                                @click="previewDoc = req; $nextTick(() => window.renderIcons?.())"
                                                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-ink-muted hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-colors"
                                                title="View submitted document"
                                            ><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset whitespace-nowrap"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': (req.status === 'Submitted' && req.file) || req.status === 'Flagged for Replacement', 'bg-slate-100 text-slate-600 ring-slate-200': req.status === 'Submitted' && !req.file, 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : (req.status === 'Submitted' && !req.file ? 'Not submitted' : req.status)"></span></span>
                                        </div>
                                    </div>

                                    <div x-show="['Rejected', 'Flagged for Replacement'].includes(req.status) || (req.status === 'Resubmitted' && (req.versions || []).length)" class="mt-2 pt-2 border-t text-[11px]" :class="req.status === 'Rejected' ? 'border-rose-100/70' : req.status === 'Flagged for Replacement' ? 'border-amber-100/70' : 'border-sky-100/70'">
                                        <p class="font-semibold" x-show="req.reject_reason" :class="req.status === 'Rejected' ? 'text-rose-700' : 'text-amber-700'" x-text="req.reject_reason"></p>
                                        <p class="mt-0.5" :class="req.status === 'Rejected' ? 'text-rose-600' : 'text-amber-600'" x-show="req.remarks" x-text="req.remarks"></p>
                                        <p class="font-medium text-sky-700 flex items-center gap-1" x-show="req.status === 'Resubmitted'"><i data-lucide="refresh-ccw" class="w-3 h-3"></i> Citizen replaced this file — compare with the flagged version below.</p>
                                        <button type="button" x-show="(req.versions || []).length" @click="req._showVersions = !req._showVersions" class="mt-1.5 text-ink-muted hover:text-slate-600 transition-colors">
                                            <span x-text="(req._showVersions ? 'Hide' : 'Show') + ' version history (' + req.versions.length + ')'"></span>
                                        </button>
                                        <div x-show="req._showVersions" x-cloak class="mt-1.5 space-y-1 pl-2 border-l-2 border-brand-600/10">
                                            <template x-for="(v, vi) in (req.versions || [])" :key="vi">
                                                <p class="text-ink-muted">Version <span x-text="vi + 1"></span> — <span x-text="v.status"></span> · <span x-text="v.date"></span><span x-show="v.reject_reason"> · <span x-text="v.reject_reason"></span></span></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Three decision outcomes at the requirement level: Approve /
                                         Flag for Replacement / Reject. No separate "needs verification"
                                         action — reviewing a requirement here already IS the
                                         verification step. --}}
                                    <div class="flex items-center gap-2 mt-2 flex-wrap" x-show="(req.status === 'Submitted' || req.status === 'Resubmitted') && !req._decidingOutcome && may('review')">
                                        <button type="button" x-show="mayAccept(req)" @click="verifyRequirement(req)" class="text-[11px] font-medium text-emerald-600 hover:underline">Approve</button>
                                        <span x-show="!mayAccept(req)" class="text-[11px] text-ink-muted">Nothing uploaded yet</span>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Flagged for Replacement')" class="text-[11px] font-medium text-amber-600 hover:underline">Flag for Replacement</button>
                                        <span class="text-slate-200">·</span>
                                        <button type="button" @click="startRequirementDecision(req, 'Rejected')" class="text-[11px] font-medium text-rose-600 hover:underline">Reject</button>
                                    </div>

                                    <div x-show="req._decidingOutcome" x-cloak class="mt-2.5 pt-2.5 border-t border-brand-600/10 space-y-2">
                                        <p class="text-[11px] font-semibold text-ink-muted" x-text="(decisionOutcomes[req._decidingOutcome]?.label || req._decidingOutcome) + ' — ' + req.name"></p>
                                        <select aria-label="Reason" x-model="req._reason" class="w-full text-xs rounded-xl py-1.5 px-2.5 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                                            <option value="">Select a reason...</option>
                                            <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                                            <template x-for="reason in rejectReasons" :key="reason"><option :value="reason" x-text="reason"></option></template>
                                        </select>
                                        <textarea aria-label="Remarks for the citizen (optional)" x-model="req._remarks" rows="2" placeholder="Remarks for the citizen (optional)..." class="w-full text-xs rounded-xl py-1.5 px-2.5 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none border-0 surface-pressed-sm"></textarea>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button" x-bind:disabled="!req._reason" @click="confirmRequirementDecision(req)"
                                                class="text-[11px] font-medium text-white disabled:opacity-40 disabled:pointer-events-none rounded-xl px-2.5 py-1.5 transition-colors"
                                                :class="req._decidingOutcome === 'Rejected' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-500 hover:bg-amber-600'"
                                            >Confirm</button>
                                            <button type="button" @click="req._decidingOutcome = null" class="text-[11px] font-medium text-ink-muted hover:text-slate-600">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border px-3.5 py-3 mt-3 flex items-start gap-2.5"
                        :class="selected.status === 'Rejected' ? 'bg-rose-50 border-rose-100' : selected.status === 'Under Verification' ? 'bg-indigo-50 border-indigo-100' : 'bg-amber-50 border-amber-100'"
                        x-show="['Rejected', 'Under Review', 'Under Verification'].includes(selected.status) && selected.remarks"
                    >
                        <i :data-lucide="selected.status === 'Rejected' ? 'circle-x' : selected.status === 'Under Verification' ? 'search-check' : 'file-plus-2'" class="w-4 h-4 shrink-0 mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-500' : selected.status === 'Under Verification' ? 'text-indigo-500' : 'text-amber-500'"></i>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold" :class="selected.status === 'Rejected' ? 'text-rose-700' : selected.status === 'Under Verification' ? 'text-indigo-700' : 'text-amber-700'" x-text="decisionOutcomes[selected.status]?.label || selected.status"></p>
                            <p class="text-xs mt-0.5" :class="selected.status === 'Rejected' ? 'text-rose-600' : selected.status === 'Under Verification' ? 'text-indigo-600' : 'text-amber-600'" x-text="selected.remarks"></p>
                        </div>
                    </div>

                    {{-- Status history: every transition the server recorded, newest first. --}}
                    <div class="rounded-xl border border-slate-200 p-4 mt-3" x-show="(selected.history || []).length" x-cloak>
                        <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5 mb-2"><i data-lucide="history" class="w-3.5 h-3.5"></i> Status History</p>
                        <ul class="space-y-2">
                            <template x-for="(h, hi) in (selected.history || []).slice().reverse()" :key="hi">
                                <li class="text-xs border-l-2 border-slate-200 pl-2.5">
                                    <p class="font-medium text-ink" x-text="h.stage + (h.note ? ' — ' + h.note : '')"></p>
                                    <p class="text-[11px] text-ink-muted" x-text="h.date + (h.by ? ' · ' + h.by : '')"></p>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="mt-3" x-show="canReject">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Decision Remarks <span class="text-ink-muted font-normal">(required for Reject — this is what the citizen will see)</span></label>
                        <p class="text-[11px] text-ink-muted mb-1.5">This is the final message the citizen will see. If you've already flagged specific requirements below, pull those reasons in instead of retyping them.</p>
                        <div class="flex items-center gap-2 mb-1.5">
                            <select
                                @change="insertStandardReason($event.target.value); $event.target.value = ''"
                                class="flex-1 min-w-0 text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm"
                            >
                                <option value="">Insert a standard reason…</option>
                                <template x-for="reason in standardReasons" :key="reason.label"><option :value="reason.label" x-text="reason.label"></option></template>
                            </select>
                            <button
                                type="button" x-show="flaggedRequirements.length" x-cloak
                                @click="useRequirementReasons()"
                                class="shrink-0 text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 hover:border-brand-300 rounded-2xl px-3 py-2 transition-colors whitespace-nowrap"
                            >Use requirement reasons</button>
                        </div>
                        <textarea aria-label="Reject remarks"
                            x-model="rejectRemarks" rows="2" placeholder="e.g. Missing Fire Safety Inspection Certificate. Please resubmit with this requirement attached."
                            class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-3.5 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                        ></textarea>
                    </div>
                    </div>

                    {{-- RIGHT — System Advice, isolated (never scrolls). Mirrors
                         the Esperanza Mobile app's own VerificationStatusPanel
                         pattern: icon + title + plain-language explanation,
                         so the admin always has a read on what to do next
                         regardless of how far they've scrolled the middle
                         column. --}}
                    <div class="sm:w-72 shrink-0">
                        <div
                            class="rounded-2xl border p-4"
                            :class="{
                                'bg-emerald-50/60 border-emerald-200': processAdvice.tone === 'emerald',
                                'bg-rose-50/60 border-rose-200': processAdvice.tone === 'rose',
                                'bg-amber-50/60 border-amber-200': processAdvice.tone === 'amber',
                                'bg-indigo-50/60 border-indigo-200': processAdvice.tone === 'indigo',
                                'bg-brand-50/60 border-brand-200': processAdvice.tone === 'brand',
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': processAdvice.tone === 'emerald',
                                        'bg-rose-100 text-rose-700': processAdvice.tone === 'rose',
                                        'bg-amber-100 text-amber-700': processAdvice.tone === 'amber',
                                        'bg-indigo-100 text-indigo-700': processAdvice.tone === 'indigo',
                                        'bg-brand-100 text-brand-700': processAdvice.tone === 'brand',
                                    }"
                                ><i :data-lucide="processAdvice.icon" class="w-4.5 h-4.5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide"
                                        :class="{
                                            'text-emerald-700': processAdvice.tone === 'emerald',
                                            'text-rose-700': processAdvice.tone === 'rose',
                                            'text-amber-700': processAdvice.tone === 'amber',
                                            'text-indigo-700': processAdvice.tone === 'indigo',
                                            'text-brand-700': processAdvice.tone === 'brand',
                                        }"
                                    >System Advice — <span x-text="processAdvice.title"></span></p>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed" x-text="processAdvice.message"></p>
                                </div>
                            </div>
                        </div>

                        {{-- The number written on a Senior Citizen, PWD or Solo Parent ID,
                             recorded on the resident when it is issued (MSWDO forms,
                             2026-10-26); and the IDs the resident already holds. --}}
                        <div class="rounded-2xl border border-brand-200 bg-brand-50/30 p-4 mt-3" x-show="selected.idNumberSpec && selected.status === 'Approved'" x-cloak>
                            <label for="id-number" class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5">
                                <i data-lucide="id-card" class="w-3.5 h-3.5"></i> <span x-text="selected.idNumberSpec?.label"></span>
                            </label>
                            <input id="id-number" type="text" x-model="selected._idNumber" maxlength="40" :placeholder="selected.idNumberSpec?.pattern ? 'e.g. 05-4107-001-0000123' : 'The number on the card'"
                                class="mt-2 w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm font-mono text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <p class="text-[11px] text-ink-muted mt-1.5">Written on the card and kept on the resident's record and ID Wallet<span x-text="selected.idNumberSpec?.validity_months ? '; good for ' + selected.idNumberSpec.validity_months + ' months' : '; it does not expire'"></span>.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="(selected.sectorIds || []).length" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5"><i data-lucide="wallet-cards" class="w-3.5 h-3.5"></i> IDs on record</p>
                            <ul class="mt-2 space-y-1.5">
                                <template x-for="sid in (selected.sectorIds || [])" :key="sid.id_number">
                                    <li class="flex items-center justify-between gap-2 text-xs">
                                        <span class="text-ink"><span class="font-semibold" x-text="sid.name"></span> <span class="font-mono text-ink-muted" x-text="sid.id_number"></span></span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="sid.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="sid.status === 'active' ? (sid.valid_until ? 'Until ' + sid.valid_until : 'Active') : (sid.status === 'expired' ? 'Expired ' + sid.valid_until : 'Revoked')"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- Proof of Release — a photo showing the citizen actually
                             received the document, captured/attached by the admin
                             at handover. Only relevant once the request has reached
                             Mark to Release; stays visible afterward once captured
                             so Released/Completed requests still show their own
                             proof on reopen. The photo is sent with the release
                             itself (proof_file); the server refuses a release
                             without proof. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i> Proof of Release
                            </p>
                            <p class="text-[11px] text-ink-muted mt-1 leading-relaxed" x-show="!selected.release_evidence">Photo evidence that the citizen actually received this document — required before releasing.</p>

                            <template x-if="selected.release_evidence">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 aspect-[4/3]">
                                        <img alt="Proof of release" class="w-full h-full object-cover" x-data="{ src: '' }" x-effect="window.viewableImage(selected.release_evidence, selected.release_evidence_mime).then((b) => { src = b; }).catch(() => { src = ''; })" :src="src" x-show="src">
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[11px] text-emerald-600 font-medium flex items-start gap-1 min-w-0"><i data-lucide="circle-check" class="w-3 h-3 mt-0.5 shrink-0"></i><span class="min-w-0">Captured<span x-show="selected.release_evidence_at" class="block font-normal text-ink-muted break-words" x-text="selected.release_evidence_at"></span></span></span>
                                        <button type="button" @click="clearReleaseEvidence()" class="text-[11px] text-ink-muted hover:text-rose-500" x-show="selected.status === 'Mark to Release'">Retake</button>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-2 mt-2.5" x-show="!selected.release_evidence">
                                <button type="button" @click="captureReleaseEvidence('camera')" class="flex flex-col items-center justify-center gap-1 rounded-2xl border border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 py-3 text-ink-muted hover:text-brand-600 transition-colors">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Take Photo</span>
                                </button>
                                <button type="button" @click="captureReleaseEvidence('upload')" class="flex flex-col items-center justify-center gap-1 rounded-2xl border border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 py-3 text-ink-muted hover:text-brand-600 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span class="text-[11px] font-medium">Upload Photo</span>
                                </button>
                            </div>
                        </div>

                        {{-- Reference Form — the actual official form for this
                             service, so the admin can check the prepared
                             document against it before handover. Only shown
                             once the request reaches Mark to Release, same
                             visibility condition as Proof of Release above. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="selected.status === 'Mark to Release' || selected.release_evidence" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Reference Form
                                <span x-show="selected.formNo" class="normal-case font-normal text-ink-muted" x-text="'— ' + selected.formNo"></span>
                            </p>
                            <template x-if="selected.reference_pdf">
                                <div class="mt-2.5">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50" style="height: 220px;">
                                        <iframe :src="selected.reference_pdf" class="w-full h-full" title="Reference form preview"></iframe>
                                    </div>
                                    <a :href="selected.reference_pdf" target="_blank" rel="noopener" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium inline-flex items-center gap-1 mt-1.5">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> Open in new tab
                                    </a>
                                </div>
                            </template>
                            <p class="text-[11px] text-ink-muted mt-1 leading-relaxed" x-show="!selected.reference_pdf">No reference form on file for this service.</p>
                        </div>

                        {{-- Document — the citizen's own prepared/issued
                             certificate (not the blank Reference Form above),
                             so staff can preview, print, or download exactly
                             what will be handed over. Same visibility window
                             as Proof of Release / Reference Form. --}}
                        <div class="rounded-2xl border border-slate-200 p-4 mt-3" x-show="['Approved', 'Mark to Release', 'Released'].includes(selected.status)" x-cloak>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5">
                                <i data-lucide="file-check-2" class="w-3.5 h-3.5"></i> Document
                            </p>
                            <template x-if="documentReady">
                                <div class="mt-2.5">
                                    <p class="text-[11px] text-ink-muted leading-relaxed mb-2">Preview, print, or download the prepared document before handing it over.</p>
                                    <x-ui.button size="sm" variant="secondary" icon="eye" class="w-full justify-center" @click="if (editingDocument) cancelDocumentEdits(); docPreviewOpen = true">View Document</x-ui.button>
                                </div>
                            </template>
                            <p class="text-[11px] text-ink-muted mt-1 leading-relaxed" x-show="!documentReady">No prepared document on file for this request.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document viewer lightbox — opened from the "eye" button on any
                 submitted requirement above. Fixed to the viewport (not nested
                 inside the scrollable middle column) so it sits above the
                 whole Process modal regardless of scroll position. --}}
            <div
                data-modal-overlay x-show="previewDoc"
                x-cloak
                x-transition:enter="transition duration-[200ms] ease-emphasized"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-[150ms] ease-exit"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="position: fixed; inset: 0; z-index: 95;"
                class="bg-navy-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="previewDoc = null"
                @keydown.escape.window="previewDoc = null"
            >
                <div
                    class="w-full max-w-sm bg-white rounded-2xl shadow-float overflow-hidden"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-brand-600/10">
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                            :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500'"
                        ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-3.5 h-3.5"></i></span>
                        <h3 class="text-sm font-semibold text-ink truncate flex-1" x-text="previewDoc?.name"></h3>
                        <button type="button" @click="previewDoc = null" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-ink-muted hover:text-slate-600 transition-colors shrink-0" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        {{-- The file itself: an image shown inline, a download link, or
                             why it cannot be opened (the server links a file only once
                             the malware scan has cleared it). A PDF is not framed: the
                             server serves uploads as downloads, never inline, so a
                             frame only started a download and stayed blank. --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/2] overflow-hidden mb-3" x-show="previewDoc?.preview_asset">
                            <img :src="previewDoc?.preview_asset" :alt="previewDoc?.name" class="w-full h-full object-contain">
                        </div>
                        <a x-show="previewDoc?.file_url" :href="previewDoc?.file_url" target="_blank" rel="noopener" class="text-[11px] text-brand-600 hover:text-brand-700 font-medium inline-flex items-center gap-1 mb-3">
                            <i data-lucide="download" class="w-3 h-3"></i> Download the file
                        </a>
                        {{-- A PDF is read here: PDF.js draws its pages from the file's data, so the
                             file is never opened on the portal's origin and runs nothing
                             (Esperanza bugs QA, 2026-10-06: staff downloaded every file to read it). --}}
                        <div x-data="pdfPreview" x-effect="show(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))" x-show="!previewDoc?.preview_asset && previewKind(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))" x-cloak class="rounded-xl border border-slate-200 bg-slate-100 max-h-[60vh] overflow-y-auto p-2 mb-3">
                            <p x-show="loading" class="text-xs text-ink-muted text-center py-8">Opening the document…</p>
                            <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8">This file could not be shown here. Use Download the file.</p>
                            <img x-show="image" x-cloak :src="image" :alt="previewDoc?.name" class="block w-full h-auto rounded-lg bg-white">
                            <div x-ref="pages"></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 aspect-[3/4] flex flex-col items-center justify-center gap-2.5 mb-3" x-show="!previewDoc?.preview_asset && !previewKind(previewDoc?.file_url, (previewDoc?.file_mime || '') + ' ' + (previewDoc?.file || ''))">
                            <span
                                class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                :class="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'bg-rose-100 text-rose-500' : 'bg-blue-100 text-blue-500'"
                            ><i :data-lucide="previewDoc?.file?.toLowerCase().endsWith('.pdf') ? 'file-text' : 'image'" class="w-7 h-7"></i></span>
                            <p class="text-xs font-medium text-ink-muted px-4 text-center truncate max-w-full" x-text="previewDoc?.file"></p>
                            <p x-show="previewDoc?.file_url" class="text-[11px] leading-snug text-ink-muted px-5 text-center">This kind of file cannot be shown here. Download it to read it.</p>
                            <p x-show="previewDoc?.file_notice" class="text-[11px] leading-snug text-rose-600 px-5 text-center" x-text="previewDoc?.file_notice"></p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-ink-muted shrink-0">Requirement</dt>
                                <dd class="font-medium text-ink text-right truncate" x-text="previewDoc?.name"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-ink-muted shrink-0">Status</dt>
                                <dd class="font-medium text-ink" x-text="previewDoc?.status === 'Approved' ? 'Verified' : previewDoc?.status"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-3" x-show="previewDoc?.remarks">
                                <dt class="text-ink-muted shrink-0">Remarks</dt>
                                <dd class="font-medium text-right text-slate-600" x-text="previewDoc?.remarks"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <p class="text-xs text-ink-muted flex-1 flex items-center gap-1.5" x-show="selected && terminalStatuses.includes(selected.status)" x-cloak>
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>This request is finalized — no further status changes.
                </p>
                <template x-if="selected && !terminalStatuses.includes(selected.status)">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <p x-show="approveBlocked" class="mr-auto text-xs text-amber-700 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i><span x-text="approveBlocked"></span></p>
                        <template x-if="canReject">
                            <x-ui.button variant="danger" size="sm" x-bind:disabled="processing || !rejectRemarks.trim()" @click="reject()">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                    <span x-text="processing ? 'Rejecting…' : 'Reject'"></span>
                                </span>
                            </x-ui.button>
                        </template>
                        <x-ui.button size="sm" x-show="!primaryPermission || may(primaryPermission)" x-bind:disabled="processing || !!approveBlocked || (selected.status === 'Mark to Release' && !selected.release_evidence)" @click="approve()" x-bind:title="approveBlocked || (selected.status === 'Mark to Release' && !selected.release_evidence ? 'Capture or upload Proof of Release first' : null)">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="!processing"></i>
                                <span x-text="processing ? 'Saving…' : nextStageLabel"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </template>
            </x-slot:footer>
        </x-ui.modal>

        {{-- Document Preview: reuses the exact same letterhead components the
             citizen wizard uses for its own live pre-submission preview,
             bound here to the request's frozen formSnapshot so staff see
             precisely what the citizen will receive. Same #dp-print-area /
             dp-printing print isolation as the citizen portal (shared rules
             in app.css). --}}
        <div
            x-data="{ get open() { return docPreviewOpen }, set open(v) { if (!v && editingDocument) cancelDocumentEdits(); docPreviewOpen = v; if (!v) { editingDocument = false; documentEditBackup = null; backgroundEditBackup = null; templateKeyEditBackup = null; resetEditorUiState(); } else { zoomLevel = 100; $nextTick(() => recomputeZoomSizer()); } } }"
            @keydown.window="handleZoomShortcut($event); handleUndoRedoShortcut($event)"
        >
            <x-ui.modal title="Prepared Document" maxWidth="3xl" :fullscreen="true" :start-compact="true">
                <template x-if="selected && documentReady">
                    <div>
                        <div class="flex items-center justify-between mb-3 dp-no-print" x-show="!editingDocument">
                            <p class="text-[11px] text-ink-muted leading-relaxed">Review the prepared document below, or edit its wording and formatting before printing or downloading.</p>
                            <x-ui.button size="sm" variant="secondary" icon="pencil" x-show="mayEditDocument" @click="startEditingDocument()">Edit Document</x-ui.button>
                        </div>

                        <x-citizen.document-editor-zoom-controls />
                        <x-citizen.document-editor-toolbar />
                        <x-citizen.document-editor-background-panel />
                        <x-citizen.document-editor-spacing-panel />
                        <x-citizen.document-editor-header-seal-panel />

                        {{-- Template sidebar sits beside the paper only —
                             the toolbar/floating panels above stay full-
                             width, matching the requested layout (sidebar
                             + paper in the middle, toolbar spanning the
                             top, inspectors floating independently). --}}
                        <div class="flex gap-4 items-start">
                            <x-citizen.document-editor-template-sidebar />
                            <div class="flex-1 min-w-0">
                        <div id="dp-print-area">
                            <div class="flex items-center justify-between mb-4 text-[11px] text-ink-muted dp-no-print">
                                <span x-text="selected.ref"></span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-medium ring-1 ring-inset"
                                      :class="selected.status === 'Released' ? 'bg-cyan-50 text-cyan-700 ring-cyan-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                                      x-text="selected.status"></span>
                            </div>

                            {{-- Zoom is purely a view affordance — it wraps
                                 both rendering branches below without
                                 changing either. doc-zoom-viewport is the
                                 scrollable window; doc-zoom-sizer's explicit
                                 width/height (recomputeZoomSizer() in the
                                 x-data above) reserves exactly the scaled
                                 paper's footprint so the viewport actually
                                 has something to scroll; doc-zoom-scale
                                 carries the transform itself. All three are
                                 neutralized during print/PDF (see app.css)
                                 so the exported page is always true,
                                 unscaled A4. --}}
                            <div class="doc-zoom-viewport max-h-[60vh]" x-ref="zoomViewport">
                                <div class="doc-zoom-sizer" x-ref="zoomSizer">
                                    <div class="doc-zoom-scale" x-ref="zoomScale" :style="'transform: scale(' + (zoomLevel / 100) + ');'">
                                        {{-- Not yet customized — render the live
                                             template exactly as before
                                             (unchanged appearance/behavior). --}}
                                        <template x-if="!selected.customDocumentHtml">
                                            <div>
                                                <template x-if="resolvedDocument?.previewType === 'barangay'">
                                                    <x-citizen.barangay-certificate-preview
                                                        docKey="resolvedDocument.docKey"
                                                        bind="resolvedDocument.snapshot"
                                                        title="resolvedDocument.certTitle"
                                                        subtitle="resolvedDocument.certSubtitle"
                                                        barangay="selected.barangay"
                                                        seal="null"
                                                        punongBarangay="resolvedDocument.barangayOfficials?.punong_barangay"
                                                        barangaySecretary="resolvedDocument.barangayOfficials?.barangay_secretary"
                                                        background="documentBackground"
                                                        verify="selected.issued_documents"
                                                    />
                                                </template>
                                                <template x-if="resolvedDocument?.previewType === 'municipal_request'">
                                                    <x-citizen.municipal-request-preview
                                                        docKey="resolvedDocument.docKey"
                                                        bind="resolvedDocument.snapshot"
                                                        title="resolvedDocument.certTitle"
                                                        office="selected.office"
                                                        formNo="selected.formNo"
                                                        officiant="selected.officiant"
                                                        officiantTitle="selected.officiantTitle"
                                                        technician="selected.technician"
                                                        technicianTitle="selected.technicianTitle"
                                                        verifier="selected.verifier"
                                                        verifierTitle="selected.verifierTitle"
                                                        background="documentBackground"
                                                        verify="selected.issued_documents"
                                                    />
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Customized (or currently being
                                             edited) — the frozen/edited HTML
                                             from startEditingDocument()/
                                             saveDocumentEdits() above, with
                                             the watermark rendered as an
                                             independent sibling layer driven
                                             by documentBackground so Change
                                             Background never touches the
                                             editable text, and vice versa. --}}
                                        <template x-if="selected.customDocumentHtml">
                                            <div class="doc-paper border border-slate-200 shadow-sm relative overflow-hidden mx-auto flex flex-col p-6 sm:p-10" :style="paperStyle()" :class="documentBackground.decorative ? 'doc-paper--tpl-' + documentBackground.decorative : ''">
                                                <img class="doc-template-artwork" alt="" aria-hidden="true" x-show="documentBackground.decorative === 'labangtaytay'" x-cloak src="{{ asset('images/esperanza/templates/barangay-labangtaytay-artwork.svg') }}">
                                                <img class="doc-watermark" alt="" aria-hidden="true" x-show="documentBackground.src" x-cloak :src="documentBackground.src" :style="watermarkStyle()">
                                                <div
                                                    class="doc-content relative flex flex-col flex-1"
                                                    x-ref="docEditor"
                                                    :contenteditable="editingDocument ? 'true' : 'false'"
                                                    x-html="selected.customDocumentHtml"
                                                    @click="selectSection($event)"
                                                    @mouseover="hoverSection($event)"
                                                    @mouseleave="clearHoverSection()"
                                                    @keydown="handleContentKeydown($event)"
                                                    @input="reconcileSectionMarkers(); reconcileEmptyBlocks(); refreshOverflowWarning(); recomputeZoomSizer()"
                                                ></div>
                                                {{-- Control number, code and QR of the issued copy, outside the editable text (scope round 2026-10-15). --}}
                                                <x-documents.verify-strip doc="selected.issued_documents" doc-key="resolvedDocument?.docKey" />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    {{-- While editing: keep or drop the changes. Closing the window drops them too. --}}
                    <x-ui.button size="sm" variant="secondary" x-show="editingDocument" x-cloak @click="cancelDocumentEdits()">Cancel</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-show="editingDocument" x-cloak @click="saveDocumentEdits()">Save Changes</x-ui.button>
                    <p class="mr-auto text-[11px] leading-snug text-amber-700" x-show="!editingDocument && editedInWindow && editedInWindow === selected?.ref" x-cloak>Not saved: this edit is in this window only. Save again, or print or download it before you leave.</p>
                    <div class="mr-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] leading-snug text-ink-muted" x-show="!editingDocument && selected?.prepared && editedInWindow !== selected?.ref" x-cloak>
                        <span x-text="'Edited by ' + (selected?.prepared?.by || 'an officer') + (selected?.prepared?.at ? ' on ' + new Date(selected.prepared.at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '') + '. Staff see this copy; the resident sees the template.'"></span>
                        <button type="button" x-show="selected?.prepared?.editable && mayEditDocument" @click="useTemplateAgain()" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">Use the template again</button>
                    </div>
                    <x-ui.button size="sm" variant="secondary" icon="printer" x-show="!editingDocument" @click="printDocument()">Print</x-ui.button>
                    <x-ui.button size="sm" icon="download" x-show="!editingDocument" @click="downloadDocument()">Download PDF</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        </div>

        </div>
    </div>
</x-layouts.admin>
