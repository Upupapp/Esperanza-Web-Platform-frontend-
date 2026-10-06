<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * "Esperanza bugs QA" (2026-10-06): what a tester found on the staff site and
 * the resident site, pinned so it cannot come back. Each test is named for
 * what they saw.
 */
class BugsQaFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** Closing the prepared document kept the edits, and they printed. */
    public function test_closing_or_cancelling_an_edit_puts_the_document_back(): void
    {
        $view = $this->source('resources/views/admin/document-requests.blade.php');

        $this->assertStringContainsString('set open(v) { if (!v && editingDocument) cancelDocumentEdits();', $view, 'closing the window while editing cancels');
        $this->assertStringContainsString('const editor = this.$refs.docEditor || this.getDocContentEl();', $view, 'found from the close path too, where $refs does not resolve');
        $this->assertStringContainsString('editor.innerHTML = this.documentEditBackup;', $view, 'the editor content is reset, not only the bound string');
        $this->assertStringContainsString('x-show="editingDocument" x-cloak @click="saveDocumentEdits()">Save Changes', $view);
        $this->assertStringContainsString('x-show="editingDocument" x-cloak @click="cancelDocumentEdits()">Cancel', $view);
        $this->assertStringContainsString('x-show="!editingDocument" @click="printDocument()"', $view, 'nothing prints half-edited');

        $toolbar = $this->source('resources/views/components/citizen/document-editor-toolbar.blade.php');
        $this->assertStringNotContainsString('saveDocumentEdits()', $toolbar, 'one Save Changes, in the footer, not a second one in the toolbar');
        $this->assertStringContainsString("current: staffSite ? 'en'", $this->source('resources/js/app.js'), 'the staff site is English-only (TAB 09), the shared editor included');

        $modal = $this->source('resources/views/components/ui/modal.blade.php');
        $this->assertStringContainsString('@click.outside="if (!$event.target.closest(\'[data-modal-layer]\')) open = false"', $modal, 'a click in the document window does not close the request behind it');
        $this->assertStringContainsString('data-modal-layer', $modal);
        $this->assertStringContainsString('@keydown.escape.window="if (window.closesOnEscape($el)) open = false"', $modal, 'Escape closes only the window on top');
        $this->assertStringContainsString('x-effect="window.modalLayer($el, open)"', $modal);
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('window.closesOnEscape = (layer) =>', $app);
        $this->assertStringContainsString("if (e.key === 'Escape') { e.stopPropagation(); done(null); }", $app, 'Escape in the camera leaves the request open');
        foreach (['document-requests', 'assistance-requests', 'papeles'] as $page) {
            $this->assertStringContainsString('data-modal-overlay x-show="previewDoc"', $this->source("resources/views/admin/{$page}.blade.php"), "{$page}: Escape on the document viewer closes the viewer only");
        }
    }

    /** A Business Permit reached Released with 2 of its 7 documents checked. */
    public function test_approve_waits_until_every_document_is_accepted(): void
    {
        $view = $this->source('resources/views/admin/document-requests.blade.php');

        $this->assertStringContainsString('get approveBlocked()', $view);
        $this->assertStringContainsString('x-bind:disabled="processing || !!approveBlocked', $view);
        $this->assertStringContainsString('x-text="approveBlocked"', $view, 'and says why');
        $this->assertStringContainsString("'Waiting for the resident to replace '", $view, 'a flagged document is waited for, not approved');
    }

    /** The website never asked for the declared capital, so the server refused the permit. */
    public function test_the_resident_site_asks_for_the_amount_a_fee_is_computed_from(): void
    {
        $view = $this->source('resources/views/citizen/document-requests.blade.php');

        $this->assertStringContainsString('fee?.required_inputs', $view, 'the names come from the service');
        $this->assertStringContainsString('fee_inputs: feeInputs', $view, 'and are sent with the request');
        $this->assertStringContainsString("'Enter the amount the fee is computed from: '", $view, 'Continue checks them');
        $this->assertStringContainsString('declared_capital:', $view);
    }

    /** The resident could not tell which documents were required, or which the office accepted. */
    public function test_each_requirement_says_whether_it_is_owed_and_whether_it_was_accepted(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('function reqKind(label)', $app);
        $this->assertStringContainsString('interview|case study|case assessment|details of the record being requested|thumbmark|to be taken at', $app, 'the server\'s rule');

        foreach (['resources/views/citizen/document-requests.blade.php', 'resources/views/citizen/assistance-requests.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertSame(2, substr_count($view, 'x-text="reqKindLabel('), "{$path}: the form and Track Request both label it");
            $this->assertStringContainsString("'Accepted' : 'Tinanggap'", $view, "{$path}: an accepted one says so");
            $this->assertStringContainsString("reqKind(req.name) !== 'office'", $view, "{$path}: no Attach on a step the office does");
            $this->assertStringContainsString("reqKind(q) === 'required' && !this.attachments[i]", $view, "{$path}: the wizard names the documents still owed, not office steps");
            $this->assertStringContainsString("filter(q => reqKind(q) !== 'office').length", $view, "{$path}: the attached count leaves out office steps");
            $this->assertStringContainsString('x-show="(selectedRequest.requirements || []).length" x-cloak>', $view, "{$path}: the list shows at every stage, not only the first");
        }
    }

    /** "Take Photo" opened the file picker on a computer. */
    public function test_take_photo_opens_the_camera_on_a_computer(): void
    {
        $this->assertStringContainsString('window.takePhoto = async () =>', $this->source('resources/js/app.js'));
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $path) {
            $this->assertStringContainsString('const shot = await window.takePhoto();', $this->source($path), $path);
        }
    }

    /** Every uploaded document had to be downloaded to be read. */
    public function test_documents_are_read_in_the_portal_without_running_them(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("import('pdfjs-dist')", $app, 'loaded only when a PDF is shown');
        $this->assertStringContainsString('isEvalSupported: false, enableXfa: false', $app, 'nothing in a PDF runs');
        $this->assertStringContainsString("Alpine.data('pdfPreview'", $app);
        $this->assertStringContainsString("? 'assets/[name]-[hash].js'", $this->source('vite.config.js'), 'the worker is named .js, or a host serves it as octet-stream and nothing draws');

        foreach ([
            'resources/views/admin/document-requests.blade.php',
            'resources/views/admin/assistance-requests.blade.php',
            'resources/views/admin/papeles.blade.php',
            'resources/views/admin/constituents/modal-citizen-account.blade.php',
        ] as $path) {
            $this->assertStringContainsString('x-data="pdfPreview"', $this->source($path), $path);
            $this->assertStringNotContainsString('uploaded files are never opened inside the portal', $this->source($path), $path);
        }
        $this->assertStringContainsString("'/admin/papeles/' + doc.id + '/view'", $this->source('resources/views/admin/papeles.blade.php'), 'Papeles reads with view, not export');
    }
}
