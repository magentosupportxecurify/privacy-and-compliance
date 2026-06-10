<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Block\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtect\Helper\Data;

class PrivacyPopup extends Template
{
    public function __construct(
        Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Data $dataHelper,
        private readonly CookieManagerInterface $cookieManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isPopupEnabled(): bool
    {
        $enabled = (bool) $this->scopeConfig->getValue(
            'pdprotect/general/display_popup',
            ScopeInterface::SCOPE_STORE
        );
        $this->dataHelper->log_debug('PrivacyPopup: isPopupEnabled = ' . ($enabled ? 'true' : 'false'));
        return $enabled;
    }

    // Free version always shows to all countries — country filtering is premium only.
    public function shouldShowForCountry(): bool
    {
        return true;
    }

    public function hasConsentCookie(): bool
    {
        return $this->cookieManager->getCookie('mopdp_consent') !== null;
    }

    public function getPopupConfigJson(): string
    {
        $this->dataHelper->log_debug('PrivacyPopup: getPopupConfigJson');
        $config = [
            'consentCookie'  => 'mopdp_consent',
            'cookieDuration' => 365,
            'saveUrl'        => $this->getUrl('mopdp/consent/save'),
        ];
        return json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
