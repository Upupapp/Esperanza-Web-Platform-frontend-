{{-- ============================================================
     Staff access (owner decision, 2026-11-20: "maybe the head can modify the
     things that the officers can do"; best practice asked for). The office's
     Department Head switches which steps of the office's Dokyu and Tulong
     work its Department Staff take: screen the papers, write the assessment,
     mark ready and release, import past records. Final approval and reject
     stay with the Head and are never offered here. The Super Administrator
     picks any office. A view of the Dokyu and Tulong tabs (the owner wants the
     fewest sidebar entries), at /admin/{document,assistance}-requests/staff-access.
     Logic: staffAccess in resources/js/staff-access.js (GET/PUT
     /admin/staff-access; the server decides who may change what).
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $isDokyu = $kind === 'dokyu';
    $views = $isDokyu
        ? [
            ['Requests', 'file-text', route('admin.document-requests'), null],
            ['Document types', 'layout-list', route('admin.document-requests.types'), 'set_service_catalog'],
            ['Staff access', 'user-cog', null, null],
        ]
        : [
            ['Cases', 'folder-open', route('admin.assistance-requests'), null],
            ['Tulong types', 'layout-list', route('admin.assistance-requests.types'), 'set_service_catalog'],
            ['Staff access', 'user-cog', null, null],
        ];
@endphp

<x-layouts.admin
    :title="$isDokyu ? 'Dokyu' : 'Tulong'"
    subtitle="Staff access: what the office's Department Staff may do on its requests."
    :active="$isDokyu ? 'document-requests' : 'assistance-requests'"
>
    <div class="animate-fade-up">
        <nav class="flex flex-wrap items-center gap-1 surface-pressed rounded-2xl p-1 w-fit max-w-full mb-4" aria-label="{{ $isDokyu ? 'Dokyu' : 'Tulong' }} views">
            @foreach($views as [$viewLabel, $viewIcon, $viewHref, $viewPerm])
                @if($viewHref === null)
                    <span aria-current="page" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl whitespace-nowrap bg-white text-brand-700 shadow-sm"><i data-lucide="{{ $viewIcon }}" class="w-3.5 h-3.5"></i>{{ $viewLabel }}</span>
                @else
                    <a href="{{ $viewHref }}" @if($viewPerm) x-show="$store.session.canPerm('{{ $viewPerm }}', 'view')" x-cloak @endif
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl whitespace-nowrap text-ink-muted hover:text-ink transition-colors"><i data-lucide="{{ $viewIcon }}" class="w-3.5 h-3.5"></i>{{ $viewLabel }}</a>
                @endif
            @endforeach
        </nav>

        <div x-data="staffAccess()" x-init="start()" data-staff-access>
            <div x-show="denied" x-cloak>
                <x-admin.access-restricted module="Staff access" icon="user-cog" />
            </div>

            <div x-show="!denied" class="space-y-4 max-w-3xl">
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-ink">What your Staff may do</h2>
                            <p class="text-xs text-ink-muted mt-1">
                                The Department Staff of <span class="font-semibold text-slate-700" x-text="data.short || data.office || 'your office'"></span>
                                do the legwork on its Dokyu and Tulong requests. Switch a step off to keep it with you. A change applies at once.
                            </p>
                        </div>
                        {{-- The Super Administrator picks the office; a Head has only their own. --}}
                        <select aria-label="Office" x-show="(data.offices || []).length > 1" x-cloak x-model="office" @change="load()"
                                class="text-xs rounded-2xl py-2 pl-3 pr-8 border-0 surface-pressed-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-full sm:max-w-[18rem]">
                            <template x-for="o in (data.offices || [])" :key="o"><option :value="o" x-text="o" :selected="o === data.office"></option></template>
                        </select>
                    </div>

                    <p x-show="loading && !loaded" class="text-xs text-ink-muted mt-4">Loading…</p>
                    <p x-show="failed" x-cloak class="text-xs text-rose-600 mt-4">Could not load Staff access. <button type="button" class="underline font-semibold" @click="load()">Try Again</button></p>

                    <ul class="mt-4 divide-y divide-slate-100" x-show="loaded" x-cloak>
                        <template x-for="s in data.steps" :key="s.step">
                            <li class="flex items-start justify-between gap-4 py-3" :data-step="s.step">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink" :id="'sa-' + s.step" x-text="s.label"></p>
                                    <p class="text-xs text-ink-muted mt-0.5" x-text="s.description"></p>
                                    <p x-show="!s.available" class="text-[11px] text-amber-700 mt-1">Your own role does not hold this step, so Staff cannot be given it.</p>
                                    <p x-show="s.updated_by" class="text-[11px] text-ink-faint mt-1" x-text="'Last changed by ' + s.updated_by + (s.updated_at ? ', ' + when(s.updated_at) : '')"></p>
                                </div>
                                <button type="button" role="switch" :aria-labelledby="'sa-' + s.step" :aria-checked="s.enabled ? 'true' : 'false'"
                                        :disabled="saving === s.step || (!s.enabled && !s.available)" @click="toggle(s)"
                                        class="relative shrink-0 w-12 h-7 rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 disabled:opacity-50" :class="s.enabled ? 'bg-brand-600' : 'bg-slate-300'">
                                    <span class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="s.enabled && 'translate-x-5'"></span>
                                    <span class="sr-only" x-text="s.enabled ? 'On' : 'Off'"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </x-ui.card>

                <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-ink-muted" x-show="loaded" x-cloak>
                    <i data-lucide="lock" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <div>
                        <p><span class="font-semibold text-slate-700">Always the Head's:</span> <span x-text="(data.head_only || []).join(', ')"></span>.</p>
                        <p class="mt-1" x-show="(data.staff || []).length" x-text="'Staff of this office: ' + (data.staff || []).join(', ') + '.'"></p>
                        <p class="mt-1" x-show="loaded && !(data.staff || []).length">This office has no Department Staff yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
