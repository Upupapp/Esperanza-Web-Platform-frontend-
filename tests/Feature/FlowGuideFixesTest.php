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

    public function test_an_incident_report_can_be_marked_not_valid_with_a_reason(): void
    {
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $citizen = $this->source('resources/js/scope-citizen.js');

        // A duplicate or a prank ended as "resolved. Thank you for reporting it." (2026-11-13).
        $this->assertStringContainsString("x-show=\"selectedIncident?.available_verbs?.includes('reject') && can('sakuna_incidents', 'validate')\"", $modal);
        $this->assertStringContainsString(':disabled="incidentActing || incidentRejectReason.trim().length < 5"', $modal);
        $this->assertStringContainsString("if (verb === 'reject') { await this.actIncidentVerb(inc, 'reject', { note: this.incidentRejectReason.trim() }); return; }", $sakuna);
        $this->assertStringContainsString("'Rejected': 'Not valid',", $sakuna);
        $this->assertStringContainsString("Rejected: ['Not confirmed', 'Hindi nakumpirma',", $citizen);
        $this->assertStringContainsString("reject: ['Not confirmed by the office', 'Hindi nakumpirma ng opisina'],", $citizen);
    }

    public function test_two_events_with_one_name_on_one_day_still_list(): void
    {
        $events = $this->source('resources/views/citizen/events.blade.php');

        // The list was keyed by date + title: a repeated key stopped Alpine
        // drawing any card, so Kaganapan showed none while counting three.
        $this->assertStringContainsString("uid: (e.key || e.date + ' ' + (e.title || e.name)) + '#' + i,", $events);
        $this->assertStringContainsString('<template x-for="(event, i) in selectedEvents" :key="event.uid">', $events);
        $this->assertStringNotContainsString('event.key + event.title', $events);
    }

    public function test_the_to_verify_cards_count_what_the_server_has(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $tab = $this->source('resources/views/admin/constituents/tab-citizen-verification.blade.php');

        // Under Verification is a Pending Review account whose ID is confirmed;
        // the server has no such account status, so the card read 0 forever.
        $this->assertStringContainsString("if (a.status === 'Pending Review' && a.identity_status === 'Verified') return 'Under Verification';", $page);
        $this->assertStringContainsString("underVerification: n('Under Verification'),", $page);
        $this->assertStringContainsString('x-text="cvStatusOf(i)"', $tab);
        // Families and households have no Rejected or Missing Documents.
        $this->assertStringContainsString("return ['Unverified', 'For Validation', 'Partially Verified', 'Verified'];", $page);
        $this->assertStringNotContainsString('cvFamilyCounts.rejected', $tab);
        $this->assertStringNotContainsString('cvHouseholdCounts.rejected', $tab);
        // An imported, untouched record is not waiting on anyone.
        $this->assertStringContainsString("const started = r => ['For Validation', 'Partially Verified'].includes(r.verification);", $page);
        // No reminder is sent, so none is claimed.
        $this->assertStringNotContainsString('Already Notified', $tab);
        $this->assertStringContainsString('Waiting for them to register', $tab);
    }

    public function test_indigenous_peoples_can_be_tagged_and_filtered(): void
    {
        $this->assertStringContainsString('<option value="ip">Indigenous Peoples</option>', $this->source('resources/views/admin/constituents.blade.php'));
        $this->assertStringContainsString('<option value="ip">Indigenous Peoples</option>', $this->source('resources/views/admin/constituents/tab-citizen-verification.blade.php'));
        $this->assertStringContainsString("{ key: 'ip', label: 'Indigenous Peoples'", $this->source('resources/views/admin/constituents/modal-add-individual.blade.php'));
        $this->assertStringContainsString("'farmer','high_risk','ip']", $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php'));
    }

    public function test_app_reports_count_as_residents_and_alert_toasts_count_people(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-incidents.blade.php');
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');

        $this->assertStringContainsString("x-show=\"['Citizen Portal', 'Mobile App'].includes(inc.source)\"", $tab);
        $this->assertStringContainsString("['Citizen Portal', 'Mobile App'].includes(m.source)", $this->source('resources/js/sakuna-map.js'));
        $this->assertStringContainsString("' released to ' + n + (n === 1 ? ' resident.' : ' residents.')", $sakuna);
        $this->assertStringContainsString("'Cancellation sent to ' + n + (n === 1 ? ' resident.' : ' residents.')", $sakuna);
        $this->assertStringNotContainsString('recipient(s)', $sakuna);
    }

    public function test_a_scheduled_post_can_be_changed(): void
    {
        $js = $this->source('resources/js/scope-admin-records.js');
        $view = $this->source('resources/views/admin/communications/balita.blade.php');

        $this->assertStringContainsString("moved = (await this.move(post, 'Draft')) === true;", $js);
        $this->assertStringContainsString('x-show="isScheduled(a) && canMove(a, \'Draft\') && can(\'edit\')" @click="changeScheduled(a)"', $view);
    }
}
