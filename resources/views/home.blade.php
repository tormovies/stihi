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
</div>
@endsection
