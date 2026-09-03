@php
    $content = config('esperanza_site_content');
    $privacy = $content['privacy_policy'];
    $terms = $content['terms_conditions'];
    $tab = $tab ?? 'privacy';
@endphp

<x-layouts.citizen title="Legal" subtitle="Privacy Policy and Terms &amp; Conditions." active="settings">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up max-w-3xl" x-init="$nextTick(() => window.renderIcons?.())">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-5">
            <button @click="tab = 'privacy'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'privacy' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i><x-ui.t fil="Patakaran sa Privacy" en="Privacy Policy" />
            </button>
            <button @click="tab = 'terms'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'terms' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Tuntunin" en="Terms &amp; Conditions" />
            </button>
        </div>

        <!-- Privacy Policy -->
        <div x-show="tab === 'privacy'">
            <x-ui.card>
                <div class="flex items-start gap-3 mb-1">
                    <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="shield-check" class="w-5 h-5"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-navy-900">{{ $privacy['title'] }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <x-ui.t fil="Ipinatutupad simula" en="Effective" /> {{ \Carbon\Carbon::parse($privacy['effective_date'])->format('F j, Y') }}
                            &middot; <x-ui.t fil="Huling na-update noong" en="Last updated" /> {{ \Carbon\Carbon::parse($privacy['last_updated'])->format('F j, Y') }}
                        </p>
                    </div>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mt-4 pb-5 border-b border-slate-100">{{ $privacy['intro'] }}</p>

                <div class="space-y-5 mt-5">
                    @foreach($privacy['sections'] as $section)
                        <div>
                            <h3 class="text-sm font-semibold text-navy-900 mb-1.5">{{ $section['heading'] }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $section['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <!-- Terms & Conditions -->
        <div x-show="tab === 'terms'" x-cloak>
            <x-ui.card>
                <div class="flex items-start gap-3 mb-1">
                    <span class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-5 h-5"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-navy-900">{{ $terms['title'] }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <x-ui.t fil="Ipinatutupad simula" en="Effective" /> {{ \Carbon\Carbon::parse($terms['effective_date'])->format('F j, Y') }}
                            &middot; <x-ui.t fil="Huling na-update noong" en="Last updated" /> {{ \Carbon\Carbon::parse($terms['last_updated'])->format('F j, Y') }}
                        </p>
                    </div>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mt-4 pb-5 border-b border-slate-100">{{ $terms['intro'] }}</p>

                <div class="space-y-5 mt-5">
                    @foreach($terms['sections'] as $section)
                        <div>
                            <h3 class="text-sm font-semibold text-navy-900 mb-1.5">{{ $section['heading'] }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $section['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

    </div>
</x-layouts.citizen>
