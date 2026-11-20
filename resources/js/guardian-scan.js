// An officer's scan of a presented ID, and the guardian behind it (welfare
// round 2026-10-29). Shared by the admin top bar's Scan ID window
// (resources/js/id-scan.js) and the officer card on the public Verify page
// (verifyPage in resources/js/scope-citizen.js), so both ask the same way and
// say the same things.
//
// GET /admin/id-scan?code= answers who the holder is and whether the paper is
// still good. The guardian's name and number are NOT in that answer: the
// officer asks for them with a reason (reveal=1&reason=...), each such ask is
// its own audit row, and there are 30 an hour per officer. While the MSWDO
// handles a protection case for the person the guardian is withheld outright
// (guardian_withheld), and the screen says only to contact the MSWDO - never
// what kind of case.

import api from './api/client';

/** Why the officer needs the guardian's contact; the server keeps it with the audit row. */
export const REVEAL_REASONS = [
    { key: 'emergency', en: 'Emergency', fil: 'Emergency' },
    { key: 'lost_or_found_person', en: 'Lost or found person', fil: 'Nawawala o natagpuang tao' },
    { key: 'service_verification', en: 'Checking before giving a service', fil: 'Pagsusuri bago magbigay ng serbisyo' },
    { key: 'other', en: 'Other (say why)', fil: 'Iba pa (sabihin kung bakit)' },
];

/** Where the guardian information came from (App\Domain\Constituents\Guardians). */
export const GUARDIAN_SOURCES = {
    record: { en: 'Recorded by the office on the resident record', fil: 'Naitala ng office sa record ng residente' },
    id_application: { en: 'From the person’s PWD, Solo Parent or Senior Citizen ID application', fil: 'Mula sa aplikasyon ng PWD, Solo Parent o Senior Citizen ID' },
    household: { en: 'From the household roster (a parent or the household head)', fil: 'Mula sa listahan ng sambahayan (magulang o puno ng sambahayan)' },
};

const SECTOR_NAMES = { pwd: 'PWD ID', senior: 'Senior Citizen ID', osca: 'Senior Citizen ID', solo_parent: 'Solo Parent ID' };

/**
 * One scan. reveal: { reason, note } asks for the guardian too. silent: the
 * caller handles 401/403 itself (the Verify page asks in case the visitor is
 * an officer; a resident's token refused there is not news).
 */
export function scanId(code, { reveal = null, silent = false } = {}) {
    const params = { code: String(code || '').trim() };
    if (reveal) {
        params.reveal = 1;
        params.reason = reveal.reason;
        const note = String(reveal.note || '').trim();
        if (note) params.reason_note = note.slice(0, 200);
    }
    return api.get('/admin/id-scan', { params, silent });
}

/** A phone number a tel: link can dial: digits and a leading plus only. */
export function telHref(contact) {
    const raw = String(contact || '').trim();
    const digits = raw.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '');
    return digits.replace(/\D/g, '').length >= 7 ? 'tel:' + digits : '';
}

/** "PWD ID" for a sector row, the server's own name first. */
export function sectorName(s) {
    return s?.name || SECTOR_NAMES[s?.sector] || String(s?.sector || 'ID').replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

/** A sector ID's state, from its status and valid-until date (YYYY-MM-DD, Esperanza's day). */
export function sectorState(s, today) {
    if (s?.status === 'revoked') return 'revoked';
    if (s?.status === 'expired' || (s?.valid_until && String(s.valid_until).slice(0, 10) < today)) return 'expired';
    return 'valid';
}

/** Today in Esperanza, as YYYY-MM-DD. */
export function manilaToday() {
    return new Date(Date.now() + 8 * 3600000).toISOString().slice(0, 10);
}

/** Whether the holder is a PWD: a PWD ID still in force on the scan. */
export function isPwd(holder) {
    return (holder?.sectors || []).some((s) => s.sector === 'pwd' && s.status !== 'revoked');
}

/** The reason picker's state, checked before a reveal is asked for. */
export function revealProblem(reason, note) {
    if (!reason) return 'reason';
    if (reason === 'other' && String(note || '').trim().length < 3) return 'note';
    return '';
}
