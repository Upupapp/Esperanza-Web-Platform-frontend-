{{-- ============================================================
     The event form's Registration and Certificate boxes (Esperanza round 2,
     2026-10-30; owner: residents register for a posted event, the
     municipality keeps a record of who attended, and those who attended
     receive an e-certificate).
     Registration: whether residents may register in the portal and the app,
     how many (empty for no limit), and until when (empty: until the event
     starts, and it follows the event if it moves).
     Certificate: whether those marked as attended receive one, its title (a
     preset or the office's own), its wording with :name, :event, :date and
     :venue, and who signs it. The certificates themselves are issued after
     the event from its Registrations window.
     Inside adminEvents' form (form.registration_open, form.capacity,
     form.closes_at, form.cert); logic in resources/js/scope-admin-records.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $exInput = 'w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard';
    $exLabel = 'block text-xs font-semibold text-slate-700 mb-1';
@endphp
<div>
    <p class="block text-sm font-medium text-slate-700 mb-1.5" id="event-registration-label">Registration</p>
    <div class="rounded-2xl border border-slate-200 p-3.5 space-y-3" role="group" aria-labelledby="event-registration-label">
        <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" x-model="form.registration_open" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
            <span class="min-w-0">
                <span class="block text-xs font-semibold text-ink">Residents can register</span>
                <span class="block text-[11px] text-ink-muted">A Register button on the event in the portal and the app, for the residents who can see it. Each one gets a code to show on the day and a reminder the day before. Walk-ins are added on the day from Registrations.</span>
            </span>
        </label>
        <div x-show="form.registration_open" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="event-capacity" class="{{ $exLabel }}">Capacity <span class="font-normal text-ink-muted">(optional)</span></label>
                <input id="event-capacity" type="number" min="1" step="1" inputmode="numeric" x-model="form.capacity" placeholder="No limit" class="{{ $exInput }}">
                <p class="text-[11px] text-ink-muted mt-1" x-text="capacityHint()"></p>
            </div>
            <div>
                <label for="event-closes" class="{{ $exLabel }}">Registration closes</label>
                <div class="flex items-center gap-2">
                    <input id="event-closes" type="datetime-local" x-model="form.closes_at" :max="form.date ? form.date + 'T' + (form.start_time || '23:59') : null" class="{{ $exInput }}">
                    <button type="button" x-show="form.closes_at" x-cloak @click="form.closes_at = ''" class="shrink-0 text-[11px] font-semibold text-brand-600 hover:underline">At the start</button>
                </div>
                <p class="text-[11px] text-ink-muted mt-1" x-text="closesHint()"></p>
            </div>
        </div>
        <p x-show="!form.registration_open && (editing?.registration?.count || 0) > 0" x-cloak class="text-[11px] text-amber-800 bg-amber-50 rounded-xl px-2.5 py-1.5" x-text="'No one new can register. The ' + (editing?.registration?.count || 0) + ' already registered stay on the list and keep their codes.'"></p>
    </div>
</div>

<div>
    <p class="block text-sm font-medium text-slate-700 mb-1.5" id="event-certificate-label">Certificate</p>
    <div class="rounded-2xl border border-slate-200 p-3.5 space-y-3" role="group" aria-labelledby="event-certificate-label">
        <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" x-model="form.cert.enabled" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
            <span class="min-w-0">
                <span class="block text-xs font-semibold text-ink">Give certificates to those who attend</span>
                <span class="block text-[11px] text-ink-muted">After the event, an officer who approves events issues them from Registrations to everyone marked as attended. Residents with an account are told and keep it in the portal and the app; anyone can check one on the Verify page.</span>
            </span>
        </label>
        <div x-show="form.cert.enabled" x-cloak class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="event-cert-kind" class="{{ $exLabel }}">Title</label>
                    <select id="event-cert-kind" x-model="form.cert.preset" @change="certPreset(form.cert.preset)" class="{{ $exInput }}">
                        <template x-for="p in certPresets" :key="p.key"><option :value="p.key" x-text="p.title" :selected="form.cert.preset === p.key"></option></template>
                        <option value="custom" :selected="form.cert.preset === 'custom'">Another title…</option>
                    </select>
                </div>
                <div x-show="form.cert.preset === 'custom'" x-cloak>
                    <label for="event-cert-title" class="{{ $exLabel }}">Its title</label>
                    <input id="event-cert-title" type="text" maxlength="120" x-model="form.cert.title" placeholder="e.g. Certificate of Recognition" class="{{ $exInput }}">
                </div>
            </div>
            <div>
                <label for="event-cert-body" class="{{ $exLabel }}">Wording</label>
                <textarea id="event-cert-body" rows="3" maxlength="600" x-model="form.cert.body" aria-describedby="event-cert-body-hint" class="{{ $exInput }}"></textarea>
                <p id="event-cert-body-hint" class="text-[11px] text-ink-muted mt-1">:name, :event, :date and :venue are filled in for each person.</p>
                <p class="text-[11px] text-slate-600 bg-slate-50 rounded-xl px-2.5 py-1.5 mt-1.5"><span class="font-semibold">Reads as:</span> <span x-text="certSample()"></span></p>
            </div>
            <div>
                <p class="{{ $exLabel }}">Signed by</p>
                <div class="space-y-2">
                    <template x-for="(s, si) in form.cert.signatories" :key="s.k">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="s.name" maxlength="120" placeholder="Name, e.g. Hon. Maria L. Santos" :aria-label="'Signatory ' + (si + 1) + ', name'" class="{{ $exInput }}">
                            <input type="text" x-model="s.position" maxlength="120" placeholder="Position, e.g. Municipal Mayor" :aria-label="'Signatory ' + (si + 1) + ', position'" class="{{ $exInput }}">
                            <button type="button" x-show="form.cert.signatories.length > 1" @click="removeSignatory(s)" :aria-label="'Remove signatory ' + (si + 1)" class="shrink-0 w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-rose-600 hover:bg-rose-50 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                        </div>
                    </template>
                </div>
                <button type="button" x-show="form.cert.signatories.length < maxSignatories" @click="addSignatory()" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:underline"><i data-lucide="plus" class="w-3 h-3"></i>Add a signatory</button>
            </div>
            <p x-show="editing && editing.certificates_issued_at" x-cloak class="text-[11px] text-amber-800 bg-amber-50 rounded-xl px-2.5 py-1.5">Certificates were issued already. A change here shows only on the ones issued from now on.</p>
        </div>
    </div>
</div>

<p x-show="extrasProblem()" x-cloak role="alert" class="text-xs text-rose-700 bg-rose-50 rounded-xl px-3 py-2 flex items-start gap-1.5"><i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i><span x-text="extrasProblem()"></span></p>
