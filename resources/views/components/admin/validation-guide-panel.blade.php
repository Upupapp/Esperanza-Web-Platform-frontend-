{{-- ============================================================
     FILE: validation-guide-panel.blade.php
     Partial: Manual Document Validation & Application Decision Guide,
     shown inside a Dokyu/Tulong request modal — collapsed by default.
     Alpine scope: parent x-data (selected, serviceGuides, evidenceTier).
     Static reference content (universal checklist, evidence hierarchy,
     mismatch rule, decision tree, general checklist) is rendered directly
     from PHP since it never changes per-request; only the per-service
     checklist reacts to which request is open.
     ============================================================ --}}
@props(['group', 'universalChecklist', 'evidenceHierarchy', 'mismatchRule', 'decisionTree', 'generalChecklist', 'adminReviewChecklist' => [], 'knownGaps' => []])

<div class="mt-4 rounded-2xl border border-slate-200 overflow-hidden" x-data="{ guideOpen: false }">
    <button
        type="button"
        @click="guideOpen = !guideOpen; $nextTick(() => window.renderIcons?.())"
        class="w-full flex items-center justify-between gap-3 px-4 py-3 bg-slate-50/80 hover:bg-slate-50 transition-colors text-left"
    >
        <span class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="shield-question" class="w-4 h-4"></i></span>
            <span>
                <span class="text-xs font-semibold text-navy-900 block">Manual Validation Guide</span>
                <span class="text-[10.5px] text-slate-400">{{ $group }} document checklist &amp; decision framework — manual reference, not automatic approval</span>
            </span>
        </span>
        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-[160ms] ease-standard" :class="guideOpen && 'rotate-180'"></i>
    </button>

    <div x-show="guideOpen" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 border-t border-slate-200 space-y-4">

        <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 leading-snug">
            <span class="font-semibold">Manual administrative guide — no AI/automatic approval.</span>
            This does not automatically approve, reject, score, or authenticate documents. Final action remains with the authorized LGU office.
        </p>

        {{-- Per-service checklist — reactive to whichever request is open --}}
        <template x-if="selectedServiceGuide">
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 bg-white border-b border-slate-100">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-navy-900" x-text="selected?.type"></p>
                        <p class="text-[11px] text-slate-400" x-text="'Office: ' + selectedServiceGuide.office"></p>
                    </div>
                    <span
                        class="text-[10px] font-semibold px-2 py-1 rounded-full shrink-0 whitespace-nowrap"
                        :class="{
                            'bg-emerald-50 text-emerald-700': selectedServiceGuide.source_basis === 'Official Esperanza source',
                            'bg-amber-50 text-amber-700': selectedServiceGuide.source_basis === 'Placeholder-supported',
                            'bg-slate-100 text-slate-600': selectedServiceGuide.source_basis === 'Catalog + general guidance',
                        }"
                        x-text="selectedServiceGuide.source_basis"
                        :title="selectedServiceGuide.source_note"
                    ></span>
                </div>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-[11px]">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 uppercase tracking-wide text-[10px]">
                                <th class="text-left px-3 py-2 font-semibold whitespace-nowrap">Check</th>
                                <th class="text-left px-3 py-2 font-semibold">Accept / approve when</th>
                                <th class="text-left px-3 py-2 font-semibold">If weak, missing, or unclear</th>
                                <th class="text-left px-3 py-2 font-semibold">Reject only when</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="row in selectedServiceGuide.checks" :key="row.check">
                                <tr class="align-top">
                                    <td class="px-3 py-2 font-medium text-navy-900 whitespace-nowrap" x-text="row.check"></td>
                                    <td class="px-3 py-2 text-emerald-700" x-text="row.accept"></td>
                                    <td class="px-3 py-2 text-amber-700" x-text="row.weak"></td>
                                    <td class="px-3 py-2 text-rose-700" x-text="row.reject"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
        <template x-if="!selectedServiceGuide">
            <div>
                <p class="text-[11px] text-slate-400 mb-2 italic">
                    No service-specific checklist is on file yet for <span class="font-medium text-slate-500" x-text="selected?.type"></span> — using the general {{ $group }} checklist below.
                </p>
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-[11px]">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 uppercase tracking-wide text-[10px]">
                                <th class="text-left px-3 py-2 font-semibold whitespace-nowrap">Check</th>
                                <th class="text-left px-3 py-2 font-semibold">Approve when</th>
                                <th class="text-left px-3 py-2 font-semibold">If uncertain / weak</th>
                                <th class="text-left px-3 py-2 font-semibold">Reject when</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($generalChecklist as $row)
                                <tr class="align-top">
                                    <td class="px-3 py-2 font-medium text-navy-900 whitespace-nowrap">{{ $row['check'] }}</td>
                                    <td class="px-3 py-2 text-emerald-700">{{ $row['approve'] }}</td>
                                    <td class="px-3 py-2 text-amber-700">{{ $row['uncertain'] }}</td>
                                    <td class="px-3 py-2 text-rose-700">{{ $row['reject'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        {{-- Universal Document Checklist — same for every attachment on every service --}}
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Universal Document Checklist</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-[11px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 uppercase tracking-wide text-[10px]">
                            <th class="text-left px-3 py-2 font-semibold whitespace-nowrap">Check</th>
                            <th class="text-left px-3 py-2 font-semibold">Pass condition</th>
                            <th class="text-left px-3 py-2 font-semibold">If not passed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($universalChecklist as $row)
                            <tr class="align-top">
                                <td class="px-3 py-2 font-medium text-navy-900 whitespace-nowrap">{{ $row['check'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['pass'] }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $row['if_not'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Evidence Strength Hierarchy --}}
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Evidence Strength Hierarchy</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($evidenceHierarchy as $lvl)
                    @php
                        $lvlColors = [1 => 'bg-slate-100 text-slate-600', 2 => 'bg-blue-50 text-blue-700', 3 => 'bg-emerald-50 text-emerald-700', 4 => 'bg-purple-50 text-purple-700'];
                    @endphp
                    <div class="rounded-lg border border-slate-100 px-2.5 py-2">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $lvlColors[$lvl['level']] }}">L{{ $lvl['level'] }}</span>
                            <span class="text-[11px] font-semibold text-navy-900">{{ $lvl['name'] }}</span>
                        </div>
                        <p class="text-[10.5px] text-slate-500 leading-snug">{{ $lvl['evidence'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mismatch Rule --}}
        <div class="rounded-lg bg-blue-50/60 border border-blue-100 px-3 py-2.5">
            <p class="text-[11px] font-semibold text-blue-800 uppercase tracking-wide mb-1">Mismatch Rule</p>
            <p class="text-[11px] text-blue-800/90 leading-snug">{{ $mismatchRule }}</p>
        </div>

        {{-- Decision Tree --}}
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Decision Tree</p>
            <ol class="space-y-1 list-decimal list-inside">
                @foreach($decisionTree as $step)
                    <li class="text-[11px] text-slate-600 leading-snug">{{ $step }}</li>
                @endforeach
            </ol>
        </div>

        {{-- Admin Review Checklist — a general reminder of the review
             process, not a per-service list; decorative only, no state. --}}
        @if(count($adminReviewChecklist))
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Admin Review Checklist</p>
                <ul class="space-y-1">
                    @foreach($adminReviewChecklist as $item)
                        <li class="text-[11px] text-slate-600 leading-snug flex items-start gap-1.5">
                            <i data-lucide="square" class="w-3 h-3 text-slate-300 shrink-0 mt-0.5"></i>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Source Basis and Known Gaps — the guide's own running list of
             which areas are still provisional pending an official form. --}}
        @if(count($knownGaps))
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Known Gaps — Confirm Before Production Use</p>
                <div class="space-y-1.5">
                    @foreach($knownGaps as $gap)
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-2.5 py-2">
                            <p class="text-[11px] font-semibold text-navy-900">{{ $gap['area'] }}</p>
                            <p class="text-[10.5px] text-slate-500 leading-snug">{{ $gap['status'] }}</p>
                            <p class="text-[10.5px] text-brand-600 leading-snug mt-0.5">→ {{ $gap['action'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
