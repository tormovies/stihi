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
        <div class="admin-cron-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
            <span class="admin-cron-row__label" style="grid-column: 1 / -1; font-weight: 600;">Расписание (cron)</span>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="cron_run_poems">SEO стихов</label>
                <select id="cron_run_poems" name="cron_run_poems">
                    <option value="off" {{ old('cron_run_poems', $cronRunPoems ?? 'off') === 'off' ? 'selected' : '' }}>выкл</option>
                    <option value="1" {{ old('cron_run_poems', $cronRunPoems ?? '') === '1' ? 'selected' : '' }}>каждую 1 мин</option>
                    <option value="2" {{ old('cron_run_poems', $cronRunPoems ?? '') === '2' ? 'selected' : '' }}>каждые 2 мин</option>
                    <option value="3" {{ old('cron_run_poems', $cronRunPoems ?? '') === '3' ? 'selected' : '' }}>каждые 3 мин</option>
                    <option value="4" {{ old('cron_run_poems', $cronRunPoems ?? '') === '4' ? 'selected' : '' }}>каждые 4 мин</option>
                    <option value="5" {{ old('cron_run_poems', $cronRunPoems ?? '') === '5' ? 'selected' : '' }}>каждые 5 мин</option>
                    <option value="10" {{ old('cron_run_poems', $cronRunPoems ?? '') === '10' ? 'selected' : '' }}>каждые 10 мин</option>
                    <option value="15" {{ old('cron_run_poems', $cronRunPoems ?? '') === '15' ? 'selected' : '' }}>каждые 15 мин</option>
                    <option value="20" {{ old('cron_run_poems', $cronRunPoems ?? '') === '20' ? 'selected' : '' }}>каждые 20 мин</option>
                    <option value="30" {{ old('cron_run_poems', $cronRunPoems ?? '') === '30' ? 'selected' : '' }}>каждые 30 мин</option>
                </select>
            </div>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="cron_run_analyses">Анализы стихов</label>
                <select id="cron_run_analyses" name="cron_run_analyses">
                    <option value="off" {{ old('cron_run_analyses', $cronRunAnalyses ?? '5') === 'off' ? 'selected' : '' }}>выкл</option>
                    <option value="1" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '1' ? 'selected' : '' }}>каждую 1 мин</option>
                    <option value="2" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '2' ? 'selected' : '' }}>каждые 2 мин</option>
                    <option value="3" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '3' ? 'selected' : '' }}>каждые 3 мин</option>
                    <option value="4" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '4' ? 'selected' : '' }}>каждые 4 мин</option>
                    <option value="5" {{ old('cron_run_analyses', $cronRunAnalyses ?? '5') === '5' ? 'selected' : '' }}>каждые 5 мин</option>
                    <option value="10" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '10' ? 'selected' : '' }}>каждые 10 мин</option>
                    <option value="15" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '15' ? 'selected' : '' }}>каждые 15 мин</option>
                    <option value="20" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '20' ? 'selected' : '' }}>каждые 20 мин</option>
                    <option value="30" {{ old('cron_run_analyses', $cronRunAnalyses ?? '') === '30' ? 'selected' : '' }}>каждые 30 мин</option>
                </select>
            </div>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="cron_run_tags_seo">SEO страниц тегов</label>
                <select id="cron_run_tags_seo" name="cron_run_tags_seo">
                    <option value="off" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? 'off') === 'off' ? 'selected' : '' }}>выкл</option>
                    <option value="1" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '1' ? 'selected' : '' }}>каждую 1 мин</option>
                    <option value="2" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '2' ? 'selected' : '' }}>каждые 2 мин</option>
                    <option value="3" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '3' ? 'selected' : '' }}>каждые 3 мин</option>
                    <option value="4" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '4' ? 'selected' : '' }}>каждые 4 мин</option>
                    <option value="5" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '5' ? 'selected' : '' }}>каждые 5 мин</option>
                    <option value="10" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '10' ? 'selected' : '' }}>каждые 10 мин</option>
                    <option value="15" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '15' ? 'selected' : '' }}>каждые 15 мин</option>
                    <option value="20" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '20' ? 'selected' : '' }}>каждые 20 мин</option>
                    <option value="30" {{ old('cron_run_tags_seo', $cronRunTagsSeo ?? '') === '30' ? 'selected' : '' }}>каждые 30 мин</option>
                </select>
            </div>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="cron_run_poem_tags">Разметка стихов по тегам</label>
                <select id="cron_run_poem_tags" name="cron_run_poem_tags">
                    <option value="off" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? 'off') === 'off' ? 'selected' : '' }}>выкл</option>
                    <option value="1" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '1' ? 'selected' : '' }}>каждую 1 мин</option>
                    <option value="2" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '2' ? 'selected' : '' }}>каждые 2 мин</option>
                    <option value="3" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '3' ? 'selected' : '' }}>каждые 3 мин</option>
                    <option value="4" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '4' ? 'selected' : '' }}>каждые 4 мин</option>
                    <option value="5" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '5' ? 'selected' : '' }}>каждые 5 мин</option>
                    <option value="10" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '10' ? 'selected' : '' }}>каждые 10 мин</option>
                    <option value="15" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '15' ? 'selected' : '' }}>каждые 15 мин</option>
                    <option value="20" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '20' ? 'selected' : '' }}>каждые 20 мин</option>
                    <option value="30" {{ old('cron_run_poem_tags', $cronRunPoemTags ?? '') === '30' ? 'selected' : '' }}>каждые 30 мин</option>
                </select>
            </div>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="cron_run_suno">Suno-анализ</label>
                <select id="cron_run_suno" name="cron_run_suno">
                    <option value="off" {{ old('cron_run_suno', $cronRunSuno ?? 'off') === 'off' ? 'selected' : '' }}>выкл</option>
                    <option value="1" {{ old('cron_run_suno', $cronRunSuno ?? '') === '1' ? 'selected' : '' }}>каждую 1 мин</option>
                    <option value="2" {{ old('cron_run_suno', $cronRunSuno ?? '') === '2' ? 'selected' : '' }}>каждые 2 мин</option>
                    <option value="3" {{ old('cron_run_suno', $cronRunSuno ?? '') === '3' ? 'selected' : '' }}>каждые 3 мин</option>
                    <option value="4" {{ old('cron_run_suno', $cronRunSuno ?? '') === '4' ? 'selected' : '' }}>каждые 4 мин</option>
                    <option value="5" {{ old('cron_run_suno', $cronRunSuno ?? '') === '5' ? 'selected' : '' }}>каждые 5 мин</option>
                    <option value="10" {{ old('cron_run_suno', $cronRunSuno ?? '') === '10' ? 'selected' : '' }}>каждые 10 мин</option>
                    <option value="15" {{ old('cron_run_suno', $cronRunSuno ?? '') === '15' ? 'selected' : '' }}>каждые 15 мин</option>
                    <option value="20" {{ old('cron_run_suno', $cronRunSuno ?? '') === '20' ? 'selected' : '' }}>каждые 20 мин</option>
                    <option value="30" {{ old('cron_run_suno', $cronRunSuno ?? '') === '30' ? 'selected' : '' }}>каждые 30 мин</option>
                </select>
            </div>
            <div class="admin-cron-row__field" style="padding: 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
                <label for="suno_batch_size">Suno: стихов за тик</label>
                <input type="number" id="suno_batch_size" name="suno_batch_size" value="{{ old('suno_batch_size', $sunoBatchSize ?? 1) }}" min="1" max="20" step="1" title="1 стих = 1 request; сколько request за один запуск cron">
            </div>
            <p class="admin-cron-row__hint" style="grid-column: 1 / -1; margin: 0; font-size: 0.9rem; color: var(--text-muted);">На сервере должен быть настроен <code>* * * * * php artisan schedule:run</code>. Расписание применяется при следующем проходе планировщика. Suno: 1 стих = 1 запрос; «стихов за тик» — сколько таких запросов подряд.</p>
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
        <form method="POST" action="{{ route('admin.deepseek.wipe.suno') }}" class="admin-deepseek-wipe-tile" onsubmit="return confirm('Удалить ВСЕ Suno-анализы и начать очередь заново?');">
            @csrf
            <button type="submit" class="admin-btn admin-btn-secondary">Сбросить все Suno-анализы</button>
        </form>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">Ручной запуск</h2>
    <p class="admin-card-desc">Отправляются только не обработанные. Стихов (SEO): <strong>{{ $unprocessedPoems }}</strong>. Авторов: <strong>{{ $unprocessedAuthors }}</strong>. Анализов (длина ≥ {{ $analysisLengthMin }} знаков): <strong>{{ $unprocessedAnalyses }}</strong>. Тегов без SEO: <strong>{{ $unprocessedTagsSeo }}</strong>. Стихов без тегов: <strong>{{ $unprocessedPoemsForTags }}</strong>. Suno (400–2000, без анализа): <strong>{{ $unprocessedSuno ?? 0 }}</strong>. Размер батча SEO/тегов и таймаут — из настроек выше; Suno — «стихов за тик».</p>
    <div class="admin-deepseek-wipe-tiles" style="margin-top: 0.5rem;">
        <a href="{{ route('admin.deepseek.run') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить стихи (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.authors') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить авторов (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.analyses') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить анализы (в новой вкладке)</a>
        <a href="{{ route('admin.deepseek.run.tags-seo') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">SEO страниц тегов</a>
        <a href="{{ route('admin.deepseek.run.poem-tags') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Разметка стихов по тегам</a>
        <a href="{{ route('admin.deepseek.run.suno') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить Suno</a>
    </div>
    <p class="admin-form-hint" style="margin-top: 0.5rem;">Откроется новая страница: запрос к API, ожидание и результат одного батча.</p>
</div>
@endsection
