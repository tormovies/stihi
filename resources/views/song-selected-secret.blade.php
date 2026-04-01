@extends('layouts.site')

@section('title', 'Выбранные песни (служебно)')
@section('meta_description', 'Служебный список')

@push('meta')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
<div class="container">
    <h1>Стихи: статус «Песня» — выбран</h1>
    <p class="page-tagline">Всего: {{ $poems->count() }}</p>
    @if($poems->isEmpty())
        <p>Нет стихов с этим статусом.</p>
    @else
        <ul class="authors-list song-selected-list">
            @foreach($poems as $poem)
                <li class="song-selected-item">
                    {{ $poem->author->name }} — <a href="{{ url('/' . $poem->slug) }}" class="list-row-link" target="_blank" rel="noopener noreferrer">{{ $poem->title }}</a>
                    @if(!empty($poem->song_url))
                        <span class="song-selected-url"> · <a href="{{ $poem->song_url }}" target="_blank" rel="noopener noreferrer">URL песни</a></span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
