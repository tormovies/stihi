@extends('admin.layouts.app')

@section('title', 'Панель управления')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Панель управления</h1>
</div>
<div class="admin-dashboard-grid">
    <a href="{{ route('admin.authors.index') }}" class="admin-dashboard-card">
        <h2>Авторы</h2>
        <p>{{ $authorsCount }}</p>
    </a>
    <a href="{{ route('admin.poems.index') }}" class="admin-dashboard-card">
        <h2>Стихи</h2>
        <p>{{ $poemsCount }}</p>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="admin-dashboard-card">
        <h2>Страницы</h2>
        <p>{{ $pagesCount }}</p>
    </a>
</div>
@endsection
