<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Owner fixes, 2026-11-25: the resident's "What happens next" stepper gives
 * every step an equal share of the full width with its circle on one line,
 * and a Tulong service that gives money or goods needs its amount when it is
 * recommended, approved or recorded as already given.
 */
class StepperAndAmountTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_what_happens_next_stepper_uses_equal_full_width_steps(): void
    {
        foreach (['resources/views/citizen/assistance-requests.blade.php', 'resources/views/citizen/document-requests.blade.php'] as $page) {
            $view = $this->source($page);
            // Equal-width steps; the connector runs from this circle's centre to the next one's.
            $this->assertStringContainsString('<li class="relative flex-1 min-w-0 flex flex-col items-center', $view, $page);
            $this->assertStringContainsString('absolute top-[13px] left-1/2 w-full h-0.5', $view, $page);
            // The old layout: a trailing flex-1 slot and a connector nudged up by hand.
            $this->assertStringNotContainsString('flex-1 h-0.5 bg-brand-200 -mt-5', $view, $page);
        }
    }

    public function test_money_or_goods_services_require_the_amount(): void
    {
        $admin = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString("return ['cash', 'goods', 'medical'].includes(this.processingKind);", $admin);
        $this->assertStringContainsString("'Enter the recommended amount (₱) first.'", $admin);
        $this->assertStringContainsString("'Enter the approved amount (₱) first.'", $admin);
        $this->assertStringContainsString('x-model="approveAmount" :required="needsAmount"', $admin);

        $apply = $this->source('resources/js/welfare-admin.js');
        $this->assertStringContainsString("return ['cash', 'goods', 'medical'].includes(this.apType?.processingKind);", $apply);
        $this->assertStringContainsString("'Enter the amount (₱) given, more than ₱0.'", $apply);
    }
}
