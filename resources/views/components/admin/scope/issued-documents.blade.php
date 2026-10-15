{{-- The documents issued for this request, in a Dokyu or Tulong detail
     (scope round 2026-10-15): each copy's control number, verification code,
     dates, and whether it was revoked. Reprint the original (a new, numbered
     copy), revoke a copy with the reason the public Verify page then shows,
     open the Verify page, attach the scan of the signed paper the resident
     sees in the app (2026-10-15). can: the page's release gate, as issuing is
     releasing; issuable: when a request with no document yet may be issued
     one from here. Logic: issuedDocuments() in resources/js/scope-admin-requests.js. --}}
@props(['can' => 'false', 'issuable' => 'false'])
<div x-data="issuedDocuments()" x-show="docs.length || ({{ $issuable }})" x-cloak class="mt-4">
    <div class="mb-2 flex items-center justify-between gap-2">
        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ink"><i data-lucide="qr-code" class="w-3.5 h-3.5"></i>Issued Documents</p>
        <p class="text-[11px] text-ink-muted" x-show="docs.length > 1" x-text="docs.length + ' copies'"></p>
    </div>
    <div x-show="!docs.length" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-slate-300 px-3 py-2.5">
        <p class="min-w-0 flex-1 text-xs text-ink-muted">No document is issued for this request yet, so the printed copy has no control number or QR code.</p>
        <x-ui.button size="sm" variant="secondary" icon="stamp" x-show="{{ $can }}" x-bind:disabled="docBusy" @click="issueNow()"><span x-text="docBusy ? 'Issuing…' : 'Issue document'"></span></x-ui.button>
    </div>
    <ul x-show="docs.length" class="space-y-2">
        <template x-for="d in docs" :key="d.control_number">
            <li class="rounded-xl border p-3" :class="d.revoked ? 'border-rose-200 bg-rose-50/30' : 'border-slate-200'">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-ink">
                            <span class="font-mono break-all" x-text="d.control_number"></span>
                            <span x-show="d.is_reprint" class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600" x-text="'Reprint' + (d.reprint_number ? ' ' + d.reprint_number : '')"></span>
                            <span x-show="d.revoked" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200"><i data-lucide="ban" class="w-3 h-3"></i>Revoked</span>
                            <span x-show="expired(d)" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200"><i data-lucide="calendar-x" class="w-3 h-3"></i>Expired</span>
                        </p>
                        <p class="mt-0.5 text-[11px] text-ink-muted">Code <span class="font-mono font-semibold text-slate-700" x-text="d.serial"></span></p>
                        <p class="text-[11px] text-ink-muted" x-text="'Issued ' + day(d.issued_on) + (d.valid_until ? ' · Valid until ' + day(d.valid_until) : '')"></p>
                        <p x-show="d.revoked" class="mt-0.5 break-words text-[11px] text-rose-700" x-text="'Revoked' + (d.revoked_at ? ' ' + day(d.revoked_at) : '') + (d.revoked_reason ? ': ' + d.revoked_reason : '')"></p>
                        {{-- The scan of the signed, sealed paper: the resident's digital copy (2026-10-15). --}}
                        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px]">
                            <span class="inline-flex items-center gap-1" :class="d.has_scan ? 'text-emerald-700' : 'text-ink-muted'"><i :data-lucide="d.has_scan ? 'file-check' : 'file-x'" class="w-3 h-3"></i><span x-text="d.has_scan ? 'Signed copy attached' + (d.scan_uploaded_at ? ' ' + day(d.scan_uploaded_at) : '') : 'No signed copy for the resident yet'"></span></span>
                            <a x-show="d.scan_url" :href="d.scan_url" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">View</a>
                            <label x-show="({{ $can }}) && !d.revoked" class="cursor-pointer font-medium text-brand-600 hover:underline" :class="scanBusy ? 'pointer-events-none opacity-50' : ''">
                                <span x-text="scanBusy === d.control_number ? 'Uploading…' : (d.has_scan ? 'Replace' : 'Upload signed copy')"></span>
                                <input type="file" accept="application/pdf,image/jpeg,image/png,image/webp" class="sr-only" :aria-label="'Signed copy of ' + d.control_number" @change="uploadScan(d, $event.target.files[0] || null); $event.target.value = ''">
                            </label>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-medium">
                        <a x-show="d.verify_url" :href="d.verify_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline">Open verification page<i data-lucide="external-link" class="w-3 h-3"></i></a>
                        <button type="button" x-show="({{ $can }}) && !d.revoked && !d.is_reprint" @click="start(d, 'reprint')" :aria-expanded="acting === d.control_number && kind === 'reprint'" class="text-brand-600 hover:underline">Reprint</button>
                        <button type="button" x-show="({{ $can }}) && !d.revoked" @click="start(d, 'revoke')" :aria-expanded="acting === d.control_number && kind === 'revoke'" class="text-rose-600 hover:underline">Revoke</button>
                    </div>
                </div>
                <div x-show="acting === d.control_number" x-cloak class="mt-2.5 space-y-2 border-t border-brand-600/10 pt-2.5">
                    <p class="text-[11px] leading-snug" :class="kind === 'revoke' ? 'text-rose-700' : 'text-ink-muted'" x-text="kind === 'revoke' ? 'Revoke ' + d.control_number + '? The Verify page will say it is not valid, and show your reason. This cannot be undone.' : 'Reprint ' + d.control_number + ': a new copy with its own control number and QR code, marked as a reprint.'"></p>
                    <label :for="'doc-reason-' + d.control_number" class="block text-[11px] font-medium text-ink-muted" x-text="kind === 'revoke' ? 'Reason, shown on the Verify page' : 'Reason for the reprint'"></label>
                    <textarea :id="'doc-reason-' + d.control_number" x-model="reason" rows="2" maxlength="250" :placeholder="kind === 'revoke' ? 'e.g. Issued with the wrong name' : 'e.g. The resident lost the first copy'" class="w-full resize-none rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-brand-100"></textarea>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" :disabled="!reasonOk || docBusy" @click="confirmAction()" class="rounded-xl px-2.5 py-1.5 text-[11px] font-medium text-white transition-colors disabled:pointer-events-none disabled:opacity-40" :class="kind === 'revoke' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700'" x-text="docBusy ? 'Saving…' : (kind === 'revoke' ? 'Revoke document' : 'Reprint')"></button>
                        <button type="button" @click="stop()" class="text-[11px] font-medium text-ink-muted hover:text-slate-600">Cancel</button>
                        <span x-show="!reasonOk" class="text-[10.5px] text-ink-faint" x-text="'At least ' + (kind === 'revoke' ? 5 : 3) + ' characters.'"></span>
                    </div>
                </div>
            </li>
        </template>
    </ul>
</div>
