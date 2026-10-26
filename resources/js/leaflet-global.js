// leaflet.markercluster extends the global `L` when it loads, so Leaflet is
// put on window first. Imported before the plugin; ES modules run in import
// order, and the bundle keeps that order.
import L from 'leaflet';

window.L = window.L || L;

export default L;
