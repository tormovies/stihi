@extends('admin.layouts.app')

@section('title', 'Страницы 410')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span>
    <a href="{{ route('admin.security.index') }}">Безопасность</a><span>›</span><span>Страницы 410</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Страницы 410</h1>
    <p class="admin-card-desc">Пути из этого списка при запросе отдают ответ 410 Gone (страница удалена). Запросы логируются в тот же лог 404 с пометкой 410.</p>
</div>

@if(session('error'))
    <p class="alert alert-danger">{{ session('error') }}</p>
@endif

{{-- Добавить путь вручную --}}
<div class="admin-card">
    <h2 class="admin-card-title">Добавить путь в список 410</h2>
    <form method="POST" action="{{ route('admin.security.gone') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <label for="path" class="admin-form-label">Путь (как в URL, без ведущего слэша)</label>
            <input type="text" id="path" name="path" value="{{ old('path') }}" class="admin-input" placeholder="stariy-avtor-ili-stih" maxlength="500" style="max-width: 400px;">
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Добавить</button>
        </div>
    </form>
    <p class="admin-card-desc">Перед добавлением проверяется, что стиха или автора с таким slug нет.</p>
</div>

{{-- Текущий список --}}
<div class="admin-card">
    <h2 class="admin-card-title">Текущий список ({{ $gonePaths->count() }})</h2>
    @if($gonePaths->isEmpty())
        <p class="admin-card-desc">Список пуст.</p>
    @else
        <div class="gone-list">
            @foreach($gonePaths as $row)
                <span class="gone-chip">
                    <span class="gone-chip-text" title="{{ e($row->path) }}">{{ e($row->path) }}</span>
                    <form method="POST" action="{{ route('admin.security.gone.destroy', $row->id) }}" class="gone-chip-form" onsubmit="return confirm('Удалить путь из списка 410?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="gone-chip-remove" aria-label="Удалить">×</button>
                    </form>
                </span>
            @endforeach
        </div>
    @endif
</div>

