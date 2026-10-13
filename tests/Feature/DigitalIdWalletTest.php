<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Unified Digital ID Wallet (owner decisions, 2026-10-13): the Profile's
 * Digital ID reads GET /citizen/wallet, as the mobile app's Digital ID screen
 * does, and the officer can revoke a residency approval apart from the
 * identity. These pin what each page sends and claims.
 */
class DigitalIdWalletTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_profile_opens_the_wallet_for_every_resident(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');

        $this->assertStringContainsString('<x-citizen.digital-id-wallet />', $profile);
        // Open to everyone: the Resident ID inside it is what waits for approval.
        $this->assertStringNotContainsString('x-show="$store.citizenSession.account?.fullServiceAccess" x-cloak
                    @click="showDigitalId = true"', $profile);
    }

    public function test_the_wallet_reads_one_endpoint_and_shows_the_card_only_while_active(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');
        $card = $this->source('resources/views/components/citizen/digital-id-card.blade.php');

        $this->assertStringContainsString("\$api.get('/citizen/wallet')", $wallet);
        $this->assertStringContainsString("return this.wallet?.resident_id?.state === 'active' ? this.wallet.resident_id.card : null;", $wallet);
        $this->assertStringContainsString("'Resident ID suspended'", $wallet);
        $this->assertStringContainsString("'Resident ID not issued yet'", $wallet);
        // The card no longer fetches a card of its own, valid or not.
        $this->assertStringNotContainsString('/citizen/digital-id', $card);
    }

    public function test_identity_residency_and_the_resident_id_are_shown_apart(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');

        $this->assertStringContainsString('wallet.identity.status', $wallet);
        $this->assertStringContainsString('<x-ui.badge status-bind="wallet.residency.status" />', $wallet);
        $this->assertStringContainsString('wallet.resident_id.state', $wallet);
        $this->assertStringContainsString('wallet.residency.approval_revoked', $wallet);
    }

    public function test_a_kept_id_claims_no_check_it_did_not_have(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');

        $this->assertStringContainsString("'Uploaded — Not checked'", $wallet);
        $this->assertStringContainsString('en="Not checked"', $wallet);
        $this->assertStringContainsString('en="Not available"', $wallet);
        $this->assertStringContainsString('Esperanza is not connected to the agency that issued this ID.', $wallet);
        // A returned ID shows the officer's reason; one the LGU verified carries the seal.
        $this->assertStringContainsString('doc.lgu_remarks', $wallet);
        $this->assertStringContainsString("verified_by_lgu: [this.t('Verified by the LGU', 'Beripikado ng LGU')", $wallet);
        $this->assertStringContainsString("x-show=\"d.document_status === 'verified_by_lgu'\"", $wallet);
        $this->assertStringContainsString('IDs verified by the LGU have the Esperanza seal on them.', $wallet);
    }

    public function test_an_officer_sees_the_whole_wallet_id_and_can_return_it(): void
    {
        $papeles = $this->source('resources/views/admin/papeles.blade.php');

        // Both sides, and what kind of ID it is.
        $this->assertStringContainsString("'/view' + (side === 'back' ? '?side=back' : '')", $papeles);
        $this->assertStringContainsString('previewDoc.issuer', $papeles);
        $this->assertStringContainsString('previewDoc.expires_on', $papeles);
        // Return, with a reason, only for a wallet ID and only with the verify permission.
        $this->assertStringContainsString("\$api.post('/admin/papeles/' + doc.id + '/return', { reason })", $papeles);
        $this->assertStringContainsString("can('verify') && isWalletId(previewDoc) && previewDoc.status !== 'Rejected'", $papeles);
        $this->assertStringContainsString('returnReason.trim().length < 5', $papeles);
        // Verify says what it does for a wallet ID.
        $this->assertStringContainsString("Verifying it puts the Esperanza seal on it in their wallet. It does not change the resident's identity status, and it does not check the ID with the agency that issued it.", $papeles);
    }

    public function test_adding_an_id_sends_the_type_and_photos_but_never_a_status(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');

        $this->assertStringContainsString("\$api.post('/citizen/wallet/ids', body)", $wallet);
        foreach (['id_kind', 'front', 'back', 'expires_on', 'replaces', 'name', 'issuer'] as $field) {
            $this->assertStringContainsString("body.append('{$field}'", $wallet);
        }
        $this->assertDoesNotMatchRegularExpression("/body\\.append\\('[a-z_]*status/", $wallet);
        // A name and an issuer only for Other: a listed ID takes the server's.
        $this->assertStringContainsString("if (this.isOther) {\n                body.append('name'", str_replace("\r\n", "\n", $wallet));
        // Other needs both, and every ID a front.
        $this->assertStringContainsString("'Enter the name of the ID.'", $wallet);
        $this->assertStringContainsString("'Enter who issued the ID.'", $wallet);
        $this->assertStringContainsString("'Add a photo of the front of the ID.'", $wallet);
        // Removing is the Papeles archive.
        $this->assertStringContainsString("\$api.post('/citizen/papeles/' + encodeURIComponent(doc.id) + '/archive')", $wallet);
    }

    public function test_the_officer_revokes_a_residency_approval_apart_from_the_identity(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $modal = $this->source('resources/views/admin/constituents/modal-citizen-account.blade.php');

        $this->assertStringContainsString("'/revoke-approval', { reason }", $page);
        $this->assertStringContainsString("accountDetail?.status === 'Approved' && can('const_citizen_verification','reject')", $modal);
        $this->assertStringContainsString('revokeApprovalReason.trim().length < 5', $modal);
        $this->assertStringContainsString('accountDetail?.residency?.history', $modal);
        // Its own action, not the identity's.
        $this->assertStringContainsString("decideIdentity('revoke')", $modal);
        $this->assertStringContainsString('@click="revokeApproval()"', $modal);
    }

    public function test_every_kind_of_id_has_its_own_wallet_card_and_none_copies_an_agency(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');

        foreach (['philippine_national_id', 'drivers_license', 'passport', 'umid', 'sss', 'gsis', 'philhealth', 'owwa', 'prc', 'postal', 'voters', 'tin', 'other'] as $kind) {
            $this->assertStringContainsString("{$kind}: { band: ", $wallet, "{$kind} has no card design");
        }
        // Every card says what it is.
        $this->assertStringContainsString('en="Esperanza wallet copy · not an official ID"', $wallet);
        // Its picture is the face on the ID when one was found, else the photo.
        $this->assertStringContainsString(':style="portraitStyle(d)"', $wallet);
        $this->assertStringContainsString("String(d.portrait_box || '').split(',')", $wallet);
        // The only seal on a card is Esperanza's, for an ID the LGU verified.
        preg_match_all('/<img[^>]+src="([^"]+)"/', $wallet, $images);
        foreach ($images[1] as $src) {
            $this->assertTrue(str_contains($src, 'esperanza-seal') || str_starts_with($src, '{{') === false, "unexpected image {$src}");
        }
    }

    public function test_what_the_id_says_is_sent_with_it_and_can_be_corrected(): void
    {
        $wallet = $this->source('resources/views/components/citizen/digital-id-wallet.blade.php');
        $fields = $this->source('resources/views/components/citizen/wallet-id-fields.blade.php');

        foreach (['id_number', 'holder_name', 'birthdate', 'sex', 'address'] as $field) {
            $this->assertStringContainsString("x-model=\"details.{$field}\"", $fields);
        }
        $this->assertStringContainsString('x-model="details.extras[key]"', $fields);
        $this->assertStringContainsString("body.append('details[' + k + ']', v)", $wallet);
        $this->assertStringContainsString("\$api.patch('/citizen/wallet/ids/' + encodeURIComponent(this.doc.id)", $wallet);
        $this->assertStringContainsString('Saving a change sends this ID back to the LGU for review.', $wallet);
    }

    public function test_an_officer_sees_what_the_id_says_only_with_view_sensitive(): void
    {
        $papeles = $this->source('resources/views/admin/papeles.blade.php');

        $this->assertStringContainsString('viewing?.id_details?.restricted', $papeles);
        $this->assertStringContainsString('needs the Papeles "view sensitive" permission', $papeles);
    }
}
