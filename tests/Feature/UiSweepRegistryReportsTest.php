<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UI sweep of staging, 2026-11-18 (every staff role at 1440 px and 390 px):
 * Constituents, Reports, Appointments and Feedback.
 */
class UiSweepRegistryReportsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /**
     * Every role (Super Admin too) read six 0s on the registry tiles while the
     * lists' pages were still loading. The tiles come from one-row pages asked
     * for first, and show a dash rather than a 0 until a count is known.
     */
    public function test_the_registry_tiles_never_show_a_0_that_is_not_known(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');

        $this->assertStringContainsString('this.loadTileCounts(may);', $page);
        $this->assertStringContainsString("meta('const_resident', '/admin/residents', { counts: 1 }),", $page);
        $this->assertStringContainsString('if (!this.loading && !this.failed) return listed;', $page);
        $this->assertStringContainsString("return Object.fromEntries(Object.keys(listed).map(k => [k, c[k] ?? '—']));", $page);
        // The Data Quality tab's count is the tile's, not the still-empty list's.
        $this->assertStringContainsString('bg-rose-100 text-rose-600" x-text="activeStats.issues"', $page);
        $this->assertStringNotContainsString('x-text="qualityIssues.length"', $page);
    }

    /** The office picker cut 'MSWDO · Municipal Social Welfare &' at 260 px. */
    public function test_the_report_pickers_show_the_whole_name(): void
    {
        $reports = $this->source('resources/views/admin/reports.blade.php');

        $this->assertStringNotContainsString('max-w-[260px]', $reports);
        $this->assertStringContainsString('<div x-show="catalog.offices.length > 1" class="flex flex-col gap-1 min-w-0 max-w-full">', $reports);
        // Past the row (a phone), the office ends in an ellipsis, not mid-word;
        // so does a long program name in its 240 px picker.
        $this->assertMatchesRegularExpression('/<select id="pr-office"[^\n]*max-w-full text-ellipsis">/', $reports);
        $this->assertMatchesRegularExpression('/<select id="pr-service"[^\n]*max-w-\[240px\] text-ellipsis">/', $reports);
    }

    /**
     * One schedule alone sat in half the row, beside an empty band. A card
     * alone on the last row (one schedule, or the third of three) takes the
     * whole row, and a card that wide sets its facts out across it.
     */
    public function test_a_lone_schedule_takes_the_whole_row(): void
    {
        $desk = $this->source('resources/views/components/admin/scope/appointments.blade.php');

        $this->assertStringContainsString('<div x-show="!sFailed && schedules.length" class="grid gap-4 items-start lg:grid-cols-2 lg:[&>article:last-of-type:nth-of-type(odd)]:col-span-2">', $desk);
        $this->assertStringContainsString('<article class="@container surface-glass rounded-[22px]', $desk);
        $this->assertStringContainsString('<dl class="grid grid-cols-2 @xl:grid-cols-3 @4xl:grid-cols-6 gap-x-4', $desk);
    }

    /** On Feedback the Super Administrator read who runs Balita. */
    public function test_the_balita_role_note_stays_on_the_publishing_tabs(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');

        $this->assertStringContainsString("x-show=\"roleNote && ['balita', 'banners', 'events'].includes(tab)\"", $page);
    }
}
