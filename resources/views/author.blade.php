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
            <li><a href="/{{ $poem->slug }}/" class="list-row-link {{ in_array($poem->id, $readIds ?? [], true) ? 'is-read' : '' }}">{{ e_decode($poem->title) }}</a></li>
        @endforeach
    </ul>
    <div class="pagination-wrap">{{ $poems->links() }}</div>
    @if(!empty($read_debug))
    <div class="read-debug" style="margin-top:1.5rem;padding:1rem;background:#f0f0f0;border:1px solid #ccc;font-family:monospace;font-size:12px;word-break:break-all;">
        <strong>Отладка «Прочитано» на странице автора (?debug)</strong><br>
        raw_cookie: {{ $read_debug['raw_cookie'] === null ? 'null' : (strlen($read_debug['raw_cookie'] ?? '') > 200 ? substr($read_debug['raw_cookie'], 0, 200) . '…' : ($read_debug['raw_cookie'] ?? '')) }}<br>
        count: {{ $read_debug['count'] ?? 0 }}<br>
        read_ids: {{ json_encode($read_debug['read_ids'] ?? []) }}
    </div>
    @endif
</div>
@endsection
