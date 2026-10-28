{{-- Scan ID (welfare round 2026-10-29): the top bar's window for reading a
     presented ID or document - by the camera, or the code typed or pasted -
     and, for a minor or a PWD, reaching their guardian.

     Inert until first used: it waits in this <template> and is copied into
     the page, with its logic, by resources/js/id-scan.js when the Scan ID
     button is pressed (window.openIdScan in app.js), so the pages that never
     scan carry neither. GET /admin/id-scan?code=; the guardian's contact only
     with a reason (reveal=1&reason=), and every look is in the audit log. --}}
<template id="id-scan-template">
<div x-data="idScan" @id-scan-open.window="show()">
<x-ui.modal title="Scan ID" maxWidth="sm">
    <div class="space-y-4">
        {{-- The camera, while nothing has been read yet --}}
        <div x-show="state === 'idle'">
            <div class="relative rounded-2xl overflow-hidden bg-navy-950 aspect-[4/3]">
                <video id="id-scan-video" x-show="camera === 'live'" autoplay playsinline muted class="w-full h-full object-cover" aria-label="Camera view"></video>
                <span x-show="camera === 'live'" aria-hidden="true" class="absolute inset-[16%] rounded-2xl border-2 border-white/85 shadow-[0_0_0_9999px_rgb(4_12_32/.35)]"></span>
                <div x-show="camera !== 'live'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-white/85">
                    <span x-show="camera === 'starting' || camera === 'idle'"><i data-lucide="loader-circle" class="w-7 h-7 animate-spin"></i></span>
                    <span x-show="camera === 'off' || camera === 'blocked'"><i data-lucide="camera-off" class="w-7 h-7"></i></span>
                    <p class="text-sm max-w-xs" x-text="camera === 'off' || camera === 'blocked' ? cameraNote : 'Opening the camera…'"></p>
                    <button type="button" x-show="camera === 'blocked'" @click="startCamera()" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40"><i data-lucide="refresh-cw" class="w-4 h-4"></i>Try the camera again</button>
                </div>
            </div>
            <p x-show="camera === 'live'" class="mt-2 text-xs text-ink-muted flex items-center gap-1.5" role="status" aria-live="polite"><i data-lucide="scan-qr-code" class="w-3.5 h-3.5 shrink-0"></i><span x-text="cameraLine"></span></p>
        </div>

        {{-- Or the code, typed or pasted --}}
        <form @submit.prevent="submitTyped()" class="space-y-1.5">
            <label for="id-scan-code" class="block text-xs font-semibold text-slate-700">Or type or paste the code</label>
            <div class="flex gap-2">
                <input id="id-scan-code" type="text" x-model="code" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="2048" placeholder="ABCD-EFGH-IJKL"
                    class="flex-1 min-w-0 rounded-2xl border-0 surface-pressed py-2.5 px-4 font-mono text-sm text-ink placeholder:font-sans placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                <x-ui.button type="submit" icon="search" x-bind:disabled="!code.trim() || state === 'loading'">Check</x-ui.button>
            </div>
            <p class="text-[11px] text-ink-muted">The verification code beside a QR code, a control number, the number on a PWD, Solo Parent or Senior Citizen ID, or the link the QR opens.</p>
        </form>

        <div aria-live="polite">
            <div x-show="state === 'loading'" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-20 rounded-2xl" /><x-ui.skeleton class="h-14 rounded-2xl opacity-70" /></div>

            <div x-show="state === 'notfound'" x-cloak role="alert" class="rounded-2xl border border-slate-200 bg-white/80 p-4 flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-slate-500 text-white grid place-items-center shrink-0"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-ink">Not found</p>
                    <p class="text-sm text-ink-body mt-0.5">No ID or document in Esperanza's records matches this code. Check that it was typed correctly. A QR code that is not from LGU Esperanza is not found either.</p>
                </div>
            </div>
            <div x-show="state === 'toomany'" x-cloak role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 flex items-start gap-2.5">
                <i data-lucide="timer" class="w-4 h-4 shrink-0 mt-0.5"></i><p>Too many scans in a minute. Wait a minute, then try again.</p>
            </div>
            <div x-show="state === 'error'" x-cloak role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex items-start gap-2.5">
                <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><p x-text="failure"></p>
            </div>

            {{-- Found: the holder, the paper, the IDs, the guardian --}}
            <template x-if="state === 'found' && result">
            <div class="space-y-3" data-testid="id-scan-result">
                <div class="rounded-2xl border border-brand-600/10 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <span class="relative w-14 h-14 rounded-2xl bg-brand-50 text-brand-700 grid place-items-center font-bold overflow-hidden shrink-0" aria-hidden="true">
                            <span x-text="initials(holder?.name)"></span>
                            <img x-show="holder?.photo_url" :src="holder?.photo_url" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display = 'none'">
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[.07em] text-ink-muted" x-text="matchLabel"></p>
                            <p class="text-lg font-bold text-ink leading-snug break-words" x-text="holder?.name || 'Name not on record'"></p>
                            <p class="text-sm text-ink-body" x-show="facts" x-text="facts"></p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <span x-show="holder?.is_minor" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-violet-50 text-violet-800 ring-1 ring-inset ring-violet-200"><i data-lucide="baby" class="w-3 h-3"></i>Minor</span>
                                <span x-show="pwd" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-200"><i data-lucide="accessibility" class="w-3 h-3"></i>PWD</span>
                                <span x-show="holder?.individual_ref" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-mono text-ink-body bg-slate-100" x-text="holder?.individual_ref"></span>
                                <span x-show="holder?.account_no" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-mono text-ink-body bg-slate-100" x-text="holder?.account_no"></span>
                            </div>
                        </div>
                    </div>
                    <a x-show="holder?.individual_ref" :href="recordHref(holder?.individual_ref)" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline"><i data-lucide="user-round-search" class="w-4 h-4"></i>Open the resident record</a>
                </div>

                {{-- The paper that was scanned --}}
                <div x-show="doc" class="rounded-2xl border border-brand-600/10 bg-white px-4 py-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink break-words" x-text="doc?.title || 'Document'"></p>
                        <p class="text-xs font-mono text-ink-body" x-show="doc?.control_number" x-text="doc?.control_number"></p>
                        <p class="text-[11px] text-ink-muted" x-show="doc?.valid_until" x-text="'Valid until ' + doc?.valid_until"></p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="docState.tone" x-text="docState.label"></span>
                </div>

                {{-- The sector IDs the LGU released to the holder --}}
                <ul x-show="sectors.length" class="rounded-2xl border border-brand-600/10 bg-white divide-y divide-brand-600/[.08]" aria-label="IDs issued by the LGU">
                    <template x-for="s in sectors" :key="s.key">
                        <li class="px-4 py-2.5 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink" x-text="s.name"></p>
                                <p class="text-xs font-mono text-ink-body" x-text="s.number"></p>
                                <p class="text-[11px] text-ink-muted" x-show="s.category" x-text="s.category"></p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="s.tone" x-text="s.text"></span>
                        </li>
                    </template>
                </ul>

                {{-- The guardian: for a minor, a PWD, or someone the office gave one to --}}
                <div x-show="result.guardian_applicable" class="rounded-2xl border-2 border-brand-200 bg-white p-4" data-testid="id-scan-guardian">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.08em] text-brand-700 flex items-center gap-1.5"><i data-lucide="shield-user" class="w-3.5 h-3.5"></i>Guardian</p>

                    <p x-show="result.guardian_withheld" class="mt-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 text-sm text-amber-900">Guardian contact withheld while the MSWDO handles a case for this person. Contact the MSWDO.</p>

                    <template x-if="guardian">
                        <div class="mt-1.5 space-y-1">
                            <p class="text-base font-bold text-ink break-words" x-text="guardian.name"></p>
                            <p class="text-sm text-ink-body" x-show="guardian.relationship" x-text="guardian.relationship"></p>
                            <div x-show="guardian.contact" class="flex flex-wrap items-center gap-2 pt-1">
                                <a x-show="tel(guardian.contact)" :href="tel(guardian.contact)" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200"><i data-lucide="phone" class="w-4 h-4"></i>Call</a>
                                <span class="font-mono text-base font-semibold text-ink select-all" x-text="guardian.contact"></span>
                                <button type="button" @click="copyContact()" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="copy" class="w-3.5 h-3.5"></i><span x-text="copied ? 'Copied' : 'Copy'"></span></button>
                            </div>
                            <p x-show="!guardian.contact" class="text-sm text-ink-muted">No phone number on record.</p>
                            <p x-show="guardian.address" class="text-sm text-ink-body break-words"><span class="text-ink-muted">Address:</span> <span x-text="guardian.address"></span></p>
                            <p class="text-[11px] text-ink-muted pt-1" x-text="'Source: ' + source(guardian.source)"></p>
                            <a x-show="guardian.individual_ref" :href="recordHref(guardian.individual_ref)" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="user-round-search" class="w-3.5 h-3.5"></i>The guardian's own record</a>
                        </div>
                    </template>

                    {{-- Asked for with a reason; logged --}}
                    <div x-show="!guardian && result.guardian_available && !result.guardian_withheld" class="mt-2 space-y-2">
                        <x-ui.button size="sm" icon="eye" x-show="!rvOpen" @click="openReveal()">Show guardian contact</x-ui.button>
                        <div x-show="rvOpen" class="space-y-2 rounded-xl bg-slate-50 border border-slate-200 p-3">
                            <label for="id-scan-reason" class="block text-xs font-semibold text-slate-700">Why do you need it?</label>
                            <select id="id-scan-reason" x-model="rvReason" class="w-full rounded-xl border-0 surface-pressed-sm py-2 px-3 text-sm text-slate-700 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="">Choose a reason</option>
                                <template x-for="r in reasons" :key="r.key"><option :value="r.key" x-text="r.en"></option></template>
                            </select>
                            <div x-show="rvReason === 'other'">
                                <label for="id-scan-note" class="block text-xs font-semibold text-slate-700 mb-1">Say why</label>
                                <input id="id-scan-note" type="text" x-model="rvNote" maxlength="200" class="w-full rounded-xl border-0 surface-pressed-sm py-2 px-3 text-sm text-slate-700 focus:ring-4 focus:ring-brand-100 outline-none">
                            </div>
                            <p x-show="rvError" role="alert" class="text-xs text-rose-700" x-text="rvError"></p>
                            <div class="flex items-center gap-2">
                                <x-ui.button size="sm" icon="eye" x-bind:disabled="rvBusy || !!rvProblem" @click="reveal()"><span x-text="rvBusy ? 'Getting it…' : 'Show contact'"></span></x-ui.button>
                                <x-ui.button size="sm" variant="secondary" @click="closeReveal()">Cancel</x-ui.button>
                            </div>
                        </div>
                        <p class="text-[11px] text-ink-muted flex items-start gap-1"><i data-lucide="scroll-text" class="w-3 h-3 shrink-0 mt-px"></i>Each view of a guardian's contact is logged with your name and the reason.</p>
                    </div>

                    <div x-show="!result.guardian_available && !result.guardian_withheld" class="mt-2 text-sm text-ink-body">
                        <p>No guardian on record.</p>
                        <a x-show="holder?.individual_ref" :href="recordHref(holder?.individual_ref)" class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline"><i data-lucide="user-plus" class="w-4 h-4"></i>Add one on the resident record</a>
                        <p x-show="!holder?.individual_ref" class="text-xs text-ink-muted mt-0.5">This account is not linked to a registry record yet, so there is no record to add one to.</p>
                    </div>
                </div>
            </div>
            </template>

            <button type="button" x-show="state !== 'idle' && state !== 'loading'" x-cloak @click="again()" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline"><i data-lucide="scan-qr-code" class="w-4 h-4"></i>Scan another</button>
        </div>
    </div>
</x-ui.modal>
</div>
</template>