{{-- Анализ лога 404 --}}
<div class="admin-card">
    <h2 class="admin-card-title">Кандидаты из лога 404</h2>
    <p class="admin-card-desc">Укажите период и нажмите «Анализ». Будут показаны уникальные пути из логов; можно отфильтровать только запросы от ботов. Пути с расширением .php (брутфорс) в кандидаты не попадают и в список 410 не добавляются.</p>
    <form method="GET" action="{{ route('admin.security.gone') }}" class="admin-form" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem;">
        <input type="hidden" name="analyze" value="1">
        <label for="date_from" class="admin-form-label" style="margin: 0;">Дата с</label>
        <input type="date" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}" class="admin-input">
        <label for="date_to" class="admin-form-label" style="margin: 0;">Дата по</label>
        <input type="date" id="date_to" name="date_to" value="{{ $dateTo ?? '' }}" class="admin-input">
        <span class="admin-form-label" style="margin: 0 0 0 0.5rem;">Показать:</span>
        <label class="checkbox-label" style="margin: 0;">
            <input type="radio" name="filter_bot" value="all" {{ ($filterBot ?? 'all') === 'all' ? 'checked' : '' }}> все
        </label>
        <label class="checkbox-label" style="margin: 0;">
            <input type="radio" name="filter_bot" value="bots" {{ ($filterBot ?? '') === 'bots' ? 'checked' : '' }}> только боты
        </label>
        <button type="submit" class="admin-btn admin-btn-primary" style="margin-left: 0.25rem;">Анализ</button>
    </form>

    @if(!empty($excludedPaths) && $excludedPaths->isNotEmpty())
        <h3 class="admin-card-title" style="margin-top: 1.5rem;">Скрыты из кандидатов</h3>
        <p class="admin-card-desc">Эти пути не показываются в таблице кандидатов. Нажмите ×, чтобы снова показывать при анализе.</p>
        <div class="gone-list">
            @foreach($excludedPaths as $row)
                <span class="gone-chip">
                    <span class="gone-chip-text" title="{{ e($row->path) }}">{{ e($row->path) }}</span>
                    <form method="POST" action="{{ route('admin.security.gone.exclude.destroy', $row->id) }}" class="gone-chip-form" onsubmit="return confirm('Снова показывать в кандидатах?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="gone-chip-remove" aria-label="Показать в кандидатах">×</button>
                    </form>
                </span>
            @endforeach
        </div>
    @endif

    @if(!empty($candidates))
        <h3 class="admin-card-title" style="margin-top: 1.5rem;">Кандидаты (уже в списке 410 и пути с существующим контентом исключены)</h3>
        <form method="POST" action="{{ route('admin.security.gone') }}" class="admin-form" id="gone-candidates-form" data-gone-hide-action="{{ route('admin.security.gone', ['analyze' => '1', 'date_from' => $dateFrom ?? '', 'date_to' => $dateTo ?? '', 'filter_bot' => $filterBot ?? 'all']) }}">
            @csrf
            <div class="gone-candidates-wrap">
                <table class="gone-candidates-table admin-table">
                    <thead>
                        <tr>
                            <th class="gone-th-check">
                                <label class="gone-check-label" title="Выбрать все">
                                    <input type="checkbox" id="gone-select-all" aria-label="Выбрать все">
                                </label>
                            </th>
                            <th>Путь</th>
                            <th>Запросов</th>
                            <th>Бот</th>
                            <th>Контент есть</th>
                            <th>Скрыть</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $c)
                            <tr class="{{ $c['content_exists'] ? 'gone-row-no-add' : '' }}">
                                <td class="gone-td-check">
                                    @if(!$c['content_exists'])
                                        <label class="gone-check-label">
                                            <input type="checkbox" name="paths[]" value="{{ e($c['path']) }}" class="gone-path-check">
                                        </label>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><code>{{ e($c['path']) }}</code></td>
                                <td>{{ $c['count'] }}</td>
                                <td>{{ $c['has_bot'] ? 'да' : '—' }}</td>
                                <td>{{ $c['content_exists'] ? 'да (не добавлять)' : 'нет' }}</td>
                                <td class="gone-td-action">
                                    <button type="button" class="gone-hide-btn" title="Скрыть из кандидатов" data-path="{{ e($c['path']) }}">Скрыть</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @php $canAdd = collect($candidates)->contains(fn($c) => !$c['content_exists']); @endphp
            @if($canAdd)
                <div class="admin-form-actions" style="margin-top: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">Добавить выбранные в список 410</button>
                </div>
            @else
                <p class="admin-card-desc">Нет кандидатов без контента для добавления (все уже в списке или по пути есть стих/автор).</p>
            @endif
        </form>
        <script>
        (function() {
            var form = document.getElementById('gone-candidates-form');
            if (!form) return;
            var selectAll = form.querySelector('#gone-select-all');
            var checks = form.querySelectorAll('.gone-path-check');
            if (selectAll && checks.length) {
                selectAll.addEventListener('change', function() {
                    checks.forEach(function(cb) { cb.checked = selectAll.checked; });
                });
                form.addEventListener('change', function() {
                    var checked = form.querySelectorAll('.gone-path-check:checked').length;
                    selectAll.checked = checked === checks.length;
                    selectAll.indeterminate = checked > 0 && checked < checks.length;
                });
            }
            // Кнопки «Скрыть» — отдельная отправка, без вложенной формы
            var hideAction = form.getAttribute('data-gone-hide-action');
            var token = form.querySelector('input[name="_token"]');
            if (hideAction && token) {
                form.querySelectorAll('.gone-hide-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var path = this.getAttribute('data-path');
                        if (!path || !confirm('Скрыть путь из кандидатов?')) return;
                        var f = document.createElement('form');
                        f.method = 'POST';
                        f.action = hideAction;
                        var csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = token.value;
                        f.appendChild(csrf);
                        var inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'exclude_path';
                        inp.value = path;
                        f.appendChild(inp);
                        document.body.appendChild(f);
                        f.submit();
                    });
                });
            }
        })();
        </script>
    @endif
</div>
@endsection
