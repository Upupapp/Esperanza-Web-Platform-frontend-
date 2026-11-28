<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The notifications redesign (owner, 28 Nov 2026, after eBPCO's), both
 * portals: the bell's one filter row, Mark all as read and a close button,
 * "See all notifications"; the Notifications page (the staff one is new) with
 * a breadcrumb, All / Unread, Search, Category, When and Clear filters; unread
 * notices as a blue-tinted row with a bold title and a blue dot, no Unread tag
 * and no Mark as read button; messages from residents as their own kind with
 * an Open conversation link; staff links only where the role may go.
 */
class NotificationsRedesignTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_bell_has_one_filter_row_mark_all_close_and_see_all(): void
    {
        $bell = $this->source('resources/views/components/notifications/bell.blade.php');

        $this->assertStringContainsString('@click.outside="close" @keydown.escape.window="open && close()"', $bell, 'a click outside or Esc closes it');
        $this->assertStringContainsString("{{ \$t('Mark all as read', 'Markahang nabasa lahat') }}", $bell);
        $this->assertStringContainsString('<button type="button" @click="close()"', $bell, 'an X at the top');
        $this->assertStringContainsString("@click=\"{{ \$S }}.setBell('all')\"", $bell);
        $this->assertStringContainsString("@click=\"{{ \$S }}.setBell('unread')\"", $bell);
        $this->assertStringContainsString('x-text="{{ $S }}.allCount"', $bell);
        $this->assertStringContainsString('x-text="{{ $S }}.unreadCount"', $bell);
        $this->assertStringContainsString('<template x-for="k in {{ $S }}.categories" :key="k.key">', $bell, 'only the kinds this person has');
        $this->assertStringContainsString("{{ \$t('See all notifications', 'Tingnan lahat ng abiso') }}", $bell);
        $this->assertStringContainsString("route('admin.notifications')", $bell);
        $this->assertStringNotContainsString("' new'", $bell, 'no yellow "N new" tag: the Unread count says it');

        foreach (['admin', 'citizen'] as $portal) {
            $topbar = $this->source("resources/views/components/{$portal}/topbar.blade.php");
            $this->assertStringContainsString('<x-notifications.bell portal="'.$portal.'" />', $topbar);
            $this->assertStringNotContainsString('dismiss(n.id)', $topbar);
        }
    }

    public function test_an_unread_row_is_tinted_bold_with_a_dot_that_marks_it_read(): void
    {
        $row = $this->source('resources/views/components/notifications/row.blade.php');

        $this->assertStringContainsString(":class=\"n.unread ? 'bg-brand-100/70 hover:bg-brand-100' : 'hover:bg-white/80'\"", $row);
        $this->assertStringContainsString(":class=\"n.unread ? 'font-bold' : 'font-medium'\" x-text=\"n.title\"", $row);
        $this->assertStringContainsString('<button type="button" x-show="n.unread" @click.stop="{{ $S }}.markRead(n.id)"', $row);
        $this->assertStringContainsString('<span class="w-2.5 h-2.5 rounded-full bg-brand-600" aria-hidden="true"></span>', $row);
        // Opening its link marks it read too, and closes the bell.
        $this->assertStringContainsString("<a :href=\"{{ \$S }}.href(n)\" @click=\"{{ \$S }}.open(n){{ \$dense ? '; close()' : '' }}\"", $row);
        $this->assertStringContainsString('x-text="{{ $S }}.action(n)"', $row);
        // No Unread tag, no Mark as read button (the dot only names it for screen readers).
        $this->assertDoesNotMatchRegularExpression('/>\s*Unread\s*</', $row);
        $this->assertDoesNotMatchRegularExpression('/>\s*Mark as read\s*</', $row);

        $js = $this->source('resources/js/notifications.js');
        // The link leaves the page at once: the read is sent to outlive it.
        $this->assertStringContainsString("fetch(api.baseUrl.replace(/\\/\$/, '') + `\${path}/\${n.id}/read`, {\n                    method: 'POST',\n                    keepalive: true,", $js);
    }

    public function test_the_notifications_page_has_a_breadcrumb_tabs_filters_and_a_way_back(): void
    {
        $page = $this->source('resources/views/components/notifications/page.blade.php');

        $this->assertStringContainsString("{{ \$t('Dashboard', 'Home') }}</a></li>", $page);
        $this->assertStringContainsString('<li class="text-ink-faint" aria-hidden="true">/</li>', $page);
        $this->assertStringContainsString("@click=\"setTab('all')\"", $page);
        $this->assertStringContainsString("@click=\"setTab('unread')\"", $page);
        $this->assertStringContainsString('<input type="search" x-model.debounce.350ms="q"', $page);
        $this->assertStringContainsString('<select x-model="category" @change="apply()"', $page);
        foreach (['any' => 'Any time', 'today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'on' => 'On a date…'] as $value => $label) {
            $this->assertStringContainsString('<option value="'.$value.'"', $page);
            $this->assertStringContainsString($label, $page);
        }
        $this->assertStringContainsString('<input type="date" x-ref="date" x-show="when === \'on\'"', $page);
        $this->assertStringContainsString('<button type="button" x-show="filtered" x-cloak @click="clearFilters()"', $page, 'Clear filters only once something is filtered');
        $this->assertStringContainsString("{{ \$t('No notifications match these filters.', 'Walang abisong tugma sa mga filter na ito.') }}", $page);
        $this->assertStringContainsString("{{ \$t('Back to Dashboard', 'Bumalik sa Home') }}", $page);

        $js = $this->source('resources/js/notifications-page.js');
        $this->assertStringContainsString("get filtered() { return !!this.q.trim() || !!this.category || this.when !== 'any'; },", $js);
        $this->assertStringContainsString("this.feed.filters = { unread: this.tab === 'unread', category: this.category, q: this.q, since: range.since, until: range.until };", $js);
        $this->assertStringContainsString('this.store.attach(this.feed);', $js, 'a notice read on the page is read in the bell');

        $this->assertStringContainsString('<x-notifications.page portal="admin" />', $this->source('resources/views/admin/notifications.blade.php'));
        $this->assertStringContainsString('<x-notifications.page portal="citizen" />', $this->source('resources/views/citizen/notifications.blade.php'));
        $this->assertStringContainsString("area(/^\\/(admin|citizen)\\/notifications(\\/|\$)/.test(areaPath), () => import('./notifications-page'));", $this->source('resources/js/app.js'));
    }

    public function test_both_pages_render(): void
    {
        $this->get('/admin/notifications')->assertOk()->assertSee('x-data="notificationsPage(\'adminNotifications\')"', false)->assertSee('>Dashboard</a></li>', false);
        $this->get('/citizen/notifications')->assertOk()->assertSee('x-data="notificationsPage(\'citizenNotifications\')"', false)->assertSee('Mga Abiso', false);
    }

    public function test_the_server_filters_and_an_older_server_is_filtered_here(): void
    {
        $js = $this->source('resources/js/notifications.js');

        foreach (['{ unread: 1 }', '{ category: f.category }', '{ q }', '{ since: f.since }', '{ until: f.until }'] as $param) {
            $this->assertStringContainsString($param, $js);
        }
        $this->assertStringContainsString('this.serverFiltered = meta?.filtered === true;', $js);
        $this->assertStringContainsString('get shown() { return this.serverFiltered || !this.filtering ? this.items : this.items.filter((n) => matches(n, this.filters)); },', $js);
        $this->assertStringContainsString('if (Array.isArray(meta?.categories)) this.kinds = meta.categories;', $js);
        // When, from this browser's midnight.
        $this->assertStringContainsString("if (when === '7d') return { since: back(midnight, -6).toISOString(), until: null };", $js);
        $this->assertStringContainsString('return { since: start.toISOString(), until: back(start, 1).toISOString() };', $js);
    }

    public function test_messages_from_residents_and_staff_links_follow_the_pages_checks(): void
    {
        $js = $this->source('resources/js/notifications.js');

        $this->assertStringContainsString("messages: t('Messages from residents', 'Mga mensahe'),", $js);
        $this->assertStringContainsString("['/admin/settings/feedback', 'Open conversation']", $js);
        // The same checks as each page's Access Restricted guard.
        $this->assertStringContainsString("return s.canPerm('comm_feedback', 'view');", $js);
        $this->assertStringContainsString("'/admin/document-requests': 'dokyu', '/admin/assistance-requests': 'tulong', '/admin/constituents': 'constituents',", $js);
        $this->assertStringContainsString("if (under('/admin/appointments') || under('/admin/dashboard/appointments')) return any(['appt_bookings', 'appt_schedules']);", $js);
        $this->assertStringContainsString("return mayOpen(link, window.Alpine?.store('session')) ? link : null;", $js);
    }
}
