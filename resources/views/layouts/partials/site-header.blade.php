{{-- Вся верхняя часть сайта: head, открытие body, шапка и меню. Менять в одном месте. --}}
@php
    // Декодируем сущности и экранируем только " & < > (апостроф ' не трогаем — в атрибутах не нужен &#039;)
    $safeAttr = function ($s) {
        $s = trim(strip_tags((string) $s));
        $s = str_replace(["&#039;", "&#39;", "&apos;"], "'", $s);
        for ($i = 0; $i < 3; $i++) {
            $prev = $s;
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $s = preg_replace_callback('/&#(\d+);?/', fn ($m) => mb_chr((int) $m[1], 'UTF-8'), $s);
            $s = preg_replace_callback('/&#x([0-9a-fA-F]+);?/', fn ($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $s);
            if ($s === $prev) { break; }
        }
        return str_replace(['&', '<', '>', '"'], ['&amp;', '&lt;', '&gt;', '&quot;'], $s);
    };
    $pageTitle = $safeAttr(view()->yieldContent('title', 'Стихотворения поэтов классиков') ?: 'Стихотворения поэтов классиков');
    $pageDesc = $safeAttr(view()->yieldContent('meta_description', 'Портал классической поэзии') ?: 'Портал классической поэзии');
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="{!! $pageDesc !!}">
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
    <meta property="og:title" content="{!! $pageTitle !!}">
    <meta property="og:description" content="{!! $pageDesc !!}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    @stack('og_meta')
    @stack('json_ld')
    @if(!empty($counterCode) && empty($skipCounter))
{!! $counterCode !!}
    @endif
</head>
<body id="top">
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="/" class="site-logo">Стихотворения поэтов классиков</a>
            <div class="site-header-tools">
                <div class="site-search-wrap">
                    <input type="search" class="site-search-input" id="site-search-input" placeholder="Поиск по стихам, авторам и анализам…" autocomplete="off" aria-label="Поиск по стихам, авторам и анализам" minlength="3">
                    <div class="site-search-dropdown" id="site-search-dropdown" role="listbox" aria-hidden="true"></div>
                </div>
                <div class="site-burger-wrap">
                    <button type="button" class="site-burger-btn" id="burger-toggle" aria-label="Меню" aria-expanded="false" aria-haspopup="true">
                        <span class="site-burger-icon" aria-hidden="true"></span>
                    </button>
                    <div class="site-burger-menu" id="burger-menu" aria-hidden="true">
                        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Переключить тему">Тема</button>
                        <a href="{{ route('tags.index') }}" class="site-burger-link">Стихи по темам</a>
                        <a href="{{ route('favorites') }}" class="site-burger-link">Понравившееся Вам</a>
                        <a href="{{ route('liked.by.all') }}" class="site-burger-link">Понравившееся Всем</a>
                        <button type="button" class="site-burger-clear-read" id="clear-read-btn" aria-label="Очистить список прочитанного">Очистить прочитанное</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
