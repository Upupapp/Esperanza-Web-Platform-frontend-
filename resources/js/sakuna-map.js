/**
 * Sakuna on a real map (2026-10-26). One chunk, loaded only on the staff
 * Sakuna pages and the resident's Emergency page, with four components:
 *
 *   sakunaLocationPicker  pin a place: tap the map, use the device's GPS, or
 *                         (staff) search an address. Writes `lat`/`lng` into
 *                         the form it is given, the pair the API keeps.
 *   sakunaPinView         one saved place, shown on a small map.
 *   sakunaCommandMap      the Command Center's incidents and centres.
 *   sakunaCentresMap      the residents' evacuation centres: map or list,
 *                         by barangay, and the nearest open centre.
 *
 * Every point drawn is a coordinate someone saved (backend Coordinates). A
 * record without one is listed, never placed at its barangay's middle.
 *
 * The map tiles and the address search are public OpenStreetMap services by
 * default; both are build settings (VITE_MAP_TILE_URL, VITE_GEOCODER_URL, see
 * docs/SAKUNA_MAPS.md), and pinning works without the search at all.
 */
import L from './leaflet-global';
import 'leaflet.markercluster';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import '../css/sakuna-map.css';

const env = import.meta.env;

// Esperanza, Masbate: the town (OpenStreetMap node 198506055). Where a map
// opens when there is nothing on it yet.
export const HOME = { lat: 11.7369622, lng: 124.041699 };
const HOME_ZOOM = 13;
const PIN_ZOOM = 17;

const TILES = {
    url: env.VITE_MAP_TILE_URL || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: env.VITE_MAP_TILE_ATTRIBUTION
        || '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    maxZoom: Number(env.VITE_MAP_TILE_MAX_ZOOM) || 19,
};
// Empty turns the staff address search off; pinning still works.
const GEOCODER = String(env.VITE_GEOCODER_URL ?? 'https://nominatim.openstreetmap.org').replace(/\/+$/, '');

// 'Rejected' = marked Not valid: closed too, as on the Incidents tab's Active count (sweep, 18 Nov).
const CLOSED_INCIDENT = ['Completed', 'Archived', 'Rejected'];
const SEVERITY_TONE = { Critical: 'bg-rose-500', High: 'bg-orange-500', Medium: 'bg-amber-400', Low: 'bg-slate-400' };
const SEVERITY_RANK = { Critical: 0, High: 1, Medium: 2, Low: 3 };

// lucide "house", drawn inline so a marker needs no icon pass.
const HOUSE_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>';

// ----------------------------------------------------------------- helpers

/** Both numbers, in range, and not 0, 0 - the same test the API applies. */
export function isPair(lat, lng) {
    if (lat === null || lng === null || lat === undefined || lng === undefined || lat === '' || lng === '') return false;
    const a = Number(lat);
    const b = Number(lng);
    return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a) <= 90 && Math.abs(b) <= 180 && !(a === 0 && b === 0);
}

/** Seven decimals, as the database keeps them (about a centimetre). */
export const round7 = (v) => Math.round(Number(v) * 1e7) / 1e7;

/** Straight-line (great-circle) distance in kilometres. Not a road route. */
export function distanceKm(a, b) {
    const R = 6371.0088;
    const rad = (d) => (d * Math.PI) / 180;
    const dLat = rad(b.lat - a.lat);
    const dLng = rad(b.lng - a.lng);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
}

export function distanceText(km) {
    if (km < 1) return Math.max(10, Math.round((km * 1000) / 10) * 10) + ' m';
    return (km < 10 ? km.toFixed(1) : String(Math.round(km))) + ' km';
}

export const coordText = (lat, lng) => Number(lat).toFixed(5) + ', ' + Number(lng).toFixed(5);

export const osmUrl = (lat, lng) => 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=' + PIN_ZOOM + '/' + lat + '/' + lng;

export const directionsUrl = (lat, lng) => 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(lat + ',' + lng);

const severityKey = (s) => (s === 'Moderate' ? 'Medium' : s);

/**
 * A Leaflet map in `el` with the tile layer. `onReady` fires on the first
 * tile, `onFailed` when none load (offline, blocked, or the tile service
 * refusing): the page says so and the form keeps working.
 */
