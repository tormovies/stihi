@extends('layouts.site')

@section('title', 'Доступ запрещён (403) | Стихотворения поэтов классиков')
@section('meta_description', 'Доступ к запрашиваемой странице запрещён.')

@section('content')
<div class="container error-page">
    <h1 class="error-page-code">403</h1>
    <p class="error-page-title">Доступ запрещён</p>
    <p class="error-page-text">Доступ с вашего адреса к этому сайту ограничен.</p>
    <p class="error-page-actions">
        <a href="{{ url('/') }}" class="poem-btn">Перейти на главную</a>
    </p>
</div>
@endsection
