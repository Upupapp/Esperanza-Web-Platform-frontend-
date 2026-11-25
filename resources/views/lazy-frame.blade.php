{{--
    One lazy partial, rendered the same way for its own address
    (/partials/<name>.html) and for the hash its page's placeholder carries
    (x-lazy-partial). Its windows number their headings on their own
    (lp-<name>-title-N): a partial is its own request, so the page's
    modal-title-N count would hand it ids the page already uses.
--}}
@php
    $lazyFrameAttrs = request()->attributes;
    $lazyFrameSaved = [$lazyFrameAttrs->get('esperanza_modal_seq'), $lazyFrameAttrs->get('esperanza_modal_id_prefix')];
    $lazyFrameAttrs->set('esperanza_modal_seq', 0);
    $lazyFrameAttrs->set('esperanza_modal_id_prefix', 'lp-'.$lazyPartialName.'-title-');
@endphp
@include('lazy.'.$lazyPartialName)
@php
    $lazyFrameAttrs->set('esperanza_modal_seq', $lazyFrameSaved[0]);
    $lazyFrameAttrs->set('esperanza_modal_id_prefix', $lazyFrameSaved[1]);
@endphp
