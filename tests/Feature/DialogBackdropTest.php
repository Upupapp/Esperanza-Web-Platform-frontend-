<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A dialog's dark backdrop covers the whole window (screen sweep, 2026-11-18).
 * Inside a space-y-3 page, Tailwind gave the Sakuna incident dialog's fixed
 * backdrop the 12 px bottom margin of a list item, so a light strip showed
 * under every Sakuna dialog. One unlayered rule takes that margin off every
 * full-window layer.
 */
class DialogBackdropTest extends TestCase
{
    public function test_a_full_window_layer_takes_no_margin_from_its_parent(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertMatchesRegularExpression('/\.fixed\.inset-0\s*\{\s*margin:\s*0;\s*\}/', $css);
        // Unlayered: it must not sit inside an @layer block, or the space-y utility would win.
        $at = strpos($css, '.fixed.inset-0 {');
        $this->assertNotFalse($at);
        $before = substr($css, 0, $at);
        $this->assertSame(substr_count($before, '{'), substr_count($before, '}'), 'the rule is at the top level of app.css');
    }

    public function test_the_sakuna_incident_dialog_is_a_fixed_full_window_layer(): void
    {
        $modal = file_get_contents(base_path('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php'));
        $this->assertStringContainsString('class="fixed inset-0 ', $modal);
    }
}
