@props(['banners' => []])

<div
    x-data="{
        banners: @js($banners),
        active: 0,
        timer: null,
        start() {
            if (this.banners.length <= 1) return;
            this.timer = setInterval(() => this.next(), 6000);
        },
        next() { this.active = (this.active + 1) % this.banners.length; },
        prev() { this.active = (this.active - 1 + this.banners.length) % this.banners.length; },
        goTo(i) { this.active = i; this.restart(); },
        restart() { clearInterval(this.timer); this.start(); },
    }"
    x-init="start()"
    x-show="banners.length > 0"
    class="relative rounded-2xl overflow-hidden mb-4 shadow-card group"
    style="aspect-ratio: 21 / 7;"
>
    <template x-for="(b, i) in banners" :key="b.id">
        <a
            :href="b.cta_url || '#'"
            x-show="active === i"
            x-transition:enter="transition duration-[420ms] ease-standard"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="absolute inset-0 block"
        >
            <img :src="b.image_url" :alt="b.title" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
            <div class="absolute inset-0 bg-gradient-to-r" :class="b.theme + ' opacity-85'"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-navy-950/70 via-transparent to-transparent"></div>

            <div class="relative h-full flex flex-col justify-end p-4 sm:p-6">
                <h3 class="text-white font-bold text-base sm:text-xl leading-tight max-w-lg drop-shadow-sm" x-text="b.title"></h3>
                <p class="text-white/85 text-xs sm:text-sm mt-1 max-w-md leading-snug hidden sm:block" x-text="b.subtitle"></p>
                <span
                    x-show="b.cta_label"
                    class="inline-flex items-center gap-1.5 mt-3 w-fit bg-white/95 text-navy-900 text-xs font-semibold px-3.5 py-2 rounded-lg shadow-sm group-hover:bg-white transition-colors"
                >
                    <span x-text="b.cta_label"></span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </span>
            </div>
        </a>
    </template>

    <!-- Arrows (visible on hover, desktop) -->
    <button
        type="button" @click.prevent="prev(); restart()"
        x-show="banners.length > 1"
        class="hidden sm:flex absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-navy-950/40 hover:bg-navy-950/60 backdrop-blur text-white items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-[160ms]"
    ><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
    <button
        type="button" @click.prevent="next(); restart()"
        x-show="banners.length > 1"
        class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-navy-950/40 hover:bg-navy-950/60 backdrop-blur text-white items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-[160ms]"
    ><i data-lucide="chevron-right" class="w-4 h-4"></i></button>

    <!-- Dots -->
    <div class="absolute bottom-3 right-4 flex items-center gap-1.5" x-show="banners.length > 1">
        <template x-for="(b, i) in banners" :key="'dot-' + b.id">
            <button
                type="button" @click.prevent="goTo(i)"
                class="h-1.5 rounded-full transition-all duration-[220ms] ease-standard"
                :class="active === i ? 'w-5 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/60'"
            ></button>
        </template>
    </div>
</div>