function baseMap(el, { center = HOME, zoom = HOME_ZOOM, interactive = true, onReady, onFailed } = {}) {
    const map = L.map(el, {
        center: [center.lat, center.lng],
        zoom,
        zoomControl: interactive,
        dragging: interactive,
        touchZoom: interactive,
        doubleClickZoom: interactive,
        boxZoom: interactive,
        keyboard: interactive,
        // The page scrolls past a map; the wheel zooms it once it is clicked.
        scrollWheelZoom: false,
        tapTolerance: 15,
    });
    if (interactive) {
        map.on('focus click', () => map.scrollWheelZoom.enable());
        map.on('blur mouseout', () => map.scrollWheelZoom.disable());
    }

    let loaded = 0;
    let failed = 0;
    let settled = false;
    const fail = () => { if (!settled && !loaded) { settled = true; onFailed?.(); } };
    const tiles = L.tileLayer(TILES.url, { maxZoom: TILES.maxZoom, attribution: TILES.attribution });
    tiles.on('tileload', () => {
        loaded++;
        if (!settled) { settled = true; onReady?.(); }
    });
    tiles.on('tileerror', () => { if (++failed >= 4) fail(); });
    tiles.addTo(map);
    const timer = setTimeout(fail, 12000);
    map.on('unload', () => clearTimeout(timer));
    map._sakunaTiles = tiles;
    map._sakunaRetry = () => { settled = false; failed = 0; tiles.redraw(); setTimeout(fail, 12000); };

    // Leaflet measures its box once; a map in a modal, a tab or a panel that
    // opens later is measured again when it is shown.
    if ('ResizeObserver' in window) {
        const ro = new ResizeObserver(() => map.invalidateSize());
        ro.observe(el);
        map.on('unload', () => ro.disconnect());
    }
    return map;
}

function pinIcon(tone = 'text-rose-600') {
    return L.divIcon({
        className: '',
        html: '<svg class="sakuna-pin ' + tone + '" viewBox="0 0 34 44" aria-hidden="true"><path fill="currentColor" stroke="#fff" stroke-width="2.5" d="M17 2C9.3 2 3 8.1 3 15.7 3 26 17 42 17 42s14-16 14-26.3C31 8.1 24.7 2 17 2z"/><circle cx="17" cy="15.5" r="5.5" fill="#fff"/></svg>',
        iconSize: [34, 44],
        iconAnchor: [17, 42],
        popupAnchor: [0, -38],
    });
}

function incidentIcon(severity) {
    const key = severityKey(severity);
    const tone = (SEVERITY_TONE[key] || 'bg-slate-400') + (key === 'Critical' ? ' sakuna-critical' : '');
    return L.divIcon({ className: '', html: '<span class="sakuna-dot ' + tone + '"></span>', iconSize: [18, 18], iconAnchor: [9, 9], popupAnchor: [0, -10] });
}

function centreIcon(open) {
    return L.divIcon({
        className: '',
        html: '<span class="sakuna-centre ' + (open ? 'bg-teal-600' : 'bg-slate-500') + '">' + HOUSE_SVG + '</span>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        popupAnchor: [0, -14],
    });
}

const hereIcon = () => L.divIcon({ className: '', html: '<span class="sakuna-here"></span>', iconSize: [16, 16], iconAnchor: [8, 8] });

/** A cluster takes the colour of the worst severity inside it. */
function clusterIcon(cluster) {
    const worst = cluster.getAllChildMarkers()
        .map((m) => m.options.severity)
        .sort((a, b) => (SEVERITY_RANK[a] ?? 9) - (SEVERITY_RANK[b] ?? 9))[0];
    // White figures need the darker shade of each severity to read (4.5:1);
    // High keeps a true orange with dark figures, so it never reads as Critical.
    const tone = ({ Critical: 'bg-rose-700 sakuna-critical', High: 'bg-orange-500 sakuna-cluster-dark', Medium: 'bg-amber-700', Low: 'bg-slate-600' })[worst] || 'bg-slate-600';
    return L.divIcon({ className: '', html: '<span class="sakuna-cluster ' + tone + '">' + cluster.getChildCount() + '</span>', iconSize: [36, 36], iconAnchor: [18, 18] });
}

/** Popup content as nodes, never as HTML: every line is data someone typed. */
function popup(lines, action) {
    const box = document.createElement('div');
    box.className = 'min-w-[180px] max-w-[240px]';
    lines.filter(([text]) => text).forEach(([text, cls]) => {
        const p = document.createElement('p');
        p.textContent = text;
        p.className = cls || 'text-slate-600';
        box.appendChild(p);
    });
    if (action) {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = action.label;
        b.className = 'mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 px-2.5 py-1.5 rounded-xl';
        b.addEventListener('click', action.run);
        box.appendChild(b);
    }
    return box;
}

