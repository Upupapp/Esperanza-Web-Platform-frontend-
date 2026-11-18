<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QA sweep 2026-11-18, assignment web-7: the resident Directory, Help,
 * Profile, Settings and Notifications pages, the Balita photo tiles, the
 * staff bell, the Dokyu & Tulong Matrix and the standard rejection reasons.
 */
class Sweep1118web7Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** At 390px the barangay's number and two buttons ran past the card and the page scrolled sideways. */
    public function test_my_barangay_actions_wrap_on_a_phone(): void
    {
        $page = $this->source('resources/views/citizen/directory.blade.php');

        $this->assertStringContainsString('<div class="flex flex-wrap items-center gap-2 shrink-0">', $page);
        $this->assertStringNotContainsString('<div class="flex items-center gap-2 shrink-0">', $page);
    }

    /** The viewer drops items still being checked, so the tile's place in the whole list opened the wrong photo. */
    public function test_a_tile_opens_its_own_photo_when_an_earlier_one_is_still_being_checked(): void
    {
        $gallery = $this->source('resources/views/components/balita/gallery.blade.php');

        $this->assertStringContainsString(
            '@click="(shown => $store.balitaViewer.show(shown, shown.findIndex((m) => m.id === tile.media.id), {{ $label }}, {{ $en }}))(({{ $media }} || []).filter((m) => m && m.url))"',
            $gallery
        );
        $this->assertStringNotContainsString('show({{ $media }}, tile.index', $gallery);
    }

    /** The 22rem bell panel hung from the bell, left of the profile chip, and ran off a phone's left edge. */
    public function test_the_staff_notifications_panel_stays_on_a_phone_screen(): void
    {
        $topbar = $this->source('resources/views/components/admin/topbar.blade.php');

        $this->assertStringContainsString("<div class=\"sm:relative\" x-data=\"dropdown\" @click.outside=\"close\">\n            <button @click=\"toggle\" aria-label=\"Notifications\"", $topbar);
        $this->assertStringContainsString('class="absolute right-0 mt-2 w-[min(22rem,calc(100vw-2rem))] surface-glass', $topbar);
        $this->assertStringNotContainsString('w-[22rem]', $topbar);
        // The header the panel hangs from below sm is positioned.
        $this->assertStringContainsString('class="relative z-30 flex items-center gap-4 flex-wrap px-1', $topbar);
    }

    /** The window only hides itself, so a tutorial video played on, with sound, after it closed. */
    public function test_a_tutorial_video_stops_when_its_window_closes(): void
    {
        $help = $this->source('resources/views/citizen/help.blade.php');

        $this->assertStringContainsString('<template x-if="open && activeTutorial.playable">', $help);
        $this->assertStringNotContainsString('<template x-if="activeTutorial.playable">', $help);
        $this->assertStringContainsString('watchTutorial(t) { this.activeTutorial = t; this.open = true; },', $help);
    }

    /** "16 y/o" for a member born 2010-12-20 on 2026-11-18: the birthday was ignored. */
    public function test_a_family_members_age_waits_for_the_birthday(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');

        // The shared rule in app.js, not a second copy of it.
        $this->assertStringContainsString('memberAge(dob) { return window.ageFromBirthDate(dob); },', $profile);
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('if (today.getMonth() + 1 < m || (today.getMonth() + 1 === m && today.getDate() < d)) age--;', $app);
        $this->assertStringContainsString('x-text="memberAge(member.dob) !== null ? memberAge(member.dob) + \' \' + ', $profile);
        $this->assertStringNotContainsString('new Date().getFullYear() - new Date(member.dob).getFullYear()', $profile);
    }

    /** A failed load read "You have no requests yet." to a resident with a pending data request. */
    public function test_settings_says_when_a_list_did_not_load(): void
    {
        $settings = $this->source('resources/views/citizen/settings.blade.php');

        $this->assertStringContainsString('} catch { this.notifPrefs = []; this.notifPrefsFailed = true; }', $settings);
        $this->assertStringContainsString('} catch { this.dataRequests = []; this.dataRequestsFailed = true; }', $settings);
        $this->assertStringContainsString('retryExpr="loadNotifPrefs()"', $settings);
        $this->assertStringContainsString('retryExpr="loadDataRequests()"', $settings);
        $this->assertStringContainsString('x-show="!notifPrefsLoading && !notifPrefsFailed && !notifPrefs.length"', $settings);
        $this->assertStringContainsString('x-show="!dataRequestsLoading && !dataRequestsFailed && !dataRequests.length"', $settings);
        $this->assertStringNotContainsString('} catch { this.dataRequests = []; } finally', $settings);
        $this->assertStringNotContainsString('} catch { this.notifPrefs = []; } finally', $settings);
    }

    /** The success toast showed in .finally(), so a refused Mark all as read still said it worked. */
    public function test_mark_all_as_read_says_so_only_when_it_worked(): void
    {
        $page = $this->source('resources/views/citizen/notifications.blade.php');

        // The store answers true or false (it no longer has to be guessed from
        // the unread count, which a live update can change meanwhile).
        $this->assertStringContainsString('this.$store.citizenNotifications.markAllRead().then((ok) => {', $page);
        $this->assertStringContainsString("this.\$dispatch('toast', ok === false", $page);
        $app = $this->source('resources/js/app.js');
        $this->assertMatchesRegularExpression('/async markAllRead\(\) \{.*?return true;.*?return false;/s', $app);
        $this->assertStringContainsString("variant: 'error' }", $page);
        $this->assertStringContainsString('}).finally(() => { this.markingAll = false; });', $page);
        $this->assertStringNotContainsString('markAllRead().finally(', $page);
    }

    /** The matrix was typed by hand and contradicted the grants (Department Staff and Barangay Secretary rows). */
    public function test_the_dokyu_and_tulong_matrix_is_read_from_the_roles_grants(): void
    {
        $users = $this->source('resources/views/admin/users.blade.php');
        $start = strpos($users, 'DOKYU & TULONG MATRIX');
        $this->assertNotFalse($start);
        $matrix = substr($users, $start, strpos($users, "@include('admin.users.departments')") - $start);

        $this->assertStringNotContainsString('$matrixRows', $users);
        $this->assertStringNotContainsString('8 system roles', $users);
        $this->assertStringNotContainsString('Built from the existing role/permission configuration', $users);
        $this->assertStringContainsString("const result = await \$api.get('/admin/roles');", $matrix);
        $this->assertStringContainsString("this.roles = (result.data || []).filter((r) => r.key !== 'citizen');", $matrix);
        $this->assertStringContainsString('<template x-for="role in roles" :key="role.key">', $matrix);
        $this->assertStringContainsString("holds(role, ['dokyu', 'tulong'], action)", $matrix);
        $this->assertStringContainsString("holds(role, ['reports'], 'view')", $matrix);
        $this->assertStringContainsString("return role.key === 'super_admin' || this.subs(keys).some((s) => (role.permissions?.[s] || []).includes(action));", $matrix);
        // Read again each time the tab opens: a grant saved on the Permissions
        // tab shows here without a reload, the rows staying up meanwhile.
        $this->assertStringContainsString('x-init="$watch(\'tab\', (t) => { if (t === \'matrix\') load(); }); if (tab === \'matrix\') load()"', $matrix);
        $this->assertStringContainsString('this.loading = !this.roles.length;', $matrix);
        $this->assertStringContainsString('retryExpr="load()"', $matrix);
        $this->assertStringContainsString("->whereIn('key', ['dokyu', 'tulong', 'reports'])", $users);
        $this->assertStringContainsString("'barangay_secretary' => 'Own barangay',", $users);
    }

    /** "Please upload: [required document]." reached residents word for word. */
    public function test_no_standard_rejection_reason_carries_a_placeholder(): void
    {
        $reasons = config('esperanza_validation_guide.standard_reasons');

        $this->assertNotEmpty($reasons);
        foreach ($reasons as $reason) {
            $this->assertDoesNotMatchRegularExpression('/\[[^\]]+\]/', $reason['message'], $reason['label']);
        }
    }
}
