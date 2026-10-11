/**
 * The one HTTP client.
 *
 * Before TAB 05 there was exactly one axios reference in this codebase — the
 * window.axios assignment in bootstrap.js — and no page made a network call.
 * Everything rendered build-time constants.
 *
 * Read this before adding a fetch() anywhere:
 *
 *   In production there is NO PHP. netlify.toml renders every route to static
 *   HTML at build time and publishes flat files to a CDN. So this client runs
 *   in the browser, against a DIFFERENT origin, after paint. There is no
 *   server-side fetching available to fall back on.
 *
 * Consequences that are settled here rather than per page:
 *   - base URL comes from build-time config (VITE_API_BASE_URL)
 *   - every request carries Accept, Content-Type, a request id and a timeout
 *   - every failure becomes exactly one ApiError (see errors.js)
 *   - 401/403/422/5xx are mapped in ONE place, not per caller
 *   - in-flight requests can be cancelled by group, for navigation and for
 *     rapid filter changes on the admin lists
 *
 * OPEN, and owed to the backend before it ships:
 *   - the CORS origin allowlist
 *   - whether the token rides in an Authorization header (assumed here) or in
 *     a cookie, which would need withCredentials and a preflight budget
 */

import axios from 'axios';
import { ApiError, toApiError } from './errors';

const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';
const TIMEOUT_MS = Number(import.meta.env.VITE_API_TIMEOUT_MS || 15000);
const TOKEN_KEY = 'esperanza_token';

/** Named groups of in-flight requests, so navigation can cancel a whole screen. */
const controllers = new Map();

function requestId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    return `req-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
}

export function getToken() {
    try {
        return localStorage.getItem(TOKEN_KEY);
    } catch {
        return null;
    }
}

export function setToken(token) {
    try {
        if (token) {
            localStorage.setItem(TOKEN_KEY, token);
        } else {
            localStorage.removeItem(TOKEN_KEY);
        }
    } catch {
        // Private mode with storage disabled: the request simply goes unauthenticated
        // and the server rejects it. Failing loudly here would be worse.
    }
}

const http = axios.create({
    baseURL: BASE_URL,
    timeout: TIMEOUT_MS,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    config.headers['X-Request-Id'] = requestId();

    const token = getToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // A FormData body (real file uploads: citizen/profile.blade.php's ID
    // verification, papeles, community post images) must NOT carry the
    // fixed 'application/json' content-type this instance defaults to.
    // axios's own default transformRequest checks the header first and, if
    // it is already 'application/json', JSON.stringifies the FormData
    // instead of sending it as multipart -- which would silently drop the
    // file. Deleting the header here lets axios/the browser set
    // 'multipart/form-data; boundary=...' itself, the only thing a
    // multipart request can correctly carry.
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    return config;
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        const apiError = toApiError(error);

        // The single place 401/403 are interpreted. Pages listen for these
        // rather than each deciding what an expired token means.
        if (apiError.isAuth) {
            window.dispatchEvent(new CustomEvent('api:unauthorized', { detail: { error: apiError } }));
        } else if (apiError.isForbidden) {
            window.dispatchEvent(new CustomEvent('api:forbidden', { detail: { error: apiError } }));
        }

        // Rethrow, always. A caught exception that resolves to an empty list is
        // the worst outcome available: the officer believes there is no data.
        return Promise.reject(apiError);
    },
);

/**
 * Cancel every in-flight request in a group and start a fresh one.
 * Use a group per screen, and per rapidly-changing filter set.
 */
export function cancelGroup(group) {
    const existing = controllers.get(group);
    if (existing) {
        existing.abort();
        controllers.delete(group);
    }
}

function signalFor(group) {
    if (!group) {
        return undefined;
    }

    cancelGroup(group);
    const controller = new AbortController();
    controllers.set(group, controller);

    return controller.signal;
}

async function send(method, url, { data, params, group, ...rest } = {}) {
    try {
        const response = await http.request({
            method,
            url,
            data,
            params,
            signal: signalFor(group),
            ...rest,
        });

        return response.data;
    } catch (err) {
        // Interceptor already normalised anything axios produced; a genuinely
        // unexpected throw still becomes an ApiError rather than leaking.
        throw err instanceof ApiError ? err : toApiError(err);
    }
}

/**
 * Every page of a list, for a screen that filters, counts or groups rows
 * client-side. The API caps a page at 100 rows (ListQuery::MAX_PER_PAGE) and
 * clamps a larger per_page silently, so a single get() of "per_page: 100"
 * quietly showed the first hundred records as if they were all of them.
 *
 * Bounded by maxPages, never unbounded. The result keeps the list envelope,
 * with meta.total (the server's count), meta.loaded and meta.truncated, so a
 * screen can say "showing N of M" instead of pretending.
 */
// At most three extra list pages in flight across the whole page.
const EXTRA_PAGE_SLOTS = 3;
let extraInFlight = 0;
const extraQueue = [];
function extraPage(run) {
    return new Promise((resolve, reject) => {
        const start = () => {
            extraInFlight += 1;
            run().then(resolve, reject).finally(() => {
                extraInFlight -= 1;
                extraQueue.shift()?.();
            });
        };
        if (extraInFlight < EXTRA_PAGE_SLOTS) start(); else extraQueue.push(start);
    });
}

async function getAll(url, { params = {}, maxPages = 20, ...options } = {}) {
    const first = await send('get', url, { ...options, params: { ...params, per_page: 100, page: 1 } });
    const data = [...(first?.data || [])];
    let meta = first?.meta || null;
    // The rest a few at a time, kept in order: one after another, the
    // registry's 25 pages of an imported municipality took most of a minute
    // to arrive (live test 2026-10-12). The few are shared by every list on
    // the page: four per list (seven lists at once on Constituents) queued
    // past the 15-second timeout on a one-CPU server and the page failed.
    const last = Math.min(maxPages, meta?.last_page || 1);
    const pages = [];
    for (let page = 2; page <= last; page += 1) pages.push(page);
    // One slow page is asked for again rather than failing the whole list.
    const fetchPage = (page) => send('get', url, { ...options, params: { ...params, per_page: 100, page } });
    const results = await Promise.all(pages.map((page) => extraPage(() => fetchPage(page).catch(() => fetchPage(page)))));
    for (const result of results) {
        data.push(...(result?.data || []));
        meta = result?.meta || meta;
    }
    const total = meta?.total ?? data.length;

    return { data, meta: { ...(meta || {}), total, loaded: data.length, truncated: data.length < total } };
}

export const api = {
    get: (url, options) => send('get', url, options),
    getAll,
    post: (url, data, options) => send('post', url, { ...options, data }),
    put: (url, data, options) => send('put', url, { ...options, data }),
    patch: (url, data, options) => send('patch', url, { ...options, data }),
    delete: (url, options) => send('delete', url, options),
    cancelGroup,
    getToken,
    setToken,
    baseUrl: BASE_URL,
};

// Navigating away must not leave requests resolving into a dead page.
window.addEventListener('beforeunload', () => {
    controllers.forEach((controller) => controller.abort());
    controllers.clear();
});

export default api;
