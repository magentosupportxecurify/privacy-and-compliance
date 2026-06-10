<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Block\Frontend;

use Magento\Customer\Block\Account\SortLink;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class PrivacyNavLink extends SortLink
{
    private const CONFIG_PATH        = 'pdprotect/customer_privacy/tab_name';
    private const DEFAULT_LABEL      = 'Privacy Settings';
    private const PATH_DATA_DOWNLOAD  = 'pdprotect/customer_privacy/enable_data_download';
    private const PATH_ANONYMIZE      = 'pdprotect/customer_privacy/enable_anonymize';
    private const PATH_DELETE_ACCOUNT = 'pdprotect/customer_privacy/enable_delete_account';
    private const PATH_OPT_OUT        = 'pdprotect/customer_privacy/enable_opt_out';
    private const PATH_SHOW_PRIVACY   = 'pdprotect/customer_privacy/show_privacy_policy';
    private const PATH_PRIVACY_URL    = 'pdprotect/customer_privacy/privacy_policy_url';
    private const PATH_SHOW_COOKIE    = 'pdprotect/customer_privacy/show_cookie_policy';
    private const PATH_COOKIE_URL     = 'pdprotect/customer_privacy/cookie_policy_url';
    private const PATH_DPO_INFO       = 'pdprotect/customer_privacy/enable_dpo_info';
    private const PATH_DPO_NAME       = 'pdprotect/customer_privacy/dpo_name';
    private const PATH_DPO_EMAIL      = 'pdprotect/customer_privacy/dpo_email';
    private const PATH_DPO_PHONE      = 'pdprotect/customer_privacy/dpo_phone';

    /**
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * Returns the sidebar tab label from admin config, falling back to the default.
     *
     * @return string
     */
    public function getLabel(): string
    {
        $label = (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );

        return $label !== '' ? $label : self::DEFAULT_LABEL;
    }

    /**
     * Suppress the tab entirely when no privacy content is enabled.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->hasVisibleContent()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Returns true if at least one section would be visible on the privacy page.
     * Mirrors the visibility condition used in privacy.phtml.
     *
     * @return bool
     */
    private function hasVisibleContent(): bool
    {
        $scope = ScopeInterface::SCOPE_STORE;

        $dataDownload  = (bool) $this->scopeConfig->getValue(self::PATH_DATA_DOWNLOAD, $scope);
        $anonymize     = (bool) $this->scopeConfig->getValue(self::PATH_ANONYMIZE, $scope);
        $deleteAccount = (bool) $this->scopeConfig->getValue(self::PATH_DELETE_ACCOUNT, $scope);
        $optOut        = (bool) $this->scopeConfig->getValue(self::PATH_OPT_OUT, $scope);

        if ($dataDownload || $anonymize || $deleteAccount || $optOut) {
            return true;
        }

        $showPrivacy = (bool) $this->scopeConfig->getValue(self::PATH_SHOW_PRIVACY, $scope);
        $privacyUrl  = (string) $this->scopeConfig->getValue(self::PATH_PRIVACY_URL, $scope);

        if ($showPrivacy && $privacyUrl !== '') {
            return true;
        }

        $showCookie = (bool) $this->scopeConfig->getValue(self::PATH_SHOW_COOKIE, $scope);
        $cookieUrl  = (string) $this->scopeConfig->getValue(self::PATH_COOKIE_URL, $scope);

        if ($showCookie && $cookieUrl !== '') {
            return true;
        }

        $dpoInfo  = (bool) $this->scopeConfig->getValue(self::PATH_DPO_INFO, $scope);
        $dpoName  = (string) $this->scopeConfig->getValue(self::PATH_DPO_NAME, $scope);
        $dpoEmail = (string) $this->scopeConfig->getValue(self::PATH_DPO_EMAIL, $scope);
        $dpoPhone = (string) $this->scopeConfig->getValue(self::PATH_DPO_PHONE, $scope);

        if ($dpoInfo && ($dpoName !== '' || $dpoEmail !== '' || $dpoPhone !== '')) {
            return true;
        }

        return false;
    }
}
