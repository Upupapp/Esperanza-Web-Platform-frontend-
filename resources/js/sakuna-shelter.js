// Shelter and relief records, Phase 2 (owner, 2026-10-27). Merged into the
// Sakuna page's own component (sakuna.blade.php: x-data="{ ...sakunaShelter(),
// ... }"), so `this` is that component: its checkInForm, selectedEvacuee,
// distributionForm, toast() and can().
//
//   Find a family in the registry   GET  /admin/sakuna/registry/families?q=
//   Correct a shelter record        PUT  /admin/sakuna/evacuees/{ref}
//   Add a member                    POST /admin/sakuna/evacuees/{ref}/members
//   Correct a member / the head     PUT  /admin/sakuna/evacuees/{ref}/members/{id}
//
// The registry fills the check-in form; the officer still ticks who actually
// arrived. A family already staying somewhere is asked about, not refused:
// the server answers 409 ALREADY_SHELTERED and the officer may confirm.

import api from './api/client';

function blank(v) {
    return v === '' || v === undefined ? null : v;
}

function sakunaShelter() {
    return {
        // ---------------------------------------------- The registry at check-in
        registryQuery: '',
        registryResults: [],
        registrySearching: false,
        registrySearched: false,
        registryPicked: null,
        registryTimer: null,
        checkInDuplicate: '',

        searchRegistry() {
            clearTimeout(this.registryTimer);
            const q = this.registryQuery.trim();
            if (q.length < 3) {
                this.registryResults = [];
                this.registrySearched = false;
                return;
            }
            this.registryTimer = setTimeout(async () => {
                this.registrySearching = true;
                try {
                    const res = await api.get('/admin/sakuna/registry/families?' + new URLSearchParams({ q }));
                    if (q === this.registryQuery.trim()) {
                        this.registryResults = res.data || [];
                        this.registrySearched = true;
                    }
                } catch (err) {
                    this.toast(err.localised ? err.localised('en') : 'Could not search the registry.', 'error');
                } finally { this.registrySearching = false; }
            }, 300);
        },
        pickRegistryFamily(f) {
            const head = f.members.find((m) => m.is_head);
            this.registryPicked = f;
            this.registryResults = [];
            this.checkInDuplicate = '';
            Object.assign(this.checkInForm, {
                family_id: f.id,
                head_name: head?.name || f.head_name || '',
                barangay: f.barangay || this.checkInForm.barangay,
                sitio: f.sitio || '',
                // A list the resident keeps (2026-10-27, Phase 3) has a purok.
                purok: f.purok || this.checkInForm.purok || '',
                members: f.members.map((m) => ({
                    name: m.name, relation: m.relation || '', age: '', sex: m.sex || '', needs: '', dob: m.dob || '',
                    education: '', occupation: '', vulnerability: '',
                    individual_id: m.individual_id, is_head: !!m.is_head, arrived: true,
                })),
            });
            this.$nextTick(() => window.renderIcons?.());
        },
        clearRegistryPick() {
            this.registryPicked = null;
            this.checkInForm.family_id = null;
            this.checkInForm.members = this.checkInForm.members.map((m) => ({ ...m, individual_id: null, arrived: true }));
        },
        setCheckInHead(idx) {
            this.checkInForm.members.forEach((m, i) => { m.is_head = i === idx; });
            const head = this.checkInForm.members[idx];
            if (head?.name.trim()) this.checkInForm.head_name = head.name.trim();
        },
        checkInArrived() {
            return this.checkInForm.members.filter((m) => m.arrived !== false && m.name.trim());
        },
        /** The check-in payload's additions: the head, the registry link, purok and a typed household code. */
        checkInExtras(confirm) {
            const f = this.checkInForm;
            return {
                family_id: f.family_id || undefined,
                purok: blank(f.purok?.trim()),
                hh_code: blank(f.hh_code?.trim()),
                confirm_duplicate: confirm ? true : undefined,
            };
        },

        // ------------------------------------------ Correcting a shelter record
        // Both forms are bound before they are opened, so they start filled.
        recordEditing: false,
        recordForm: { sitio: '', purok: '', hh_code: '', member_count: '' },
        recordSaving: false,
        memberEditing: null,
        memberForm: { name: '', relation: '', age: '', sex: '', dob: '', vulnerability: '', is_head: false },
        memberSaving: false,

        mayEditShelter() {
            return this.can('sakuna_evacuees', 'edit') && this.selectedEvacuee?.status === 'Processing';
        },
        editShelterRecord() {
            const ev = this.selectedEvacuee || {};
            this.recordForm = { sitio: ev.sitio || '', purok: ev.purok || '', hh_code: ev.hh_code || '', member_count: ev.member_count || '' };
            this.recordEditing = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async saveShelterRecord() {
            if (this.recordSaving || !this.selectedEvacuee) return;
            this.recordSaving = true;
            const f = this.recordForm;
            try {
                const res = await api.put('/admin/sakuna/evacuees/' + this.selectedEvacuee.ref, {
                    sitio: blank(f.sitio.trim()), purok: blank(f.purok.trim()), hh_code: blank(f.hh_code.trim()),
                    member_count: f.member_count === '' ? null : Number(f.member_count),
                });
                this.shelterUpdated(res.data);
                this.recordEditing = false;
                this.toast('Shelter record saved.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save the shelter record.', 'error');
            } finally { this.recordSaving = false; }
        },
        editMember(m) {
            this.memberForm = {
                name: m.name || '', relation: m.relation || '', age: m.age ?? '', sex: m.sex || '', dob: m.dob || '',
                vulnerability: m.vulnerability || '', is_head: !!m.is_head,
            };
            this.memberEditing = m.id;
            this.$nextTick(() => window.renderIcons?.());
        },
        newMember() {
            this.memberForm = { name: '', relation: '', age: '', sex: '', dob: '', vulnerability: '', is_head: false };
            this.memberEditing = 'new';
            this.$nextTick(() => window.renderIcons?.());
        },
        async saveMember() {
            const f = this.memberForm;
            if (this.memberSaving || !this.selectedEvacuee || !f.name.trim()) return;
            this.memberSaving = true;
            const body = {
                name: f.name.trim(), relation: blank(f.relation.trim()), age: f.age === '' ? null : Number(f.age),
                sex: blank(f.sex), dob: blank(f.dob), vulnerability: blank(f.vulnerability), is_head: !!f.is_head,
            };
            const base = '/admin/sakuna/evacuees/' + this.selectedEvacuee.ref + '/members';
            try {
                const res = this.memberEditing === 'new' ? await api.post(base, body) : await api.put(base + '/' + this.memberEditing, body);
                this.shelterUpdated(res.data);
                this.memberEditing = null;
                this.toast('Member saved.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this member.', 'error');
            } finally { this.memberSaving = false; }
        },
        shelterUpdated(detail) {
            this.selectedEvacuee = { ...this.selectedEvacuee, ...detail };
            const idx = this.evacueesList.findIndex((e) => e.ref === detail.ref);
            if (idx !== -1) this.evacueesList[idx] = { ...this.evacueesList[idx], ...detail };
            this.$nextTick(() => window.renderIcons?.());
        },

        // ------------------------------------------- Where relief was given
        reliefCenters: [],
        loadReliefCenters() {
            if (this.reliefCenters.length || !this.mayListCenters()) return;
            api.getAll('/admin/sakuna/centers').then((r) => { this.reliefCenters = r.data || []; }).catch(() => {});
        },
        /** A sheltered family's relief is given where it is staying, unless the officer says otherwise. */
        reliefCenterFor(ev) {
            return this.reliefCenters.find((c) => c.name === ev?.center)?.ref || '';
        },
    };
}

export function register() {
    window.sakunaShelter = sakunaShelter;
}
