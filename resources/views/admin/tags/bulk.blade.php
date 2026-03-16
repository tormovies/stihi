@extends('admin.layouts.app')

@section('title', 'Массовое добавление тегов')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.tags.index') }}">Теги</a><span>›</span><span>Массовое добавление</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Массовое добавление тегов</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <p class="admin-card-desc">Введите названия тегов — по одному в строке. Slug будет сгенерирован автоматически из названия. Строки с уже существующим slug будут пропущены.</p>
    <form method="POST" action="{{ route('admin.tags.bulk.store') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="lines">Названия тегов (каждое с новой строки)</label>
            <textarea id="lines" name="lines" rows="15" placeholder="Стихи про весну&#10;Стихи про маму&#10;Короткие стихи&#10;Стихи на 8 марта" required>{{ old('lines') }}</textarea>
            @error('lines')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Добавить теги</button>
            <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
