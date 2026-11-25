{{-- ============================================================
     FILE: modal-request-import.blade.php
     Dokyu / Tulong -> Import records (owner, 2026-10-12): an office's own
     records of documents issued or assistance given, brought into the portal
     and matched to the resident each was for. Logic:
     resources/js/request-import-ui.js (state, steps) and request-import.js
     (reading the file; loaded on first open).
     Include with ['importType' => 'tulong' | 'dokyu'] where the button goes.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $importType = $importType ?? 'tulong';
    $importKeys = $importType === 'tulong'
        ? collect(config('esperanza_tulong_types.types'))->pluck('key')->filter()->values()->all()
        : collect(config('esperanza_municipal_documents.documents'))->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])->pluck('key')->filter()->values()->all();
    $importBarangays = config('esperanza.barangays');
    $isTulong = $importType === 'tulong';
@endphp
<div class="contents" x-data="requestImport('{{ $importType }}', @js($importKeys), @js($importBarangays))"
     x-on:open-request-import.window="if ($event.detail?.type === type) openImport($event.detail?.panel || 'import')"
     x-on:beforeunload.window="guardLeave($event)">
<button type="button" x-show="allowed" x-cloak @click="openImport()"
        title="Bring in the office's own records from a file and match them to residents."
        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-2xl px-3 py-2 border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors shrink-0">
    <i data-lucide="file-up" class="w-3.5 h-3.5"></i> Import records
</button>
{{-- The window itself (modal-request-import-window) loads apart from the page: lazy/request-import-*. --}}
<x-lazy-partial name="request-import-{{ $importType }}" when="open" />
</div>
