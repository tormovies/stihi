@extends('admin.layouts.app')

@section('title', 'Изменить тег')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.tags.index') }}">Теги</a><span>›</span><span>{{ $tag->name }}</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Изменить тег</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="admin-form">
        @csrf
        @method('PUT')
        <div class="admin-form-group">
            <label for="name">Название</label>
            <input type="text" id="name" name="name" value="{{ old('name', $tag->name) }}" required>
            @error('name')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $tag->slug) }}" required>
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="sort_order">Порядок сортировки</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $tag->sort_order) }}">
        </div>
        <details class="admin-spoiler" open>
            <summary>SEO (meta_title, meta_description, h1, описание под h1)</summary>
            <div class="admin-spoiler-body">
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $tag->meta_title) }}" maxlength="255">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description', $tag->meta_description) }}" maxlength="500">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1', $tag->h1) }}" maxlength="255">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <textarea id="h1_description" name="h1_description" rows="4">{{ old('h1_description', $tag->h1_description) }}</textarea>
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
