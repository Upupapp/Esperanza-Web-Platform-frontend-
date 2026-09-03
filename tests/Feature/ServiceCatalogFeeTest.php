<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * An absent fee must never render as a price.
 *
 * The Service Catalog screen defaulted a missing fee to "Free" while defaulting
 * a missing duration to "—" two characters away. Nothing in the catalogue lacks
 * a fee today, so this was latent — but the screen is meant to become
 * administrable, and the first service added without one would have told staff
 * a chargeable service costs nothing.
 *
 * Raised by the mobile lane, who found the same class in their ReceiptType and
 * ServiceCategory decoders: a default that is not merely unhelpful but states
 * something false about money, or about which service a citizen filed.
 */
class ServiceCatalogFeeTest extends TestCase
{
    public function test_a_missing_fee_never_defaults_to_a_price(): void
    {
        $source = file_get_contents(resource_path('views/admin/settings.blade.php'));

        // Strip comments first, or this gate reads its own explanation as a defect.
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);
        $source = preg_replace('/(?<!:)\/\/[^\n]*/', '', $source);

        $this->assertSame(
            0,
            preg_match_all("/'fee'\s*=>\s*[^,]*\?\?\s*'(?!—)[^']*'/", $source, $m),
            'A fee falls back to a concrete value: '.implode(', ', $m[0] ?? [])."\n"
            .'An unknown amount must render as "—". Defaulting it to a price states '
            .'something false about money on a government fee schedule.'
        );
    }

    public function test_every_catalogue_entry_actually_carries_a_fee(): void
    {
        $missing = [];

        $sources = [
            'municipal' => config('esperanza_municipal_documents.documents'),
            'tulong' => config('esperanza_tulong_types.types'),
            'barangay' => config('esperanza_barangay_documents.labangtaytay.documents'),
        ];

        foreach ($sources as $label => $entries) {
            foreach ((array) $entries as $entry) {
                if (! isset($entry['fee']) || $entry['fee'] === '') {
                    $missing[] = $label.': '.($entry['name'] ?? $entry['key'] ?? '?');
                }
            }
        }

        $this->assertSame([], $missing, sprintf(
            "%d catalogue entr(y/ies) carry no fee. Give each one an explicit amount —\n"
            ."or \"Free\" where it genuinely is free, which is a statement someone chose\n"
            ."to make rather than a default that made it for them:\n  %s",
            count($missing), implode("\n  ", $missing)
        ));
    }
}
