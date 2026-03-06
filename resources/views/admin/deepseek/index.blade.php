@extends('admin.layouts.app')

@section('title', 'Оптимизация SEO (DeepSeek)')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Оптимизация SEO</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Оптимизация SEO (DeepSeek)</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.poem-analyses.index') }}" class="admin-btn admin-btn-secondary">Список анализов</a>
        <a href="{{ route('admin.deepseek.log') }}" class="admin-btn admin-btn-secondary">Лог запросов</a>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">Настройки</h2>
    <form method="POST" action="{{ route('admin.deepseek.settings.store') }}" class="admin-form admin-form--compact">
        @csrf
        <div class="admin-form-row admin-form-row--compact">
            <div class="admin-form-group admin-form-group--inline">
                <label for="api_key">API-ключ</label>
                <input type="password" id="api_key" name="api_key" value="" placeholder="{{ $apiKeySet ? '•••• (пусто = не менять)' : 'Введите ключ' }}" autocomplete="off">
            </div>
            <div class="admin-form-group admin-form-group--inline">
                <label for="timeout">Таймаут (сек)</label>
                <input type="number" id="timeout" name="timeout" value="{{ old('timeout', $timeout) }}" min="60" max="600" step="1" title="300–360 = 5–6 мин">
            </div>
            <div class="admin-form-group admin-form-group--inline">
                <label for="max_tokens">Макс. токенов</label>
                <input type="number" id="max_tokens" name="max_tokens" value="{{ old('max_tokens', $maxTokens) }}" min="500" max="32000" step="1">
            </div>
            <div class="admin-form-group admin-form-group--inline">
                <label for="batch_size">Батч</label>
                <input type="number" id="batch_size" name="batch_size" value="{{ old('batch_size', $batchSize) }}" min="1" max="50" step="1" title="Стихов/авторов/анализов за запрос">
            </div>
            <div class="admin-form-group admin-form-group--inline">
                <label for="analysis_length_min">Мин. длина для анализа</label>
                <input type="number" id="analysis_length_min" name="analysis_length_min" value="{{ old('analysis_length_min', $analysisLengthMin) }}" min="100" max="5000" step="1" title="Знаков, по умол. 600">
            </div>
            <div class="admin-form-actions admin-form-actions--inline">
                <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            </div>
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
    <p class="admin-card-desc">Отправляются только не обработанные. Стихов (SEO): <strong>{{ $unprocessedPoems }}</strong>. Авторов: <strong>{{ $unprocessedAuthors }}</strong>. Анализов (длина ≥ {{ $analysisLengthMin }} знаков): <strong>{{ $unprocessedAnalyses }}</strong>. Размер батча и таймаут — из настроек выше.</p>
    <div class="admin-deepseek-wipe-tiles" style="margin-top: 0.5rem;">
        <a href="{{ route('admin.deepseek.run') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить стихи (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.authors') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить авторов (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.analyses') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить анализы (в новой вкладке)</a>
    </div>
    <p class="admin-form-hint" style="margin-top: 0.5rem;">Откроется новая страница: запрос к API, ожидание и результат одного батча.</p>
</div>
@endsection
