@extends('layouts.site')

@section('title', \App\Models\SeoTemplate::renderTitle('author', $author))
@section('meta_description', \App\Models\SeoTemplate::renderDescription('author', $author))

@push('json_ld')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => e_decode($author->name),
    'url' => url()->current(),
    'description' => $author->years_of_life ? 'Годы жизни: ' . e_decode($author->years_of_life) : null,
], fn($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container">
    <nav class="breadcrumb"><a href="/">Главная</a> → {{ e_decode($author->name) }}</nav>
    <h1>{{ \App\Models\SeoTemplate::renderH1('author', $author) ?: e_decode($author->name) }}</h1>
    @if(\App\Models\SeoTemplate::renderH1Description('author', $author))
        <p class="author-tagline">{{ \App\Models\SeoTemplate::renderH1Description('author', $author) }}</p>
    @endif
    @if($author->years_of_life)
        <p class="author-years">{{ e_decode($author->years_of_life) }}</p>
    @endif
    <ul class="poems-list">
        @foreach($poems as $poem)
            <li><a href="/{{ $poem->slug }}/">{{ e_decode($poem->title) }}</a></li>
        @endforeach
    </ul>
    <div class="pagination-wrap">{{ $poems->links() }}</div>
</div>
@endsection
