@extends('admin.layouts.app')

@section('title', 'Добавить автора')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.authors.index') }}">Авторы</a><span>›</span><span>Добавить</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Добавить автора</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.authors.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.authors.store') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required>
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="name">Имя</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="years_of_life">Годы жизни <span class="optional">(например 1799–1837)</span></label>
            <input type="text" id="years_of_life" name="years_of_life" value="{{ old('years_of_life') }}" placeholder="1799–1837">
            @error('years_of_life')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="sort_order">Порядок сортировки</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
        </div>
        <details class="admin-spoiler">
            <summary>SEO (для этого автора)</summary>
            <div class="admin-spoiler-body">
                <p class="admin-card-desc" style="margin-top: 0;">Подстановки: <code>{name}</code>, <code>{years}</code>. Пустые — используются общие шаблоны.</p>
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" placeholder="{name} — стихи">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description') }}" placeholder="Стихи {name}. Читать текст.">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1') }}" placeholder="{name}">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <input type="text" id="h1_description" name="h1_description" value="{{ old('h1_description') }}" placeholder="Текст под заголовком">
                </div>
            </div>
        </details>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            <a href="{{ route('admin.authors.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
