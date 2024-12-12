<?php

namespace Util;

use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class JsTranslationExtractor implements ExtractorInterface
{
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    private string $prefix = '';

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix ?: '';
    }

    public function extract($directory, MessageCatalogue $catalogue): void
    {
        if (!is_dir($this->directory)) {
            echo "Invalid directory: $this->directory" . PHP_EOL;
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'js') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            preg_match_all('/\$t\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);
            foreach ($matches[1] as $key) {
                $catalogue->set($this->prefix . $key, $key);
            }
        }
    }
}
