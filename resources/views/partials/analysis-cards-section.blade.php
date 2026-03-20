{{-- Ожидает: $analyses (коллекция PoemAnalysis с poem.author), $title (строка), $headingId, опционально $sectionClass --}}
@if(isset($analyses) && $analyses->isNotEmpty())
<section class="home-analyses {{ $sectionClass ?? '' }}" aria-labelledby="{{ $headingId }}">
    <h2 id="{{ $headingId }}" class="home-analyses__title">{{ $title }}</h2>
    <ul class="home-analyses-grid">
        @foreach($analyses as $analysis)
            @php $poem = $analysis->poem; @endphp
            @if($poem)
            <li class="home-analysis-card">
                <a href="{{ url($poem->slug . '/analiz') }}" class="home-analysis-card__link">
                    <span class="home-analysis-card__author">{{ e_decode($poem->author?->name ?? '') }}</span>
                    <span class="home-analysis-card__title">{{ e_decode(trim((string) ($analysis->h1 ?? '')) !== '' ? (string) $analysis->h1 : (string) $poem->title) }}</span>
                    @if($analysis->h1_description)
                    <p class="home-analysis-card__excerpt">{{ Str::limit(strip_tags($analysis->h1_description), 120) }}</p>
                    @endif
                </a>
            </li>
            @endif
        @endforeach
    </ul>
</section>
@endif
