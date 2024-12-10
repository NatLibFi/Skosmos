<?php

namespace Translation\Extractor;

use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class JsTranslationExtractor implements ExtractorInterface
{
    private string $prefix = '';

    public function setPrefix(string $prefix): void
    {
        // Allow setting a prefix only if it is provided
        $this->prefix = $prefix ?: '';
    }

    public function extract($directory, MessageCatalogue $catalogue): void
    {
        // Ensure the provided directory exists and is a directory
        if (!is_dir($directory)) {
            echo "Skipping non-directory: $directory" . PHP_EOL;
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'js') {
                continue;
            }

            echo "Processing file: " . $file->getPathname() . PHP_EOL; // Debugging output

            $content = file_get_contents($file->getPathname());

            preg_match_all('/\$t\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);

            foreach ($matches[1] as $key) {
                echo "Found key: " . $key . PHP_EOL; // Debugging output
                $catalogue->set($this->prefix . $key, $key);
            }
        }
    }
}