let lastGeocode = 0;

/**
 * Address search for staff, on the button only (never as-you-type: the
 * public Nominatim service allows one request a second and no autocomplete),
 * biased to Esperanza and kept to the Philippines.
 */
async function geocode(query) {
    const wait = lastGeocode + 1100 - Date.now();
    if (wait > 0) await new Promise((r) => setTimeout(r, wait));
    lastGeocode = Date.now();
    const params = new URLSearchParams({
        q: query,
        format: 'jsonv2',
        limit: '5',
        countrycodes: 'ph',
        viewbox: '123.85,11.95,124.25,11.55',
        bounded: '0',
        'accept-language': 'en',
    });
    const res = await fetch(GEOCODER + '/search?' + params.toString(), { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('Address search answered ' + res.status);
    const rows = await res.json();
    return (Array.isArray(rows) ? rows : [])
        .map((r) => ({ name: String(r.display_name || ''), lat: Number(r.lat), lng: Number(r.lon) }))
        .filter((r) => r.name && isPair(r.lat, r.lng));
}

function locate() {
    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) { reject({ code: 'unsupported' }); return; }
        navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    });
}

const gpsProblem = (err) => (err?.code === 'unsupported' ? 'unsupported' : err?.code === 1 ? 'denied' : err?.code === 3 ? 'timeout' : 'unavailable');

// --------------------------------------------------------- location picker

/**
 * `target` returns the form object that keeps `lat` and `lng`; the picker
 * writes both, or clears both. Optional unless `required` returns true (a
 * centre being registered, not one being edited).
 */
