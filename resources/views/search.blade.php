@extends('layouts.site')

@section('title', $q ? 'Результаты поиска по запросу «' . e($q) . '»' : 'Поиск')
@section('meta_description', $q ? 'Поиск по стихам и авторам: «' . e($q) . '»' : 'Поиск по стихам и авторам')

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → Поиск{{ $q ? ' → «' . e($q) . '»' : '' }}</nav>
    <h1>Результаты поиска{{ $q ? ' по запросу «' . e($q) . '»' : '' }}</h1>

    @if(mb_strlen($q) < 3)
        <p class="search-empty">Введите не менее 3 символов для поиска.</p>
    @elseif($authors->isEmpty() && $poems->isEmpty())
        <p class="search-empty">Ничего не найдено.</p>
    @else
        @if($authors->isNotEmpty())
            <section class="search-section">
                <h2 class="search-section-title">Авторы</h2>
                <ul class="poems-list">
                    @foreach($authors as $author)
                        <li><a href="/{{ $author->slug }}/" class="list-row-link">{{ e_decode($author->name) }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($poems->isNotEmpty())
            <section class="search-section">
                <h2 class="search-section-title">Стихи</h2>
                <ul class="poems-list">
                    @foreach($poems as $poem)
                        <li><a href="/{{ $poem->slug }}/" class="list-row-link">{{ e_decode($poem->title) }}@if($poem->author) — <span class="list-row-meta">{{ e_decode($poem->author->name) }}</span>@endif</a></li>
                    @endforeach
                </ul>
                <div class="pagination-wrap">{{ $poems->links() }}</div>
            </section>
        @endif
    @endif
</div>
@endsection
