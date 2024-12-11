<?php

namespace Translation\Extractor;

use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class JsTranslationExtractor implements ExtractorInterface
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
        echo "Constructor directory: {$this->directory}" . PHP_EOL;
        if (!is_dir($this->directory)) {
            echo "Directory does not exist: {$this->directory}" . PHP_EOL;
        }
    }

    private string $prefix = '';

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix ?: '';
    }

    public function extract($directory, MessageCatalogue $catalogue): void
    {
        $directory = $this->directory;
        echo "Using directory: $directory" . PHP_EOL;
        if (!is_dir($directory)) {
            echo "Invalid directory: $directory" . PHP_EOL;
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'js') {
                continue;
            }
            echo "Processing file: " . $file->getPathname() . PHP_EOL;
            $content = file_get_contents($file->getPathname());
            preg_match_all('/\$t\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);
            foreach ($matches[1] as $key) {
                echo "Found key: " . $key . PHP_EOL;
                $catalogue->set($this->prefix . $key, $key);
            }
        }
    }
}
