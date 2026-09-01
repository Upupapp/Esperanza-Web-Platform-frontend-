@php
    // The status NAME is the canonical vocabulary (CLAUDE.md: reuse exactly
    // these names, never invent new ones), so the badge stays as-is in both
    // languages -- it is a token, not prose. Only the explanation is bilingual.
    $statuses = [
        [
            'name' => 'Submitted',
            'en' => 'We received your request. It is now in the queue.',
            'fil' => 'Natanggap namin ang iyong kahilingan. Nasa pila na ito.',
        ],
        [
            'name' => 'Under Verification',
            'en' => "We're verifying your documents or eligibility.",
            'fil' => 'Sinusuri namin ang iyong mga dokumento o pagiging kwalipikado.',
        ],
        [
            'name' => 'Under Review',
            'en' => 'One or more documents need correction — check what to fix and resubmit.',
            'fil' => 'May isa o higit pang dokumentong kailangang itama — tingnan kung ano ang aayusin at muling isumite.',
        ],
        [
            'name' => 'Resubmitted',
            'en' => 'We received your corrected documents and are reviewing them again.',
            'fil' => 'Natanggap namin ang iyong mga naitamang dokumento at muli naming sinusuri ang mga ito.',
        ],
        [
            'name' => 'Approved',
            'en' => 'Your request has been approved and is being prepared.',
            'fil' => 'Naaprubahan na ang iyong kahilingan at inihahanda na ito.',
        ],
        [
            'name' => 'Mark to Release',
            'en' => 'It is ready — you may claim it at the office.',
            'fil' => 'Handa na ito — maaari mo na itong kunin sa opisina.',
        ],
        [
            'name' => 'Released',
            'en' => 'It has been handed over to you.',
            'fil' => 'Naibigay na ito sa iyo.',
        ],
        [
            'name' => 'Rejected',
            'en' => 'This request was not approved. Check the reason for details.',
            'fil' => 'Hindi naaprubahan ang kahilingang ito. Tingnan ang dahilan para sa detalye.',
        ],
    ];
@endphp

<div x-data="{ open: false }" class="contents">
    <button
        type="button"
        @click="open = true"
        class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-brand-600 transition-colors"
    ><i data-lucide="circle-question-mark" class="w-3.5 h-3.5"></i><x-ui.t fil="Ano ang ibig sabihin ng mga status na ito?" en="What do these statuses mean?" /></button>

    <x-ui.modal title="Gabay sa Status" titleEn="Status Guide" maxWidth="sm">
        <p class="text-sm text-slate-500 mb-4">
            <x-ui.t
                fil="Narito ang ibig sabihin ng bawat status para sa iyong kahilingan."
                en="Here's what each status means for your request." />
        </p>
        <div class="space-y-3">
            @foreach($statuses as $s)
                <div class="flex items-start gap-3">
                    <x-ui.badge :status="$s['name']" class="shrink-0 mt-0.5" />
                    <p class="text-xs text-slate-500 leading-snug pt-1">
                        <x-ui.t :fil="$s['fil']" :en="$s['en']" />
                    </p>
                </div>
            @endforeach
        </div>
        <x-slot:footer>
            <x-ui.button size="sm" @click="open = false">
                <x-ui.t fil="Naiintindihan ko" en="Got it" />
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
