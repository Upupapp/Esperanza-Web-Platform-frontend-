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

    /**
     * The office picker cut 'MSWDO · Municipal Social Welfare &' at 260 px, then
     * grew to the whole name and took a row of its own. The owner (2026-11-18):
     * "it can be compacted more ... after picking it you can just have '...' as
     * long as in the pick list the user can see the whole word". The office and
     * what it is narrowed to share one wrapping row; the long-name pickers have a
     * set width, the chosen words end in "…", and the list shows every name.
     */
    public function test_the_report_pickers_are_compact_and_list_every_name_in_full(): void
    {
        $reports = $this->source('resources/views/admin/reports.blade.php');

        $this->assertStringNotContainsString('max-w-[260px]', $reports);
        // Every picker on the card is the compact one; no bare <select> is left.
        $this->assertStringNotContainsString('<select id="pr-', $reports);
        foreach (['pr-office', 'pr-barangay', 'pr-service', 'pr-sex', 'pr-age', 'pr-sector'] as $id) {
            $this->assertSame(1, substr_count($reports, '<x-ui.compact-select id="'.$id.'"'), $id);
        }

        // The office: 240 px from a tablet up, the row on a phone; its words are
        // the full name (cut with "…"), and so is every option in its list.
        $eyebrow = 'label-class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"';
        $this->assertStringContainsString('<x-ui.compact-select id="pr-office" label="Office" '.$eyebrow.' x-show="catalog.offices.length > 1" class="w-full sm:w-60" model="office" change="service = \'\'" shown="officeEntry ? (officeEntry.short ? officeEntry.short + \' · \' : \'\') + officeEntry.name : \'\'">', $reports);
        $this->assertStringContainsString('<option :value="o.name" x-text="(o.short ? o.short + \' · \' : \'\') + o.name" :selected="o.name === office"></option>', $reports);
        $this->assertStringContainsString('<x-ui.compact-select id="pr-barangay" label="Barangay" '.$eyebrow.' x-show="catalog.barangays.length > 1" class="w-full sm:w-60" model="barangay"', $reports);

        // Narrowed to: on the office's row from a tablet up; on a phone the
        // program takes the row and sex, age and sector go two to a row.
        $this->assertStringContainsString('<div class="flex flex-wrap items-end gap-x-4 gap-y-2 mb-3 pb-3 border-b border-brand-600/10">', $reports);
        $this->assertStringContainsString('<div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">', $reports);
        $this->assertMatchesRegularExpression('/<x-ui.compact-select id="pr-service" label="Program or document" model="service" x-show="services.length > 1" class="col-span-2 sm:w-60" shown="[^"]*\'All programs and documents\'">/', $reports);
        foreach (['sex' => ['Sex', 'Any sex'], 'age' => ['Age group', 'Any age'], 'sector' => ['Sector', 'Any sector']] as $k => [$name, $none]) {
            $this->assertStringContainsString('<x-ui.compact-select id="pr-'.$k.'" label="'.$name.'" model="'.$k.'" shown="'.$k.' ? ('.$k.'Options.find((o) => o.value === '.$k.')?.label || '.$k.') : \''.$none.'\'">', $reports);
        }
        // Less room between the period and the pickers.
        $this->assertStringContainsString('<div class="flex flex-wrap items-end gap-x-3 gap-y-1 mb-4">', $reports);

        // The picker: the select stays the control (keyboard, screen reader, the
        // native list) with its own words transparent; a span on top truncates
        // with "…" (Chrome and Edge draw no ellipsis in a <select>), and the
        // whole choice is the tooltip. Forced colours get the native select back.
        // Every picker is named: the label is not optional.
        $picker = $this->source('resources/views/components/ui/compact-select.blade.php');
        $this->assertStringContainsString("@props(['id', 'label', 'model', 'shown', 'change' => null, 'labelClass' => 'sr-only'])", $picker);
        $this->assertStringContainsString('<label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>', $picker);
        $this->assertStringContainsString('x-bind:title="{{ $shown }}"', $picker);
        $this->assertStringContainsString('<select id="{{ $id }}" x-model="{{ $model }}" @if ($change) x-on:change="{{ $change }}" @endif', $picker);
        $this->assertStringContainsString('appearance-none', $picker);
        $this->assertStringContainsString('text-transparent', $picker);
        $this->assertStringContainsString('[&>option]:text-ink-body', $picker);
        $this->assertStringContainsString('focus:ring-4 focus:ring-brand-100', $picker);
        $this->assertStringContainsString('forced-colors:appearance-auto forced-colors:text-[CanvasText]', $picker);
        $this->assertStringContainsString('<span aria-hidden="true" class="pointer-events-none absolute inset-0', $picker);
        $this->assertStringContainsString('<span class="min-w-0 flex-1 truncate" x-text="{{ $shown }}"></span>', $picker);
        $this->assertStringNotContainsString('@error=', $picker);
        $this->assertStringNotContainsString('<script', $picker);
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
