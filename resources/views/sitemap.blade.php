<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($authors ?? [] as $author)
    <url>
        <loc>{{ url($author->slug) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach($poems ?? [] as $poem)
    <url>
        <loc>{{ url($poem->slug) }}</loc>
        <lastmod>{{ $poem->updated_at?->toW3cString() ?? $poem->published_at?->toW3cString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
@endforeach
@foreach($pages ?? [] as $page)
    <url>
        <loc>{{ url($page->slug) }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
@endforeach
</urlset>
