{{-- Today's date in the browser: the server's date is the day the static site was built. --}}
<div class="absolute left-[14px] top-[12px] w-[78px] h-[84px] rounded-[14px] frost-1">
    <div class="absolute left-0 top-0 w-[78px] h-[24px] rounded-[14px_14px_0_0] frost-2"></div>
    <div class="absolute left-[16px] -top-[6px] w-[9px] h-[18px] rounded-[5px] bg-white/80"></div>
    <div class="absolute left-[53px] -top-[6px] w-[9px] h-[18px] rounded-[5px] bg-white/80"></div>
    <p class="absolute inset-x-0 top-[30px] text-center text-[34px] font-extrabold tracking-[-.03em] leading-none text-white/90" x-text="new Date().toLocaleDateString('en-US', { day: 'numeric', timeZone: 'Asia/Manila' })"></p>
    <p class="absolute inset-x-0 top-[66px] text-center text-[9px] font-bold tracking-[.12em] text-white/80" x-text="new Date().toLocaleDateString('en-US', { month: 'short', timeZone: 'Asia/Manila' }).toUpperCase()"></p>
</div>
