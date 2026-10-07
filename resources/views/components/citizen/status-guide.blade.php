@props(['type' => 'dokyu'])

@php
    // The status NAME is the canonical vocabulary (CLAUDE.md: reuse exactly
    // these names, never invent new ones), so the badge stays as-is in both
    // languages -- it is a token, not prose. Only the explanation is bilingual.
    //
    // type="tulong" explains Tulong's own case vocabulary instead
    // (config/esperanza_tulong_lifecycle.php), which is not the fifteen: a
    // resident tracking an assistance case never sees Under Verification or
    // Mark to Release. Its colours are the Tulong pages' own statusStyles,
    // since x-ui.badge styles only the fifteen.
    $tulong = [
        'Submitted' => ['We received your request. It is now in the queue.', 'Natanggap namin ang iyong kahilingan. Nasa pila na ito.', 'bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
        'Initial Screening' => ["We're checking your application and requirements.", 'Sinusuri namin ang iyong aplikasyon at mga requirement.', 'bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
        'Needs Requirements' => ['A requirement is missing or needs replacing — check what to send, then resubmit.', 'May kulang o kailangang palitang requirement — tingnan kung ano ang ipapadala, pagkatapos ay isumite muli.', 'bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'Assessment' => ['A social worker is assessing your eligibility.', 'Sinusuri ng social worker kung kwalipikado ka.', 'bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
        'For Approval' => ['The assessment is done and awaits an approval decision.', 'Tapos na ang pagtatasa at hinihintay na ang desisyon sa pag-apruba.', 'bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'Returned for Reassessment' => ['The office is re-checking the assessment. Nothing is needed from you.', 'Muling sinusuri ng opisina ang pagtatasa. Wala kang kailangang gawin.', 'bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
        'Assistance Processing' => ['Approved — your assistance is being prepared.', 'Naaprubahan — inihahanda na ang iyong tulong.', 'bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
        'Ready for Release' => ['Your assistance is ready to claim at the office.', 'Handa nang kunin ang iyong tulong sa opisina.', 'bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
        'Completed' => ['You have received the assistance.', 'Natanggap mo na ang tulong.', 'bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
        'Rejected' => ['This request was not approved. Check the reason for details.', 'Hindi naaprubahan ang kahilingang ito. Tingnan ang dahilan para sa detalye.', 'bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
        'Cancelled' => ['This request was withdrawn before a decision. Check the reason for details.', 'Binawi ang kahilingang ito bago magkaroon ng desisyon. Tingnan ang dahilan para sa detalye.', 'bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
    ];

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
        class="inline-flex items-center gap-1 text-xs font-medium text-ink-muted hover:text-brand-600 transition-colors"
    ><i data-lucide="circle-question-mark" class="w-3.5 h-3.5"></i><x-ui.t fil="Ano ang ibig sabihin ng mga status na ito?" en="What do these statuses mean?" /></button>

    <x-ui.modal title="Gabay sa Status" titleEn="Status Guide" maxWidth="sm">
        <p class="text-sm text-ink-muted mb-4">
            <x-ui.t
                fil="Narito ang ibig sabihin ng bawat status para sa iyong kahilingan."
                en="Here's what each status means for your request." />
        </p>
        <div class="space-y-3">
            @if($type === 'tulong')
                @foreach($tulong as $name => [$en, $fil, $classes, $dot])
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap shrink-0 mt-0.5 {{ $classes }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ $name }}
                        </span>
                        <p class="text-xs text-ink-muted leading-snug pt-1">
                            <x-ui.t :fil="$fil" :en="$en" />
                        </p>
                    </div>
                @endforeach
            @else
                @foreach($statuses as $s)
                    <div class="flex items-start gap-3">
                        <x-ui.badge :status="$s['name']" class="shrink-0 mt-0.5" />
                        <p class="text-xs text-ink-muted leading-snug pt-1">
                            <x-ui.t :fil="$s['fil']" :en="$s['en']" />
                        </p>
                    </div>
                @endforeach
            @endif
        </div>
        <x-slot:footer>
            <x-ui.button size="sm" @click="open = false">
                <x-ui.t fil="Naiintindihan ko" en="Got it" />
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
