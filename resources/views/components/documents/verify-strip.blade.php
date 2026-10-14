{{-- The foot of a printed document (scope round 2026-10-15): the note staff
     set on the document type's template (Settings > Templates), and once the
     document is issued, its control number, verification code and a QR code
     that opens the website's Verify page. Prints with the paper.

     doc: Alpine expression for the request's issued_documents (a list) or one
     issued document; docKey: Alpine expression for the document type. --}}
@props(['doc' => 'null', 'docKey' => 'null'])

<div x-data="verifyStrip()" x-effect="render({{ $doc }}, {{ $docKey }})" x-show="issued || note" class="relative mt-4 flex items-end justify-between gap-4">
    <p class="text-[10px] italic text-slate-600 max-w-[62%]" x-show="note" x-text="note"></p>
    <div class="flex items-center gap-2 ml-auto" x-show="issued">
        <div class="text-right text-[9px] leading-tight text-slate-600">
            <p>Control No. <span class="font-semibold text-slate-800" x-text="issued ? issued.control_number : ''"></span></p>
            <p>Code <span class="font-mono font-semibold text-slate-800" x-text="issued ? issued.serial : ''"></span></p>
            <p x-show="verifyHost" x-text="'Verify at ' + verifyHost"></p>
        </div>
        <div class="w-[21mm] h-[21mm] bg-white [&>svg]:w-full [&>svg]:h-full" x-html="svg" aria-label="QR code to verify this document" role="img"></div>
    </div>
</div>
