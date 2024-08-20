<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LinkUrlExtension extends AbstractExtension
{
    private $model;

    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('link_url', [$this, 'linkUrlFilter']),
        ];
    }

    /**
     * Creates Skosmos links from uris.
     * @param string $uri
     * @param Vocabulary $vocab
     * @param string $lang
     * @param string $type
     * @param string $clang content
     * @param string $term
     * @throws Exception if the vocabulary ID is not found in configuration
     * @return string containing the Skosmos link
     */
    public function linkUrlFilter($uri, $vocab, $lang, $type = 'page', $clang = null, $term = null)
    {
        // $vocab can either be null, a vocabulary id (string) or a Vocabulary object
        if ($vocab === null) {
            return $uri;
        } elseif (is_string($vocab)) {
            $vocid = $vocab;
            $vocab = $this->model->getVocabulary($vocid);
        } else {
            $vocid = $vocab->getId();
        }

        $params = [];
        if (isset($clang) && $clang !== $lang) {
            $params['clang'] = $clang;
        }

        if (isset($term)) {
            $params['q'] = $term;
        }

        $localname = $vocab->getLocalName($uri);
        if ($localname !== $uri && $localname === urlencode($localname)) {
            $paramstr = count($params) > 0 ? '?' . http_build_query($params) : '';
            if ($type && $type !== '' && $type !== 'vocab' && !($localname === '' && $type === 'page')) {
                return "$vocid/$lang/$type/$localname" . $paramstr;
            }

            return "$vocid/$lang/$localname" . $paramstr;
        }

        $params['uri'] = $uri;
        return "$vocid/$lang/$type/?" . http_build_query($params);
    }
}