function sakunaLocationPicker({ target, search = false, bilingual = false, required = () => false, subject = 'place' } = {}) {
    let map = null;
    let pin = null;
    // The picker's own element: a search result is chosen from inside its
    // x-for row, where Alpine's $refs are the row's and hold no map.
    let root = null;
    return {
        target,
        lat: null,
        lng: null,
        mapOpen: false,
        mapState: 'idle',
        gps: 'idle',
        accuracy: null,
        canSearch: !!search && !!GEOCODER,
        query: '',
        results: [],
        searchState: 'idle',

        init() { root = this.$el; },
        t(en, fil) { return bilingual && this.$store.lang?.current !== 'en' ? fil : en; },
        destroy() { map?.remove(); map = null; pin = null; },

        get hasPin() { return isPair(this.lat, this.lng); },
        get isRequired() { return !!required(); },
        get coords() { return this.hasPin ? coordText(this.lat, this.lng) : ''; },
        get osm() { return this.hasPin ? osmUrl(this.lat, this.lng) : '#'; },
        get emptyText() {
            if (this.isRequired) return this.t('Not pinned yet. A new centre needs its place on the map so residents can find it.', 'Hindi pa naka-pin. Kailangang nasa mapa ang bagong center para mahanap ito ng mga residente.');
            if (subject === 'centre') return this.t('Not on the map yet. Residents still see it in the list; pin it so it shows on the map and in "nearest centre".', 'Wala pa sa mapa. Makikita pa rin ito sa listahan; i-pin para lumabas sa mapa at sa "pinakamalapit na center".');
            return this.t('No map location selected. The barangay and the place you typed are still sent.', 'Walang napiling lokasyon sa mapa. Ipapadala pa rin ang barangay at ang lugar na isinulat mo.');
        },
        get failedText() {
            if (this.isRequired) return this.t('A new centre needs its place on the map. Try again once the map loads.', 'Kailangang nasa mapa ang bagong center. Subukang muli kapag na-load na ang mapa.');
            if (subject === 'centre') return this.t('You can still save the other details and pin it later.', 'Maise-save pa rin ang ibang detalye at mai-pin ito mamaya.');
            return this.t('You can still send this without a pin: describe the place in the field above.', 'Maipapadala mo pa rin ito nang walang pin: ilarawan ang lugar sa field sa itaas.');
        },

        /** Follows the form: a reset clears the pin, an edit brings one in. */
        follow() {
            const f = this.target?.() || {};
            const lat = f.lat ?? null;
            const lng = f.lng ?? null;
            if (isPair(lat, lng)) {
                if (Number(lat) !== this.lat || Number(lng) !== this.lng) {
                    this.lat = Number(lat);
                    this.lng = Number(lng);
                    this.placePin(true);
                }
            } else if (this.lat !== null || this.lng !== null) {
                this.lat = null;
                this.lng = null;
                this.gps = 'idle';
                this.accuracy = null;
                if (pin) { pin.remove(); pin = null; }
            }
        },

        setPin(lat, lng, zoom = false) {
            const a = round7(lat);
            const b = round7(lng);
            if (!isPair(a, b)) return;
            this.lat = a;
            this.lng = b;
            const f = this.target?.();
            if (f) { f.lat = a; f.lng = b; }
            this.placePin(zoom);
        },

        placePin(zoom) {
            if (!map || !this.hasPin) return;
            const at = [this.lat, this.lng];
            if (!pin) {
                pin = L.marker(at, {
                    icon: pinIcon(subject === 'centre' ? 'text-teal-600' : 'text-rose-600'),
                    draggable: true,
                    autoPan: true,
                    keyboard: true,
                    title: this.t('Drag to move the pin', 'I-drag para ilipat ang pin'),
                    alt: this.t('Pinned location', 'Naka-pin na lokasyon'),
                }).addTo(map);
                pin.on('dragend', () => { const p = pin.getLatLng(); this.setPin(p.lat, p.lng); });
            } else {
                pin.setLatLng(at);
            }
            if (zoom) map.setView(at, typeof zoom === 'number' ? zoom : Math.max(map.getZoom(), PIN_ZOOM));
            else if (!map.getBounds().contains(at)) map.panTo(at);
        },

        clearPin() {
            this.lat = null;
            this.lng = null;
            const f = this.target?.();
            if (f) { f.lat = null; f.lng = null; }
            if (pin) { pin.remove(); pin = null; }
            this.gps = 'idle';
            this.accuracy = null;
        },

        openMap() {
            this.mapOpen = true;
            return new Promise((resolve) => this.$nextTick(() => { this.ensureMap(); resolve(); }));
        },

        ensureMap() {
            if (map) { map.invalidateSize(); return; }
            const el = root?.querySelector('[x-ref="map"]');
            if (!el) return;
            this.mapState = 'loading';
            map = baseMap(el, {
                center: this.hasPin ? { lat: this.lat, lng: this.lng } : HOME,
                zoom: this.hasPin ? PIN_ZOOM : HOME_ZOOM,
                onReady: () => { this.mapState = 'ready'; },
                onFailed: () => { this.mapState = 'failed'; },
            });
            map.on('click', (e) => this.setPin(e.latlng.lat, e.latlng.lng));
            this.placePin(false);
        },

        retryMap() {
            if (!map) return;
            this.mapState = 'loading';
            map._sakunaRetry();
        },

        async useMyLocation() {
            if (this.gps === 'locating') return;
            this.gps = 'locating';
            try {
                const pos = await locate();
                this.gps = 'found';
                this.accuracy = Math.round(pos.coords.accuracy || 0) || null;
                await this.openMap();
                this.setPin(pos.coords.latitude, pos.coords.longitude, PIN_ZOOM);
            } catch (err) {
                this.gps = gpsProblem(err);
                await this.openMap();
            }
        },

        // Whole sentences, so the app reads the same Filipino (its tr()
        // dictionary is built from these pairs).
        get gpsMessage() {
            const incident = subject === 'incident';
            const within = this.accuracy ? ' (±' + this.accuracy + ' m)' : '';
            switch (this.gps) {
                case 'locating': return this.t('Finding your location…', 'Hinahanap ang iyong lokasyon…');
                case 'found': return (incident
                    ? this.t('Pinned where your device is. If the emergency is somewhere else, tap that spot on the map.', 'Naka-pin kung nasaan ang iyong device. Kung nasa ibang lugar ang emergency, i-tap ang lugar na iyon sa mapa.')
                    : this.t('Pinned where your device is. If it is somewhere else, tap that spot on the map.', 'Naka-pin kung nasaan ang iyong device. Kung nasa ibang lugar ito, i-tap ang lugar na iyon sa mapa.')) + within;
                case 'denied': return incident
                    ? this.t('Location access was not allowed, so nothing was pinned. Tap the map where the emergency is instead.', 'Hindi pinayagan ang lokasyon, kaya walang na-pin. I-tap na lang ang mapa kung saan ang emergency.')
                    : this.t('Location access was not allowed, so nothing was pinned. Tap the map where it is instead.', 'Hindi pinayagan ang lokasyon, kaya walang na-pin. I-tap na lang ang mapa kung saan ito.');
                case 'timeout':
                case 'unavailable': return incident
                    ? this.t('Your device could not find its location. Tap the map where the emergency is instead.', 'Hindi mahanap ng iyong device ang lokasyon nito. I-tap na lang ang mapa kung saan ang emergency.')
                    : this.t('Your device could not find its location. Tap the map where it is instead.', 'Hindi mahanap ng iyong device ang lokasyon nito. I-tap na lang ang mapa kung saan ito.');
                case 'unsupported': return incident
                    ? this.t('This browser cannot share its location. Tap the map where the emergency is instead.', 'Hindi maibahagi ng browser na ito ang lokasyon. I-tap na lang ang mapa kung saan ang emergency.')
                    : this.t('This browser cannot share its location. Tap the map where it is instead.', 'Hindi maibahagi ng browser na ito ang lokasyon. I-tap na lang ang mapa kung saan ito.');
                default: return '';
            }
        },
        get gpsTrouble() { return ['denied', 'timeout', 'unavailable', 'unsupported'].includes(this.gps); },

        async runSearch() {
            const q = this.query.trim();
            if (!this.canSearch || q.length < 3 || this.searchState === 'searching') return;
            this.searchState = 'searching';
            this.results = [];
            try {
                this.results = await geocode(q);
                this.searchState = this.results.length ? 'found' : 'empty';
            } catch {
                this.searchState = 'failed';
            }
        },

        async choose(r) {
            this.results = [];
            this.searchState = 'chosen';
            await this.openMap();
            this.setPin(r.lat, r.lng, PIN_ZOOM);
        },
    };
}

