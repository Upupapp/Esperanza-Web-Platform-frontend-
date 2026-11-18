// Balita photos and videos (owner, 2026-10-18: "attach images and videos when
// writing or posting a new balita"), shared by the resident's composer
// (scope-citizen.js) and the staff's announcement form (scope-admin-records.js).
//
//   balitaMediaField  the picker: photos are resized on the device (a 12 MP
//                     phone photo becomes ~0.5 MB, which matters on mobile
//                     data), a video gets a still taken from it, and each file
//                     goes up in 4 MB pieces that are retried, so a dropped
//                     connection costs one piece. Its `value` ({ ids, busy,
//                     failed }) is x-modelable, so the form reads it.
//   $store.balitaViewer  the full-screen viewer: photos and videos, arrows,
//                     swipe, Esc.
//   balitaTiles()     how a post's media are laid out in its card.
//
// The server checks every file again (type, size, codec, scan): what is done
// here only spares a resident a wait for a file that would be refused.

import api from './api/client';

export const LIMITS = {
    maxItems: 10,
    imageBytes: 10 * 1024 * 1024,
    videoBytes: 100 * 1024 * 1024,
    // Longest side of a photo once resized; enough for a full-screen view.
    imageEdge: 2048,
};
export const ACCEPT = 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime';
const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
const CHUNK_TIMEOUT_MS = 120000;
const RETRY_DELAYS = [1000, 2000, 4000, 8000];

const store = () => window.Alpine?.store('lang');
const tr = (english, en, fil) => (english || store()?.current === 'en' ? en : fil);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export function fmtDuration(ms) {
    if (!ms && ms !== 0) return '';
    const total = Math.max(0, Math.round(ms / 1000));
    const m = Math.floor(total / 60);
    const s = String(total % 60).padStart(2, '0');
    return m >= 60 ? Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0') + ':' + s : m + ':' + s;
}

const mb = (bytes) => Math.round(bytes / 1048576);

// ------------------------------------------------------------- Preparing

