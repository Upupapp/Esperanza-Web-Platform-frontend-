<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Scheduled occasion greetings (2026-10-19): an official Balita post that
 * greets residents for a festival, a holiday or Foundation Day, in the feed, as
 * a pop-up when they open Esperanza, or both, each on its own schedule. These
 * pin the staff form's fields, the contract lists it reads, the Scheduled tab
 * (which is not a status), the resident's pop-up and the feed's chip. No
 * resident data: the pages read the API at run time.
 */
class OccasionGreetingsUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_contract_lists_come_from_config_not_the_view(): void
    {
        $config = config('esperanza_balita');
        $this->assertSame(['festival', 'holiday', 'foundation_day', 'anniversary', 'custom'], array_column($config['greeting_occasions'], 'key'));
        $this->assertSame(['once', 'daily', 'every_launch'], array_column($config['greeting_popup_frequencies'], 'key'));

        $view = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString("x-data=\"adminBalita(@js(config('esperanza.barangays')), @js(config('esperanza_balita.greeting_occasions')), @js(config('esperanza_balita.greeting_popup_frequencies')))\"", $view);
        $this->assertStringContainsString('<template x-for="o in occasions" :key="o.key">', $view);
        $this->assertStringContainsString('<template x-for="f in frequencies" :key="f.key">', $view);
        // Not hard-coded in the form: no occasion or frequency appears as an <option>.
        foreach (['Foundation Day', 'Once per resident', 'Every time Esperanza is opened'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'</option>', $view);
        }
    }

    public function test_the_staff_form_carries_every_greeting_field(): void
    {
        $view = $this->source('resources/views/admin/communications.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        // Post type uses the Audience picker's pressed option cards.
        $this->assertStringContainsString(":aria-pressed=\"form.post_type === 'greeting'\"", $view);
        $this->assertStringContainsString(":aria-pressed=\"form.post_type === 'regular'\"", $view);
        foreach ([
            'form.occasion', 'form.occasion_name', 'form.in_feed', 'form.as_popup',
            'form.publish_date', 'form.publish_time', 'form.until_date', 'form.until_time',
            'form.popup_start_date', 'form.popup_start_time', 'form.popup_end_date', 'form.popup_end_time',
            'form.popup_frequency', 'form.popup_button_label', 'form.popup_button_url',
        ] as $model) {
            $this->assertStringContainsString('x-model="'.$model.'"', $view, $model);
        }
        $this->assertStringContainsString('Times are Esperanza time (Asia/Manila)', $view);
        $this->assertStringContainsString('@click="previewOpen = true">Preview pop-up</x-ui.button>', $view);
        $this->assertStringContainsString("x-text=\"fieldError('popup_ends_at')\"", $view, 'the server\'s reason under the field');

        // Times go as Esperanza instants, and an edit sends the set when any of it changed.
        $this->assertStringContainsString("const manilaIso = (ymd, hm) => (ymd ? String(ymd).slice(0, 10) + 'T' + (hm || '00:00') + ':00+08:00' : null);", $js);
        $this->assertStringContainsString('...greetingPayload(this.form),', $js);
        $this->assertStringContainsString('if (JSON.stringify(greeting) !== JSON.stringify(greetingPayload(greetingForm(post)))) Object.assign(changes, greeting);', $js);
        foreach (['show_in_feed', 'publish_at', 'visible_until', 'popup_enabled', 'popup_starts_at', 'popup_ends_at', 'popup_frequency', 'popup_button_label', 'popup_button_url', 'occasion_name'] as $field) {
            $this->assertStringContainsString($field.':', $js, $field);
        }
        // The server's own words for a refused combination, mirrored, not invented.
        $this->assertStringContainsString("'Show the greeting in Balita, as a pop-up, or both.'", $js);
        $this->assertStringContainsString("'The pop-up must end after it starts.'", $js);
        $this->assertStringContainsString("'It must leave Balita after it is shown there.'", $js);
        $this->assertStringContainsString("'Name the occasion.'", $js);
    }

    public function test_scheduled_is_a_filter_beside_the_statuses_not_a_status(): void
    {
        $view = $this->source('resources/views/admin/communications.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString("statuses: ['Draft', 'Pending Review', 'Approved', 'Released', 'Rejected', 'Archived'],", $js);
        $this->assertStringNotContainsString("'Scheduled']", $js);
        $this->assertStringContainsString('&& (this.scheduledOnly ? this.isScheduled(a) : (this.status === \'All\' || a.status === this.status))', $js);
        $this->assertStringContainsString('@click="showScheduled()" :aria-pressed="scheduledOnly"', $view);
        $this->assertStringContainsString('x-text="scheduledCount"', $view);
        $this->assertStringContainsString("if (g.feed_state === 'scheduled' || g.popup_state === 'scheduled') return true;", $js);
    }

    public function test_a_greeting_card_shows_its_schedules_and_its_own_actions(): void
    {
        $view = $this->source('resources/views/admin/communications.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString("x-text=\"'Occasion greeting · ' + occasionLabel(a.greeting)\"", $view);
        $this->assertStringContainsString('x-text="feedSpan(a)"', $view);
        $this->assertStringContainsString("x-text=\"a.greeting.popup ? popupSpan(a) : ''\"", $view);
        // An approved greeting waiting for its time is not published by hand.
        $this->assertStringContainsString("x-show=\"a.status === 'Approved' && canMove(a, 'Released') && !waitsForFeed(a)\"", $view);
        $this->assertStringContainsString("x-text=\"'Goes live ' + ", $view);
        // Cancel = Archive, confirmed; a template opens New Balita and saves nothing.
        $this->assertStringContainsString('@click="cancelGreeting(a)"', $view);
        $this->assertStringContainsString("this.quick(post, 'Archived', 'Greeting cancelled. Residents will not see it.');", $js);
        $this->assertStringContainsString('@click="useAsTemplate(a)"', $view);
        $this->assertMatchesRegularExpression('/useAsTemplate\(post\) \{\s+this\.openNew\(\);/', $js);
        // Approving a greeting with a later time in Balita only approves it.
        $this->assertStringContainsString("'Approved. It goes into Balita on ' + when(at) + '.'", $js);
        $this->assertStringContainsString("if (err?.code === 'SCHEDULED_FOR_LATER') {", $js);
    }

    public function test_the_resident_pop_up_is_mounted_and_records_that_it_was_shown(): void
    {
        $layout = $this->source('resources/views/components/layouts/citizen.blade.php');
        $popup = $this->source('resources/views/components/citizen/greeting-popup.blade.php');

        $this->assertStringContainsString('<x-citizen.greeting-popup />', $layout);
        $this->assertGreaterThan(strpos($layout, '<x-citizen.alert-popup />'), strpos($layout, '<x-citizen.greeting-popup />'), 'after the other pop-ups');

        $this->assertStringContainsString("\$api.get('/citizen/greetings/active')", $popup);
        $this->assertStringContainsString("\$api.post('/citizen/greetings/' + encodeURIComponent(this.g.id) + '/seen')", $popup);
        // Once per browser session for a greeting, and never over an alert or another window.
        $this->assertStringContainsString("'esperanza_greeting_shown_' + id", $popup);
        $this->assertStringContainsString('if (n.pendingAlert || n.pendingWelcome) return true;', $popup);
        $this->assertStringContainsString('data-birthday-check="pending"', $this->source('resources/views/citizen/dashboard.blade.php'));
        // Escaped text, the button's link kept to https and opened apart from the portal.
        $this->assertStringContainsString('x-text="g.message"', $popup);
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $popup);
        $this->assertStringContainsString("this.balita + '?post=' + encodeURIComponent(this.g.post_id)", $popup);
        $this->assertStringContainsString('<x-ui.t fil="Salamat!" en="Thank you!" />', $popup);
        $this->assertStringNotContainsString('x-html', $popup);
    }

    public function test_the_feed_marks_a_greeting_with_a_pagbati_chip(): void
    {
        $feed = $this->source('resources/views/citizen/announcements.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        $this->assertStringContainsString("greeting: a.post_type === 'greeting' ? (a.greeting || {}) : null,", $js);
        $this->assertStringContainsString('<x-ui.t fil="Pagbati" en="Greeting" />', $feed);
        $this->assertStringContainsString('occasionLabel(post.greeting)', $feed);
        $this->assertStringContainsString('<x-ui.t fil="Pagbati" en="Greeting" />', $this->source('resources/views/citizen/dashboard.blade.php'));
    }
}
