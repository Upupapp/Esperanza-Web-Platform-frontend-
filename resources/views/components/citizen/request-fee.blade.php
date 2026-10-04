{{-- The request's fee and where it stands, in Track Request. The fee is
     paid at the office, which issues the official receipt; the resident saw
     neither the amount nor the receipt before (QA run 2026-10-05). A free
     request has no payment and shows nothing. The payment status "Approved"
     means paid. --}}
<div
    x-show="selectedRequest?.payment && Number(selectedRequest.payment.amount) > 0"
    x-cloak
    class="mt-5 rounded-xl border p-3.5"
    :class="selectedRequest?.payment?.status === 'Approved' ? 'border-emerald-200 bg-emerald-50/60' : (selectedRequest?.payment?.status === 'Rejected' ? 'border-rose-200 bg-rose-50/60' : 'border-amber-200 bg-amber-50/60')"
>
    <div class="flex items-center justify-between gap-3">
        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><x-ui.t fil="Bayarin" en="Fee" /></p>
        <p class="text-sm font-semibold text-navy-900 tabular-nums" x-text="'₱' + Number(selectedRequest?.payment?.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></p>
    </div>
    <p x-show="selectedRequest?.payment?.status === 'Pending Review'" class="text-xs text-amber-800 mt-1">
        <x-ui.t fil="Babayaran sa opisina; dalhin ang reference number. Ang opisina ang magbibigay ng opisyal na resibo." en="Pay at the office and bring your reference number. The office issues the official receipt." />
    </p>
    <p x-show="selectedRequest?.payment?.status === 'Approved'" class="text-xs text-emerald-800 mt-1">
        <x-ui.t fil="Bayad na. Opisyal na resibo:" en="Paid. Official receipt:" />
        <span class="font-mono" x-text="selectedRequest?.payment?.receipt_no || '—'"></span>
    </p>
    <p x-show="selectedRequest?.payment?.status === 'Rejected'" class="text-xs text-rose-700 mt-1">
        <x-ui.t fil="Hindi natanggap ang bayad. Makipag-ugnayan sa opisina." en="The payment was not accepted. Please contact the office." />
    </p>
</div>
