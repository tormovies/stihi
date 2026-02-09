<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MinifyCss extends Command
{
    protected $signature = 'css:minify {--source=public/css/site.css} {--output=public/css/site.min.css}';
    protected $description = 'Minify site.css to site.min.css for production';

    public function handle(): int
    {
        $source = base_path($this->option('source'));
        $output = base_path($this->option('output'));

        if (!is_file($source)) {
            $this->error("Source file not found: {$source}");
            return self::FAILURE;
        }

        $css = file_get_contents($source);

        // Remove block comments (keep /*! ... */ for licenses)
        $css = preg_replace_callback(
            '/\/\*(!?)[\s\S]*?\*\//u',
            fn ($m) => $m[1] === '!' ? $m[0] : '',
            $css
        );

        // Collapse multiple whitespace (including newlines) to single space
        $css = preg_replace('/\s+/u', ' ', $css);
        $css = trim($css);

        $dir = dirname($output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($output, $css) === false) {
            $this->error("Failed to write: {$output}");
            return self::FAILURE;
        }

        $this->info('Minified: ' . basename($source) . ' → ' . basename($output));
        return self::SUCCESS;
    }
}
