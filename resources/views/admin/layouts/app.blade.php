<!DOCTYPE html>
<html lang="ru" id="html-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Админка') — Стихотворения</title>
    @php
        $cssFile = file_exists(public_path('css/site.min.css')) ? 'css/site.min.css' : 'css/site.css';
        $cssPath = public_path($cssFile);
    @endphp
    <link rel="stylesheet" href="{{ asset($cssFile) }}?v={{ file_exists($cssPath) ? filemtime($cssPath) : '' }}">
</head>
<body class="admin">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a href="{{ route('admin.dashboard') }}">Админка</a>
            <nav>
            <a href="{{ route('admin.authors.index') }}">Авторы</a>
            <a href="{{ route('admin.poems.index') }}">Стихи</a>
            <a href="{{ route('admin.pages.index') }}">Страницы</a>
            <a href="{{ route('admin.seo.index') }}">SEO</a>
            <a href="{{ route('admin.security.index') }}">Безопасность</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener">Сайт ↗</a>
            <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Сменить тему">☀️ / 🌙</button>
            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit">Выход</button>
            </form>
            </nav>
        </div>
    </header>
    <main class="admin-main @yield('content_class')">
        @if(session('success'))
            <p class="alert alert-success">{{ session('success') }}</p>
        @endif
        @hasSection('breadcrumb')
            <nav class="admin-breadcrumb" aria-label="Хлебные крошки">@yield('breadcrumb')</nav>
        @endif
        @yield('content')
    </main>
    <script>
(function() {
  var KEY = 'stihotvorenie-theme';
  var html = document.documentElement;
  var btn = document.getElementById('theme-toggle');
  function setTheme(name) {
    html.classList.remove('theme-light', 'theme-dark');
    html.classList.add(name || 'theme-light');
    try { localStorage.setItem(KEY, name || 'theme-light'); } catch (e) {}
    if (btn) btn.textContent = (name === 'theme-dark') ? '☀️' : '🌙';
  }
  var saved = null;
  try { saved = localStorage.getItem(KEY); } catch (e) {}
  setTheme(saved);
  if (btn) btn.addEventListener('click', function() {
    setTheme(html.classList.contains('theme-dark') ? 'theme-light' : 'theme-dark');
  });
})();
    </script>
</body>
</html>
