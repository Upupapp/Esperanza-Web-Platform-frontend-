{{-- communications/feedback.blade.php — the feedback desk (scope round
     2026-10-15). Residents' feedback and support tickets, within the
     officer's reach (a department sees its office's, a barangay its hall's,
     the municipal desk every one): the queue, the whole conversation,
     replies that set the status, routing to an office and assigning to an
     officer, and how residents rated the FAQs and tutorials. Support Tickets
     moved here from Settings; /admin/settings/support opens this tab.
     Component feedbackDesk in resources/js/scope-admin-records.js. --}}

<div x-show="tab === 'feedback'" x-cloak x-data="feedbackDesk" class="space-y-4">

    {{-- The queue's filters --}}
    <div class="surface-glass rounded-[26px] p-3 space-y-3">
        <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto" role="group" aria-label="Tickets by status">
            <template x-for="c in chips" :key="c.key">
                <button type="button" @click="setStatus(c.key)" :aria-pressed="status === c.key" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap flex items-center gap-1.5" :class="status === c.key ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                    <span x-text="c.label"></span>
                    <span x-show="chipCount(c.key) !== undefined" class="text-[10px] font-bold px-1.5 rounded-full bg-slate-200/80 text-slate-700" x-text="chipCount(c.key)"></span>
                </button>
            </template>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search tickets" type="search" x-model="search" placeholder="Ticket, subject or request reference…" class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
            </div>
            <select aria-label="Category" x-model="category" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm max-w-full">
                <option value="">All categories</option>
                <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
            </select>
            <select aria-label="Office" x-model="office" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm max-w-full">
                <option value="">All offices</option>
                <option value="none">Not routed yet</option>
                <template x-for="o in offices" :key="o.name"><option :value="o.name" x-text="officeName(o)"></option></template>
            </select>
            <label class="inline-flex items-center gap-2 text-xs text-ink-body px-1 cursor-pointer select-none">
                <input type="checkbox" x-model="mine" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                Assigned to me
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">

        {{-- The queue: cards, so it reads on a phone too. --}}
        <div class="lg:col-span-2 space-y-2.5" :class="sel ? 'hidden lg:block' : ''">
            <div x-show="loading && !rows.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /><x-ui.skeleton class="h-24 opacity-60" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the tickets.'" retryExpr="load()" /></div>
            <div x-show="loaded && !loading && !failed && !rows.length" x-cloak>
                <x-ui.empty-state icon="inbox" title="No tickets here." description="Nothing matches these filters within your office's reach." />
            </div>
            <ul x-show="rows.length && !failed" class="space-y-2.5" :aria-busy="loading">
                <template x-for="t in rows" :key="t.ref">
                    <li>
                        <button type="button" @click="openTicket(t.ref)" :aria-current="selRef === t.ref ? 'true' : null" class="w-full text-left surface-glass rounded-2xl px-3.5 py-3 hover:shadow-card-hover focus-visible:ring-4 focus-visible:ring-brand-100 outline-none transition" :class="selRef === t.ref ? 'ring-2 ring-brand-300' : ''">
                            <span class="flex items-start gap-2">
                                <span class="text-sm font-semibold text-ink flex-1 min-w-0 break-words line-clamp-2" x-text="t.subject"></span>
                                <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset whitespace-nowrap" :class="tone(t.status)"><span class="w-1.5 h-1.5 rounded-full" :class="dot(t.status)"></span><span x-text="t.status"></span></span>
                            </span>
                            <span class="block text-[11px] text-ink-muted mt-1 break-words" x-text="[t.ref, t.category, t.citizen?.name, t.citizen?.barangay ? 'Brgy. ' + t.citizen.barangay : null].filter(Boolean).join(' · ')"></span>
                            <span class="flex flex-wrap items-center gap-1.5 mt-1.5 text-[11px]">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5" :class="t.office ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'"><i data-lucide="building-2" class="w-3 h-3"></i><span x-text="officeLabel(t)"></span></span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-slate-100 text-slate-700"><i data-lucide="user" class="w-3 h-3"></i><span x-text="t.assigned_to ? t.assigned_to.name : 'Unassigned'"></span></span>
                                <span x-show="t.request_ref" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-slate-100 text-slate-700 font-mono"><i data-lucide="link" class="w-3 h-3"></i><span x-text="t.request_ref"></span></span>
                            </span>
                            <span class="block text-[11px] text-ink-muted mt-1" x-text="'Last activity ' + when(t.last_activity_at) + ' · ' + (t.messages_count || 0) + ((t.messages_count || 0) === 1 ? ' reply' : ' replies')"></span>
                        </button>
                    </li>
                </template>
            </ul>
            <div x-show="page < lastPage && !failed" x-cloak class="flex justify-center pt-1">
                <x-ui.button variant="secondary" size="sm" x-bind:disabled="more" @click="loadMore()"><span x-text="more ? 'Loading…' : 'Show more'"></span></x-ui.button>
            </div>
        </div>

        {{-- The ticket --}}
        <div class="lg:col-span-3 scroll-mt-24" x-ref="ticket" :class="sel ? '' : 'hidden lg:block'">
            <x-ui.card x-show="!sel" class="text-sm text-ink-muted flex items-center gap-2"><i data-lucide="message-square-reply" class="w-4 h-4 shrink-0"></i>Choose a ticket to read the conversation and answer it.</x-ui.card>

            <div x-show="sel" x-cloak class="surface-glass rounded-[26px] p-4 sm:p-5 space-y-4">
                <button type="button" @click="closeTicket()" class="lg:hidden inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700"><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>Back to the tickets</button>

                <div class="flex flex-wrap items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] text-ink-muted" x-text="[sel?.ref, sel?.category].filter(Boolean).join(' · ')"></p>
                        <h3 class="text-base font-semibold text-ink break-words" x-text="sel?.subject"></h3>
                    </div>
                    <span x-show="sel?.status" class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset whitespace-nowrap" :class="tone(sel?.status)"><span class="w-1.5 h-1.5 rounded-full" :class="dot(sel?.status)"></span><span x-text="sel?.status"></span></span>
                </div>

                <p x-show="selFailed" class="text-xs text-rose-700" role="alert">
                    <span x-text="sel?.notFound ? 'This ticket is not in your reach, or no longer exists.' : 'Could not load the conversation.'"></span>
                    <button type="button" x-show="!sel?.notFound" class="underline" @click="openTicket(selRef)">Try again</button>
                </p>

                <dl x-show="!sel?.notFound" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Resident</dt><dd class="font-semibold text-ink text-right break-words" x-text="[sel?.citizen?.name, sel?.citizen?.account_no].filter(Boolean).join(' · ') || '—'"></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Barangay</dt><dd class="font-semibold text-ink" x-text="(sel?.citizen?.barangay || sel?.barangay) ? 'Brgy. ' + (sel?.citizen?.barangay || sel?.barangay) : '—'"></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Office</dt><dd class="font-semibold text-ink text-right" x-text="sel?.office ? (sel.office === 'Barangay Office' ? officeLabel(sel) : sel.office) : 'Not routed yet'"></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Assigned to</dt><dd class="font-semibold text-ink text-right" x-text="sel?.assigned_to?.name || 'Nobody yet'"></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">About request</dt><dd class="font-semibold text-right"><a x-show="sel?.request_ref" :href="requestLink(sel?.request_ref)" class="font-mono text-brand-700 hover:underline" x-text="sel?.request_ref"></a><span x-show="!sel?.request_ref" class="text-ink">—</span></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Filed</dt><dd class="font-semibold text-ink text-right" x-text="when(sel?.created_at) || '—'"></dd></div>
                    <div class="flex justify-between gap-2" x-show="sel?.resolved_at"><dt class="text-ink-muted">Resolved</dt><dd class="font-semibold text-ink text-right" x-text="when(sel?.resolved_at)"></dd></div>
                </dl>

                {{-- Routing and assignment (comm_feedback assign) --}}
                <div x-show="mayAssign && !sel?.notFound && sel?.ref" class="rounded-xl border border-slate-200 p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink-muted">Send it to the office that can answer it, or hand it to one officer.</p>
                        <button type="button" x-show="!routing" @click="startRouting()" class="text-xs font-semibold rounded-xl px-3 py-1.5 bg-brand-50 text-brand-700 hover:bg-brand-100">Route or assign</button>
                    </div>
                    <div x-show="routing" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label for="fb-route-office" class="block text-[11px] font-semibold text-ink-muted mb-1">Office</label>
                            <select id="fb-route-office" x-model="routeOffice" @change="loadAssignees()" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="" :disabled="!!sel?.office">Not routed yet</option>
                                {{-- :selected as well: these options can arrive after the value is set, and x-model selects only when the value changes. --}}
                                <template x-for="o in offices" :key="o.name"><option :value="o.name" :selected="o.name === routeOffice" x-text="officeName(o)"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="fb-route-officer" class="block text-[11px] font-semibold text-ink-muted mb-1">Officer</label>
                            <select id="fb-route-officer" x-model="routeTo" :disabled="assigneesLoading" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none disabled:opacity-60">
                                <option value="">Nobody yet (the office's queue)</option>
                                <template x-for="a in assignees" :key="a.employee_id"><option :value="a.employee_id" :selected="a.employee_id === routeTo" x-text="a.name + (a.dept ? ' · ' + a.dept : '')"></option></template>
                            </select>
                            <p x-show="assigneesLoading" class="text-[11px] text-ink-muted mt-1">Loading the officers…</p>
                            <p x-show="!assigneesLoading && !assignees.length" class="text-[11px] text-ink-muted mt-1">No officer of this office can take tickets yet.</p>
                        </div>
                        <p x-show="routeError" class="sm:col-span-2 text-xs text-rose-600" role="alert" x-text="routeError"></p>
                        <div class="sm:col-span-2 flex flex-wrap justify-end gap-2">
                            <button type="button" @click="routing = false; routeError = ''" :disabled="assigning" class="text-xs font-semibold rounded-xl px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
                            <x-ui.button size="sm" icon="check" x-bind:disabled="assigning || assigneesLoading" @click="saveRouting()"><span x-text="assigning ? 'Saving…' : 'Save'"></span></x-ui.button>
                        </div>
                    </div>
                </div>

                {{-- The conversation, in order: the resident's ticket, then each reply. --}}
                <div x-show="selLoading && !thread.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-16 opacity-80" /></div>
                <ol x-show="thread.length" class="space-y-3" aria-label="Conversation">
                    <template x-for="(m, k) in thread" :key="k">
                        <li class="flex" :class="m.from === 'staff' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[92%] sm:max-w-[80%] rounded-2xl px-3.5 py-2.5 border" :class="m.from === 'staff' ? 'bg-brand-50 border-brand-100' : 'bg-white border-slate-200'">
                                <p class="text-[11px] font-semibold" :class="m.from === 'staff' ? 'text-brand-700' : 'text-slate-600'" x-text="(m.from === 'staff' ? 'Staff' : 'Resident') + (m.name ? ' · ' + m.name : '')"></p>
                                <p class="text-sm text-slate-800 whitespace-pre-line break-words mt-0.5" x-text="m.body"></p>
                                <p class="text-[10.5px] text-ink-muted mt-1" x-text="when(m.at)"></p>
                            </div>
                        </li>
                    </template>
                </ol>
                <p x-show="sel?.attachment" class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="paperclip" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'The resident attached ' + (sel?.attachment?.name || 'a file') + (sel?.attachment?.bytes ? ' (' + size(sel.attachment.bytes) + ')' : '')"></span></p>

                {{-- A reply, and the status it leaves the ticket in (comm_feedback edit) --}}
                <form x-show="mayReply && sel?.ref && !sel?.notFound" @submit.prevent="sendReply()" class="space-y-2 border-t border-brand-600/10 pt-4" novalidate>
                    <label for="fb-reply" class="block text-xs font-semibold text-ink">Reply to the resident</label>
                    <textarea id="fb-reply" x-model="reply" rows="4" maxlength="4000" placeholder="The resident reads this in their notifications and on the ticket." class="w-full rounded-2xl border-0 surface-pressed p-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <label for="fb-status" class="text-xs text-ink-muted shrink-0">Then the ticket is</label>
                        <select id="fb-status" x-model="replyStatus" class="text-xs rounded-xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="o in replyOptions" :key="o[0]"><option :value="o[0]" :selected="o[0] === replyStatus" x-text="o[1]"></option></template>
                        </select>
                        <x-ui.button type="submit" size="sm" icon="send" class="sm:ml-auto" x-bind:disabled="sending || !reply.trim()"><span x-text="sending ? 'Sending…' : 'Send reply'"></span></x-ui.button>
                    </div>
                    <p x-show="replyError" class="text-xs text-rose-600" role="alert" x-text="replyError"></p>
                </form>
                <p x-show="!mayReply && sel?.ref && !sel?.notFound" class="text-xs text-ink-muted border-t border-brand-600/10 pt-3">Answering tickets needs the Feedback edit permission.</p>
            </div>
        </div>
    </div>

    {{-- Help content feedback: which FAQs and tutorials residents found helpful. --}}
    <x-ui.card class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-ink flex items-center gap-1.5"><i data-lucide="thumbs-up" class="w-4 h-4 text-ink-muted"></i>Help content feedback</h3>
                <p class="text-xs text-ink-muted">What residents said about the FAQs and tutorials, least helpful first.</p>
            </div>
            <a x-show="$store.session.canPerm('set_site_content', 'view')" href="{{ route('admin.settings.site-content') }}" class="text-xs font-semibold text-brand-700 hover:underline">Edit the help content</a>
        </div>
        <div x-show="helpLoading && !help.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-8" /><x-ui.skeleton class="h-8 opacity-80" /></div>
        <p x-show="helpFailed" class="text-xs text-rose-700" role="alert">Could not load the ratings. <button type="button" class="underline" @click="loadHelp()">Try again</button></p>
        <p x-show="helpLoaded && !helpFailed && !help.length" class="text-xs text-ink-muted">No resident has rated a FAQ or tutorial yet.</p>
        <ul x-show="help.length" class="divide-y divide-brand-600/[.08]">
            <template x-for="(h, k) in helpShown" :key="h.doc_key + ':' + k">
                <li class="py-2 flex items-start gap-3">
                    <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide rounded px-1.5 py-0.5 bg-slate-100 text-slate-600 shrink-0" x-text="h.doc_key === 'tutorials' ? 'Tutorial' : 'FAQ'"></span>
                    <p class="text-sm text-ink flex-1 min-w-0 break-words" x-text="h.item"></p>
                    <span class="shrink-0 inline-flex items-center gap-3 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1 text-emerald-700"><i data-lucide="thumbs-up" class="w-3.5 h-3.5"></i><span x-text="h.helpful"></span><span class="sr-only">found it helpful</span></span>
                        <span class="inline-flex items-center gap-1 text-rose-700"><i data-lucide="thumbs-down" class="w-3.5 h-3.5"></i><span x-text="h.not_helpful"></span><span class="sr-only">did not</span></span>
                    </span>
                </li>
            </template>
        </ul>
        <button type="button" x-show="help.length > 6" @click="helpAll = !helpAll" class="text-xs font-semibold text-brand-700 hover:underline" x-text="helpAll ? 'Show fewer' : 'Show all ' + help.length"></button>
    </x-ui.card>
</div>
