@extends('admin.layouts.app')

@section('title', 'Стихи')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Стихи</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Стихи</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.poems.create') }}" class="admin-btn admin-btn-primary">Добавить стих</a>
    </div>
</div>
<form method="GET" class="admin-filter-bar">
    <div class="admin-filter-row">
        <input type="hidden" name="sort" value="{{ request('sort', 'updated_at') }}">
        <input type="hidden" name="order" value="{{ request('order', 'desc') }}">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск по названию, slug или тексту">
        <select name="author_id">
            <option value="">Все авторы</option>
            @foreach($authors as $a)
                <option value="{{ $a->id }}" @selected(request('author_id') == $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        <select name="tag_id">
            <option value="">Все теги</option>
            @foreach($tags as $t)
                <option value="{{ $t->id }}" @selected(request('tag_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <select name="song_status" aria-label="Статус песни">
            <option value="">Песня: все</option>
            @foreach(\App\Models\Poem::songStatusOptions() as $value => $label)
                <option value="{{ $value }}" @selected(request('song_status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="admin-filter-row">
        <label class="admin-filter-label">Длина (знак.):</label>
        <input type="number" name="length_from" value="{{ request('length_from') }}" placeholder="от" min="0" step="1" class="admin-filter-number">
        <input type="number" name="length_to" value="{{ request('length_to') }}" placeholder="до" min="0" step="1" class="admin-filter-number">
        <button type="submit" class="admin-btn admin-btn-secondary">Применить</button>
    </div>
</form>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'title', 'label' => 'Название'])</th>
                    <th>Автор</th>
                    <th>Slug</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'likes', 'label' => 'Лайки'])</th>
                    <th>Песня</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'body_length', 'label' => 'Длина'])</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'updated_at', 'label' => 'Обновлён'])</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($poems as $poem)
                    <tr>
                        <td>{{ Str::limit($poem->title, 50) }}</td>
                        <td>{{ $poem->author->name }}</td>
                        <td><a href="{{ url('/' . $poem->slug) }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ Str::limit($poem->slug, 30) }}</code></a></td>
                        <td>{{ (int) ($poem->likes ?? 0) }}</td>
                        <td>
                            @php($currentSongStatus = $poem->song_status ?? \App\Models\Poem::SONG_STATUS_NONE)
                            <form action="{{ route('admin.poems.song-status', $poem) }}" method="POST" class="admin-song-status-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="song_status" value="{{ $currentSongStatus }}">
                                <button
                                    type="button"
                                    class="admin-song-status-current"
                                    data-song-status-open
                                    data-current-status="{{ $currentSongStatus }}"
                                    title="Сменить статус песни"
                                    aria-label="Сменить статус песни"
                                >
                                    <span class="admin-song-dot is-{{ $currentSongStatus }}"></span>
                                </button>
                            </form>
                        </td>
                        <td>{{ number_format($poem->body_length ?? 0, 0, ',', ' ') }}</td>
                        <td>{{ $poem->updated_at?->format('d.m.Y H:i') }}</td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.poems.edit', $poem) }}" class="admin-icon-btn" title="Изменить" aria-label="Изменить">
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </a>
                            <form action="{{ route('admin.poems.destroy', $poem) }}" method="POST" class="inline" onsubmit="return confirm('Удалить стих?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-icon-btn admin-icon-btn-danger" title="Удалить" aria-label="Удалить">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4h8v2"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
                                        <path d="M10 11v6"/>
                                        <path d="M14 11v6"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $poems->links() }}</div>
</div>
<div class="admin-song-modal" id="song-status-modal" hidden>
    <div class="admin-song-modal-backdrop" data-song-status-close></div>
    <div class="admin-song-modal-card">
        <h3>Статус “Песня”</h3>
        <div class="admin-song-modal-options">
            <button type="button" class="admin-song-modal-btn" data-status-value="{{ \App\Models\Poem::SONG_STATUS_NONE }}" title="Нет">
                <span class="admin-song-dot is-{{ \App\Models\Poem::SONG_STATUS_NONE }}"></span>
                <span>Нет</span>
            </button>
            <button type="button" class="admin-song-modal-btn" data-status-value="{{ \App\Models\Poem::SONG_STATUS_HAS }}" title="Есть">
                <span class="admin-song-dot is-{{ \App\Models\Poem::SONG_STATUS_HAS }}"></span>
                <span>Есть</span>
            </button>
            <button type="button" class="admin-song-modal-btn" data-status-value="{{ \App\Models\Poem::SONG_STATUS_SELECTED }}" title="Выбран">
                <span class="admin-song-dot is-{{ \App\Models\Poem::SONG_STATUS_SELECTED }}"></span>
                <span>Выбран</span>
            </button>
            <button type="button" class="admin-song-modal-btn" data-status-value="{{ \App\Models\Poem::SONG_STATUS_NOT_SUITABLE }}" title="Не подходит">
                <span class="admin-song-dot is-{{ \App\Models\Poem::SONG_STATUS_NOT_SUITABLE }}"></span>
                <span>Не подходит</span>
            </button>
        </div>
        <div class="admin-song-modal-actions">
            <button type="button" class="admin-btn admin-btn-secondary" data-song-status-close>Отмена</button>
            <button type="button" class="admin-btn admin-btn-primary" id="song-status-confirm">Подтвердить</button>
        </div>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('song-status-modal');
    if (!modal) return;
    let activeForm = null;
    let selectedStatus = null;

    const optionButtons = Array.from(modal.querySelectorAll('[data-status-value]'));
    const openButtons = Array.from(document.querySelectorAll('[data-song-status-open]'));
    const closeButtons = Array.from(document.querySelectorAll('[data-song-status-close]'));
    const confirmBtn = document.getElementById('song-status-confirm');

    const setSelected = (status) => {
        selectedStatus = status;
        optionButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.statusValue === status);
        });
    };

    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            activeForm = btn.closest('form');
            setSelected(btn.dataset.currentStatus || '{{ \App\Models\Poem::SONG_STATUS_NONE }}');
            modal.hidden = false;
        });
    });

    optionButtons.forEach((btn) => {
        btn.addEventListener('click', () => setSelected(btn.dataset.statusValue));
    });

    closeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.hidden = true;
            activeForm = null;
            selectedStatus = null;
        });
    });

    confirmBtn?.addEventListener('click', () => {
        if (!activeForm || !selectedStatus) return;
        const input = activeForm.querySelector('input[name="song_status"]');
        if (!input) return;
        input.value = selectedStatus;
        activeForm.submit();
    });
})();
</script>
@endsection
