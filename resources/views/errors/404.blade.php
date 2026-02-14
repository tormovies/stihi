@extends('layouts.site')

@section('title', 'Страница не найдена (404) | Стихотворения поэтов классиков')
@section('meta_description', 'Запрашиваемая страница не найдена. Воспользуйтесь поиском или перейдите на главную.')

@section('content')
<div class="container error-page">
    <h1 class="error-page-code">404</h1>
    <p class="error-page-title">Страница не найдена</p>
    <p class="error-page-text">Страница удалена или перенесена. Попробуйте воспользоваться поиском или перейдите на главную.</p>
    <div class="error-page-search">
        <label for="error-page-search-input" class="visually-hidden">Поиск по стихам и авторам</label>
        <input type="search" class="site-search-input error-page-search-input" id="error-page-search-input" placeholder="Поиск по стихам и авторам…" autocomplete="off" aria-label="Поиск по стихам и авторам" minlength="3">
        <div class="site-search-dropdown error-page-search-dropdown" id="error-page-search-dropdown" role="listbox" aria-hidden="true"></div>
    </div>
    <p class="error-page-actions">
        <a href="{{ url('/') }}" class="poem-btn">Перейти на главную</a>
    </p>
</div>
@push('scripts')
<script>
(function() {
  var input = document.getElementById('error-page-search-input');
  var dropdown = document.getElementById('error-page-search-dropdown');
  if (!input || !dropdown) return;
  var timer = null;
  var hideTimeout = null;
  var baseUrl = '{{ url("/") }}';
  function hide() {
    clearTimeout(hideTimeout);
    hideTimeout = null;
    dropdown.classList.remove('is-open');
    dropdown.innerHTML = '';
    dropdown.setAttribute('aria-hidden', 'true');
  }
  function show(items) {
    dropdown.innerHTML = items;
    dropdown.classList.add('is-open');
    dropdown.setAttribute('aria-hidden', 'false');
  }
  function decodeHtmlEntities(str) {
    if (str == null || typeof str !== 'string') return str;
    var div = document.createElement('div');
    div.innerHTML = str;
    return div.textContent || div.innerText || str;
  }
  function escapeHtml(s) {
    if (s == null) return '';
    s = decodeHtmlEntities(String(s));
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }
  function render(data) {
    var html = [];
    if (data.authors && data.authors.length) {
      html.push('<div class="site-search-group"><span class="site-search-group-title">Авторы</span><ul class="site-search-list">');
      data.authors.forEach(function(a) {
        html.push('<li><a href="' + baseUrl + '/' + a.slug + '/" class="site-search-item">' + escapeHtml(a.name) + '</a></li>');
      });
      html.push('</ul></div>');
    }
    if (data.poems && data.poems.length) {
      html.push('<div class="site-search-group"><span class="site-search-group-title">Стихи</span><ul class="site-search-list">');
      data.poems.forEach(function(p) {
        var sub = p.author ? ' <span class="site-search-item-meta">' + escapeHtml(p.author) + '</span>' : '';
        html.push('<li><a href="' + baseUrl + '/' + p.slug + '/" class="site-search-item">' + escapeHtml(p.title) + sub + '</a></li>');
      });
      html.push('</ul></div>');
    }
    if (html.length) show(html.join('')); else hide();
  }
  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = input.value.trim();
    if (q.length < 3) { hide(); return; }
    timer = setTimeout(function() {
      fetch(baseUrl + '/search/suggest?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(render)
        .catch(function() { hide(); });
    }, 250);
  });
  input.addEventListener('blur', function() {
    clearTimeout(hideTimeout);
    hideTimeout = setTimeout(hide, 220);
  });
  dropdown.addEventListener('mousedown', function(e) {
    var a = e.target.closest('a');
    if (a && a.href) {
      e.preventDefault();
      clearTimeout(hideTimeout);
      hide();
      window.location.href = a.href;
    }
  });
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && e.target !== input) hide();
  });
})();
</script>
@endpush
@endsection
