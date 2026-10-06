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
        $this->assertStringContainsString('this.$refs.docEditor.innerHTML = this.documentEditBackup;', $view, 'the editor content is reset, not only the bound string');
        $this->assertStringContainsString('x-show="editingDocument" x-cloak @click="saveDocumentEdits()">Save Changes', $view);
        $this->assertStringContainsString('x-show="editingDocument" x-cloak @click="cancelDocumentEdits()">Cancel', $view);
        $this->assertStringContainsString('x-show="!editingDocument" @click="printDocument()"', $view, 'nothing prints half-edited');
    }

    /** A Business Permit reached Released with 2 of its 7 documents checked. */
    public function test_approve_waits_until_every_document_is_accepted(): void
    {
        $view = $this->source('resources/views/admin/document-requests.blade.php');

        $this->assertStringContainsString('get approveBlocked()', $view);
        $this->assertStringContainsString("x-bind:disabled=\"processing || !!approveBlocked", $view);
        $this->assertStringContainsString('x-text="approveBlocked"', $view, 'and says why');
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
