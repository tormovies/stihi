@extends('layouts.site')

@section('title', ($seoHome?->meta_title ?: null) ?? ($page ? e_decode($page->title) : 'Стихотворения поэтов классиков'))
@section('meta_description', ($seoHome?->meta_description ?: null) ?? 'Портал классической поэзии — стихи русских поэтов-классиков.')

@section('content')
<div class="container container--home">
    <h1>{{ ($seoHome?->h1 ?: null) ?? ($page ? e_decode($page->title) : 'Стихотворения поэтов классиков') }}</h1>
    <ul class="authors-list authors-grid">
        @foreach($authors as $author)
            <li><a href="/{{ $author->slug }}/" class="list-row-link">{{ e_decode($author->name) }}</a></li>
        @endforeach
    </ul>

    @if(isset($randomAnalyses) && $randomAnalyses->isNotEmpty())
    <section class="home-analyses" aria-labelledby="home-analyses-title">
        <h2 id="home-analyses-title" class="home-analyses__title">Анализы произведений</h2>
        <ul class="home-analyses-grid">
            @foreach($randomAnalyses as $analysis)
                @php $poem = $analysis->poem; @endphp
                @if($poem)
                <li class="home-analysis-card">
                    <a href="{{ url($poem->slug . '/analiz') }}" class="home-analysis-card__link">
                        <span class="home-analysis-card__author">{{ e_decode($poem->author?->name ?? '') }}</span>
                        <span class="home-analysis-card__title">{{ e_decode($poem->title) }}</span>
                        @if($analysis->h1_description)
                        <p class="home-analysis-card__excerpt">{{ Str::limit(strip_tags($analysis->h1_description), 120) }}</p>
                        @endif
                    </a>
                </li>
                @endif
            @endforeach
        </ul>
    </section>
    @endif
</div>
@endsection
