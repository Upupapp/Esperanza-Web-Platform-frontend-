{{-- "Upload file" on a Dokyu or Tulong requirement with no file (round 2,
     2026-10-30): the photo or scan of the paper the person brought, uploaded
     by the officer (POST .../requirements/{key}/attach); the request's detail
     comes back and replaces the one on screen. Inside the requirement row's
     x-for (req); logic: requirementUpload() in
     resources/js/scope-admin-requests.js. --}}
<label x-data="requirementUpload()" x-show="upShown(req)" x-cloak
       class="inline-flex items-center gap-1 h-7 px-2 rounded-lg bg-white border border-slate-200 text-[11px] font-medium text-brand-700 hover:border-brand-300 cursor-pointer transition-colors focus-within:ring-4 focus-within:ring-brand-100"
       :class="req._upBusy && 'pointer-events-none opacity-70'" title="Upload a photo or scan of this document" data-testid="requirement-upload">
    <input type="file" accept="image/*,application/pdf" class="sr-only" :disabled="req._upBusy" @change="upAttach(req, $event)">
    <i data-lucide="upload" class="w-3.5 h-3.5"></i><span x-text="req._upBusy ? 'Uploading… ' + (req._upProgress || 0) + '%' : 'Upload file'"></span>
</label>
