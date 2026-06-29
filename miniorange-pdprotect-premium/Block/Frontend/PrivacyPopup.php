<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Block\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtectPremium\Model\GeoIp\CountryResolver;

/**
 * Premium override of PrivacyPopup block.
 *
 * Adds country-based popup filtering via a 3-tier GeoIP cascade:
 *   Tier 1 — CDN headers (Cloudflare, CloudFront, etc.)
 *   Tier 2 — MaxMind GeoLite2 local database
 *   Tier 3 — ip-api.com external API fallback
 *
 * The resolved country is cached in the session for the duration of the visit
 * to avoid redundant lookups on every page.
 */
class PrivacyPopup extends \MiniOrange\PDProtect\Block\Frontend\PrivacyPopup
{
    private const SESSION_COUNTRY_KEY = 'mopdp_visitor_country';

    private ScopeConfigInterface $premiumScopeConfig;

    /** Stored separately so we can call isModuleActive() — parent holds $dataHelper as private. */
    private \MiniOrange\PDProtectPremium\Helper\Data $premiumHelper;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Data $dataHelper,
        CookieManagerInterface $cookieManager,
        private readonly CountryResolver $countryResolver,
        private readonly RemoteAddress $remoteAddress,
        private readonly SessionManagerInterface $session,
        array $data = []
    ) {
        $this->premiumScopeConfig = $scopeConfig;
        // DI preference guarantees $dataHelper is PDProtectPremium\Helper\Data at runtime.
        /** @var \MiniOrange\PDProtectPremium\Helper\Data $dataHelper */
        $this->premiumHelper = $dataHelper;
        parent::__construct($context, $scopeConfig, $dataHelper, $cookieManager, $data);
    }

    /**
     * Short-circuit the popup entirely when the module is not active
     * (trial expired + no license). Returns false before even reading config.
     */
    public function isPopupEnabled(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return parent::isPopupEnabled();
    }

    /**
     * Returns the visitor's ISO 3166-1 alpha-2 country code.
     * Result is cached in session to avoid repeated GeoIP lookups on every page.
     *
     * The CountryResolver handles both CDN-header detection (no IP needed) and
     * IP-based lookups (MaxMind, API), so we always pass the IP and let it decide.
     */
    public function getVisitorCountry(): string
    {
        // Return cached result if already resolved this session
        $cached = $this->session->getData(self::SESSION_COUNTRY_KEY);
        if ($cached !== null) {
            return (string) $cached;
        }

        $ip      = (string) $this->remoteAddress->getRemoteAddress();
        $country = $this->countryResolver->resolve($ip);
        $this->session->setData(self::SESSION_COUNTRY_KEY, $country);
        return $country;
    }

    /**
     * Returns false when the country filtering mode would exclude the visitor's country.
     */
    public function shouldShowForCountry(): bool
    {
        $mode = (string) $this->premiumScopeConfig->getValue(
            'pdprotect/general/allowed_countries_mode',
            ScopeInterface::SCOPE_STORE
        );

        if ($mode === 'all') {
            return true;
        }

        if ($mode === 'none') {
            return false;
        }

        // mode === 'specific'
        $allowed = (string) ($this->premiumScopeConfig->getValue(
            'pdprotect/general/country_restriction',
            ScopeInterface::SCOPE_STORE
        ) ?? '');

        // No countries configured → hide rather than show to everyone
        if ($allowed === '') {
            return false;
        }

        $visitorCode = $this->getVisitorCountry();

        // GeoIP unavailable (local/private IP, no DB, no API) — default to NOT show
        if ($visitorCode === '') {
            return false;
        }

        $allowedList = array_filter(array_map('trim', explode(',', $allowed)));
        return in_array(strtoupper($visitorCode), array_map('strtoupper', $allowedList), true);
    }

}
