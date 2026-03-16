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
        @php
            $h1Desc = \App\Models\SeoTemplate::renderH1Description('poem', $poem);
            $h1Desc = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $h1Desc);
        @endphp
        <p class="author-name">{!! $h1Desc !!}@if($poem->relationLoaded('analysis') && $poem->analysis) — <a href="/{{ $poem->slug }}/analiz/" class="poem-analysis-link">Анализ стихотворения «{{ e_decode($poem->title) }}»</a>@endif</p>
    @else
        <p class="author-name">{{ e_decode($poem->author->name) }}@if($poem->relationLoaded('analysis') && $poem->analysis) — <a href="/{{ $poem->slug }}/analiz/" class="poem-analysis-link">Анализ стихотворения «{{ e_decode($poem->title) }}»</a>@endif</p>
    @endif
    <div class="poem-body">
        {!! \Illuminate\Support\Str::replace(['https://stihotvorenie.su', 'http://stihotvorenie.su'], '', $poem->body ?? '') !!}
    </div>
    @if($poem->relationLoaded('tags') && $poem->tags->isNotEmpty())
    <div class="poem-tags">
        <span class="poem-tags-label">Теги:</span>
        @foreach($poem->tags as $tag)
            <a href="{{ url('/tegi/' . $tag->slug . '/') }}" class="poem-tag-link">{{ e_decode($tag->name) }}</a>
        @endforeach
    </div>
    @endif
    <div class="poem-actions">
        <button type="button" class="poem-btn poem-btn-back" id="poem-btn-back" aria-label="Назад">Назад</button>
        <button type="button" class="poem-btn poem-btn-like {{ ($liked ?? false) ? 'is-liked' : '' }}" id="poem-btn-like" data-poem-id="{{ $poem->id }}" aria-label="Нравится">
            <span class="poem-btn-like-text">Нравится</span>
            <span class="poem-btn-like-count">{{ (int) ($poem->likes ?? 0) }}</span>
        </button>
        <button type="button" class="poem-btn poem-btn-read {{ ($is_read ?? false) ? 'is-read' : '' }}" id="poem-btn-read" data-poem-id="{{ $poem->id }}" aria-label="{{ ($is_read ?? false) ? 'Прочитано' : 'Отметить прочитанным' }}" {{ ($is_read ?? false) ? 'disabled' : '' }}>{{ ($is_read ?? false) ? 'Прочитано ✓' : 'Прочитано' }}</button>
        <a href="#top" class="poem-btn back-to-top back-to-top-inline" aria-label="Наверх">Наверх</a>
    </div>

    @if(($related_by_author ?? collect())->isNotEmpty() || ($related_by_likes ?? collect())->isNotEmpty())
    <section class="poem-related" aria-label="Перелинковка">
        @if(($related_by_author ?? collect())->isNotEmpty())
        <h2 class="poem-related-title">Другие стихи {{ e_decode($poem->author->name) }}</h2>
        <ul class="poems-list poem-related-list">
            @foreach($related_by_author as $p)
            <li><a href="/{{ $p->slug }}/" class="list-row-link">{{ e_decode($p->title) }}@if($p->author)<span class="list-row-meta"> — {{ e_decode($p->author->name) }}</span>@endif</a></li>
            @endforeach
        </ul>
        @endif
        @if(($related_by_likes ?? collect())->isNotEmpty())
        <h2 class="poem-related-title">Понравилось читателям</h2>
        <ul class="poems-list poem-related-list">
            @foreach($related_by_likes as $p)
            <li><a href="/{{ $p->slug }}/" class="list-row-link">{{ e_decode($p->title) }}@if($p->author)<span class="list-row-meta"> — {{ e_decode($p->author->name) }}</span>@endif</a></li>
            @endforeach
        </ul>
        @endif
    </section>
    @endif

    @if(!empty($read_debug))
    <div class="read-debug" style="margin-top:1.5rem;padding:1rem;background:#f0f0f0;border:1px solid #ccc;font-family:monospace;font-size:12px;word-break:break-all;">
        <strong>Отладка «Прочитано» (добавь ?debug в URL)</strong><br>
        raw_cookie: {{ $read_debug['raw_cookie'] === null ? 'null' : (strlen($read_debug['raw_cookie'] ?? '') > 200 ? substr($read_debug['raw_cookie'], 0, 200) . '…' : ($read_debug['raw_cookie'] ?? '')) }}<br>
        count: {{ $read_debug['count'] ?? 0 }}<br>
        current_id: {{ $read_debug['current_id'] ?? '' }}<br>
        is_read: {{ ($read_debug['is_read'] ?? false) ? 'да' : 'нет' }}<br>
        read_ids: {{ json_encode($read_debug['read_ids'] ?? []) }}
    </div>
    @endif
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
      var origin = window.location.origin;
      var hasSameOriginReferrer = document.referrer && document.referrer.indexOf(origin) === 0;
      var hasHistory = window.history.length > 1;
      if (hasSameOriginReferrer || hasHistory) {
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
  var readBtn = document.getElementById('poem-btn-read');
  if (readBtn) {
    var poemId = readBtn.getAttribute('data-poem-id');
    var readUrl = '{{ url("/poem/read") }}/' + poemId;
    readBtn.addEventListener('click', function() {
      if (readBtn.classList.contains('is-read')) return;
      var csrf = document.querySelector('meta[name="csrf-token"]');
      var body = new FormData();
      body.append('_token', csrf ? csrf.getAttribute('content') : '');
      fetch(readUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function(r) { return r.json(); }).then(function() {
        readBtn.classList.add('is-read');
        readBtn.setAttribute('aria-label', 'Прочитано');
        readBtn.textContent = 'Прочитано ✓';
        readBtn.disabled = true;
      });
    });
  }
})();
</script>
@endpush
@endsection
