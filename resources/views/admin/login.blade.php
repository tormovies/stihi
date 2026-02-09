<!DOCTYPE html>
<html lang="ru" id="html-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Админка</title>
    @php
        $cssFile = file_exists(public_path('css/site.min.css')) ? 'css/site.min.css' : 'css/site.css';
        $cssPath = public_path($cssFile);
    @endphp
    <link rel="stylesheet" href="{{ asset($cssFile) }}?v={{ file_exists($cssPath) ? filemtime($cssPath) : '' }}">
</head>
<body class="admin">
    <div class="theme-toggle-wrap"><button type="button" class="theme-toggle" id="theme-toggle" aria-label="Сменить тему">☀️ / 🌙</button></div>
    <main class="admin-login-wrap">
        <h1>Вход в админку</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full">
                @error('email')<span class="text-red-600">{{ $message }}</span>@enderror
            </div>
            <div>
                <label for="password">Пароль</label>
                <input id="password" type="password" name="password" required class="w-full">
            </div>
            <div>
                <label><input type="checkbox" name="remember"> Запомнить</label>
            </div>
            <button type="submit">Войти</button>
        </form>
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
  try { setTheme(localStorage.getItem(KEY)); } catch (e) { setTheme('theme-light'); }
  if (btn) btn.addEventListener('click', function() {
    setTheme(html.classList.contains('theme-dark') ? 'theme-light' : 'theme-dark');
  });
})();
    </script>
</body>
</html>
