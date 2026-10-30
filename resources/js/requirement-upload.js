// A requirement's document uploaded at the office (round 2, 2026-10-30; the
// owner: "In the apply for new tulong the requirements here should be
// uploaded and is recorded by the system not just presented at the office").
//
// POST /admin/{dokyu|tulong}/requests/{ref}/requirements/{key}/attach, one
// multipart `file` (PDF, JPG, JPEG, PNG or WebP, at most 10 MB), with the
// service's own create or edit. The server accepts it at once ("Uploaded at
// the office by <name>") when the uploader may review the service, else leaves
// it for the screener, and answers with the request's detail.
//
// Used by Apply for New Tulong (each chosen file, after the case is filed;
// resources/js/welfare-admin.js) and by "Upload file" on a Dokyu or Tulong
// requirement with none (resources/js/scope-admin-requests.js). Imported by
// those chunks, so it is never in the shared bundle.
// The pickers offer accept="image/*,application/pdf" (a photo, or the camera
// on a phone, or a PDF); what the server takes is narrower, so it is checked.
import api from './api/client';

const UPLOAD_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
const UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
const UPLOAD_MAX_BYTES = 10 * 1024 * 1024;
// A 10 MB scan on mobile data takes longer than the client's 15 seconds.
const UPLOAD_TIMEOUT_MS = 180000;

/**
 * '' when the server will take the file, else why not, said before it is
 * sent: a phone's HEIC photo, say, which the server refuses.
 */
export function uploadProblem(file) {
    if (!file) return 'Choose a file.';
    const ext = String(file.name || '').split('.').pop().toLowerCase();
    if (!UPLOAD_TYPES.includes(file.type) && !UPLOAD_EXTENSIONS.includes(ext)) return 'Use a PDF, or a JPG, PNG or WebP photo.';
    if (file.size > UPLOAD_MAX_BYTES) return 'The file is larger than 10 MB.';
    return '';
}

/** 1536 -> '1.5 KB'. */
export function fileSize(bytes) {
    const n = Number(bytes);
    if (!Number.isFinite(n) || n < 0) return '';
    if (n < 1024) return n + ' bytes';
    if (n < 1024 * 1024) return (n / 1024).toFixed(n < 10240 ? 1 : 0) + ' KB';
    return (n / (1024 * 1024)).toFixed(1) + ' MB';
}

/**
 * Send one file; resolves with the request's detail. onProgress(percent)
 * stops at 99 until the server has answered.
 */
export async function attachAtOffice(type, ref, key, file, onProgress = () => {}) {
    const body = new FormData();
    body.append('file', file);
    const res = await api.post('/admin/' + type + '/requests/' + encodeURIComponent(ref) + '/requirements/' + encodeURIComponent(key) + '/attach', body, {
        timeout: UPLOAD_TIMEOUT_MS,
        onUploadProgress: (e) => { if (e.total) onProgress(Math.min(99, Math.round((e.loaded / e.total) * 100))); },
    });
    onProgress(100);
    return res?.data || null;
}
