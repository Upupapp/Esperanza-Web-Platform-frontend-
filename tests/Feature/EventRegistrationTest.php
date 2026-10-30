<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Registering for events, attendance and e-certificates (Esperanza round 2,
 * 2026-10-30; owner: "the e-certifications also should be in the Event screen
 * ... have also buttons for users to register to the posted events so that
 * the municipality will have record of those who attended"). The server keeps
 * the registrations and issues the certificates; these pin what the website
 * shows of it, so the event screen cannot quietly lose its Register button,
 * its check-in, or its certificates.
 */
class EventRegistrationTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_event_form_sets_registration_and_the_certificate_of_those_who_attend(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $form = $this->source('resources/views/admin/communications/event-form-extras.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString("@include('admin.communications.event-form-extras')", $page);
        // Registration: who may, how many, until when (empty: the event's start).
        $this->assertStringContainsString('Residents can register', $form);
        $this->assertStringContainsString('x-model="form.registration_open"', $form);
        $this->assertStringContainsString('x-model="form.capacity"', $form);
        $this->assertStringContainsString('type="datetime-local" x-model="form.closes_at"', $form);
        $this->assertStringContainsString('Empty: until the event starts', $js);
        // The certificate: a preset or the office's own title, the wording
        // with its placeholders, and who signs. A prefilled select marks its
        // option, and each signatory row is keyed by itself, not its index.
        $this->assertStringContainsString('Give certificates to those who attend', $form);
        $this->assertStringContainsString(':selected="form.cert.preset === p.key"', $form);
        $this->assertStringContainsString('<option value="custom" :selected="form.cert.preset === \'custom\'">', $form);
        $this->assertStringContainsString(':name, :event, :date and :venue are filled in for each person.', $form);
        $this->assertStringContainsString('x-for="(s, si) in form.cert.signatories" :key="s.k"', $form);
        foreach (['Certificate of Participation', 'Certificate of Attendance', 'Certificate of Completion'] as $title) {
            $this->assertStringContainsString("title: '{$title}'", $js);
        }
        // What the API takes, on create and as a change on edit.
        $this->assertStringContainsString('registration_open: !!f.registration_open,', $js);
        $this->assertStringContainsString('registration_closes_at: f.closes_at ? manilaIso(', $js);
        $this->assertStringContainsString('certificate: c.enabled || c.had ? {', $js);
        $this->assertStringContainsString('...extrasPayload(f),', $js);
        $this->assertStringContainsString('const was = extrasPayload(eventExtras(e));', $js);
        $this->assertStringContainsString('const problem = this.extrasProblem();', $js);
    }

    public function test_the_registrations_window_checks_people_in_and_issues_certificates_on_the_page(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $desk = $this->source('resources/views/admin/communications/event-desk.blade.php');
        $js = $this->source('resources/js/event-desk.js');
        $records = $this->source('resources/js/scope-admin-records.js');

        // Opened from a published event's card, by a window event: a page
        // method that set `open` from inside the window would set the
        // window's own. Edit comes back the same way.
        $this->assertStringContainsString('x-show="canDesk(e)" @click="openDesk(e)"', $page);
        $this->assertStringContainsString("openDesk(e) { window.dispatchEvent(new CustomEvent('event-desk', { detail: e })); }", $records);
        $this->assertStringContainsString('x-on:events-edit.window="editByKey($event.detail)"', $page);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('events-edit', { detail: key }));", $js);
        $this->assertStringContainsString("@include('admin.communications.event-desk')", $page);
        $this->assertStringContainsString('<div x-data="eventDesk()" x-on:event-desk.window="edShow($event.detail)">', $desk);
        $this->assertStringContainsString("area(/^\\/admin\\/(communications|announcements|settings\\/support)(\\/|$)/.test(areaPath), () => import('./event-desk'));", $this->source('resources/js/app.js'));

        // Counts, check-in by code (with or without the dash, any case), walk-ins, the list.
        $this->assertStringContainsString('Check in by code', $desk);
        $this->assertStringContainsString('<form x-show="edCanEdit()" @submit.prevent="edCheckIn()"', $desk);
        $this->assertStringContainsString("return String(text || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);", $js);
        $this->assertStringContainsString("'/check-in', { code }", $js);
        $this->assertStringContainsString('Add attendee', $desk);
        $this->assertStringContainsString("api.get('/admin/people/search', { params: { q }, silent: true })", $js);
        $this->assertStringContainsString("'/registrations', body)", $js);
        $this->assertStringContainsString('x-model="r._attended"', $desk);
        $this->assertStringContainsString("'/attendance', {", $js);
        $this->assertStringContainsString('Download list (CSV)', $desk);
        $this->assertStringContainsString("new Blob([text], { type: 'text/csv;charset=utf-8' })", $js);

        // Certificates: an officer who approves events, from the event's day,
        // confirmed on the page - never the browser's confirm().
        $this->assertStringContainsString("can('approve')", $js);
        $this->assertStringContainsString("x-show=\"edPanel === 'issue'\"", $desk);
        $this->assertStringContainsString("'/certificates')", $js);
        $this->assertStringContainsString('Certificates can be issued from the day of the event, ', $js);
        $this->assertStringNotContainsString('window.confirm', $js);
        $this->assertDoesNotMatchRegularExpression('/\bconfirm\(\s*[\'"`]/', $js);
        // A certificate opens in the shared certificate window, placed after
        // the window that opens it.
        $this->assertStringContainsString('openWelfareCertificate(doc, {', $js);
        $this->assertLessThan(
            strpos($page, '<x-welfare.certificate />'),
            strpos($page, "@include('admin.communications.event-desk')"),
        );
    }
}
