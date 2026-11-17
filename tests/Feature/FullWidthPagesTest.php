<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * No empty band on the right (owner, 2026-11-17, on Mga Abiso: "i do not want
 * that empty spaces on the right side ... fix also similar screens all
 * throughout the system"). A page's content spans the width beside the menu;
 * reading text that would run too long is set in columns instead of being
 * held to a narrow strip. Found by measuring every page and tab at 1440 px.
 */
class FullWidthPagesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_pages_are_not_held_to_a_narrow_strip(): void
    {
        $this->assertStringNotContainsString('animate-fade-up max-w-3xl', $this->source('resources/views/citizen/notifications.blade.php'));

        $legal = $this->source('resources/views/citizen/legal.blade.php');
        $this->assertStringNotContainsString('animate-fade-up max-w-3xl', $legal);
        $this->assertStringContainsString('<div class="grid gap-x-10 gap-y-5 mt-5 xl:grid-cols-2">', $legal);

        $help = $this->source('resources/views/citizen/help.blade.php');
        $this->assertStringContainsString('<div x-show="tab === \'faqs\'">', $help);
        $this->assertStringContainsString('<div class="grid gap-2.5 lg:grid-cols-2 items-start" x-show="!loading && !faqsFailed"', $help);

        // The Registrations desk opens full screen: its content uses the room.
        $this->assertStringNotContainsString('max-w-4xl mx-auto', $this->source('resources/views/admin/communications/event-desk.blade.php'));
    }

    /** 2026-11-17: a banner's photo shows; the colour stays behind the title only. */
    public function test_banner_photos_show_through_the_overlay(): void
    {
        $this->assertStringContainsString('.banner-photo-fade {', $this->source('resources/css/app.css'));
        $this->assertStringContainsString("(b.image_url ? ' opacity-85 banner-photo-fade' : '')", $this->source('resources/views/components/citizen/banner-carousel.blade.php'));
        $this->assertStringContainsString("(image.url ? ' opacity-85 banner-photo-fade' : '')", $this->source('resources/views/admin/communications/banners.blade.php'));
    }

    /** 2026-11-17: beside the clock, search and profile most subtitles were cut off. */
    public function test_header_subtitles_wrap_instead_of_being_cut(): void
    {
        $admin = $this->source('resources/views/components/admin/topbar.blade.php');
        $this->assertStringContainsString('<p data-page-subtitle class="text-[13.5px] leading-snug text-ink-muted line-clamp-2">', $admin);
        $this->assertStringNotContainsString('text-ink-muted truncate">{{ $subtitle }}', $admin);
        $this->assertStringContainsString('<p class="text-[13.5px] leading-snug text-ink-muted line-clamp-2" x-text=', $this->source('resources/views/components/citizen/topbar.blade.php'));
    }

    /** 2026-11-17: scrolled down a long list of events, the left column was an empty band. */
    public function test_the_events_calendar_stays_in_view(): void
    {
        $this->assertStringContainsString('<div class="space-y-5 min-w-0 lg:sticky lg:top-0">', $this->source('resources/views/citizen/events.blade.php'));
    }

    public function test_the_profile_lists_span_the_page_under_the_two_columns(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');
        $grid = strpos($profile, '<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">');
        $records = strpos($profile, '<x-citizen.records />');
        $modal = strpos($profile, '<x-ui.modal title="I-verify ang Valid ID"');
        $this->assertNotFalse($grid);
        $this->assertNotFalse($records);
        // After the two-column grid has closed, before the page's dialogs.
        $this->assertGreaterThan($grid, $records);
        $this->assertLessThan($modal, $records);
        $this->assertStringContainsString("<div class=\"mt-5 space-y-5\">\n            {{-- The Dokyu and Tulong on their record", $profile);
    }
}
