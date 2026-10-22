<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The citizen website brought up to the phone app (2026-10-15): emergency
 * hotlines, evacuation centres and alerts; reporting an incident and
 * following it; Balita (read, like, comment, share); a scannable QR on the Resident ID;
 * the server's ready-to-claim details; the signed copy of an issued document;
 * the server's fees, times and requirements; Documents Uploaded; and the
 * legal pages opening signed out. These pin what each part calls and the
 * markup that carries it. No resident data: the pages read the API at run time.
 */
class CitizenAppParityTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_a1_the_emergency_page_lists_hotlines_centres_and_alerts(): void
    {
        $page = $this->source('resources/views/citizen/emergency.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        // Its own page, in the resident's sidebar.
        $this->assertStringContainsString("Route::view('/citizen/emergency', 'citizen.emergency')", $this->source('routes/web.php'));
        $this->assertStringContainsString(":href=\"route('citizen.emergency')\" icon=\"siren\" label=\"Sakuna\" labelEn=\"Emergency\"", $this->source('resources/views/components/citizen/sidebar.blade.php'));

        // Call 911 first, and every hotline is a tap to call.
        $this->assertStringContainsString('href="tel:911"', $page);
        $this->assertStringContainsString('en="In a life-threatening emergency, call 911."', $page);
        $this->assertStringContainsString(':href="dialHref(h.contact) || null"', $page);
        $this->assertStringContainsString("return number.replace('+', '').length < 3 ? null : 'tel:' + number;", $js);

        // The public reads, each on its own so one failing hides nothing else.
        $this->assertStringContainsString("api.get('/hotlines')", $js);
        $this->assertStringContainsString("api.getAll('/sakuna/centers')", $js);
        $this->assertStringContainsString("api.get('/alerts', { params: barangay ? { barangay } : {} })", $js);

        // A centre: open or not in words, capacity and occupancy, services, directions.
        $this->assertStringContainsString("open: c.status === 'Processing',", $js);
        $this->assertStringContainsString("'https://www.google.com/maps/dir/?api=1&destination='", $js);
        $this->assertStringContainsString("'https://www.google.com/maps/search/?api=1&query='", $js);
        $this->assertStringContainsString('x-text="occupancyText(c)"', $page);
        $this->assertStringContainsString('x-for="(s, i) in centre.services"', $page);

        $response = $this->get('/citizen/emergency');
        $response->assertOk();
        $response->assertSee('x-data="citizenEmergency"', false);
    }

    public function test_a2_an_incident_is_reported_once_and_followed(): void
    {
        $page = $this->source('resources/views/citizen/emergency.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        // The incident types and severities the staff Incidents tab files and
        // filters by (SakunaVocabulary, 2026-10-17): six quick picks, the rest
        // under More kinds; the older names are read under the shared ones.
        foreach (["'Flood'", "'Fire'", "'Landslide'", "'Medical Emergency'", "'Vehicular Accident'", "'Other'", "'Rescue Request'", "'Public Health Emergency'"] as $type) {
            $this->assertStringContainsString("name: {$type}", $js, $type);
        }
        $this->assertStringContainsString("{ Flooding: 'Flood', 'Road Accident': 'Vehicular Accident', 'Other Concern': 'Other' }", $js);
        $this->assertStringContainsString("[['Low', 'Mababa'], ['Medium', 'Katamtaman'], ['High', 'Mataas'], ['Critical', 'Kritikal']]", $js);
        // The status in the staff side's words.
        $this->assertStringContainsString("Processing: ['Dispatched',", $js);
        $this->assertStringContainsString('x-text="statusLabel(row.status)"', $page);

        // Barangay names come from GET /barangays (the store), as the server requires.
        $this->assertStringContainsString('Object.keys(this.$store.barangay.catalog || {})', $js);

        // JSON, or multipart with up to three photos; one client_uuid per report,
        // kept for a retry and cleared only once it is filed.
        $this->assertStringContainsString("api.post('/citizen/incidents', body)", $js);
        $this->assertStringContainsString("body.append('photos[' + i + ']', p.file)", $js);
        $this->assertStringContainsString('client_uuid: this.clientUuid,', $js);
        $this->assertStringContainsString('if (!this.clientUuid) this.clientUuid = newUuid();', $js);
        $this->assertStringContainsString('// The form and its client_uuid stay as they were, for a retry.', $js);
        $this->assertStringContainsString("const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];", $js);
        $this->assertStringContainsString('const MAX_PHOTO_BYTES = 10 * 1024 * 1024;', $js);
        $this->assertStringContainsString('en="For life-threatening emergencies, call a hotline first."', $page);

        // The list and the detail, with its timeline and photos (a null url is still being checked).
        $this->assertStringContainsString("api.getAll('/citizen/incidents', { params: { sort: '-created_at' } })", $js);
        $this->assertStringContainsString("api.get('/citizen/incidents/' + encodeURIComponent(row.ref))", $js);
        $this->assertStringContainsString("return this.t('Being checked', 'Sinusuri pa');", $js);
        $this->assertStringContainsString('x-for="s in steps"', $page);
        $this->assertStringContainsString('x-text="statusLabel(detail.status)"', $page);
    }

    public function test_a3_residents_read_balita_and_do_not_post(): void
    {
        $page = $this->source('resources/views/citizen/announcements.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        // Only the LGU posts to Balita (owner, 2026-10-20): residents read,
        // like, comment on and share its posts, and write none of their own.
        $this->assertStringContainsString("const res = await api.get('/announcements', { params: { per_page: FEED_PAGE, page } });", $js);
        $this->assertStringContainsString("engagementPath(post, action) { return '/citizen/announcements/' + post.id + '/' + action; }", $js);
        foreach (['/citizen/community-posts', 'balitaComposer', 'sendPost', 'fromCommunity'] as $gone) {
            $this->assertStringNotContainsString($gone, $js);
            $this->assertStringNotContainsString($gone, $page);
        }
        $this->assertStringNotContainsString('reportFor', $page, 'no Report on a Balita post');
        $this->assertStringNotContainsString('x-balita.media-field', $page, 'no photo or video picker for residents');
        $this->assertStringContainsString("t('Like', 'I-like')", $page);
        $this->assertStringContainsString("t('Share', 'Ibahagi')", $page);
    }

    public function test_a4_the_resident_id_carries_a_real_qr_code(): void
    {
        $card = $this->source('resources/views/components/citizen/digital-id-card.blade.php');

        $this->assertStringContainsString('x-effect="drawQr(card?.qr || null)"', $card);
        $this->assertStringContainsString('window.qrSvg(payload, 3)', $card);
        $this->assertStringContainsString('x-html="qrMarkup"', $card);
        // The decorative barcode and the payload printed as text are gone.
        $this->assertStringNotContainsString('Barcode-style decoration', $card);
        $this->assertStringNotContainsString('x-text="card.qr"', $card);
        // The same QR helper the issued documents use.
        $this->assertStringContainsString("import('qrcode-generator')", $this->source('resources/js/shared-docs.js'));
    }

    public function test_a5_ready_to_claim_details_come_from_the_server(): void
    {
        $extras = $this->source('resources/views/components/citizen/scope/request-extras.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        foreach (['document-requests', 'assistance-requests'] as $page) {
            $source = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('r.pickup = d.pickup || null;', $source, $page);
            // The page's own directory only when the server sent no pickup.
            $this->assertStringContainsString('<div class="rounded-xl border border-slate-200 p-3.5 mt-3" x-show="!selectedRequest.pickup">', $source, $page);
        }

        $this->assertStringContainsString('get claim() { return this.r?.pickup || null; },', $js);
        $this->assertStringContainsString("if (key === 'valid_id')", $js);
        $this->assertStringContainsString("if (key === 'reference_number')", $js);
        $this->assertStringContainsString("'The fee of ' + peso(s.fee_due) + ', paid at the office before release'", $js);
        $this->assertStringContainsString('s.office_hours_fil', $js);
        $this->assertStringContainsString('x-show="claim" x-cloak', $extras);
        $this->assertStringContainsString(':href="claimTel"', $extras);
        $this->assertStringContainsString('en="Book a time to claim it"', $extras);
        $this->assertStringContainsString("get bookHref() { return this.links.appointments + '?request=' + encodeURIComponent(this.r?.ref || ''); },", $js);
    }

    public function test_a6_an_issued_document_offers_its_signed_copy(): void
    {
        $extras = $this->source('resources/views/components/citizen/scope/request-extras.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        $this->assertStringContainsString(':href="d.scan_url" target="_blank" rel="noopener"', $extras);
        $this->assertStringContainsString('en="View signed copy"', $extras);
        $this->assertStringContainsString('The paper copy you claim is the official document.', $extras);
        // Revoked: no copy at all.
        $this->assertStringContainsString("if (d.revoked) return 'revoked';", $js);
        $this->assertStringContainsString("return d.has_scan ? 'checking' : 'none';", $js);
    }

    public function test_a7_fees_times_and_requirements_come_from_the_service_catalog(): void
    {
        $js = $this->source('resources/js/scope-citizen.js');
        $dokyu = $this->source('resources/views/citizen/document-requests.blade.php');
        $tulong = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringContainsString('if (sv.fee && sv.fee.display) entry.fee = sv.fee.display;', $js);
        $this->assertStringContainsString('if (sv.days) entry.days = sv.days;', $js);
        $this->assertStringContainsString('entry.requirements = sv.requirements.map(itemText).filter(Boolean);', $js);
        $this->assertStringContainsString('if (steps.length) entry.process = steps;', $js);
        $this->assertStringContainsString('window.applyServiceCatalog = applyServiceCatalog;', $js);

        $this->assertStringContainsString('this.serviceMeta = window.applyServiceCatalog(this.docs, res.data || [], (sv) => {', $dokyu);
        $this->assertStringContainsString('const meta = window.applyServiceCatalog(this.types, res.data || [], (sv) => ({', $tulong);

        // Since 2026-10-17 the form's questions, the name and the category are
        // the server's too (staff edit them in the Service Catalog), and a
        // service added there after the site was built is listed from it.
        $this->assertStringContainsString('entry.fields = JSON.parse(JSON.stringify(sv.fields));', $js);
        $this->assertStringContainsString('if (sv.name) entry.name = sv.name;', $js);
        $this->assertStringContainsString("if (typeof addEntry === 'function') {", $js);

        // Filing still sends the same service key, and attaches each file to the
        // requirement the server made for that line (by label, then position).
        $this->assertStringContainsString("\$api.post('/citizen/requests', { service_key: doc.key, form_data: formData", $dokyu);
        $this->assertStringContainsString('const target = serverReqs.find((q) => q.label === label) || serverReqs[Number(i)];', $dokyu);
    }

    public function test_a8_documents_uploaded_lists_every_attached_file(): void
    {
        $view = $this->source('resources/views/components/citizen/uploads.blade.php');
        $js = $this->source('resources/js/scope-citizen.js');

        $this->assertStringContainsString('<x-citizen.uploads />', $this->source('resources/views/citizen/profile.blade.php'));
        $this->assertStringContainsString("api.get('/citizen/uploads')", $js);
        $this->assertStringContainsString("x-for=\"f in ['all', 'dokyu', 'tulong']\"", $view);
        $this->assertStringContainsString('x-show="!row.current"', $view);
        $this->assertStringContainsString('en="Replaced"', $view);
        $this->assertStringContainsString(':href="row.url" target="_blank" rel="noopener"', $view);
        $this->assertStringContainsString('x-text="why(row)"', $view);
        $this->assertStringContainsString("case 'pending': return this.t('Being checked for viruses.", $js);
    }

    public function test_the_legal_pages_open_signed_out_and_nothing_else_does(): void
    {
        $legal = $this->source('resources/views/citizen/legal.blade.php');
        $topbar = $this->source('resources/views/components/citizen/topbar.blade.php');

        $this->assertStringContainsString('active="legal" :public="true">', $legal);
        $this->assertStringContainsString("\$api.get('/site-content/' + key)", $legal);
        // Without a token, the bell, My IDs and the request counts are not asked for.
        $this->assertStringContainsString("guest: {{ \$public ? '!window.EsperanzaApi.getToken()' : 'false' }},", $topbar);
        $this->assertStringContainsString('if (!guest) { $store.citizenNotifications.load(); $store.citizenNotifications.poll() }', $topbar);
        $this->assertStringContainsString('<template x-if="!guest">', $topbar);
        $this->assertStringContainsString("{{ \$public ? 'if (window.EsperanzaApi.getToken()) ' : '' }}\$store.queueCounts.load('citizen')", $this->source('resources/views/components/citizen/sidebar.blade.php'));

        // Only the legal page is public.
        foreach (glob(base_path('resources/views/citizen/*.blade.php')) as $file) {
            if (basename($file) === 'legal.blade.php') {
                continue;
            }
            $this->assertStringNotContainsString(':public="true"', file_get_contents($file), basename($file));
        }

        foreach (['/citizen/legal/privacy-policy', '/citizen/legal/terms'] as $url) {
            $this->get($url)->assertOk()->assertSee('guest: !window.EsperanzaApi.getToken(),', false);
        }
        $this->get('/citizen/dashboard')->assertOk()->assertSee('guest: false,', false);
    }

    public function test_the_six_documents_the_templates_draw_now_preview_on_track_request(): void
    {
        // They showed "Online preview isn't available" while the app drew them
        // (parity check, 2026-10-23).
        $keys = array_column(config('esperanza_document_type_lookup'), 'docKey');
        foreach (['brgy_cert_national_id', 'brgy_cert_land_property', 'brgy_business_permit', 'brgy_event_permit', 'mcro_death', 'msw_pwd_id'] as $key) {
            $this->assertContains($key, $keys, $key);
        }
        // Every document a template draws has a row here.
        $templates = $this->source('resources/views/components/citizen/barangay-certificate-preview.blade.php')
            .$this->source('resources/views/components/citizen/municipal-request-preview.blade.php');
        preg_match_all("/=== '([a-z_]+)'/", $templates, $m);
        foreach (array_unique($m[1]) as $key) {
            $this->assertContains($key, $keys, "$key has a template but no lookup row");
        }
    }

    public function test_an_unverified_resident_can_change_their_barangay_as_in_the_app(): void
    {
        $page = $this->source('resources/views/citizen/profile.blade.php');
        $this->assertStringContainsString('<select id="edit_barangay" x-model="editForm.barangay"', $page);
        $this->assertStringContainsString('<div x-show="!$store.citizenSession.account?.identityLocked">', $page);
        // Sent only while the account is not verified; the server locks it after.
        $this->assertStringContainsString("if (this.editForm.barangay && this.editForm.barangay !== (a.barangay || '')) payload.barangay = this.editForm.barangay;", $page);
        $this->assertStringContainsString("\$api.get('/barangays')", $page);
    }

    public function test_a_cancelled_dokyu_request_says_so_with_its_reason(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString('x-show="selectedRequest.status === \'Cancelled\'"', $page);
        $this->assertStringContainsString('<x-ui.t fil="Kinansela ang kahilingang ito" en="This request was cancelled" />', $page);
    }

    public function test_remember_me_decides_how_long_the_sign_in_is_kept(): void
    {
        $login = $this->source('resources/views/auth/citizen-login.blade.php');
        $this->assertStringContainsString('<input type="checkbox" x-model="remember"', $login);
        $this->assertStringContainsString('this.$store.citizenSession.login(this.identifier.trim(), this.password, this.remember)', $login);

        $client = $this->source('resources/js/api/client.js');
        $this->assertStringContainsString('export function setToken(token, { remember = true } = {})', $client);
        $this->assertStringContainsString('(remember ? localStorage : sessionStorage).setItem(TOKEN_KEY, token);', $client);
        $this->assertStringContainsString('return localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY);', $client);
        $this->assertStringContainsString('api.setToken(result.data.token, { remember });', $this->source('resources/js/app.js'));
    }

    public function test_no_control_that_does_nothing_is_left(): void
    {
        // The time zone was a one-option dropdown that saved nothing.
        $settings = $this->source('resources/views/citizen/settings.blade.php');
        $this->assertStringNotContainsString('<option>(GMT+8:00) Manila</option>', $settings);
        $this->assertStringContainsString('Every date and time is shown in Philippine time (GMT+8, Manila).', $settings);

        // The hard-coded Fiesta 2026 pop-up nothing opened; occasions replaced it.
        $this->assertFileDoesNotExist(base_path('resources/views/components/citizen/fiesta-popup.blade.php'));
        $this->assertStringNotContainsString('fiesta-popup', $this->source('resources/views/components/layouts/citizen.blade.php'));
    }
}
