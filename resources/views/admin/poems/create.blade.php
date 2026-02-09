@extends('admin.layouts.app')

@section('title', 'Добавить стих')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.poems.index') }}">Стихи</a><span>›</span><span>Добавить</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Добавить стих</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.poems.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.poems.store') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="author_id">Автор</label>
            <select id="author_id" name="author_id" required>
                @foreach($authors as $a)
                    <option value="{{ $a->id }}" @selected(old('author_id') == $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required>
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="title">Название</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required>
            @error('title')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="body">Текст стихотворения</label>
            <textarea id="body" name="body" rows="15" class="w-full">{{ old('body') }}</textarea>
        </div>
        <details class="admin-spoiler">
            <summary>SEO (для этого стиха)</summary>
            <div class="admin-spoiler-body">
                <p class="admin-card-desc" style="margin-top: 0;">Подстановки: <code>{title}</code>, <code>{author}</code>. Пустые — используются общие шаблоны.</p>
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" placeholder="{title} — {author}">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description') }}">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1') }}" placeholder="{title}">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <input type="text" id="h1_description" name="h1_description" value="{{ old('h1_description') }}">
                </div>
            </div>
        </details>
        <div class="admin-form-group">
            <label for="published_at">Дата публикации</label>
            <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            <a href="{{ route('admin.poems.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
