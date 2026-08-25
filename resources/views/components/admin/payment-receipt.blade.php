{{-- ============================================================
     FILE: payment-receipt.blade.php
     Shared "Official Payment Receipt" — identical design to the receipt a
     citizen sees and downloads on the Esperanza Mobile app (same seal
     header, PAID pill, field order, and payment-mode badge colors), so the
     same transaction reads the same way everywhere it's shown: the
     dedicated Payments page, and a document/assistance request's own
     process modal. Extracted out of admin/payments.blade.php (its original
     home) so both places render one component instead of two copies that
     could drift apart.

     [recordExpr] is a raw Alpine/JS expression (not a Blade variable) that
     resolves, in the including page's own x-data scope, to a payment
     record shaped like one row of config/esperanza_payments.php: citizen,
     item, ref, amount, method ('GCash'|'PayMaya'|'Payment in Center'),
     date, receipt (filename or null). Renders nothing when that record or
     its receipt is absent, so an including page can drop this in
     unconditionally.
     ============================================================ --}}
@props(['recordExpr'])

<div
    x-data="{ showFullReceipt: false, get __receipt() { return {{ $recordExpr }}; } }"
    x-effect="__receipt?.ref; showFullReceipt = false"
    x-show="__receipt?.receipt"
    x-cloak
>

    {{-- Read-only receipt view for rows with a receipt already --}}
    <div class="rounded-xl border border-slate-200 p-3 mb-2 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="paperclip" class="w-4 h-4"></i></span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-700 truncate" x-text="__receipt?.receipt"></p>
                <p class="text-[11px] text-slate-400" x-text="'Paid via ' + (__receipt?.method || 'unspecified method')"></p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button
                type="button"
                @click="previewDoc = { name: 'Proof of Payment', file: __receipt?.receipt, status: __receipt?.status }; $nextTick(() => window.renderIcons?.())"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-colors"
                title="View uploaded proof of payment"
            ><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
            <button type="button" @click="showFullReceipt = !showFullReceipt; $nextTick(() => window.renderIcons?.())" class="text-xs font-medium text-brand-600 hover:underline" x-text="showFullReceipt ? 'Hide' : 'View'"></button>
        </div>
    </div>

    {{-- Official Payment Receipt --}}
    <div x-show="showFullReceipt" x-cloak x-transition class="mb-4">
        <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-white">
            <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 px-5 py-6 text-white text-center overflow-hidden">
                <div class="absolute -top-8 -right-8 w-28 h-28 rounded-full bg-white/5 pointer-events-none"></div>
                <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipality of Esperanza Seal" class="w-14 h-14 rounded-full ring-2 ring-white/30 object-cover mx-auto relative z-10">
                <p class="text-sm font-bold mt-2.5 relative z-10">Municipality of Esperanza, Masbate</p>
                <p class="text-[11px] font-bold text-gold-300 tracking-widest uppercase mt-0.5 relative z-10">Official Payment Receipt</p>
            </div>
            <div class="px-5 py-5">
                <div class="flex items-center justify-center py-2 rounded-full bg-emerald-50 border border-emerald-500/40 mb-4">
                    <span class="text-[15px] font-extrabold text-emerald-700 tracking-[0.2em]">PAID</span>
                </div>
                <dl class="space-y-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Resident Name</dt>
                        <dd class="text-xs font-bold text-navy-900 text-right" x-text="__receipt?.citizen"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Service</dt>
                        <dd class="text-xs font-bold text-navy-900 text-right" x-text="__receipt?.item"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Request Reference No.</dt>
                        <dd class="text-xs font-bold text-navy-900 text-right" x-text="__receipt?.ref"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Amount Paid</dt>
                        <dd class="text-base font-bold text-brand-700 text-right" x-text="__receipt?.amount"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Mode of Payment</dt>
                        <dd class="flex items-center justify-end gap-1.5 text-right">
                            {{-- Official GCash/PayMaya brand marks — same source
                                 assets as the Esperanza Mobile app's own receipt
                                 (assets/images/gcash_logo.svg, Maya_logo.svg.webp),
                                 explicitly size-capped so their own wide/tall
                                 source dimensions never drive this row's layout.
                                 Onsite keeps the plain storefront glyph — it's a
                                 municipal service, not a payment brand. --}}
                            <img
                                x-show="__receipt?.method === 'GCash'"
                                src="{{ asset('images/payment-methods/gcash-logo.svg') }}"
                                alt="GCash" class="h-[18px] w-5 object-contain shrink-0"
                            >
                            <img
                                x-show="__receipt?.method === 'PayMaya'"
                                src="{{ asset('images/payment-methods/maya-logo.webp') }}"
                                alt="Maya" class="h-3.5 w-[34px] object-contain shrink-0"
                            >
                            <span
                                x-show="!['GCash','PayMaya'].includes(__receipt?.method)"
                                class="w-4.5 h-4.5 rounded-full bg-brand-700 flex items-center justify-center shrink-0"
                            ><i data-lucide="store" class="w-2.5 h-2.5 text-white"></i></span>
                            <span class="text-xs font-bold text-navy-900" x-text="__receipt?.method === 'Payment in Center' ? 'Onsite — Municipal Office' : (__receipt?.method || '—')"></span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Date &amp; Time</dt>
                        <dd class="text-xs font-bold text-navy-900 text-right" x-text="__receipt?.date"></dd>
                    </div>
                    <div class="border-t border-dashed border-slate-200 my-1"></div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-xs text-slate-400">Transaction Reference No.</dt>
                        <dd class="text-xs font-bold text-navy-900 text-right font-mono" x-text="'OR-' + (__receipt?.ref || '').replace(/[^0-9]/g, '') + '-01'"></dd>
                    </div>
                </dl>
            </div>
        </div>
        <p class="text-center text-[10.5px] text-slate-400 mt-3 leading-relaxed">This is a frontend simulation. No real payment transaction was made.</p>
    </div>
</div>
