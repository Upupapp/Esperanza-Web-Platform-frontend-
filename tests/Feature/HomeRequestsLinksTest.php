<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The resident home's "Mga Kamakailang Kahilingan" lists Dokyu and Tulong
 * requests together, so it links to both lists. Its one "Tingnan Lahat"
 * opened Dokyu even when the request was Tulong (2026-11-13).
 */
class HomeRequestsLinksTest extends TestCase
{
    public function test_the_recent_requests_card_links_to_both_dokyu_and_tulong(): void
    {
        $dashboard = file_get_contents(base_path('resources/views/citizen/dashboard.blade.php'));
        $start = strpos($dashboard, 'fil="Mga Kamakailang Kahilingan"');
        $this->assertNotFalse($start);
        $header = substr($dashboard, $start, 1800);

        $this->assertStringContainsString("href=\"{{ route('citizen.document-requests') }}\"", $header);
        $this->assertStringContainsString("href=\"{{ route('citizen.assistance-requests') }}\"", $header);
        $this->assertStringContainsString('fil="Lahat ng Dokyu" en="All Dokyu"', $header);
        $this->assertStringContainsString('fil="Lahat ng Tulong" en="All Tulong"', $header);
        $this->assertStringNotContainsString('fil="Tingnan Lahat"', substr($header, 0, strpos($header, 'x-for="req in requests"')));
    }

    public function test_each_request_row_still_opens_its_own_list(): void
    {
        $dashboard = file_get_contents(base_path('resources/views/citizen/dashboard.blade.php'));

        $this->assertStringContainsString(
            ":href=\"(req.type === 'tulong' ? '{{ route('citizen.assistance-requests') }}' : '{{ route('citizen.document-requests') }}') + '?ref=' + encodeURIComponent(req.ref)\"",
            $dashboard,
        );
    }
}
