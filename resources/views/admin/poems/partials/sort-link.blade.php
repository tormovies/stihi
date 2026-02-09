@php
    $isActive = ($sort ?? 'updated_at') === $key;
    $currentOrder = $order ?? 'desc';
    $nextOrder = $isActive && $currentOrder === 'desc' ? 'asc' : 'desc';
    $query = array_merge(request()->query(), ['sort' => $key, 'order' => $nextOrder]);
    $href = request()->url() . '?' . http_build_query($query);
@endphp
<a href="{{ $href }}" class="admin-th-sort {{ $isActive ? 'admin-th-sort--active' : '' }}">
    {{ $label }}
    @if($isActive)
        <span aria-hidden="true">{{ $currentOrder === 'desc' ? ' ↓' : ' ↑' }}</span>
    @endif
</a>
