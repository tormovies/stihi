@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('tags_index', null))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('tags_index', null))

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → Теги стихов</nav>
    <h1>{{ \App\Models\SeoTemplate::renderH1('tags_index', null) ?: 'Теги стихов' }}</h1>
    @if(\App\Models\SeoTemplate::renderH1Description('tags_index', null))
        @php $tagline = \App\Models\SeoTemplate::renderH1Description('tags_index', null); $tagline = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $tagline); @endphp
        <p class="author-tagline">{!! $tagline !!}</p>
    @endif
    <ul class="tags-index-grid">
        @foreach($tags as $tag)
            <li class="tags-index-item">
                <a href="{{ url('/tegi/' . $tag->slug) }}" class="tags-index-link">
                    <span class="tags-index-name">{{ e_decode($tag->name) }}</span>
                    @php
                        $nc = (int) $tag->poems_count;
                        if ($nc % 10 === 1 && $nc % 100 !== 11) {
                            $word = 'стих';
                        } elseif ($nc % 10 >= 2 && $nc % 10 <= 4 && ($nc % 100 < 10 || $nc % 100 >= 20)) {
                            $word = 'стиха';
                        } else {
                            $word = 'стихов';
                        }
                    @endphp
                    <span class="tags-index-count">{{ $nc }} {{ $word }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
