<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Owner, 2026-11-19: a resident's past office records (the Dokyu and Tulong
 * an office imported) reach their account safely. The offices' records show
 * only once the account is verified, and the resident is told they wait; a
 * record the office's import kept by name only can be added to the resident
 * from their profile, after a confirm inside the page.
 */
class PastOfficeRecordsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_resident_reads_that_the_offices_records_wait_for_a_verified_account(): void
    {
        $card = $this->source('resources/views/components/citizen/records.blade.php');
        $js = $this->source('resources/js/citizen-records.js');

        $this->assertStringContainsString('<x-ui.t fil="Lalabas dito ang mga Dokyu at Tulong mo kapag na-verify na ng barangay ang account mo at na-confirm na sa iyo ang record." en="They appear here once your barangay verifies your account and confirms the record is yours." />', $card);
        $this->assertStringNotContainsString('nakumpirma ng barangay na iyo ang tala', $card, 'the old, deeper Filipino is gone');
        // The note shows whenever the server says records wait; what the
        // resident filed still lists below it.
        $this->assertStringContainsString('x-show="!loading && !failed && pending"', $card);
        $this->assertStringContainsString('<ul x-show="list.length" x-cloak', $card);
        $this->assertStringContainsString('this.pending = !!res.meta?.pending;', $js);
    }

    public function test_an_officer_adds_an_office_record_that_may_be_the_residents(): void
    {
        $modal = $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        // Under Dokyu & Tulong Received, only when there are some.
        $card = substr($modal, strpos($modal, '>Dokyu &amp; Tulong Received</h3>'));
        $this->assertStringContainsString('<div x-data="officeRecordMatches" x-show="omRows.length" x-cloak', $card);
        $this->assertStringContainsString(">Office records that may be this resident's</h4>", $modal);
        $this->assertStringContainsString('>The office\'s import kept these by name only. Add one only if it is this resident\'s.</p>', $modal);
        $this->assertStringContainsString('@click="omAsk = m.ref">Add to This Resident</button>', $modal);
        // Asked inside the page, never with the browser's confirm().
        $this->assertStringContainsString("x-text=\"'Add ' + m.ref + ' to ' + (selectedIndividual?.name || 'this resident') + '? It shows on this profile, and on their account once it is verified.'\"", $modal);
        $this->assertStringContainsString(':disabled="omBusy" @click="omLink(m)">Add</button>', $modal);
        $this->assertStringNotContainsString('confirm(', substr($js, strpos($js, "Alpine.data('officeRecordMatches'"), 3000));

        // Its logic is in the Constituents chunk, loaded per resident, quietly.
        $this->assertStringContainsString("Alpine.data('officeRecordMatches', () => ({", $js);
        $this->assertStringContainsString("this.\$watch('selectedIndividual?.ref', (ref) => this.omLoad(ref));", $js);
        $this->assertStringContainsString("api.get('/admin/residents/' + encodeURIComponent(ref) + '/office-records/possible', { silent: true })", $js);
        $this->assertStringContainsString("api.post('/admin/residents/' + encodeURIComponent(ref) + '/office-records/' + encodeURIComponent(m.ref) + '/link')", $js);
        // Linked: gone from the offer, and the card above shows it.
        $this->assertStringContainsString('if (this.residentRequests?.ref === ref) this.loadResidentRequests(ref);', $js);
        $this->assertStringContainsString("import { reasonText } from './resident-import-ui';", $js);
        $this->assertStringContainsString("(m.strength === 'strong' ? 'Strong match' : 'Possible match')", $js);
        // Not in the page's own x-data: the page is at its size budget.
        $this->assertStringNotContainsString('office-records', $this->source('resources/views/admin/constituents.blade.php'));
    }
}
