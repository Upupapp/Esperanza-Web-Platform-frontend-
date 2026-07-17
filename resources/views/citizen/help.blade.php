@php
    $tab = $tab ?? 'faqs';

    $faqs = [
        ['q' => 'How do I request a document online?', 'a' => 'Go to Dokyu in your sidebar, click "New Dokyu Request," choose the document type, fill in the purpose, and submit. You\'ll be notified once it\'s ready for release.'],
        ['q' => 'How long does it take to process a document request?', 'a' => 'Most documents like Barangay Clearance and Certificate of Residency are processed within 1-2 working days. Business Permits may take 3-5 working days.'],
        ['q' => 'What are the accepted payment methods for document fees?', 'a' => 'You may pay in cash at the Municipal Hall cashier, or online via GCash when your document is ready for release.'],
        ['q' => 'Who is qualified to apply for assistance programs?', 'a' => 'Residents of Esperanza with a verified profile may apply. Some programs, like Medical and Burial Assistance, require supporting documents such as hospital bills or a death certificate.'],
        ['q' => 'How do I update my resident profile information?', 'a' => 'Go to My Profile and click "Edit Profile." Changes to your official records may require verification by the MSWDO or your barangay office.'],
        ['q' => 'I forgot my password. How do I reset it?', 'a' => 'Click "Forgot password?" on the login page. This is a frontend preview, so password resets are simulated for demonstration purposes.'],
        ['q' => 'Can I track the status of my request in real time?', 'a' => 'Yes. Visit Dokyu or Tulong to see the current status of each request, from submission to release.'],
    ];

    $tutorials = [
        ['title' => 'How to Request a Barangay Clearance Online', 'duration' => '3:24', 'category' => 'Documents', 'icon' => 'file-text'],
        ['title' => 'Completing Your Resident Profile Verification', 'duration' => '4:10', 'category' => 'Account', 'icon' => 'user-round-check'],
        ['title' => 'Applying for Medical or Financial Assistance', 'duration' => '5:02', 'category' => 'Assistance', 'icon' => 'hand-heart'],
        ['title' => 'Tracking Your Document Request Status', 'duration' => '2:18', 'category' => 'Documents', 'icon' => 'search'],
        ['title' => 'Paying Document Fees via GCash', 'duration' => '3:41', 'category' => 'Payments', 'icon' => 'credit-card'],
        ['title' => 'Managing Notifications & Announcements', 'duration' => '2:47', 'category' => 'Account', 'icon' => 'bell'],
    ];

    $channels = [
        ['icon' => 'phone', 'label' => 'Call the ICT Office', 'labelFil' => 'Tawagan ang ICT Office', 'value' => '(056) 333-1099', 'color' => 'text-brand-600 bg-brand-50'],
        ['icon' => 'mail', 'label' => 'Email Support', 'labelFil' => 'Mag-email sa Suporta', 'value' => 'support@esperanza.gov.ph', 'color' => 'text-purple-600 bg-purple-50'],
        ['icon' => 'map-pin', 'label' => 'Visit Us', 'labelFil' => 'Bisitahin Kami', 'value' => 'Municipal Hall, Brgy. Poblacion, Esperanza', 'color' => 'text-emerald-600 bg-emerald-50'],
    ];

    $tickets = [
        ['ref' => 'TCK-2026-0071', 'subject' => 'Unable to upload valid ID during registration', 'status' => 'Processing', 'time' => 'Jul 5, 2026'],
        ['ref' => 'TCK-2026-0043', 'subject' => 'Wrong barangay listed on my profile', 'status' => 'Completed', 'time' => 'Jun 12, 2026'],
    ];
@endphp

