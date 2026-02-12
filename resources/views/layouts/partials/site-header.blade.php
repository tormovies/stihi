{{-- Вся верхняя часть сайта: head, открытие body, шапка и меню. Менять в одном месте. --}}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Стихотворения поэтов классиков')</title>
    <meta name="description" content="@yield('meta_description', 'Портал классической поэзии')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @stack('meta')
    <link rel="preload" href="{{ asset('fonts/literata/literata-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    @php
        $useMin = !config('app.debug') && file_exists(public_path('css/site.min.css'));
        $cssFile = $useMin ? 'css/site.min.css' : 'css/site.css';
        $cssPath = public_path($cssFile);
    @endphp
    <link rel="stylesheet" href="{{ asset($cssFile) }}?v={{ file_exists($cssPath) ? filemtime($cssPath) : '' }}">
    <meta property="og:title" content="{{ e(trim(strip_tags((string) view()->yieldContent('title'))) ?: 'Стихотворения поэтов классиков') }}">
    <meta property="og:description" content="{{ e(trim(strip_tags((string) view()->yieldContent('meta_description'))) ?: 'Портал классической поэзии') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    @stack('og_meta')
    @stack('json_ld')
    @if(!empty($counterCode))
{!! $counterCode !!}
    @endif
</head>
<body id="top">
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="/" class="site-logo">Стихотворения поэтов классиков</a>
            <div class="site-header-tools">
                <div class="site-search-wrap">
                    <input type="search" class="site-search-input" id="site-search-input" placeholder="Поиск по стихам и авторам…" autocomplete="off" aria-label="Поиск по стихам и авторам">
                    <div class="site-search-dropdown" id="site-search-dropdown" role="listbox" aria-hidden="true"></div>
                </div>
                <div class="site-burger-wrap">
                    <button type="button" class="site-burger-btn" id="burger-toggle" aria-label="Меню" aria-expanded="false" aria-haspopup="true">
                        <span class="site-burger-icon" aria-hidden="true"></span>
                    </button>
                    <div class="site-burger-menu" id="burger-menu" aria-hidden="true">
                        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Переключить тему">Тема</button>
                        <a href="{{ route('favorites') }}" class="site-burger-link">Понравившееся</a>
                        <button type="button" class="site-burger-clear-read" id="clear-read-btn" aria-label="Очистить список прочитанного">Очистить прочитанное</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
