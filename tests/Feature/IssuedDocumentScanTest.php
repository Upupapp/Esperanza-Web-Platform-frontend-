<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The scan of the signed, sealed paper (owner, 2026-10-15): an officer
 * attaches it to an issued document from the request's Issued Documents
 * panel, and the resident sees it in the app beside the control number and
 * QR. These pin what the panel sends and when it offers the upload.
 */
class IssuedDocumentScanTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_an_officer_with_the_release_permission_attaches_the_signed_copy(): void
    {
        $panel = $this->source('resources/views/components/admin/scope/issued-documents.blade.php');
        $js = $this->source('resources/js/scope-admin-requests.js');

        // Offered only to someone who may release it, and never on a revoked copy.
        $this->assertStringContainsString('x-show="({{ $can }}) && !d.revoked" class="cursor-pointer', $panel);
        $this->assertStringContainsString('accept="application/pdf,image/jpeg,image/png,image/webp"', $panel);
        $this->assertStringContainsString("'Signed copy attached'", $panel);
        // Sent as a file to the scan endpoint; the reply's documents replace the list.
        $this->assertStringContainsString("body.append('scan', file);", $js);
        $this->assertStringContainsString("'/admin/documents/' + encodeURIComponent(d.control_number) + '/scan', body", $js);
        $this->assertStringContainsString('r.issued_documents = res.data?.documents || r.issued_documents;', $js);
    }
}
