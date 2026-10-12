<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every password field can show what was typed (owner, 2026-10-12: on the
 * portal and the app alike). <x-ui.input type="password"> carries the eye;
 * a page that draws its own password input must give it one too.
 */
class PasswordVisibilityTest extends TestCase
{
    public function test_the_shared_input_gives_a_password_its_eye(): void
    {
        $input = file_get_contents(base_path('resources/views/components/ui/input.blade.php'));

        $this->assertStringContainsString(":type=\"shown ? 'text' : 'password'\"", $input);
        $this->assertStringContainsString('@click="shown = !shown"', $input);
        $this->assertStringContainsString("'Show password'", $input);
        $this->assertStringContainsString("'Hide password'", $input);
    }

    public function test_no_page_draws_a_password_input_without_one(): void
    {
        $component = base_path('resources/views/components/ui/input.blade.php');
        foreach (File::allFiles(resource_path('views')) as $file) {
            if ($file->getPathname() === $component) {
                continue;
            }
            $source = $file->getContents();
            // A bare <input type="password">: hidden for good. Pages that draw
            // their own bind the type to a toggle instead (:type="... 'password'").
            $this->assertDoesNotMatchRegularExpression(
                '/<input\b[^>]*\stype="password"/',
                $source,
                $file->getRelativePathname().' draws a password field with no way to show it',
            );
        }
    }
}
