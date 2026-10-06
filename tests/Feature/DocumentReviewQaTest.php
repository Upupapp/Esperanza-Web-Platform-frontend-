<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Esperanza QA (2026-10-07): the bell's unread number and Mark all as read, a
 * rejected document's reason and replacement, Proceed to Assessment only once
 * every document is accepted, the proof of release, the mobile-number rule,
 * and the Educational Assistance fields on the staff review. Pinned so they
 * cannot come back. The rules themselves are the API's (esperanza-backend
 * B-054); these pages follow them.
 */
class DocumentReviewQaTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** The bell showed a dot, not how many; Mark all as read sent one call per notification. */
    public function test_the_bell_counts_unread_and_mark_all_is_one_call(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('this.serverUnread = Number.isInteger(result.meta?.unread) ? result.meta.unread : null;', $app, 'the count is the server\'s, every page');
        $this->assertStringContainsString('get unreadCount() { return this.serverUnread ?? this.visible.filter((n) => n.unread).length; }', $app);
        $this->assertStringContainsString('await api.post(`${path}/read-all`);', $app, 'one call, so it holds after a refresh');
        $this->assertStringNotContainsString('Promise.all(this.visible.filter((n) => n.unread).map((n) => this.markRead(n.id)))', $app);

        $bell = $this->source('resources/views/components/citizen/topbar.blade.php');
        $this->assertStringContainsString('x-show="$store.citizenNotifications.unreadCount > 0"', $bell, 'no badge at zero');
        $this->assertStringContainsString("x-text=\"\$store.citizenNotifications.unreadCount > 99 ? '99+' : \$store.citizenNotifications.unreadCount\"", $bell, 'the number itself');
    }

    /** A rejected document said nothing about why, and could not be replaced. */
    public function test_a_rejected_document_shows_why_and_takes_a_replacement(): void
    {
        foreach (['assistance-requests', 'document-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('replaceable: !!q.replaceable,', $view, "{$page}: the server says which may be replaced");
            $this->assertStringContainsString("x-show=\"req.status === 'Rejected' && (req.reject_reason || req.remarks)\"", $view, "{$page}: the reason");
            $this->assertStringContainsString("x-show=\"req.replaceable && req.status === 'Rejected'\"", $view, "{$page}: the upload");
            $this->assertStringContainsString('@change="replaceRequirementFile(req, $event.target.files[0]);', $view);
        }
    }

    /** A Tulong case moved to assessment with a rejected document. */
    public function test_proceed_to_assessment_needs_every_document_accepted(): void
    {
        $view = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString("return (this.selected?.requirements || []).every(q => q.status === 'Approved');", $view);
        $this->assertStringNotContainsString("every(q => !['Submitted', 'Resubmitted', 'Flagged for Replacement'].includes(q.status))", $view, 'rejected no longer counts as decided');
        $this->assertStringContainsString("title: 'Waiting on a Replacement'", $view);
    }

    /** A released Pet Registration showed its status but not the proof of release. */
    public function test_the_resident_can_view_the_proof_of_release(): void
    {
        foreach (['assistance-requests', 'document-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('r.releaseProof = d.proof_of_release?.file || null;', $view, "{$page}: from the resident's own request detail");
            $this->assertStringContainsString('x-data="pdfPreview" x-effect="show(proofOpen && selectedRequest.releaseProof ? selectedRequest.releaseProof.url : null', $view, "{$page}: shown in the page's viewer");
        }
    }

    /** Contact numbers took letters and any length. */
    public function test_contact_numbers_are_eleven_digits_only(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('window.phMobile = {', $app);
        $this->assertStringContainsString("return String(value ?? '').replace(/\\D+/g, '').slice(0, 11);", $app);
        $this->assertStringContainsString("return /^09\\d{9}$/.test(v) ? '' :", $app);
        $this->assertStringContainsString('Contact number must contain 11 digits and numbers only.', $app);

        $this->assertStringContainsString('@input="form.mobile = window.phMobile.bind($event.target)"', $this->source('resources/views/auth/citizen-register.blade.php'));
        foreach (['assistance-requests', 'document-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('@input="applicant.mobile = window.phMobile.clean($event.target.value)"', $view);
            $this->assertStringContainsString('const badPhone = this.phoneProblem();', $view, "{$page}: Continue stops on a bad number");
        }

        $fields = collect(config('esperanza_municipal_documents.documents'))->concat(config('esperanza_tulong_types.types'))
            ->flatMap(fn ($d) => $d['fields'] ?? [])->keyBy('key');
        foreach (['phone', 'emergency_contact_phone', 'contact_no', 'contact_alternate'] as $key) {
            $this->assertSame('tel', $fields[$key]['type'] ?? null, "{$key} is a mobile number field");
        }
    }

    /** The staff review follows the app's Educational Assistance form. */
    public function test_the_educational_assistance_review_has_the_new_fields(): void
    {
        $fields = collect(config('esperanza_service_form_specs')['Educational Assistance']['steps'])->flatMap(fn ($s) => $s['fields'])->keyBy('key');

        $this->assertSame('select', $fields['yearOrGradeLevel']['type']);
        $this->assertContains('1st Year College', $fields['yearOrGradeLevel']['options']);
        $this->assertContains('Grade 12', $fields['yearOrGradeLevel']['options']);
        $this->assertArrayHasKey('fatherMonthlyIncome', $fields->all());
        $this->assertArrayHasKey('motherMonthlyIncome', $fields->all());
        $this->assertArrayNotHasKey('parentsMonthlyIncome', $fields->all(), 'an older single answer shows under Other details');
    }
}
