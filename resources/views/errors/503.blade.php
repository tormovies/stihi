@extends('layouts.site')

@section('title', 'Сайт временно недоступен (503) | Стихотворения поэтов классиков')
@section('meta_description', 'Сайт временно на обслуживании. Попробуйте зайти позже.')

@section('content')
<div class="container error-page">
    <h1 class="error-page-code">503</h1>
    <p class="error-page-title">Сайт временно недоступен</p>
    <p class="error-page-text">Идёт техническое обслуживание. Попробуйте зайти через несколько минут.</p>
    <p class="error-page-actions">
        <a href="{{ url('/') }}" class="poem-btn">На главную</a>
    </p>
</div>
@endsection
