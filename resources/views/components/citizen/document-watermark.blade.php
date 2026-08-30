{{-- LANGUAGE (TAB 09): nothing to translate. This component renders a single
     decorative <img> with alt="" and aria-hidden="true" and emits no
     user-visible text in any language. Recorded so its absence from the
     bilingual coverage count is a decision rather than an omission. --}}
{{-- Large, centered, low-opacity Esperanza seal watermark — shared by every
     Dokyu paper (barangay-certificate-preview + municipal-request-preview)
     so the treatment stays identical everywhere rather than being
     reimplemented per document. Absolutely positioned and z-index 0 as the
     FIRST child of its .doc-paper parent, so every later sibling (which all
     already use position:relative per this component's own siblings) paints
     over it in normal DOM order — no text is ever covered. pointer-events
     none keeps it from intercepting clicks on the fields above it (e.g. the
     editable inputs — see doc-field.blade.php). Prints and appears in the
     downloaded PDF exactly as on screen since it's a plain <img>, not a CSS
     background-image some print/PDF pipelines drop.

     `background` is an Alpine expression string (like every other prop on
     this component tree — see barangay-certificate-preview's own header
     comment) evaluating to a {src, opacity, size, position} object, so the
     admin's selected Dokyu template/background state (documentBackground in
     document-requests.blade.php) drives this exact same img BEFORE the
     first Edit Document click too — previously this was a second, static,
     hardcoded copy of the default Esperanza mark that never reflected a
     chosen barangay template at all, which is what made "View Document" (or
     a request nobody had edited yet) always show the generic 5%-opacity
     Esperanza seal instead of the applied template's own watermark: exactly
     the "top/bottom visible, middle white" symptom, just one layer up from
     where it looked like a CSS stacking bug. Defaults to the same literal
     values this element used to hardcode, so any caller that doesn't pass
     `background` (the citizen portal's own document view, which has no
     Dokyu editor state at all) renders identically to before. --}}
@props([
    'background' => "{ src: '" . asset('images/esperanza/esperanza-seal.png') . "', opacity: 5, size: 'large', position: 'center' }",
])
<img
    class="doc-watermark"
    alt=""
    aria-hidden="true"
    x-show="({!! $background !!}).src"
    x-cloak
    :src="({!! $background !!}).src"
    :style="window.DokyuEditor.watermarkStyle({!! $background !!})"
>
