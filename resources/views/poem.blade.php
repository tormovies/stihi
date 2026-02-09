@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('poem', $poem))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('poem', $poem))

@push('json_ld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CreativeWork',
    'name' => e_decode($poem->title),
    'url' => url()->current(),
    'inLanguage' => 'ru',
    'author' => [
        '@type' => 'Person',
        'name' => e_decode($poem->author->name),
        'url' => url('/' . $poem->author->slug . '/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container poem">
    <nav class="breadcrumb">
        <a href="/">Главная</a> →
        <a href="/{{ $poem->author->slug }}/">{{ e_decode($poem->author->name) }}</a> →
        {{ e_decode($poem->title) }}
    </nav>
    <h1>{{ \App\Models\SeoTemplate::renderH1('poem', $poem) ?: e_decode($poem->title) }}</h1>
    @if(\App\Models\SeoTemplate::renderH1Description('poem', $poem))
        <p class="author-name">{{ \App\Models\SeoTemplate::renderH1Description('poem', $poem) }}</p>
    @else
        <p class="author-name">{{ e_decode($poem->author->name) }}</p>
    @endif
    <div class="poem-body">
        {!! \Illuminate\Support\Str::replace(['https://stihotvorenie.su', 'http://stihotvorenie.su'], '', $poem->body ?? '') !!}
    </div>
    <div class="poem-actions">
        <button type="button" class="poem-btn poem-btn-back" id="poem-btn-back" aria-label="Назад">Назад</button>
        <button type="button" class="poem-btn poem-btn-like {{ ($liked ?? false) ? 'is-liked' : '' }}" id="poem-btn-like" data-poem-id="{{ $poem->id }}" aria-label="Нравится">
            <span class="poem-btn-like-text">Нравится</span>
            <span class="poem-btn-like-count">{{ (int) ($poem->likes ?? 0) }}</span>
        </button>
        <a href="#top" class="poem-btn back-to-top back-to-top-inline" aria-label="Наверх">Наверх</a>
    </div>
</div>
@push('back-to-top')
<a href="#top" class="poem-btn back-to-top back-to-top-float" aria-label="Наверх">Наверх</a>
@endpush
@push('scripts')
<script>
(function() {
  var backBtn = document.getElementById('poem-btn-back');
  if (backBtn) {
    backBtn.addEventListener('click', function() {
      if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) {
        window.history.back();
      } else {
        window.location.href = '{{ url('/' . $poem->author->slug . '/') }}';
      }
    });
  }
  var likeBtn = document.getElementById('poem-btn-like');
  if (likeBtn && !likeBtn.classList.contains('is-liked')) {
    likeBtn.addEventListener('click', function() {
      var id = likeBtn.getAttribute('data-poem-id');
      var countEl = likeBtn.querySelector('.poem-btn-like-count');
      var csrf = document.querySelector('meta[name="csrf-token"]');
      var body = new FormData();
      body.append('_token', csrf ? csrf.getAttribute('content') : '');
      fetch('{{ url('/poem') }}/' + id + '/like', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.likes !== undefined) {
          if (countEl) countEl.textContent = d.likes;
          likeBtn.classList.add('is-liked');
          likeBtn.disabled = true;
        }
      });
    });
  }
})();
</script>
@endpush
@endsection
