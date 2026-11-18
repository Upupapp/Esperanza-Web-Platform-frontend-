<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Residents' Feedback is a tab of Settings, not of Balita (owner, 2026-11-18:
 * "i want to move it in the settings because this is not really in the balita
 * part in the citizen side, and some account types dont have balita and they
 * need to access the feedback screen. do whats best").
 *
 * The tab asks for the API's own permission, comm_feedback (the server's
 * perm:comm_feedback on /admin/support/*), never Balita's or the rest of
 * Settings'. No sidebar entry: Settings is in the profile menu (owner,
 * 2026-10-07: as few sidebar entries as possible). The old addresses and the
 * notifications already sent still open the ticket.
 */
class FeedbackInSettingsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_feedback_is_a_settings_tab_for_whoever_answers_tickets(): void
    {
        $settings = $this->source('resources/views/admin/settings.blade.php');

        $this->assertStringContainsString("'feedback' => ['message-square-reply', 'Feedback', '/admin/settings/feedback'],", $settings);
        $this->assertStringContainsString("get feedbackAllowed() { return this.\$store.session.canPerm('comm_feedback', 'view'); },", $settings);
        $this->assertStringContainsString("@elseif(\$tabKey === 'feedback') x-show=\"feedbackAllowed\" x-cloak", $settings);
        $this->assertStringContainsString('<div x-show="!$store.session.canSettings() && !directoryAllowed && !facebookAllowed && !feedbackAllowed" x-cloak>', $settings);
        // Outside the Settings-only block, so an officer without the rest of Settings has it.
        $include = strpos($settings, "@include('admin.settings.feedback')");
        $this->assertNotFalse($include);
        $this->assertLessThan(strpos($settings, '<template x-if="$store.session.canSettings()">'), $include);
        // The tab's count of open tickets, which the desk sets.
        $this->assertStringContainsString('feedbackOpen: null,', $settings);
        $this->assertStringContainsString("@if(\$tabKey === 'feedback')<span x-show=\"feedbackOpen\" x-cloak", $settings);
        // Without Settings, the page opens on the tab asked for, else Feedback first.
        $this->assertStringContainsString("if (this.tab === 'feedback' && this.feedbackAllowed) return;", $settings);
        $this->assertStringContainsString("if (this.feedbackAllowed) this.go('feedback');\n                    else if (this.directoryAllowed) this.go('directory');\n                    else if (this.facebookAllowed) this.go('integrations');", $settings);
    }

    public function test_the_desk_keeps_everything_it_did_on_balita(): void
    {
        $this->assertFileDoesNotExist(base_path('resources/views/admin/communications/feedback.blade.php'));
        $desk = $this->source('resources/views/admin/settings/feedback.blade.php');

        $this->assertStringContainsString('<div x-show="tab === \'feedback\'" x-cloak x-data="feedbackDesk" class="space-y-4">', $desk);
        foreach ([
            'role="group" aria-label="Tickets by status"',
            'aria-label="Search tickets"',
            'aria-label="Category"',
            'aria-label="Office"',
            'Assigned to me',
            'Route or assign',
            'aria-label="Conversation"',
            'Reply to the resident',
            'Help content feedback',
        ] as $part) {
            $this->assertStringContainsString($part, $desk, $part);
        }
        // Site Content is a tab of the same page now: it opens in place.
        $this->assertStringContainsString("href=\"{{ route('admin.settings.site-content') }}\" @click.prevent=\"go('site-content')\"", $desk);
        // Beside the Settings list the column is narrower: queue and ticket side by side from xl.
        $this->assertStringContainsString('<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 items-start">', $desk);
        $this->assertStringNotContainsString('lg:', $desk);

        $js = $this->source('resources/js/feedback-desk.js');
        $this->assertStringContainsString("export function register(Alpine) {\n    Alpine.data('feedbackDesk', () => ({", $js);
        $this->assertStringContainsString("this.\$watch('tab', (t) => { if (t === 'feedback' && this.may) this.start(); });", $js);
        $this->assertStringContainsString("window.matchMedia?.('(max-width: 1279px)')", $js);
        $this->assertStringNotContainsString('feedbackDesk', $this->source('resources/js/scope-admin-records.js'));

        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("area(/^\\/admin\\/settings(\\/|$)/.test(areaPath), () => import('./feedback-desk'));", $app);
        $this->assertStringNotContainsString('settings\\/support', $app);
    }

    public function test_balita_has_no_feedback_tab_and_no_sidebar_entry_leads_to_one(): void
    {
        $balita = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString("tabSubs: { balita: 'comm_news', banners: 'comm_banners', events: 'comm_events', blotter: 'comm_directory' },", $balita);
        foreach (["go('feedback')", 'feedbackOpen', "@include('admin.communications.feedback')", 'syncHeader'] as $gone) {
            $this->assertStringNotContainsString($gone, $balita, $gone);
        }
        // Someone holding comm_feedback and none of Balita's tabs is sent on, not refused.
        $this->assertStringContainsString("else if (\$store.session.canPerm('comm_feedback', 'view')) window.location.replace('/admin/settings/feedback');", $balita);
        $this->assertStringContainsString('<div x-show="!anyTabAllowed && !$store.session.canPerm(\'comm_feedback\', \'view\')" x-cloak>', $balita);

        $sidebar = $this->source('resources/views/components/admin/sidebar.blade.php');
        $this->assertStringNotContainsString('label="Feedback"', $sidebar);
        $this->assertStringNotContainsString('comm_feedback', $sidebar);

        $topbar = $this->source('resources/views/components/admin/topbar.blade.php');
        $this->assertStringContainsString("x-show=\"\$store.session.canSettings() || \$store.session.canPerm('comm_feedback', 'view') ||", $topbar);
        $this->assertStringContainsString("\$store.session.canPerm('comm_feedback', 'view') ? '{{ route('admin.settings.feedback') }}'", $topbar);
        $this->assertStringContainsString("'href' => route('admin.settings.feedback'), 'module' => 'communications'],", $topbar);
    }

    public function test_old_addresses_and_sent_notifications_still_open_the_ticket(): void
    {
        $routes = $this->source('routes/web.php');
        $this->assertStringContainsString("Route::view('/admin/settings/feedback', 'admin.settings', ['tab' => 'feedback'])->name('admin.settings.feedback');", $routes);
        $this->assertStringContainsString("Route::view('/admin/communications/feedback', 'admin.forward', ['to' => '/admin/settings/feedback', 'label' => 'Feedback', 'active' => 'settings'])->name('admin.communications.feedback');", $routes);
        $this->assertStringContainsString("Route::view('/admin/settings/support', 'admin.forward', ['to' => '/admin/settings/feedback', 'label' => 'Feedback', 'active' => 'settings'])->name('admin.support');", $routes);

        // The forward keeps ?ref= (and anything else) on its way.
        foreach (['/admin/communications/feedback', '/admin/settings/support'] as $old) {
            $page = $this->get($old)->assertOk()
                ->assertSee("x-init=\"window.location.replace('/admin/settings/feedback' + window.location.search + window.location.hash)\"", false)
                ->getContent();
            preg_match_all('/<script\b([^>]*)>/i', $page, $scripts);
            foreach ($scripts[1] as $attributes) {
                $this->assertStringContainsString('src=', $attributes, $old.': no inline script');
            }
        }

        // Notifications sent before 2026-11-18 link to /admin/communications?tab=feedback&ref=.
        $balita = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString("if (asked === 'feedback') {\n                    params.delete('tab');\n                    const rest = params.toString();\n                    window.location.replace('/admin/settings/feedback' + (rest ? '?' + rest : '') + window.location.hash);\n                    return;\n                }", $balita);
        // ... and the desk opens the ticket a ?ref= names.
        $this->assertStringContainsString("const ref = new URLSearchParams(window.location.search).get('ref');\n            if (ref) this.openTicket(ref);", $this->source('resources/js/feedback-desk.js'));
    }

    public function test_the_settings_feedback_page_renders_without_inline_script_or_blade_error(): void
    {
        $page = $this->get('/admin/settings/feedback')->assertOk()
            ->assertSee('x-data="feedbackDesk"', false)
            ->assertSee("tab: 'feedback'", false)
            ->getContent();
        preg_match_all('/<script\b([^>]*)>/i', $page, $scripts);
        foreach ($scripts[1] as $attributes) {
            $this->assertStringContainsString('src=', $attributes, 'no inline script');
        }
        foreach (['resources/views/admin/settings.blade.php', 'resources/views/admin/settings/feedback.blade.php', 'resources/views/admin/communications.blade.php', 'resources/views/admin/forward.blade.php'] as $view) {
            $this->assertStringNotContainsString('@error=', $this->source($view), $view);
        }
    }
}
