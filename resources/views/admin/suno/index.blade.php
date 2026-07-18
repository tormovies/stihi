@extends('admin.layouts.app')

@section('title', 'Suno')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Suno</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Suno</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.deepseek.index') }}" class="admin-btn admin-btn-secondary">DeepSeek (cron / запуск)</a>
        <a href="{{ route('admin.deepseek.run.suno') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Запустить батч</a>
    </div>
</div>

@if($errors->has('suno'))
    <p class="alert alert-success" style="background:#fde8e8;color:#8a1f1f;">{{ $errors->first('suno') }}</p>
@endif

<form method="GET" class="admin-filter-bar">
    <div class="admin-filter-row">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="order" value="{{ $order }}">
        <select name="analysis" aria-label="Наличие анализа">
            <option value="with" @selected($analysisFilter === 'with')>С анализом</option>
            <option value="all" @selected($analysisFilter === 'all')>Все (400–2000)</option>
            <option value="without" @selected($analysisFilter === 'without')>Без анализа</option>
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск: автор, название, slug, стиль (Chanson…)">
        <select name="author_id">
            <option value="">Все авторы</option>
            @foreach($authors as $a)
                <option value="{{ $a->id }}" @selected(request('author_id') == $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        <select name="song_status" aria-label="Статус песни">
            <option value="">Песня: все</option>
            @foreach(\App\Models\Poem::songStatusOptions() as $value => $label)
                <option value="{{ $value }}" @selected(request('song_status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Статус: все</option>
            @foreach($statusOptions as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="admin-filter-row">
        <select name="male">
            <option value="">Male: все</option>
            @foreach($verdictOptions as $val => $label)
                <option value="{{ $val }}" @selected(request('male') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="folk">
            <option value="">Folk: все</option>
            @foreach($verdictOptions as $val => $label)
                <option value="{{ $val }}" @selected(request('folk') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="comfort">
            <option value="">Позитив: все</option>
            @foreach($verdictOptions as $val => $label)
                <option value="{{ $val }}" @selected(request('comfort') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="suitable">
            <option value="">Suno: все</option>
            <option value="1" @selected(request('suitable') === '1')>подходит</option>
            <option value="0" @selected(request('suitable') === '0')>не подходит</option>
        </select>
        <button type="submit" class="admin-btn admin-btn-secondary">Применить</button>
    </div>
</form>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Стих</th>
                    <th>Автор</th>
                    <th>Песня</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'score_total', 'label' => 'Балл'])</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'status', 'label' => 'Статус'])</th>
                    <th>Male</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'folk_fit', 'label' => 'Folk'])</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'comfort_fit', 'label' => 'Позитив'])</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'updated_at', 'label' => 'Обновлён'])</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($poems as $poem)
                    @php
                        $a = $poem->sunoAnalysis;
                        $currentSongStatus = $poem->song_status ?? \App\Models\Poem::SONG_STATUS_NONE;
                        $songStatusLabel = \App\Models\Poem::songStatusOptions()[$currentSongStatus] ?? $currentSongStatus;
                    @endphp
                    <tr @if($a) class="admin-suno-row" data-suno-open="{{ $poem->id }}" style="cursor:pointer;" @endif>
                        <td>
                            <a href="{{ url('/' . $poem->slug) }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">{{ Str::limit($poem->title, 45) }}</a>
                        </td>
                        <td>{{ $poem->author?->name }}</td>
                        <td onclick="event.stopPropagation()">
                            <form action="{{ route('admin.poems.song-status', $poem) }}" method="POST" class="admin-song-status-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="song_status" value="{{ $currentSongStatus }}">
                                <button
                                    type="button"
                                    class="admin-song-status-current"
                                    data-song-status-open
                                    data-current-status="{{ $currentSongStatus }}"
                                    title="{{ $songStatusLabel }}"
                                    aria-label="{{ $songStatusLabel }}. Нажмите, чтобы сменить"
                                >
                                    <span class="admin-song-dot is-{{ $currentSongStatus }}"></span>
                                </button>
                            </form>
                        </td>
                        <td>{{ $a?->score_total ?? '—' }}</td>
                        <td>{{ $a ? $a->statusLabel() : '—' }}</td>
                        <td onclick="event.stopPropagation()">
                            @if($a)
                                @php
                                    $maleVerdict = $a->male_verdict ?? 'maybe';
                                    $maleLabel = ($verdictOptions[$maleVerdict] ?? $maleVerdict) . ' (' . $a->male_fit . ')';
                                @endphp
                                <form action="{{ route('admin.suno.male', $a) }}" method="POST" class="admin-suno-male-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="male_verdict" value="{{ $maleVerdict }}">
                                    <button
                                        type="button"
                                        class="admin-suno-male-current"
                                        data-male-open
                                        data-current-male="{{ $maleVerdict }}"
                                        title="{{ $maleLabel }}. Нажмите, чтобы сменить"
                                        aria-label="Male: {{ $maleLabel }}. Нажмите, чтобы сменить"
                                    >
                                        <span class="admin-suno-male-dot is-{{ $maleVerdict }}"></span>
                                        <span>{{ $verdictOptions[$maleVerdict] ?? $maleVerdict }}</span>
                                        <span class="admin-suno-male-fit">({{ $a->male_fit }})</span>
                                    </button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $a ? (($verdictOptions[$a->folk_verdict] ?? $a->folk_verdict) . ' (' . $a->folk_fit . ')') : '—' }}</td>
                        <td>{{ $a ? (($verdictOptions[$a->comfort_verdict] ?? $a->comfort_verdict) . ' (' . $a->comfort_fit . ')') : '—' }}</td>
                        <td>{{ $a?->updated_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td class="admin-cell-actions" onclick="event.stopPropagation()">
                            @if($a)
                                <button type="button" class="admin-icon-btn" data-suno-open="{{ $poem->id }}" title="Карточка" aria-label="Карточка">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            @endif
                            <form action="{{ route('admin.suno.reanalyze', $poem) }}" method="POST" class="inline" onsubmit="return confirm('Переанализировать этот стих сейчас?');">
                                @csrf
                                <button type="submit" class="admin-icon-btn" title="Перезапуск" aria-label="Перезапуск">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 2v6h-6"/>
                                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                                        <path d="M3 22v-6h6"/>
                                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                                    </svg>
                                </button>
                            </form>
                            @if($a)
                                <form action="{{ route('admin.suno.destroy', $a) }}" method="POST" class="inline" onsubmit="return confirm('Удалить анализ? Стих вернётся в очередь.');">
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
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">Ничего не найдено.</td></tr>
                @endforelse
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
            @foreach(\App\Models\Poem::songStatusOptions() as $value => $label)
            <button type="button" class="admin-song-modal-btn" data-status-value="{{ $value }}" title="{{ $label }}">
                <span class="admin-song-dot is-{{ $value }}"></span>
                <span>{{ $label }}</span>
            </button>
            @endforeach
        </div>
        <div class="admin-song-modal-actions">
            <button type="button" class="admin-btn admin-btn-secondary" data-song-status-close>Отмена</button>
            <button type="button" class="admin-btn admin-btn-primary" id="song-status-confirm">Подтвердить</button>
        </div>
    </div>
</div>

<div class="admin-song-modal" id="male-verdict-modal" hidden>
    <div class="admin-song-modal-backdrop" data-male-close></div>
    <div class="admin-song-modal-card">
        <h3>Male</h3>
        <div class="admin-song-modal-options">
            @foreach($verdictOptions as $value => $label)
            <button type="button" class="admin-song-modal-btn" data-male-value="{{ $value }}" title="{{ $label }}">
                <span class="admin-suno-male-dot is-{{ $value }}"></span>
                <span>{{ $label }}</span>
            </button>
            @endforeach
        </div>
        <div class="admin-song-modal-actions">
            <button type="button" class="admin-btn admin-btn-secondary" data-male-close>Отмена</button>
            <button type="button" class="admin-btn admin-btn-primary" id="male-verdict-confirm">Подтвердить</button>
        </div>
    </div>
</div>

<div class="admin-suno-drawer" id="suno-drawer" hidden>
    <div class="admin-suno-drawer-backdrop" data-suno-close></div>
    <aside class="admin-suno-drawer-panel" aria-label="Карточка Suno">
        <div class="admin-suno-drawer-head">
            <h2 id="suno-drawer-title">Suno</h2>
            <button type="button" class="admin-btn admin-btn-secondary" data-suno-close>Закрыть</button>
        </div>
        <div class="admin-suno-drawer-body" id="suno-drawer-body">Загрузка…</div>
    </aside>
</div>

<script>
(() => {
    const drawer = document.getElementById('suno-drawer');
    const body = document.getElementById('suno-drawer-body');
    const title = document.getElementById('suno-drawer-title');
    if (!drawer) return;

    const flames = (n) => {
        n = Math.max(0, Math.min(5, Number(n) || 0));
        return n ? '🔥'.repeat(n) : '—';
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const copySvg = `<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;

    const copyText = async (text, btn) => {
        try {
            await navigator.clipboard.writeText(text || '');
            const prev = btn.getAttribute('title') || 'Копировать';
            btn.setAttribute('title', 'Скопировано');
            btn.setAttribute('aria-label', 'Скопировано');
            showCopyToast('Скопировано');
            setTimeout(() => {
                btn.setAttribute('title', prev);
                btn.setAttribute('aria-label', prev);
            }, 1200);
        } catch (e) {
            showCopyToast('Не удалось скопировать', true);
        }
    };

    const showCopyToast = (message, isError = false) => {
        let toast = document.getElementById('admin-suno-copy-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'admin-suno-copy-toast';
            toast.className = 'admin-suno-copy-toast';
            toast.setAttribute('role', 'status');
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.classList.add('is-visible');
        clearTimeout(showCopyToast._timer);
        showCopyToast._timer = setTimeout(() => toast.classList.remove('is-visible'), 1600);
    };

    const open = async (poemId) => {
        drawer.hidden = false;
        body.textContent = 'Загрузка…';
        title.textContent = 'Suno';
        try {
            const res = await fetch(`{{ url('/admin/suno') }}/${poemId}`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Ошибка загрузки');
            const data = await res.json();
            const a = data.analysis;
            const p = data.poem;
            title.textContent = p.title;
            const stylesRows = (a.styles || []).map((s, i) => `
                <tr>
                    <td>${esc(s.type === 'modern' ? 'Осовремененный' : 'Аутентичный')}</td>
                    <td>
                        <div class="admin-suno-style-cell">
                            <div>
                                <strong>${esc(s.label)}</strong>
                                <div class="admin-suno-prompt">${esc(s.suno_prompt)}</div>
                            </div>
                            <button type="button" class="admin-icon-btn" data-copy-style="${i}" title="Копировать стиль" aria-label="Копировать стиль">${copySvg}</button>
                        </div>
                    </td>
                    <td title="Массовость: ${esc(s.mass_appeal)}/5">${flames(s.mass_appeal)}</td>
                    <td title="Виральность: ${esc(s.virality)}/5">${flames(s.virality)}</td>
                    <td title="Нишевый культ: ${esc(s.niche_cult)}/5 — насколько текст силён в этом стиле для своей аудитории">${flames(s.niche_cult)}</td>
                    <td title="К тексту: ${esc(s.fit_to_text)}/5">${flames(s.fit_to_text)}</td>
                </tr>`).join('');
            body.innerHTML = `
                <p><a href="${esc(p.url)}" target="_blank" rel="noopener">${esc(p.author)} — ${esc(p.title)}</a> · ${esc(p.body_length)} знаков · ${esc(a.updated_at)}</p>
                <p><strong>${esc(a.status_label)}</strong> · балл ${esc(a.scores.total)}
                (крючок ${a.scores.hook}, ритм ${a.scores.rhythm}, динамика ${a.scores.dynamics}, сюжет ${a.scores.plot}, воздух ${a.scores.vocal_air})
                · для Suno: <strong>${a.suitable_for_suno ? 'да' : 'нет'}</strong></p>
                <p>Male: <strong>${esc(a.male.verdict)}</strong> (${a.male.fit}) — ${esc(a.male.why || '')}<br>
                Folk: <strong>${esc(a.folk.verdict)}</strong> (${a.folk.fit}) — ${esc(a.folk.why || '')}<br>
                Позитив: <strong>${esc(a.comfort.verdict)}</strong> (${a.comfort.fit}) — ${esc(a.comfort.why || '')}</p>
                ${a.structure_notes ? `<p><em>${esc(a.structure_notes)}</em></p>` : ''}
                ${(a.risks || []).length ? `<p>Риски: ${a.risks.map(esc).join('; ')}</p>` : ''}
                <h3>Разметка</h3>
                <div class="admin-suno-lyrics-wrap">
                    <button type="button" class="admin-icon-btn admin-suno-lyrics-copy" data-copy-lyrics title="Копировать разметку" aria-label="Копировать разметку">${copySvg}</button>
                    <pre class="admin-suno-lyrics" id="suno-marked-lyrics">${esc(a.marked_lyrics)}</pre>
                </div>
                <h3>Стили</h3>
                <p>Лучший overall: <strong>${esc(a.best_overall)}</strong> · viral: <strong>${esc(a.best_viral)}</strong> · cult: <strong>${esc(a.best_cult)}</strong></p>
                <div class="admin-table-wrap"><table class="table admin-suno-styles-table">
                    <thead><tr>
                        <th>Тип</th>
                        <th>Стиль</th>
                        <th title="Широкая популярность">Массовость</th>
                        <th title="Шанс разлететься">Виральность</th>
                        <th title="Потенциал нишевого культа / сила текста в этом стиле">Нишевый культ</th>
                        <th title="Насколько стиль ложится на текст">К тексту</th>
                    </tr></thead>
                    <tbody>${stylesRows}</tbody>
                </table></div>
            `;

            const lyricsBtn = body.querySelector('[data-copy-lyrics]');
            if (lyricsBtn) {
                lyricsBtn.addEventListener('click', () => copyText(a.marked_lyrics || '', lyricsBtn));
            }
            body.querySelectorAll('[data-copy-style]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = Number(btn.getAttribute('data-copy-style'));
                    const style = (a.styles || [])[idx];
                    copyText(style?.suno_prompt || '', btn);
                });
            });
        } catch (e) {
            body.textContent = e.message || 'Ошибка';
        }
    };

    document.querySelectorAll('[data-suno-open]').forEach((el) => {
        el.addEventListener('click', () => open(el.getAttribute('data-suno-open')));
    });
    drawer.querySelectorAll('[data-suno-close]').forEach((el) => {
        el.addEventListener('click', () => { drawer.hidden = true; });
    });
})();
</script>
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
<script>
(() => {
    const modal = document.getElementById('male-verdict-modal');
    if (!modal) return;
    let activeForm = null;
    let selectedVerdict = null;

    const optionButtons = Array.from(modal.querySelectorAll('[data-male-value]'));
    const openButtons = Array.from(document.querySelectorAll('[data-male-open]'));
    const closeButtons = Array.from(document.querySelectorAll('[data-male-close]'));
    const confirmBtn = document.getElementById('male-verdict-confirm');

    const setSelected = (verdict) => {
        selectedVerdict = verdict;
        optionButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.maleValue === verdict);
        });
    };

    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            activeForm = btn.closest('form');
            setSelected(btn.dataset.currentMale || 'maybe');
            modal.hidden = false;
        });
    });

    optionButtons.forEach((btn) => {
        btn.addEventListener('click', () => setSelected(btn.dataset.maleValue));
    });

    closeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.hidden = true;
            activeForm = null;
            selectedVerdict = null;
        });
    });

    confirmBtn?.addEventListener('click', () => {
        if (!activeForm || !selectedVerdict) return;
        const input = activeForm.querySelector('input[name="male_verdict"]');
        if (!input) return;
        input.value = selectedVerdict;
        activeForm.submit();
    });
})();
</script>
@endsection
