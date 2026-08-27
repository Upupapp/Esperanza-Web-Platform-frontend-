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
     background-image some print/PDF pipelines drop. --}}
<img
    src="{{ asset('images/esperanza/esperanza-seal.png') }}"
    alt=""
    aria-hidden="true"
    class="absolute top-1/2 left-1/2 pointer-events-none select-none"
    style="width: 68%; max-width: 620px; height: auto; aspect-ratio: 1 / 1; object-fit: contain; transform: translate(-50%, -50%); opacity: 0.05; z-index: 0;"
>
