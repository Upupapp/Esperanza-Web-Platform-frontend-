@props(['name', 'when' => 'false'])
{{--
    A window kept out of the page's own HTML (page budget, 2026-11-25).

    resources/views/lazy/<name>.blade.php holds the markup; routes/web.php
    serves it as /partials/<name>.html and the export writes it to
    dist/partials/<name>.html. This placeholder stands where the markup was:
    resources/js/lazy-partial.js puts the markup back in it once the page is
    idle, or at once when `when` (an Alpine expression in this spot's scope,
    usually the window's open flag) turns true. The markup then sits in the
    same place, under the same Alpine scope, as it did inline.

    The partial must not need the page's PHP variables: it is rendered on its
    own. ?v= is its content's hash, so a page never takes another build's copy.
--}}
@php
    $lazyPartialHtml = view('lazy-frame', ['lazyPartialName' => $name])->render();
    $lazyPartialSrc = '/partials/'.$name.'.html?v='.substr(md5($lazyPartialHtml), 0, 10);
@endphp
<div class="contents" data-lazy-partial="{{ $lazyPartialSrc }}" x-lazy-partial="{{ $when }}"></div>
