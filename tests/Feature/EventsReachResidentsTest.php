<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Events that reach residents (owner, 2026-10-21: "will the citizens be
 * notified of this? can this be linked to a balita post?"). The server tells
 * residents and posts the event in Balita; these pin what the website shows
 * of it, so it cannot quietly go back to a calendar nobody hears about.
 */
class EventsReachResidentsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_staff_form_writes_what_residents_read_and_says_who_is_told(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString('x-data="adminEvents(', $page);
        $this->assertStringContainsString("Alpine.data('adminEvents', adminEvents)", $js);
        // A description, photos, and "Post in Balita too".
        $this->assertStringContainsString('x-model="form.description"', $page);
        $this->assertStringContainsString('<x-balita.media-field base="/admin/balita" model="media" existing="(editing ? (editing.media || []) : [])"', $page);
        $this->assertStringContainsString('Post in Balita too', $page);
        $this->assertStringContainsString('post_to_balita: !!f.post_to_balita, media_ids: this.media.ids', $js);
        $this->assertStringNotContainsString("Posters aren't part of this pass", $page);
        // Approving says who is told; a sent-back event shows its remarks.
        $this->assertStringContainsString('in their bell and on their phones.', $page);
        $this->assertStringContainsString('e.review_remarks', $page);
        // A published event is put right by one who approves events; withdrawn
        // before its day it is a cancellation, with a reason residents read.
        $this->assertStringContainsString("(e.status === 'Released' && this.can('approve'))", $js);
        $this->assertStringContainsString('Cancel event', $page);
        $this->assertStringContainsString('Reason residents will read', $page);
        $this->assertStringContainsString("this.transition(this.selected, 'Archived', this.cancelReason.trim())", $js);
        // The reviewer's notification opens the event.
        $this->assertStringContainsString("params.get('tab') !== 'events' || !params.get('event')", $js);
    }

    public function test_residents_set_a_reminder_and_open_an_event_from_a_notification(): void
    {
        $page = $this->source('resources/views/citizen/events.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        $this->assertStringContainsString("api.get('/citizen/event-reminders')", $js);
        $this->assertStringContainsString("'/citizen/events/' + encodeURIComponent(e.ref) + '/reminder'", $js);
        $this->assertStringContainsString('toggleReminder(event)', $page);
        $this->assertStringContainsString("new URLSearchParams(window.location.search).get('event')", $page);
        $this->assertStringContainsString("\$api.get('/events/' + encodeURIComponent(key))", $page);
        // What it is about, its poster, and its Balita post.
        $this->assertStringContainsString('x-text="event.description"', $page);
        $this->assertStringContainsString(':src="event.image_url"', $page);
        $this->assertStringContainsString('event.balita_post_id', $page);
    }

    public function test_balita_and_the_dashboard_open_the_event_and_show_only_what_is_to_come(): void
    {
        $balita = $this->source('resources/views/citizen/announcements.blade.php');
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');

        $this->assertStringContainsString(':href="post.event?.link"', $balita);
        $this->assertStringContainsString('<a :href="ev.link"', $balita);
        $this->assertStringContainsString('event: a.event || null', $this->source('resources/js/scope-citizen.js'));
        // Since Sprint 4 (2026-11-06) also upcoming=1: only events not yet ended.
        $this->assertStringContainsString("\$api.get('/events', { params: { from, upcoming: 1, per_page: 3 } })", $dashboard);
        $this->assertStringNotContainsString("\$api.get('/events', { params: { month } })", $dashboard);
    }
}
