<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Fixes found while mapping the MSWDO, MIO, MDRRMO and resident flows for the
 * office guides (2026-11-13).
 */
class FlowGuideFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_a_request_notice_opens_the_request_on_this_site(): void
    {
        $app = $this->source('resources/js/app.js');

        // The server links /citizen/document-requests/{ref}; this site opens ?ref=.
        $this->assertStringContainsString('function siteLink(link)', $app);
        $this->assertStringContainsString("return m ? '/citizen/' + m[1] + '?ref=' + m[2] : link;", $app);
        $this->assertStringContainsString('link: siteLink(n.link),', $app);
    }

    public function test_the_resident_steppers_speak_the_residents_words(): void
    {
        $dokyu = $this->source('resources/views/citizen/document-requests.blade.php');
        $tulong = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringContainsString("'Mark to Release': ['Handa nang Kunin', 'Ready to pick up'],", $dokyu);
        $this->assertStringContainsString("'Ready for Release': ['Handa nang Ibigay', 'Ready to receive'],", $tulong);
        foreach ([$dokyu, $tulong] as $page) {
            $this->assertStringContainsString('x-text="stepText(label)"', $page);
        }
    }

    public function test_a_resubmitted_dokyu_request_can_be_rejected_and_says_what_to_do(): void
    {
        $admin = $this->source('resources/views/admin/document-requests.blade.php');

        $this->assertStringContainsString("['Under Verification', 'Under Review', 'Resubmitted'].includes(this.selected.status) && this.may('reject')", $admin);
        $this->assertStringContainsString("'Resubmitted': 'Approve Request',", $admin);
        $this->assertStringContainsString('Check and approve each replaced document, then approve the request.', $admin);
        $this->assertStringNotContainsString('You can approve this request directly.', $admin);
    }

    public function test_banners_and_occasions_can_be_sent_back_with_remarks(): void
    {
        $js = $this->source('resources/js/admin-occasions.js');
        $banners = $this->source('resources/views/admin/communications/banners.blade.php');
        $occasions = $this->source('resources/views/admin/communications/occasions.blade.php');

        $this->assertStringContainsString("'/admin/banners/' + b.id + '/transition', { to: 'Rejected', remarks }", $js);
        $this->assertStringContainsString("'/admin/occasions/' + o.id + '/transition', { to: 'Rejected', remarks }", $js);
        $this->assertStringContainsString("x-show=\"b.status === 'Pending Review' && canMove(b, 'Rejected')\"", $banners);
        $this->assertStringContainsString("x-show=\"o.status === 'Pending Review' && canMove(o, 'Rejected')\"", $occasions);
        foreach ([$banners, $occasions] as $view) {
            $this->assertStringContainsString('Remarks for the writer (required to send it back)', $view);
        }
    }

    public function test_events_and_banners_name_statuses_as_balita_does(): void
    {
        $comms = $this->source('resources/views/admin/communications.blade.php');
        $banners = $this->source('resources/views/admin/communications/banners.blade.php');

        $this->assertStringContainsString("labelBind=\"({ 'Released': 'Published', 'Rejected': 'Sent back', 'Pending Review': 'Waiting for review' })[e.status] || e.status\"", $comms);
        $this->assertStringContainsString("labelBind=\"({ 'Released': 'Published', 'Rejected': 'Sent back', 'Pending Review': 'Waiting for review' })[b.status] || b.status\"", $banners);
        $this->assertStringContainsString('@click="requestChanges()">Send back</x-ui.button>', $comms);
        $this->assertStringContainsString('<span x-text="{{ $labelBind ?? $statusBind }}"></span>', $this->source('resources/views/components/ui/badge.blade.php'));
    }

    public function test_small_wording_fixes(): void
    {
        $this->assertStringContainsString('Verify your identity above first.', $this->source('resources/views/auth/citizen-register.blade.php'));
    }
}
