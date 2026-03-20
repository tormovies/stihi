@extends('admin.layouts.app')

@section('title', $redirect ? 'Редактировать 301' : 'Новый 301 редирект')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span>
    <a href="{{ route('admin.seo.index') }}">SEO</a><span>›</span>
    <a href="{{ route('admin.seo.redirects.index') }}">301 редиректы</a><span>›</span><span>{{ $redirect ? 'Изменение' : 'Новый' }}</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>{{ $redirect ? 'Редактировать редирект' : 'Новый редирект 301' }}</h1>
    <a href="{{ route('admin.seo.redirects.index') }}" class="admin-btn admin-btn-secondary">К списку</a>
</div>

<div class="admin-card">
    <form method="POST" action="{{ $redirect ? route('admin.seo.redirects.update', $redirect) : route('admin.seo.redirects.store') }}" class="admin-form">
        @csrf
        @if($redirect)
            @method('PUT')
        @endif
        <div class="admin-form-group">
            <label for="from_path">Откуда (path)</label>
            <input type="text" id="from_path" name="from_path" value="{{ old('from_path', $redirect?->from_path) }}" class="admin-input" style="max-width: 480px;" required placeholder="staryy-slug или staryy-slug/analiz" pattern="[a-z0-9\-]+(?:/[a-z0-9\-]+)*">
            @error('from_path')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="to_path">Куда (path)</label>
            <input type="text" id="to_path" name="to_path" value="{{ old('to_path', $redirect?->to_path) }}" class="admin-input" style="max-width: 480px;" required placeholder="novyy-slug" pattern="[a-z0-9\-]+(?:/[a-z0-9\-]+)*">
            @error('to_path')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <p class="admin-card-desc">Без ведущего и завершающего слэша. Только латиница, цифры, дефис; для анализа — второй сегмент <code>analiz</code>. Не используйте зарезервированные префиксы: admin, poem, search, tegi и т.д.</p>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">{{ $redirect ? 'Сохранить' : 'Добавить' }}</button>
        </div>
    </form>
</div>
@endsection
