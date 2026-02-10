@extends('layouts.site')

@section('title', 'Ошибка сервера (500) | Стихотворения поэтов классиков')
@section('meta_description', 'Временная ошибка сервера. Попробуйте обновить страницу или перейти на главную.')

@section('content')
<div class="container error-page">
    <h1 class="error-page-code">500</h1>
    <p class="error-page-title">Ошибка сервера</p>
    <p class="error-page-text">Что-то пошло не так на сервере. Попробуйте обновить страницу или перейдите на главную.</p>
    <p class="error-page-actions">
        <a href="{{ url('/') }}" class="poem-btn">Перейти на главную</a>
    </p>
</div>
@endsection
