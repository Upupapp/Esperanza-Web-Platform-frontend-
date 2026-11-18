<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The resident screens from the 2026-11-18 screen sweep (res-911 at 1440 px
 * and 390 px): codes and dates cut over two lines, an empty Layunin column, a
 * phone row cut to "…", badges floating away from the photo, a top bar that
 * took a fifth of a phone screen, and a Resident Profile card that said
 * Unverified beside "Verified na Residente". Status chips keep the canonical
 * status names in both languages.
 */
class ResidentScreenSweepTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_request_lists_keep_codes_and_dates_on_one_line_and_drop_an_empty_purpose_column(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('<td class="px-4 py-3 font-mono text-xs text-ink-muted whitespace-nowrap" x-text="req.ref"></td>', $view, $page);
            $this->assertStringContainsString('<td class="px-4 py-3 text-ink-muted hidden sm:table-cell whitespace-nowrap" x-text="req.submitted"></td>', $view, $page);
            $this->assertSame(2, substr_count($view, 'x-show="filteredRequests.some(r => r.purpose)"'), $page.': the Layunin header and cell');
        }
        // Tulong read the purpose only when a case was opened.
        $tulong = $this->source('resources/views/citizen/assistance-requests.blade.php');
        $this->assertStringContainsString("purpose: r.purpose || '',", $tulong);
        $this->assertStringNotContainsString("purpose: '',", $tulong);
    }

    /**
     * The status names are a fixed vocabulary that is never renamed or
     * translated (CLAUDE.md; BilingualCoverageTest), so a chip shows the name
     * itself in both languages, even under Filipino tiles and tabs.
     */
    public function test_status_chips_show_the_canonical_status_names_untranslated(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            // The phone card and the table row.
            $this->assertSame(2, substr_count($view, '<span x-text="req.status"></span>'), $page);
            $this->assertStringNotContainsString('statusText(', $view, $page);
            $this->assertStringNotContainsString('statusWords', $view, $page);
        }
        $this->assertStringContainsString('x-text="selectedRequest.status"></span>', $this->source('resources/views/citizen/document-requests.blade.php'));

        // The status guide shows each name as its badge, with no translated word before the explanation.
        $this->assertStringNotContainsString('statusText(', $this->source('resources/views/components/citizen/status-guide.blade.php'));
    }

    public function test_the_profile_does_not_say_unverified_before_the_profile_is_sent(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');
        $this->assertStringContainsString("x-show=\"profileVerification !== 'Unverified' || profileReview?.outcome === 'returned'\" :class=\"profileVerification === 'Verified'", $profile);
        // The card says whose status it shows: the household record, not the account.
        $this->assertStringContainsString('data-resident-profile-scope><x-ui.t fil="Ang talaan mo at ng iyong sambahayan sa barangay. Hiwalay ito sa pag-verify ng iyong account."', $profile);
        // The camera and the check sit on the photo, not at the card's far edge.
        $this->assertStringContainsString('<div class="relative shrink-0 w-20">', $profile);
    }

    public function test_the_phone_barangay_row_and_top_bar_are_not_cut_or_tall(): void
    {
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');
        $this->assertStringContainsString('<p class="min-w-0 flex-1 text-[13px] text-[#dce5ff] max-sm:leading-snug sm:truncate">', $dashboard);
        $this->assertStringContainsString('<span class="max-sm:block"><x-ui.t fil="Barangay" en="Barangay" />', $dashboard);
        $this->assertStringContainsString('<span class="text-white/25 mx-1 max-sm:hidden">·</span>', $dashboard);
        $this->assertStringNotContainsString('max-sm:truncate', $dashboard);

        // On a phone: menu and icon buttons on one row, the title under them, no subtitle.
        $topbar = $this->source('resources/views/components/citizen/topbar.blade.php');
        $this->assertStringContainsString('<div class="flex-1 basis-44 flex items-center gap-3 min-w-0 max-sm:contents">', $topbar);
        $this->assertStringContainsString('<div class="min-w-0 max-sm:order-last max-sm:basis-full max-sm:[&>p]:hidden">', $topbar);
        $this->assertStringNotContainsString('max-sm:top-16', $topbar);
    }
}
