@extends('admin.layouts.app')

@section('title', 'SEO')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>SEO</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>SEO</h1>
</div>

{{-- SEO-шаблоны: каждый тип в спойлере --}}
<div class="admin-card">
    <h2 class="admin-card-title">SEO-шаблоны</h2>
    <p class="admin-card-desc">Главная — meta и заголовки для главной (/). Остальные — для страниц, авторов и стихов без своих meta. Подстановки: <code>{title}</code>, <code>{name}</code>, <code>{author}</code>.</p>
    <form method="POST" action="{{ route('admin.seo.templates.update') }}" class="admin-form">
        @csrf
        @foreach(['home' => 'Главная (/)', 'page' => 'Страницы', 'author' => 'Авторы', 'poem' => 'Стихи'] as $type => $label)
            @php $t = $templates->get($type); @endphp
            <details class="admin-spoiler" {{ old('_spoiler') === $type ? 'open' : '' }}>
                <summary>{{ $label }}</summary>
                <div class="admin-spoiler-body">
                    <div class="admin-form-group">
                        <label for="tpl_meta_title_{{ $type }}">Meta title</label>
                        <input type="text" id="tpl_meta_title_{{ $type }}" name="templates[{{ $type }}][meta_title]" value="{{ old("templates.{$type}.meta_title", $t?->meta_title) }}" placeholder="{{ $type === 'home' ? 'Стихотворения поэтов классиков' : ($type === 'poem' ? '{title} — {author}' : ($type === 'author' ? '{name} — стихи' : '{title}')) }}">
                    </div>
                    <div class="admin-form-group">
                        <label for="tpl_meta_desc_{{ $type }}">Meta description</label>
                        <input type="text" id="tpl_meta_desc_{{ $type }}" name="templates[{{ $type }}][meta_description]" value="{{ old("templates.{$type}.meta_description", $t?->meta_description) }}" placeholder="Краткое описание">
                    </div>
                    <div class="admin-form-group">
                        <label for="tpl_h1_{{ $type }}">H1</label>
                        <input type="text" id="tpl_h1_{{ $type }}" name="templates[{{ $type }}][h1]" value="{{ old("templates.{$type}.h1", $t?->h1) }}" placeholder="{{ $type === 'home' ? 'Стихотворения поэтов классиков' : ($type === 'author' ? '{name}' : ($type === 'poem' ? '{title}' : '{title}')) }}">
                    </div>
                    <div class="admin-form-group">
                        <label for="tpl_h1_desc_{{ $type }}">Описание под H1</label>
                        <input type="text" id="tpl_h1_desc_{{ $type }}" name="templates[{{ $type }}][h1_description]" value="{{ old("templates.{$type}.h1_description", $t?->h1_description) }}" placeholder="Текст под заголовком">
                    </div>
                </div>
            </details>
        @endforeach
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить шаблоны</button>
        </div>
    </form>
</div>

{{-- SEO-страницы — под спойлером --}}
<div class="admin-card">
    <details class="admin-spoiler">
        <summary>SEO-страницы</summary>
        <div class="admin-spoiler-body">
            <p class="admin-card-desc" style="margin-top: 0;">Мета для URL, которые не являются страницей, автором или стихом. Путь — часть URL без слэшей (например <code>privacy</code> для /privacy/).</p>
            <form method="POST" action="{{ route('admin.seo.pages.store') }}" class="admin-form admin-form-inline">
                @csrf
                <div class="admin-form-group">
                    <label for="new_path">Путь (slug)</label>
                    <input type="text" id="new_path" name="path" value="{{ old('path') }}" placeholder="privacy" pattern="[a-z0-9\-]+" required>
                    @error('path')<p class="admin-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="admin-form-group">
                    <label for="new_meta_title">Meta title</label>
                    <input type="text" id="new_meta_title" name="meta_title" value="{{ old('meta_title') }}">
                </div>
                <div class="admin-form-group">
                    <label for="new_meta_description">Meta description</label>
                    <input type="text" id="new_meta_description" name="meta_description" value="{{ old('meta_description') }}">
                </div>
                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">Добавить</button>
                </div>
            </form>
            <div class="admin-table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Путь</th><th>Meta title</th><th>Meta description</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($seoPages as $sp)
                            <tr>
                                <td><a href="{{ url('/' . $sp->path . '/') }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ $sp->path }}</code></a></td>
                                <td>{{ Str::limit($sp->meta_title, 40) }}</td>
                                <td>{{ Str::limit($sp->meta_description, 50) }}</td>
                                <td class="admin-cell-actions">
                                    <button type="button" class="admin-btn-link" onclick="editSeoPage({{ json_encode($sp->only(['id','path','meta_title','meta_description'])) }})">Изменить</button>
                                    <form action="{{ route('admin.seo.pages.destroy', $sp) }}" method="POST" class="inline" onsubmit="return confirm('Удалить?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-delete">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Нет записей.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $seoPages->links() }}</div>
        </div>
    </details>
</div>

{{-- Модальное редактирование SEO-страницы --}}
<div id="seo-page-edit-modal" class="admin-modal" style="display: none;">
    <div class="admin-modal-backdrop" onclick="closeSeoModal()"></div>
    <div class="admin-modal-content">
        <h3>Изменить SEO-страницу</h3>
        <form id="seo-page-edit-form" method="POST" class="admin-form">
            @csrf
            @method('PUT')
            <div class="admin-form-group">
                <label for="edit_path">Путь (slug)</label>
                <input type="text" id="edit_path" name="path" required pattern="[a-z0-9\-]+">
            </div>
            <div class="admin-form-group">
                <label for="edit_meta_title">Meta title</label>
                <input type="text" id="edit_meta_title" name="meta_title">
            </div>
            <div class="admin-form-group">
                <label for="edit_meta_description">Meta description</label>
                <input type="text" id="edit_meta_description" name="meta_description">
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeSeoModal()">Отмена</button>
            </div>
        </form>
    </div>
</div>

{{-- Sitemap — кнопки в ряд, одного размера --}}
<div class="admin-card">
    <h2 class="admin-card-title">Sitemap</h2>
    <p class="admin-card-desc">Кеш sitemap.xml. После обновления дата изменится.</p>
    <p><strong>Последнее обновление кеша:</strong>
        @if($sitemapUpdatedAt)
            {{ \Carbon\Carbon::parse($sitemapUpdatedAt)->setTimezone(config('app.timezone'))->format('d.m.Y H:i') }}
        @else
            ещё не создавался (будет создан при первом запросе sitemap.xml или по кнопке ниже)
        @endif
    </p>
    <div class="admin-sitemap-actions">
        <form method="POST" action="{{ route('admin.seo.sitemap.refresh') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="admin-btn admin-btn-primary">Обновить sitemap</button>
        </form>
        <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">Открыть sitemap.xml</a>
    </div>
</div>

<script>
function editSeoPage(data) {
    document.getElementById('seo-page-edit-form').action = '{{ url('admin/seo/pages') }}/' + data.id;
    document.getElementById('edit_path').value = data.path || '';
    document.getElementById('edit_meta_title').value = data.meta_title || '';
    document.getElementById('edit_meta_description').value = data.meta_description || '';
    document.getElementById('seo-page-edit-modal').style.display = 'flex';
}
function closeSeoModal() {
    document.getElementById('seo-page-edit-modal').style.display = 'none';
}
</script>
@endsection
