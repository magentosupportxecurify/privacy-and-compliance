<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\ViewModel\Adminhtml;

use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Config\Source\Order\Status as OrderStatusSource;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use MiniOrange\PDProtect\Model\Config\DataDeletionConfig;

class Configure implements ArgumentInterface
{
    private const CURRENT_VERSION = '1.0.0';

    private const TAB_MAP = [
        'generalsettings'    => 'generalsettings',
        'customerprivacy'    => 'customerprivacy',
        'datadeletion'       => 'datadeletion',
        'datadeletionconfig' => 'datadeletionconfig',
        'consentlogs'        => 'consentlogs',
        'upgrade'            => 'upgrade',
        'account'            => 'account',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly WriterInterface $configWriter,
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder,
        private readonly Country $countrySource,
        private readonly OrderStatusSource $orderStatusSource,
        private readonly DataDeletionConfig $dataDeletionConfig,
        private readonly AssetRepository $assetRepository,
        private readonly PDProtectHelper $pdHelper
    ) {}

    public function getTabUrl(string $tab): string
    {
        return $this->urlBuilder->getUrl('mopdp/' . $tab . '/index');
    }

    public function getCurrentVersion(): string
    {
        return self::CURRENT_VERSION;
    }

    public function getViewFileUrl(string $fileId): string
    {
        return $this->assetRepository->getUrl($fileId);
    }

    public function getCurrentActiveTab(): string
    {
        $controllerName = strtolower((string) $this->request->getControllerName());
        return self::TAB_MAP[$controllerName] ?? 'generalsettings';
    }

    public function getCountryOptions(): array
    {
        return $this->countrySource->toOptionArray(true);
    }

    public function isPremium(): bool
    {
        return $this->pdHelper->isPremium();
    }

    public function getPlanName(): string
    {
        return $this->pdHelper->getPlanName();
    }

    public function getUpgradeUrl(): string
    {
        return $this->getTabUrl('upgrade');
    }

    // ── Trial / License status — proxy to helper (premium helper at runtime via DI preference) ──

    public function isLicenseVerified(): bool
    {
        return $this->pdHelper->isLicenseVerified();
    }

    public function isTrialActive(): bool
    {
        return $this->pdHelper->isTrialActive();
    }

    public function isTrialExpired(): bool
    {
        return $this->pdHelper->isTrialExpired();
    }

    public function getTrialDaysRemaining(): int
    {
        return $this->pdHelper->getTrialDaysRemaining();
    }

    public function getSupportSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/support/submit');
    }

    // --- General Settings getters ---

    public function getDisplayPopup(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/general/display_popup');
    }

    public function getAllowedCountriesMode(): string
    {
        $mode = (string) $this->scopeConfig->getValue('pdprotect/general/allowed_countries_mode');
        return in_array($mode, ['all', 'none', 'specific'], true) ? $mode : 'all';
    }

    public function getCountryRestriction(): array
    {
        $value = (string) $this->scopeConfig->getValue('pdprotect/general/country_restriction');
        return $value !== '' ? explode(',', $value) : [];
    }

    public function getLogGuestConsent(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/general/log_guest_consent');
    }

    public function getLogAutoClean(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/general/log_auto_clean');
    }

    public function getLogAutoCleanPeriod(): int
    {
        return (int) ($this->scopeConfig->getValue('pdprotect/general/log_auto_clean_period') ?: 30);
    }

    public function getLogAutoCleanUnit(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/general/log_auto_clean_unit') ?: 'days');
    }

    // --- Customer Account Privacy getters ---

    public function getCustomerPrivacyTabName(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/tab_name') ?: 'Privacy Settings');
    }

    public function getShowPrivacyPolicy(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/show_privacy_policy');
    }

    public function getPrivacyPolicyUrl(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/privacy_policy_url') ?: '');
    }

    public function getShowCookiePolicy(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/show_cookie_policy');
    }

    public function getCookiePolicyUrl(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/cookie_policy_url') ?: '');
    }

    public function getEnableDataDownload(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_data_download');
    }

    public function getEnableAnonymize(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_anonymize');
    }

    public function getEnableDeleteAccount(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_delete_account');
    }

    public function getEnableOptOut(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_opt_out');
    }

    public function getEnableDpoInfo(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_dpo_info');
    }

    public function getDpoName(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_name') ?: '');
    }

    public function getDpoEmail(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_email') ?: '');
    }

    public function getDpoPhone(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_phone') ?: '');
    }

    // --- Data Deletion & Anonymization Config getters ---

    public function isAbandonedDeletionEnabled(): bool
    {
        return $this->dataDeletionConfig->isAbandonedDeletionEnabled();
    }

    public function getAbandonedValue(): int
    {
        return $this->dataDeletionConfig->getAbandonedValue();
    }

    public function getAbandonedUnit(): string
    {
        return $this->dataDeletionConfig->getAbandonedUnit();
    }

    public function getAbandonedThresholdDays(): int
    {
        return $this->dataDeletionConfig->getAbandonedThresholdDays();
    }

    public function isRecentDocsDeletionEnabled(): bool
    {
        return $this->dataDeletionConfig->isRecentDocsDeletionEnabled();
    }

    public function getRecentDocsValue(): int
    {
        return $this->dataDeletionConfig->getRecentDocsValue();
    }

    public function getRecentDocsUnit(): string
    {
        return $this->dataDeletionConfig->getRecentDocsUnit();
    }

    public function getRecentDocsDays(): int
    {
        return $this->dataDeletionConfig->getRecentDocsThresholdDays();
    }

    public function isOrderStatusDeletionEnabled(): bool
    {
        return $this->dataDeletionConfig->isOrderStatusDeletionEnabled();
    }

    public function getOrderStatuses(): array
    {
        return $this->dataDeletionConfig->getOrderStatuses();
    }

    public function getOrderAction(): string
    {
        return $this->dataDeletionConfig->getOrderAction();
    }

    public function getOrderStatusOptions(): array
    {
        $options = [];
        foreach ($this->orderStatusSource->toOptionArray() as $option) {
            if (!empty($option['value'])) {
                $options[] = $option;
            }
        }
        return $options;
    }

    // --- Config writer ---

    public function saveConfig(string $path, mixed $value): void
    {
        $this->configWriter->save($path, $value, 'default', 0);
    }
}
