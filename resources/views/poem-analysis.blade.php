@extends('layouts.site')

@section('title', $analysis->meta_title ?: (e_decode($poem->title) . ' — анализ'))
@section('meta_description', $analysis->meta_description ?: ('Анализ стихотворения «' . e_decode($poem->title) . '» ' . e_decode($poem->author->name ?? '') . '.'))

@section('content')
<div class="container poem-analysis">
    <nav class="breadcrumb">
        <a href="/">Главная</a> →
        <a href="/{{ $poem->author->slug }}">{{ e_decode($poem->author->name) }}</a> →
        <a href="/{{ $poem->slug }}">{{ e_decode($poem->title) }}</a> →
        Анализ
    </nav>
    @php
        $h1 = $analysis->h1 ?: (e_decode($poem->title) . ' — анализ');
        $h1 = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $h1);
        $h1Desc = $analysis->h1_description ? str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $analysis->h1_description) : '';
    @endphp
    <h1>{!! $h1 !!}</h1>
    @if($h1Desc !== '')
        <p class="author-name">{!! $h1Desc !!}</p>
    @endif
    <div class="page-body analysis-body">
        {!! $analysis->analysis_html !!}
    </div>
    <p style="margin-top: 1.5rem;"><a href="/{{ $poem->slug }}">← К стихотворению</a></p>
</div>
@endsection
