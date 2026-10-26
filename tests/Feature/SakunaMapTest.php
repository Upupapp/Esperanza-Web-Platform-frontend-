<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sakuna on a real map (2026-10-26): residents pin where an emergency is,
 * staff pin incidents and centres, the Command Center draws both from the
 * coordinates saved on their records, and residents find the nearest open
 * centre from a point they chose. One pair everywhere (`lat`, `lng`), as the
 * backend keeps it; nothing placed at a guessed point.
 */
class SakunaMapTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_leaflet_is_bundled_and_loaded_only_where_a_map_is_drawn(): void
    {
        $package = json_decode($this->source('package.json'), true);
        $this->assertSame('1.9.4', $package['dependencies']['leaflet']);
        $this->assertSame('1.5.3', $package['dependencies']['leaflet.markercluster']);

        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("area(/^\\/(admin\\/sakuna|citizen\\/emergency)(\\/|$)/.test(areaPath), () => import('./sakuna-map'));", $app);
        $this->assertStringNotContainsString("from 'leaflet'", $app, 'never in the shared bundle');

        $map = $this->source('resources/js/sakuna-map.js');
        foreach (['sakunaLocationPicker', 'sakunaPinView', 'sakunaCommandMap', 'sakunaCentresMap'] as $component) {
            $this->assertStringContainsString("Alpine.data('{$component}', {$component});", $map);
        }
        // The API's own test for a pair: both, in range, and not 0, 0.
        $this->assertStringContainsString('!(a === 0 && b === 0)', $map);
        // Esperanza, Masbate, when there is nothing on the map yet.
        $this->assertStringContainsString('export const HOME = { lat: 11.7369622, lng: 124.041699 };', $map);
    }

    public function test_the_headers_let_the_page_use_location_and_reach_the_map_services(): void
    {
        require_once base_path('build/export_manifest.php');
        $headers = esperanza_response_headers('https://api.example.test', ['tiles' => 'https://tile.openstreetmap.org', 'geocoder' => 'https://nominatim.openstreetmap.org']);

        $this->assertStringContainsString('geolocation=(self)', $headers);
        $this->assertStringContainsString("img-src 'self' data: blob: https://api.example.test https://tile.openstreetmap.org", $headers);
        $this->assertStringContainsString("connect-src 'self' https://api.example.test https://nominatim.openstreetmap.org", $headers);

        // A build that turns the address search off adds no host for it.
        $off = esperanza_response_headers('https://api.example.test', ['tiles' => 'https://tile.openstreetmap.org', 'geocoder' => null]);
        $this->assertStringContainsString("connect-src 'self' https://api.example.test;", $off);
    }

    public function test_a_resident_can_pin_where_the_emergency_is_and_the_pair_is_sent(): void
    {
        $page = $this->source('resources/views/citizen/emergency.blade.php');
        $this->assertStringContainsString('<x-sakuna.location-picker target="form" bilingual subject="incident" />', $page);

        $js = $this->source('resources/js/scope-citizen.js');
        $this->assertStringContainsString("form: { type: '', severity: '', barangay: '', sitio: '', description: '', lat: null, lng: null },", $js);
        $this->assertStringContainsString('lat: pinned ? Number(f.lat) : null,', $js);
        $this->assertStringContainsString('lng: pinned ? Number(f.lng) : null,', $js);

        // Optional: saying so, and the report goes anyway.
        $map = $this->source('resources/js/sakuna-map.js');
        $this->assertStringContainsString('No map location selected. The barangay and the place you typed are still sent.', $map);
        $this->assertStringContainsString('Location access was not allowed, so nothing was pinned. Tap the map where the emergency is instead.', $map);
    }

    public function test_residents_find_centres_on_a_map_and_the_nearest_open_one(): void
    {
        $page = $this->source('resources/views/citizen/emergency.blade.php');
        $this->assertStringContainsString('x-data="sakunaCentresMap" x-effect="refresh()"', $page);
        $this->assertStringContainsString('<template x-for="c in shown" :key="c.ref">', $page);
        $this->assertStringContainsString('Distances are straight lines, not by road. The nearest is not always the safest or quickest to reach', $page);

        $map = $this->source('resources/js/sakuna-map.js');
        // Nearest is among OPEN centres with a pin, measured from a chosen point.
        $this->assertStringContainsString("if (!c.open) continue;\n                const d = this.distanceOf(c);", $map);
        $this->assertStringContainsString('return this.here && isPair(c.lat, c.lng) ? distanceKm(this.here', $map);
    }

    public function test_staff_pin_incidents_and_register_centres_on_the_map(): void
    {
        $incidents = $this->source('resources/views/admin/partials/sakuna/tab-incidents.blade.php');
        $this->assertStringContainsString('<x-sakuna.location-picker target="incidentForm" search subject="incident" />', $incidents);

        $centres = $this->source('resources/views/admin/partials/sakuna/tab-centers.blade.php');
        $this->assertStringContainsString('<x-sakuna.location-picker target="centerForm" search subject="centre" required="centerFormMode === \'create\'" />', $centres);
        $this->assertStringContainsString('|| centerNeedsPin" @click="saveCenter()"', $centres);

        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString("get centerNeedsPin() { return this.centerFormMode === 'create' && !this.hasMapPin(this.centerForm); },", $sakuna);
        $this->assertStringContainsString("await \$api.put('/admin/sakuna/incidents/' + inc.ref + '/location', {", $sakuna);
        $this->assertStringContainsString("incidentForm: { title: '', type: '', barangay: '', sitio: '', severity: '', description: '', source: '', lat: null, lng: null },", $sakuna);

        $detail = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $this->assertStringContainsString('<x-sakuna.location-picker target="incidentLocationDraft" search subject="incident" />', $detail);
        $this->assertStringContainsString("x-show=\"can('sakuna_incidents', 'edit')\"", $detail);
    }

    public function test_the_command_center_draws_a_real_map_and_counts_what_it_cannot_place(): void
    {
        $command = $this->source('resources/views/admin/partials/sakuna/tab-command.blade.php');
        $this->assertStringContainsString('x-data="sakunaCommandMap" x-effect="draw()"', $command);
        $this->assertStringNotContainsString('schematic panel', $command);
        $this->assertStringNotContainsString('groupedMarkers', $command);
        $this->assertStringContainsString("' open incidents have no map location, so they are not on the map.", $command);

        $map = $this->source('resources/js/sakuna-map.js');
        $this->assertStringContainsString('(this.commandBoard?.center_markers || []).filter((c) => isPair(c.lat, c.lng))', $map);
        $this->assertStringContainsString('incidentLayer = L.markerClusterGroup({', $map);
    }

    public function test_the_address_search_is_on_a_button_and_throttled(): void
    {
        $map = $this->source('resources/js/sakuna-map.js');
        $this->assertStringContainsString('const wait = lastGeocode + 1100 - Date.now();', $map);
        $this->assertStringContainsString("countrycodes: 'ph',", $map);
        $picker = $this->source('resources/views/components/sakuna/location-picker.blade.php');
        $this->assertStringContainsString('@keydown.enter.prevent="runSearch()"', $picker);
        $this->assertStringNotContainsString('@input', $picker, 'no search as you type');
    }
}
