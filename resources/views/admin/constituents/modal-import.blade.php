{{-- ============================================================
     FILE: modal-import.blade.php
     Constituents -> Import from a file (owner, 2026-10-09): the LGU's own
     resident lists, in any common format, into the registry. Logic:
     resources/js/resident-import-ui.js (state, steps) and
     resident-import.js (reading the file; loaded on first open).
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
<div x-data="residentImport" x-on:open-resident-import.window="openImport($event.detail?.panel || 'import')" x-on:beforeunload.window="guardLeave($event)">
{{-- The window loads apart from the page: lazy/constituents-import. --}}
<x-lazy-partial name="constituents-import" when="open" />
</div>
