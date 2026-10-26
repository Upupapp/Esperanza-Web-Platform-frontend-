# Sakuna maps: setup and limits

*2026-10-26. Applies to the website (this repo), the mobile app
(`Esperanza-Mobile-App/docs/SAKUNA_MAPS.md`) and the backend
(`esperanza-backend/docs/SAKUNA_MAPS.md`).*

## What it does

- **Residents** pin where an emergency is when they report it, by tapping the map
  or with "Use my location". The pin is optional; the barangay and the written
  place are sent either way.
- **Residents** see the open evacuation centres on a map or as a list, can filter
  by barangay, and can find the nearest open centre. The distance is measured from
  the device's GPS or from a point they tap on the map.
- **Staff** pin an incident when they report it, and can add or move the pin on an
  existing report from its detail. They must pin a new centre to register it;
  centres registered before the map can be pinned later through Edit.
- **The Command Center** plots incidents and centres at their saved coordinates,
  with filters, a legend and grouping of nearby markers. It counts the open
  incidents that have no pin.

## How the data is kept

There is one coordinate pair everywhere: `lat` and `lng`, WGS 84 decimal degrees,
stored to seven decimals.

- The backend accepts both values or neither. It rejects anything out of range
  and rejects 0, 0.
- Nothing is ever placed at a barangay's centre or estimated from an address. A
  record without a pin is listed but never plotted or measured.
- "Nearest" counts only centres that are open: status `Processing`, the rule that
  already existed. Distances are straight lines and are labelled that way.

## Configuration (build time, Vite)

| Variable | Default | Purpose |
|---|---|---|
| `VITE_MAP_TILE_URL` | `https://tile.openstreetmap.org/{z}/{x}/{y}.png` | Map tiles. `{s}` subdomains are allowed. |
| `VITE_MAP_TILE_ATTRIBUTION` | © OpenStreetMap contributors | Shown on every map; required by the tile provider. |
| `VITE_MAP_TILE_MAX_ZOOM` | `19` | Highest zoom level. |
| `VITE_GEOCODER_URL` | `https://nominatim.openstreetmap.org` | Staff address search. Set it to an empty value to turn search off; pinning still works. |

`export.php` builds the Content-Security-Policy from these same variables: the
tile host goes in `img-src` and the search host in `connect-src`. If you switch
providers, set the variables in Netlify and redeploy; no code change is needed.
`Permissions-Policy` now allows `geolocation=(self)`.

No API key is used or needed. Do not put a secret key in a `VITE_` variable,
because every `VITE_` value is published in the JavaScript bundle.

## Usage limits: read before production traffic grows

- **OpenStreetMap's tile servers** (`tile.openstreetmap.org`) are free for light
  use, but their tile usage policy forbids heavy use and needs the attribution
  (kept). A municipal portal and app are normally fine. Before a large
  campaign, or if OSM throttles or blocks the site, move to a paid or
  self-hosted tile provider and set `VITE_MAP_TILE_URL`.
- **Nominatim** (the address search) allows at most one request per second, no
  search-as-you-type, and requires identification.
  - The site searches only when the Find button is pressed, waits at least
    1.1 s between searches, and limits results to the Philippines, preferring
    Esperanza.
  - Only the staff forms offer search; residents just pin.
  - For heavy use, run your own Nominatim or use a commercial geocoder.
- **Leaflet 1.9.4** and **leaflet.markercluster 1.5.3** are bundled through npm, as
  the site's CSP requires (no CDN). They load only on `/admin/sakuna/*` and
  `/citizen/emergency`.

## Deploying

The release order matters:

1. Deploy the backend first, including migration
   `2026_10_26_000100_add_coordinates_to_sakuna_centers`.
2. Then deploy the website (one Netlify deploy).

Until the new website is live, the old one cannot register a centre, because a
new centre now requires `lat` and `lng`. Everything else on the old website keeps
working.

## Known limits

- Distances are straight lines, not road routes. The page says so, and it never
  calls the nearest centre the safest or quickest.
- Maps need a connection for tiles. If tiles fail, the page says so; the form and
  the lists still work, and pins can still be placed and saved.
- In the browser, "Use my location" needs HTTPS (Netlify serves it).
- Existing centres and incidents have no pins until staff add them.