// ------------------------------------------------------------- pin view

/** One saved place on a small map; `point` returns { lat, lng } or nothing. */
function sakunaPinView({ point, tone = 'incident', severity = null } = {}) {
    let map = null;
    let marker = null;
    return {
        point,
        viewState: 'idle',
        destroy() { map?.remove(); map = null; marker = null; },
        show() {
            const p = this.point?.() || {};
            const ok = isPair(p.lat, p.lng);
            if (!ok) { if (marker) { marker.remove(); marker = null; } return; }
            this.$nextTick(() => {
                const at = [Number(p.lat), Number(p.lng)];
                if (!map) {
                    this.viewState = 'loading';
                    map = baseMap(this.$refs.map, {
                        center: { lat: at[0], lng: at[1] },
                        zoom: 16,
                        onReady: () => { this.viewState = 'ready'; },
                        onFailed: () => { this.viewState = 'failed'; },
                    });
                }
                // An incident's pin takes its severity's colour (critique 2026-11-03).
                const sev = { Critical: 'text-rose-600', High: 'text-orange-600', Medium: 'text-amber-500', Low: 'text-slate-500' }[severity?.()] || 'text-rose-600';
                const icon = tone === 'centre' ? centreIcon(true) : pinIcon(sev);
                if (!marker) marker = L.marker(at, { icon, keyboard: false }).addTo(map);
                else { marker.setLatLng(at); marker.setIcon(icon); }
                map.setView(at, 16);
                map.invalidateSize();
            });
        },
    };
}

// ------------------------------------------------------- command center

/**
 * Lives inside the Command Center tab and reads its `commandBoard`. Opening
 * an incident uses the tab's own detail modal; a centre opens on the
 * Evacuation Centers tab.
 */
