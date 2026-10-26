<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The citizen website after the app/web parity check of 2026-10-26: older
 * notifications reachable, Dokyu listing only what the server offers and
 * asking its table questions, Tulong counting only offered programs and
 * honouring show-if on tables, the Sakuna pop-up opening Emergency, Balita
 * previews opening their own post, Papeles taking only the app's file types,
 * and the Resident Profile shown view only once sent. These pin the source
 * that carries each; no resident data.
 */
class ParityCheckFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_notifications_load_100_and_reach_older_pages_with_load_more(): void
    {
        $js = $this->source('resources/js/app.js');
        $page = $this->source('resources/views/citizen/notifications.blade.php');

        // 100 a page (the server's most) for residents, not its default 25; the staff bell keeps 25.
        $this->assertStringContainsString('const NOTIFICATIONS_PER_PAGE = 100;', $js);
        $this->assertStringContainsString("Alpine.store('citizenNotifications', makeNotificationStore('/citizen/notifications', NOTIFICATIONS_PER_PAGE));", $js);
        $this->assertStringContainsString("Alpine.store('adminNotifications', makeNotificationStore('/admin/notifications'));", $js);
        $this->assertStringContainsString('const pageParams = (page = null) => ({ ...(perPage ? { per_page: perPage } : {}), ...(page ? { page } : {}) });', $js);
        $this->assertStringContainsString('await api.get(path, { params: pageParams() });', $js);

        // The next page while meta.last_page allows, added under what is shown, without repeats.
        $this->assertStringContainsString('get hasMore() { return this.state === \'loaded\' && this.page < this.lastPage; },', $js);
        $this->assertStringContainsString('await api.get(path, { params: pageParams(next) });', $js);
        $this->assertStringContainsString('this.lastPage = Math.max(this.page, Number(result.meta?.last_page) || 1);', $js);
        $this->assertStringContainsString('...(result.data || []).map(notificationItem).filter((n) => !ids.has(n.id))', $js);

        // The bell's quiet refresh keeps the pages already opened; the unread count stays the server's.
        $this->assertStringContainsString('if (quiet && this.page > 1) {', $js);
        $this->assertStringContainsString('this.serverUnread = Number.isInteger(result.meta?.unread) ? result.meta.unread : null;', $js);

        // A bilingual Load more on the page, shown only while there is more.
        $this->assertStringContainsString('x-show="$store.citizenNotifications.hasMore"', $page);
        $this->assertStringContainsString('@click="$store.citizenNotifications.loadMore()"', $page);
        $this->assertStringContainsString('<x-ui.t fil="Mag-load pa" en="Load more" />', $page);
    }

    public function test_dokyu_lists_only_what_the_server_catalogue_offers(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');

        $this->assertStringContainsString('offered(doc) { return this.catalogLoaded && !this.catalogFailed && !!this.serviceMeta[doc?.key]; },', $page);
        $this->assertStringContainsString('.filter(d => d.barangay === this.myBarangay && this.offered(d)); },', $page);
        $this->assertStringContainsString('.filter(d => !d.barangay && this.offered(d)); },', $page);
        // The old rule offered everything until (or unless) the catalogue answered.
        $this->assertStringNotContainsString('!this.catalogLoaded || !Object.keys(this.serviceMeta).length', $page);
        // The list waits for the catalogue, and says so when it cannot load.
        $this->assertStringContainsString('x-show="catalogTab === \'barangay\' && catalogLoaded && !catalogFailed"', $page);
        $this->assertStringContainsString('x-show="catalogTab === \'municipal\' && catalogLoaded && !catalogFailed"', $page);
        $this->assertStringContainsString('en="Could not load the list of documents."', $page);
    }

    public function test_dokyu_draws_and_sends_table_questions_like_tulong(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');

        // Started empty, a row added at a time, drawn under its show-if rule.
        $this->assertStringContainsString("else if (f.type === 'table') { this.formData[f.key] = []; }", $page);
        $this->assertStringContainsString('addTableRow(f) {', $page);
        $this->assertMatchesRegularExpression('/x-for="f in \(docs\[selectedDoc\]\?\.fields \|\| \[\]\)\.filter\(f => f\.type === \'table\'\)" :key="f\.key">\s*<div class="mt-5" x-show="fieldShown\(f\)" data-dokyu-table-field>/', $page);
        $this->assertStringContainsString('@click="addTableRow(f)"', $page);

        // Required like any other question (an empty table is missing); hidden, neither required nor sent.
        $this->assertStringContainsString("if (!f.required || f.type === 'derived_age' || !this.fieldShown(f)) return false;", $page);
        $this->assertStringContainsString('for (const f of (doc?.fields || [])) if (!this.fieldShown(f)) delete formData[f.key];', $page);
        // The review step shows the rows.
        $this->assertStringContainsString('x-show="fieldShown(f) && (formData[f.key] || []).length"', $page);
    }

    public function test_tulong_counts_only_offered_programs_and_hides_a_table_whose_show_if_is_unmet(): void
    {
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringContainsString('isOffered(t) { return !this.offered || !t.key || this.offered.includes(t.key); },', $page);
        $this->assertStringContainsString('this.types.filter(t => this.isOffered(t)).forEach(t => { counts[t.category] = (counts[t.category] || 0) + 1; });', $page);
        $this->assertStringNotContainsString('this.types.forEach(t => { counts[t.category]', $page);

        $this->assertMatchesRegularExpression('/x-for="f in \(types\[selectedType\]\?\.fields \|\| \[\]\)\.filter\(f => f\.type === \'table\'\)" :key="f\.key">\s*<div class="mb-6" x-show="fieldShown\(f\)">/', $page);
        $this->assertStringContainsString('<div class="mt-3" x-show="fieldShown(f) && (formData[f.key] || []).length">', $page);
    }

    public function test_the_sakuna_pop_up_opens_the_emergency_page(): void
    {
        $popup = $this->source('resources/views/components/citizen/alert-popup.blade.php');

        $this->assertStringContainsString(":href=\"route('citizen.emergency')\"", $popup);
        $this->assertStringContainsString('<x-ui.t fil="Buksan ang Sakuna" en="Open Emergency" />', $popup);
        $this->assertStringNotContainsString("route('citizen.dashboard')", $popup);
        $this->assertStringNotContainsString('View on Home', $popup);
    }

    public function test_a_dashboard_balita_preview_opens_its_own_post(): void
    {
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');

        $this->assertStringContainsString('postId: post.id ?? null,', $dashboard);
        $this->assertStringContainsString(":href=\"'{{ route('citizen.announcements') }}' + (post.postId !== null ? '?post=' + encodeURIComponent(post.postId) : '')\"", $dashboard);
        $this->assertStringNotContainsString(":href=\"'{{ route('citizen.announcements') }}'\"", $dashboard);
        // The page it lands on opens ?post=ID.
        $this->assertStringContainsString("get('post')", $this->source('resources/js/scope-citizen.js'));
    }

    public function test_papeles_takes_only_jpg_png_webp_and_pdf(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');

        $this->assertStringContainsString('accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"', $page);
        $this->assertStringNotContainsString('accept="image/*,application/pdf" @change="upload(', $page);
        $this->assertStringContainsString("const okTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];", $page);
        $this->assertStringContainsString('if (!this.papelesTypeOk(file)) {', $page);
        $this->assertStringContainsString("lang('Only JPG, PNG, WebP or PDF files can be saved to your Papeles.', 'JPG, PNG, WebP o PDF lamang ang maaaring i-imbak sa iyong Papeles.')", $page);
        $this->assertStringContainsString('x-text="uploadError"', $page);
    }

    public function test_a_sent_resident_profile_is_shown_view_only(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');
        $form = $this->source('resources/views/components/citizen/resident-profile-form.blade.php');
        $js = $this->source('resources/js/resident-profile-form.js');

        // Offered once sent; the Unverified path is unchanged.
        $this->assertStringContainsString("x-show=\"profileVerification === 'For Validation' || profileVerification === 'Verified'\"", $profile);
        $this->assertStringContainsString("@click=\"\$dispatch('view-resident-profile', { view: residentProfileView, status: profileVerification })\"", $profile);
        $this->assertStringContainsString('<x-ui.t fil="Tingnan ang ipinadala mo" en="View what you sent" />', $profile);
        $this->assertMatchesRegularExpression("/x-show=\"profileVerification === 'Unverified'\" x-cloak\\s+@click=\"\\\$dispatch\\('open-resident-profile', residentProfileView\\)\"/", $profile);

        // The same form, every field disabled and greyed, no Send, the app's note.
        $this->assertStringContainsString('@view-resident-profile.window="openWith($event.detail?.view, $event.detail?.status)"', $form);
        $this->assertStringContainsString(':disabled="readOnly"', $form);
        $this->assertStringContainsString("readOnly && 'opacity-60", $form);
        $this->assertStringContainsString('<x-ui.button size="sm" icon="send" x-show="!readOnly"', $form);
        $this->assertStringContainsString('en="Your barangay office verified your information and your household\'s. To change something, ask your barangay office."', $form);
        $this->assertStringContainsString('en="Your barangay office is reviewing it. You will be told when it is done, and you can change it again if they send it back."', $form);

        // The app's rule: shown, not edited, while For Validation or once Verified.
        $this->assertStringContainsString("const LOCKED_STATUSES = ['For Validation', 'Verified'];", $js);
        $this->assertStringContainsString('this.readOnly = LOCKED_STATUSES.includes(lockedStatus);', $js);
        $this->assertStringContainsString('if (this.saving || this.readOnly) return;', $js);
    }

    public function test_a_shared_balita_post_can_open_in_the_phone_app(): void
    {
        // Android App Links: the app's manifest claims /citizen/announcements on
        // this site, and this file vouches for the app (2026-10-26).
        $links = json_decode($this->source('public/.well-known/assetlinks.json'), true);
        $this->assertSame('ph.gov.esperanza.esperanza_mobile', $links[0]['target']['package_name']);
        $this->assertSame(['delegate_permission/common.handle_all_urls'], $links[0]['relation']);
        $this->assertNotEmpty($links[0]['target']['sha256_cert_fingerprints']);
        $this->assertStringContainsString("copy(\$assetLinks, \$distDir.'/.well-known/assetlinks.json');", $this->source('export.php'));
    }
}
