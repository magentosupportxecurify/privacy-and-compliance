<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Block\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtect\Helper\Data;

class CustomerPrivacy extends Template
{
    public function __construct(
        Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTabName(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/tab_name', ScopeInterface::SCOPE_STORE) ?: 'Privacy Settings');
    }

    public function getShowPrivacyPolicy(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/show_privacy_policy', ScopeInterface::SCOPE_STORE);
    }

    public function getPrivacyPolicyUrl(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/privacy_policy_url', ScopeInterface::SCOPE_STORE) ?: '');
    }

    public function getShowCookiePolicy(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/show_cookie_policy', ScopeInterface::SCOPE_STORE);
    }

    public function getCookiePolicyUrl(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/cookie_policy_url', ScopeInterface::SCOPE_STORE) ?: '');
    }

    // Free: always show these buttons (they are blocked at the controller/JS level).
    public function getEnableDataDownload(): bool
    {
        return true;
    }

    public function getEnableAnonymize(): bool
    {
        return true;
    }

    // Free: always show the delete button (blocked at JS/controller level, consistent with other actions).
    public function getEnableDeleteAccount(): bool
    {
        return true;
    }

    // Free: always show Withdraw Consent (blocked at controller/JS level).
    public function getEnableOptOut(): bool
    {
        return true;
    }

    public function getEnableDpoInfo(): bool
    {
        return (bool) $this->scopeConfig->getValue('pdprotect/customer_privacy/enable_dpo_info', ScopeInterface::SCOPE_STORE);
    }

    public function getDpoName(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_name', ScopeInterface::SCOPE_STORE) ?: '');
    }

    public function getDpoEmail(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_email', ScopeInterface::SCOPE_STORE) ?: '');
    }

    public function getDpoPhone(): string
    {
        return (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/dpo_phone', ScopeInterface::SCOPE_STORE) ?: '');
    }

    public function getConsentSaveUrl(): string
    {
        return $this->getUrl('mopdp/consent/save');
    }

    public function areDataControlsFunctional(): bool
    {
        return $this->dataHelper->isCustomerDataControlsFunctional();
    }
}
