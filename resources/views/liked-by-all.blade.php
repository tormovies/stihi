@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('liked_by_all', null))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('liked_by_all', null))

@section('content')
@php
    $seoH1 = \App\Models\SeoTemplate::renderH1('liked_by_all', null);
    $seoH1Description = \App\Models\SeoTemplate::renderH1Description('liked_by_all', null);
@endphp
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → {{ $seoH1 ?: 'Понравившееся всем' }}</nav>
    <h1>{{ $seoH1 ?: 'Понравившееся всем' }}</h1>
    @if($seoH1Description)
        <p class="author-tagline liked-by-all-h1-desc">{{ $seoH1Description }}</p>
    @endif

    @if($poems->isEmpty())
        <p class="liked-by-all-empty">Пока нет стихов с отметками «Нравится».</p>
    @else
        <div class="liked-by-all-cols">
            <ul class="liked-by-all-list">
                @foreach($poemsLeft as $poem)
                    <li class="liked-by-all-item">
                        <a href="{{ url('/' . $poem->slug . '/') }}" class="liked-by-all-link">
                            <span class="liked-by-all-title">{{ e_decode($poem->title) }}</span>
                            <span class="liked-by-all-meta">
                                <span class="liked-by-all-author">{{ e_decode($poem->author->name) }}</span>
                                <span class="liked-by-all-likes" title="Отметок «Нравится»">♥ {{ (int) $poem->likes }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <ul class="liked-by-all-list">
                @foreach($poemsRight as $poem)
                    <li class="liked-by-all-item">
                        <a href="{{ url('/' . $poem->slug . '/') }}" class="liked-by-all-link">
                            <span class="liked-by-all-title">{{ e_decode($poem->title) }}</span>
                            <span class="liked-by-all-meta">
                                <span class="liked-by-all-author">{{ e_decode($poem->author->name) }}</span>
                                <span class="liked-by-all-likes" title="Отметок «Нравится»">♥ {{ (int) $poem->likes }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($poems->hasPages())
            <div class="liked-by-all-pagination">
                {{ $poems->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
