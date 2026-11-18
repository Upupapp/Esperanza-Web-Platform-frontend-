{{-- ============================================================
     Dokyu > Document types and Tulong > Tulong types (owner, 2026-10-30:
     "give the option to modify the dokyu and tulong in their respective tabs,
     in dokyu screen they can edit, add, modify the dokyu offered in the
     system"). They were Settings > Service Catalog. Each is a page of its
     own (routes/web.php, $kind dokyu | tulong) under its tab's address,
     reached from the view switch beside the tab's title: the Dokyu page is
     near its page-weight budget, and the editor's script loads only here
     (resources/js/catalog-editor.js, by path in app.js). For whoever holds
     set_service_catalog view: the Super Administrator, the Municipal
     Administrator, and each Department Head for their own office's types.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $isDokyu = $kind === 'dokyu';
    // The switch beside the title, as on the tab's own page: label, icon,
    // address (null: this page), the permission that shows it (its view
    // action), and the module that shows it. Citizen Document Storage shows
    // only to a role that may open Papeles, the Dokyu page's own check (UI
    // sweep 2026-11-18: Department Staff got Access Restricted there).
    $views = $isDokyu
        ? [
            ['Requests', 'file-text', route('admin.document-requests'), null, null],
            ['Document types', 'layout-list', null, null, null],
            ['Citizen Document Storage', 'folder-open', route('admin.papeles'), null, 'papeles'],
        ]
        : [
            ['Cases', 'folder-open', route('admin.assistance-requests'), null, null],
            ['Sessions', 'calendar-days', route('admin.assistance-requests').'?view=sessions', 'tulong_sessions', null],
            ['Tulong types', 'layout-list', null, null, null],
        ];
@endphp

<x-layouts.admin
    :title="$isDokyu ? 'Dokyu' : 'Tulong'"
    :subtitle="$isDokyu ? 'Document types: what residents can request, its fee, documents, steps and form.' : 'Tulong types: the assistance residents can apply for, its documents, steps and form.'"
    :active="$isDokyu ? 'document-requests' : 'assistance-requests'"
>
    <div class="animate-fade-up">
        <nav class="flex flex-wrap items-center gap-1 surface-pressed rounded-2xl p-1 w-fit max-w-full mb-4" aria-label="{{ $isDokyu ? 'Dokyu' : 'Tulong' }} views">
            @foreach($views as [$viewLabel, $viewIcon, $viewHref, $viewPerm, $viewModule])
                @if($viewHref === null)
                    <span aria-current="page" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl whitespace-nowrap bg-white text-brand-700 shadow-sm"><i data-lucide="{{ $viewIcon }}" class="w-3.5 h-3.5"></i>{{ $viewLabel }}</span>
                @else
                    <a href="{{ $viewHref }}" @if($viewPerm) x-show="$store.session.canPerm('{{ $viewPerm }}', 'view')" x-cloak @elseif($viewModule) x-show="$store.session.can('{{ $viewModule }}')" x-cloak @endif
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl whitespace-nowrap text-ink-muted hover:text-ink transition-colors"><i data-lucide="{{ $viewIcon }}" class="w-3.5 h-3.5"></i>{{ $viewLabel }}</a>
                @endif
            @endforeach
        </nav>

        @include('admin.partials.type-catalog', ['kind' => $kind])
    </div>
</x-layouts.admin>