<x-layouts.citizen title="Help & Support" subtitle="FAQs, tutorials, and support from the ICT Office." active="help">
    <div x-data="{ tab: '{{ $tab }}', submitting: false }" class="animate-fade-up">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
            <button @click="tab = 'faqs'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'faqs' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="circle-help" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Tanong" en="FAQs" /></button>
            <button @click="tab = 'tutorials'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'tutorials' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Gabay" en="Tutorials" /></button>
            <button @click="tab = 'support'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'support' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="life-buoy" class="w-3.5 h-3.5"></i><x-ui.t fil="Suporta" en="Support" /></button>
        </div>

        <!-- FAQs -->
        <div x-show="tab === 'faqs'" class="max-w-3xl">
            <div class="space-y-2.5" x-data="{ activeIndex: 0 }">
                @foreach($faqs as $i => $faq)
                    <x-ui.card padded="false" class="overflow-hidden">
                        <button @click="activeIndex = activeIndex === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                            <span class="text-sm font-medium text-slate-700">{{ $faq['q'] }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="activeIndex === {{ $i }} && 'rotate-180'"></i>
                        </button>
                        <div
                            x-show="activeIndex === {{ $i }}"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="px-5 pb-4 -mt-1"
                        >
                            <p class="text-sm text-slate-500 leading-relaxed">{{ $faq['a'] }}</p>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>

        <!-- Tutorials -->
        <div x-show="tab === 'tutorials'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($tutorials as $i => $tut)
                <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden">
                    <div class="relative h-32 bg-gradient-to-br from-navy-900 to-brand-800 flex items-center justify-center">
                        <div class="w-11 h-11 rounded-full bg-white/15 backdrop-blur flex items-center justify-center group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                            <i data-lucide="play" class="w-5 h-5 text-white ml-0.5"></i>
                        </div>
                        <span class="absolute bottom-2.5 right-2.5 text-[11px] font-medium text-white bg-navy-950/70 px-2 py-0.5 rounded-md">{{ $tut['duration'] }}</span>
                    </div>
                    <div class="p-4">
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full mb-2">
                            <i data-lucide="{{ $tut['icon'] }}" class="w-3 h-3"></i>{{ $tut['category'] }}
                        </span>
                        <p class="text-sm font-medium text-slate-700 leading-snug">{{ $tut['title'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Support -->
        <div x-show="tab === 'support'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-5">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4"><x-ui.t fil="Magsumite ng Support Ticket" en="Submit a Support Ticket" /></h3>
                    <form
                        class="space-y-4"
                        @submit.prevent="submitting = true; setTimeout(() => { submitting = false; $dispatch('toast', { message: $store.lang.current === 'en' ? 'Support ticket submitted — our team will respond within 24 hours.' : 'Naisumite ang support ticket — sasagutin ng aming team sa loob ng 24 oras.', variant: 'success' }); $event.target.reset(); }, 800)"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kategorya" en="Category" /></label>
                                <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                    <option>Account & Login</option>
                                    <option>Dokyu</option>
                                    <option>Tulong</option>
                                    <option>Technical Issue</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <x-ui.input name="subject" label="Subject" placeholder="Brief summary of your concern" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Mensahe" en="Message" /></label>
                            <textarea rows="4" required placeholder="Describe your issue in detail" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
                        </div>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="btn-shine inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-brand-500 to-brand-600 text-white px-5 py-2.5 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-200 active:scale-[0.98] disabled:opacity-70"
                        >
                            <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="$store.lang.current === 'en' ? (submitting ? 'Submitting…' : 'Submit Ticket') : (submitting ? 'Sinusumite…' : 'Isumite ang Ticket')"></span>
                        </button>
                    </form>
                </x-ui.card>

                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Support Ticket" en="My Support Tickets" /></h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($tickets as $t)
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate">{{ $t['subject'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ $t['ref'] }} · {{ $t['time'] }}</p>
                                </div>
                                <x-ui.badge :status="$t['status']" class="shrink-0" />
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-3.5">
                @foreach($channels as $ch)
                    <x-ui.card class="flex items-center gap-3.5">
                        <span class="w-11 h-11 rounded-xl {{ $ch['color'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $ch['icon'] }}" class="w-5 h-5"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-400"><x-ui.t :fil="$ch['labelFil']" :en="$ch['label']" /></p>
                            <p class="text-sm font-medium text-navy-900 truncate">{{ $ch['value'] }}</p>
                        </div>
                    </x-ui.card>
                @endforeach

                <x-ui.card class="bg-slate-50/60">
                    <p class="text-xs text-slate-500 flex items-center gap-1.5 mb-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i><x-ui.t fil="Oras ng Opisina" en="Office Hours" /></p>
                    <p class="text-sm text-slate-700 font-medium"><x-ui.t fil="Lunes – Biyernes, 8:00 AM – 5:00 PM" en="Monday – Friday, 8:00 AM – 5:00 PM" /></p>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.citizen>
