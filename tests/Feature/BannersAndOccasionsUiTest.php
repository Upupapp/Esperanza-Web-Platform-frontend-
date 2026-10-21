<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Balita as one feed, Banners & Occasions, automated pop-ups and pictures
 * (owner, 2026-10-21). These pin the contract lists the screens read, the
 * Balita dashboard and composer, the banner form with its picture and
 * preview, the Occasion & Pop-up manager, and the resident's pop-up. No
 * resident data: the pages read the API at run time.
 */
class BannersAndOccasionsUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_lists_come_from_the_contract_not_the_views(): void
    {
        $c = config('esperanza_occasions');
        $this->assertSame(['birthday', 'barangay_fiesta', 'municipal_fiesta', 'national_holiday', 'lgu_event', 'custom'], array_column($c['types'], 'key'));
        $this->assertSame(['window', 'annual', 'birthday'], array_column($c['triggers'], 'key'));
        $this->assertSame(['draft', 'scheduled', 'active', 'paused', 'completed', 'archived'], array_column($c['states'], 'key'));
        $this->assertSame(['senior', 'pwd', 'solo_parent', '4ps', 'ip'], array_column($c['audience_sectors'], 'key'));
        $this->assertSame(['once', 'daily', 'every_launch'], array_column($c['popup_frequencies'], 'key'));
        $this->assertSame(['seen', 'until_dismissed'], array_column($c['popup_dismissals'], 'key'));
        $this->assertSame(['image/jpeg', 'image/png', 'image/webp'], $c['image']['mime']);
        $this->assertArrayNotHasKey('greeting_occasions', config('esperanza_balita'), 'greetings left Balita');

        $occasions = $this->source('resources/views/admin/communications/occasions.blade.php');
        $banners = $this->source('resources/views/admin/communications/banners.blade.php');
        $this->assertStringContainsString("x-data=\"adminOccasions(@js(config('esperanza_occasions')), @js(config('esperanza.barangays')), @js(config('esperanza_occasions.image.max_bytes')))\"", $occasions);
        $this->assertStringContainsString("x-data=\"adminBanners(@js(config('esperanza_occasions')), @js(config('esperanza.barangays')), @js(config('esperanza_occasions.image.max_bytes')))\"", $banners);
        foreach (['contract.types', 'contract.triggers', 'contract.audience_sectors', 'contract.popup_frequencies', 'contract.popup_dismissals', 'contract.banner_displays'] as $list) {
            $this->assertStringContainsString('x-for="', $occasions);
            $this->assertStringContainsString($list, $occasions, $list);
        }
        foreach (['Barangay Fiesta', 'Once per resident', 'Senior citizens (60+)'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'</option>', $occasions, 'not typed in the view: '.$label);
        }
    }

    public function test_balita_is_one_feed_with_four_cards_filters_and_template(): void
    {
        $view = $this->source('resources/views/admin/communications/balita.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString("x-data=\"adminBalita(@js(config('esperanza.barangays')))\"", $view);
        foreach (["label=\"Published\" valueBind=\"card('published')\"", "label=\"Pending Review\" valueBind=\"card('pending_review')\"", "label=\"Drafts and sent back\" valueBind=\"card('drafts')\"", "label=\"Scheduled\" valueBind=\"card('scheduled')\""] as $card) {
            $this->assertStringContainsString($card, $view);
        }
        $this->assertStringContainsString("api.get('/admin/announcements/summary')", $js);
        $this->assertStringContainsString('aria-label="Balita audience" x-model="scope"', $view);
        $this->assertStringContainsString('@click="showScheduled()" :aria-pressed="scheduledOnly"', $view);
        $this->assertStringContainsString("statuses: ['Draft', 'Pending Review', 'Approved', 'Released', 'Rejected', 'Archived'],", $js, 'Scheduled is a filter, not a status');
        // Edit, Archive, Use as Template - the template is made by the server.
        $this->assertStringContainsString('@click="openEdit(a)"', $view);
        $this->assertStringContainsString("@click=\"quick(a, 'Archived', ", $view);
        $this->assertStringContainsString('@click="useAsTemplate(a)"', $view);
        $this->assertStringContainsString("api.post('/admin/announcements/' + post.id + '/duplicate')", $js);
        // No greeting kind left anywhere in Balita.
        foreach (['post_type', 'greeting', 'visible_until', 'Occasion Greeting'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, $gone);
        }
        $this->assertStringNotContainsString('greetingPayload', $js);
    }

    public function test_the_composer_previews_saves_publishes_or_schedules_and_asks_before_discarding(): void
    {
        $view = $this->source('resources/views/admin/communications/balita.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString('aria-label="Preview"', $view);
        $this->assertStringContainsString('x-text="previewWhen + ', $view);
        $this->assertStringContainsString('aria-label="Who sees it" x-model="form.audience"', $view);
        $this->assertStringContainsString('<x-balita.media-field base="/admin/balita" model="media"', $view);
        $this->assertStringContainsString("@click=\"save('draft')\"", $view);
        $this->assertStringContainsString("@click=\"save('send')\"", $view);
        $this->assertStringContainsString("if (this.form.schedule) return this.approver ? 'Schedule Post' : 'Send for review';", $js);
        $this->assertStringContainsString("return this.approver ? 'Publish Now' : 'Send for review';", $js);
        $this->assertStringContainsString('x-model="form.publish_date"', $view);
        $this->assertStringContainsString('x-model="form.publish_time"', $view);
        $this->assertStringContainsString("const manilaIso = (ymd, hm) => (ymd ? String(ymd).slice(0, 10) + 'T' + (hm || '00:00') + ':00+08:00' : null);", $js);
        // Cancel, the X, Esc and a click outside all ask first.
        $this->assertStringContainsString('set open(v) { v ? (composerOpen = true) : requestClose(); }', $view);
        $this->assertStringContainsString("'Discard the changes to this post?'", $js);
        // A writer's post still goes through review; an approver's moves on.
        $this->assertStringContainsString("if (this.approver && status === 'Pending Review') {", $js);
        $this->assertStringContainsString("if (!this.form.schedule) await step('Released');", $js);
    }

    public function test_banners_and_occasions_is_one_tab_with_two_views(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString('Banners &amp; Occasions</button>', $page);
        $this->assertStringContainsString("@include('admin.communications.banners')", $page);
        $this->assertStringContainsString("@include('admin.communications.occasions')", $page);
        $this->assertStringContainsString('Occasions &amp; Pop-ups</button>', $page);
        $this->assertStringContainsString("@include('admin.communications.balita')", $page);
    }

    public function test_a_banner_picture_is_uploaded_checked_previewed_and_removable(): void
    {
        $banners = $this->source('resources/views/admin/communications/banners.blade.php');
        $field = $this->source('resources/views/components/balita/image-field.blade.php');
        $media = $this->source('resources/js/balita-media.js');
        $js = $this->source('resources/js/admin-occasions.js');

        $this->assertStringContainsString('<x-balita.image-field model="image" existing="existingImage" rules="rules" label="Banner picture" aspect="aspect-[3/1]"', $banners);
        $this->assertStringContainsString('x-model="form.alt_text"', $banners);
        $this->assertStringContainsString('x-model="form.placement"', $banners);
        $this->assertStringContainsString("toggle('sectors', s.key)", $banners);
        $this->assertStringContainsString("toggle('barangays', b)", $banners);
        $this->assertStringContainsString("previewDevice === 'mobile' ? 'aspect-ratio: 390 / 172' : 'aspect-ratio: 21 / 7'", $banners);
        $this->assertStringContainsString('@click="remove(b)"', $banners);
        $this->assertStringContainsString("api.delete('/admin/banners/' + b.id)", $js);
        // Real type from the first bytes, then the same upload as a Balita photo.
        $this->assertStringContainsString('export async function sniffImage(file)', $media);
        $this->assertStringContainsString('if (at(0, 0xff, 0xd8, 0xff)) return \'image/jpeg\';', $media);
        $this->assertStringContainsString("' is not a JPEG, PNG or WebP picture.'", $media);
        $this->assertStringContainsString('item.media = await upload(base, item);', $media);
        $this->assertStringContainsString('@click="remove()"', $field);
        $this->assertStringContainsString('Replace', $field);
        $this->assertStringContainsString('x-text="statusText()"', $field);
        // Only a changed picture is sent; null removes it.
        $this->assertStringContainsString("if (this.mode === 'add' || this.image.changed) body.media_id = this.image.id;", $js);
        // The carousel describes the picture.
        $this->assertStringContainsString(':alt="b.alt_text || b.title"', $this->source('resources/views/components/citizen/banner-carousel.blade.php'));
    }

    /** One picture field for banners and pop-ups, and a lifecycle that never loses a saved picture by accident (2026-10-21). */
    public function test_one_picture_field_keeps_the_saved_picture_safe(): void
    {
        $banners = $this->source('resources/views/admin/communications/banners.blade.php');
        $occasions = $this->source('resources/views/admin/communications/occasions.blade.php');
        $field = $this->source('resources/views/components/balita/image-field.blade.php');
        $media = $this->source('resources/js/balita-media.js');
        $js = $this->source('resources/js/admin-occasions.js');

        // The same component in both editors, for new and saved records.
        $this->assertStringContainsString('<x-balita.image-field model="image" existing="existingImage"', $banners);
        $this->assertStringContainsString('<x-balita.image-field model="image" existing="existingImage"', $occasions);
        $this->assertSame(['image/jpeg', 'image/png', 'image/webp'], config('esperanza_occasions.image.mime'));
        $this->assertSame(10 * 1024 * 1024, config('esperanza_occasions.image.max_bytes'), 'set per deployment; 10 MB by default');
        $this->assertStringContainsString('maxBytes: contract.image?.max_bytes || maxBytes,', $js);
        $this->assertStringContainsString('@drop.prevent="drop($event)"', $field);

        // A failed replacement keeps the saved picture, and says so.
        $this->assertStringContainsString('const holding = !done && item && this.kept ? this.kept : null;', $media);
        $this->assertStringContainsString('@click="keepCurrent()"', $field);
        $this->assertStringContainsString('Keep the current picture', $field);
        // Removing a saved picture is confirmed and happens on save; Cancel keeps it.
        $this->assertStringContainsString("'Remove the picture? It is taken off when you save; until then, Cancel keeps it.'", $media);
        // Only a changed picture is sent, so an edit of the words keeps it.
        $this->assertStringContainsString('changed: id !== this.original,', $media);
        $this->assertStringContainsString("if (this.mode === 'add' || this.image.changed) body.media_id = this.image.id;", $js);
        // Cancel drops what was uploaded and never saved.
        $this->assertStringContainsString("discardPicture('banner-image');", $js);
        $this->assertStringContainsString("discardPicture('occ-image');", $js);
        $this->assertStringContainsString("this.\$el.addEventListener('image-field-discard', () => this.discardUnsaved());", $media);
        // Try again resumes the same upload rather than sending it twice.
        $this->assertStringContainsString("if (held.data?.status === 'open') started = held;", $media);
        // A pop-up shows the whole picture; a banner shows its crop.
        $this->assertStringContainsString('fit="contain" field-id="occ-image"', $occasions);
        $this->assertStringContainsString("{{ \$fit === 'contain' ? 'object-contain' : 'object-cover' }}", $field);
        // The banner's picture only by choice, and each record keeps its own link.
        $this->assertStringContainsString('@click="useBannerPicture(true)"', $occasions);
        $this->assertStringContainsString('body.picture_from_banner = true;', $js);
        // The Balita photo picker reads the real type too.
        $this->assertStringContainsString("if (kind === 'image' && !(await sniffImage(file))) kind = null;", $media);
    }

    public function test_the_occasion_manager_lists_filters_and_acts_without_publishing_on_preview(): void
    {
        $view = $this->source('resources/views/admin/communications/occasions.blade.php');
        $js = $this->source('resources/js/admin-occasions.js');

        foreach (["{ key: 'all', label: 'All' }", "{ key: 'draft', label: 'Draft' }", "{ key: 'scheduled', label: 'Scheduled' }", "{ key: 'active', label: 'Active' }", "{ key: 'paused', label: 'Paused' }", "{ key: 'completed', label: 'Completed' }"] as $chip) {
            $this->assertStringContainsString($chip, $js, $chip);
        }
        foreach (['view(o)', 'openEdit(o)', 'previewRow(o)', "act(o, 'duplicate')", "act(o, 'pause')", "act(o, 'resume')", "act(o, 'activate')", "act(o, 'archive')"] as $action) {
            $this->assertStringContainsString($action, $view, $action);
        }
        // A grid of compact cards (2026-10-21 UI pass): three, two or one across;
        // the rarer actions in a More menu that Esc and a click away close.
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4', $view);
        $this->assertStringContainsString('role="menu"', $view);
        $this->assertStringContainsString('@keydown.escape.stop="menu = false" @click.outside="menu = false"', $view);
        $this->assertStringContainsString('x-text="cardWhen(o)"', $view);
        $this->assertStringContainsString("@click=\"search = ''; setState('all')\">Clear filters", $view);
        // The occasion and post windows are sized to their content.
        $this->assertStringContainsString('<x-ui.modal maxWidth="xl" :bare="true">', $view);
        $this->assertStringContainsString('<x-ui.modal maxWidth="2xl" :bare="true">', $this->source('resources/views/admin/communications/balita.blade.php'));
        // The preview is drawn from the form; it calls nothing.
        $preview = substr($js, strpos($js, 'previewForm() {'), strpos($js, 'when,', strpos($js, 'previewForm() {')) - strpos($js, 'previewForm() {'));
        $this->assertStringNotContainsString('api.', $preview);
        $this->assertStringContainsString('A preview only: nothing is sent.', $view);

        // Audience: all, barangays and groups, its summary and its count from the server.
        $this->assertStringContainsString("@change=\"setAudience('some')\"", $view);
        $this->assertStringContainsString('x-text="audienceSummary"', $view);
        $this->assertStringContainsString("api.get('/admin/occasions/audience'", $js);
        // Banner: none, existing, new; synchronised without touching its dates; warnings.
        $this->assertStringContainsString('x-model="form.banner_sync"', $view);
        $this->assertStringContainsString('@click="newBanner()"', $view);
        $this->assertStringContainsString("The banner's own dates are not changed", $view);
        $this->assertStringContainsString('x-for="w in formWarnings"', $view);
        // Unsaved changes are asked about.
        $this->assertStringContainsString("'Discard the changes to this occasion?'", $js);
    }

    public function test_the_occasion_form_is_a_five_step_wizard(): void
    {
        $view = $this->source('resources/views/admin/communications/occasions.blade.php');
        $js = $this->source('resources/js/admin-occasions.js');

        // Five steps, in order (the banner is chosen with the pop-up), each with its Next label.
        $labels = ["label: 'Details', next: 'Next: Schedule'", "label: 'Schedule & Trigger', next: 'Next: Audience'", "label: 'Audience', next: 'Next: Pop-up Content'", "label: 'Pop-up Content', next: 'Review'", "label: 'Review & Activate', next: ''"];
        $at = 0;
        foreach ($labels as $label) {
            $found = strpos($js, $label, $at);
            $this->assertNotFalse($found, $label);
            $at = $found;
        }
        // One step shows at a time; x-show keeps the others (and the picture) in the page.
        for ($n = 1; $n <= 5; $n++) {
            $this->assertStringContainsString('<section x-show="step === '.$n.'"', $view);
        }
        $this->assertStringNotContainsString('<section x-show="step === 6"', $view);
        $this->assertStringNotContainsString('x-if="step', $view);
        // The banner is part of step 4; the live preview is on Review.
        $step4 = substr($view, strpos($view, '<section x-show="step === 4"'), strpos($view, '<section x-show="step === 5"') - strpos($view, '<section x-show="step === 4"'));
        $this->assertStringContainsString('name="occ-banner"', $step4);
        $this->assertStringContainsString('@click="newBanner()"', $step4);
        $this->assertStringNotContainsString('aria-label="Live preview"', $step4);
        $this->assertStringContainsString('aria-label="Live preview"', substr($view, strpos($view, '<section x-show="step === 5"')));
        // On a phone: "Step 3 of 5 — Audience".
        $this->assertStringContainsString("x-text=\"'Step ' + step + ' of 5 — ' + stepInfo.label\"", $view);

        // Next checks the step first; a step further on is reached only through ready steps.
        $next = substr($js, strpos($js, 'next() {'), 300);
        $this->assertLessThan(strpos($next, 'this.show('), strpos($next, 'this.stepProblems(this.step).length'));
        $this->assertStringContainsString('@click="next()"', $view);
        $this->assertStringContainsString('x-show="step > 1" x-cloak x-bind:disabled="processing" @click="back()">Back', $view);
        $this->assertStringContainsString('canReach(n) { return n <= this.maxStep + 1; }', $js);

        // Save Draft on every step; Save and activate only on Review. Both guard against a second press.
        $this->assertMatchesRegularExpression('/<x-ui.button variant="secondary" size="sm" loading="processing"[^>]*@click="save\(\'draft\'\)">/', $view);
        $this->assertStringContainsString('x-show="step === 5 && (!editing || editing.state === \'draft\')" x-cloak @click="save(\'activate\')"', $view);
        $this->assertStringContainsString('if (this.processing) return;', substr($js, strpos($js, 'async save(how) {'), 200));

        // Errors beside the field once its step was tried; the server's go to their step.
        $this->assertStringContainsString("issue('name', 'name')", $view);
        $this->assertStringContainsString("issue('popup_title', 'popup_title')", $view);
        $this->assertStringContainsString('function serverFieldStep(key)', $js);
        // Review: a card per step with Edit; a later start is labelled Scheduled.
        $this->assertStringContainsString('@click="goTo(card.n)"', $view);
        $this->assertStringContainsString("label: 'Scheduled'", $js);
        // A saved occasion opens on its review.
        $this->assertStringContainsString('await this.openForm(OCCASION_STEPS.length);', $js);
    }

    public function test_the_resident_pop_up_comes_from_the_server_birthday_included(): void
    {
        $layout = $this->source('resources/views/components/layouts/citizen.blade.php');
        $popup = $this->source('resources/views/components/citizen/greeting-popup.blade.php');

        $this->assertStringContainsString('<x-citizen.greeting-popup />', $layout);
        $this->assertStringNotContainsString('<x-citizen.birthday-popup />', $layout, 'no birthday check in the browser any more');
        $this->assertFileDoesNotExist(base_path('resources/views/components/citizen/birthday-popup.blade.php'));
        $this->assertStringNotContainsString('checkBirthday', $this->source('resources/views/citizen/dashboard.blade.php'));

        $this->assertStringContainsString("\$api.get('/citizen/popups/active')", $popup);
        $this->assertStringContainsString("\$api.post('/citizen/popups/' + encodeURIComponent(this.g.id) + '/seen')", $popup);
        $this->assertStringContainsString("\$api.post('/citizen/popups/' + encodeURIComponent(this.g.id) + '/dismiss')", $popup);
        $this->assertStringContainsString('<x-ui.t fil="Huwag nang ipakita" en="Don\'t show again" />', $popup);
        $this->assertStringContainsString('x-show="g.birthday"', $popup);
        $this->assertStringContainsString("'esperanza_popup_shown_' + g.id + '_' + (g.occurrence || 'once')", $popup);
        $this->assertStringContainsString('if (n.pendingAlert || n.pendingWelcome) return true;', $popup);
        $this->assertStringContainsString('x-text="g.message"', $popup);
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $popup);
        $this->assertStringNotContainsString('x-html', $popup);
        $this->assertStringNotContainsString('Pagbati" en="Greeting"', $this->source('resources/views/citizen/announcements.blade.php'), 'no greeting chip in the feed');
    }
}
