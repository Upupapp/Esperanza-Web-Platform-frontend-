<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Esperanza QA follow-up (2026-10-07): the Edit Profile mobile number, the
 * reopened proof-of-release photo, and a new Balita post's notification
 * opening the post. Pinned so they cannot come back.
 */
class QaFollowUpTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** Edit Profile took a new mobile number and silently dropped it. */
    public function test_a_new_mobile_number_is_confirmed_with_a_code(): void
    {
        $view = $this->source('resources/views/citizen/profile.blade.php');

        $this->assertStringNotContainsString('a flow this quick-edit modal does not implement', $view);
        $this->assertStringContainsString("await \$api.post('/citizen/profile/contact', { channel: 'mobile', value: this.editForm.mobile });", $view);
        $this->assertStringContainsString("await \$api.post('/citizen/profile/contact/verify', { code: this.mobileCode });", $view);
        $this->assertStringContainsString("const mobileError = this.mobileChanged ? window.phMobile.error(this.editForm.mobile, false, lang) : '';", $view, 'the 11-digit rule first');
        $this->assertStringContainsString('@click="verifyMobile()"', $view);
        $this->assertStringContainsString('@input="editForm.mobile = window.phMobile.clean($event.target.value)"', $view);
    }

    /** A reopened request's proof-of-release photo was blank: the API serves files as downloads. */
    public function test_the_reopened_proof_of_release_photo_is_drawn(): void
    {
        $this->assertStringContainsString('window.viewableImage = async (url, mime) => {', $this->source('resources/js/app.js'));
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringNotContainsString('<img :src="selected.release_evidence"', $view, "{$page}: not the download URL itself");
            $this->assertStringContainsString('x-effect="window.viewableImage(selected.release_evidence, selected.release_evidence_mime)', $view, "{$page}: read as an image");
            $this->assertStringContainsString("r.release_evidence_mime = (proof.file?.mime || '') + ' ' + (proof.file?.name || '');", $view);
        }
    }

    /** A New in Balita notification opens /citizen/announcements?post=ID, on that post. */
    public function test_a_balita_notification_opens_the_post(): void
    {
        $view = $this->source('resources/views/citizen/announcements.blade.php');

        $this->assertStringContainsString("const want = Number(new URLSearchParams(window.location.search).get('post'));", $view);
        $this->assertStringContainsString("const one = await \$api.get('/announcements/' + want);", $view, 'an older post is fetched, not missed');
        $this->assertStringContainsString(":id=\"'post-' + post.kind + '-' + post.id\"", $view);
        $this->assertStringContainsString("document.getElementById('post-announcement-' + want)?.scrollIntoView(", $view);
        $this->assertStringContainsString('await this.openLinkedPost();', $view);
        $this->assertStringContainsString("community: ['megaphone', 'teal']", $this->source('resources/js/app.js'), 'the notification has its own icon');
    }
}
