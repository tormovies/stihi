@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('tag', $tag))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('tag', $tag))

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → <a href="{{ route('tags.index') }}">Теги</a> → {{ e_decode($tag->name) }}</nav>
    <h1>{{ \App\Models\SeoTemplate::renderH1('tag', $tag) ?: e_decode($tag->name) }}</h1>
    @if(\App\Models\SeoTemplate::renderH1Description('tag', $tag))
        @php $tagline = \App\Models\SeoTemplate::renderH1Description('tag', $tag); $tagline = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $tagline); @endphp
        <p class="author-tagline">{!! $tagline !!}</p>
    @endif
    @if($poems->isEmpty())
        <p class="search-empty">Пока нет стихов с этим тегом.</p>
    @else
        <ul class="poems-list">
            @foreach($poems as $poem)
                <li><a href="/{{ $poem->slug }}" class="list-row-link">{{ e_decode($poem->title) }}@if($poem->author)<span class="list-row-meta"> — {{ e_decode($poem->author->name) }}</span>@endif</a></li>
            @endforeach
        </ul>
        <div class="pagination-wrap">{{ $poems->links() }}</div>
    @endif
</div>
@endsection
