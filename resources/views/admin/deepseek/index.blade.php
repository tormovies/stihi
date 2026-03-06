@extends('admin.layouts.app')

@section('title', 'Оптимизация SEO (DeepSeek)')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Оптимизация SEO</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Оптимизация SEO (DeepSeek)</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.deepseek.log') }}" class="admin-btn admin-btn-secondary">Лог запросов</a>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">Настройки</h2>
    <form method="POST" action="{{ route('admin.deepseek.settings.store') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="api_key">API-ключ DeepSeek</label>
            <input type="password" id="api_key" name="api_key" value="" placeholder="{{ $apiKeySet ? '•••••••• (оставьте пустым, чтобы не менять)' : 'Введите ключ' }}" autocomplete="off">
            @if($apiKeySet)
                <p class="admin-form-hint">Ключ сохранён. Введите новый, чтобы заменить.</p>
            @endif
        </div>
        <div class="admin-form-group">
            <label for="timeout">Таймаут запроса (сек)</label>
            <input type="number" id="timeout" name="timeout" value="{{ old('timeout', $timeout) }}" min="60" max="600" step="1">
            <p class="admin-form-hint">Рекомендуется 300–360 (5–6 минут).</p>
        </div>
        <div class="admin-form-group">
            <label for="batch_size">Размер батча (стихов или авторов за один запрос)</label>
            <input type="number" id="batch_size" name="batch_size" value="{{ old('batch_size', $batchSize) }}" min="1" max="50" step="1">
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить настройки</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">Сброс SEO</h2>
    <p class="admin-card-desc">Перед первой массовой оптимизацией сбросьте SEO у стихов. Позже — только добавляйте (не стирайте).</p>
    <div class="admin-deepseek-wipe-tiles">
        <form method="POST" action="{{ route('admin.deepseek.wipe.poems') }}" class="admin-deepseek-wipe-tile" onsubmit="return confirm('Стереть все SEO-параметры у всех стихов?');">
            @csrf
            <button type="submit" class="admin-btn admin-btn-secondary">Стереть SEO у всех стихов</button>
        </form>
        <form method="POST" action="{{ route('admin.deepseek.wipe.authors') }}" class="admin-deepseek-wipe-tile" onsubmit="return confirm('Стереть все SEO-параметры у всех авторов?');">
            @csrf
            <button type="submit" class="admin-btn admin-btn-secondary">Стереть SEO у всех авторов</button>
        </form>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">Ручной запуск</h2>
    <p class="admin-card-desc">Отправляются только не обработанные (без SEO). Стихов: <strong>{{ $unprocessedPoems }}</strong>. Авторов: <strong>{{ $unprocessedAuthors }}</strong>. Размер батча и таймаут — из настроек выше.</p>
    <div class="admin-deepseek-wipe-tiles" style="margin-top: 0.5rem;">
        <a href="{{ route('admin.deepseek.run') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить стихи (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.authors') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить авторов (в новой вкладке)</a>
    </div>
    <p class="admin-form-hint" style="margin-top: 0.5rem;">Откроется новая страница: запрос к API, ожидание и результат одного батча.</p>
</div>
@endsection
