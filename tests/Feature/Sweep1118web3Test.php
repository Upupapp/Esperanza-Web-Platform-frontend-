<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QA sweep 2026-11-18, assignment web-3: a resident's guardian, the Events
 * form and card, the Feedback queue, the Tulong case notes, sessions and
 * Apply for New Tulong, and Site Content's Support Info.
 */
class Sweep1118web3Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function records(): string
    {
        return $this->source('resources/js/scope-admin-records.js');
    }

    private function welfare(): string
    {
        return $this->source('resources/js/welfare-admin.js');
    }

    /** The text of a method, from its opening line to the next line that starts one at the same depth. */
    private function method(string $src, string $opening): string
    {
        $start = strpos($src, $opening);
        $this->assertNotFalse($start, $opening);
        $end = strpos($src, "\n        },", $start);
        $this->assertNotFalse($end, $opening.' has an end');

        return substr($src, $start, $end - $start);
    }

    /** Changing only the relationship, without view_sensitive, sent contact and address as null. */
    public function test_a_guardian_saved_by_a_role_that_cannot_see_its_number_keeps_it(): void
    {
        $js = $this->records();
        $this->assertStringContainsString("get gdWithheld() { return !this.\$store.session.canPerm('const_data_quality', 'view_sensitive'); },", $js);
        $this->assertStringContainsString("this.gdWas = g ? { name: g.name || '', ref: g.individual_ref || null } : null;", $js);

        $save = $this->method($js, 'async gdSave() {');
        $this->assertStringContainsString('const keep = same && this.gdWithheld;', $save);
        // An empty field of the same guardian is left out, never sent as null.
        $this->assertStringContainsString('if (v) body[k] = v;', $save);
        $this->assertStringContainsString('else if (!keep) body[k] = null;', $save);
        $this->assertStringNotContainsString("contact: f.contact.trim() || null, address: f.address.trim() || null", $save);
    }

    /** A failed Submit for review after the create left the form open, and a retry made a second event. */
    public function test_an_event_made_but_not_sent_for_review_is_not_made_again(): void
    {
        $create = $this->method($this->records(), 'async create(submit) {');
        $made = strpos($create, 'created = true;');
        $sent = strpos($create, "'/transition', { to: 'Pending Review' }");
        $this->assertNotFalse($made);
        $this->assertNotFalse($sent);
        $this->assertLessThan($sent, $made, 'the event counts as made before it is sent for review');
        $this->assertStringContainsString('let created = false;', $create);
        $this->assertStringContainsString("if (created) {\n                    this.open = false;", $create);
        $this->assertStringContainsString("'Saved as a draft, but not sent for review. Use Submit for review on its card.'", $create);
    }

    /** The form read the effective closing time (the start, or 23:59) as one the office set. */
    public function test_the_event_form_reads_the_closing_time_the_office_set(): void
    {
        $js = $this->records();
        $this->assertStringContainsString("const own = !!e && 'registration_closes_at' in e;", $js);
        $this->assertStringContainsString('const set = own ? e.registration_closes_at : reg.closes_at;', $js);
        $this->assertStringContainsString("toHm(e.start_time) || toHm(e.time) || '23:59'", $js);
        $this->assertStringContainsString("closes_at: own || closes !== start ? closes : '',", $js);
        $this->assertStringNotContainsString('const at = reg.closes_at ? manilaParts(reg.closes_at) : null;', $js);
        $this->assertStringNotContainsString("(toHm(e.time) || '00:00')", $js);
    }

    /** Archive on an event card is final and sat beside Publish, with no question asked. */
    public function test_archiving_an_event_is_asked_first(): void
    {
        $archive = $this->method($this->records(), 'async archive(e) {');
        $asked = strpos($archive, "if (!window.confirm('Archive \"' + (e.title || e.name || 'this event') + '\"? An archived event cannot be edited or published again.')) return;");
        $sent = strpos($archive, "this.transition(e, 'Archived')");
        $this->assertNotFalse($asked);
        $this->assertNotFalse($sent);
        $this->assertLessThan($sent, $asked);
    }

    /** Save as draft and Submit for review did nothing, silently, without a title or a date. */
    public function test_an_event_without_a_title_or_date_says_so(): void
    {
        $save = $this->method($this->records(), 'async saveEvent(submit) {');
        $this->assertStringContainsString("if (!f.name.trim() || !f.date) { balitaToast(!f.name.trim() ? 'Give the event a title.' : 'Give the event a date.', 'error'); return; }", $save);
        $this->assertStringNotContainsString('if (this.processing || !f.name.trim() || !f.date) return;', $save);
    }

    /** A live reload during a filter change merged the new filter's tickets with the old one's pages. */
    public function test_the_feedback_queue_never_mixes_two_filters(): void
    {
        $js = $this->records();
        $this->assertStringContainsString("{ busy: () => this.sending || this.assigning || this.routing || this.loading });", $js);

        $load = $this->method($js, "async load(quiet = false) {\n            const seq = ++this.seq;");
        $this->assertStringContainsString("if (!quiet) {\n                this.loading = true;\n                this.failed = false;\n                // The pages Load more added were the old filter's.\n                this.page = 1;\n            }", $load);
        $this->assertStringContainsString('if (quiet && this.page > 1) {', $load);
    }

    /** `selected` stays set behind the closed case window, so reopening the same case kept old notes. */
    public function test_case_notes_are_read_again_each_time_the_case_opens(): void
    {
        $sync = $this->method($this->welfare(), 'cnSync() {');
        $closed = strpos($sync, 'if (this.open === false) { this.cnFor = null; return; }');
        $same = strpos($sync, 'if (ref === this.cnFor) return;');
        $this->assertNotFalse($closed);
        $this->assertNotFalse($same);
        $this->assertLessThan($same, $closed);
        $this->assertStringContainsString('x-data="caseNotes()" x-effect="cnSync()"', $this->source('resources/views/admin/tulong/case-notes.blade.php'));
    }

    /** The Sessions view and the session picker read only the first page of 25. */
    public function test_every_session_is_listed(): void
    {
        $js = $this->welfare();
        $this->assertStringContainsString("await api.getAll('/admin/tulong/sessions', { params });", $js);
        $this->assertStringContainsString("await api.getAll('/admin/tulong/sessions', { params: { status: 'Scheduled', service_key: this.selected.service_key } });", $js);
        $this->assertStringNotContainsString("api.get('/admin/tulong/sessions'", $js);
    }

    /** Apply for New Tulong offered only the page's own catalog, never a type made under Tulong types. */
    public function test_apply_offers_the_tulong_types_made_since(): void
    {
        $js = $this->welfare();
        $catalog = $this->method($js, 'apCatalog() {');
        $this->assertStringContainsString('const known = new Set(page.map((t) => t.key));', $catalog);
        $this->assertStringContainsString(".filter((sv) => sv && sv.key && !known.has(sv.key) && (!sv.type || sv.type === 'tulong'))", $catalog);
        $this->assertStringContainsString('.map(liveOnlyEntry);', $catalog);
        $this->assertStringContainsString('return [...page, ...added].map((t) => catalogEntry(t, this.apLive));', $catalog);
        $this->assertStringContainsString('function liveOnlyEntry(sv) {', $js);
        // Filed and released under its permission, the type it was made like.
        $this->assertStringContainsString("const typePerm = (t) => (t && (t.submodule || t.key)) || '';", $js);
        $this->assertStringContainsString('if (sv && sv.submodule_key) t.submodule = sv.submodule_key;', $js);
        $this->assertStringContainsString("return this.apCatalog().filter((t) => can(typePerm(t), 'create'));", $js);
        $this->assertStringContainsString("can(typePerm(this.apType || { key: this.apKey }), 'release')", $js);
        $this->assertStringNotContainsString("can(t.key, 'create')", $js);
        $this->assertStringNotContainsString("can(this.apKey, 'release')", $js);
    }

    /** A support channel's Filipino label could not be edited, and the publish sent no sections key for Support Info. */
    public function test_support_info_edits_the_filipino_label_and_sends_its_sections(): void
    {
        $view = $this->source('resources/views/admin/settings/site-content.blade.php');
        $this->assertStringContainsString('<input aria-label="Label (Filipino)" type="text" x-model="ch.label_fil"', $view);
        $this->assertStringContainsString('>Label (Filipino)</label>', $view);
        $this->assertStringContainsString("sections: doc.sections || [], channels: doc.channels || [],", $view);
        $this->assertStringNotContainsString('@error=', $view);
        $this->assertStringNotContainsString('<script', $view);
    }
}
