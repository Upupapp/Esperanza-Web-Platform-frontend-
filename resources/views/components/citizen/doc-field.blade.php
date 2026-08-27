{{-- One editable value on a generated Dokyu paper — a read-only span while
     viewing (identical markup/behavior to what every doc-key template
     already did with a plain x-text span) and a real text input while the
     Admin's own `editing` flag (see document-requests.blade.php's
     editingDocument state) is true, bound to the exact same snapshot path
     so Print/Download PDF immediately reflect whatever was typed — no
     separate "preview" copy to keep in sync.

     `transform`:
       - 'none' (default): plain value, x-model bound directly.
       - 'lowercase': display-only lowercasing (e.g. civil status inside a
         sentence) — the underlying value itself is never edited in
         lowercase, only shown that way, matching what the original
         template already did.
       - 'array': the field is a string[] (e.g. purpose checkboxes) shown
         joined by ", "; the input edits a comma-separated version of the
         same list.

     Never used for decorative elements (seals, letterhead labels, static
     certificate wording) — only for the specific values a document
     actually varies by, matching the CORE RULE this whole feature is
     built around. --}}
@props(['bind', 'field', 'fallback' => '', 'class' => '', 'transform' => 'none', 'tag' => 'span', 'editing' => 'false'])
@php
    $expr = "{$bind}.{$field}";
    $displayExpr = match ($transform) {
        'lowercase' => "({$expr} || '').toLowerCase()",
        'array' => "({$expr} || []).join(', ')",
        default => $expr,
    };
    $fallbackJs = "'" . addslashes($fallback) . "'";
@endphp
<{{ $tag }} class="{{ $class }}" x-show="!({{ $editing }})" x-text="{{ $displayExpr }} || {{ $fallbackJs }}"></{{ $tag }}>
@if ($transform === 'array')
    <input
        type="text"
        x-show="{{ $editing }}"
        x-cloak
        :value="({{ $expr }} || []).join(', ')"
        @input="{{ $expr }} = $event.target.value.split(',').map(s => s.trim()).filter(Boolean)"
        placeholder="{{ $fallback }}"
        class="doc-field-input {{ $class }}"
    >
@else
    <input
        type="text"
        x-show="{{ $editing }}"
        x-cloak
        x-model="{{ $expr }}"
        placeholder="{{ $fallback }}"
        class="doc-field-input {{ $class }}"
    >
@endif
