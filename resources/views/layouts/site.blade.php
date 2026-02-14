<!DOCTYPE html>
<html lang="ru" id="html-theme">
@include('layouts.partials.site-header')
    <main class="site-main">
        @yield('content')
    </main>
@include('layouts.partials.site-footer')
    <script>
(function() {
  var KEY = 'stihotvorenie-theme';
  var html = document.documentElement;
  var btn = document.getElementById('theme-toggle');
  function setTheme(name) {
    html.classList.remove('theme-light', 'theme-dark');
    html.classList.add(name || 'theme-light');
    try { localStorage.setItem(KEY, name || 'theme-light'); } catch (e) {}
    if (btn) { btn.textContent = (name === 'theme-dark') ? 'Светлая' : 'Тёмная'; btn.setAttribute('aria-label', (name === 'theme-dark') ? 'Включить светлую тему' : 'Включить тёмную тему'); }
  }
  var saved = null;
  try { saved = localStorage.getItem(KEY); } catch (e) {}
  setTheme(saved);
  if (btn) btn.addEventListener('click', function() {
    setTheme(html.classList.contains('theme-dark') ? 'theme-light' : 'theme-dark');
  });
})();
    </script>
    <script>
(function() {
  var burger = document.getElementById('burger-toggle');
  var menu = document.getElementById('burger-menu');
  var clearRead = document.getElementById('clear-read-btn');
  var readCookieName = 'poem_read';
  if (burger && menu) {
    burger.addEventListener('click', function(e) {
      e.stopPropagation();
      var open = menu.getAttribute('aria-hidden') !== 'true';
      menu.setAttribute('aria-hidden', open ? 'true' : 'false');
      burger.setAttribute('aria-expanded', open ? 'false' : 'true');
      menu.classList.toggle('is-open', !open);
    });
    document.addEventListener('click', function() {
      menu.setAttribute('aria-hidden', 'true');
      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
    });
    menu.addEventListener('click', function(e) { e.stopPropagation(); });
  }
  if (clearRead) {
    clearRead.addEventListener('click', function() {
      if (!confirm('Очистить список прочитанного?')) return;
      document.cookie = readCookieName + '=; path=/; max-age=0';
      if (menu) { menu.classList.remove('is-open'); menu.setAttribute('aria-hidden', 'true'); }
      if (burger) burger.setAttribute('aria-expanded', 'false');
      var toast = document.createElement('div');
      toast.className = 'site-toast';
      toast.setAttribute('role', 'status');
      toast.textContent = 'Список прочитанного очищен';
      document.body.appendChild(toast);
      setTimeout(function() {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 3000);
    });
  }
})();
    </script>
    <script>
(function() {
  var input = document.getElementById('site-search-input');
  var dropdown = document.getElementById('site-search-dropdown');
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
  function render(data, query) {
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
        var sub = p.author ? ' — <span class="site-search-item-meta">' + escapeHtml(p.author) + '</span>' : '';
        html.push('<li><a href="' + baseUrl + '/' + p.slug + '/" class="site-search-item">' + escapeHtml(p.title) + sub + '</a></li>');
      });
      html.push('</ul></div>');
    }
    if (query && query.length >= 3) {
      html.push('<div class="site-search-group site-search-all-wrap"><a href="' + baseUrl + '/search?q=' + encodeURIComponent(query) + '" class="site-search-all-link">Все результаты</a></div>');
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
        .then(function(data) { render(data, q); })
        .catch(function() { hide(); });
    }, 250);
  });
  input.addEventListener('blur', function() {
    clearTimeout(hideTimeout);
    hideTimeout = setTimeout(hide, 220);
  });
  input.addEventListener('focus', function() { clearTimeout(hideTimeout); });
  dropdown.addEventListener('mousedown', function(e) {
    var a = e.target.closest('a');
    if (a && a.href) {
      e.preventDefault();
      clearTimeout(hideTimeout);
      hide();
      window.location.href = a.href;
    }
  });
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { hide(); input.blur(); return; }
    if (e.key === 'Enter') {
      var q = input.value.trim();
      if (q.length >= 3) {
        e.preventDefault();
        hide();
        window.location.href = baseUrl + '/search?q=' + encodeURIComponent(q);
      }
    }
  });
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && e.target !== input) hide();
  });
})();
    </script>
    @stack('scripts')
    @stack('back-to-top')
</body>
</html>
