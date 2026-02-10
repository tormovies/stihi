@extends('admin.layouts.app')

@section('title', 'Безопасность')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Безопасность</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Безопасность</h1>
</div>

{{-- Режим логирования --}}
<div class="admin-card">
    <h2 class="admin-card-title">Логирование запросов</h2>
    <p class="admin-card-desc">Запросы пишутся в <code>storage/logs/bots-YYYY-MM-DD.log</code>. Режим «все» создаёт много записей.</p>
    <form method="POST" action="{{ route('admin.security.update') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <span class="admin-form-label">Логировать:</span>
            <label class="checkbox-label">
                <input type="radio" name="request_log_mode" value="bots" {{ ($logMode ?? '') === 'bots' ? 'checked' : '' }}>
                только ботов (по User-Agent)
            </label>
            <label class="checkbox-label" style="margin-left: 1rem;">
                <input type="radio" name="request_log_mode" value="all" {{ ($logMode ?? '') === 'all' ? 'checked' : '' }}>
                всех
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
        </div>
    </form>
</div>

{{-- Просмотр лога за сегодня (фиксированное окно, без лишних HTML-сущностей) --}}
<div class="admin-card">
    <h2 class="admin-card-title">Просмотр лога за сегодня</h2>
    <p class="admin-card-desc">Файл: <code>bots-{{ date('Y-m-d') }}.log</code></p>
    <div class="admin-form-group">
        <span class="admin-form-label">Показать:</span>
        <a href="{{ route('admin.security.index', ['display' => 'last100']) }}" class="admin-btn admin-btn-secondary {{ ($display ?? '') === 'last100' ? 'admin-btn-active' : '' }}">Последние 100 строк</a>
        <a href="{{ route('admin.security.index', ['display' => 'full']) }}" class="admin-btn admin-btn-secondary {{ ($display ?? '') === 'full' ? 'admin-btn-active' : '' }}">Весь лог</a>
    </div>
    <div class="admin-log-wrap" style="width: 100%;">
        <textarea class="admin-log-view" readonly rows="24" aria-label="Содержимое лога" id="admin-security-log" style="width: 100%; box-sizing: border-box;"></textarea>
    </div>
    <script type="application/json" id="admin-security-log-data">{!! json_encode($logContent ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (function() {
            var el = document.getElementById('admin-security-log');
            var data = document.getElementById('admin-security-log-data');
            if (el && data) try { var r = data.textContent; el.value = r ? JSON.parse(r) : ''; } catch (e) { el.value = r || ''; }
        })();
    </script>
</div>
@endsection
