@extends('admin.layouts.app')

@section('title', 'Анализы стихов')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Анализы стихов</span>
@endsection

@section('content')
@if(session('message'))
    <p class="admin-message">{{ session('message') }}</p>
@endif
<div class="admin-toolbar">
    <h1>Анализы стихов</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.deepseek.index') }}" class="admin-btn admin-btn-secondary">DeepSeek (запуск генерации)</a>
    </div>
</div>
<form method="get" action="{{ route('admin.poem-analyses.index') }}" class="admin-filter-bar">
    <label for="poem-analyses-q" class="admin-filter-label">Поиск по поэту, стиху, анализу</label>
    <input type="search" id="poem-analyses-q" name="q" value="{{ old('q', $q ?? '') }}" placeholder="поэт, стих, анализ…">
    <input type="hidden" name="sort" value="{{ $sort ?? 'updated_at' }}">
    <input type="hidden" name="order" value="{{ $order ?? 'desc' }}">
    <button type="submit" class="admin-btn admin-btn-secondary">Искать</button>
</form>
<form action="{{ route('admin.poem-analyses.destroy') }}" method="post" id="poem-analyses-form">
    @csrf
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="admin-col-checkbox"><input type="checkbox" id="select-all-analyses" aria-label="Выбрать все"></th>
                    @php
                        $currentSort = $sort ?? 'updated_at';
                        $currentOrder = $order ?? 'desc';
                        $queryParams = ['q' => $q ?? ''];
                        $thSort = function ($column, $label) use ($currentSort, $currentOrder, $queryParams) {
                            $isActive = ($currentSort === $column);
                            if ($column === 'updated_at') {
                                $nextOrder = ($isActive && $currentOrder === 'desc') ? 'asc' : 'desc';
                            } else {
                                $nextOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
                            }
                            $url = route('admin.poem-analyses.index', array_merge($queryParams, ['sort' => $column, 'order' => $nextOrder]));
                            $arrow = $isActive ? ($currentOrder === 'asc' ? ' ↑' : ' ↓') : '';
                            return '<a href="' . e($url) . '" class="admin-th-sort">' . e($label) . $arrow . '</a>';
                        };
                    @endphp
                    <th>{!! $thSort('poem', 'Стих') !!}</th>
                    <th>{!! $thSort('author', 'Автор') !!}</th>
                    <th>{!! $thSort('updated_at', 'Обновлён') !!}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($analyses as $a)
                    @php $poem = $a->poem; @endphp
                    <tr>
                        <td class="admin-col-checkbox"><input type="checkbox" name="ids[]" value="{{ $a->id }}" class="analysis-row-cb"></td>
                        <td>
                            @if($poem)
                                <a href="{{ url('/' . $poem->slug . '/') }}" target="_blank" rel="noopener">{{ Str::limit($poem->title, 50) }}</a>
                            @else
                                — (стих удалён)
                            @endif
                        </td>
                        <td>{{ $poem && $poem->author ? $poem->author->name : '—' }}</td>
                        <td>{{ $a->updated_at?->format('d.m.Y H:i') }}</td>
                        <td class="admin-cell-actions">
                            @if($poem)
                                <a href="{{ url('/' . $poem->slug . '/analiz/') }}" target="_blank" rel="noopener" class="admin-btn-link">Анализ на сайте</a>
                            @endif
                            <button type="submit" form="poem-analyses-form" formaction="{{ route('admin.poem-analyses.destroy') }}" class="admin-btn-link admin-btn-danger" onclick="return setSingleIdAndConfirm({{ $a->id }}, this.form);" title="Удалить этот анализ">Удалить</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="admin-card-footer">
        <button type="submit" class="admin-btn admin-btn-danger" onclick="return document.querySelectorAll('.analysis-row-cb:checked').length ? confirm('Удалить выбранные анализы?') : (alert('Выберите анализы для удаления.'), false);">Удалить выбранные</button>
    </div>
    <div class="pagination-wrap">{{ $analyses->links() }}</div>
</div>
</form>
<script>
document.getElementById('select-all-analyses')?.addEventListener('change', function() {
    document.querySelectorAll('.analysis-row-cb').forEach(function(cb) { cb.checked = this.checked; }, this);
});
function setSingleIdAndConfirm(id, form) {
    form.querySelectorAll('.analysis-row-cb').forEach(function(cb) { cb.checked = false; });
    var one = form.querySelector('input.analysis-row-cb[value="' + id + '"]');
    if (one) one.checked = true;
    return confirm('Удалить этот анализ?');
}
</script>
@endsection
