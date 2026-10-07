@props(['padded' => false])

{{-- Clay design (STYLE_GUIDE §3.3/3.7): the table sits in a glass container. --}}
<div {{ $attributes->merge(['class' => 'surface-glass rounded-[26px] overflow-hidden']) }}>
    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full text-sm {{ $padded ? 'p-5' : '' }}">
            {{ $slot }}
        </table>
    </div>
</div>
