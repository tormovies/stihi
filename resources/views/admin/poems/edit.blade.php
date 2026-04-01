@extends('admin.layouts.app')

@section('title', 'Изменить стих')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.poems.index') }}">Стихи</a><span>›</span><span>{{ Str::limit($poem->title, 40) }}</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Изменить стих</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.poems.index') }}" class="admin-btn admin-btn-secondary">← К списку</a>
    </div>
</div>
<div class="admin-card">
    <form method="POST" action="{{ route('admin.poems.update', $poem) }}" class="admin-form">
        @csrf
        @method('PUT')
        <div class="admin-form-group">
            <label for="author_id">Автор</label>
            <select id="author_id" name="author_id" required>
                @foreach($authors as $a)
                    <option value="{{ $a->id }}" @selected(old('author_id', $poem->author_id) == $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-form-group">
            <label for="slug">Slug <span class="optional">(URL)</span></label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $poem->slug) }}" required>
            @error('slug')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="title">Название</label>
            <input type="text" id="title" name="title" value="{{ old('title', $poem->title) }}" required>
            @error('title')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <div class="admin-form-group">
            <label for="body">Текст стихотворения</label>
            <textarea id="body" name="body" rows="15" class="w-full">{{ old('body', $poem->body) }}</textarea>
        </div>
        <div class="admin-form-group">
            <label for="song_url">URL песни <span class="optional">(необязательно)</span></label>
            <input type="url" id="song_url" name="song_url" value="{{ old('song_url', $poem->song_url) }}" placeholder="https://…" inputmode="url" autocomplete="off">
            @error('song_url')<p class="admin-form-error">{{ $message }}</p>@enderror
        </div>
        <details class="admin-spoiler">
            <summary>SEO (для этого стиха)</summary>
            <div class="admin-spoiler-body">
                <p class="admin-card-desc" style="margin-top: 0;">Подстановки: <code>{title}</code>, <code>{author}</code>. Пустые — используются общие шаблоны.</p>
                <div class="admin-form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $poem->meta_title) }}" placeholder="{title} — {author}">
                </div>
                <div class="admin-form-group">
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description', $poem->meta_description) }}">
                </div>
                <div class="admin-form-group">
                    <label for="h1">H1</label>
                    <input type="text" id="h1" name="h1" value="{{ old('h1', $poem->h1) }}" placeholder="{title}">
                </div>
                <div class="admin-form-group">
                    <label for="h1_description">Описание под H1</label>
                    <input type="text" id="h1_description" name="h1_description" value="{{ old('h1_description', $poem->h1_description) }}">
                </div>
            </div>
        </details>
        <div class="admin-form-group">
            <label for="published_at">Дата публикации</label>
            <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at', $poem->published_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="admin-form-group">
            <label>Теги (темы)</label>
            <p class="admin-form-hint" style="margin-top: 0;">Чипсы — выбранные теги. Клик по полю или по стрелке — список; ввод текста — поиск по подстроке в названии.</p>
            <div class="admin-tags-field">
                <div class="admin-tags-row">
                    <div class="admin-tags-chips" id="poem-tags-chips">
                        @php $selectedIds = old('tag_ids', $poem->tags->pluck('id')->all()); @endphp
                        @foreach($allTags as $tag)
                            @if(in_array($tag->id, $selectedIds))
                                <span class="admin-tag-chip" data-tag-id="{{ $tag->id }}">
                                    {{ $tag->name }}
                                    <button type="button" class="admin-tag-chip-remove" aria-label="Удалить тег">×</button>
                                </span>
                                <input type="hidden" name="tag_ids[]" value="{{ $tag->id }}">
                            @endif
                        @endforeach
                    </div>
                    <div class="admin-tags-input-wrap">
                        <input type="text" class="admin-tags-input" id="poem-tags-input" placeholder="Добавить тег…" autocomplete="off">
                        <button type="button" class="admin-tags-btn admin-tags-btn-dropdown" id="poem-tags-btn-dropdown" aria-label="Выбрать из списка">▼</button>
                        <button type="button" class="admin-tags-btn admin-tags-btn-plus" id="poem-tags-btn-plus" aria-label="Добавить тег">+</button>
                        <div class="admin-tags-dropdown" id="poem-tags-dropdown" hidden>
                            @foreach($allTags as $tag)
                                <button type="button" class="admin-tags-dropdown-item" data-tag-id="{{ $tag->id }}" data-tag-name="{{ e($tag->name) }}">{{ $tag->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
            <a href="{{ route('admin.poems.index') }}" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>
    </form>
</div>
<script>
(function() {
  var wrap = document.getElementById('poem-tags-chips');
  var input = document.getElementById('poem-tags-input');
  var dropdown = document.getElementById('poem-tags-dropdown');
  if (!wrap || !input || !dropdown) return;

  function getSelectedIds() {
    return Array.from(wrap.querySelectorAll('input[name="tag_ids[]"]')).map(function(inp) { return parseInt(inp.value, 10); });
  }

  function addChip(tagId, tagName) {
    var chip = document.createElement('span');
    chip.className = 'admin-tag-chip';
    chip.setAttribute('data-tag-id', tagId);
    chip.textContent = tagName + ' ';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'admin-tag-chip-remove';
    btn.setAttribute('aria-label', 'Удалить тег');
    btn.textContent = '×';
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      chip.nextElementSibling && chip.nextElementSibling.remove();
      chip.remove();
    });
    chip.appendChild(btn);
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'tag_ids[]';
    hidden.value = tagId;
    wrap.appendChild(chip);
    wrap.appendChild(hidden);
  }

  function filterDropdown() {
    var query = (input.value || '').trim().toLowerCase();
    var selected = getSelectedIds();
    dropdown.querySelectorAll('.admin-tags-dropdown-item').forEach(function(item) {
      var id = parseInt(item.getAttribute('data-tag-id'), 10);
      var name = (item.getAttribute('data-tag-name') || item.textContent || '').toLowerCase();
      var alreadySelected = selected.indexOf(id) !== -1;
      var matches = !query || name.indexOf(query) !== -1;
      item.classList.toggle('is-added', alreadySelected);
      item.classList.toggle('is-hidden', alreadySelected || !matches);
    });
  }

  function showDropdown() {
    dropdown.hidden = false;
    filterDropdown();
  }

  function hideDropdown() {
    dropdown.hidden = true;
  }

  var btnDropdown = document.getElementById('poem-tags-btn-dropdown');
  var btnPlus = document.getElementById('poem-tags-btn-plus');
  var inputWrap = input.closest('.admin-tags-input-wrap');

  input.addEventListener('focus', showDropdown);
  input.addEventListener('input', showDropdown);
  input.addEventListener('blur', function() {
    setTimeout(hideDropdown, 180);
  });
  if (btnDropdown) {
    btnDropdown.addEventListener('click', function(e) {
      e.preventDefault();
      if (dropdown.hidden) { showDropdown(); input.focus(); } else { hideDropdown(); }
    });
  }
  if (btnPlus) {
    btnPlus.addEventListener('click', function(e) {
      e.preventDefault();
      input.focus();
      showDropdown();
    });
  }

  dropdown.querySelectorAll('.admin-tags-dropdown-item').forEach(function(item) {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      var id = parseInt(item.getAttribute('data-tag-id'), 10);
      var name = item.getAttribute('data-tag-name') || item.textContent;
      if (getSelectedIds().indexOf(id) !== -1) return;
      addChip(id, name);
      input.value = '';
      filterDropdown();
    });
  });

  wrap.querySelectorAll('.admin-tag-chip-remove').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var chip = btn.closest('.admin-tag-chip');
      if (chip && chip.nextElementSibling && chip.nextElementSibling.name === 'tag_ids[]') {
        chip.nextElementSibling.remove();
      }
      chip && chip.remove();
    });
  });

  document.addEventListener('click', function(e) {
    if (dropdown.hidden) return;
    var field = input.closest('.admin-tags-field');
    if (!field.contains(e.target) && !dropdown.contains(e.target)) {
      hideDropdown();
    }
  });
})();
</script>
@endsection
