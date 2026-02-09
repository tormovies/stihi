@extends('layouts.site')

@section('title', e($seoPage->meta_title ?: $seoPage->path))
@section('meta_description', e($seoPage->meta_description ?? ''))

@section('content')
<div class="container">
    @if($seoPage->meta_title || $seoPage->meta_description)
        @if($seoPage->meta_title)<h1>{{ $seoPage->meta_title }}</h1>@endif
        @if($seoPage->meta_description)<div class="page-body"><p>{{ $seoPage->meta_description }}</p></div>@endif
    @else
        <p class="page-body">Страница «{{ $seoPage->path }}».</p>
    @endif
</div>
@endsection