function sakunaCommandMap() {
    let map = null;
    let incidentLayer = null;
    let centreLayer = null;
    let fitted = false;
    return {
        mapState: 'loading',
        statusFilter: 'open',
        severityFilter: '',
        typeFilter: '',
        showCentres: true,

        init() { this.$nextTick(() => this.build()); },
        destroy() { map?.remove(); map = null; },

        build() {
            if (map || !this.$refs.map) return;
            map = baseMap(this.$refs.map, {
                onReady: () => { this.mapState = 'ready'; },
                onFailed: () => { this.mapState = 'failed'; },
            });
            incidentLayer = L.markerClusterGroup({
                showCoverageOnHover: false,
                maxClusterRadius: 44,
                spiderfyOnMaxZoom: true,
                iconCreateFunction: clusterIcon,
            });
            centreLayer = L.layerGroup();
            map.addLayer(centreLayer);
            map.addLayer(incidentLayer);
            this.draw();
        },

        get boardMarkers() { return (this.commandBoard?.markers || []).filter((m) => isPair(m.lat, m.lng)); },
        get incidentPoints() {
            return this.boardMarkers.filter((m) => {
                if (this.statusFilter === 'open' && CLOSED_INCIDENT.includes(m.status)) return false;
                if (!['open', 'all'].includes(this.statusFilter) && m.status !== this.statusFilter) return false;
                if (this.severityFilter && severityKey(m.severity) !== this.severityFilter) return false;
                if (this.typeFilter && m.type !== this.typeFilter) return false;
                return true;
            });
        },
        get centrePoints() {
            return this.showCentres ? (this.commandBoard?.center_markers || []).filter((c) => isPair(c.lat, c.lng)) : [];
        },
        get mapTypes() { return [...new Set(this.boardMarkers.map((m) => m.type).filter(Boolean))].sort(); },
        get mapStatuses() { return [...new Set(this.boardMarkers.map((m) => m.status).filter(Boolean))]; },
        get unmapped() { return Number(this.commandBoard?.counts?.incidents_unmapped) || 0; },
        get nothingToPlot() { return !this.incidentPoints.length && !this.centrePoints.length; },

        draw() {
            // Read first, so the effect follows the data even before the map exists.
            const incidents = this.incidentPoints;
            const centres = this.centrePoints;
            const settled = !this.commandLoading && !this.commandFailed;
            if (!map) return;
            incidentLayer.clearLayers();
            centreLayer.clearLayers();
            incidentLayer.addLayers(incidents.map((m) => {
                const label = (m.title || m.ref) + ', ' + (m.severity || 'severity not set') + ' severity';
                const marker = L.marker([m.lat, m.lng], {
                    icon: incidentIcon(m.severity),
                    title: label,
                    alt: label,
                    riseOnHover: true,
                    severity: severityKey(m.severity),
                });
                marker.on('add', () => marker.getElement()?.setAttribute('aria-label', label));
                marker.bindPopup(() => this.incidentPopup(m));
                return marker;
            }));
            centres.forEach((c) => {
                const label = c.name + ', evacuation center, ' + (c.open ? 'open' : 'not open');
                const marker = L.marker([c.lat, c.lng], { icon: centreIcon(c.open), title: label, alt: label, zIndexOffset: -200 });
                marker.on('add', () => marker.getElement()?.setAttribute('aria-label', label));
                marker.bindPopup(() => this.centrePopup(c)).addTo(centreLayer);
            });
            if (settled && !fitted) {
                fitted = true;
                // The board shows itself as it settles: measure once it has a size.
                setTimeout(() => this.fit(), 80);
            }
        },

        fit() {
            if (!map) return;
            map.invalidateSize();
            const points = [...this.incidentPoints, ...this.centrePoints].map((p) => [p.lat, p.lng]);
            if (!points.length) { map.setView([HOME.lat, HOME.lng], HOME_ZOOM); return; }
            map.fitBounds(L.latLngBounds(points), { padding: [36, 36], maxZoom: 15 });
        },

        retryMap() {
            if (!map) return;
            this.mapState = 'loading';
            map._sakunaRetry();
        },

        incidentPopup(m) {
            const when = m.reported ? new Date(m.reported).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';
            return popup([
                [m.title || m.ref, 'font-semibold text-slate-800 text-[13px]'],
                [[m.type, severityKey(m.severity)].filter(Boolean).join(' · ')],
                ['Status: ' + (this.incidentStatusLabel ? this.incidentStatusLabel(m.status) : m.status)],
                [[m.sitio, m.barangay ? 'Brgy. ' + m.barangay : ''].filter(Boolean).join(', ')],
                [[m.ref, when].filter(Boolean).join(' · '), 'text-slate-500 font-mono text-[11px]'],
                [['Citizen Portal', 'Mobile App'].includes(m.source) ? 'Reported by a resident' : '', 'text-sky-800 text-[11px] font-semibold'],
            ], {
                label: 'Open incident',
                run: () => {
                    map?.closePopup();
                    this.openIncidentDetail({ available_verbs: [], ...m });
                },
            });
        },

        centrePopup(c) {
            const people = c.capacity > 0 ? c.individuals + ' of ' + c.capacity + ' people' : c.individuals + ' people';
            const may = this.tabAllowed ? this.tabAllowed('evacuation-centers') : false;
            return popup([
                [c.name, 'font-semibold text-slate-800 text-[13px]'],
                [c.open ? 'Open, taking evacuees' : 'Not open (' + c.status + ')', c.open ? 'text-emerald-700 font-semibold' : 'text-slate-500 font-semibold'],
                [[c.address, c.barangay ? 'Brgy. ' + c.barangay : ''].filter(Boolean).join(', ')],
                [c.open ? 'Now here: ' + people + (c.families ? ', ' + c.families + ' families' : '') : ''],
            ], may && this.tabUrls?.['evacuation-centers'] ? {
                label: 'Open centre',
                run: () => { window.location.href = this.tabUrls['evacuation-centers'] + '?center=' + encodeURIComponent(c.ref); },
            } : null);
        },
    };
}

