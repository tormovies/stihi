@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('page', $page))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('page', $page))

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → {{ e_decode($page->title) }}</nav>
    <h1>{{ \App\Models\SeoTemplate::renderH1('page', $page) ?: e_decode($page->title) }}</h1>
    @if(\App\Models\SeoTemplate::renderH1Description('page', $page))
        @php $tagline = \App\Models\SeoTemplate::renderH1Description('page', $page); $tagline = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $tagline); @endphp
        <p class="page-tagline">{!! $tagline !!}</p>
    @endif
    <div class="page-body">
        {!! \Illuminate\Support\Str::replace(['https://stihotvorenie.su', 'http://stihotvorenie.su'], '', $page->body ?? '') !!}
    </div>
</div>
@endsection
