<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\ViewModel\Adminhtml;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Sales\Model\Config\Source\Order\Status as OrderStatusSource;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use MiniOrange\PDProtect\Model\Config\DataDeletionConfig;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium extension of the free Configure ViewModel.
 *
 * Adds isModuleActive() so premium admin templates can determine
 * whether to fade settings (trial expired + no license), without
 * conflating that check with isPremium() which means "is the premium
 * module installed?" (always true here).
 */
class Configure extends \MiniOrange\PDProtect\ViewModel\Adminhtml\Configure
{
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        Country $countrySource,
        OrderStatusSource $orderStatusSource,
        DataDeletionConfig $dataDeletionConfig,
        AssetRepository $assetRepository,
        PDProtectHelper $pdHelper,
        AuthSession $authSession,
        AuthorizationInterface $authorization,
        private readonly PremiumHelper $premiumHelper
    ) {
        parent::__construct(
            $request,
            $urlBuilder,
            $countrySource,
            $orderStatusSource,
            $dataDeletionConfig,
            $assetRepository,
            $pdHelper,
            $authSession,
            $authorization
        );
    }

    /**
     * Returns true when the module is permitted to function:
     * trial is still running OR a valid license key is present.
     * Used by premium admin templates to fade settings when inactive.
     */
    public function isModuleActive(): bool
    {
        return $this->premiumHelper->isModuleActive();
    }

    private function scoped(string $path): mixed
    {
        return $this->premiumHelper->getStoreConfig($path);
    }

    public function getDisplayPopup(): bool
    {
        return (bool) $this->scoped('pdprotect/general/display_popup');
    }

    public function getAllowedCountriesMode(): string
    {
        return (string) ($this->scoped('pdprotect/general/allowed_countries_mode') ?: 'all');
    }

    public function getCountryRestriction(): array
    {
        $v = (string) ($this->scoped('pdprotect/general/country_restriction') ?? '');
        return $v !== '' ? explode(',', $v) : [];
    }

    public function getLogGuestConsent(): bool
    {
        return (bool) $this->scoped('pdprotect/general/log_guest_consent');
    }

    public function getLogAutoClean(): bool
    {
        return (bool) $this->scoped('pdprotect/general/log_auto_clean');
    }

    public function getLogAutoCleanPeriod(): int
    {
        return (int) ($this->scoped('pdprotect/general/log_auto_clean_period') ?: 30);
    }

    public function getLogAutoCleanUnit(): string
    {
        return (string) ($this->scoped('pdprotect/general/log_auto_clean_unit') ?: 'days');
    }

    public function getCustomerPrivacyTabName(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/tab_name') ?: 'Privacy Settings');
    }

    public function getShowPrivacyPolicy(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/show_privacy_policy');
    }

    public function getPrivacyPolicyUrl(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/privacy_policy_url') ?: '');
    }

    public function getShowCookiePolicy(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/show_cookie_policy');
    }

    public function getCookiePolicyUrl(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/cookie_policy_url') ?: '');
    }

    public function getEnableDataDownload(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/enable_data_download');
    }

    public function getEnableAnonymize(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/enable_anonymize');
    }

    public function getEnableDeleteAccount(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/enable_delete_account');
    }

    public function getEnableOptOut(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/enable_opt_out');
    }

    public function getEnableDpoInfo(): bool
    {
        return (bool) $this->scoped('pdprotect/customer_privacy/enable_dpo_info');
    }

    public function getDpoName(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/dpo_name') ?: '');
    }

    public function getDpoEmail(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/dpo_email') ?: '');
    }

    public function getDpoPhone(): string
    {
        return (string) ($this->scoped('pdprotect/customer_privacy/dpo_phone') ?: '');
    }
}
