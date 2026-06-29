<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Block\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override: reads button visibility from config (instead of always forcing true).
 *
 * Injects PremiumHelper directly as its own property rather than relying on the parent's
 * protected $dataHelper, which is unreliable across PHP/Magento version combinations.
 */
class CustomerPrivacy extends \MiniOrange\PDProtect\Block\Frontend\CustomerPrivacy
{
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Data $dataHelper,
        private readonly PremiumHelper $premiumHelper,
        array $data = []
    ) {
        parent::__construct($context, $scopeConfig, $dataHelper, $data);
    }

    public function getEnableDataDownload(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/enable_data_download',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getEnableAnonymize(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/enable_anonymize',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getEnableDeleteAccount(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/enable_delete_account',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getEnableOptOut(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/enable_opt_out',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getEnableDpoInfo(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/enable_dpo_info',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getShowPrivacyPolicy(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/show_privacy_policy',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getShowCookiePolicy(): bool
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return false;
        }
        return (bool) $this->_scopeConfig->getValue(
            'pdprotect/customer_privacy/show_cookie_policy',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function areDataControlsFunctional(): bool
    {
        return $this->premiumHelper->isModuleActive();
    }
}
