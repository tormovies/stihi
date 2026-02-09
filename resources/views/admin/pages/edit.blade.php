@extends('admin.layouts.app')

@section('title', 'Изменить страницу')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.pages.index') }}">Страницы</a><span>›</span><span>{{ Str::limit($page->title, 40) }}</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Изменить страницу</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.pages.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="admin-form">
        @csrf
        @method('PUT')
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" required>
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="title">Название</label>
            <input type="text" id="title" name="title" value="{{ old('title', $page->title) }}" required>
            @error('title')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="body">Текст (HTML)</label>
            <textarea id="body" name="body" rows="15" class="w-full">{{ old('body', $page->body) }}</textarea>
        </div>
        <details class="admin-spoiler">
            <summary>SEO (для этой страницы)</summary>
            <div class="admin-spoiler-body">
                <p class="admin-card-desc" style="margin-top: 0;">Подстановка: <code>{title}</code>. Пустые — используются общие шаблоны.</p>
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" placeholder="{title}">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1', $page->h1) }}" placeholder="{title}">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <input type="text" id="h1_description" name="h1_description" value="{{ old('h1_description', $page->h1_description) }}">
                </div>
            </div>
        </details>
        <div class="admin-form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))>
                Опубликована
            </label>
        </div>
        <div class="admin-form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_home" value="1" @checked(old('is_home', $page->is_home))>
                Главная страница
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            <a href="{{ route('admin.pages.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
