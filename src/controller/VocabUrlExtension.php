<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class VocabUrlExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('vocab_url', [$this, 'vocabUrlFilter']),
        ];
    }

    /**
     * Creates Skosmos vocabulary links for top-level pages
     * @param string $page 'home', 'about', or 'feedback'
     * @param string $lang language code (e.g., 'en', 'fi')
     * @param string $contentLang content language code (e.g., 'en', 'fi')
     * @param string $vocabid vocabulary ID (optional)
     * @param bool $anylang whether anylang parameter is active
     * @return string containing the Skosmos link
     */
    public function vocabUrlFilter($page, $lang, $contentLang = null, $vocabid = null, $anylang = false)
    {
        $url = '';

        // Build base URL
        if ($vocabid && $vocabid !== '') {
            $url = $vocabid . '/';
        }

        $url .= $lang;

        // Add page suffix for non-home pages
        if ($page !== 'home') {
            $url .= '/' . $page;
        }

        // Build query parameters
        $params = [];

        // Add clang parameter if needed
        if ($contentLang && $contentLang !== $lang) {
            $params['clang'] = $contentLang;
        }

        // Add anylang parameter if needed
        if ($anylang) {
            $params['anylang'] = 'on';
        }

        // Append parameters if any
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}
