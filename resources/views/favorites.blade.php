@extends('layouts.site')

@section('title', 'Понравившиеся')
@section('meta_description', 'Стихи, которые вам понравились')

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → Понравившиеся</nav>
    <h1>Понравившиеся</h1>
    @if($poems->isEmpty())
        <p class="favorites-empty">Пока здесь пусто. Нажимайте «Нравится» на странице стиха — они появятся здесь.</p>
    @else
        <ul class="poems-list favorites-list">
            @foreach($poems as $poem)
                <li class="favorites-row" data-poem-id="{{ $poem->id }}">
                    <a href="/{{ $poem->slug }}/" class="list-row-link">{{ e_decode($poem->title) }}</a>
                    <span class="favorites-author">{{ e_decode($poem->author->name ?? '') }}</span>
                    <button type="button" class="favorites-remove poem-btn" data-poem-id="{{ $poem->id }}" aria-label="Убрать из понравившегося">Убрать</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@if(!$poems->isEmpty())
@push('scripts')
<script>
(function() {
  var removeBtns = document.querySelectorAll('.favorites-remove');
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var token = csrf ? csrf.getAttribute('content') : '';
  removeBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.getAttribute('data-poem-id');
      var row = btn.closest('.favorites-row');
      var body = new FormData();
      body.append('_token', token);
      fetch('/poem/' + id + '/unlike', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function(r) { return r.json(); }).then(function() {
        if (row) row.remove();
        var list = document.querySelector('.favorites-list');
        if (list && list.querySelectorAll('.favorites-row').length === 0) {
          list.closest('.container').innerHTML = '<nav class="breadcrumb"><a href="/">Главная</a> → Понравившиеся</nav><h1>Понравившиеся</h1><p class="favorites-empty">Пока здесь пусто. Нажимайте «Нравится» на странице стиха — они появятся здесь.</p>';
        }
      });
    });
  });
})();
</script>
@endpush
@endif
@endsection
