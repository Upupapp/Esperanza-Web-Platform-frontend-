<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Canva guide review, 2026-11-19: wording and labels the four user guides showed
 * wrong on the real screens.
 */
class GuideReview1119Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_dokyu_list_takes_each_requests_category_from_the_server(): void
    {
        // The old name list filed the PWD ID, Solo Parent ID, Cedula and the MCRO certificates under "Other".
        $this->assertStringContainsString(
            "category: row.category || cls.category || 'Other Municipal Services',",
            $this->source('resources/views/admin/document-requests.blade.php'),
        );
    }

    public function test_sakuna_and_balita_say_what_they_hold(): void
    {
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $this->assertStringContainsString('Casualties: dead, injured, missing or not identified', $modal);
        $this->assertStringNotContainsString('Missing, deceased or unidentified persons', $modal);

        $dashboard = $this->source('resources/views/components/admin/dashboards/sakuna.blade.php');
        $this->assertStringContainsString("num(k.unidentified) + ' not identified'", $dashboard);

        $balita = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringNotContainsString('the barangay blotter', $balita);
        // The events filter uses the cards' words.
        $this->assertStringContainsString("x-text=\"({ 'Released': 'Published', 'Rejected': 'Sent back', 'Pending Review': 'Waiting for review' })[s] || s\"", $balita);

        // English says "center" on the resident's Emergency page, as on every other screen.
        $emergency = $this->source('resources/views/citizen/emergency.blade.php');
        $this->assertDoesNotMatchRegularExpression('/en="[^"]*[Cc]entres?\b/', $emergency);
    }

    public function test_the_registry_screens_use_the_same_words_as_the_lists(): void
    {
        $this->assertStringContainsString('>Registry record</th>', $this->source('resources/views/admin/constituents/tab-citizen-verification.blade.php'));
        $this->assertStringContainsString('cvStatusOf(accountRow)', $this->source('resources/views/admin/constituents/modal-citizen-account.blade.php'));
        $this->assertStringContainsString('label="Profiled, whole town"', $this->source('resources/views/admin/constituents/tab-profiling.blade.php'));
        $this->assertStringContainsString('Undo archives the residents', $this->source('resources/views/lazy/constituents-import.blade.php'));
        $this->assertStringContainsString('>Resident ID<', $this->source('resources/views/components/citizen/digital-id-card.blade.php'));
    }
}
