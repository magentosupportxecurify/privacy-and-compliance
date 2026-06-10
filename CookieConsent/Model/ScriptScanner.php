<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Fetches storefront HTML and extracts third-party script URLs.
 */
class ScriptScanner
{
    public function __construct(
        private readonly Curl $curl,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return list<string> Lowercase absolute script URLs (third-party only), sorted unique
     */
    public function scanStoreHomepage(?int $storeId = null): array
    {
        try {
            $store = $storeId !== null
                ? $this->storeManager->getStore($storeId)
                : $this->storeManager->getDefaultStoreView();
        } catch (\Throwable) {
            return [];
        }

        if ($store === null) {
            return [];
        }

        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $host = (string) (parse_url($baseUrl, PHP_URL_HOST) ?: '');
        if ($host === '') {
            return [];
        }

        $html = $this->fetchUrl($baseUrl);
        if ($html === '') {
            return [];
        }

        return $this->extractThirdPartyScriptUrls($html, $baseUrl, strtolower($host));
    }

    private function fetchUrl(string $url): string
    {
        $this->curl->setTimeout(20);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_MAXREDIRS, 5);
        try {
            $this->curl->get($url);
        } catch (\Throwable) {
            return '';
        }

        return (string) $this->curl->getBody();
    }

    /**
     * @return list<string>
     */
    private function extractThirdPartyScriptUrls(string $html, string $baseUrl, string $storeHost): array
    {
        if (!preg_match_all('/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $html, $matches)) {
            return [];
        }
        $urls = [];
        foreach ($matches[2] as $src) {
            $absolute = $this->resolveUrl(trim($src), $baseUrl);
            if ($absolute === '') {
                continue;
            }
            $host = parse_url($absolute, PHP_URL_HOST);
            if ($host === null || $host === false || $host === '') {
                continue;
            }
            $host = strtolower((string) $host);
            if ($host === $storeHost) {
                continue;
            }
            $urls[] = strtolower($absolute);
        }
        $urls = array_values(array_unique($urls));
        sort($urls);

        return $urls;
    }

    private function resolveUrl(string $src, string $baseUrl): string
    {
        if ($src === '') {
            return '';
        }
        if (str_starts_with($src, '//')) {
            $src = 'https:' . $src;
        }
        if (preg_match('#^https?://#i', $src)) {
            return $src;
        }

        $base = parse_url($baseUrl);
        if ($base === false || !isset($base['scheme'], $base['host'])) {
            return '';
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = $scheme . '://' . $host . $port;

        if ($src[0] === '/') {
            return $origin . $src;
        }

        $basePath = isset($base['path']) ? $base['path'] : '/';
        if (!str_ends_with($basePath, '/')) {
            $basePath = dirname($basePath);
            if ($basePath === '.' || $basePath === '\\') {
                $basePath = '/';
            }
            if ($basePath !== '/' && !str_ends_with($basePath, '/')) {
                $basePath .= '/';
            }
        }

        return $origin . $basePath . $src;
    }
}