/** A photo no larger than LIMITS.imageEdge on its longest side, as JPEG (PNG kept when small). */
async function preparePhoto(file) {
    let bitmap = null;
    try {
        bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch {
        // Older browsers: the file is sent as it is; the server checks it.
        return { blob: file, width: null, height: null, preview: URL.createObjectURL(file) };
    }
    const { width, height } = bitmap;
    const scale = Math.min(1, LIMITS.imageEdge / Math.max(width, height));
    const keep = scale === 1 && file.size <= 1.5 * 1024 * 1024 && file.type !== 'image/png';
    if (keep) {
        bitmap.close?.();
        return { blob: file, width, height, preview: URL.createObjectURL(file) };
    }
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(width * scale);
    canvas.height = Math.round(height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    if (!blob) return { blob: file, width, height, preview: URL.createObjectURL(file) };
    return { blob, width: canvas.width, height: canvas.height, preview: URL.createObjectURL(blob) };
}

/** A video's length and frame size, and a still from it when this browser can play it. */
function prepareVideo(file) {
    const url = URL.createObjectURL(file);
    return new Promise((resolve) => {
        const video = document.createElement('video');
        let settled = false;
        const done = (extra = {}) => {
            if (settled) return;
            settled = true;
            resolve({ blob: file, preview: url, width: video.videoWidth || null, height: video.videoHeight || null, durationMs: Number.isFinite(video.duration) ? Math.round(video.duration * 1000) : null, ...extra });
        };
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            video.currentTime = Math.min(1, (video.duration || 2) / 2);
        };
        video.onseeked = () => {
            try {
                const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(video.videoWidth * scale);
                canvas.height = Math.round(video.videoHeight * scale);
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((poster) => done(poster ? { poster, posterPreview: URL.createObjectURL(poster) } : {}), 'image/jpeg', 0.8);
            } catch {
                done();
            }
        };
        // This browser cannot decode it (an HEVC file on most PCs): uploaded
        // anyway, and the server says what to do if no phone could play it.
        video.onerror = () => done();
        setTimeout(() => done(), 8000);
        video.src = url;
    });
}

// -------------------------------------------------------------- Uploading

async function withRetry(task, signal) {
    for (let attempt = 0; ; attempt += 1) {
        try {
            return await task();
        } catch (err) {
            const retryable = err?.retryable || err?.status === 429 || (err?.status >= 500 && err?.status < 600);
            if (signal?.aborted || !retryable || attempt >= RETRY_DELAYS.length) throw err;
            await sleep(RETRY_DELAYS[attempt]);
        }
    }
}

async function upload(base, item) {
    const kind = item.kind;
    // "Try again" carries on with the same upload, from where the server
    // stopped, so nothing is sent twice (2026-10-21); a finished or expired
    // one starts afresh.
    let started = null;
    if (item.uploadId) {
        try {
            const held = await api.get(base + '/uploads/' + item.uploadId, { signal: item.abort.signal });
            if (held.data?.status === 'open') started = held;
        } catch { /* gone: start again */ }
    }
    started ??= await withRetry(() => api.post(base + '/uploads', { name: item.name, size: item.blob.size, kind }, { signal: item.abort.signal }), item.abort.signal);
    const id = started.data.id;
    item.uploadId = id;
    const step = started.data.chunk_bytes || 4 * 1024 * 1024;
    let offset = started.data.received_bytes || 0;
    while (offset < item.blob.size) {
        const piece = item.blob.slice(offset, Math.min(offset + step, item.blob.size));
        const body = new FormData();
        body.append('offset', String(offset));
        body.append('chunk', piece, 'piece');
        const sent = offset;
        try {
            const res = await withRetry(() => api.post(base + '/uploads/' + id + '/chunk', body, {
                signal: item.abort.signal,
                timeout: CHUNK_TIMEOUT_MS,
                onUploadProgress: (e) => { item.progress = Math.min(99, Math.round(((sent + (e.loaded || 0)) / item.blob.size) * 100)); },
            }), item.abort.signal);
            offset = res.data.received_bytes;
        } catch (err) {
            // The server holds a different amount: resume from there.
            const held = err?.cause?.response?.data?.data?.received_bytes;
            if (err?.status === 409 && Number.isFinite(held)) { offset = held; continue; }
            throw err;
        }
        item.progress = Math.min(99, Math.round((offset / item.blob.size) * 100));
    }
    const finish = new FormData();
    if (item.width) finish.append('width', String(item.width));
    if (item.height) finish.append('height', String(item.height));
    if (item.durationMs) finish.append('duration_ms', String(item.durationMs));
    if (item.poster) finish.append('poster', item.poster, 'poster.jpg');
    const res = await withRetry(() => api.post(base + '/uploads/' + id + '/complete', finish, { signal: item.abort.signal, timeout: CHUNK_TIMEOUT_MS }), item.abort.signal);
    return res.data;
}

// ------------------------------------------------------------ The picker

let keySeq = 0;

/**
 * The photo-and-video picker for a Balita being written.
 * base: '/citizen/balita' or '/admin/balita'. existing: the media rows a post
 * already carries (editing). english: the staff portal, which is English-only.
 */
export function balitaMediaField(base, existing = [], english = false) {
    return {
        items: (existing || []).map((m) => ({
            key: 'm' + (keySeq += 1), kind: m.kind, name: '', status: 'done', progress: 100, error: '',
            preview: m.kind === 'video' ? (m.poster_url || null) : m.url, videoSrc: m.kind === 'video' ? m.url : null,
            media: m, durationMs: m.duration_ms, abort: null,
        })),
        dragging: false,
        notice: '',
        value: { ids: [], busy: false, failed: false },
        limits: LIMITS,
        accept: ACCEPT,
        init() {
            this.sync();
            // x-modelable first copies the form's value in (empty); say what
            // this field really holds once that has happened, or an edited
            // post would be saved without the media it already had.
            setTimeout(() => this.sync(), 0);
        },
        destroy() { this.items.forEach((i) => i.abort?.abort()); },
        t(en, fil) { return tr(english, en, fil); },
        fmtDuration,
        get room() { return LIMITS.maxItems - this.items.length; },
        hint() {
            return this.t(
                'Up to ' + LIMITS.maxItems + ' photos or videos. Photos up to ' + mb(LIMITS.imageBytes) + ' MB (resized before sending); videos up to ' + mb(LIMITS.videoBytes) + ' MB, MP4 or WebM.',
                'Hanggang ' + LIMITS.maxItems + ' larawan o video. Larawan hanggang ' + mb(LIMITS.imageBytes) + ' MB (pinapaliit bago ipadala); video hanggang ' + mb(LIMITS.videoBytes) + ' MB, MP4 o WebM.',
            );
        },
        // The composer's count line: the sizes, short.
        shortHint() {
            return this.t(
                'photos up to ' + mb(LIMITS.imageBytes) + ' MB, videos up to ' + mb(LIMITS.videoBytes) + ' MB',
                'larawan hanggang ' + mb(LIMITS.imageBytes) + ' MB, video hanggang ' + mb(LIMITS.videoBytes) + ' MB',
            );
        },
        sync() {
            const done = this.items.filter((i) => i.status === 'done' && i.media);
            this.value = {
                ids: done.map((i) => i.media.id),
                busy: this.items.some((i) => i.status === 'preparing' || i.status === 'uploading'),
                failed: this.items.some((i) => i.status === 'error'),
                // The first photo, which an occasion greeting's pop-up shows
                // as its poster (the staff form's preview, 2026-10-19).
                cover: done.find((i) => i.kind === 'image')?.preview || null,
            };
        },
        async pick(fileList) {
            this.notice = '';
            const files = [...(fileList || [])];
            for (const file of files) {
                if (this.items.length >= LIMITS.maxItems) {
                    this.notice = this.t('Up to ' + LIMITS.maxItems + ' photos or videos per post.', 'Hanggang ' + LIMITS.maxItems + ' larawan o video lamang bawat post.');
                    break;
                }
                let kind = IMAGE_TYPES.includes(file.type) ? 'image' : (VIDEO_TYPES.includes(file.type) ? 'video' : null);
                // A photo is what its first bytes say, not its name (2026-10-21):
                // a PDF called .jpg is refused here, as the server would.
                if (kind === 'image' && !(await sniffImage(file))) kind = null;
                if (!kind) {
                    this.notice = this.t(file.name + ': only JPG, PNG or WebP photos and MP4 or WebM videos.', file.name + ': JPG, PNG o WebP na larawan at MP4 o WebM na video lamang.');
                    continue;
                }
                if (kind === 'video' && file.size > LIMITS.videoBytes) {
                    this.notice = this.t(file.name + ' is larger than ' + mb(LIMITS.videoBytes) + ' MB. Shorten it or record at a lower quality.', 'Lampas sa ' + mb(LIMITS.videoBytes) + ' MB ang ' + file.name + '. Paikliin ito o i-record sa mas mababang kalidad.');
                    continue;
                }
                if (kind === 'image' && file.size > 40 * 1024 * 1024) {
                    this.notice = this.t(file.name + ' is too large for a photo.', 'Masyadong malaki ang ' + file.name + ' para sa larawan.');
                    continue;
                }
                const item = {
                    key: 'n' + (keySeq += 1), kind, name: file.name, file, status: 'preparing', progress: 0, error: '',
                    preview: null, videoSrc: null, media: null, durationMs: null, abort: new AbortController(),
                };
                this.items.push(item);
                this.start(this.items[this.items.length - 1]);
            }
            this.sync();
        },
        async start(item) {
            item.status = 'preparing';
            item.error = '';
            this.sync();
            try {
                if (!item.blob) {
                    const prepared = item.kind === 'image' ? await preparePhoto(item.file) : await prepareVideo(item.file);
                    Object.assign(item, prepared);
                    if (item.kind === 'video') { item.videoSrc = prepared.preview; item.preview = prepared.posterPreview || null; }
                    if (item.kind === 'image' && item.blob.size > LIMITS.imageBytes) {
                        throw Object.assign(new Error('too big'), { local: this.t('This photo is still over ' + mb(LIMITS.imageBytes) + ' MB after resizing.', 'Lampas pa rin sa ' + mb(LIMITS.imageBytes) + ' MB ang larawan kahit pinaliit na.') });
                    }
                }
                if (item.abort?.signal.aborted) return;
                item.status = 'uploading';
                this.sync();
                item.media = await upload(base, item);
                item.status = 'done';
                item.progress = 100;
            } catch (err) {
                if (item.abort?.signal.aborted) return;
                item.status = 'error';
                item.error = err?.local || (typeof err?.explain === 'function' ? err.explain(english ? 'en' : (store()?.current || 'fil')) : this.t('Could not upload this file.', 'Hindi ma-upload ang file na ito.'));
            } finally {
                this.sync();
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        retry(item) {
            item.abort = new AbortController();
            item.progress = 0;
            this.start(item);
        },
        remove(item) {
            item.abort?.abort();
            // Not attached to anything yet: the server forgets it now
            // (it would be swept after a day anyway).
            if (item.media && !(existing || []).some((m) => m.id === item.media.id)) {
                api.delete(base + '/media/' + item.media.id).catch(() => {});
            }
            this.items = this.items.filter((i) => i.key !== item.key);
            this.sync();
        },
        move(item, step) {
            const from = this.items.findIndex((i) => i.key === item.key);
            const to = from + step;
            if (from < 0 || to < 0 || to >= this.items.length) return;
            const list = [...this.items];
            [list[from], list[to]] = [list[to], list[from]];
            this.items = list;
            this.sync();
        },
        drop(event) {
            this.dragging = false;
            this.pick(event.dataTransfer?.files);
        },
        statusText(item) {
            if (item.status === 'preparing') return item.kind === 'image' ? this.t('Resizing…', 'Pinapaliit…') : this.t('Preparing…', 'Inihahanda…');
            if (item.status === 'uploading') return this.t('Uploading ', 'Ina-upload ') + item.progress + '%';
            if (item.status === 'error') return item.error;
            return item.media && item.media.ready === false ? this.t('Being checked', 'Sinusuri pa') : '';
        },
        label(item, index) {
            const what = item.kind === 'video' ? this.t('Video', 'Video') : this.t('Photo', 'Larawan');
            return what + ' ' + (index + 1) + this.t(' of ', ' sa ') + this.items.length;
        },
    };
}

// ------------------------------------------------- One picture (2026-10-21)

/**
 * What a file really is, read from its first bytes rather than its name or
 * the type the computer claims: a PDF renamed .jpg is a PDF. The server
 * checks the same again; this spares the officer the upload.
 */
export async function sniffImage(file) {
    let head;
    try {
        head = new Uint8Array(await file.slice(0, 12).arrayBuffer());
    } catch {
        return null;
    }
    const at = (i, ...bytes) => bytes.every((b, k) => head[i + k] === b);
    if (at(0, 0xff, 0xd8, 0xff)) return 'image/jpeg';
    if (at(0, 0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a)) return 'image/png';
    if (at(0, 0x52, 0x49, 0x46, 0x46) && at(8, 0x57, 0x45, 0x42, 0x50)) return 'image/webp';
    return null;
}

/**
 * The one-picture field of a banner or an occasion's pop-up (Banners &
 * Occasions, 2026-10-21): one component for both, for a new record and an
 * edited one. Choose or drop an image, see it, replace it, remove it. It goes
 * up through the same pieces and checks as a Balita photo; the server checks
 * it all again.
 *
 * `value` ({ id, url, busy, failed, changed }) is x-modelable. id is the
 * media to save (null: no picture); changed is whether it differs from what
 * the record had, so a form sends the picture only when the officer changed
 * it and an edit of the words keeps the saved one.
 *
 * Nothing here touches the saved record. A replacement that fails keeps the
 * saved picture: the form still holds it, and "Keep the current picture"
 * goes back to it. Removing the saved picture is confirmed, and takes effect
 * only when the form is saved; the file itself is never deleted from here.
 * A picture uploaded and then replaced, removed or left behind by Cancel is
 * dropped from the server at once (discardUnsaved), or swept a day later.
 *
 * existing: the record's image row ({ id, url, width, height }) or null.
 * rules: { mime: [...], maxBytes, min: {width, height}, recommended: {width, height} }
 */
export function imageField(base, existing = null, rules = {}) {
    const mime = rules.mime || IMAGE_TYPES;
    const maxBytes = rules.maxBytes || LIMITS.imageBytes;
    const savedItem = () => (existing ? { status: 'done', progress: 100, preview: existing.url, media: existing, width: existing.width, height: existing.height, error: '', abort: null } : null);
    return {
        item: savedItem(),
        // What the field goes back to when a replacement fails.
        kept: null,
        original: existing ? existing.id : null,
        dragging: false,
        notice: '',
        warning: '',
        value: { id: existing ? existing.id : null, url: existing ? existing.url : null, busy: false, failed: false, changed: false },
        init() {
            this.sync();
            setTimeout(() => this.sync(), 0);
            // The form says when it closed without saving: what this field
            // uploaded is not needed (the saved picture is untouched).
            this.$el.addEventListener('image-field-discard', () => this.discardUnsaved());
        },
        destroy() { this.item?.abort?.abort(); },
        get accept() { return mime.join(','); },
        hint() {
            const r = rules.recommended;
            return 'JPEG, PNG or WebP, up to ' + mb(maxBytes) + ' MB' + (r ? '. Best at ' + r.width + ' × ' + r.height + ' pixels.' : '.');
        },
        sync() {
            const item = this.item;
            const done = item && item.status === 'done' && item.media;
            // A replacement still uploading or failed: the form holds on to
            // the picture it is replacing, so a save never drops it.
            const holding = !done && item && this.kept ? this.kept : null;
            const id = done ? item.media.id : (holding ? holding.media.id : null);
            this.value = {
                id,
                url: item?.preview || holding?.preview || null,
                busy: !!item && (item.status === 'preparing' || item.status === 'uploading'),
                failed: !!item && item.status === 'error',
                changed: id !== this.original,
            };
        },
        async pick(fileList) {
            this.notice = '';
            this.warning = '';
            const file = [...(fileList || [])][0];
            if (!file) return;
            const real = await sniffImage(file);
            if (!real || !mime.includes(real)) {
                this.notice = file.name + ' is not a JPEG, PNG or WebP picture.';
                return;
            }
            if (file.size > 40 * 1024 * 1024) {
                this.notice = file.name + ' is too large for a picture.';
                return;
            }
            this.item?.abort?.abort();
            // The last good picture is kept to fall back to until the new
            // one is in.
            if (this.item?.status === 'done' && this.item.media) this.kept = this.item;
            this.item = { status: 'preparing', progress: 0, preview: null, media: null, error: '', file, name: file.name, kind: 'image', abort: new AbortController(), uploadId: null };
            this.start();
        },
        async start() {
            const item = this.item;
            item.status = 'preparing';
            item.error = '';
            this.sync();
            try {
                if (!item.blob) {
                    Object.assign(item, await preparePhoto(item.file));
                    if (item.blob.size > maxBytes) {
                        throw Object.assign(new Error('too big'), { local: 'This picture is still over ' + mb(maxBytes) + ' MB after resizing.' });
                    }
                    const min = rules.min;
                    if (min && item.width && item.height && (item.width < min.width || item.height < min.height)) {
                        this.warning = 'This picture is ' + item.width + ' × ' + item.height + ' pixels; under ' + min.width + ' × ' + min.height + ' it may look blurred.';
                    }
                }
                if (item.abort?.signal.aborted) return;
                item.status = 'uploading';
                this.sync();
                item.media = await upload(base, item);
                item.status = 'done';
                item.progress = 100;
                // It is in: what it replaced is no longer needed to fall back
                // to, and goes if it was never saved.
                this.forget(this.kept);
                this.kept = null;
            } catch (err) {
                if (item.abort?.signal.aborted) return;
                item.status = 'error';
                item.error = err?.local || (typeof err?.explain === 'function' ? err.explain('en') : 'Could not upload this picture. Check the connection and try again.');
            } finally {
                this.sync();
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        // The same upload again, from where the server stopped: nothing is
        // sent twice.
        retry() {
            if (!this.item) return;
            this.item.abort = new AbortController();
            this.start();
        },
        // A replacement that failed: back to the picture it was replacing.
        keepCurrent() {
            this.item?.abort?.abort();
            this.item = this.kept;
            this.kept = null;
            this.notice = '';
            this.warning = '';
            this.sync();
        },
        /** An upload this form made and has not saved: the server forgets it now (the sweep would, a day later). */
        forget(entry) {
            const id = entry?.media?.id;
            if (id && id !== this.original) api.delete(base + '/media/' + id).catch(() => {});
        },
        discardUnsaved() {
            this.forget(this.item);
            this.forget(this.kept);
        },
        remove() {
            const saved = (this.item?.media?.id ?? null) !== null && this.item.media.id === this.original;
            if (saved && !window.confirm('Remove the picture? It is taken off when you save; until then, Cancel keeps it.')) return;
            this.item?.abort?.abort();
            this.discardUnsaved();
            this.item = null;
            this.kept = null;
            this.notice = '';
            this.warning = '';
            this.sync();
        },
        drop(event) {
            this.dragging = false;
            this.pick(event.dataTransfer?.files);
        },
        statusText() {
            const item = this.item;
            if (!item) return '';
            if (item.status === 'preparing') return 'Preparing…';
            if (item.status === 'uploading') return 'Uploading ' + item.progress + '%';
            if (item.status === 'error') return item.error;
            return item.media && item.media.ready === false ? 'Being checked' : '';
        },
    };
}

// --------------------------------------------------------- Showing them

/** The tiles of a post's card: the first four, the fourth saying how many more. */
export function balitaTiles(media) {
    const list = (media || []).filter(Boolean);
    const shown = list.slice(0, 4);
    return shown.map((m, i) => ({ media: m, index: i, more: i === 3 && list.length > 4 ? list.length - 4 : 0 }));
}

/** Grid classes for a card with n media. */
export function balitaGridClass(n) {
    if (n <= 1) return 'grid-cols-1';
    return 'grid-cols-2';
}

/** Tile classes: one alone keeps its shape; with three, the first spans the row. */
export function balitaTileClass(n, index) {
    if (n <= 1) return 'max-h-[28rem]';
    if (n === 3 && index === 0) return 'col-span-2 aspect-[16/9]';
    return 'aspect-square';
}

export function viewerStore() {
    return {
        open: false,
        items: [],
        index: 0,
        label: '',
        english: false,
        show(items, index = 0, label = '', english = false) {
            // index is a place in the list as passed: the item there is found
            // again after the ones with no address are dropped, so a gap before
            // it does not open the photo after it (QA 2026-11-18).
            const target = (items || [])[index];
            this.items = (items || []).filter((m) => m && m.url);
            if (!this.items.length) return;
            const at = this.items.indexOf(target);
            this.index = at !== -1 ? at : Math.max(0, Math.min(index, this.items.length - 1));
            this.label = label || '';
            this.english = english;
            this.open = true;
            window.Alpine?.nextTick(() => window.renderIcons?.());
        },
        close() {
            this.pause();
            this.open = false;
        },
        get current() { return this.items[this.index] || null; },
        go(step) {
            if (this.items.length < 2) return;
            this.pause();
            this.index = (this.index + step + this.items.length) % this.items.length;
        },
        pause() {
            document.querySelectorAll('[data-balita-viewer] video').forEach((v) => { try { v.pause(); } catch { /* gone */ } });
        },
        t(en, fil) { return tr(this.english, en, fil); },
        swipeStart: null,
        touchStart(e) { this.swipeStart = e.changedTouches?.[0]?.clientX ?? null; },
        touchEnd(e) {
            if (this.swipeStart === null) return;
            const dx = (e.changedTouches?.[0]?.clientX ?? this.swipeStart) - this.swipeStart;
            this.swipeStart = null;
            if (Math.abs(dx) > 50) this.go(dx < 0 ? 1 : -1);
        },
    };
}

export function registerBalitaMedia(Alpine) {
    Alpine.data('balitaMediaField', balitaMediaField);
    Alpine.data('imageField', imageField);
    if (!Alpine.store('balitaViewer')) Alpine.store('balitaViewer', viewerStore());
    window.balitaTiles = balitaTiles;
    window.balitaGridClass = balitaGridClass;
    window.balitaTileClass = balitaTileClass;
    window.fmtBalitaDuration = fmtDuration;
}
