<?php
declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model\GeoIp;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use MiniOrange\PDProtect\Helper\Data;

/**
 * 3-tier automatic country detection cascade:
 *
 *   Tier 1 — CDN headers   (CF-IPCountry, X-Country-Code, X-Geo-Country, Geoip-Country-Code)
 *   Tier 2 — MaxMind DB    (var/geoip/GeoLite2-Country.mmdb  — optional, silently skipped if absent)
 *   Tier 3 — External API  (ip-api.com — last resort, 3 s timeout)
 *
 * The first tier that returns a valid 2-letter ISO code wins; the rest are skipped.
 * Private/local IP detection is the caller's responsibility (see PrivacyPopup::isPrivateIp()).
 */
class CountryResolver
{
    private const CDN_HEADERS = [
        'CF-IPCountry',       // Cloudflare
        'X-Country-Code',     // AWS CloudFront / generic CDN
        'X-Geo-Country',      // generic reverse proxies
        'Geoip-Country-Code', // nginx geoip2 module
    ];

    private const DB_RELATIVE_PATH = 'geoip/GeoLite2-Country.mmdb';

    /** @var \GeoIp2\Database\Reader|null */
    private $reader = null;
    private bool $readerInitialized = false;

    public function __construct(
        private readonly Curl $curl,
        private readonly Request $request,
        private readonly DirectoryList $directoryList,
        private readonly Data $dataHelper
    ) {}

    /**
     * Returns the ISO 3166-1 alpha-2 country code, or '' on failure.
     *
     * Strategy:
     *   1. CDN headers — always checked first; no IP lookup needed, works behind any CDN/proxy
     *   2. MaxMind DB  — only if the IP is a routable public address
     *   3. External API — fallback for public IPs when the DB is absent
     *
     * Tiers 2 & 3 are skipped for private/loopback/reserved addresses (e.g. DDEV container IPs).
     */
    public function resolve(string $ip): string
    {
        $this->dataHelper->log_debug('CountryResolver: resolve() called for IP: ' . $ip);

        // Tier 1: always check CDN headers — they don't require a resolvable IP
        $country = $this->fromCdnHeaders();
        if ($country !== '') {
            $this->dataHelper->log_debug("CountryResolver: resolved country = '{$country}' (via CDN header)");
            return $country;
        }

        // Tiers 2 & 3 require a routable public IP
        if ($ip === '' || $this->isPrivateIp($ip)) {
            $this->dataHelper->log_debug("CountryResolver: private/local IP ({$ip}), skipping MaxMind and API tiers");
            return '';
        }

        $country = $this->fromMaxMind($ip) ?: $this->fromExternalApi($ip);
        $this->dataHelper->log_debug("CountryResolver: resolved country = '{$country}'");
        return $country;
    }

    /**
     * Returns true if the IP is private, loopback, or reserved (not routable on the public internet).
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    // ── Tier 1: CDN / reverse-proxy headers ──────────────────────────────────

    private function fromCdnHeaders(): string
    {
        foreach (self::CDN_HEADERS as $header) {
            $value = strtoupper(trim((string) $this->request->getHeader($header)));
            if ($value !== '' && $value !== 'XX' && preg_match('/^[A-Z]{2}$/', $value)) {
                $this->dataHelper->log_debug("CountryResolver: Tier 1 (CDN header {$header}): {$value}");
                return $value;
            }
        }
        $this->dataHelper->log_debug('CountryResolver: Tier 1 (CDN headers): no country found');
        return '';
    }

    // ── Tier 2: MaxMind GeoLite2 local database ───────────────────────────────

    private function fromMaxMind(string $ip): string
    {
        $reader = $this->getReader();
        if ($reader === null) {
            return '';
        }

        try {
            $country = strtoupper((string) ($reader->country($ip)->country->isoCode ?? ''));
            if ($country !== '') {
                $this->dataHelper->log_debug("CountryResolver: Tier 2 (MaxMind DB): {$country}");
            } else {
                $this->dataHelper->log_debug('CountryResolver: Tier 2 (MaxMind DB): no country found');
            }
            return $country;
        } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
            $this->dataHelper->log_debug('CountryResolver: Tier 2 (MaxMind DB): address not found');
            return '';
        } catch (\Throwable $e) {
            $this->dataHelper->log_debug('CountryResolver: Tier 2 (MaxMind DB): error — ' . $e->getMessage());
            return '';
        }
    }

    private function getReader(): ?\GeoIp2\Database\Reader
    {
        if ($this->readerInitialized) {
            return $this->reader;
        }

        $this->readerInitialized = true;

        try {
            $dbPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . self::DB_RELATIVE_PATH;

            if (!file_exists($dbPath)) {
                $this->dataHelper->log_debug('CountryResolver: Tier 2 (MaxMind DB): file not found at ' . $dbPath . ', skipping');
                return null;
            }

            $this->reader = new \GeoIp2\Database\Reader($dbPath);
        } catch (\Throwable $e) {
            $this->dataHelper->log_debug('CountryResolver: Tier 2 (MaxMind DB): could not initialise reader — ' . $e->getMessage());
            $this->reader = null;
        }

        return $this->reader;
    }

    // ── Tier 3: External IP-geolocation API ───────────────────────────────────

    private function fromExternalApi(string $ip): string
    {
        try {
            $this->curl->setTimeout(3);
            $this->curl->get('https://ip-api.com/json/' . rawurlencode($ip) . '?fields=countryCode');
            $data    = json_decode((string) $this->curl->getBody(), true);
            $country = strtoupper((string) ($data['countryCode'] ?? ''));
            if ($country !== '') {
                $this->dataHelper->log_debug("CountryResolver: Tier 3 (API): {$country}");
            } else {
                $this->dataHelper->log_debug('CountryResolver: Tier 3 (API): no country found');
            }
            return $country;
        } catch (\Throwable $e) {
            $this->dataHelper->log_debug('CountryResolver: Tier 3 (API): error — ' . $e->getMessage());
            return '';
        }
    }
}
