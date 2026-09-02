{{-- LANGUAGE (TAB 09): exempt. This component renders one decorative <img>
     with alt="" and aria-hidden="true" and carries no user-visible text at
     all -- there is nothing here to translate. The empty alt is deliberate
     and correct for artwork: giving it a label would announce a corner
     flourish to a screen reader as though it were content. --}}
{{-- Official full-page template artwork (corner geometric accents etc.) —
     a real per-template design layer, deliberately separate from the
     large center watermark (document-watermark.blade.php) and the small
     header seals. Sits behind both watermark and .doc-content as a plain
     <img>, not a CSS background-image: the exact same reasoning as
     document-watermark.blade.php's own header comment — a CSS background
     is silently dropped by real browser Print unless the page opts into
     print-color-adjust:exact, and is one more thing that can go wrong in
     the PDF export pipeline, where a plain <img> just works (and is
     already covered by DokyuEditor.waitForImages() before html2pdf runs).

     `background` is the same Alpine expression object every other prop on
     this component tree receives (see barangay-certificate-preview's own
     header comment) — only `.decorative` is read here. Each key renders
     its own asset; a key with no matching branch (Villa, Standard, or no
     template at all) renders nothing, which is deliberately how Villa
     stays on plain paper until a real Villa reference is available. --}}
@props([
    'background' => "{ decorative: null }",
])
<img
    class="doc-template-artwork"
    alt=""
    aria-hidden="true"
    x-show="({!! $background !!}).decorative === 'labangtaytay'"
    x-cloak
    src="{{ asset('images/esperanza/templates/barangay-labangtaytay-artwork.svg') }}"
>
