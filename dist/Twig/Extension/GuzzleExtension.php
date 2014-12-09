<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Bundle\GuzzleBundle\Twig\Extension;

/**
 * Csa Guzzle Collector
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class GuzzleExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('pretty_print', [$this, 'prettyPrint']),
            new \Twig_SimpleFilter('status_code_class', [$this, 'statusCodeClass']),
            new \Twig_SimpleFilter('cache_status_class', [$this, 'cacheStatusClass']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('detect_lang', [$this, 'detectLang']),
        ];
    }

    public function detectLang($body)
    {
        $lang = 'markup';
        if (0 === strpos($body, '<?xml')) {
            $lang = 'xml';
        } elseif (preg_match('/^{.*}$|^\[.*\]$/', $body)) {
            $lang = 'json';
        }

        return $lang;
    }

    public function prettyPrint($code, $lang)
    {
        switch ($lang) {
            case 'json':
                return json_encode(json_decode($code), JSON_PRETTY_PRINT);
            case 'xml':
                $xml = new \DomDocument('1.0');
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
                $xml->loadXml($code);

                return $xml->saveXml();
            default:
                return $code;
        }
    }

    public function statusCodeClass($statusCode)
    {
        if ($statusCode >= 500) {
            return 'server-error';
        } elseif ($statusCode >= 400) {
            return 'client-error';
        } elseif ($statusCode >= 300) {
            return 'redirection';
        } elseif ($statusCode >= 200) {
            return 'success';
        } elseif ($statusCode >= 100) {
            return 'informational';
        } else {
            return 'unknown';
        }
    }

    public function cacheStatusClass($cacheStatus)
    {
        switch ($cacheStatus) {
            case 'cached':
                return 'informational';
            case 'cache saved':
                return 'client-error';
            default:
                return '';
        }
    }

    public function getName()
    {
        return 'csa_guzzle';
    }
}
