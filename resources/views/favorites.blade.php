@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('favorites', null))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('favorites', null))

@section('content')
@php
    $seoH1 = \App\Models\SeoTemplate::renderH1('favorites', null);
    $seoH1Description = \App\Models\SeoTemplate::renderH1Description('favorites', null);
@endphp
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → {{ $seoH1 ?: 'Понравившееся' }}</nav>
    <h1>{{ $seoH1 ?: 'Понравившееся' }}</h1>
    @if($seoH1Description)
        <p class="author-tagline favorites-h1-desc">{{ $seoH1Description }}</p>
    @endif
    @if($poems->isEmpty())
        <p class="favorites-empty">Пока здесь пусто. Нажимайте «Нравится» на странице стиха — они появятся здесь.</p>
    @else
        <div class="favorites-by-authors">
            @foreach($poemsByAuthor as $group)
                <section class="favorites-author-block" data-author-id="{{ $group['author']->id }}">
                    <h2 class="favorites-author-name"><a href="/{{ $group['author']->slug }}/" class="favorites-author-link">{{ e_decode($group['author']->name) }}</a></h2>
                    @foreach($group['poems'] as $poem)
                        <p class="favorites-poem-row" data-poem-id="{{ $poem->id }}">
                            <a href="/{{ $poem->slug }}/" class="favorites-poem-link">{{ e_decode($poem->title) }}</a>
                            <button type="button" class="favorites-remove-btn" data-poem-id="{{ $poem->id }}" aria-label="Убрать из понравившегося" title="Убрать из понравившегося">×</button>
                        </p>
                    @endforeach
                </section>
            @endforeach
        </div>
    @endif
</div>
@if(!$poems->isEmpty())
@push('scripts')
<script>
(function() {
  var removeBtns = document.querySelectorAll('.favorites-remove-btn');
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var token = csrf ? csrf.getAttribute('content') : '';
  removeBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Убрать стих из понравившегося?')) return;
      var id = btn.getAttribute('data-poem-id');
      var row = btn.closest('.favorites-poem-row');
      var body = new FormData();
      body.append('_token', token);
      fetch('{{ url("/poem") }}/' + id + '/unlike', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function(r) { return r.json(); }).then(function() {
        if (row) {
          var block = row.closest('.favorites-author-block');
          row.remove();
          if (block && block.querySelectorAll('.favorites-poem-row').length === 0) {
            block.remove();
          }
        }
        var container = document.querySelector('.favorites-by-authors');
        if (container && container.querySelectorAll('.favorites-poem-row').length === 0) {
          var h1Text = document.querySelector('.container h1') ? document.querySelector('.container h1').textContent : 'Понравившееся';
          var desc = document.querySelector('.favorites-h1-desc');
          container.closest('.container').innerHTML = '<nav class="breadcrumb"><a href="/">Главная</a> → ' + h1Text + '</nav><h1>' + h1Text + '</h1>' + (desc ? '<p class="author-tagline favorites-h1-desc">' + desc.textContent + '</p>' : '') + '<p class="favorites-empty">Пока здесь пусто. Нажимайте «Нравится» на странице стиха — они появятся здесь.</p>';
        }
      });
    });
  });
})();
</script>
@endpush
@endif
@endsection
