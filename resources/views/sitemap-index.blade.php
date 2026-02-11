{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sitemapUrls as $sitemapUrl)
    <sitemap>
        <loc>{{ $sitemapUrl['loc'] }}</loc>
        @if(!empty($sitemapUrl['lastmod']))
        <lastmod>{{ $sitemapUrl['lastmod'] }}</lastmod>
        @endif
    </sitemap>
@endforeach
</sitemapindex>
