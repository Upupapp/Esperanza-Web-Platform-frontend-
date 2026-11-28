<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Citizens bug hunt, 25 Nov 2026 - the website's side. The backend's side
 * has its own tests (AccountRecordSyncTest, CitizensBugHuntTest).
 */
class CitizensBugHuntWebTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** #5, #27, #28 */
    public function test_to_verify_shows_a_new_id_counts_it_and_keeps_archived_accounts_apart(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $this->assertStringContainsString("if (a.status === 'Approved' && a.identity_status !== 'Verified' && a.id_status === 'Pending Review') return 'New ID Sent';", $page);
        $this->assertStringContainsString("pending: n('Pending Review') + n('New ID Sent'),", $page);
        $this->assertStringContainsString('accounts: this.citizenAccountList.filter(i => this.cvNeedsReview(i)).length,', $page);
        $this->assertStringContainsString("if (!this.cvStatusFilter && status === 'Archived') return false;", $page);
        $this->assertStringNotContainsString("(i.citizen_account?.email || '').toLowerCase().includes(q)", $page);

        $tab = $this->source('resources/views/admin/constituents/tab-citizen-verification.blade.php');
        $this->assertStringContainsString("'Pending Review', 'New ID Sent', 'Under Verification', 'Approved', 'Rejected', 'Not Registered', 'Archived'", $tab);
        $this->assertStringContainsString('(cvNeedsReview(i) || i.citizen_account.photo_pending)', $tab);
        $this->assertStringNotContainsString('barangay, or email…', $tab);
    }

    /** #6, #24 */
    public function test_the_resident_reads_what_their_status_and_identity_mean(): void
    {
        $notice = $this->source('resources/views/components/citizen/approval-notice.blade.php');
        $this->assertStringContainsString("if (this.a.status === 'Approved' && this.a.identityStatus === 'Revoked') return 'idRevoked';", $notice);
        $this->assertStringContainsString("if (this.a.status === 'Rejected') return 'rejected';", $notice);
        $this->assertStringContainsString('Send a new valid ID from your profile', $notice);

        $profile = $this->source('resources/views/citizen/profile.blade.php');
        $this->assertStringContainsString("'Approved · requests paused'", $profile);
        $this->assertStringContainsString('x-show="$store.citizenSession.account?.residentSince"', $profile);
    }

    /** #7, #29 */
    public function test_the_edit_form_ticks_a_resident_s_own_tags_and_the_lists_agree(): void
    {
        $detail = $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php');
        $this->assertStringContainsString('tags: registryTags(i.tags),', $detail);
        $this->assertStringContainsString('tags: (this.edit.tags || []).map(k => this.tagWords?.[k] || k)', $detail);

        $resident = $this->source('resources/js/resident-profile-form.js');
        $this->assertStringContainsString("civilStatus: ['Single', 'Married', 'Live-in', 'Widowed', 'Separated', 'Annulled', 'Divorced'],", $resident);
        foreach (['modal-individual-detail', 'modal-add-individual'] as $staff) {
            $view = $this->source("resources/views/admin/constituents/{$staff}.blade.php");
            foreach (['Single', 'Married', 'Live-in', 'Widowed', 'Separated', 'Annulled', 'Divorced'] as $status) {
                $this->assertStringContainsString('<option value="'.$status.'">'.$status.'</option>', $view, $staff);
            }
        }
        $this->assertStringContainsString('personal.education && !options.education.includes(personal.education)', $this->source('resources/views/components/citizen/resident-profile-form.blade.php'));
    }

    /** #8 */
    public function test_a_saved_family_member_is_not_offered_a_remove_that_does_nothing(): void
    {
        $form = $this->source('resources/views/components/citizen/resident-profile-form.blade.php');
        $this->assertStringContainsString('x-show="!readOnly && !m.ref" @click="removeMember(i)"', $form);
        $this->assertStringContainsString('removeMember(i) { if (!this.readOnly && !this.members[i]?.ref) this.members.splice(i, 1); },', $this->source('resources/js/resident-profile-form.js'));
    }

    /** #13, #14, #20 */
    public function test_the_registry_opens_a_to_z_assigns_without_personnel_and_follows_live_changes(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $this->assertStringContainsString("\$api.getAll('/admin/residents', { params: { sort: 'name', direction: 'asc' } })", $page);
        $this->assertStringContainsString("\$api.getAll('/admin/households', { params: { sort: 'label', direction: 'asc' } })", $page);
        $this->assertStringContainsString("\$api.get('/admin/data-quality/assignees')", $page);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'residents', () => this.refreshAccounts()", $page);
        $this->assertSame(2, substr_count($page, 'this.keepAccount(i.citizen_account);'), 'verify and reject both keep the list copy');
    }

    /** #15 */
    public function test_the_merge_keeps_the_original_by_default_and_names_the_duplicate(): void
    {
        $merge = $this->source('resources/views/admin/constituents/modal-merge.blade.php');
        $this->assertStringContainsString("keep: 'B',", $merge);
        $this->assertStringContainsString('Record A — Suspected Duplicate', $merge);
        $this->assertStringContainsString('Record B — Original (older)', $merge);
        $this->assertStringContainsString('Original Record (older)', $this->source('resources/views/admin/constituents/modal-dq-detail.blade.php'));
    }

    /** #18, #22 */
    public function test_correct_details_and_the_account_window_read_plainly(): void
    {
        $js = $this->source('resources/js/scope-admin-records.js');
        $this->assertStringContainsString('(had.birthdate && !f.birthdate) || (had.sex && !f.sex)', $js);
        $this->assertStringContainsString('birthdate: f.birthdate || null,', $js);
        $this->assertStringContainsString('window.cvWords = (code) =>', $js);

        $modal = $this->source('resources/views/admin/constituents/modal-citizen-account.blade.php');
        $this->assertStringNotContainsString('It is retried automatically.', $modal);
        $this->assertStringContainsString('(c.reasons || []).map(r => window.cvWords(r))', $modal);
        $this->assertStringContainsString('window.cvWords(h.method)', $modal);
        $this->assertStringContainsString('window.cvWords(h.action)', $modal);
    }

    /** #1, #2 */
    public function test_the_lifecycle_window_says_what_happens_to_the_account(): void
    {
        $modal = $this->source('resources/views/admin/constituents/modal-lifecycle.blade.php');
        $this->assertStringContainsString('x-show="selectedIndividual?.citizen_account"', $modal);
        $this->assertStringContainsString('it is closed with the record, and their Resident ID stops being valid.', $modal);
    }

    /** #25, #26, #31 */
    public function test_the_import_and_export_read_plainly(): void
    {
        $import = $this->source('resources/js/resident-import.js');
        $this->assertStringContainsString("'lalaki', 'lalake'", $import);
        $this->assertStringContainsString("'babae', 'babai'", $import);
        $this->assertStringContainsString('Unchanged or left out', $this->source('resources/views/lazy/constituents-import.blade.php'));
        $this->assertStringContainsString("dob:'Birth Date'", $this->source('resources/views/admin/constituents.blade.php'));
    }
}
