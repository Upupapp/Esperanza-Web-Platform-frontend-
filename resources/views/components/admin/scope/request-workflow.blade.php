{{-- The service's workflow on one request (2026-10-17): each step of the
     Citizen's Charter, who is responsible and how long it takes, and which
     are done. Filing, payment, approval and release finish by themselves;
     an officer who may review the request marks the office steps done, with
     an optional note, and the resident is told. Approve and Release wait for
     the office steps before them. Hidden for a request filed before its
     service had a workflow. Logic: requestWorkflow() in
     resources/js/scope-admin-requests.js. --}}
<div x-data="requestWorkflow()" x-show="wf" x-cloak class="mt-4 rounded-2xl border border-brand-600/10 bg-white/60 p-4" data-testid="request-workflow">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="workflow" class="w-3.5 h-3.5"></i> Steps of this service</p>
        <p class="text-[11px] text-ink-muted" x-text="wfSummary"></p>
    </div>
    <ol class="mt-2 space-y-1.5">
        <template x-for="st in (wf?.steps || [])" :key="'wf-' + st.index">
            <li class="flex items-start gap-3 rounded-xl px-2.5 py-2" :class="wf?.current === st.index ? 'bg-brand-50/70 ring-1 ring-brand-200' : ''">
                <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full grid place-items-center text-[11px] font-bold"
                      :class="st.done ? 'bg-emerald-500 text-white' : (wf?.current === st.index ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500')"
                      :aria-label="st.done ? 'Done' : (wf?.current === st.index ? 'Current step' : 'Not yet')">
                    <span x-show="st.done"><i data-lucide="check" class="w-3.5 h-3.5"></i></span>
                    <span x-show="!st.done" x-text="st.index + 1"></span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink" x-text="st.name"></p>
                    <p class="text-[11px] text-ink-muted" x-text="stepMeta(st)"></p>
                    <p x-show="st.done && (st.done_by || st.note)" class="text-[11px] text-emerald-800 break-words" x-text="doneLine(st)"></p>
                    <div x-show="wfMay && st.markable && !st.done && noteFor === st.index" class="mt-1.5 flex flex-col sm:flex-row gap-2">
                        <label :for="'wf-note-' + st.index" class="sr-only">Note for this step (optional)</label>
                        <input :id="'wf-note-' + st.index" type="text" x-model="note" maxlength="500" placeholder="Note, optional (the resident does not see it)" class="flex-1 min-w-0 text-xs rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        <x-ui.button size="sm" icon="check" x-bind:disabled="busy !== null" @click="mark(st, true)"><span x-text="busy === st.index ? 'Saving…' : 'Mark done'"></span></x-ui.button>
                    </div>
                </div>
                <div class="shrink-0" x-show="wfMay && st.markable">
                    <button type="button" x-show="!st.done && noteFor !== st.index" @click="noteFor = st.index; note = ''" class="text-xs font-semibold rounded-xl px-3 py-1.5 bg-brand-50 text-brand-700 hover:bg-brand-100">Mark done…</button>
                    {{-- Undo archives the mark, never deletes it (2026-11-14). --}}
                    <button type="button" x-show="st.done" @click="mark(st, false)" :disabled="busy !== null" title="Marks the step not done; the mark is archived and stays in the history" class="text-[11px] font-medium text-ink-muted hover:text-rose-700 underline">Undo</button>
                </div>
            </li>
        </template>
    </ol>
    <p x-show="wfMay && pendingForAction" class="mt-2 text-[11px] text-amber-800" x-text="pendingForAction"></p>
</div>
