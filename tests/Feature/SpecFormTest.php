<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Renders every service form from its spec and checks what the spec promises.
 *
 * TAB 07 asks for 28 services to be rendered FROM
 * config/esperanza_service_form_specs.php rather than hand-written, which is
 * only maintainable if the renderer actually covers every field the spec can
 * contain. Measured: 25 services, 45 steps, 230 fields, 8 field types.
 *
 * A component nothing uses yet is a component nothing tests, so this renders
 * all 25 for real rather than trusting that it compiles.
 */
class SpecFormTest extends TestCase
{
    private const RENDER_FAILURE_MARKERS = [
        'Undefined variable',
        'Undefined array key',
        'Undefined property',
        'ViewException',
        'ErrorException',
        'Attempt to read property',
    ];

    private function specs(): array
    {
        return config('esperanza_service_form_specs');
    }

    private function render(array $spec): string
    {
        return Blade::render('<x-ui.spec-form :spec="$spec" />', ['spec' => $spec]);
    }

    public function test_there_are_specs_to_render(): void
    {
        // Without this the whole file passes by rendering nothing.
        $this->assertGreaterThanOrEqual(20, count($this->specs()));
    }

    public function test_every_service_spec_renders_without_error(): void
    {
        $failures = [];

        foreach ($this->specs() as $service => $spec) {
            try {
                $html = $this->render($spec);
            } catch (\Throwable $e) {
                $failures[] = sprintf('%s threw %s: %s', $service, $e::class, $e->getMessage());

                continue;
            }

            if (trim($html) === '') {
                $failures[] = $service.' rendered empty';

                continue;
            }

            foreach (self::RENDER_FAILURE_MARKERS as $marker) {
                if (str_contains($html, $marker)) {
                    $failures[] = sprintf('%s rendered but contains "%s"', $service, $marker);
                    break;
                }
            }
        }

        $this->assertSame([], $failures, sprintf(
            "%d service form(s) failed to render:\n  %s",
            count($failures), implode("\n  ", $failures)
        ));
    }

    public function test_every_spec_field_reaches_the_form(): void
    {
        $missing = [];

        foreach ($this->specs() as $service => $spec) {
            $html = $this->render($spec);

            foreach ($spec['steps'] ?? [] as $step) {
                foreach ($step['fields'] ?? [] as $field) {
                    if (! str_contains($html, 'name="'.$field['key'].'"')) {
                        $missing[] = $service.' -> '.$field['key'].' ('.($field['type'] ?? '?').')';
                    }
                }
            }
        }

        $this->assertSame([], $missing, sprintf(
            "%d spec field(s) are defined but never rendered — the form silently drops them:\n  %s",
            count($missing), implode("\n  ", $missing)
        ));
    }

    public function test_every_rendered_control_has_a_label_bound_to_it(): void
    {
        // The platform measured 799 of 1729 rendered controls with no accessible
        // name (TAB 10). Anything generated from a spec must not add to that.
        $orphans = [];

        foreach ($this->specs() as $service => $spec) {
            $html = $this->render($spec);

            preg_match_all('/<label[^>]*\sfor="([^"]+)"/', $html, $labels);
            $labelled = array_flip($labels[1]);

            preg_match_all('/<(input|select|textarea)[^>]*\sid="([^"]+)"/', $html, $controls, PREG_SET_ORDER);

            foreach ($controls as $c) {
                if (! isset($labelled[$c[2]])) {
                    $orphans[] = $service.' -> <'.$c[1].' id="'.$c[2].'">';
                }
            }
        }

        $this->assertSame([], $orphans, sprintf(
            "%d generated control(s) have no <label for>:\n  %s",
            count($orphans), implode("\n  ", array_slice($orphans, 0, 20))
        ));
    }

    public function test_required_fields_are_marked_for_assistive_tech(): void
    {
        $unmarked = [];

        foreach ($this->specs() as $service => $spec) {
            $html = $this->render($spec);

            foreach ($spec['steps'] ?? [] as $step) {
                foreach ($step['fields'] ?? [] as $field) {
                    $required = ($field['required'] ?? true) && ($field['type'] ?? 'text') !== 'derivedAge';
                    if (! $required || ($field['type'] ?? '') === 'multiselect') {
                        continue;
                    }

                    // The control for this key must carry aria-required.
                    if (! preg_match('/name="'.preg_quote($field['key'], '/').'"[^>]*aria-required|aria-required[^>]*name="'.preg_quote($field['key'], '/').'"/s', $html)) {
                        $unmarked[] = $service.' -> '.$field['key'];
                    }
                }
            }
        }

        $this->assertSame([], $unmarked, sprintf(
            "%d required field(s) render without aria-required, so a screen reader is not told:\n  %s",
            count($unmarked), implode("\n  ", array_slice($unmarked, 0, 20))
        ));
    }
}
