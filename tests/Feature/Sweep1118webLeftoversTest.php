<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Screen sweep 18 Nov, the web leftovers: what the backend and app fixers
 * found belonged in this repo (receipting a refused payment, CSV formula
 * guards, the photo limit, list fields the API now sends, revoked event
 * certificates, Super Admin rows, the event registration failure, and the
 * rest). Source-reading, as the rest of this suite.
 */
class Sweep1118webLeftoversTest extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    /** #19: a refused payment still owes the fee, and nothing on the screen could receipt it. */
    public function test_a_refused_payment_can_still_be_receipted(): void
    {
        $page = $this->source('resources/views/admin/payments.blade.php');

        $this->assertStringContainsString("if (p.status === 'Pending Review') return true;", $page);
        $this->assertStringContainsString("return p.status === 'Rejected' && !['Rejected', 'Cancelled'].includes(p.request_status);", $page);
        $this->assertStringContainsString('<div x-show="canReceipt(payments[selected])" x-cloak>', $page);
        $this->assertStringContainsString('<template x-if="selected !== null && canReceipt(payments[selected]) && !showRejectReason">', $page);
        $this->assertStringContainsString('<template x-if="selected !== null && !(canReceipt(payments[selected]) && !showRejectReason)">', $page);
        // Only a payment awaiting review can be refused.
        $this->assertStringContainsString("<template x-if=\"payments[selected].status === 'Pending Review'\">\n                            <x-ui.button variant=\"danger\"", $page);
        $this->assertStringNotContainsString("<div x-show=\"payments[selected].status === 'Pending Review'\" x-cloak>", $page);
        // The reason goes to the citizen, and the placeholder says so.
        $this->assertStringContainsString('The citizen sees this reason. At least 5 characters.', $page);
        $this->assertStringContainsString("'Issue Receipt'", $page);
    }

    /** #238: Sakuna's and Reports' CSVs opened a cell typed as =HYPERLINK(...) as a formula. */
    public function test_sakuna_and_reports_csv_cells_are_guarded_against_formulas(): void
    {
        $guard = "const guard = (v, t) => typeof v !== 'number' && /^[=+\\-@\\t\\r]/.test(t) ? String.fromCharCode(39) + t : t;";
        foreach (['resources/views/admin/sakuna.blade.php', 'resources/views/admin/reports.blade.php'] as $path) {
            $src = $this->source($path);
            $this->assertStringContainsString($guard, $src, $path);
        }
        $this->assertStringContainsString('const cell = (v) => { const t = guard(v, text(v));', $this->source('resources/views/admin/sakuna.blade.php'));

        $reports = $this->source('resources/views/admin/reports.blade.php');
        $this->assertStringContainsString('const cell = (v) => { const t = guard(v, String(flat(v)));', $reports);
        // A list or an object is a readable cell, not JSON.
        $this->assertStringNotContainsString('JSON.stringify(v)', $reports);
        $this->assertStringContainsString("Array.isArray(v) ? v.map(flat).filter((x) => x !== '').join('; ')", $reports);
    }

    /** #241: the form promised 10 MB and sent the camera file; the server takes 5 MB. */
    public function test_an_emergency_photo_is_made_to_fit_the_servers_5_mb(): void
    {
        $js = $this->source('resources/js/scope-citizen.js');

        $this->assertStringContainsString('const MAX_PHOTO_BYTES = 5 * 1024 * 1024;', $js);
        $this->assertStringNotContainsString('const MAX_PHOTO_BYTES = 10 * 1024 * 1024;', $js);
        $this->assertStringContainsString('async function shrinkPhoto(file) {', $js);
        $this->assertStringContainsString("canvas.toBlob(resolve, 'image/jpeg', quality)", $js);
        $this->assertStringContainsString('const file = await shrinkPhoto(picked);', $js);
        $this->assertStringContainsString('async addPhotos(fileList) {', $js);
        $this->assertStringNotContainsString("'Each photo must be 10 MB or smaller.'", $js);
    }

    /** #20: members were resent with mobileOrEmpty(contact), wiping an email or landline on every save. */
    public function test_a_members_contact_is_sent_only_when_it_changed(): void
    {
        $js = $this->source('resources/js/resident-profile-form.js');

        $this->assertStringNotContainsString('contact: mobileOrEmpty(m.contact),', $js);
        $this->assertStringContainsString("_contact: m.contact || '',", $js);
        $this->assertStringContainsString("if (!m.ref || (m.contact || '') !== (m._contact || '')) return fields;", $js);
        $this->assertStringContainsString('...withoutUnchangedContact(m, person(m)),', $js);
    }

    /** #117: a failed read of the resident's registrations showed nothing, or "full" in place of their code. */
    public function test_the_event_card_says_when_registrations_did_not_load(): void
    {
        $view = $this->source('resources/views/components/citizen/event-signup.blade.php');
        $js = $this->source('resources/js/citizen-events.js');

        $this->assertStringContainsString('x-show="suState() !== \'none\' || suFailed()"', $view);
        $this->assertStringContainsString('<div x-show="suFailed()" role="status"', $view);
        $this->assertStringContainsString('loading="suRetrying()" @click="suRetry()"', $view);
        $this->assertStringContainsString('fil="Subukan Muli" en="Try Again"', $view);
        $this->assertStringContainsString('suRetrying() { return !!this.$store.eventRegs.loading && !this.$store.eventRegs.ready; },', $js);
        $this->assertStringContainsString('return signedIn() && !!(info.enabled ?? info.open) && this.$store.eventRegs.failed && !this.$store.eventRegs.ready;', $js);
    }

    /** #233: a [placeholder] in a reason reached residents word for word. */
    public function test_a_reason_with_a_placeholder_cannot_be_sent(): void
    {
        $dokyu = $this->source('resources/views/admin/document-requests.blade.php');
        $tulong = $this->source('resources/views/admin/assistance-requests.blade.php');
        // The standard reasons themselves carry none any more (Sweep1118web7Test).

        $this->assertStringContainsString("get rejectHasPlaceholder() { return /\\[[^\\]]+\\]/.test(this.rejectRemarks || ''); },", $dokyu);
        $this->assertStringContainsString('x-bind:disabled="processing || !rejectRemarks.trim() || rejectHasPlaceholder"', $dokyu);
        $this->assertStringContainsString("get sideReasonHasPlaceholder() { return /\\[[^\\]]+\\]/.test(this.sideReason || ''); },", $tulong);
        $this->assertStringContainsString('x-bind:disabled="processing || !sideReason.trim() || sideReasonHasPlaceholder"', $tulong);
    }

    /** #35/#30/#36: the list rows now carry a household's sitio and address and a resident's tags. */
    public function test_the_registry_reads_the_new_list_fields(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');

        $this->assertStringContainsString("(h.sitio||'').toLowerCase().includes(q)||(h.address||'').toLowerCase().includes(q)", $page);
        $this->assertStringContainsString("const words = { 'senior citizen': 'senior',", $page);
        $this->assertStringContainsString('!this.registryTags(r.tags).includes(this.tagFilter)', $page);
        $this->assertStringContainsString('<template x-for="tag in registryTags(r.tags)" :key="tag">', $this->source('resources/views/admin/constituents/view-individuals.blade.php'));
        $this->assertStringContainsString("[h.sitio ? sitioLabel(h.sitio) : '', h.address || ''].filter(Boolean).join(' · ') || '—'", $this->source('resources/views/admin/constituents/view-households.blade.php'));
    }

    /** #237: the Dokyu list's Needs Action disagreed with the dashboard's. */
    public function test_dokyu_needs_action_matches_the_dashboard(): void
    {
        $data = require base_path('resources/data/admin/document-requests.php');

        $this->assertSame(['Submitted', 'Under Verification', 'Resubmitted', 'Approved'], $data['actionStatuses']);
    }

    /** #66 and #14/#15/#225: the API refuses these; the screen no longer offers them. */
    public function test_personnel_follows_the_scope_and_super_admin_rules(): void
    {
        $page = $this->source('resources/views/admin/users.blade.php');

        $this->assertStringContainsString("return u?.roleKey !== 'super_admin' || this.\$store.session.account?.roleKey === 'super_admin';", $page);
        $this->assertStringContainsString('type="button" x-show="mayChangeAccount(u)"', $page);
        $this->assertStringContainsString("x-show=\"u.status !== 'Archived' && mayChangeAccount(u)\"", $page);
        $this->assertStringContainsString('if (!this.mayChangeAccount(u)) return;', $page);

        $this->assertStringContainsString('@click="pickRole(role.key)"', $page);
        $this->assertStringContainsString("if (key === 'barangay_secretary') {\n                            this.form.scope = 'barangay';", $page);
        $this->assertStringContainsString('@click="form.scope = \'municipal\'" :disabled="barangayOnlyRole"', $page);
        $this->assertStringContainsString("<option value=\"Municipal\" :disabled=\"edit.roleKey === 'barangay_secretary'\">", $page);
        $this->assertStringContainsString('if (this.editScopeProblem) { this.editError = this.editScopeProblem; return; }', $page);
    }

    /** A processing time with no number ('Per case plan') became a guessed date five days on. */
    public function test_expected_release_keeps_the_offices_words_when_there_is_no_number(): void
    {
        foreach (['assistance-requests', 'document-requests'] as $name) {
            $page = $this->source('resources/views/citizen/'.$name.'.blade.php');
            $this->assertStringContainsString("const days = String(r.days ?? '').trim();", $page, $name);
            $this->assertStringContainsString('if (!/\d/.test(days) && !/same day/i.test(days)) return days;', $page, $name);
        }
    }

    /** #157: a revoked event certificate still blocked unmarking and issuing again. */
    public function test_a_revoked_event_certificate_counts_as_none(): void
    {
        $js = $this->source('resources/js/event-desk.js');
        $view = $this->source('resources/views/admin/communications/event-desk.blade.php');

        $this->assertStringContainsString('const liveCert = (r) => !!(r && r.certificate && !r.certificate.revoked);', $js);
        $this->assertStringContainsString('edCanMark(r) { return this.edCanEdit() && this.edDayCome() && !cancelled(r) && !liveCert(r); },', $js);
        $this->assertStringContainsString('r._attended && !liveCert(r)).length; },', $js);
        $this->assertStringNotContainsString('&& !r.certificate;', $js);
        $this->assertStringNotContainsString('&& !r.certificate)', $js);
        $this->assertStringContainsString('x-show="edHasCert(r)" @click="edCert(r)"', $view);
    }

    /** #55: Show again republished comments their authors had removed. */
    public function test_show_again_is_only_for_comments_staff_hid(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');

        $this->assertStringContainsString('x-show="c.hidden && c.hidden_by" :disabled="bcBusy" @click="bcRestore(c)"', $page);
        $this->assertStringNotContainsString('x-show="c.hidden" :disabled="bcBusy" @click="bcRestore(c)"', $page);
    }

    /** #58: conflicts were toasted as classified, and account-only rows had no way to clear. */
    public function test_occupation_review_says_what_stays_in_conflict(): void
    {
        $tab = $this->source('resources/views/admin/constituents/tab-occupations.blade.php');

        $this->assertStringContainsString('const left = Number(res.data?.still_conflict || 0);', $tab);
        $this->assertStringContainsString("variant: left ? 'warning' : 'success'", $tab);
        $this->assertStringNotContainsString('your choice applies to the registry record and the account follows it', $tab);
        $this->assertStringContainsString('x-show="row.residents > 0 || row.accounts > 0" @click="review(row)"', $tab);
    }

    /** #34 and #254: the guardian hint, and the Art. 16 check's birth date. */
    public function test_guardian_hint_and_art16_birth_date(): void
    {
        $this->assertStringContainsString('<p x-show="gdWithheld && gdWas" x-cloak', $this->source('resources/views/admin/constituents/section-guardian.blade.php'));

        $tulong = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString('r.applicant_birth_date = d.applicant_birth_date || null;', $tulong);
        $this->assertStringContainsString('r.art16 = d.art16 || null;', $tulong);
    }

    /** Tulong had Dokyu's landline and guessed-number defects (#171/#172). */
    public function test_tulong_reads_the_directory_and_takes_a_landline(): void
    {
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringNotContainsString('pickupDirectory', $page);
        $this->assertStringNotContainsString('Municipal Hall Trunkline', $page);
        $this->assertStringContainsString("try { this.directory = (await \$api.get('/directory')).data || []; } catch { this.directory = null; }", $page);
        $this->assertStringContainsString("this.selectedRequest = r;\n                this.loadDirectory();", $page);
        $this->assertStringContainsString("route('citizen.directory')", $page);
        $this->assertStringContainsString("filter((f) => f.type === 'tel' && !this.isLandline(f))", $page);
        $this->assertStringContainsString(":maxlength=\"f.type === 'tel' ? (isLandline(f) ? 60 : 11) : null\"", $page);

        foreach (['assistance-requests', 'document-requests'] as $name) {
            $data = require base_path('resources/data/citizen/'.$name.'.php');
            $this->assertArrayNotHasKey('pickupDirectory', $data, $name);
        }
    }

    /** #126: closing a record import failed and left "Import paused" with no button. */
    public function test_a_failed_close_of_a_record_import_can_be_retried(): void
    {
        $js = $this->source('resources/js/request-import-ui.js');

        $this->assertStringContainsString('closeFailed: false,', $js);
        $this->assertStringContainsString("if (this.importRef && !this.summary) {\n                    this.running = true;\n                    try { await this.finish(); }", $js);
        $this->assertStringContainsString("if (this.stoppedAt === null) {\n                    this.closeFailed = true;\n                    this.stoppedAt = 0;\n                }", $js);
        $this->assertStringContainsString('stopped: this.stoppedAt !== null && !this.closeFailed,', $js);
        $this->assertStringNotContainsString('if (!batches.length) return;', $js);
    }

    /** The viewer opened the photo after the one tapped when an item before it had no address. */
    public function test_the_media_viewer_opens_the_item_tapped(): void
    {
        $js = $this->source('resources/js/balita-media.js');

        $this->assertStringContainsString('const target = (items || [])[index];', $js);
        $this->assertStringContainsString('const at = this.items.indexOf(target);', $js);
    }

    /** #120: a death or birth record's copy filled the deceased's or child's details from the requester. */
    public function test_a_record_about_another_person_is_not_filled_from_the_requester(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');

        $this->assertStringContainsString('aboutAnotherByKey: @js($aboutAnotherByKey),', $page);
        $this->assertStringContainsString("->filter(fn (\$d) => collect(\$d['fields'] ?? [])->contains(fn (\$f) => (\$f['prefill'] ?? true) === false))", $page);
        $this->assertStringContainsString('if (formKeys && (personKeys.includes(key) || formKeys.includes(key))) continue;', $page);

        // The two records the finding names are among them.
        $docs = collect(config('esperanza_municipal_documents.documents'))->keyBy('key');
        foreach (['mcro_death', 'mcro_live_birth'] as $key) {
            $this->assertTrue(collect($docs[$key]['fields'])->contains(fn ($f) => ($f['prefill'] ?? true) === false), $key);
        }
    }
}
