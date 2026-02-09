@extends('admin.layouts.app')

@section('title', 'Авторы')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Авторы</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Авторы</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.authors.create') }}" class="admin-btn admin-btn-primary">Добавить автора</a>
    </div>
</div>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'slug', 'dir' => ($sort === 'slug' && $dir === 'asc' ? 'desc' : 'asc'), 'page' => null]) }}" class="admin-th-sort {{ $sort === 'slug' ? 'admin-th-sort--active' : '' }}">Slug</a></th>
                    <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => ($sort === 'name' && $dir === 'asc' ? 'desc' : 'asc'), 'page' => null]) }}" class="admin-th-sort {{ $sort === 'name' ? 'admin-th-sort--active' : '' }}">Имя</a></th>
                    <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'years_of_life', 'dir' => ($sort === 'years_of_life' && $dir === 'asc' ? 'desc' : 'asc'), 'page' => null]) }}" class="admin-th-sort {{ $sort === 'years_of_life' ? 'admin-th-sort--active' : '' }}">Годы жизни</a></th>
                    <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'sort_order', 'dir' => ($sort === 'sort_order' && $dir === 'asc' ? 'desc' : 'asc'), 'page' => null]) }}" class="admin-th-sort {{ $sort === 'sort_order' ? 'admin-th-sort--active' : '' }}">Порядок</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($authors as $author)
                    <tr>
                        <td><a href="{{ url('/' . $author->slug . '/') }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ $author->slug }}</code></a></td>
                        <td>{{ $author->name }}</td>
                        <td>{{ $author->years_of_life ?? '—' }}</td>
                        <td>{{ $author->sort_order }}</td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.authors.edit', $author) }}" class="admin-btn-link">Изменить</a>
                            <form action="{{ route('admin.authors.destroy', $author) }}" method="POST" class="inline" onsubmit="return confirm('Удалить автора?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn-delete">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $authors->links() }}</div>
</div>
@endsection
