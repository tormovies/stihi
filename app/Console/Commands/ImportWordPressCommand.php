<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use Illuminate\Console\Command;

class ImportWordPressCommand extends Command
{
    protected $signature = 'import:wordpress 
                            {file? : Path to WordPress WXR XML (default: backup-old/WordPress.*.xml)}';

    protected $description = 'Import content from WordPress WXR export (authors, poems, pages)';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! $file) {
            $glob = base_path('backup-old/WordPress.*.xml');
            $files = glob($glob);
            if (empty($files)) {
                $this->error('No WordPress XML found. Specify file or put WordPress.*.xml in backup-old/');
                return self::FAILURE;
            }
            $file = $files[0];
        }
        if (! is_readable($file)) {
            $this->error("File not readable: {$file}");
            return self::FAILURE;
        }

        $this->info("Loading {$file}...");
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $this->error('Invalid XML: ' . implode("\n", array_map(fn ($e) => $e->message, libxml_get_errors())));
            return self::FAILURE;
        }

        $xml->registerXPathNamespace('wp', 'http://wordpress.org/export/1.2/');
        $xml->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
        $channel = $xml->channel;
        if (! $channel) {
            $this->error('Invalid WXR: no channel');
            return self::FAILURE;
        }

        $authorsBySlug = $this->importAuthors($channel);
        $this->importPages($channel);
        $this->importPoems($channel, $authorsBySlug);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function importAuthors(\SimpleXMLElement $channel): array
    {
        $this->info('Importing authors (categories)...');
        $map = [];
        $categories = $channel->xpath('.//wp:category');
        foreach ($categories as $cat) {
            $nicename = (string) $cat->children('wp', true)->category_nicename;
            $name = (string) $cat->children('wp', true)->cat_name;
            if ($nicename === '' || str_contains($nicename, '%')) {
                continue; // skip "Без рубрики" (URL-encoded slug)
            }
            $slug = $nicename;
            $author = Author::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
            $map[$slug] = $author->id;
        }
        $this->info('  Authors: ' . count($map));
        return $map;
    }

    private function importPages(\SimpleXMLElement $channel): void
    {
        $this->info('Importing pages...');
        $items = $channel->item;
        $count = 0;
        foreach ($items as $item) {
            $postType = (string) $item->children('wp', true)->post_type;
            if ($postType !== 'page') {
                continue;
            }
            $status = (string) $item->children('wp', true)->status;
            $slug = (string) $item->children('wp', true)->post_name;
            $title = (string) $item->title;
            $body = (string) $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
            $isHome = ($slug === 'stihotvoreniya-russkih-poetov');
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'body' => $body,
                    'is_published' => $status === 'publish',
                    'is_home' => $isHome,
                ]
            );
            $count++;
        }
        $this->info("  Pages: {$count}");
    }

    private function importPoems(\SimpleXMLElement $channel, array $authorsBySlug): void
    {
        $this->info('Importing poems (posts)...');
        $items = $channel->item;
        $count = 0;
        $bar = $this->output->createProgressBar();
        $bar->start();
        foreach ($items as $item) {
            $postType = (string) $item->children('wp', true)->post_type;
            if ($postType !== 'post') {
                continue;
            }
            $status = (string) $item->children('wp', true)->status;
            if ($status !== 'publish') {
                continue;
            }
            $slug = (string) $item->children('wp', true)->post_name;
            $title = (string) $item->title;
            $body = (string) $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
            $postDate = (string) $item->children('wp', true)->post_date;
            $authorId = null;
            foreach ($item->category as $cat) {
                $domain = (string) $cat->attributes()->domain;
                if ($domain === 'category') {
                    $nicename = (string) $cat->attributes()->nicename;
                    if (isset($authorsBySlug[$nicename])) {
                        $authorId = $authorsBySlug[$nicename];
                        break;
                    }
                }
            }
            if (! $authorId) {
                $bar->advance();
                continue;
            }
            $metaTitle = $this->getPostMeta($item, '_yoast_wpseo_title');
            $metaDesc = $this->getPostMeta($item, '_yoast_wpseo_meta_description');
            $publishedAt = $postDate ? date('Y-m-d H:i:s', strtotime($postDate)) : null;
            Poem::updateOrCreate(
                ['slug' => $slug],
                [
                    'author_id' => $authorId,
                    'title' => $title,
                    'body' => $body,
                    'meta_title' => $metaTitle ?: null,
                    'meta_description' => $metaDesc ?: null,
                    'published_at' => $publishedAt,
                ]
            );
            $count++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("  Poems: {$count}");
    }

    private function getPostMeta(\SimpleXMLElement $item, string $key): ?string
    {
        foreach ($item->children('wp', true)->postmeta as $meta) {
            if ((string) $meta->meta_key === $key) {
                return (string) $meta->meta_value;
            }
        }
        return null;
    }
}
