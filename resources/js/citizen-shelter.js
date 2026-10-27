// The resident's side of shelter and relief (owner, 2026-10-27, Phase 3), on
// the Emergency page (citizen/emergency.blade.php, x-data="citizenShelter").
// Mirrors the app's Sakuna tab.
//
//   Their stays and relief          GET    /citizen/sakuna/records
//   Received / not received         POST   /citizen/sakuna/relief/{ref}/acknowledge
//   A mistake on a record           POST   /citizen/sakuna/records/report  (a support ticket)
//   The household list, kept ready  GET | PUT | DELETE /citizen/sakuna/household
//
// Records are found by the registry link on the account only. Staff still
// check families in and release relief; nothing here changes a record. The
// household list is kept only with consent, and only while the LGU has it on.

import api from './api/client';

const TZ = 'Asia/Manila';

function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

function blankMember() {
    return { name: '', relation: '', sex: '', dob: '', vulnerability: '' };
}

function citizenShelter() {
    return {
        recordsState: 'loading',
        enabled: true,
        linked: false,
        stays: [],
        relief: [],
        answering: '',
        reportFor: '',
        reportMessage: '',
        reportSending: false,

        hhState: 'loading',
        hhEnabled: false,
        household: null,
        hhEditing: false,
        hhForm: { barangay: '', sitio: '', purok: '', members: [blankMember()], consent: false },
        hhSaving: false,
        vulnerabilities: ['Older person', 'Pregnant', 'Lactating mother', 'PWD', 'Solo parent', 'Child (under 18)'],

        init() {
            this.loadRecords();
            this.loadHousehold();
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        when(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return String(iso);
            return d.toLocaleDateString(this.$store.lang.current === 'en' ? 'en-PH' : 'fil-PH', { month: 'short', day: 'numeric', year: 'numeric', timeZone: TZ });
        },
        error(err, en, fil) {
            return err?.localised ? err.localised(this.$store.lang.current) : this.t(en, fil);
        },
        get barangays() {
            return Object.keys(this.$store.barangay?.catalog || {}).sort((a, b) => a.localeCompare(b));
        },

        // ------------------------------------------- Their stays and relief
        async loadRecords() {
            this.recordsState = 'loading';
            try {
                const res = await api.get('/citizen/sakuna/records');
                const d = res.data || {};
                this.enabled = d.enabled !== false;
                this.linked = !!d.linked;
                this.stays = d.stays || [];
                this.relief = d.relief || [];
                this.recordsState = 'loaded';
            } catch { this.recordsState = 'error'; }
            this.$nextTick(() => window.renderIcons?.());
        },
        reliefLine(r) {
            return [r.quantity, r.unit, r.item].filter((v) => v !== null && v !== '' && v !== undefined).join(' ');
        },
        async acknowledge(r, received) {
            if (this.answering) return;
            let note = null;
            if (!received) {
                note = window.prompt(this.t(
                    'Tell the office what happened (optional). They will check the release.',
                    'Sabihin sa opisina ang nangyari (opsyonal). Susuriin nila ang paglabas nito.',
                ));
                if (note === null) return;
            }
            this.answering = r.ref;
            try {
                const res = await api.post('/citizen/sakuna/relief/' + encodeURIComponent(r.ref) + '/acknowledge', { received, note: note || null });
                const i = this.relief.findIndex((x) => x.ref === r.ref);
                if (i !== -1) this.relief[i] = { ...this.relief[i], ...res.data };
                toast(received
                    ? this.t('Thank you. Recorded as received.', 'Salamat. Naitala bilang natanggap.')
                    : this.t('Recorded as not received. The office has been told.', 'Naitala bilang hindi natanggap. Naabisuhan na ang opisina.'));
            } catch (err) {
                toast(this.error(err, 'Could not record your answer.', 'Hindi maitala ang iyong sagot.'), 'error');
            } finally { this.answering = ''; }
        },
        openReport(ref) {
            this.reportFor = this.reportFor === ref ? '' : ref;
            this.reportMessage = '';
        },
        async sendReport() {
            if (this.reportSending || this.reportMessage.trim().length < 5) return;
            this.reportSending = true;
            try {
                const res = await api.post('/citizen/sakuna/records/report', { ref: this.reportFor, message: this.reportMessage.trim() });
                toast(this.t('Sent as support ticket ' + res.data.ticket + '. The office will reply there.',
                    'Naipadala bilang support ticket ' + res.data.ticket + '. Doon sasagot ang opisina.'));
                this.reportFor = '';
                this.reportMessage = '';
            } catch (err) {
                toast(this.error(err, 'Could not send your report.', 'Hindi maipadala ang iyong ulat.'), 'error');
            } finally { this.reportSending = false; }
        },

        // ------------------------------------- The household list, kept ready
        async loadHousehold() {
            this.hhState = 'loading';
            try {
                const res = await api.get('/citizen/sakuna/household');
                this.hhEnabled = !!res.data?.enabled;
                this.household = res.data?.household || null;
                this.hhState = 'loaded';
            } catch { this.hhState = 'error'; }
        },
        editHousehold() {
            const h = this.household;
            this.hhForm = h
                ? { barangay: h.barangay || '', sitio: h.sitio || '', purok: h.purok || '', members: (h.members || []).map((m) => ({ ...blankMember(), ...m, relation: m.relation || '', sex: m.sex || '', dob: m.dob || '', vulnerability: m.vulnerability || '' })), consent: false }
                : { barangay: this.$store.citizenSession?.account?.barangay || '', sitio: '', purok: '', members: [blankMember()], consent: false };
            if (!this.hhForm.members.length) this.hhForm.members.push(blankMember());
            this.hhEditing = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        addHouseholdMember() {
            if (this.hhForm.members.length < 30) this.hhForm.members.push(blankMember());
            this.$nextTick(() => window.renderIcons?.());
        },
        removeHouseholdMember(i) {
            this.hhForm.members.splice(i, 1);
            if (!this.hhForm.members.length) this.hhForm.members.push(blankMember());
        },
        get hhReady() {
            const f = this.hhForm;
            return f.consent && f.barangay && f.members.some((m) => m.name.trim());
        },
        async saveHousehold() {
            if (this.hhSaving || !this.hhReady) return;
            this.hhSaving = true;
            const f = this.hhForm;
            try {
                const res = await api.put('/citizen/sakuna/household', {
                    consent: true,
                    barangay: f.barangay,
                    sitio: f.sitio.trim() || null,
                    purok: f.purok.trim() || null,
                    members: f.members.filter((m) => m.name.trim()).map((m) => ({
                        name: m.name.trim(), relation: m.relation.trim() || null, sex: m.sex || null, dob: m.dob || null, vulnerability: m.vulnerability || null,
                    })),
                });
                this.household = res.data.household;
                this.hhEditing = false;
                toast(this.t('Your household list is saved.', 'Naitala ang listahan ng inyong sambahayan.'));
            } catch (err) {
                toast(this.error(err, 'Could not save your household list.', 'Hindi maitala ang listahan.'), 'error');
            } finally { this.hhSaving = false; }
        },
        async withdrawHousehold() {
            if (!window.confirm(this.t('Delete your household list? Staff will fill the check-in form by hand.', 'Burahin ang listahan ng inyong sambahayan? Mano-manong pupunan ng staff ang check-in.'))) return;
            try {
                await api.delete('/citizen/sakuna/household');
                this.household = null;
                this.hhEditing = false;
                toast(this.t('Your household list is deleted.', 'Nabura na ang listahan ng inyong sambahayan.'));
            } catch (err) {
                toast(this.error(err, 'Could not delete your household list.', 'Hindi mabura ang listahan.'), 'error');
            }
        },
    };
}

export function register(Alpine) {
    Alpine.data('citizenShelter', citizenShelter);
}