// -------------------------------------------------- residents' centres

/**
 * Wraps the Emergency page's centres (`centres` from citizenEmergency, each
 * with `lat`/`lng` or none). Distances are straight lines from a point the
 * resident chose: the phone's GPS or a tap on the map, never a barangay name.
 * "Nearest" is among open centres only.
 */
function sakunaCentresMap() {
    let map = null;
    let layer = null;
    let hereMarker = null;
    let fitted = false;
    return {
        view: 'list',
        barangayFilter: '',
        here: null,
        hereState: 'idle',
        accuracy: null,
        mapState: 'idle',

        destroy() { map?.remove(); map = null; },

        get placed() { return (this.centres || []).filter((c) => isPair(c.lat, c.lng)); },
        get centreBarangays() { return [...new Set((this.centres || []).map((c) => c.barangay).filter(Boolean))].sort((a, b) => a.localeCompare(b)); },
        distanceOf(c) {
            return this.here && isPair(c.lat, c.lng) ? distanceKm(this.here, { lat: Number(c.lat), lng: Number(c.lng) }) : null;
        },
        get shown() {
            const rows = (this.centres || []).filter((c) => !this.barangayFilter || c.barangay === this.barangayFilter);
            if (!this.here) return rows;
            // With a point to measure from: open centres by distance first,
            // then the rest in the page's own order.
            return rows
                .map((c, i) => ({ c, i, d: this.distanceOf(c) }))
                .sort((a, b) => ((b.c.open && a.d !== null) - (a.c.open && a.d !== null))
                    || ((a.d ?? Infinity) - (b.d ?? Infinity))
                    || (a.i - b.i))
                .map((x) => x.c);
        },
        get nearest() {
            if (!this.here) return null;
            let best = null;
            for (const c of this.centres || []) {
                if (!c.open) continue;
                const d = this.distanceOf(c);
                if (d !== null && (best === null || d < best.d)) best = { c, d };
            }
            return best;
        },
        distanceLabel(c) {
            const d = this.distanceOf(c);
            return d === null ? '' : distanceText(d) + ' ' + this.t('away, in a straight line', 'ang layo, sa tuwid na linya');
        },

        async findNearest() {
            if (this.hereState === 'locating') return;
            this.hereState = 'locating';
            try {
                const pos = await locate();
                this.accuracy = Math.round(pos.coords.accuracy || 0) || null;
                this.setHere(pos.coords.latitude, pos.coords.longitude, 'gps');
            } catch (err) {
                this.hereState = gpsProblem(err);
            }
        },
        pickOnMap() {
            this.hereState = 'picking';
            this.showMap();
        },
        setHere(lat, lng, source) {
            this.here = { lat: round7(lat), lng: round7(lng), source };
            this.hereState = source;
            this.drawHere();
            if (map) this.fitAll();
        },
        clearHere() {
            this.here = null;
            this.hereState = 'idle';
            this.accuracy = null;
            if (hereMarker) { hereMarker.remove(); hereMarker = null; }
        },
        get hereMessage() {
            switch (this.hereState) {
                case 'locating': return this.t('Finding your location…', 'Hinahanap ang iyong lokasyon…');
                case 'gps': return this.t('Measured from where your device is.', 'Sinukat mula kung nasaan ang iyong device.') + (this.accuracy ? ' (±' + this.accuracy + ' m)' : '');
                case 'map': return this.t('Measured from the point you chose on the map.', 'Sinukat mula sa puntong pinili mo sa mapa.');
                case 'picking': return this.t('Tap the map where you are.', 'I-tap ang mapa kung nasaan ka.');
                case 'denied': return this.t('Location access was not allowed. Choose your place on the map, or pick your barangay below.', 'Hindi pinayagan ang lokasyon. Piliin ang iyong lugar sa mapa, o piliin ang iyong barangay sa ibaba.');
                case 'timeout':
                case 'unavailable': return this.t('Your device could not find its location. Choose your place on the map, or pick your barangay below.', 'Hindi mahanap ng iyong device ang lokasyon nito. Piliin ang iyong lugar sa mapa, o piliin ang iyong barangay sa ibaba.');
                case 'unsupported': return this.t('This browser cannot share its location. Choose your place on the map, or pick your barangay below.', 'Hindi maibahagi ng browser na ito ang lokasyon. Piliin ang iyong lugar sa mapa, o piliin ang iyong barangay sa ibaba.');
                default: return '';
            }
        },
        get hereTrouble() { return ['denied', 'timeout', 'unavailable', 'unsupported'].includes(this.hereState); },

        showMap() {
            this.view = 'map';
            this.$nextTick(() => this.ensureMap());
        },
        ensureMap() {
            if (!this.placed.length) return;
            if (map) { map.invalidateSize(); this.drawCentres(); return; }
            this.mapState = 'loading';
            map = baseMap(this.$refs.map, {
                onReady: () => { this.mapState = 'ready'; },
                onFailed: () => { this.mapState = 'failed'; },
            });
            layer = L.layerGroup().addTo(map);
            map.on('click', (e) => {
                if (this.hereState !== 'picking') return;
                this.setHere(e.latlng.lat, e.latlng.lng, 'map');
            });
            this.drawCentres();
            this.drawHere();
        },
        retryMap() {
            if (!map) return;
            this.mapState = 'loading';
            map._sakunaRetry();
        },
        drawCentres() {
            const rows = this.shown.filter((c) => isPair(c.lat, c.lng));
            if (!map) return;
            layer.clearLayers();
            rows.forEach((c) => {
                L.marker([Number(c.lat), Number(c.lng)], { icon: centreIcon(c.open), title: c.name, alt: c.name })
                    .bindPopup(() => popup([
                        [c.name, 'font-semibold text-slate-800 text-[13px]'],
                        [c.open ? this.t('Open now', 'Bukas ngayon') : this.t('Not open', 'Hindi bukas'), c.open ? 'text-emerald-700 font-semibold' : 'text-slate-500 font-semibold'],
                        [[c.address, c.barangay ? 'Brgy. ' + c.barangay : ''].filter(Boolean).join(', ')],
                        [this.distanceLabel(c), 'text-slate-700'],
                    ], { label: this.t('Details', 'Mga detalye'), run: () => { map?.closePopup(); this.openCentre(c); } }))
                    .addTo(layer);
            });
            if (!fitted) { fitted = true; setTimeout(() => this.fitAll(), 60); }
        },
        drawHere() {
            if (!map) return;
            if (!this.here) { if (hereMarker) { hereMarker.remove(); hereMarker = null; } return; }
            const at = [this.here.lat, this.here.lng];
            if (!hereMarker) hereMarker = L.marker(at, { icon: hereIcon(), keyboard: false, zIndexOffset: 500, title: this.t('You', 'Ikaw') }).addTo(map);
            else hereMarker.setLatLng(at);
        },
        fitAll() {
            if (!map) return;
            map.invalidateSize();
            const pts = this.shown.filter((c) => isPair(c.lat, c.lng)).map((c) => [Number(c.lat), Number(c.lng)]);
            if (this.here) pts.push([this.here.lat, this.here.lng]);
            if (!pts.length) { map.setView([HOME.lat, HOME.lng], HOME_ZOOM); return; }
            if (pts.length === 1) { map.setView(pts[0], 15); return; }
            map.fitBounds(L.latLngBounds(pts), { padding: [32, 32], maxZoom: 15 });
        },
        /** Redrawn whenever the list, the filter or the resident's point changes. */
        refresh() {
            void this.shown;
            void this.here;
            const filter = this.barangayFilter;
            if (!map) return;
            this.drawCentres();
            this.drawHere();
            if (filter !== this.lastFilter) { this.lastFilter = filter; this.fitAll(); }
        },
        lastFilter: '',
    };
}

export function register(Alpine) {
    Alpine.data('sakunaLocationPicker', sakunaLocationPicker);
    Alpine.data('sakunaPinView', sakunaPinView);
    Alpine.data('sakunaCommandMap', sakunaCommandMap);
    Alpine.data('sakunaCentresMap', sakunaCentresMap);
    window.sakunaGeo = { isPair, distanceKm, distanceText, coordText, osmUrl, directionsUrl };
}
