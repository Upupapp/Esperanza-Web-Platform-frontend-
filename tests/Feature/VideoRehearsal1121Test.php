<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Fixes from the rehearsal of the owner's demo video (21 Nov 2026): a Tulong
 * file that lists one record twice says so in the review, and a report period
 * longer than two years is stopped in the window instead of in the preview.
 */
class VideoRehearsal1121Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_a_record_listed_twice_in_an_office_file_is_shown_in_the_review_not_dropped_at_import(): void
    {
        $js = $this->source('resources/js/request-import-ui.js');
        $modal = $this->source('resources/views/admin/partials/modal-request-import.blade.php');

        // The server's own keys: the service and its control number, or the service, the day and the person.
        $this->assertStringContainsString("r.source_ref ? r.service_key + '|ref|' + String(r.source_ref).trim().toLowerCase() : null,", $js);
        $this->assertStringContainsString("r.service_key + '|' + r.date + '|' + nameKey(r) + '|' + r.barangay,", $js);
        $this->assertStringContainsString("if (first) { r.status = 'duplicate'; r.twinRow = first; return; }", $js);
        $this->assertStringContainsString('markListedTwice(rows);', $js);
        $this->assertStringContainsString("'Listed twice in the file: the same record as row ' + r.twinRow", $js);
        // The row, the "not added" list and the finished summary say it.
        $this->assertStringContainsString('x-text="duplicateNote(r)"', $modal);
        $this->assertStringContainsString("if (r.status === 'duplicate') return duplicateNote(r);", $js);
        $this->assertStringContainsString("twice: this.rows.filter((r) => r.status === 'duplicate' && r.twinRow).length,", $js);
        $this->assertStringContainsString("' listed twice in the file, so each was added once.'", $modal);
    }

    public function test_a_report_period_over_two_years_is_refused_in_the_window(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $picker = $this->source('resources/views/components/admin/dashboards/period-picker.blade.php');
        $tulong = $this->source('resources/views/admin/partials/modal-tulong-report.blade.php');

        // The API's PERIOD_TOO_LONG is more than 731 days; a method, since the picker is spread into each component.
        $this->assertStringContainsString("return (Date.parse(this.to) - Date.parse(this.from)) / 86400000 > 731;", $js);
        $this->assertStringContainsString('<p x-show="periodTooLong?.()" x-cloak role="alert"', $picker);
        $this->assertStringContainsString('A report covers at most two years. Choose a shorter period.', $picker);
        $this->assertStringContainsString('x-bind:disabled="!office || !from || !to || periodTooLong()"', $tulong);
        $this->assertStringContainsString('if (!this.office || !this.from || !this.to || this.periodTooLong()) return;', $js);
    }
}
