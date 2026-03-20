@extends('admin.layouts.app')

@section('title', '301 редиректы')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span>
    <a href="{{ route('admin.seo.index') }}">SEO</a><span>›</span><span>301 редиректы</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>301 редиректы</h1>
    <a href="{{ route('admin.seo.redirects.create') }}" class="admin-btn admin-btn-primary">Добавить</a>
</div>

@if(session('success'))
    <p class="alert alert-success">{{ session('success') }}</p>
@endif

<div class="admin-card">
    <p class="admin-card-desc">Пути в базе <strong>без</strong> слэшей по краям. Редирект срабатывает для GET/HEAD; целевой URL на сайте открывается со слэшем в конце (как в канонических ссылках).</p>
    <form method="GET" action="{{ route('admin.seo.redirects.index') }}" class="admin-redirects-search-form">
        <label for="q" class="admin-redirects-search-label">Поиск</label>
        <input type="text" id="q" name="q" value="{{ e($q) }}" placeholder="from или to" class="admin-input admin-redirects-search-input" autocomplete="off">
        <button type="submit" class="admin-btn admin-btn-secondary">Найти</button>
        @if($q !== '')
            <a href="{{ route('admin.seo.redirects.index') }}" class="admin-btn admin-btn-secondary">Сброс</a>
        @endif
    </form>

    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr><th>Откуда (from_path)</th><th>Куда (to_path)</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($redirects as $row)
                    <tr>
                        <td><code>{{ $row->from_path }}</code></td>
                        <td><code>{{ $row->to_path }}</code></td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.seo.redirects.edit', $row) }}" class="admin-btn-link">Изменить</a>
                            <form action="{{ route('admin.seo.redirects.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Удалить редирект?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn-delete">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Нет записей.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $redirects->links() }}</div>
</div>
@endsection
