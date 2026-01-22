<?php

/**
 * Tests for validating Twig translation completeness and consistency
 */
class TranslationTest extends PHPUnit\Framework\TestCase
{
    private const DEFAULT_LANG = 'en';

    /**
     * Test that all translation keys used in Twig templates exist in the English translation file
     *
     * @covers Translation extraction completeness
     */
    public function testAllTwigTranslationKeysExist()
    {
        $twigKeys = $this->extractTranslationKeysFromTwig();
        $translationKeys = $this->loadTranslationKeys(self::DEFAULT_LANG);

        $missingKeys = array_diff($twigKeys, $translationKeys);

        $this->assertEmpty(
            $missingKeys,
            "The following translation keys are used in Twig templates but missing from messages.en.json:\n" .
            implode("\n", $missingKeys) .
            "\n\nRun: bin/extract-translations to update translations"
        );
    }

    /**
     * Test that all JavaScript $t() translation keys exist in the English translation file
     *
     * @covers JavaScript translation extraction completeness
     */
    public function testAllJavaScriptTranslationKeysExist()
    {
        $jsKeys = $this->extractTranslationKeysFromJavaScript();
        $translationKeys = $this->loadTranslationKeys(self::DEFAULT_LANG);

        $missingKeys = array_diff($jsKeys, $translationKeys);

        $this->assertEmpty(
            $missingKeys,
            "The following translation keys are used in JavaScript but missing from messages.en.json:\n" .
            implode("\n", $missingKeys) .
            "\n\nRun: bin/extract-translations to update translations"
        );
    }

    /**
     * Test that all translation files have the same keys (no missing translations)
     *
     * @covers Translation consistency across languages
     */
    public function testAllLanguagesHaveSameKeys()
    {
        $englishKeys = $this->loadTranslationKeys(self::DEFAULT_LANG);
        $translationFiles = glob(__DIR__ . '/../resource/translations/messages.*.json');

        $inconsistencies = [];

        foreach ($translationFiles as $file) {
            $lang = $this->extractLanguageFromFilename($file);
            if ($lang === self::DEFAULT_LANG) {
                continue; // skip English, it's our reference
            }

            $langKeys = $this->loadTranslationKeys($lang);
            $missing = array_diff($englishKeys, $langKeys);
            $extra = array_diff($langKeys, $englishKeys);

            if (!empty($missing) || !empty($extra)) {
                $inconsistencies[$lang] = [
                    'missing' => $missing,
                    'extra' => $extra
                ];
            }
        }

        $errorMessage = "";
        foreach ($inconsistencies as $lang => $issues) {
            if (!empty($issues['missing'])) {
                $errorMessage .= "\n$lang is missing keys: " . implode(', ', array_slice($issues['missing'], 0, 5));
                if (count($issues['missing']) > 5) {
                    $errorMessage .= " (+" . (count($issues['missing']) - 5) . " more)";
                }
            }
            if (!empty($issues['extra'])) {
                $errorMessage .= "\n$lang has extra keys: " . implode(', ', array_slice($issues['extra'], 0, 5));
                if (count($issues['extra']) > 5) {
                    $errorMessage .= " (+" . (count($issues['extra']) - 5) . " more)";
                }
            }
        }

        $this->assertEmpty(
            $inconsistencies,
            "Translation files have inconsistent keys:$errorMessage"
        );
    }

    /**
     * Test that English translations don't have empty values
     *
     * @covers Translation quality
     */
    public function testEnglishTranslationsAreNotEmpty()
    {
        $translations = $this->loadTranslations(self::DEFAULT_LANG);
        $emptyKeys = [];

        foreach ($translations as $key => $value) {
            if (trim($value) === '') {
                $emptyKeys[] = $key;
            }
        }

        $this->assertEmpty(
            $emptyKeys,
            "The following English translation keys have empty values:\n" .
            implode("\n", $emptyKeys)
        );
    }

    /**
     * Test that translation JSON files are valid
     *
     * @covers Translation file validity
     */
    public function testTranslationFilesAreValidJson()
    {
        $translationFiles = glob(__DIR__ . '/../resource/translations/messages.*.json');

        foreach ($translationFiles as $file) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);

            $this->assertNotNull(
                $decoded,
                "Translation file $file contains invalid JSON: " . json_last_error_msg()
            );

            $this->assertIsArray(
                $decoded,
                "Translation file $file should contain a JSON object"
            );
        }
    }

    /**
     * Extract all translation keys from Twig templates
     *
     * @return array Array of unique translation keys
     */
    private function extractTranslationKeysFromTwig(): array
    {
        $keys = [];
        $twigFiles = $this->findTwigFiles();

        foreach ($twigFiles as $file) {
            $content = file_get_contents($file);

            // Pattern 1: {{ "key" | trans }}
            preg_match_all('/\{\{\s*["\']([^"\']+)["\']\s*\|\s*trans/', $content, $matches1);
            $keys = array_merge($keys, $matches1[1]);

            // Pattern 2: {{ 'key' | trans({...}) }}
            preg_match_all('/\{\{\s*["\']([^"\']+)["\']\s*\|\s*trans\s*\(/', $content, $matches2);
            $keys = array_merge($keys, $matches2[1]);

            // Pattern 3: {% trans %}key{% endtrans %}
            preg_match_all('/\{%\s*trans\s*%\}([^{]+)\{%\s*endtrans\s*%\}/', $content, $matches3);
            $keys = array_merge($keys, array_map('trim', $matches3[1]));
        }

        return array_unique(array_filter($keys));
    }

    /**
     * Extract all translation keys from JavaScript files
     *
     * @return array Array of unique translation keys
     */
    private function extractTranslationKeysFromJavaScript(): array
    {
        $keys = [];
        $jsFiles = glob(__DIR__ . '/../resource/js/**/*.js', GLOB_BRACE);

        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);

            // Pattern: $t("key") or $t('key')
            preg_match_all('/\$t\s*\(\s*["\']([^"\']+)["\']\s*\)/', $content, $matches);
            $keys = array_merge($keys, $matches[1]);
        }

        return array_unique(array_filter($keys));
    }

    /**
     * Load translation keys from a specific language file
     *
     * @param string $lang Language code
     * @return array Array of translation keys
     */
    private function loadTranslationKeys(string $lang): array
    {
        $translations = $this->loadTranslations($lang);
        return array_keys($translations);
    }

    /**
     * Load translations from a specific language file
     *
     * @param string $lang Language code
     * @return array Associative array of translations
     */
    private function loadTranslations(string $lang): array
    {
        $file = __DIR__ . "/../resource/translations/messages.$lang.json";

        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $translations = json_decode($content, true);

        return is_array($translations) ? $translations : [];
    }

    /**
     * Find all Twig template files
     *
     * @return array Array of file paths
     */
    private function findTwigFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../src/view')
        );

        $twigFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $twigFiles[] = $file->getPathname();
            }
        }

        return $twigFiles;
    }

    /**
     * Extract language code from translation filename
     *
     * @param string $filename Full path to translation file
     * @return string Language code
     */
    private function extractLanguageFromFilename(string $filename): string
    {
        if (preg_match('/messages\.([a-z_]+)\.json$/i', $filename, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
