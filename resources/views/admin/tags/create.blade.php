@extends('admin.layouts.app')

@section('title', 'Добавить тег')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.tags.index') }}">Теги</a><span>›</span><span>Добавить</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Добавить тег</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.tags.store') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="name">Название</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Стихи про весну">
            @error('name')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required placeholder="stihi-pro-vesnu">
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="sort_order">Порядок сортировки</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
        </div>
        <details class="admin-spoiler">
            <summary>SEO (meta_title, meta_description, h1, описание под h1)</summary>
            <div class="admin-spoiler-body">
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" maxlength="255" placeholder="Стихи про весну — читать онлайн">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description') }}" maxlength="500" placeholder="Краткое описание страницы тега">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1') }}" maxlength="255" placeholder="Стихи про весну">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <textarea id="h1_description" name="h1_description" rows="3" placeholder="Текст под заголовком страницы тега">{{ old('h1_description') }}</textarea>
                </div>
            </div>
        </details>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
